<?php

namespace App\Support;

use App\Models\HelpRequest;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * 1200x630 social preview card for a help request (og:image / twitter:image).
 *
 * Drawn with GD + FreeType using the bundled Poppins font (resources/fonts, SIL OFL).
 * GD cannot shape Devanagari, so user-typed text (title, place) is drawn only when
 * it is Latin script; otherwise a generic line is used. Platforms still show the
 * Hindi title/description from the og: tags next to the image.
 *
 * Images are cached on the local disk per content fingerprint.
 */
class HelpPreviewImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private const BOLD = 'Poppins-Bold.ttf';

    private const MEDIUM = 'Poppins-Medium.ttf';

    private const URGENCY_COLORS = [
        'Emergency' => [220, 38, 38],
        'Urgent' => [234, 88, 12],
        'Normal' => [22, 163, 74],
    ];

    public static function isSupported(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagettftext')
            && is_file(self::font(self::BOLD))
            && is_file(self::font(self::MEDIUM));
    }

    /** Short content fingerprint, used for the cache file and the og:image cache-buster. */
    public static function fingerprint(HelpRequest $help): string
    {
        return substr(md5(implode('|', [
            'v1',
            $help->id,
            $help->title,
            $help->category,
            $help->urgency,
            $help->status,
            $help->city,
            $help->state,
            optional($help->updated_at)->timestamp,
        ])), 0, 12);
    }

    /** Absolute path to the cached PNG, generating it when missing. Null when unsupported. */
    public static function path(HelpRequest $help): ?string
    {
        if (! self::isSupported()) {
            return null;
        }

        $disk = Storage::disk('local');
        $relative = 'og/help-'.$help->id.'-'.self::fingerprint($help).'.png';

        if (! $disk->exists($relative)) {
            try {
                $png = self::render($help);
            } catch (Throwable $e) {
                report($e);

                return null;
            }

            // Drop older versions of this request's card before writing the new one.
            foreach ($disk->files('og') as $old) {
                if (str_starts_with(basename($old), 'help-'.$help->id.'-')) {
                    $disk->delete($old);
                }
            }

            $disk->put($relative, $png);
        }

        return $disk->path($relative);
    }

    public static function render(HelpRequest $help): string
    {
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagesavealpha($img, true);

        $navy = imagecolorallocate($img, 7, 29, 73);
        $white = imagecolorallocate($img, 255, 255, 255);
        $ink = imagecolorallocate($img, 15, 23, 42);
        $muted = imagecolorallocate($img, 71, 85, 105);
        $orange = imagecolorallocate($img, 249, 115, 22);
        $paleOrange = imagecolorallocate($img, 253, 186, 116);
        $chip = imagecolorallocate($img, 241, 245, 249);
        [$r, $g, $b] = self::URGENCY_COLORS[$help->urgency] ?? self::URGENCY_COLORS['Normal'];
        $accent = imagecolorallocate($img, $r, $g, $b);

        // Background + urgency strip.
        imagefilledrectangle($img, 0, 0, self::WIDTH, self::HEIGHT, $navy);
        imagefilledrectangle($img, 0, 0, self::WIDTH, 14, $accent);

        // Brand row.
        self::roundedRect($img, 60, 44, 120, 104, 14, $orange);
        self::text($img, 'KC', self::BOLD, 26, 72, 86, $white);
        self::text($img, 'Kushwaha', self::BOLD, 30, 138, 84, $paleOrange);
        self::text($img, 'Connect', self::BOLD, 30, 138 + self::width('Kushwaha ', self::BOLD, 30), 84, $white);
        $tag = 'HELP EACH OTHER';
        self::text($img, $tag, self::BOLD, 20, self::WIDTH - 60 - self::width($tag, self::BOLD, 20), 82, $paleOrange);

        // White card.
        $left = 60;
        $right = self::WIDTH - 60;
        $top = 132;
        $bottom = self::HEIGHT - 44;
        self::roundedRect($img, $left, $top, $right, $bottom, 28, $white);
        $x = $left + 48;
        $maxWidth = $right - $left - 96;

        // Badges: urgency + category (fixed vocabularies, always Latin).
        $y = $top + 66;
        $urgency = strtoupper(self::latinOr($help->urgency, 'Help needed'));
        $x2 = self::pill($img, $urgency, $x, $y, $accent, $white);
        $category = self::latinOr($help->category, '');
        if ($category !== '') {
            self::pill($img, $category, $x2 + 14, $y, $chip, $ink);
        }

        // Title: up to 2 lines at 48px, otherwise up to 3 lines at 40px.
        $title = self::latinOr($help->title, 'A community member needs your help');
        $size = 48;
        $lines = self::wrap($title, self::BOLD, $size, $maxWidth, 3);
        if (count($lines) > 2) {
            $size = 40;
            $lines = self::wrap($title, self::BOLD, $size, $maxWidth, 3);
        }
        $lineHeight = (int) round($size * 1.3);
        $y = $top + 112 + $lineHeight;
        foreach ($lines as $line) {
            self::text($img, $line, self::BOLD, $size, $x, $y, $ink);
            $y += $lineHeight;
        }

        // Location.
        $place = collect([$help->city, $help->state])
            ->map(fn ($part) => trim((string) $part))
            ->filter(fn ($part) => $part !== '' && self::isLatin($part))
            ->unique()
            ->implode(', ');
        if ($place !== '') {
            $y += 6;
            imagefilledellipse($img, $x + 9, $y - 11, 18, 18, $orange);
            imagefilledellipse($img, $x + 9, $y - 11, 7, 7, $white);
            self::text($img, self::fit($place, self::MEDIUM, 26, $maxWidth - 36), self::MEDIUM, 26, $x + 32, $y, $muted);
        }

        // Footer line inside the card.
        $footerY = $bottom - 40;
        imageline($img, $x, $footerY - 46, $right - 48, $footerY - 46, $chip);
        self::text($img, 'Please help & share', self::BOLD, 26, $x, $footerY, $orange);
        $site = 'kushwahaconnect.com';
        self::text($img, $site, self::MEDIUM, 24, $right - 48 - self::width($site, self::MEDIUM, 24), $footerY, $muted);

        ob_start();
        imagepng($img, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    private static function font(string $file): string
    {
        return resource_path('fonts/'.$file);
    }

    private static function isLatin(string $text): bool
    {
        // Latin, Latin-1 / Extended, general punctuation and common symbols only.
        return preg_match('/[^\x{0000}-\x{024F}\x{2000}-\x{206F}\x{20B9}]/u', $text) === 0;
    }

    private static function latinOr(?string $text, string $fallback): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text));

        return $text !== '' && self::isLatin($text) ? $text : $fallback;
    }

    private static function text($img, string $text, string $font, int $size, int $x, int $y, int $color): void
    {
        imagettftext($img, $size, 0, $x, $y, $color, self::font($font), $text);
    }

    private static function width(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size, 0, self::font($font), $text);

        return (int) abs($box[2] - $box[0]);
    }

    /** Draw a pill-shaped badge; returns its right edge x. */
    private static function pill($img, string $label, int $x, int $y, int $bg, int $fg): int
    {
        $size = 20;
        $w = self::width($label, self::BOLD, $size) + 40;
        self::roundedRect($img, $x, $y - 34, $x + $w, $y + 12, 22, $bg);
        self::text($img, $label, self::BOLD, $size, $x + 20, $y - 2, $fg);

        return $x + $w;
    }

    private static function roundedRect($img, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        $radius = min($radius, (int) floor(($x2 - $x1) / 2), (int) floor(($y2 - $y1) / 2));
        imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        $d = $radius * 2;
        imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $d, $d, $color);
        imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $d, $d, $color);
        imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $d, $d, $color);
        imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $d, $d, $color);
    }

    /**
     * Word-wrap to $maxWidth, at most $maxLines lines; the last line gets an ellipsis
     * when text remains.
     *
     * @return list<string>
     */
    private static function wrap(string $text, string $font, int $size, int $maxWidth, int $maxLines): array
    {
        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $index => $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;

            if (self::width($candidate, $font, $size) <= $maxWidth) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }
            $current = self::fit($word, $font, $size, $maxWidth);

            if (count($lines) === $maxLines - 1) {
                $rest = implode(' ', array_slice($words, $index));

                return [...$lines, self::fit($rest, $font, $size, $maxWidth)];
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, $maxLines);
    }

    /** Trim text with an ellipsis so it fits $maxWidth. */
    private static function fit(string $text, string $font, int $size, int $maxWidth): string
    {
        if (self::width($text, $font, $size) <= $maxWidth) {
            return $text;
        }

        while (mb_strlen($text) > 1 && self::width($text.'…', $font, $size) > $maxWidth) {
            $text = mb_substr($text, 0, -1);
        }

        return rtrim($text).'…';
    }
}
