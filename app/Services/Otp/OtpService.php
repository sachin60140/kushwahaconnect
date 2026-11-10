<?php

namespace App\Services\Otp;

use App\Models\MobileOtp;
use App\Services\Sms\SmsConfig;
use App\Services\Sms\SmsException;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    public const LENGTH = 6;
    public const TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_SENDS_PER_MOBILE_PER_HOUR = 5; // per purpose
    public const MAX_SENDS_PER_MOBILE_ALL_PURPOSES_PER_HOUR = 8;
    public const MAX_SENDS_PER_IP_PER_HOUR = 10;

    public const PURPOSE_LOGIN = 'login';
    public const PURPOSE_VERIFY = 'verify';
    public const PURPOSE_FORM = 'form'; // inline check on public forms (FormOtpController)

    public const COOLDOWN = 'cooldown';
    public const RATE_LIMITED = 'rate_limited';
    public const SEND_FAILED = 'send_failed';
    public const INVALID = 'invalid';
    public const EXPIRED = 'expired';
    public const TOO_MANY_ATTEMPTS = 'too_many_attempts';

    public function __construct(private SmsSender $sms)
    {
    }

    /**
     * Generate a new code for $mobile, store its hash and send it by SMS.
     *
     * One send per number and purpose runs at a time (cache lock), and the rate
     * limits are counted before the SMS goes out, so parallel requests cannot
     * slip past them and failed sends still count.
     *
     * @throws OtpException
     */
    public function send(string $mobile, string $purpose, ?string $ip = null): void
    {
        $lock = Cache::lock("otp-send:{$mobile}:{$purpose}", 30);

        if (! $lock->get()) {
            throw new OtpException(self::COOLDOWN, self::RESEND_COOLDOWN_SECONDS);
        }

        try {
            $this->sendLocked($mobile, $purpose, $ip);
        } finally {
            $lock->release();
        }
    }

    private function sendLocked(string $mobile, string $purpose, ?string $ip): void
    {
        $existing = $this->find($mobile, $purpose);

        if ($existing && ($wait = $this->cooldownRemaining($existing)) > 0) {
            throw new OtpException(self::COOLDOWN, $wait);
        }

        // Per purpose, so nobody can use up a number's login codes by requesting form codes;
        // plus a modest overall cap per number across all purposes.
        $limits = [
            'otp-send-mobile:'.$purpose.':'.$mobile => self::MAX_SENDS_PER_MOBILE_PER_HOUR,
            'otp-send-mobile-all:'.$mobile => self::MAX_SENDS_PER_MOBILE_ALL_PURPOSES_PER_HOUR,
        ];
        if ($ip) {
            $limits['otp-send-ip:'.$ip] = self::MAX_SENDS_PER_IP_PER_HOUR;
        }

        foreach ($limits as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw new OtpException(self::RATE_LIMITED, RateLimiter::availableIn($key));
            }
        }

        // Count the attempt now: it stays counted even if the SMS then fails.
        foreach (array_keys($limits) as $key) {
            RateLimiter::hit($key, 3600);
        }

        $code = str_pad((string) random_int(0, 10 ** self::LENGTH - 1), self::LENGTH, '0', STR_PAD_LEFT);

        // DLT template: sent exactly as registered, with only {otp} filled in.
        $message = str_replace('{otp}', $code, (string) SmsConfig::resolve()['otp_message']);

        try {
            $this->sms->send($mobile, $message, $purpose);
        } catch (SmsException $e) {
            Log::error('OTP SMS could not be sent.', ['purpose' => $purpose, 'error' => $e->getMessage()]);

            throw new OtpException(self::SEND_FAILED);
        }

        MobileOtp::updateOrCreate(
            ['mobile' => $mobile, 'purpose' => $purpose],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'last_sent_at' => now(),
            ],
        );
    }

    /**
     * Check $code. The stored code is consumed on success, on expiry and once
     * the attempt budget is spent.
     *
     * Each check first claims one attempt with a single conditional UPDATE, so
     * parallel guesses cannot get past MAX_ATTEMPTS.
     *
     * @throws OtpException
     */
    public function verify(string $mobile, string $purpose, string $code): void
    {
        $otp = $this->find($mobile, $purpose);

        if (! $otp) {
            throw new OtpException(self::INVALID);
        }

        if ($otp->expires_at->isPast()) {
            $otp->delete();

            throw new OtpException(self::EXPIRED);
        }

        $claimed = MobileOtp::whereKey($otp->getKey())
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->increment('attempts');

        if ($claimed !== 1) {
            MobileOtp::whereKey($otp->getKey())->delete();

            throw new OtpException(self::TOO_MANY_ATTEMPTS);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            if ($otp->attempts + 1 >= self::MAX_ATTEMPTS) {
                MobileOtp::whereKey($otp->getKey())->delete();

                throw new OtpException(self::TOO_MANY_ATTEMPTS);
            }

            throw new OtpException(self::INVALID);
        }

        MobileOtp::whereKey($otp->getKey())->delete();
    }

    /**
     * Seconds until a new code may be sent, and until the current one expires.
     *
     * @return array{resend_in: int, expires_in: int}
     */
    public function status(string $mobile, string $purpose): array
    {
        $otp = $this->find($mobile, $purpose);

        return [
            'resend_in' => $otp ? $this->cooldownRemaining($otp) : 0,
            'expires_in' => $otp && $otp->expires_at->isFuture() ? (int) ceil(now()->diffInSeconds($otp->expires_at)) : 0,
        ];
    }

    private function find(string $mobile, string $purpose): ?MobileOtp
    {
        return MobileOtp::where('mobile', $mobile)->where('purpose', $purpose)->first();
    }

    private function cooldownRemaining(MobileOtp $otp): int
    {
        $ready = $otp->last_sent_at->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        return $ready->isFuture() ? (int) ceil(now()->diffInSeconds($ready)) : 0;
    }
}
