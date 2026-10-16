<?php

namespace App\Http\Controllers;

use App\Support\UserAgentInfo;
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
            'title' => 'Perfil',
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
                'platform' => UserAgentInfo::platform((string) $session->user_agent),
                'browser' => UserAgentInfo::browser((string) $session->user_agent),
                'is_mobile' => UserAgentInfo::isMobile((string) $session->user_agent),
            ])
            ->all();
    }
}
