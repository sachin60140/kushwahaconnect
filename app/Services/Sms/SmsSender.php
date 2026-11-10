<?php

namespace App\Services\Sms;

interface SmsSender
{
    /**
     * Send a transactional SMS to a 10-digit Indian mobile number.
     *
     * @throws SmsException when the gateway refuses or cannot be reached.
     */
    /** $purpose: OTP purpose (login / verify / form) or 'other'; used for SMS budgets. */
    public function send(string $mobile, string $message, string $purpose = 'other'): void;
}
