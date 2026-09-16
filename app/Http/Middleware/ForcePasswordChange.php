<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->must_change_password) {
                // Allow only force change routes, logout, or static assets
                if (!$request->routeIs('password.force_change*') && !$request->routeIs('logout')) {
                    return redirect()->route('password.force_change')
                        ->with('warning', 'You are using a temporary one-time password. Please set a permanent password to continue.');
                }
            }
        }

        return $next($request);
    }
}