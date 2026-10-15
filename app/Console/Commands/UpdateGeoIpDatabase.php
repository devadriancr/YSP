<?php

namespace App\Console\Commands;

use App\Support\IpLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateGeoIpDatabase extends Command
{
    protected $signature = 'geoip:update';

    protected $description = 'Descarga la base de datos gratuita DB-IP Lite (ciudad) para geolocalizar IPs';

    public function handle(): int
    {
        $target = IpLocation::databasePath();
        @mkdir(dirname($target), 0755, true);

        foreach ([now(), now()->subMonth()] as $date) {
            $url = 'https://download.db-ip.com/free/dbip-city-lite-'.$date->format('Y-m').'.mmdb.gz';
            $gz = $target.'.gz';

            $this->info("Descargando {$url}");
            $response = Http::timeout(900)->sink($gz)->get($url);

            if ($response->successful()) {
                $this->decompress($gz, $target.'.tmp');
                rename($target.'.tmp', $target);
                @unlink($gz);
                $this->info('Base de datos actualizada: '.$target);

                return self::SUCCESS;
            }

            @unlink($gz);
        }

        $this->error('No se pudo descargar la base de datos de DB-IP.');

        return self::FAILURE;
    }

    private function decompress(string $gzPath, string $outPath): void
    {
        $in = gzopen($gzPath, 'rb');
        $out = fopen($outPath, 'wb');

        while (! gzeof($in)) {
            fwrite($out, gzread($in, 1 << 20));
        }

        gzclose($in);
        fclose($out);
    }
}
