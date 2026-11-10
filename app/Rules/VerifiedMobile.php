<?php

namespace App\Rules;

use App\Services\Otp\VerifiedMobiles;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The submitted mobile number must have been confirmed by OTP in this session
 * (see VerifiedMobiles). Errors are dictionary keys shown via __() in the UI.
 *
 * $unchanged: a number already on file (e.g. the member's saved profile value)
 * that may be re-submitted without a new OTP. Only edits need verifying.
 */
class VerifiedMobile implements ValidationRule
{
    public function __construct(private ?string $unchanged = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '' || ! VerifiedMobiles::enforced()) {
            return;
        }

        $normalized = VerifiedMobiles::normalize($value);

        if ($normalized === null) {
            $fail('otp_error_mobile_format');

            return;
        }

        if ($this->unchanged !== null && VerifiedMobiles::normalize($this->unchanged) === $normalized) {
            return;
        }

        if (! VerifiedMobiles::has($value)) {
            $fail('otp_error_mobile_not_verified');
        }
    }
}
