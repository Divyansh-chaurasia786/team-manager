<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        if (!Auth::check() || Auth::user()->role !== $role) {
            if (Auth::check()) {
                $dashboardRoute = match(Auth::user()->role) {
                    'tl'     => 'tl.dashboard',
                    'hr'     => 'hr.dashboard',
                    'ceo'    => 'ceo.dashboard',
                    default  => 'member.dashboard',
                };
                return redirect()->route($dashboardRoute)
                    ->with('error', 'Unauthorized access.');
            }
            return redirect()->route('login');
        }
        return $next($request);
    }
}
