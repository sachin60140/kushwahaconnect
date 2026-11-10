<?php

namespace App\Http\Middleware;

use App\Support\AppVersion;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'unread_notifications_count' => $request->user() ? $request->user()->unreadNotifications()->count() : 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'websiteSettings' => fn () => \App\Models\WebsiteSetting::pluck('value', 'key')->toArray(),
            'locale' => app()->getLocale(),
            'seo' => fn () => Seo::forRequest($request),
            'appVersion' => fn () => AppVersion::info(),
            'displayTimeZone' => config('app.display_timezone', 'Asia/Kolkata'),
            // Inline mobile OTP on forms: whether it is required, and this visitor's own verified numbers.
            'mobileOtp' => fn () => [
                'required' => \App\Services\Otp\VerifiedMobiles::enforced(),
                'verified' => \App\Services\Otp\VerifiedMobiles::forBrowser(),
                'codeLength' => \App\Services\Otp\OtpService::LENGTH,
            ],
        ];
    }
}
