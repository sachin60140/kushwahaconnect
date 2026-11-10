<?php

namespace App\Services\Otp;

use RuntimeException;

/**
 * A user-facing OTP failure. $reason is one of the OtpService reason constants and
 * $retryAfter (seconds) is set for cooldown / rate-limit failures.
 */
class OtpException extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly int $retryAfter = 0)
    {
        parent::__construct($reason);
    }
}
