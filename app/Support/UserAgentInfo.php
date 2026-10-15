<?php

namespace App\Support;

class UserAgentInfo
{
    public static function platform(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/iPhone|iPad|iOS/i', $agent) => 'iOS',
            (bool) preg_match('/Android/i', $agent) => 'Android',
            (bool) preg_match('/Windows/i', $agent) => 'Windows',
            (bool) preg_match('/Macintosh|Mac OS/i', $agent) => 'macOS',
            (bool) preg_match('/Linux/i', $agent) => 'Linux',
            default => 'Desconocido',
        };
    }

    public static function platformVersion(string $agent): ?string
    {
        if (preg_match('/Windows NT ([\d.]+)/i', $agent, $m)) {
            return match ($m[1]) {
                '10.0' => '10/11',
                '6.3' => '8.1',
                '6.2' => '8',
                '6.1' => '7',
                default => $m[1],
            };
        }

        if (preg_match('/Android ([\d.]+)/i', $agent, $m)) {
            return $m[1];
        }

        if (preg_match('/(?:iPhone )?OS ([\d_]+) like Mac OS X/i', $agent, $m)) {
            return str_replace('_', '.', $m[1]);
        }

        return null;
    }

    public static function browser(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/Edg/i', $agent) => 'Edge',
            (bool) preg_match('/OPR|Opera/i', $agent) => 'Opera',
            (bool) preg_match('/Firefox|FxiOS/i', $agent) => 'Firefox',
            (bool) preg_match('/Chrome|CriOS/i', $agent) => 'Chrome',
            (bool) preg_match('/Safari/i', $agent) => 'Safari',
            default => 'Desconocido',
        };
    }

    public static function browserVersion(string $agent): ?string
    {
        $pattern = match (self::browser($agent)) {
            'Edge' => '/Edg(?:e|A|iOS)?\/([\d.]+)/i',
            'Opera' => '/(?:OPR|Opera)\/([\d.]+)/i',
            'Firefox' => '/(?:Firefox|FxiOS)\/([\d.]+)/i',
            'Chrome' => '/(?:Chrome|CriOS)\/([\d.]+)/i',
            'Safari' => '/Version\/([\d.]+)/i',
            default => null,
        };

        return $pattern && preg_match($pattern, $agent, $m) ? $m[1] : null;
    }

    public static function isMobile(string $agent): bool
    {
        return (bool) preg_match('/Android|iPhone|iPad|Mobile/i', $agent);
    }

    public static function deviceType(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $agent) => 'Tablet',
            self::isMobile($agent) => 'Móvil',
            default => 'Escritorio',
        };
    }
}
