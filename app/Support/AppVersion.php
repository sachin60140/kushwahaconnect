<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Version + "last updated" information shown in the footer of every page.
 *
 * - version:    APP_VERSION from .env (config app.version)
 * - build:      short git commit hash of the deployed code, when a .git directory exists
 * - updated_at: APP_UPDATED_AT from .env if set; otherwise when HEAD last moved
 *               (commit / pull / checkout, read from .git/logs/HEAD); otherwise when
 *               the frontend assets were last built (public/build/manifest.json).
 *
 * Reads a few small files only — no shell commands — so it also works on shared hosting.
 */
class AppVersion
{
    private const DISPLAY_TIMEZONE = 'Asia/Kolkata';

    private static ?array $resolved = null;

    public static function info(): array
    {
        return self::$resolved ??= self::resolve();
    }

    public static function flush(): void
    {
        self::$resolved = null;
    }

    private static function resolve(): array
    {
        $git = self::gitHead();
        $updatedAt = self::configuredUpdatedAt() ?? $git['updated_at'] ?? self::buildTime();
        $local = $updatedAt?->setTimezone(self::DISPLAY_TIMEZONE);

        return [
            'version' => (string) config('app.version', '1.0.0'),
            'build' => $git['commit'] ?? null,
            'updated_at' => $updatedAt?->toIso8601String(),
            'updated_at_display' => $local ? $local->format('d M Y, h:i A').' IST' : null,
        ];
    }

    private static function configuredUpdatedAt(): ?CarbonImmutable
    {
        $value = config('app.updated_at');

        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, self::DISPLAY_TIMEZONE);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{commit?: string, updated_at?: CarbonImmutable}
     */
    private static function gitHead(): array
    {
        $gitDir = base_path('.git');

        if (! is_dir($gitDir)) {
            return [];
        }

        $result = [];
        $commit = self::resolveHeadCommit($gitDir);

        if ($commit) {
            $result['commit'] = substr($commit, 0, 7);
        }

        // Last line of the HEAD reflog: "<old> <new> Name <email> <unix-ts> <tz>\t<message>"
        $lastEntry = self::lastLine($gitDir.'/logs/HEAD');

        if ($lastEntry && preg_match('/^[0-9a-f]{40} ([0-9a-f]{40}) .*?> (\d+) [+-]\d{4}\t/', $lastEntry, $matches)) {
            $result['commit'] ??= substr($matches[1], 0, 7);
            $result['updated_at'] = CarbonImmutable::createFromTimestamp((int) $matches[2]);
        }

        return $result;
    }

    private static function resolveHeadCommit(string $gitDir): ?string
    {
        $head = @file_get_contents($gitDir.'/HEAD');

        if ($head === false) {
            return null;
        }

        $head = trim($head);

        if (preg_match('/^[0-9a-f]{40}$/', $head)) {
            return $head; // detached HEAD
        }

        if (! str_starts_with($head, 'ref: ')) {
            return null;
        }

        $ref = substr($head, 5);
        $refFile = $gitDir.'/'.$ref;

        if (is_file($refFile)) {
            $hash = trim((string) @file_get_contents($refFile));

            return preg_match('/^[0-9a-f]{40}$/', $hash) ? $hash : null;
        }

        $packed = @file($gitDir.'/packed-refs', FILE_IGNORE_NEW_LINES) ?: [];

        foreach ($packed as $line) {
            if (str_ends_with($line, ' '.$ref) && preg_match('/^([0-9a-f]{40}) /', $line, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private static function lastLine(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $handle = @fopen($path, 'rb');

        if (! $handle) {
            return null;
        }

        try {
            $size = filesize($path) ?: 0;
            $chunk = min($size, 8192);
            fseek($handle, -$chunk, SEEK_END);
            $lines = preg_split('/\R/', trim((string) fread($handle, $chunk)));

            return $lines ? end($lines) : null;
        } finally {
            fclose($handle);
        }
    }

    private static function buildTime(): ?CarbonImmutable
    {
        $manifest = public_path('build/manifest.json');

        return is_file($manifest) ? CarbonImmutable::createFromTimestamp(filemtime($manifest)) : null;
    }
}
