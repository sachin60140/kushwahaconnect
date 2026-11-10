<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Otp\OtpException;
use App\Services\Otp\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets a signed-in member confirm that their mobile number is theirs with an SMS OTP.
 *
 * Validation errors carry dictionary keys (see resources/js/dictionary.js) so the
 * page can show them in Hindi or English.
 */
class MobileVerificationController extends Controller
{
    public function __construct(private OtpService $otp)
    {
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        return Inertia::render('Auth/VerifyMobile', [
            'mobile' => $user->mobile ? self::mask($user->mobile) : null,
            'verified' => $user->mobile_verified_at !== null,
            'otp' => $user->mobile ? $this->otp->status($user->mobile, OtpService::PURPOSE_VERIFY) : null,
            'codeLength' => OtpService::LENGTH,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->mobile) {
            throw ValidationException::withMessages(['otp' => 'otp_error_no_mobile']);
        }

        if ($user->mobile_verified_at) {
            return redirect()->route('mobile.verify.show');
        }

        try {
            $this->otp->send($user->mobile, OtpService::PURPOSE_VERIFY, $request->ip());
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['otp' => self::errorKey($e)]);
        }

        return redirect()->route('mobile.verify.show')->with('status', 'otp_sent');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{'.OtpService::LENGTH.'}$/'],
        ], [
            'code.required' => 'otp_error_code_format',
            'code.regex' => 'otp_error_code_format',
        ]);

        $user = $request->user();

        if (! $user->mobile) {
            throw ValidationException::withMessages(['code' => 'otp_error_no_mobile']);
        }

        try {
            $this->otp->verify($user->mobile, OtpService::PURPOSE_VERIFY, $request->input('code'));
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['code' => self::errorKey($e)]);
        }

        $user->forceFill(['mobile_verified_at' => now()])->save();

        return redirect()->intended(route('dashboard', absolute: false))->with('success', 'otp_verified_success');
    }

    public static function errorKey(OtpException $e): string
    {
        return match ($e->reason) {
            OtpService::COOLDOWN => 'otp_error_cooldown',
            OtpService::RATE_LIMITED => 'otp_error_rate_limited',
            OtpService::SEND_FAILED => 'otp_error_send_failed',
            OtpService::EXPIRED => 'otp_error_expired',
            OtpService::TOO_MANY_ATTEMPTS => 'otp_error_too_many_attempts',
            default => 'otp_error_invalid',
        };
    }

    public static function mask(string $mobile): string
    {
        return strlen($mobile) > 4
            ? str_repeat('•', strlen($mobile) - 4).substr($mobile, -4)
            : $mobile;
    }
}
