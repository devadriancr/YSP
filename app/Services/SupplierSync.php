<?php

namespace App\Services;

use App\Models\DocumentSnapshot;
use App\Models\SyncRun;
use App\Models\UnreceivedLine;
use App\Models\Vendor;
use App\Models\WorkCalendar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Saves what the sync service sends. A vendor is replaced as a whole inside a
 * transaction: if anything fails, suppliers keep seeing the previous data.
 * What changed from today on is recorded by DocumentChanges.
 */
class SupplierSync
{
    public function __construct(private DocumentChanges $changes) {}

    private const CHUNK_SIZE = 500;

    // Every row gets all columns, so a field left out by the sender cannot misalign a bulk insert
    private const PART_DEFAULTS = ['description' => null, 'project' => null, 'standard_pack' => null, 'side' => null, 'next_process' => null];

    private const LINE_DEFAULTS = ['received_quantity' => 0, 'shift' => null, 'warehouse' => null];

    private const DELIVERY_DEFAULTS = ['number' => null, 'line' => null];

    private const UNRECEIVED_DEFAULTS = ['item_class' => '', 'received_quantity' => 0,'material' => null, 'symbol' => null, 'thickness' => 0, 'width' => 0, 'length' => 0];

    /**
     * @param  array{vendor: array, part_numbers: array, purchase_order_lines: array, delivery_rows: array, unreceived_lines?: array}  $data
     */
    public function vendor(int $number, array $data): SyncRun
    {
        // An older supply-relay does not send the list: that is not the same as an empty one
        $sentUnreceived = isset($data['unreceived_lines']);
        $data['unreceived_lines'] ??= [];
        $rows =count($data['part_numbers']) + count($data['purchase_order_lines']) + count($data['delivery_rows']) + count($data['unreceived_lines']);

        return $this->track('vendor', $rows, function (SyncRun $run) use ($number, $data, $sentUnreceived) {
            $now = now();
            $today = CarbonImmutable::today();

            // The first load of a vendor has nothing to compare with
            $previous = Vendor::firstWhere('number', $number);
            $before = $previous?->synced_at ? $this->changes->picture($previous, $today) : null;
            $beforeUnreceived = $previous?->unreceived_synced_at ? $this->changes->unreceivedPicture($previous) : null;

            $vendor = Vendor::updateOrCreate(
                ['number' => $number],
                [...$data['vendor'], 'active' => true, 'synced_at' => $now, 'unreceived_synced_at' => $sentUnreceived ? $now : null],
            );
            $run->vendor()->associate($vendor);

            $vendor->purchaseOrderLines()->delete();
            $vendor->deliveryRows()->delete();
            $vendor->unreceivedLines()->delete();
            $vendor->partNumbers()->delete();

            $this->insert('part_numbers', collect($data['part_numbers'])
                ->keyBy('number') // a repeated part keeps its last values
                ->map(fn (array $part) => [...self::PART_DEFAULTS, ...$part, 'vendor_id' => $vendor->id, 'created_at' => $now, 'updated_at' => $now])
                ->values()
                ->all());

            $partIds = $vendor->partNumbers()->pluck('id', 'number');

            $lines = array_map(fn (array $line) => [...self::LINE_DEFAULTS, ...$line], $data['purchase_order_lines']);
            $deliveries = $this->withoutEarlyForecasts(array_map(fn (array $row) => [...self::DELIVERY_DEFAULTS, ...$row], $data['delivery_rows']));

            $this->insert('purchase_order_lines', $this->withPartIds($lines, $partIds, 'purchase_order_lines', $vendor->id, $now));
            $this->insert('delivery_rows', $this->withPartIds($deliveries, $partIds, 'delivery_rows', $vendor->id, $now));

            // Only the last year is kept; a null size from Infor takes the default: those columns are not nullable
            $since = UnreceivedLine::since()->toDateString();
            $unreceived = collect($data['unreceived_lines'])
                ->filter(fn (array $row) => $row['due_date'] >= $since)
                ->map(fn (array $row) => [...self::UNRECEIVED_DEFAULTS, ...array_filter($row, fn ($value) => $value !== null)])
                ->all();
            $this->insert('unreceived_lines', $this->withPartIds($unreceived, $partIds, 'unreceived_lines', $vendor->id, $now));
            // Lines of vendors that were not synced since they aged past the year
            UnreceivedLine::where('due_date', '<', $since)->delete();

            if ($beforeUnreceived !== null && $sentUnreceived) {
                $this->changes->recordUnreceived($vendor, $beforeUnreceived, $this->changes->unreceivedPicture($vendor), $now->toImmutable());
            }

            $changed = $before === null ? 0 : $this->changes->record($vendor, $before, $this->changes->picture($vendor, $today), $now->toImmutable());

            // A snapshot after every change (and the first one, so the first change has an "original" to compare with)
            if ($changed > 0 || ! $vendor->documentSnapshots()->exists()) {
                $snapshot = $this->snapshot($vendor, $today, $now->toImmutable());
                $vendor->documentChanges()->whereNull('snapshot_id')->where('detected_at', '>=', $now->startOfSecond())->update(['snapshot_id' => $snapshot->id]);
            }
        });
    }

    // Kept this long so a supplier can show what it was given before a change
    private const SNAPSHOT_KEEP_DAYS = 60;

    /**
     * The lines and rows that can still be downloaded (today and the week before), as they are now.
     */
    private function snapshot(Vendor $vendor, CarbonImmutable $today, CarbonImmutable $at): DocumentSnapshot
    {
        $from = $today->subWeek()->toDateString();
        $lines = $vendor->purchaseOrderLines()->with('partNumber')->whereDate('due_date', '>=', $from)->get();
        $rows = $vendor->deliveryRows()->with('partNumber')->whereDate('due_date', '>=', $from)->get();

        $vendor->documentSnapshots()->where('taken_at', '<', $at->subDays(self::SNAPSHOT_KEEP_DAYS))->delete();

        return $vendor->documentSnapshots()->create(['taken_at' => $at, 'payload' => DocumentSnapshot::encode($lines, $rows)]);
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

    /**
     * YPU204B1 reads the forecasts (FR) from a start date the planner types in (STRDAT), so
     * the weeks still covered by firm planned orders (FP) show no forecast. Until that rule
     * is defined, the start is the day after the vendor's last FP, which is what Infor printed
     * (vendor 410010 on 2026-11-10: FP up to 12-04, FR from 12-07).
     */
    private function withoutEarlyForecasts(array $rows): array
    {
        $lastFirm = collect($rows)->where('origin', 'FP')->max('due_date');

        // shortcut: a vendor without FP keeps all its forecasts; Infor would cut them at STRDAT
        if ($lastFirm === null) {
            return $rows;
        }

        return array_values(array_filter($rows, fn (array $row) => $row['origin'] !== 'FR' || $row['due_date'] > $lastFirm));
    }

    private function insert(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        // SQL Server takes at most 2100 parameters per statement
        $size = min(self::CHUNK_SIZE, intdiv(2000, count(reset($rows))));

        foreach (array_chunk($rows, $size) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
