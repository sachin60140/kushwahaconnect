<?php

namespace App\Services\Sms;

use App\Mail\SmsDegradedAlert;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Health of the SMS gateway, plus a small daily usage counter.
 *
 * "Degraded": SmartPing is failing on its side (no balance, bad credentials,
 * sender or DLT template not registered, or repeated connection errors). For
 * DEGRADED_MINUTES, OTP is then not enforced (same as SMS off), so a gateway
 * problem cannot block every form. A successful send, the admin "Retry SMS now"
 * button, or the window running out ends the episode.
 *
 * Everything lives in the cache: no message text, numbers or codes are stored.
 */
class SmsHealth
{
    /** SmartPing status codes that mean the gateway/account, not the recipient, is the problem. */
    public const DEGRADE_CODES = [2051, 2070, 6001, 7001];
    public const REASON_CONNECTION = 'connection';

    public const DEGRADED_MINUTES = 30;
    public const CONNECTION_FAILURE_THRESHOLD = 3;
    public const CONNECTION_WINDOW_MINUTES = 5;

    private const DEGRADED_KEY = 'sms-health:degraded';
    private const CONNECTION_FAILURES_KEY = 'sms-health:connection-failures';
    private const LAST_SUCCESS_KEY = 'sms-health:last-success';
    private const CAP_HIT_KEY = 'sms-health:cap-hit';

