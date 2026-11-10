<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * SmartPing (India, DLT) SMS gateway driver.
 *
 * Request: form POST to {base_url} with username, password, unicode=false, from, text, to,
 * dltContentId, dltPrincipalEntityId (SmartPing HTTP API; parameter names are case-sensitive).
 * SmartPing answers HTTP 200 even when a send fails, so success is judged from the JSON
 * body: state SUBMIT_ACCEPTED (or body statusCode 200). Anything else is a failure.
 *
 * Credentials and the code are never logged; query strings are stripped from error text
 * as a safety net.
 */
class SmartPingSms implements SmsSender
{
    public const MAX_PER_HOUR = 300;
    public const MAX_PER_DAY = 1500;

    /**
     * Guest form OTPs may use at most this share of the hourly / daily cap, so
     * anonymous traffic cannot use up the SMS needed for login and verification.
     */
    public const FORM_SHARE = 0.6;

    public function __construct(private array $config)
    {
    }

    public function send(string $mobile, string $message, string $purpose = 'other'): void
    {
        $result = $this->deliver($mobile, $message, $purpose);

        if (! $result['ok']) {
            throw new SmsException($result['description'] ?: 'SMS was not accepted by the gateway.');
        }
    }

    /**
     * Send one SMS and report what the gateway said. Never throws.
     *
     * @return array{ok: bool, http: ?int, state: ?string, status_code: ?int, description: string, transaction_id: ?string, hint: ?string}
     */
    public function deliver(string $mobile, string $message, string $purpose = 'other'): array
    {
        $to = self::normalizeMobile($mobile);

        if ($to === null) {
            return self::result(false, description: 'Invalid mobile number. Use a 10-digit Indian number.');
        }

        if (! self::allowedBaseUrl($this->config['base_url'] ?? null)) {
            return self::result(false, description: 'SmartPing API URL must be an https://….smartping.io address.');
        }

        foreach (['base_url' => 'API URL', 'username' => 'user ID', 'password' => 'password', 'sender_id' => 'sender ID'] as $key => $label) {
            if (blank($this->config[$key] ?? null)) {
                return self::result(false, description: "SmartPing is not configured: missing {$label}.");
            }
        }

        if (! $this->takePlatformBudget($purpose)) {
            return self::result(false, description: 'Platform SMS limit reached. Please try again later.');
        }

        try {
            // Form POST (SmartPing's recommended method) keeps the password and code out of URLs.
            $response = Http::asForm()
                ->timeout((int) ($this->config['timeout'] ?? 15))
                ->acceptJson()
                ->post($this->config['base_url'], [
                    'username' => $this->config['username'],
                    'password' => $this->config['password'],
                    'unicode' => 'false',
                    'from' => $this->config['sender_id'],
                    'text' => $message,
                    'to' => $to,
                    'dltContentId' => $this->config['dlt_content_id'] ?? '',
                    'dltPrincipalEntityId' => $this->config['dlt_principal_entity_id'] ?? '',
                ]);
        } catch (Throwable $e) {
            $error = self::stripQuery($e->getMessage());
            Log::warning('SmartPing request failed.', ['to' => self::mask($to), 'error' => $error]);
            SmsHealth::record(false, connectionError: true);

            return self::result(false, description: 'Could not reach SmartPing: '.$error);
        }

        $body = $response->json();
        $state = is_array($body) && isset($body['state']) ? (string) $body['state'] : null;
        $statusCode = is_array($body) && isset($body['statusCode']) && is_numeric($body['statusCode']) ? (int) $body['statusCode'] : null;
        $description = is_array($body) && isset($body['description']) ? self::stripQuery((string) $body['description']) : '';
        $transactionId = is_array($body) && isset($body['transactionId']) && is_scalar($body['transactionId']) ? (string) $body['transactionId'] : null;

        $ok = $response->successful() && ($state === 'SUBMIT_ACCEPTED' || $statusCode === 200);

        // Gateway health and usage counters (fallback when SmartPing itself is failing).
        SmsHealth::record($ok, $ok ? null : $statusCode, connectionError: ! $ok && $response->serverError());

        if ($ok) {
            // transactionId ties this send to SmartPing's delivery reports.
            Log::info('SmartPing accepted SMS.', ['to' => self::mask($to), 'transaction_id' => $transactionId]);
        } else {
            if ($description === '') {
                $description = $state || $statusCode
                    ? 'SMS was not accepted by the gateway.'
                    : 'Unexpected response from SmartPing (HTTP '.$response->status().').';
            }

            Log::warning('SmartPing did not accept the SMS.', [
                'to' => self::mask($to),
                'http' => $response->status(),
                'state' => $state,
                'status_code' => $statusCode,
                'description' => $description,
            ]);
        }

        return self::result($ok, $response->status(), $state, $statusCode, $description ?: ($ok ? 'Accepted' : ''), $transactionId);
    }

