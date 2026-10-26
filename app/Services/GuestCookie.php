<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Identifies a visitor without an account, so a single search they paid for stays open in
 * their browser. The cookie is encrypted like the rest of the site's cookies.
 */
class GuestCookie
{
    public const NAME = 'cs_guest';

    /** A year: long enough to come back to a paid search, short enough not to linger. */
    private const MINUTES = 60 * 24 * 365;

    public static function read(Request $request): ?string
    {
        $token = $request->cookie(self::NAME);

        return is_string($token) && preg_match('/^[A-Za-z0-9]{40}$/', $token) ? $token : null;
    }

    /** The visitor's token, creating (and queueing the cookie for) a new one when they have none. */
    public static function issue(Request $request): string
    {
        $token = self::read($request) ?? Str::random(40);
        Cookie::queue(self::NAME, $token, self::MINUTES);

        return $token;
    }
}
