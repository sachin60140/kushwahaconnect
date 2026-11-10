<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\MobileVerificationController;
use App\Services\Otp\OtpException;
use App\Services\Otp\OtpService;
use App\Services\Otp\VerifiedMobiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inline "Send OTP → enter code → Verified ✓" step used next to the mobile
 * field of public forms (resources/js/Components/MobileOtpField.vue).
 *
 * Reuses OtpService, so the same hashing, expiry, attempt limits, resend
 * cooldown, per-number / per-IP limits and platform SMS budget apply.
 * Responses never contain the code.
 */
class FormOtpController extends Controller
{
    public function __construct(private OtpService $otp)
    {
    }

    public function send(Request $request): JsonResponse
    {
        $mobile = $this->mobile($request);

        if (VerifiedMobiles::has($mobile)) {
            return response()->json(['verified' => true]);
        }

        // While SMS is degraded, guest form codes never reach the gateway: no balance is
        // spent and guest traffic cannot keep the failure state going. Recovery comes
        // from the timed retry window or "Retry SMS now".
        if (\App\Services\Sms\SmsConfig::configured() && \App\Services\Sms\SmsHealth::isDegraded()) {
            return response()->json(['message' => 'otp_field_degraded', 'retry_after' => 0, 'degraded' => true], 422);
        }

        try {
            $this->otp->send($mobile, OtpService::PURPOSE_FORM, $request->ip());
        } catch (OtpException $e) {
            // This failure may have put SMS into degraded mode: OTP is no longer required,
            // so the form can switch to the "SMS unavailable" note without a reload.
            $degraded = ! VerifiedMobiles::enforced() && \App\Services\Sms\SmsHealth::isDegraded();

            return response()->json([
                'message' => $degraded ? 'otp_field_degraded' : MobileVerificationController::errorKey($e),
                'retry_after' => $e->retryAfter,
                'degraded' => $degraded,
            ], 422);
        }

        return response()->json([
            'sent' => true,
            ...$this->otp->status($mobile, OtpService::PURPOSE_FORM),
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $mobile = $this->mobile($request);

        $code = (string) $request->input('code');
        if (! preg_match('/^\d{'.OtpService::LENGTH.'}$/', $code)) {
            return response()->json(['message' => 'otp_error_code_format'], 422);
        }

        try {
            $this->otp->verify($mobile, OtpService::PURPOSE_FORM, $code);
        } catch (OtpException $e) {
            return response()->json(['message' => MobileVerificationController::errorKey($e)], 422);
        }

        VerifiedMobiles::remember($mobile);

        return response()->json(['verified' => true, 'mobile' => $mobile]);
    }

    private function mobile(Request $request): string
    {
        $mobile = VerifiedMobiles::normalize((string) $request->input('mobile'));

        if ($mobile === null) {
            abort(response()->json(['message' => 'otp_error_mobile_format'], 422));
        }

        return $mobile;
    }
}
