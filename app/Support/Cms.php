<?php

namespace App\Support;

use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** Editable website content: dashboard values from the database, falling back to config/cms.php defaults. */
class Cms
{
    private const CACHE_KEY = 'caresenda:cms';

    /** Field definitions; read as a whole array because the keys themselves contain dots. */
    public static function fields(): array
    {
        return config()->get('cms', [])['fields'] ?? [];
    }

    public static function pages(): array
    {
        return config()->get('cms', [])['pages'] ?? [];
    }

    public static function get(string $key, ?string $fallback = null): string
    {
        $saved = self::all();
        if (array_key_exists($key, $saved) && $saved[$key] !== null && $saved[$key] !== '') {
            return $saved[$key];
        }

        return $fallback ?? (self::fields()[$key][3] ?? '');
    }

    /**
     * An image slot: the uploaded photo when there is one, otherwise the default photo.
     *
     * @return array{src: string, srcset: string, width: int, height: int, alt: string, credit: ?array, custom: bool}
     */
    public static function image(string $key, ?string $alt = null): array
    {
        $field = self::fields()[$key] ?? null;
        $saved = json_decode(self::all()[$key] ?? '', true);

        if (is_array($saved) && ! empty($saved['path'])) {
            return self::fromUpload($saved, $alt ?? ($field[4] ?? ''));
        }

        return self::fromSlug($field[3] ?? 'hero-concept', $alt ?? ($field[4] ?? ''));
    }

    /** @param array{path: string, w: int, h: int, alt?: string} $upload */
    public static function fromUpload(array $upload, string $alt = ''): array
    {
        $base = '/storage/'.$upload['path'];

        return [
            'src' => "{$base}-1200.webp",
            'srcset' => "{$base}-640.webp 640w, {$base}-1200.webp 1200w",
            'width' => (int) $upload['w'], 'height' => (int) $upload['h'],
            'alt' => ($upload['alt'] ?? '') !== '' ? $upload['alt'] : $alt,
            'credit' => null, 'custom' => true,
        ];
    }

    public static function fromSlug(string $slug, string $alt = ''): array
    {
        $p = config('caresenda.photos')[$slug] ?? null;

        return [
            'src' => "/images/photos/{$slug}-1200.webp",
            'srcset' => "/images/photos/{$slug}-640.webp 640w, /images/photos/{$slug}-1200.webp 1200w",
            'width' => $p['width'] ?? 1200, 'height' => $p['height'] ?? 800,
            'alt' => $alt, 'credit' => ($p['credit_required'] ?? false) ? $p : null, 'custom' => false,
        ];
    }

    /** @return array<string, ?string> */
    public static function all(): array
    {
        return Cache::memo()->rememberForever(self::CACHE_KEY, fn () => Schema::hasTable('site_contents')
            ? SiteContent::pluck('value', 'key')->all()
            : []);
    }

    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if (isset(self::fields()[$key])) {
                SiteContent::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }
        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::memo()->forget(self::CACHE_KEY);
    }
}
