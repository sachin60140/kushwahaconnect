<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of a member an admin has blocked, including existing and
 * "remember me" sessions, on their next request.
 */
class LogoutBlockedUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status === 'blocked') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'otp_login_blocked');
        }

        return $next($request);
    }
}
