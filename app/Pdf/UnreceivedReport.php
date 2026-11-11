<?php

namespace App\Pdf;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Unreceived reports as Infor prints them: YPU705B (parts, portrait) and
 * YPU715B (raw material, landscape). The grid fills every page down to the
 * bottom, and a vendor without lines gets one "NO DATA" row.
 */
final class UnreceivedReport extends CpdfDocument
{
    private const FONT = 7.5;

    private const GRID = 0.6;

    // Measured on the Infor PDFs: column edges, table top and bottom, row height
    private const LAYOUTS = [
        'parts' => [
            'program' => 'YPU705B',
            'title' => 'Procurement parts unreceived report',
            'landscape' => false,
            'columns' => [14.4, 94.3, 292.3, 363.6, 442.8, 522.7, 601.2],
            'titles' => ['Order No.', 'Parts No.', 'Due Date', 'Quantity ordered', 'Unreceived quantity', 'Unreceived days'],
            'align' => ['center', 'left', 'center', 'right', 'right', 'center'],
            'top' => 103.0,
            'bottom' => 741.0,
            'row' => 12.8,
        ],
        'materials' => [
            'program' => 'YPU715B',
            'title' => 'Raw material unreceived report',
            'landscape' => true,
            'columns' => [14.1, 68.0, 187.2, 237.9, 286.0, 370.6, 414.2, 456.5, 499.4, 556.6, 618.8, 698.3, 754.7, 777.2],
            'titles' => ['Order No.', 'Parts No.', 'Material code', 'Mother M', 'Material symbol', 'Thickness', 'Width', 'Length',
                'Due Date', 'Quantity ordered', 'Unreceived quantity', 'Unreceived day', 'U/M'],
            'align' => ['left', 'left', 'center', 'center', 'center', 'right', 'right', 'right', 'center', 'right', 'right', 'center', 'center'],
            'top' => 104.0,
            'bottom' => 585.0,
            'row' => 12.2,
        ],
    ];

    private array $layout;

    /**
     * @param  'parts'|'materials'  $type
     * @param  list<array<string, mixed>>  $rows  from App\Services\SupplierDocuments::unreceived()
     */
    public function render(string $type, array $rows, int $vendor, string $vendorName, CarbonInterface $issued, string $company): string
    {
        $this->layout = self::LAYOUTS[$type];
        $this->start($this->layout['landscape']);

        // Order No. like the Infor report, which reads the lines by order
        usort($rows, fn (array $a, array $b) => $a['order_no'] <=> $b['order_no']);
        $cells = array_map(fn (array $row) => $this->cells($type, $row), $rows) ?: [$this->noData($type)];

        $perPage = (int) floor(($this->layout['bottom'] - $this->layout['top']) / $this->layout['row']) - 1;
        foreach (array_chunk($cells, $perPage) as $index => $page) {
            if ($index > 0) {
                $this->pdf->newPage();
            }
            $this->header($index + 1, $vendor, $vendorName, $issued, $company);
            $this->grid(array_pad($page, $perPage, []));
        }

        return $this->pdf->output();
    }

    /** @return list<string> */
    private function cells(string $type, array $row): array
    {
        $date = CarbonImmutable::parse($row['due_date'])->format('j/n/Y');
        $quantities = [self::number($row['quantity'], 3), self::number($row['pending'], 3), (string) $row['days_late']];

        if ($type === 'parts') {
            return [$row['order_no'], $row['part'], $date, ...$quantities];
        }

        return [
            $row['order_no'], $row['part'], $row['description'] ?? '', $row['material'] ?? '', $row['symbol'] ?? '',
            self::number($row['thickness'], 2), self::number($row['width'], 1), self::number($row['length'], 1),
            $date, ...$quantities, $row['unit'],
        ];
    }

    /** @return list<string> */
    private function noData(string $type): array
    {
        return $type === 'parts'
            ? ['', 'NO DATA', '', '.000', '.000', '']
            : ['', '*NO DATA', '', '', '', '', '', '', '', '.000', '.000', '', ''];
    }

    private function header(int $page, int $vendor, string $vendorName, CarbonInterface $issued, string $company): void
    {
        $left = $this->layout['columns'][0];
        $right = end($this->layout['columns']);
        $width = $right - $left;

        $this->text($left, 10, 8, "PGM-ID    {$this->layout['program']}");
        $this->text($left, 12, 8, "Page   {$page}", width: $width, align: 'right');
        $this->text($left, 24, 12, $this->layout['title'], width: $width);
        $this->text($left, 42, 9, 'Issued date        '.$issued->format('j/n/Y'), width: $width);
        $this->text($left, 68, 8, "Supplier Code       {$vendor}");
        $this->text($left, 82, 8, "Supplier Name      {$vendorName}");
        $this->text($left, 82, 8, $company, width: $width, align: 'right');
    }

    /** @param  list<list<string>>  $rows  [] is an empty row */
    private function grid(array $rows): void
    {
        ['columns' => $columns, 'top' => $top, 'row' => $height] = $this->layout;
        $left = $columns[0];
        $right = end($columns);

        $this->line($left, $top, $right, $top, self::GRID);
        $this->cellsAt($top, $this->layout['titles'], header: true);

        $rowTop = $top + $height;
        foreach ($rows as $cells) {
            $this->line($left, $rowTop, $right, $rowTop, self::GRID);
            $this->cellsAt($rowTop, $cells);
            $rowTop += $height;
        }
        $this->line($left, $rowTop, $right, $rowTop, self::GRID);

        foreach ($columns as $x) {
            $this->line($x, $top, $x, $rowTop, self::GRID);
        }
    }

    private function cellsAt(float $top, array $cells, bool $header = false): void
    {
        $columns = $this->layout['columns'];
        foreach ($cells as $i => $value) {
            // 1.5pt of padding: the 12 digits of Order No. just fit the raw material column
            $left = $columns[$i] + 1.5;
            $width = $columns[$i + 1] - $columns[$i] - 3;
            $align = $header ? 'center' : $this->layout['align'][$i];
            $text = $this->fit((string) $value, $width);

            $align === 'left'
                ? $this->text($left, $top + 2.2, self::FONT, $text)
                : $this->text($left, $top + 2.2, self::FONT, $text, width: $width, align: $align);
        }
    }

    // Cuts what does not fit in the cell, as the fixed width fields of Infor do
    private function fit(string $text, float $width): string
    {
        while ($text !== '' && $this->pdf->getTextWidth(self::FONT, $text) > $width) {
            $text = mb_substr($text, 0, -1);
        }

        return $text;
    }

    // Infor numbers: thousands separator and no zero before the point (.000, .80)
    private static function number(float|int $value, int $decimals): string
    {
        return preg_replace('/^(-?)0\./', '$1.', number_format((float) $value, $decimals));
    }
}
