<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();
            if (!$user) {
                return route('login');
            }
            if ($user->must_change_password) {
                return route('password.force_change');
            }
            return match($user->role) {
                'tl'    => route('tl.dashboard'),
                'hr'    => route('hr.dashboard'),
                'ceo'   => route('ceo.dashboard'),
                default => route('member.dashboard'),
            };
        });
        $middleware->alias([
            'role'                 => \App\Http\Middleware\RoleMiddleware::class,
            'force_password_change' => \App\Http\Middleware\ForcePasswordChange::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\ForcePasswordChange::class,
            \App\Http\Middleware\PreventBrowserCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session refreshed. Please retry.'], 419);
            }
            return redirect()->back()->with('warning', 'Your page session was refreshed. Please try again.');
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 403) {
                if (app()->environment('testing')) {
                    return false;
                }
                $msg = $e->getMessage() ?: 'Unauthorized. You do not have permission to perform this action.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 403);
                }
                return redirect()->back()->with('error', $msg);
            }
            return false;
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if (app()->environment('testing')) {
                return false;
            }
            $msg = $e->getMessage() ?: 'Unauthorized. You do not have permission to perform this action.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        });
    })->create();