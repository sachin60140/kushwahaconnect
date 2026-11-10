<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\Otp\OtpException;
use App\Services\Otp\OtpService;
use App\Services\Otp\VerifiedMobiles;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Sign in with a one-time SMS code sent to the member's registered mobile.
 *
 * Errors are dictionary keys (resources/js/dictionary.js) so the login page
 * shows them in Hindi or English. Available only while SMS is on and healthy
 * (VerifiedMobiles::enforced()); otherwise members use their password.
 */
class OtpLoginController extends Controller
{
    /** Number lookups per IP per hour: "no account found" must not become a number scanner. */
    public const MAX_LOOKUPS_PER_IP_PER_HOUR = 20;

    /** Session key holding the number a login code was sent to (the visitor's own input). */
    public const SESSION_KEY = 'otp_login_mobile';

    public function __construct(private OtpService $otp)
    {
    }

    /**
     * Login page data for the OTP option.
     *
     * @return array{available: bool, mobile: ?string, resend_in: int, expires_in: int, codeLength: int}
     */
    public static function pageState(Request $request): array
    {
        $mobile = $request->session()->get(self::SESSION_KEY);
        $status = $mobile ? app(OtpService::class)->status($mobile, OtpService::PURPOSE_LOGIN) : ['resend_in' => 0, 'expires_in' => 0];

        return [
            'available' => VerifiedMobiles::enforced(),
            'mobile' => $mobile,
            'resend_in' => $status['resend_in'],
            'expires_in' => $status['expires_in'],
            'codeLength' => OtpService::LENGTH,
        ];
    }

    public function generateOtp(Request $request): RedirectResponse
    {
        if (! VerifiedMobiles::enforced()) {
            throw ValidationException::withMessages(['mobile' => 'otp_login_unavailable']);
        }

        $mobile = $this->mobile($request);

        $this->takeLookup($request, 'mobile');

        // Only existing members can log in with OTP. Accounts are created through the
        // Join form; auto-creating users here would reserve the number and block the
        // real owner from joining later.
        $user = User::where('mobile', $mobile)->first();

        if (! $user) {
            throw ValidationException::withMessages(['mobile' => 'otp_login_no_account']);
        }

        if ($user->status === 'blocked') {
            throw ValidationException::withMessages(['mobile' => 'otp_login_blocked']);
        }

        try {
            $this->otp->send($user->mobile, OtpService::PURPOSE_LOGIN, $request->ip());
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['mobile' => MobileVerificationController::errorKey($e)]);
        }

        $request->session()->put(self::SESSION_KEY, $mobile);

        return back()->with('status', 'otp_login_sent');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $mobile = $this->mobile($request);

        $request->validate([
            'otp' => ['required', 'string', 'regex:/^\d{'.OtpService::LENGTH.'}$/'],
        ], [
            'otp.required' => 'otp_error_code_format',
            'otp.regex' => 'otp_error_code_format',
        ]);

        $this->takeLookup($request, 'otp');

        // Only the number this browser asked a code for can be checked. Anything else gets
        // the same generic answer, never touches that member's code or attempt budget, and
        // so cannot be used to find registered numbers or lock their owners out.
        if ($mobile !== $request->session()->get(self::SESSION_KEY)) {
            throw ValidationException::withMessages(['otp' => 'otp_error_invalid']);
        }

        $user = User::where('mobile', $mobile)->first();

        if (! $user || $user->status === 'blocked') {
            throw ValidationException::withMessages(['otp' => 'otp_error_invalid']);
        }

        try {
            $this->otp->verify($user->mobile, OtpService::PURPOSE_LOGIN, (string) $request->input('otp'));
        } catch (OtpException $e) {
            $this->logLoginHistory($user->id, $request, 'failed');

            throw ValidationException::withMessages(['otp' => MobileVerificationController::errorKey($e)]);
        }

        // Receiving the code proves the member holds this number.
        if (! $user->mobile_verified_at) {
            $user->forceFill(['mobile_verified_at' => Carbon::now()])->save();
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();
        $request->session()->forget(self::SESSION_KEY);

        $this->logLoginHistory($user->id, $request, 'success');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** Per-IP budget for number lookups (code requests and code checks). */
    private function takeLookup(Request $request, string $field): void
    {
        $key = 'otp-login-lookup:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_LOOKUPS_PER_IP_PER_HOUR)) {
            throw ValidationException::withMessages([$field => 'otp_error_rate_limited']);
        }

        RateLimiter::hit($key, 3600);
    }

    /** "Use a different number": forget the pending login number. */
    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return back();
    }

    private function mobile(Request $request): string
    {
        $mobile = VerifiedMobiles::normalize((string) $request->input('mobile'));

        if ($mobile === null) {
            throw ValidationException::withMessages(['mobile' => 'otp_error_mobile_format']);
        }

        return $mobile;
    }

    protected function logLoginHistory($userId, Request $request, $status)
    {
        LoginHistory::create([
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_at' => Carbon::now(),
            'status' => $status,
        ]);
    }
}
