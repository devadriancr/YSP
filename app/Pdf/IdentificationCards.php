<?php

namespace App\Pdf;

use App\Support\Barcodes;
use Illuminate\Support\Collection;

/**
 * Identification cards (YPU202B), laid out like Infor's: 3 per letter page, one per box.
 * Positions and sizes were measured on Infor's PDF; its font (MS UI Gothic) is not
 * available, so the text is Helvetica centered where Infor's is. The QR and the
 * order barcode are vector shapes, so they print sharp at any size.
 */
final class IdentificationCards extends CpdfDocument
{
    // Top of each card relative to the first one (Infor's third card sits 3.7pt lower than an even pitch)
    private const CARD_TOPS = [0.0, 261.0, 525.7];

    // Dashed separators between the cards, from the top of the page
    private const SEPARATORS = [263.2, 524.9];

    // Infor prints only the first characters of these fields
    private const PACK_LENGTH = 3;

    private const NEXT_PROCESS_LENGTH = 8;

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
            $this->offset = 0;
            $this->separators($page->count());
        }

        return $this->pdf->output();
    }

    private function card(array $label, string $company): void
    {
        // Captions: [center or left x, baseline, size, text, is centered]
        $captions = [
            [137.2, 31.7, 9, 'Delivery Date'], [247.7, 31.7, 9, 'Delivery Time'], [358.7, 31.7, 9, 'Warehouse'], [467.9, 31.7, 9, 'Next Process'],
            [116.5, 128.5, 9, 'Pack-C'], [272.4, 128.5, 9, 'SNP'], [430.7, 128.5, 9, 'SEQ'], [359.5, 178.7, 9, 'Order No.'], [446.0, 178.0, 9, 'Order Quantity'],
        ];
        foreach ($captions as [$center, $base, $size, $caption]) {
            $this->centered($center, $base, $size, $caption);
        }
        $this->left(58.5, 73.0, 9, 'Parts No.');
        $this->left(508.5, 73.0, 9, 'R/L');
        $this->left(520.5, 128.5, 9, 'Truck No.');
        $this->left(54.0, 178.7, 9.8, 'Parts Name');
        $this->left(54.0, 211.7, 9.8, 'Supplier');

        $this->centered(136.9, 54.0, 21.8, $label['delivery_date']);
        $this->centered(359.4, 53.2, 21.8, $label['warehouse']);
        $this->centered(468.4, 53.2, 21.8, mb_substr((string) $label['next_process'], 0, self::NEXT_PROCESS_LENGTH));

        // Part number box with the R/L column on the right
        $this->frame(53.6, 61.1, 501.0, 55.5, 0.72);
        $this->line(505.5, 61.5, 505.5, 116.3, 0.72);
        $this->centered(279.5, 109.0, $this->fit(36, $label['part'], 440), $label['part']);
        $this->centered(530.0, 98.0, 14, $label['rl']);

        $this->centered(116.5, 155.1, 27.8, mb_substr((string) $label['pack'], 0, self::PACK_LENGTH));
        $this->centered(272.7, 155.1, 27.8, (string) $label['snp']);
        $this->centered(431.1, 155.1, 27.8, $label['seq']);
        $this->centered(538.8, 155.1, 27.8, $label['truck']);

        $this->left(54.0, 196.0, 14.3, $label['description'], 265);
        $this->centered(360.0, 194.8, 12, $label['order']);
        $this->centered(446.3, 194.8, 12, number_format($label['order_quantity']));
        $this->qr($label['qr'], 515.9, 167.3, 45.4);

        $this->left(100.5, 212.8, 12, (string) $label['vendor']);
        $this->left(158.3, 214.0, 14.3, $label['vendor_name'], 205);

        $this->frame(51.8, 220.5, 168.7, 18.8, 0.75);
        $this->left(57.0, 235.6, 15.8, 'IDENTIFICATION CARD', 160);
        $this->left(74.3, 254.1, 12, $company, 280);

        $this->barcode($label['order'], 376.3, 222.1, 182.8, 30.6);
        foreach (str_split($label['order']) as $i => $digit) {
            $this->centered(383.9 + 15.24 * $i, 259.3, 8.3, $digit);
        }
    }

    // Text whose baseline is at $base, centered on $x
    private function centered(float $x, float $base, float $size, ?string $text): void
    {
        $this->text($x - 150, $base - 0.978 * $size, $size, $text, width: 300);
    }

    // Text whose baseline is at $base, starting at $x
    private function left(float $x, float $base, float $size, ?string $text, ?float $maxWidth = null): void
    {
        $size = $maxWidth === null ? $size : $this->fit($size, (string) $text, $maxWidth);
        $this->text($x, $base - 0.978 * $size, $size, $text);
    }

    // Font size that makes $text fit in $width, never above $size
    private function fit(float $size, string $text, float $width): float
    {
        $natural = $this->pdf->getTextWidth($size, $text);

        return $natural > $width ? $size * $width / $natural : $size;
    }

    // Dashed line between the cards of a page
    private function separators(int $cards): void
    {
        $this->pdf->setStrokeColor([0, 0, 0]);
        $this->pdf->setLineStyle(0.72, '', '', [1.44, 1.44]);
        foreach (array_slice(self::SEPARATORS, 0, $cards - 1) as $top) {
            $this->pdf->line(52.0, self::PAGE_HEIGHT - $top, 571.0, self::PAGE_HEIGHT - $top);
        }
        $this->pdf->setLineStyle(0.72);
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
