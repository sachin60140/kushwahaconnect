<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Fallback for compiled Vite assets (/build/...).
 *
 * On Hostinger the domain may serve public_html/ while Laravel reads the Vite
 * manifest from the project's public/build. If a deploy updates the code but
 * public/build is not copied into public_html/build, every new hashed asset
 * 404s and the whole site renders blank. The web server rewrites those missing
 * files to Laravel, so serve them straight from the project's public/build.
 *
 * When the files do exist in the document root, the web server serves them
 * directly and this is never reached.
 */
class BuildAsset
{
    private const CONTENT_TYPES = [
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'map' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];

    public static function response(string $path): BinaryFileResponse
    {
        $root = realpath(public_path('build'));
        $file = $root ? realpath($root.DIRECTORY_SEPARATOR.str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path)) : false;

        if (! $root || ! $file || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR) || ! is_file($file)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Browsers refuse ES module scripts served with a non-JavaScript MIME type.
        $headers = ['Content-Type' => self::CONTENT_TYPES[$extension] ?? 'application/octet-stream'];

        // Hashed filenames never change content; the manifest itself must stay fresh.
        $headers['Cache-Control'] = basename($file) === 'manifest.json'
            ? 'no-cache'
            : 'public, max-age=31536000, immutable';

        return response()->file($file, $headers);
    }
}
