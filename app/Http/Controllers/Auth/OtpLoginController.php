<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;

class OtpLoginController extends Controller
{
    private const MAX_VERIFY_ATTEMPTS = 5;

    /**
     * Generate and send OTP to the given mobile number.
     */
    public function generateOtp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
        ], [
            'mobile.regex' => 'Please enter a valid 10-digit Indian mobile number.'
        ]);

        $mobile = $request->mobile;

        // Rate limiting: max 3 requests per minute per IP
        if (RateLimiter::tooManyAttempts('send-otp:'.$request->ip(), 3)) {
            $seconds = RateLimiter::availableIn('send-otp:'.$request->ip());
            return back()->withErrors(['mobile' => "Too many attempts. Please try again in {$seconds} seconds."]);
        }

        RateLimiter::hit('send-otp:'.$request->ip(), 60);

        // Only existing members can log in with OTP. Accounts are created through the
        // Join form; auto-creating users here would reserve the number and block the
        // real owner from joining later.
        $user = User::where('mobile', $mobile)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'mobile' => 'No account found with this mobile number. Please join the community first.',
            ]);
        }

        if ($user->status === 'blocked') {
            return back()->withErrors(['mobile' => 'This account has been blocked by the administrator.']);
        }

        $otp = app()->environment('local', 'testing') ? '123456' : (string) random_int(100000, 999999);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        RateLimiter::clear($this->verifyThrottleKey($mobile));

        // No SMS gateway is integrated yet. Never write real OTPs to production logs.
        if (app()->environment('local', 'testing')) {
            Log::info("OTP for mobile {$mobile} is {$otp}");
        }

        return back()->with('status', 'OTP sent successfully to ' . $mobile);
    }

    /**
     * Verify the OTP and login.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $throttleKey = $this->verifyThrottleKey($request->mobile);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_VERIFY_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'otp' => 'Too many incorrect attempts. Please request a new OTP.',
            ]);
        }

        $user = User::where('mobile', $request->mobile)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'mobile' => 'No account found with this mobile number.',
            ]);
        }

        if ($user->status === 'blocked') {
            throw ValidationException::withMessages([
                'mobile' => 'This account has been blocked by the administrator.',
            ]);
        }

        if (!$user->otp_code || !hash_equals((string) $user->otp_code, (string) $request->otp)) {
            RateLimiter::hit($throttleKey, 600);

            // Burn the OTP once the attempt budget is spent so it can't be brute-forced.
            if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_VERIFY_ATTEMPTS)) {
                $user->update(['otp_code' => null, 'otp_expires_at' => null]);
            }

            $this->logLoginHistory($user->id, $request, 'failed');
            throw ValidationException::withMessages([
                'otp' => 'The provided OTP is incorrect.',
            ]);
        }

        if (!$user->otp_expires_at || Carbon::now()->greaterThan($user->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => 'The provided OTP has expired. Please request a new one.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // OTP is correct and valid
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
            'mobile_verified_at' => $user->mobile_verified_at ?? Carbon::now(),
        ]);

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        $this->logLoginHistory($user->id, $request, 'success');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function verifyThrottleKey(string $mobile): string
    {
        return 'verify-otp:'.$mobile;
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
