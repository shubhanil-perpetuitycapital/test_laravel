<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Stores uploaded images on the public "uploads" disk (public/uploads).
 *
 * When the GD extension is available, large photos are scaled down and
 * re-encoded, which also strips EXIF data (camera, GPS location). Without
 * GD the original file is stored unchanged.
 */
class ImageService
{
    public const DISK = 'uploads';

    public function store(UploadedFile $file, string $folder, ?int $maxWidth = 1600, int $quality = 82): string
    {
        $extension = strtolower($file->extension() ?: 'jpg');
        $name = $folder.'/'.date('Y/m').'/'.Str::lower(Str::random(20)).'.'.$extension;

        $optimised = $maxWidth ? $this->optimise($file->getRealPath(), $extension, $maxWidth, $quality) : null;

        if ($optimised !== null) {
            Storage::disk(self::DISK)->put($name, $optimised);
        } else {
            Storage::disk(self::DISK)->putFileAs(dirname($name), $file, basename($name));
        }

        return $name;
    }

    /** Delete an uploaded file; bundled placeholder images are left alone. */
    public function delete(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'images/') && ! str_starts_with($path, 'http')) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function optimise(string $path, string $extension, int $maxWidth, int $quality): ?string
    {
        if (! extension_loaded('gd') || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        try {
            [$width, $height] = getimagesize($path) ?: [0, 0];
            if ($width === 0) {
                return null;
            }

            $source = match ($extension) {
                'png' => @imagecreatefrompng($path),
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
                default => @imagecreatefromjpeg($path),
            };
            if (! $source) {
                return null;
            }

            $image = $source;
            if ($width > $maxWidth) {
                $newHeight = (int) round($height * ($maxWidth / $width));
                $image = imagecreatetruecolor($maxWidth, $newHeight);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagecopyresampled($image, $source, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
                imagedestroy($source);
            }

            ob_start();
            match ($extension) {
                'png' => imagepng($image, null, 7),
                'webp' => imagewebp($image, null, $quality),
                default => imagejpeg($image, null, $quality),
            };
            $data = ob_get_clean();
            imagedestroy($image);

            return $data ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
