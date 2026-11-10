<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\Otp\OtpException;
use App\Services\Otp\OtpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OtpLoginController extends Controller
{
    public function __construct(private OtpService $otp)
    {
    }

    /**
     * Generate and send OTP to the given mobile number.
     */
    public function generateOtp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
        ], [
            'mobile.regex' => 'Please enter a valid 10-digit Indian mobile number.',
        ]);

        $mobile = $request->mobile;

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

        try {
            $this->otp->send($mobile, OtpService::PURPOSE_LOGIN, $request->ip());
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['mobile' => self::message($e)]);
        }

        return back()->with('status', 'otp_sent');
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

        if (! $user) {
            throw ValidationException::withMessages([
                'mobile' => 'No account found with this mobile number.',
            ]);
        }

        if ($user->status === 'blocked') {
            throw ValidationException::withMessages([
                'mobile' => 'This account has been blocked by the administrator.',
            ]);
        }

        try {
            $this->otp->verify($user->mobile, OtpService::PURPOSE_LOGIN, (string) $request->otp);
        } catch (OtpException $e) {
            $this->logLoginHistory($user->id, $request, 'failed');

            throw ValidationException::withMessages(['otp' => self::message($e)]);
        }

        // Receiving the code proves the member holds this number.
        if (! $user->mobile_verified_at) {
            $user->forceFill(['mobile_verified_at' => Carbon::now()])->save();
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        $this->logLoginHistory($user->id, $request, 'success');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private static function message(OtpException $e): string
    {
        return match ($e->reason) {
            OtpService::COOLDOWN, OtpService::RATE_LIMITED => "Too many attempts. Please try again in {$e->retryAfter} seconds.",
            OtpService::SEND_FAILED => 'We could not send the OTP right now. Please try again later.',
            OtpService::EXPIRED => 'The provided OTP has expired. Please request a new one.',
            OtpService::TOO_MANY_ATTEMPTS => 'Too many incorrect attempts. Please request a new OTP.',
            default => 'The provided OTP is incorrect.',
        };
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
