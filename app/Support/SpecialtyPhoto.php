<?php

namespace App\Support;

use App\Models\Specialty;
use App\Services\DirectoryCache;

/**
 * Picks the search header photo, in order of preference:
 * 1. a photo uploaded for the matching specialty (Specialties page),
 * 2. a built-in specialty photo (config/caresenda.php 'specialty_photos'),
 * 3. the category photo set under Website content › Search.
 */
class SpecialtyPhoto
{
    /** @return array{src: string, srcset: string, width: int, height: int, alt: string, credit: ?array, custom: bool} */
    public static function for(?string $query, string $type): array
    {
        $q = SearchNormalizer::normalise($query);

        if (strlen($q) >= 3) {
            foreach (self::uploaded() as [$name, $image]) {
                if (str_contains($q, $name) || str_contains($name, $q)) {
                    return Cms::fromUpload($image, $image['alt'] ?? '');
                }
            }
            foreach (config('caresenda.specialty_photos') as $stem => [$slug, $alt]) {
                if (str_contains($q, $stem)) {
                    return Cms::fromSlug($slug, $alt);
                }
            }
        }

        $key = in_array($type, ['specialist', 'test', 'facility'], true) ? "search.image_$type" : 'search.image_all';

        return Cms::image($key);
    }

    /** Absolute URL of the small (640px) photo for a specialty card in the app. */
    public static function cardUrl(string $name): string
    {
        return url(str_replace('-1200.webp', '-640.webp', self::for($name, 'specialist')['src']));
    }

    /** @return list<array{0: string, 1: array}> normalised specialty name => uploaded image */
    private static function uploaded(): array
    {
        return app(DirectoryCache::class)->remember('specialty-photos', fn () => Specialty::active()->whereNotNull('image')->get(['name', 'image'])
            ->map(fn ($s) => [rtrim(SearchNormalizer::normalise($s->name), 's'), $s->image])
            ->filter(fn ($row) => ! empty($row[1]['path']))
            ->values()->all());
    }
}
