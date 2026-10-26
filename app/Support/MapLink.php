<?php

namespace App\Support;

/** Reads coordinates from Google Maps links or "lat, lng" text. */
class MapLink
{
    /** @return array{0: float, 1: float}|null [lat, lng] */
    public static function coordinates(?string $text): ?array
    {
        $text = trim(rawurldecode((string) $text));
        if ($text === '') {
            return null;
        }

        $patterns = [
            '/!3d(-?\d{1,2}\.\d+)!4d(-?\d{1,3}\.\d+)/',                    // place pin inside data=…
            '/@(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/',                        // map centre: /@5.60,-0.18,17z
            '/[?&](?:q|query|ll|destination|center)=(-?\d{1,2}\.\d+),\s*(-?\d{1,3}\.\d+)/',
            '/^(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)$/',      // plain "5.6037, -0.1870"
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $text, $m)) {
                [$lat, $lng] = [(float) $m[1], (float) $m[2]];
                if (abs($lat) <= 90 && abs($lng) <= 180 && ($lat !== 0.0 || $lng !== 0.0)) {
                    return [round($lat, 6), round($lng, 6)];
                }
            }
        }

        return null;
    }

    public static function isGoogleMaps(string $url): bool
    {
        return (bool) preg_match('#^https://((www\.)?google\.[a-z.]+/maps|maps\.google\.[a-z.]+|maps\.app\.goo\.gl|goo\.gl/maps)#i', $url);
    }
}
