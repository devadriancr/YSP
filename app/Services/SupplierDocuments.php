<?php

namespace App\Services;

use App\Models\DeliveryRow;
use App\Models\DocumentSnapshot;
use App\Models\PartNumber as PartNumberModel;
use App\Models\PurchaseOrderLine;
use App\Models\UnreceivedLine;
use App\Models\Vendor;
use App\Support\InforCalendar;
use App\Support\PartNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Supplier documents built from the data the sync service copies from Infor LX:
 * - Identification cards (box labels), program YPU202B: the PO lines due that day.
 * - Delivery instruction (DI), programs YPU204B1 + YPU204B2: PO lines, firm
 *   planned orders and customer forecasts due from that day on.
 */
class SupplierDocuments
{
    // YPU202B: this vendor gets one label per PO line with the full quantity
    private const ONE_LABEL_PER_LINE_VENDOR = 101004;

    // With a snapshot the documents are built from how the data was then, not from the current data
    public function __construct(private Vendor $vendor, private ?DocumentSnapshot $snapshot = null) {}

    public function vendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * What each day has, for the calendar.
     *
     * @return array<string, array{labels: int, di: int}> keyed by Y-m-d
     */
    public function calendarSummary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = [];

        foreach ($this->labelLines($from, $to) as $line) {
            $date = $line->due_date->toDateString();
            $days[$date] ??= ['labels' => 0, 'di' => 0];
            $days[$date]['labels'] += $this->boxCount($line->quantity, $line->partNumber->standard_pack_quantity);

            // Pieces ordered for the day and how many the plant already received
            $days[$date]['receipt'] ??= ['ordered' => 0, 'received' => 0];
            $days[$date]['receipt']['ordered'] += $this->number($line->quantity);
            $days[$date]['receipt']['received'] += min($this->number($line->received_quantity), $this->number($line->quantity));
        }

        $rows = $this->vendor->deliveryRows()
            ->whereDate('due_date', '>=', $from->toDateString())
            ->whereDate('due_date', '<=', $to->toDateString())
            ->get(['due_date']);

        foreach ($rows as $row) {
            $date = $row->due_date->toDateString();
            $days[$date] ??= ['labels' => 0, 'di' => 0];
            $days[$date]['di']++;
        }

        ksort($days);

