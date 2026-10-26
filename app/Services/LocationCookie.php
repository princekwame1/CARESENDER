<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * The web client stores the detected location in a small plain cookie
 * ("lat,lng,area") so server-rendered pages can sort by distance.
 */
class LocationCookie
{
    public const NAME = 'cs_loc';

    /** @return array{lat: float, lng: float, area: ?string}|null */
    public static function read(Request $request): ?array
    {
        $raw = $request->cookie(self::NAME);
        if (! is_string($raw) || ! preg_match('/^(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)(?:,(.{0,80}))?$/', rawurldecode($raw), $m)) {
            return null;
        }

        return ['lat' => (float) $m[1], 'lng' => (float) $m[2], 'area' => isset($m[3]) && $m[3] !== '' ? $m[3] : null];
    }
}
