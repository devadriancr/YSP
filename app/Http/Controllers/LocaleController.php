<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Supported locales with their metadata.
     */
    public const SUPPORTED_LOCALES = [
        "es" => [
            "name" => "Spanish",
            "native" => "Español",
            "flag" => "mx",
            "dir" => "ltr",
        ],
        "en" => [
            "name" => "English",
            "native" => "English",
            "flag" => "us",
            "dir" => "ltr",
        ],
        "ja" => [
            "name" => "Japanese",
            "native" => "日本語",
            "flag" => "jp",
            "dir" => "ltr",
        ],
    ];

    /**
     * Switch application locale.
     */
    public function switch(string $locale, Request $request): RedirectResponse
    {
        if (array_key_exists($locale, self::SUPPORTED_LOCALES)) {
            session(["locale" => $locale]);
            cookie()->queue("locale", $locale, 60 * 24 * 365); // 1 year
            $dir = self::SUPPORTED_LOCALES[$locale]["dir"] ?? "ltr";
            session(["dir" => $dir]);
            cookie()->queue("dir", $dir, 60 * 24 * 365);
        }

        return redirect()->back();
    }
}
