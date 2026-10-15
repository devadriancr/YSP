<?php

namespace App\Support;

use MaxMind\Db\Reader;
use Throwable;

class IpLocation
{
    public static function databasePath(): string
    {
        return storage_path('app/geoip/dbip-city-lite.mmdb');
    }

    public static function lookup(?string $ip): ?string
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        if (! is_file(self::databasePath())) {
            return null;
        }

        try {
            $reader = new Reader(self::databasePath());
            $record = $reader->get($ip);
            $reader->close();
        } catch (Throwable) {
            return null;
        }

        $parts = array_filter([
            $record['city']['names']['en'] ?? null,
            $record['subdivisions'][0]['names']['en'] ?? null,
            $record['country']['names']['en'] ?? null,
        ]);

        return $parts === [] ? null : implode(', ', array_unique($parts));
    }
}
