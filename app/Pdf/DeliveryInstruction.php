<?php

namespace App\Pdf;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Delivery instruction (YPU204B): one row per requirement, grouped by part,
 * with the page header and the column titles repeated on every page.
 */
final class DeliveryInstruction extends CpdfDocument
{
    private const TABLE_TOP = 96.3;

    // Row heights as dompdf laid them out: column titles and data rows
    private const HEADER_ROW = 12.98;

    private const ROW = 12.963;

    // Rows end before the 30pt bottom margin
    private const TABLE_BOTTOM = 762.0;

    private const FONT = 8.5;

    private const GRID = 0.6;

    // Column edges: Parts No, SNP, Order No, Delivery Date, Shift, Quantity, Box Qty
    private const COLUMNS = [36.3, 168.0, 215.3, 319.2, 413.7, 452.5, 515.3, 575.7];

    private const TITLES = ['Parts No', '. SNP', 'Order No', 'Delivery Date', 'Shift', 'Quantity', 'Box Qty'];

    private int $page = 0;

    private float $rowTop = 0;

    private array $header;

    /**
     * @param  Collection<string, Collection<int, array<string, mixed>>>  $parts  from App\Services\SupplierDocuments::deliveryInstruction()
     */
    public function render(Collection $parts, int $vendor, string $vendorName, CarbonInterface $issued, string $company): string
    {
        $this->start();
        $this->header = compact('vendor', 'vendorName', 'issued', 'company');
        $this->newPage();

        foreach ($parts->values() as $index => $rows) {
            if ($index > 0) {
                $this->row([]); // blank row between parts
            }
            foreach ($rows->values() as $i => $row) {
                $this->row([
                    $i === 0 ? $row['part'] : '',
                    $i === 0 ? (string) $row['snp'] : '',
                    $row['order'],
                    $row['date'],
                    '',
                    number_format($row['quantity']),
                    (string) $row['boxes'],
                ]);
            }
        }

        $this->closeGrid();

        return $this->pdf->output();
    }

    private function newPage(): void
    {
        if ($this->page > 0) {
            $this->closeGrid();
            $this->pdf->newPage();
        }
        $this->page++;

        ['vendor' => $vendor, 'vendorName' => $vendorName, 'issued' => $issued, 'company' => $company] = $this->header;
        $this->text(36, 18, 7.5, 'YPU204B');
        $this->text(36, 18, 11, 'DELIVERY INSTRUCTION (Weekly)', width: 540);
        $this->text(36, 18, 9, "P.  {$this->page}", width: 540, align: 'right');
        $this->text(36, 44, 9, "Supplier  {$vendor}   {$vendorName}");
        $this->text(36, 40, 9, 'Issued date   '.$issued->format('d/m/Y'), width: 528, align: 'right');
        $this->text(36, 58, 9, $company, width: 540, align: 'right');

        $this->rowTop = self::TABLE_TOP;
        $this->row(self::TITLES, header: true);
    }

    /**
     * @param  array<int, string>  $cells  one per column; empty array = blank row
     */
    private function row(array $cells, bool $header = false): void
    {
        $height = $header ? self::HEADER_ROW : self::ROW;
        if (! $header && $this->rowTop + $height > self::TABLE_BOTTOM) {
            $this->newPage();
        }

        $this->line(36.0, $this->rowTop, 576.0, $this->rowTop, self::GRID);

        $top = $this->rowTop + 1.3; // border + padding
        foreach ($cells as $column => $value) {
            $left = self::COLUMNS[$column];
            $width = self::COLUMNS[$column + 1] - $left;
            match (true) {
                $header => $this->text($left, $top, self::FONT, $value, width: $width),
                $column === 0 => $this->text($left + 3.3, $top, self::FONT, $value),
                $column === 5 => $this->text($left, $top, self::FONT, $value, width: $width - 8.3, align: 'right'),
                default => $this->text($left, $top, self::FONT, $value, width: $width),
            };
        }

        $this->rowTop += $height;
    }

    // Bottom line and column lines of the rows drawn on the current page
    private function closeGrid(): void
    {
        $this->line(36.0, $this->rowTop, 576.0, $this->rowTop, self::GRID);
        foreach (self::COLUMNS as $x) {
            $this->line($x, self::TABLE_TOP - 0.3, $x, $this->rowTop + 0.3, self::GRID);
        }
    }
}
