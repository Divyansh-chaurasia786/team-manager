<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->input('login'));
        $loginField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $loginField => $loginInput,
            'password'  => $request->input('password'),
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Check if user must change one-time password
            if ($user->must_change_password) {
                return redirect()->route('password.force_change');
            }

            $dashboardRoute = match($user->role) {
                'tl'    => 'tl.dashboard',
                'hr'    => 'hr.dashboard',
                'ceo'   => 'ceo.dashboard',
                default => 'member.dashboard',
            };
            return redirect()->route($dashboardRoute);
        }

        return back()->withErrors(['login' => 'Invalid username/email or password.'])->withInput($request->only('login', 'remember'));
    }

    public function showForceChangePassword()
    {
        $user = Auth::user();
        return view('auth.force_change_password', compact('user'));
    }

    public function updateForceChangePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:6|confirmed|different:current_password',
        ], [
            'password.different' => 'Your new password cannot be the same as your one-time temporary password.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current temporary password you entered is incorrect.']);
        }

        $user->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
            'temp_password_plain'  => null,
        ]);

        // Audit Log
        ActivityLog::log(
            action: 'password_changed',
            description: sprintf('%s updated their one-time temporary password to a permanent password', $user->name),
            entityType: 'User',
            entityId: $user->id
        );

        $dashboardRoute = match($user->role) {
            'tl'    => 'tl.dashboard',
            'hr'    => 'hr.dashboard',
            'ceo'   => 'ceo.dashboard',
            default => 'member.dashboard',
        };
        return redirect()->route($dashboardRoute)->with('success', 'Password successfully updated! Welcome to your dashboard.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}