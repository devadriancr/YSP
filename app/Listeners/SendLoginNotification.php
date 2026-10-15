<?php

namespace App\Listeners;

use App\Notifications\NewLoginNotification;
use App\Support\IpLocation;
use App\Support\UserAgentInfo;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Str;
use Throwable;

class SendLoginNotification
{
    public function handle(Login $event): void
    {
        $request = request();

        if (! $request->routeIs('login.store', 'two-factor.login.store')) {
            return;
        }

        $agent = (string) $request->userAgent();
        $ip = $request->ip();

        $deviceType = UserAgentInfo::deviceType($agent);
        if ($deviceType === 'Escritorio' && UserAgentInfo::platform($agent) === 'macOS' && (int) $request->input('device_touch') > 1) {
            $deviceType = 'Tablet';
        }

        try {
            $event->user->notify(new NewLoginNotification([
                'login_id' => (string) Str::ulid(),
                'logged_in_at' => now()->toIso8601String(),
                'ip' => $ip,
                'location' => IpLocation::lookup($ip),
                'browser' => UserAgentInfo::browser($agent),
                'browser_version' => UserAgentInfo::browserVersion($agent),
                'platform' => UserAgentInfo::platform($agent),
                'platform_version' => UserAgentInfo::platformVersion($agent),
                'device_type' => $deviceType,
                'incognito' => in_array($request->input('device_incognito'), ['yes', 'no'], true) ? $request->input('device_incognito') : 'unknown',
                'client_browser' => $this->clean($request->input('device_client_browser'), 64),
                'client_platform' => $this->clean($request->input('device_client_platform'), 64),
                'language' => $this->clean(Str::before((string) $request->getPreferredLanguage(), ','), 16),
                'timezone' => $this->clean($request->input('device_timezone'), 64),
                'screen' => $this->clean($request->input('device_screen'), 16),
                'device_id' => $this->clean($request->input('device_id'), 64),
            ]));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function clean(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(Str::limit(preg_replace('/[^A-Za-z0-9._\/+: -]/', '', $value), $max, ''));

        return $value === '' ? null : $value;
    }
}
