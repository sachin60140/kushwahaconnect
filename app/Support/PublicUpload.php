<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicUpload
{
    public static function url(?string $path): ?string
    {
        $path = self::cleanPath($path);

        return $path ? route('public.uploads.show', ['path' => $path], false) : null;
    }

    public static function response(string $path)
    {
        $path = self::cleanPath($path);

        if (! $path) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        $root = realpath($disk->path(''));
        $file = realpath($disk->path($path));

        if (! $root || ! $file || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR) || ! is_file($file)) {
            abort(404);
        }

        return response()->file($file, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    private static function cleanPath(?string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path ?? ''), '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }
}
