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

        // Find or create the user (if registering via OTP)
        $user = User::firstOrCreate(
            ['mobile' => $mobile],
            [
                'name' => 'User ' . substr($mobile, -4),
                'status' => 'profile_incomplete',
                'language_preference' => 'en',
            ]
        );

        if ($user->status === 'blocked') {
            return back()->withErrors(['mobile' => 'This account has been blocked by the administrator.']);
        }

        // Generate a 6-digit OTP
        $otp = (app()->environment('local', 'testing')) ? '123456' : rand(100000, 999999);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // In a real scenario, integrate an SMS gateway here.
        Log::info("OTP for mobile {$mobile} is {$otp}");

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

        if (!$user->otp_code || $user->otp_code !== $request->otp) {
            $this->logLoginHistory($user->id, $request, 'failed');
            throw ValidationException::withMessages([
                'otp' => 'The provided OTP is incorrect.',
            ]);
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => 'The provided OTP has expired. Please request a new one.',
            ]);
        }

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
