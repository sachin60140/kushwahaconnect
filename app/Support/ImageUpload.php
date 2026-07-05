<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUpload
{
    public static function storeProfilePhoto(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return self::store($file, $directory, $disk, [
            'width' => 900,
            'height' => 1200,
            'mode' => 'contain',
            'background' => [245, 247, 251],
            'quality' => 86,
            'upscale' => true,
        ]);
    }

    public static function storeSquare(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return self::store($file, $directory, $disk, [
            'width' => 900,
            'height' => 900,
            'mode' => 'contain',
            'background' => [255, 255, 255],
            'quality' => 86,
            'upscale' => true,
        ]);
    }

    public static function storeCover(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return self::store($file, $directory, $disk, [
            'width' => 1600,
            'height' => 900,
            'mode' => 'cover',
            'background' => [255, 255, 255],
            'quality' => 86,
            'upscale' => true,
        ]);
    }

    public static function storeGalleryImage(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return self::store($file, $directory, $disk, [
            'width' => 1600,
            'height' => 1200,
            'mode' => 'fit',
            'quality' => 86,
            'upscale' => false,
        ]);
    }

    public static function store(UploadedFile $file, string $directory, string $disk, array $options): string
    {
        if (! extension_loaded('gd')) {
            return $file->store($directory, $disk);
        }

        $image = self::createImage($file);

        if (! $image) {
            return $file->store($directory, $disk);
        }

        $image = self::orientate($image, $file);
        $canvas = self::resize($image, $options);
        $path = trim($directory, '/').'/'.Str::uuid().'.jpg';

        ob_start();
        imagejpeg($canvas, null, $options['quality'] ?? 86);
        $contents = ob_get_clean();

        imagedestroy($image);
        imagedestroy($canvas);

        if ($contents === false) {
            return $file->store($directory, $disk);
        }

        Storage::disk($disk)->put($path, $contents);

        return $path;
    }

    private static function createImage(UploadedFile $file): mixed
    {
        $path = $file->getRealPath();
        $mime = $file->getMimeType();

        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private static function orientate(mixed $image, UploadedFile $file): mixed
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = $exif['Orientation'] ?? null;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated ?: $image;
    }

    private static function resize(mixed $image, array $options): mixed
    {
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $targetWidth = (int) $options['width'];
        $targetHeight = (int) $options['height'];
        $mode = $options['mode'] ?? 'fit';
        $upscale = $options['upscale'] ?? false;

        $scale = match ($mode) {
            'cover' => max($targetWidth / $sourceWidth, $targetHeight / $sourceHeight),
            default => min($targetWidth / $sourceWidth, $targetHeight / $sourceHeight),
        };

        if (! $upscale) {
            $scale = min($scale, 1);
        }

        $resizedWidth = max(1, (int) round($sourceWidth * $scale));
        $resizedHeight = max(1, (int) round($sourceHeight * $scale));

        if ($mode === 'fit') {
            $targetWidth = $resizedWidth;
            $targetHeight = $resizedHeight;
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        [$red, $green, $blue] = $options['background'] ?? [255, 255, 255];
        $background = imagecolorallocate($canvas, $red, $green, $blue);
        imagefill($canvas, 0, 0, $background);

        imagecopyresampled(
            $canvas,
            $image,
            (int) round(($targetWidth - $resizedWidth) / 2),
            (int) round(($targetHeight - $resizedHeight) / 2),
            0,
            0,
            $resizedWidth,
            $resizedHeight,
            $sourceWidth,
            $sourceHeight,
        );

        return $canvas;
    }
}
