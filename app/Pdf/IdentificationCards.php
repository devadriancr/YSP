<?php

namespace App\Pdf;

use App\Support\Barcodes;
use Illuminate\Support\Collection;

/**
 * Identification cards (YPU202B): 3 per letter page, one per box. The QR and
 * the order barcode are vector shapes, so they print sharp at any size.
 */
final class IdentificationCards extends CpdfDocument
{
    // Top of each card; the 2nd and 3rd sit under a 0.8pt dashed separator
    private const CARD_TOPS = [6.0, 264.8, 522.8];

    private const SEPARATORS = [264.4, 522.4];

    /**
     * @param  Collection<int, array<string, mixed>>  $labels  from App\Services\SupplierDocuments::labels()
     */
    public function render(Collection $labels, string $company): string
    {
        $this->start();

        foreach ($labels->values()->chunk(3) as $index => $page) {
            if ($index > 0) {
                $this->pdf->newPage();
            }
            foreach ($page->values() as $position => $label) {
                $this->offset = self::CARD_TOPS[$position];
                $this->card($label, $company);
            }
            $this->separators($page->count());
        }

        return $this->pdf->output();
    }

    private function card(array $label, string $company): void
    {
        $captions = [
            [105, 12, 'Delivery Date'], [215, 12, 'Delivery Time'], [328, 12, 'Warehouse'], [430, 12, 'Next Process'],
            [54.8, 49.8, 'Parts No.'], [495.8, 49.8, 'R/L'],
            [100, 104, 'Pack-C'], [258, 104, 'SNP'], [410, 104, 'SEQ'], [505, 104, 'Truck No.'],
            [50, 154, 'Parts Name'], [330, 154, 'Order No.'], [405, 154, 'Order Quantity'],
        ];
        foreach ($captions as [$left, $top, $caption]) {
            $this->text($left, $top, 7, $caption);
        }

        $this->text(100, 20, 19, $label['delivery_date']);
        $this->text(330, 20, 19, $label['warehouse']);
        $this->text(410, 26, 10, $label['next_process']);

        // Part number box with the R/L column on the right
        $this->frame(50.4, 46.4, 490.8, 54.8);
        $this->line(492.4, 46.8, 492.4, 100.8);
        $this->text(50.8, 59.8, 30, $label['part'], width: 442);
        $this->text(492.8, 66.8, 14, $label['rl'], width: 48);

        $this->text(85, 110, 26, $label['pack']);
        $this->text(245, 110, 26, (string) $label['snp']);
        $this->text(370, 110, 26, $label['seq']);
        $this->text(505, 110, 26, $label['truck']);

        $this->text(50, 164, 11, $label['description']);
        $this->text(315, 164, 10, $label['order']);
        $this->text(405, 164, 10, number_format($label['order_quantity']), width: 60);
        $this->qr($label['qr'], 498, 146, 48);

        $this->text(50, 184, 8, "Supplier   {$label['vendor']}");
        $this->text(152, 182, 11, $label['vendor_name']);

        $this->frame(50.4, 207.4, 163.8, 13.8);
        $this->text(53.8, 207.8, 10, 'IDENTIFICATION CARD');
        $this->text(70, 226, 9, $company);

        $this->barcode($label['order'], 368, 200, 178, 26);
        $this->text(372, 228, 6, $label['order'], charSpacing: 7);
    }

    // Dashed line between the cards of a page
    private function separators(int $cards): void
    {
        $this->pdf->setStrokeColor([0.333, 0.333, 0.333]);
        $this->pdf->setLineStyle(0.8, '', '', [2.4]);
        foreach (array_slice(self::SEPARATORS, 0, $cards - 1) as $top) {
            $this->pdf->line(0, self::PAGE_HEIGHT - $top, self::PAGE_WIDTH, self::PAGE_HEIGHT - $top);
        }
        $this->pdf->setLineStyle(0.8);
    }

    private function qr(string $text, float $left, float $top, float $size): void
    {
        $code = Barcodes::qr($text);
        $module = $size / $code['size'];

        $this->pdf->setColor([0, 0, 0]);
        foreach ($code['rects'] as [$x, $y, $width, $height]) {
            $this->pdf->filledRectangle(
                $left + $x * $module,
                $this->y($top + ($y + $height) * $module),
                $width * $module,
                $height * $module,
            );
        }
    }

    private function barcode(string $digits, float $left, float $top, float $width, float $height): void
    {
        $code = Barcodes::code39($digits);
        $unit = $width / $code['width'];

        $this->pdf->setColor([0, 0, 0]);
        foreach ($code['bars'] as [$x, $barWidth]) {
            $this->pdf->filledRectangle($left + $x * $unit, $this->y($top + $height), $barWidth * $unit, $height);
        }
    }
}
