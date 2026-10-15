<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * QR and Code 39 barcodes as SVG data URIs, ready for an <img> in a PDF view.
 * Built from plain rectangles so dompdf renders them reliably.
 */
final class Barcodes
{
    // Code 39: 9 elements per character (bar, space, bar...), w = wide, n = narrow
    private const CODE39 = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', '*' => 'nwnnwnwnn',
    ];

    public static function qr(string $text): string
    {
        $matrix = Encoder::encode($text, ErrorCorrectionLevel::M(), 'ISO-8859-1', null, false)->getMatrix();
        $size = $matrix->getWidth();
        $rects = '';

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
                $rects .= sprintf('<rect x="%d" y="%d" width="%d" height="1"/>', $start, $y, $x - $start);
            }
        }

        return self::svg($size, $size, $rects);
    }

    /** Code 39 with start/stop characters; only digits are supported. */
    public static function code39(string $digits, int $height = 40): string
    {
        $chars = '*'.preg_replace('/\D/', '', $digits).'*';
        $narrow = 1;
        $wide = 3;
        $x = 0;
        $rects = '';

        foreach (str_split($chars) as $char) {
            foreach (str_split(self::CODE39[$char]) as $i => $element) {
                $width = $element === 'w' ? $wide : $narrow;
                if ($i % 2 === 0) {
                    $rects .= sprintf('<rect x="%d" y="0" width="%d" height="%d"/>', $x, $width, $height);
                }
                $x += $width;
            }
            $x += $narrow; // gap between characters
        }

        return self::svg($x, $height, $rects);
    }

    private static function svg(int $width, int $height, string $rects): string
    {
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" shape-rendering="crispEdges"><g fill="#000">%s</g></svg>',
            $width, $height, $width, $height, $rects
        );

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
