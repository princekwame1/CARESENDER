<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Name, logo and colour set in /admin/settings; used by the website, the admin area and the mobile app. */
class Branding
{
    public const DEFAULT_COLOR = '#07689f';

    public static function name(): string
    {
        return Settings::get('brand.name') ?: 'CareSenda';
    }

    /** Short line shown under the name in the mobile app. */
    public static function tagline(): string
    {
        return Settings::get('brand.tagline') ?: 'Find your way to care';
    }

    /** Main colour for the mobile app and the browser bar. */
    public static function color(): string
    {
        return Settings::get('brand.color') ?: self::DEFAULT_COLOR;
    }

    public static function logoUrl(bool $absolute = false): ?string
    {
        return self::url('logo', $absolute);
    }

    /** Browser-tab icon: the uploaded favicon, else the logo, else null (the layout's built-in icon). */
    public static function faviconUrl(): ?string
    {
        return self::url('favicon') ?? self::url('logo');
    }

    /**
     * Stores an uploaded "logo" or "favicon" untouched (no re-encoding, so transparency and .ico
     * files survive) and removes the previous file. Returns the stored path.
     */
    public static function store(string $slot, UploadedFile $file): string
    {
        self::delete($slot);

        return $file->storeAs('branding', $slot.'-'.Str::lower(Str::random(8)).'.'.strtolower($file->getClientOriginalExtension()), 'public');
    }

    public static function delete(string $slot): void
    {
        if ($path = Settings::get("brand.{$slot}")) {
            Storage::disk('public')->delete($path);
        }
    }

    private static function url(string $slot, bool $absolute = false): ?string
    {
        $path = Settings::get("brand.{$slot}");
        if (! $path) {
            return null;
        }

        return $absolute ? url('/storage/'.$path) : '/storage/'.$path;
    }

    public static function toApi(): array
    {
        return ['name' => self::name(), 'tagline' => self::tagline(), 'color' => self::color(), 'logo_url' => self::logoUrl(true), 'favicon_url' => self::url('favicon', true)];
    }
}
