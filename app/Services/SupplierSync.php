<?php

namespace App\Services;

use App\Models\SyncRun;
use App\Models\Vendor;
use App\Models\WorkCalendar;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Saves what the sync service sends. A vendor is replaced as a whole inside a
 * transaction: if anything fails, suppliers keep seeing the previous data.
 */
class SupplierSync
{
    private const CHUNK_SIZE = 500;

    // Every row gets all columns, so a field left out by the sender cannot misalign a bulk insert
    private const PART_DEFAULTS = ['description' => null, 'project' => null, 'standard_pack' => null, 'side' => null, 'next_process' => null];

    private const LINE_DEFAULTS = ['shift' => null, 'warehouse' => null];

    private const DELIVERY_DEFAULTS = ['number' => null, 'line' => null];

    /**
     * @param  array{vendor: array, part_numbers: array, purchase_order_lines: array, delivery_rows: array}  $data
     */
    public function vendor(int $number, array $data): SyncRun
    {
        $rows = count($data['part_numbers']) + count($data['purchase_order_lines']) + count($data['delivery_rows']);

        return $this->track('vendor', $rows, function (SyncRun $run) use ($number, $data) {
            $now = now();

            $vendor = Vendor::updateOrCreate(
                ['number' => $number],
                [...$data['vendor'], 'active' => true, 'synced_at' => $now],
            );
            $run->vendor()->associate($vendor);

            $vendor->purchaseOrderLines()->delete();
            $vendor->deliveryRows()->delete();
            $vendor->partNumbers()->delete();

            $this->insert('part_numbers', collect($data['part_numbers'])
                ->keyBy('number') // a repeated part keeps its last values
                ->map(fn (array $part) => [...self::PART_DEFAULTS, ...$part, 'vendor_id' => $vendor->id, 'created_at' => $now, 'updated_at' => $now])
                ->values()
                ->all());

            $partIds = $vendor->partNumbers()->pluck('id', 'number');

            $lines = array_map(fn (array $line) => [...self::LINE_DEFAULTS, ...$line], $data['purchase_order_lines']);
            $deliveries = array_map(fn (array $row) => [...self::DELIVERY_DEFAULTS, ...$row], $data['delivery_rows']);

            $this->insert('purchase_order_lines', $this->withPartIds($lines, $partIds, 'purchase_order_lines', $vendor->id, $now));
            $this->insert('delivery_rows', $this->withPartIds($deliveries, $partIds, 'delivery_rows', $vendor->id, $now));
        });
    }

    /**
     * @param  array<int, array{facility: string, work_center: int, year: int, days: string}>  $calendars
     */
    public function workCalendars(array $calendars): SyncRun
    {
        return $this->track('work_calendars', count($calendars), function () use ($calendars) {
            foreach ($calendars as $calendar) {
                WorkCalendar::updateOrCreate(
                    ['facility' => $calendar['facility'], 'work_center' => $calendar['work_center'], 'year' => $calendar['year']],
                    ['days' => $calendar['days'], 'synced_at' => now()],
                );
            }
        });
    }

    /**
     * Runs the import in a transaction and leaves a SyncRun with the result.
     */
    private function track(string $type, int $rows, callable $import): SyncRun
    {
        $run = SyncRun::create([
            'type' => $type,
            'status' => SyncRun::RUNNING,
            'rows_received' => $rows,
            'started_at' => now(),
        ]);

        try {
            DB::transaction(fn () => $import($run));
        } catch (Throwable $e) {
            $run->vendor()->dissociate(); // the vendor may have been rolled back
            $run->update(['status' => SyncRun::FAILED, 'error' => $e->getMessage(), 'finished_at' => now()]);

            throw $e;
        }

        $run->update(['status' => SyncRun::DONE, 'finished_at' => now()]);

        return $run;
    }

    /**
     * Replaces the part number of each row with its id; a row whose part was
     * not sent is rejected so no document is left without its part.
     */
    private function withPartIds(array $rows, Collection $partIds, string $field, int $vendorId, CarbonInterface $now): array
    {
        return collect($rows)->map(function (array $row, int $index) use ($partIds, $field, $vendorId, $now) {
            $partId = $partIds[$row['part_number']] ?? throw ValidationException::withMessages([
                "{$field}.{$index}.part_number" => "The part {$row['part_number']} is not in part_numbers.",
            ]);

            unset($row['part_number']);

            return [...$row, 'vendor_id' => $vendorId, 'part_number_id' => $partId, 'created_at' => $now, 'updated_at' => $now];
        })->all();
    }

    private function insert(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
