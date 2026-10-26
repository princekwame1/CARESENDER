<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** Stores an uploaded photo as two WebP sizes (640 and 1200 wide) on the public disk. */
class ImageUploader
{
    public const SIZES = [640, 1200];

    /** @return array{path: string, w: int, h: int} path has no size suffix, e.g. "cms/home-hero-ab12cd" */
    public static function store(UploadedFile $file, string $name): array
    {
        $raw = file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($raw);
        if (! $image) {
            throw new RuntimeException('That file could not be read as an image.');
        }
        $image = self::orient($image, $file);

        [$w, $h] = [imagesx($image), imagesy($image)];
        $path = 'cms/'.Str::slug($name).'-'.Str::lower(Str::random(6));

        foreach (self::SIZES as $target) {
            $tw = min($target, $w);
            $th = (int) round($h * $tw / $w);
            $resized = imagecreatetruecolor($tw, $th);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $tw, $th, $w, $h);
            ob_start();
            imagewebp($resized, null, 78);
            Storage::disk('public')->put("{$path}-{$target}.webp", ob_get_clean());
            imagedestroy($resized);
        }
        imagedestroy($image);

        return ['path' => $path, 'w' => min(1200, $w), 'h' => (int) round($h * min(1200, $w) / $w)];
    }

    /** A square, centre-cropped profile photo (256px WebP). Returns the stored path. */
    public static function storeAvatar(UploadedFile $file, string $name): string
    {
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $image) {
            throw new RuntimeException('That file could not be read as an image.');
        }
        $image = self::orient($image, $file);
        $side = min(imagesx($image), imagesy($image));
        $square = imagecreatetruecolor(256, 256);
        imagecopyresampled($square, $image, 0, 0, intdiv(imagesx($image) - $side, 2), intdiv(imagesy($image) - $side, 2), 256, 256, $side, $side);
        ob_start();
        imagewebp($square, null, 82);
        $path = 'avatars/'.Str::slug($name).'-'.Str::lower(Str::random(8)).'.webp';
        Storage::disk('public')->put($path, ob_get_clean());
        imagedestroy($square);
        imagedestroy($image);

        return $path;
    }

    public static function deleteAvatar(?string $path): void
    {
        if ($path && str_starts_with($path, 'avatars/')) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'cms/')) {
            Storage::disk('public')->delete(array_map(fn ($s) => "{$path}-{$s}.webp", self::SIZES));
        }
    }

    /** Phone photos are often stored sideways with an EXIF rotation flag. */
    private static function orient(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $image;
        }
        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;

        return match ((int) $orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
