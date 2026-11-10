<?php

namespace App\Support;

use App\Models\MemberProfile;
use Illuminate\Database\Eloquent\Builder;

/**
 * Helps admins chase members whose account mobile is not verified yet
 * (everyone who joined before OTP became mandatory).
 */
class MobileVerifyReminder
{
    /** Members with an account whose mobile has not been verified. */
    public static function scopeUnverified(Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $q) => $q->whereNull('mobile_verified_at'));
    }

    public static function unverifiedCount(): int
    {
        return self::scopeUnverified(MemberProfile::query())->count();
    }

    /** Hindi reminder text with the verify link (guests are sent to login first, then back). */
    public static function message(?string $name): string
    {
        $name = trim((string) $name) ?: 'साथी';
        $url = route('mobile.verify.show');

        return "नमस्ते {$name}, KushwahaConnect पर अपना मोबाइल नंबर एक बार OTP से सत्यापित करें: {$url} . धन्यवाद।";
    }

    /** wa.me link, preferring the WhatsApp number, then the mobile; null when unusable. */
    public static function whatsappLink(?string $whatsapp, ?string $mobile, ?string $name): ?string
    {
        $number = self::waNumber($whatsapp) ?? self::waNumber($mobile);

        return $number ? 'https://wa.me/'.$number.'?text='.rawurlencode(self::message($name)) : null;
    }

    /** Same rules as toWhatsAppNumber() in useWhatsAppWelcome.js. */
    public static function waNumber(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        $length = strlen($digits);

        if ($length === 10) {
            return '91'.$digits;
        }
        if ($length === 11 && str_starts_with($digits, '0')) {
            return '91'.substr($digits, 1);
        }
        if ($length >= 11 && $length <= 15 && ! str_starts_with($digits, '0')) {
            return $digits;
        }

        return null;
    }
}
