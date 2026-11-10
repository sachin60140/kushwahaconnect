<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // The "email" field accepts an email address or a mobile number, because members
        // can join without an email and would otherwise be unable to log back in.
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! $this->attemptLogin()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // A blocked account must not get in with its password either.
        if (Auth::user()?->status === 'blocked') {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => 'otp_login_blocked',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function attemptLogin(): bool
    {
        $login = trim((string) $this->input('email'));
        $password = (string) $this->input('password');
        $remember = $this->boolean('remember');

        if (str_contains($login, '@')) {
            return Auth::attempt(['email' => $login, 'password' => $password], $remember);
        }

        $candidates = [$login];
        $digits = preg_replace('/\D+/', '', $login);

        // Accept "+91 98765 43210", "098765-43210" etc. for a stored 10-digit number.
        if (strlen($digits) > 10 && (str_starts_with($digits, '91') || str_starts_with($digits, '0'))) {
            $digits = substr($digits, -10);
        }

        if ($digits !== '' && $digits !== $login) {
            $candidates[] = $digits;
        }

        foreach ($candidates as $mobile) {
            if (Auth::attempt(['mobile' => $mobile, 'password' => $password], $remember)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