    /**
     * Record what the gateway said for one send.
     *
     * @param  bool  $connectionError  no usable answer: timeout, connection error, HTTP 5xx
     */
    public static function record(bool $ok, ?int $statusCode = null, bool $connectionError = false): void
    {
        try {
            if ($ok) {
                self::increment('sent:'.now()->toDateString(), 2);
                self::increment('sent-month:'.now()->format('Y-m'), 40);
                Cache::put(self::LAST_SUCCESS_KEY, now()->toIso8601String(), now()->addDays(60));
                Cache::forget(self::CONNECTION_FAILURES_KEY);

                if (self::degraded()) {
                    self::clear('send succeeded');
                }

                return;
            }

            self::increment('failed:'.now()->toDateString(), 2);

            if ($statusCode !== null && in_array($statusCode, self::DEGRADE_CODES, true)) {
                self::degrade((string) $statusCode);

                return;
            }

            if ($connectionError) {
                Cache::add(self::CONNECTION_FAILURES_KEY, 0, now()->addMinutes(self::CONNECTION_WINDOW_MINUTES));
                if (Cache::increment(self::CONNECTION_FAILURES_KEY) >= self::CONNECTION_FAILURE_THRESHOLD) {
                    Cache::forget(self::CONNECTION_FAILURES_KEY);
                    self::degrade(self::REASON_CONNECTION);
                }
            }
        } catch (Throwable $e) {
            // Health tracking must never break sending.
            Log::warning('SMS health tracking failed.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * An SMS budget (platform-wide or the guest-form share) ran out. Shown on the
     * health card for an hour; super admins are emailed once per bucket per hour.
     */
    public static function capReached(string $bucket): void
    {
        try {
            Cache::put(self::CAP_HIT_KEY, ['bucket' => $bucket, 'at' => now()->toIso8601String()], now()->addHour());

            if (Cache::add('sms-health:cap-alert:'.$bucket, true, now()->addHour())) {
                Log::warning('SMS budget reached; further SMS refused until the window resets.', ['bucket' => $bucket]);
                self::alertSuperAdmins(['reason' => 'cap', 'since' => now()->toIso8601String()]);
            }
        } catch (Throwable $e) {
            Log::warning('SMS cap tracking failed.', ['error' => $e->getMessage()]);
        }
    }

    /** @return array{reason: string, since: string, until: string}|null */
    public static function degraded(): ?array
    {
        try {
            $value = Cache::get(self::DEGRADED_KEY);
        } catch (Throwable) {
            return null;
        }

        return is_array($value) ? $value : null;
    }

    public static function isDegraded(): bool
    {
        return self::degraded() !== null;
    }

    public static function clear(string $why = 'manual retry'): void
    {
        $was = self::degraded();
        Cache::forget(self::DEGRADED_KEY);
        Cache::forget(self::CONNECTION_FAILURES_KEY);

        if ($was) {
            Log::info('SMS gateway back to normal; OTP enforcement resumes.', ['reason' => $was['reason'], 'cleared_by' => $why]);
        }
    }

    /**
     * Usage card data for the admin dashboard and SMS Settings.
     *
     * @return array{status: string, sent_today: int, failed_today: int, sent_month: int, last_success_at: ?string, degraded: ?array, cap_hit: ?array}
     */
    public static function usage(): array
    {
        $degraded = self::degraded();

        return [
            'status' => ! SmsConfig::configured() ? 'off' : ($degraded ? 'degraded' : 'ok'),
            'sent_today' => self::count('sent:'.now()->toDateString()),
            'failed_today' => self::count('failed:'.now()->toDateString()),
            'sent_month' => self::count('sent-month:'.now()->format('Y-m')),
            'last_success_at' => Cache::get(self::LAST_SUCCESS_KEY),
            'degraded' => $degraded ? [...$degraded, 'reason_key' => self::reasonKey($degraded['reason'])] : null,
            // { bucket, at } while an SMS budget is exhausted (this hour).
            'cap_hit' => Cache::get(self::CAP_HIT_KEY),
        ];
    }

    /** Dictionary key explaining a degrade reason in plain words. */
    public static function reasonKey(string $reason): string
    {
        return match ($reason) {
            '6001' => 'sms_degraded_reason_balance',
            '2070' => 'sms_degraded_reason_auth',
            '2051' => 'sms_degraded_reason_sender',
            '7001' => 'sms_degraded_reason_template',
            default => 'sms_degraded_reason_connection',
        };
    }

    private static function degrade(string $reason): void
    {
        $episode = [
            'reason' => $reason,
            'since' => now()->toIso8601String(),
            'until' => now()->addMinutes(self::DEGRADED_MINUTES)->toIso8601String(),
        ];

        // add(): only the first failure of an episode starts it, logs and emails.
        if (! Cache::add(self::DEGRADED_KEY, $episode, now()->addMinutes(self::DEGRADED_MINUTES))) {
            return;
        }

        Log::warning('SMS gateway degraded; OTP enforcement paused.', [
            'reason' => $reason,
            'minutes' => self::DEGRADED_MINUTES,
        ]);

        self::alertSuperAdmins($episode);
    }

    private static function alertSuperAdmins(array $episode): void
    {
        if (! self::mailConfigured()) {
            return;
        }

        $recipients = User::where('role', 'super_admin')->whereNotNull('email')->pluck('email')->filter()->all();
        if ($recipients === []) {
            return;
        }

        try {
            Mail::to($recipients)->send(new SmsDegradedAlert($episode['reason'], $episode['since']));
        } catch (Throwable $e) {
            Log::warning('Could not email the SMS degraded alert.', ['error' => $e->getMessage()]);
        }
    }

    private static function mailConfigured(): bool
    {
        $mailer = (string) config('mail.default');

        if ($mailer === '' || $mailer === 'log') {
            return false;
        }

        if ($mailer === 'smtp') {
            return filled(config('mail.mailers.smtp.host')) && filled(config('mail.mailers.smtp.password'));
        }

        return true;
    }

    private static function increment(string $key, int $days): void
    {
        $key = 'sms-usage:'.$key;
        Cache::add($key, 0, now()->addDays($days));
        Cache::increment($key);
    }

    private static function count(string $key): int
    {
        try {
            return (int) Cache::get('sms-usage:'.$key, 0);
        } catch (Throwable) {
            return 0;
        }
    }
}
