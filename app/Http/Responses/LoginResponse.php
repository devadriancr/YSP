<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;

/**
 * After login (with or without 2FA) go to the intended page, except when the
 * intended page is the site root: then land on Fortify's home (the calendar).
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return $request->routeIs('two-factor.login*')
                ? new JsonResponse('', 204)
                : response()->json(['two_factor' => false]);
        }

        $intended = $request->session()->get('url.intended');

        if ($intended !== null && trim((string) parse_url($intended, PHP_URL_PATH), '/') === '') {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
