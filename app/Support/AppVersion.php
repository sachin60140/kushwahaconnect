<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Version + "last updated" information shown in the footer of every page.
 *
 * - version:    "{major}.{minor}.{build number}". major.minor come from APP_VERSION
 *               (config app.version, e.g. "1.0"); the build number is the git commit
 *               count of the deployed code, so the version increases on every deploy.
 *               The count comes from storage/app/release.json (written by
 *               scripts/hostinger-deploy.sh, because PHP on shared hosting cannot run
 *               git), or from `git rev-list --count` where PHP may run it (local dev).
 *               Without a build number, APP_VERSION is shown as configured.
 * - build:      short git commit hash of the deployed code, when a .git directory exists
 * - updated_at: APP_UPDATED_AT from .env if set; otherwise when HEAD last moved
 *               (commit / pull / checkout, read from .git/logs/HEAD); otherwise the
 *               deploy time from release.json; otherwise when the frontend assets were
 *               last built (public/build/manifest.json).
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

    public static function releaseFilePath(): string
    {
        return storage_path('app/release.json');
    }

    /**
     * Combine the configured version with a build number: ("1.0", 47) => "1.0.47".
     * Only the first two parts of the configured version are kept when a build
     * number is known, so an older APP_VERSION=1.0.0 still auto-increments.
     */
    public static function compose(string $configured, ?int $buildNumber): string
    {
        $configured = trim($configured) !== '' ? trim($configured) : '1.0';
        $parts = explode('.', ltrim($configured, 'vV'));

        if ($buildNumber === null) {
            return count($parts) >= 3 ? $configured : ($parts[0] ?? '1').'.'.($parts[1] ?? '0').'.0';
        }

        return ($parts[0] !== '' ? $parts[0] : '1').'.'.($parts[1] ?? '0').'.'.$buildNumber;
    }

    private static function resolve(): array
    {
        $git = self::gitHead();
        $release = self::releaseFile();
        $updatedAt = self::configuredUpdatedAt() ?? $git['updated_at'] ?? $release['released_at'] ?? self::buildTime();
        $local = $updatedAt?->setTimezone(self::DISPLAY_TIMEZONE);

        return [
            'version' => self::compose((string) config('app.version', '1.0'), self::buildNumber($git, $release)),
            'build' => $git['commit'] ?? $release['commit'] ?? null,
            'updated_at' => $updatedAt?->toIso8601String(),
            'updated_at_display' => $local ? $local->format('d M Y, h:i A').' IST' : null,
        ];
    }

    /**
     * Commit count of the deployed code. Prefers the deploy script's release file when
     * it describes the current HEAD, then asks git directly when PHP is allowed to run
     * it, then falls back to a release file from an earlier deploy.
     */
    private static function buildNumber(array $git, array $release): ?int
    {
        $releaseBuild = $release['build'] ?? null;
        $head = $git['commit'] ?? null;

        if ($releaseBuild !== null && ($head === null || ($release['commit'] ?? null) === $head)) {
            return $releaseBuild;
        }

        return self::gitCommitCount($git['commit_full'] ?? null) ?? $releaseBuild;
    }

    /**
     * @return array{build?: int, commit?: string, released_at?: CarbonImmutable}
     */
    private static function releaseFile(): array
    {
        $path = self::releaseFilePath();

        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) @file_get_contents($path), true);

        if (! is_array($data)) {
            return [];
        }

        $release = [];

        if (isset($data['build']) && is_numeric($data['build']) && (int) $data['build'] >= 0) {
            $release['build'] = (int) $data['build'];
        }

        if (isset($data['commit']) && is_string($data['commit']) && preg_match('/^[0-9a-f]{7,40}$/', $data['commit'])) {
            $release['commit'] = substr($data['commit'], 0, 7);
        }

        if (! empty($data['released_at']) && is_string($data['released_at'])) {
            try {
                $release['released_at'] = CarbonImmutable::parse($data['released_at']);
            } catch (Throwable) {
                // ignore an unreadable timestamp
            }
        }

        return $release;
    }

    /**
     * `git rev-list --count HEAD`, only where PHP may run commands (not on shared
     * hosting, where exec is disabled). Cached per commit, as it costs a process.
     */
    private static function gitCommitCount(?string $commit): ?int
    {
        if ($commit === null || ! function_exists('exec') || ! is_dir(base_path('.git'))) {
            return null;
        }

        $count = static function (): ?int {
            $output = [];
            $exitCode = 1;
            @exec('git -C '.escapeshellarg(base_path()).' rev-list --count HEAD 2>&1', $output, $exitCode);
            $value = trim((string) ($output[0] ?? ''));

            return $exitCode === 0 && ctype_digit($value) ? (int) $value : null;
        };

        try {
            return cache()->rememberForever('app_version.commit_count.'.$commit, $count);
        } catch (Throwable) {
            // Cache store unavailable (e.g. database down): still show the right number.
            try {
                return $count();
            } catch (Throwable) {
                return null;
            }
        }
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
     * @return array{commit?: string, commit_full?: string, updated_at?: CarbonImmutable}
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
            $result['commit_full'] = $commit;
            $result['commit'] = substr($commit, 0, 7);
        }

        // Last line of the HEAD reflog: "<old> <new> Name <email> <unix-ts> <tz>\t<message>"
        $lastEntry = self::lastLine($gitDir.'/logs/HEAD');

        if ($lastEntry && preg_match('/^[0-9a-f]{40} ([0-9a-f]{40}) .*?> (\d+) [+-]\d{4}\t/', $lastEntry, $matches)) {
            $result['commit_full'] ??= $matches[1];
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
