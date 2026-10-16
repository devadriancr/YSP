<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * Geometry of the QR and Code 39 barcodes: the black rectangles to draw, in
 * modules. App\Pdf\IdentificationCards draws them as vector shapes, so they
 * print sharp at any size and the PDF is generated quickly.
 */
final class Barcodes
{
    // Code 39: 9 elements per character (bar, space, bar...), w = wide, n = narrow
    private const CODE39 = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', '*' => 'nwnnwnwnn',
    ];

    /**
     * Modules per side and dark rectangles [x, y, width, height] counted from the top left.
     *
     * @return array{size: int, rects: list<array{int, int, int, int}>}
     */
    public static function qr(string $text): array
    {
        $matrix = Encoder::encode($text, ErrorCorrectionLevel::M(), 'ISO-8859-1', null, false)->getMatrix();
        $size = $matrix->getWidth();
        $rects = [];

        for ($y = 0; $y < $size; $y++) {
            $x = 0;
            while ($x < $size) {
                if ($matrix->get($x, $y) !== 1) {
                    $x++;

                    continue;
                }
                // Join consecutive dark modules in one rectangle
                $start = $x;
                while ($x < $size && $matrix->get($x, $y) === 1) {
                    $x++;
                }
                $rects[] = [$start, $y, $x - $start, 1];
            }
        }

        return ['size' => $size, 'rects' => $rects];
    }

    /**
     * Code 39 with start/stop characters; only digits are supported.
     * Total width in narrow units and the bars [x, width].
     *
     * @return array{width: int, bars: list<array{int, int}>}
     */
    public static function code39(string $digits): array
    {
        $chars = '*'.preg_replace('/\D/', '', $digits).'*';
        $narrow = 1;
        $wide = 3;
        $x = 0;
        $bars = [];

        foreach (str_split($chars) as $char) {
            foreach (str_split(self::CODE39[$char]) as $i => $element) {
                $width = $element === 'w' ? $wide : $narrow;
                if ($i % 2 === 0) {
                    $bars[] = [$x, $width];
                }
                $x += $width;
            }
            $x += $narrow; // gap between characters
        }

        // The SVG version kept the trailing gap in its width; same proportions on the label
        return ['width' => $x, 'bars' => $bars];
    }
}
