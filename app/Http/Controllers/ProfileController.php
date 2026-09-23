<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('pages.profile', [
            'title' => 'Profile',
            'sessions' => $this->browserSessions($request),
            'sessionsSupported' => config('session.driver') === 'database',
        ]);
    }

    public function logoutOtherBrowserSessions(Request $request): RedirectResponse
    {
        abort_unless(config('session.driver') === 'database', 404);

        $request->validateWithBag('logoutOtherBrowserSessions', [
            'password' => ['required', 'current_password'],
        ]);

        DB::table('sessions')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('status', 'browser-sessions-logged-out');
    }

    private function browserSessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::table('sessions')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => (object) [
                'ip_address' => $session->ip_address,
                'is_current' => $session->id === $request->session()->getId(),
                'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'platform' => $this->platform((string) $session->user_agent),
                'browser' => $this->browser((string) $session->user_agent),
                'is_mobile' => (bool) preg_match('/Android|iPhone|iPad|Mobile/i', (string) $session->user_agent),
            ])
            ->all();
    }

    private function platform(string $agent): string
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

    private function browser(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/Edg/i', $agent) => 'Edge',
            (bool) preg_match('/OPR|Opera/i', $agent) => 'Opera',
            (bool) preg_match('/Firefox/i', $agent) => 'Firefox',
            (bool) preg_match('/Chrome|CriOS/i', $agent) => 'Chrome',
            (bool) preg_match('/Safari/i', $agent) => 'Safari',
            default => 'Desconocido',
        };
    }
}
