<?php

namespace App\Pdf;

use Dompdf\Cpdf;
use ReflectionClass;

/**
 * Base for the supplier documents drawn directly with Cpdf, the PDF engine
 * inside dompdf. Laying out HTML is what made the PDFs slow (40+ seconds for
 * a day of labels); drawing each text and line at its position takes about a
 * second. Positions are in points from the top left, like the former HTML views.
 */
abstract class CpdfDocument
{
    protected const PAGE_WIDTH = 612.0;

    protected const PAGE_HEIGHT = 792.0;

    // Baseline of a line of text: top + 0.978 x font size (Helvetica, normal line height)
    private const BASELINE = 0.978;

    protected Cpdf $pdf;

    // Top of the block being drawn (a label card); positions are relative to it
    protected float $offset = 0;

    protected function start(): void
    {
        $fonts = dirname((new ReflectionClass(Cpdf::class))->getFileName()).'/fonts';
        $cache = storage_path('fonts');
        if (! is_dir($cache)) {
            mkdir($cache, 0755, true);
        }

        $this->pdf = new Cpdf([0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT], false, $cache, sys_get_temp_dir());
        $this->pdf->selectFont("{$fonts}/Helvetica");
    }

    /**
     * Writes a line of text; with $width it is centered (or right aligned) inside that width.
     */
    protected function text(
        float $left,
        float $top,
        float $size,
        ?string $text,
        ?float $width = null,
        string $align = 'center',
        float $charSpacing = 0,
    ): void {
        if ($text === null || $text === '') {
            return;
        }
        if ($width !== null) {
            $free = $width - $this->pdf->getTextWidth($size, $text, 0, $charSpacing);
            $left += $align === 'right' ? $free : $free / 2;
        }

        $this->pdf->setColor([0, 0, 0]);
        $this->pdf->addText($left, $this->y($top + self::BASELINE * $size), $size, $text, 0, 0, $charSpacing);
    }

    protected function frame(float $left, float $top, float $width, float $height, float $line = 0.8): void
    {
        $this->pdf->setStrokeColor([0, 0, 0]);
        $this->pdf->setLineStyle($line);
        $this->pdf->rectangle($left, $this->y($top + $height), $width, $height);
    }

    protected function line(float $x1, float $top1, float $x2, float $top2, float $width = 0.8): void
    {
        $this->pdf->setStrokeColor([0, 0, 0]);
        $this->pdf->setLineStyle($width);
        $this->pdf->line($x1, $this->y($top1), $x2, $this->y($top2));
    }

    // Distance from the top of the current block to Cpdf's y (counted from the bottom of the page)
    protected function y(float $top): float
    {
        return self::PAGE_HEIGHT - ($this->offset + $top);
    }
}
