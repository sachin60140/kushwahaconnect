<?php

namespace App\Services\Otp;

use App\Services\Sms\SmartPingSms;
use App\Services\Sms\SmsConfig;
use App\Services\Sms\SmsHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Proof, kept in the visitor's session, that they confirmed a mobile number by OTP.
 *
 * Public forms (join, matrimonial, help, volunteer, contact, nominations,
 * corrections, data deletion) only accept a mobile number listed here, or the
 * signed-in member's own already-verified number.
 *
 * Safety valve: while SMS is switched off or not configured in Admin → SMS
 * Settings, nothing is enforced (a gateway outage must not block every form).
 */
class VerifiedMobiles
{
    public const SESSION_KEY = 'verified_mobiles';
    public const VALID_MINUTES = 30;

    /** Is OTP verification currently required on forms and member pages? */
    public static function enforced(): bool
    {
        $configured = SmsConfig::configured();
        $enforced = $configured && ! SmsHealth::isDegraded();

        if (! $enforced && Cache::add('mobile-otp-not-enforced-warning', true, 3600)) {
            Log::warning($configured
                ? 'Mobile OTP verification is NOT enforced: the SMS gateway is degraded (see SMS Settings).'
                : 'Mobile OTP verification is NOT enforced: SMS is switched off or not configured in Admin → SMS Settings.');
        }

        return $enforced;
    }

    public static function normalize(?string $mobile): ?string
    {
        return $mobile === null ? null : SmartPingSms::normalizeMobile($mobile);
    }

    /**
     * Replace a request's mobile with its bare 10-digit form before validation.
     * Unparseable input is left as typed so the format rule can reject it.
     */
    public static function normalizeRequest(\Illuminate\Http\Request $request, string $field = 'mobile'): void
    {
        if ($request->filled($field) && ($normal = self::normalize((string) $request->input($field)))) {
            $request->merge([$field => $normal]);
        }
    }

    public static function remember(string $mobile): void
    {
        $normalized = self::normalize($mobile);

        if ($normalized === null) {
            return;
        }

        $proofs = self::fresh();
        $proofs[$normalized] = now()->getTimestamp();
        session()->put(self::SESSION_KEY, $proofs);
    }

    /**
     * Was $mobile confirmed by OTP in this session within the last 30 minutes,
     * or is it the signed-in member's own verified number?
     */
    public static function has(?string $mobile): bool
    {
        $normalized = self::normalize($mobile);

        if ($normalized === null) {
            return false;
        }

        $user = auth()->user();
        if ($user && $user->mobile_verified_at && self::normalize($user->mobile) === $normalized) {
            return true;
        }

        return array_key_exists($normalized, self::fresh());
    }

    /**
     * Numbers the browser may show as already verified (only this visitor's own).
     *
     * @return list<string>
     */
    public static function forBrowser(): array
    {
        $numbers = array_keys(self::fresh());

        $user = auth()->user();
        if ($user && $user->mobile_verified_at && ($own = self::normalize($user->mobile))) {
            $numbers[] = $own;
        }

        return array_values(array_unique(array_map('strval', $numbers)));
    }

    /** @return array<string, int> unexpired proofs */
    private static function fresh(): array
    {
        if (! app()->bound('session.store') || ! request()->hasSession()) {
            return [];
        }

        $cutoff = now()->subMinutes(self::VALID_MINUTES)->getTimestamp();

        return array_filter(
            (array) session()->get(self::SESSION_KEY, []),
            fn ($at) => is_int($at) && $at >= $cutoff,
        );
    }
}
