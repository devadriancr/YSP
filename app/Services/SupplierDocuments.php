<?php

namespace App\Services;

use App\Models\DeliveryRow;
use App\Models\PurchaseOrderLine;
use App\Models\Vendor;
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

    public function __construct(private Vendor $vendor) {}

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
        return $this->vendor->deliveryRows()
            ->with('partNumber')
            ->whereDate('due_date', '>=', $issued->toDateString())
            ->get()
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
     * PO lines of the vendor due between two days, sorted like YPU202B.
     */
    private function labelLines(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return $this->vendor->purchaseOrderLines()
            ->with('partNumber')
            ->whereDate('due_date', '>=', $from->toDateString())
            ->whereDate('due_date', '<=', $to->toDateString())
            ->get()
            ->sortBy([
                fn ($a, $b) => $a->due_date <=> $b->due_date,
                fn ($a, $b) => $a->partNumber->number <=> $b->partNumber->number,
                ['number', 'asc'],
                ['line', 'asc'],
            ])
            ->values();
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
