<?php

namespace App\Http\Middleware;

use App\Services\Otp\VerifiedMobiles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Member pages need a verified mobile number (users.mobile_verified_at).
 * Unverified members are sent to the verify-mobile page and returned to the
 * page they wanted afterwards. Admins are not affected, and nothing is
 * enforced while SMS is off (see VerifiedMobiles::enforced()).
 */
class EnsureMobileIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user
            || $user->mobile_verified_at
            || in_array($user->role, ['admin', 'super_admin'], true)
            || ! VerifiedMobiles::enforced()) {
            return $next($request);
        }

        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('mobile.verify.show')->with('status', 'otp_required_member');
    }
}