    /**
     * Dictionary key with a plain-language explanation of a SmartPing error code, if known.
     * Best reading of SmartPing's API document; the raw description is always shown too.
     */
    public static function errorHint(?int $statusCode): ?string
    {
        return match ($statusCode) {
            2051 => 'sms_gateway_error_sender',
            2054 => 'sms_gateway_error_number',
            2070 => 'sms_gateway_error_auth',
            6001 => 'sms_gateway_error_balance',
            7001 => 'sms_gateway_error_template',
            default => null,
        };
    }

    /**
     * Bare 10-digit Indian mobile, or null. Strips +91 / 91 / 0 prefixes.
     */
    public static function normalizeMobile(string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', $mobile);

        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        return preg_match('/^[6-9]\d{9}$/', $digits) ? $digits : null;
    }

    public static function stripQuery(string $text): string
    {
        // user:password@ in a URL, then any query string.
        $text = preg_replace('~(https?://)[^\s/@]+@~i', '$1[credentials removed]@', $text);

        return preg_replace('~(https?://[^\s?#]+)\?\S*~i', '$1?[query removed]', $text);
    }

    /** Only SmartPing's own HTTPS endpoints may be used as the API URL (no SSRF to other hosts). */
    public static function allowedBaseUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        return ($parts['scheme'] ?? '') === 'https'
            && ! isset($parts['user'])
            && ! isset($parts['port'])
            && ($host === 'smartping.io' || str_ends_with($host, '.smartping.io'));
    }

    public static function mask(string $mobile): string
    {
        return str_repeat('•', max(strlen($mobile) - 4, 0)).substr($mobile, -4);
    }

    /**
     * Platform-wide cap on real SMS so abuse of public forms cannot run up the bill.
     * Fails open if the cache store is unavailable.
     */
    private function takePlatformBudget(string $purpose = 'other'): bool
    {
        try {
            // [key, window seconds, limit]: the whole platform, plus the guest-form share.
            $buckets = [
                ['sms-platform:hour', 3600, self::MAX_PER_HOUR],
                ['sms-platform:day', 86400, self::MAX_PER_DAY],
            ];
            if ($purpose === 'form') {
                $buckets[] = ['sms-platform:form:hour', 3600, (int) floor(self::MAX_PER_HOUR * self::FORM_SHARE)];
                $buckets[] = ['sms-platform:form:day', 86400, (int) floor(self::MAX_PER_DAY * self::FORM_SHARE)];
            }

            $taken = [];
            foreach ($buckets as [$key, $seconds, $limit]) {
                $count = RateLimiter::hit($key, $seconds);
                $taken[] = $key;

                if ($count > $limit) {
                    foreach ($taken as $undo) {
                        RateLimiter::decrement($undo);
                    }
                    Log::warning('SMS budget exceeded; SMS refused.', ['bucket' => $key, 'limit' => $limit]);
                    SmsHealth::capReached($key);

                    return false;
                }
            }
        } catch (Throwable $e) {
            Log::warning('SMS budget check unavailable; allowing send.', ['error' => $e->getMessage()]);
        }

        return true;
    }

    private static function result(bool $ok, ?int $http = null, ?string $state = null, ?int $statusCode = null, string $description = '', ?string $transactionId = null): array
    {
        return [
            'ok' => $ok,
            'http' => $http,
            'state' => $state,
            'status_code' => $statusCode,
            'description' => $description,
            'transaction_id' => $transactionId,
            'hint' => $ok ? null : self::errorHint($statusCode),
        ];
    }
}
