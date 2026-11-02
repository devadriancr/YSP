<?php

namespace App\Services;

use App\Models\DocumentChange;
use App\Models\Vendor;
use App\Support\PartNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Finds what Infor changed in a vendor's documents from today on.
 *
 * The portal and Infor are not linked: each sync replaces the vendor's data.
 * Before and after that, this class takes a "picture" of the quantity of each
 * part on each day and stores the differences. Days before today are left out
 * (they still update, they just are not reported), and so is the received
 * quantity: receiving is not a change to what was ordered.
 *
 * A picture is: ['labels' => ['2026-10-30' => ['BDTS-28-911A' => 425.0, ...]], 'di' => [...]],
 * with the part number as the documents print it.
 */
class DocumentChanges
{
    public const LABELS = 'labels';

    public const DELIVERY_INSTRUCTION = 'di';

    // A change older than this no longer marks the day in the calendar
    public const RECENT_DAYS = 7;

    // Changes of days this far in the past are deleted
    private const KEEP_DAYS = 45;

    /**
     * @return array{labels: array<string, array<string, float>>, di: array<string, array<string, float>>}
     */
    public function picture(Vendor $vendor, CarbonImmutable $from): array
    {
        return [
            self::LABELS => $this->quantities('purchase_order_lines', $vendor, $from),
            self::DELIVERY_INSTRUCTION => $this->quantities('delivery_rows', $vendor, $from),
        ];
    }

    /**
     * Compares two pictures and saves one change per document and day that differs.
     *
     * @return int how many document-days changed
     */
    public function record(Vendor $vendor, array $before, array $after, CarbonImmutable $detectedAt): int
    {
        $rows = [];
        foreach ([self::LABELS, self::DELIVERY_INSTRUCTION] as $document) {
            foreach ($this->diff($before[$document], $after[$document]) as $date => $parts) {
                $rows[] = [
                    'vendor_id' => $vendor->id,
                    'document' => $document,
                    'date' => $date,
                    'parts' => json_encode($parts),
                    'detected_at' => $detectedAt,
                ];
            }
        }

        DB::table('document_changes')->insert($rows);
        $vendor->documentChanges()->where('date', '<', $detectedAt->subDays(self::KEEP_DAYS)->toDateString())->delete();

        return count($rows);
    }

    /**
     * Parts whose quantity differs, by day. A part only in `after` is new (before null),
     * one only in `before` was removed (after null).
     *
     * @return array<string, list<array{part: string, before: float|null, after: float|null}>>
     */
    public function diff(array $before, array $after): array
    {
        $changes = [];
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $date) {
            $old = $before[$date] ?? [];
            $new = $after[$date] ?? [];
            $parts = [];
            foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $part) {
                $was = $old[$part] ?? null;
                $is = $new[$part] ?? null;
                if ($was === null || $is === null || abs($was - $is) > 0.0005) {
                    $parts[] = ['part' => (string) $part, 'before' => $was, 'after' => $is];
                }
            }
            if ($parts) {
                usort($parts, fn ($a, $b) => strcmp($a['part'], $b['part']));
                $changes[$date] = $parts;
            }
        }
        ksort($changes);

        return $changes;
    }

    /**
     * The latest change of each document and day from `from` to `to`.
     *
     * @return array<string, array<string, DocumentChange>> date => document => change
     */
    public function latest(Vendor $vendor, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $latest = [];
        $changes = $vendor->documentChanges()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->orderBy('detected_at')
            ->orderBy('id')
            ->get();

        foreach ($changes as $change) {
            $latest[$change->date->toDateString()][$change->document] = $change; // newer ones overwrite
        }

        return $latest;
    }

    /**
     * Total quantity per day and part, from `from` on.
     *
     * @return array<string, array<string, float>>
     */
    private function quantities(string $table, Vendor $vendor, CarbonImmutable $from): array
    {
        $rows = DB::table("{$table} as d")
            ->join('part_numbers as p', 'p.id', '=', 'd.part_number_id')
            ->where('d.vendor_id', $vendor->id)
            ->whereDate('d.due_date', '>=', $from->toDateString())
            ->groupBy('d.due_date', 'p.number', 'p.project')
            ->select('d.due_date', 'p.number', 'p.project', DB::raw('SUM(d.quantity) as quantity'))
            ->get();

        $picture = [];
        foreach ($rows as $row) {
            $part = PartNumber::format($row->number, $row->project);
            $date = substr((string) $row->due_date, 0, 10);
            $picture[$date][$part] = round(($picture[$date][$part] ?? 0) + (float) $row->quantity, 3);
        }

        return $picture;
    }
}