        return $days;
    }

    /**
     * Identification cards for the PO lines due on a day, one per box (YPU202B).
     */
    public function labels(CarbonImmutable $date): Collection
    {
        return $this->labelLines($date, $date)->flatMap(function (PurchaseOrderLine $line) {
            $part = $line->partNumber;
            $quantity = $this->number($line->quantity);
            $snp = $part->standard_pack_quantity;
            $shift = str_pad((string) $line->shift, 1);

            if ($snp > 0) {
                $total = intdiv($quantity, $snp) + ($quantity % $snp ? 1 : 0);
                $remainder = $quantity % $snp;
            } else {
                $total = 1;
                $remainder = 0;
                $snp = $quantity;
            }

            // Vendor 101004 gets only the last label ("TTT-TTT") with the full quantity in the QR
            $oneLabelPerLine = $this->vendor->number === self::ONE_LABEL_PER_LINE_VENDOR;
            $firstBox = $oneLabelPerLine ? $total : 1;

            $labels = [];
            for ($box = $firstBox; $box <= $total; $box++) {
                $boxQty = ($box === $total && $remainder !== 0) ? $remainder : $snp;
                $qrQty = $oneLabelPerLine ? $quantity : $boxQty;
                $order = $this->pad($line->number, 8).$this->pad($line->line, 4);

                $qr = '1'.$order
                    .$this->pad($box, 3).$this->pad($total, 3).$this->pad($qrQty, 6)
                    .str_pad(substr($part->number, 0, 35), 35)
                    .$line->due_date->format('dmY')
                    .$shift;

                $labels[] = [
                    'delivery_date' => $line->due_date->format('d/m').'-'.trim($shift),
                    'warehouse' => $line->warehouse,
                    'next_process' => $part->next_process ?? '',
                    'part' => PartNumber::format($part->number, $part->project),
                    'rl' => $part->side ?? '',
                    'pack' => $part->standard_pack,
                    'snp' => $boxQty,
                    'seq' => $this->pad($box, 3).'-'.$this->pad($total, 3),
                    'truck' => '',
                    'description' => $part->description,
                    'order' => $order,
                    'order_quantity' => $quantity,
                    'vendor' => $this->vendor->number,
                    'vendor_name' => $this->vendor->name,
                    // Content of the QR; App\Pdf\IdentificationCards draws it with the order barcode
                    'qr' => $qr,
                ];
            }

            return $labels;
        });
    }

    /**
     * Delivery instruction issued on a day: everything due from that day on (YPU204B1/B2).
     */
    public function deliveryInstruction(CarbonImmutable $issued): Collection
    {
        return $this->deliveryRowsFrom($issued)
            ->map(fn (DeliveryRow $row) => [
                'part' => PartNumber::format($row->partNumber->number, $row->partNumber->project),
                'raw_part' => $row->partNumber->number,
                'snp' => $row->partNumber->standard_pack_quantity ?: 1,
                'order' => $row->number ? $this->pad($row->number, 8).$this->pad($row->line, 4) : '',
                'origin' => $row->origin,
                'date' => $row->due_date->format('d/m/y'),
                'sort_date' => (int) $row->due_date->format('Ymd'),
                'quantity' => $this->number($row->quantity),
            ])
            ->map(fn (array $row) => $row + ['boxes' => max(1, (int) ceil($row['quantity'] / $row['snp']))])
            ->sortBy([['raw_part', 'asc'], ['sort_date', 'asc'], ['order', 'asc']])
            ->groupBy('raw_part');
    }

    /**
     * What the documents of a day contain, to look at in the calendar without the PDF.
     * No QR or barcodes: nothing that could be printed and used as a label.
     *
     * @return array{labels: list<array>, di: list<array>}
     */
    public function details(CarbonImmutable $date): array
    {
        $labels = $this->labelLines($date, $date)->map(function (PurchaseOrderLine $line) {
            $quantity = $this->number($line->quantity);
            $received = min($this->number($line->received_quantity), $quantity);

            return [
                'part' => PartNumber::format($line->partNumber->number, $line->partNumber->project),
                'description' => $line->partNumber->description,
                'order' => "{$line->number}-{$line->line}",
                'shift' => $line->shift,
                'warehouse' => $line->warehouse,
                'quantity' => $quantity,
                'received' => $received,
                'pending' => $quantity - $received,
                'boxes' => $this->boxCount($line->quantity, $line->partNumber->standard_pack_quantity),
            ];
        })->values();

        return [
            'labels' => $labels->all(),
            'shortages' => $this->shortages($labels),
            'di' => $this->deliveryInstructionDetails($date),
        ];
    }

    /**
     * Parts still missing on a day, the biggest first: one part may come in several PO lines.
     *
     * @return list<array{part: string, description: string|null, quantity: int, received: int, pending: int}>
     */
    private function shortages(Collection $labels): array
    {
        return $labels->groupBy('part')
            ->map(fn (Collection $lines, string $part) => [
                'part' => $part,
                'description' => $lines->first()['description'],
                'quantity' => $lines->sum('quantity'),
                'received' => $lines->sum('received'),
                'pending' => $lines->sum('pending'),
            ])
            ->filter(fn (array $part) => $part['pending'] > 0)
            ->sortBy([['pending', 'desc'], ['part', 'asc']])
            ->values()
            ->all();
    }

    /**
     * Overdue PO lines not fully received, the most late first, split like the Infor
     * reports: parts (YPU705B) and raw material (YPU715B). Days late are calendar days, like YPU705B.
     *
     * @return array{parts: list<array>, materials: list<array>}
     */
    public function unreceived(CarbonImmutable $today): array
    {
        $lines = $this->vendor->unreceivedLines()
            ->with('partNumber')
            ->where('order_type', 'PO') // Infor HPOL86: the PZ lines are not on YPU705B/715B
            ->whereDate('due_date', '<', $today->toDateString())
            ->whereDate('due_date', '>=', UnreceivedLine::since()->toDateString())
            ->get()
            ->filter(fn (UnreceivedLine $line) => (float) $line->received_quantity < (float) $line->quantity);

        if ($lines->isEmpty()) {
            return ['parts' => [], 'materials' => []];
        }

        $rows = $lines->map(fn (UnreceivedLine $line) => [
            'raw_material' => $line->isRawMaterial(),
            // As Infor keeps it and prints it on YPU705B/715B (DGJ428B18A), without the label format
            'part' => $line->partNumber->number,
            'description' => $line->partNumber->description,
            'order' => "{$line->number}-{$line->line}",
            // Order No. of the Infor reports: 8 digits of order and 4 of line
            'order_no' => $this->pad($line->number, 8).$this->pad($line->line, 4),
            'due_date' => $line->due_date->toDateString(),
            'quantity' => (float) $line->quantity,
            'pending' => (float) $line->quantity - (float) $line->received_quantity,
            'days_late' => (int) $line->due_date->startOfDay()->diffInDays($today->startOfDay()),
            'unit' => $line->unit(),
            'material' => $line->material,
            'symbol' => $line->symbol,
            'thickness' => $line->thickness,
            'width' => $line->width,
            'length' => $line->length,
        ])->sortBy([['days_late', 'desc'], ['part', 'asc'], ['order', 'asc']]);

        [$materials, $parts] = $rows->partition(fn (array $row) => $row['raw_material']);

        return ['parts' => $parts->values()->all(), 'materials' => $materials->values()->all()];
    }

    /**
     * Delivery instruction issued on a day grouped by part, with the same rows and boxes
     * as the PDF: for the day modal and the Excel download.
     *
     * @return list<array{part: string, description: ?string, snp: int, quantity: int, boxes: int, rows: list<array>}>
     */
    public function deliveryInstructionDetails(CarbonImmutable $date): array
    {
        return $this->deliveryRowsFrom($date)
            ->sortBy([
                fn ($a, $b) => $a->partNumber->number <=> $b->partNumber->number,
                fn ($a, $b) => $a->due_date <=> $b->due_date,
                ['number', 'asc'],
                ['line', 'asc'],
            ])
            ->groupBy(fn (DeliveryRow $row) => $row->partNumber->number)
            ->map(function (Collection $rows) {
                $part = $rows->first()->partNumber;
                $snp = $part->standard_pack_quantity ?: 1;
                $items = $rows->map(function (DeliveryRow $row) use ($snp) {
                    $quantity = $this->number($row->quantity);

                    return [
                        'date' => $row->due_date->toDateString(),
                        // FP firm (planned), FR forecast; anything else comes from a purchase order
                        'origin' => in_array($row->origin, ['FP', 'FR'], true) ? $row->origin : 'PO',
                        'order' => $row->number ? "{$row->number}-{$row->line}" : null,
                        'quantity' => $quantity,
                        'boxes' => max(1, (int) ceil($quantity / $snp)),
                    ];
                })->values();

                return [
                    'part' => PartNumber::format($part->number, $part->project),
                    'description' => $part->description,
                    'snp' => $snp,
                    'quantity' => $items->sum('quantity'),
                    'boxes' => $items->sum('boxes'),
                    'rows' => $items->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Fingerprint of the identification cards of each day: changes when Infor changes
     * anything printed on them (quantity, date, shift, part data...).
     *
     * @param  list<string>  $dates  Y-m-d
     * @return array<string, string> Y-m-d => sha256
     */
    public function labelFingerprints(array $dates): array
    {
        if ($dates === []) {
            return [];
        }

        $byDay = $this->labelLines(CarbonImmutable::parse(min($dates)), CarbonImmutable::parse(max($dates)))
            ->groupBy(fn (PurchaseOrderLine $line) => $line->due_date->toDateString());

        $fingerprints = [];
        foreach ($dates as $date) {
            $rows = ($byDay[$date] ?? collect())->map(fn (PurchaseOrderLine $line) => implode('|', [
                ...$this->partFields($line->partNumber),
                $line->number, $line->line, $this->number($line->quantity), $line->shift, $line->warehouse,
            ]))->sort()->values()->all();

            $fingerprints[$date] = hash('sha256', implode("\n", $rows));
        }

        return $fingerprints;
    }

    /**
     * Fingerprint of the delivery instruction issued each day. That document lists
     * everything due from that day on, so a change to any later row changes it too.
     *
     * @param  list<string>  $dates  Y-m-d
     * @return array<string, string> Y-m-d => sha256
     */
    public function deliveryInstructionFingerprints(array $dates): array
    {
        if ($dates === []) {
            return [];
        }

        // One row per text "date|fields", sorted: rows from a day on are the tail of the list
        $rows = $this->vendor->deliveryRows()
            ->with('partNumber')
            ->whereDate('due_date', '>=', min($dates))
            ->get()
            ->map(fn (DeliveryRow $row) => [
                'date' => $row->due_date->toDateString(),
                'text' => implode('|', [
                    $row->due_date->toDateString(),
                    ...$this->partFields($row->partNumber),
                    $row->origin, $row->number, $row->line, $this->number($row->quantity),
                ]),
            ])
            ->sortBy('text')
            ->values()
            ->all();

        // Chained from the end: chain[i] covers rows i..last, so each day reads its tail in one step
        $chain = [count($rows) => hash('sha256', '')];
        for ($i = count($rows) - 1; $i >= 0; $i--) {
            $chain[$i] = hash('sha256', $rows[$i]['text']."\n".$chain[$i + 1]);
        }

        $fingerprints = [];
        foreach ($dates as $date) {
            $first = count($rows);
            foreach ($rows as $i => $row) {
                if ($row['date'] >= $date) {
                    $first = $i;
                    break;
                }
            }
            $fingerprints[$date] = $chain[$first];
        }

        return $fingerprints;
    }

    // Part data printed on both documents
    private function partFields(?PartNumberModel $part): array
    {
        return [$part?->number, $part?->project, $part?->description, $part?->standard_pack,
            $part?->standard_pack_quantity, $part?->side, $part?->next_process];
    }

    /**
     * PO lines of the vendor due between two days, sorted like YPU202B.
     */
    private function labelLines(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return $this->purchaseOrderLinesBetween($from, $to)
            ->sortBy([
                fn ($a, $b) => $a->due_date <=> $b->due_date,
                fn ($a, $b) => $a->partNumber->number <=> $b->partNumber->number,
                ['number', 'asc'],
                ['line', 'asc'],
            ])
            ->values();
    }

    /**
     * @return Collection<int, PurchaseOrderLine> with partNumber
     */
    private function purchaseOrderLinesBetween(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        if ($this->snapshot) {
            return $this->snapshot->lines()->filter(fn ($line) => $line->due_date->between($from, $to));
        }

        return $this->vendor->purchaseOrderLines()
            ->with('partNumber')
            ->whereDate('due_date', '>=', $from->toDateString())
            ->whereDate('due_date', '<=', $to->toDateString())
            ->get();
    }

    /**
     * @return Collection<int, DeliveryRow> with partNumber
     */
    private function deliveryRowsFrom(CarbonImmutable $date): Collection
    {
        if ($this->snapshot) {
            return $this->snapshot->rows()->filter(fn ($row) => $row->due_date->gte($date));
        }

        return $this->vendor->deliveryRows()
            ->with('partNumber')
            ->whereDate('due_date', '>=', $date->toDateString())
            ->get();
    }

    private function boxCount(mixed $quantity, int $snp): int
    {
        return $snp > 0 ? (int) ceil($this->number($quantity) / $snp) : 1;
    }

    private function number(mixed $value): int
    {
        return (int) round((float) $value);
    }

    private function pad(mixed $number, int $length): string
    {
        return substr(str_pad((string) $this->number($number), $length, '0', STR_PAD_LEFT), -$length);
    }
}
