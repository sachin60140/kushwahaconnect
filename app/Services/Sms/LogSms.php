<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Development driver: writes the SMS to the log instead of sending it.
 * Message text (which may hold an OTP) is only written outside production.
 */
class LogSms implements SmsSender
{
    public function send(string $mobile, string $message, string $purpose = 'other'): void
    {
        // Codes and full numbers only ever reach the log on a developer machine or in tests.
        if (app()->environment('local', 'testing')) {
            Log::info("SMS to {$mobile}: {$message}");

            return;
        }

        Log::warning('SMS_DRIVER=log is active outside local/testing; SMS was not sent.', [
            'mobile' => substr($mobile, 0, 2).'******'.substr($mobile, -2),
        ]);
    }
}
