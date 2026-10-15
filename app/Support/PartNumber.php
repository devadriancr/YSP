<?php

namespace App\Support;

/**
 * Printed part number, same rule as YPU202B:
 * - Toyota (IREF04 8 / 11) and Tesla (3Y) parts, or short parts: as is
 * - Consignment parts (start with 9): XXXXX-XXXXXXXXXXXXXX
 * - Everything else: XXXX-XX-XXXXXXXXXXXX
 */
final class PartNumber
{
    private const AS_IS_MODELS = ['8', '11', '3Y'];

    public static function format(string $part, ?string $model = null): string
    {
        $part = rtrim($part);

        if (in_array(trim((string) $model), self::AS_IS_MODELS, true) || trim(substr($part, 8, 1)) === '') {
            return $part;
        }

        if (str_starts_with($part, '9')) {
            return substr($part, 0, 5).'-'.substr($part, 6, 14);
        }

        return substr($part, 0, 4).'-'.substr($part, 4, 2).'-'.substr($part, 6, 12);
    }
}
