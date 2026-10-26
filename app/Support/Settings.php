<?php

namespace App\Support;

use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** Admin-editable settings ("brand.*", "payments.*"), stored next to website content in site_contents. */
class Settings
{
    private const CACHE_KEY = 'caresenda:settings';

    public static function get(string $key): ?string
    {
        $all = Cache::memo()->rememberForever(self::CACHE_KEY, fn () => Schema::hasTable('site_contents')
            ? SiteContent::where('key', 'like', 'brand.%')->orWhere('key', 'like', 'payments.%')->pluck('value', 'key')->all()
            : []);

        return $all[$key] ?? null;
    }

    /** @param array<string, string|bool|array|null> $values keyed by full setting name */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            $value = match (true) {
                is_bool($value) => $value ? '1' : '0',
                is_array($value) => implode(',', $value),
                default => $value,
            };
            SiteContent::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget(self::CACHE_KEY);
        Cache::memo()->forget(self::CACHE_KEY);
        Cms::forget(); // Cms caches every site_contents row too
    }
}
