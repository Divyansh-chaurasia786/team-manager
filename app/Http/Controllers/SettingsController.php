<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use App\Mail\SecurityOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    /**
     * Show the account settings page.
     */
    public function index()
    {
        $user = Auth::user();
        return view('settings.index', compact('user'));
    }

    /**
     * Update Profile Photo immediately (does not require OTP).
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
        ]);

        /** @var User $user */
        $user = Auth::user();

        // Delete old photo if it exists
        if ($user->profile_photo_path && file_exists(public_path($user->profile_photo_path))) {
            @unlink(public_path($user->profile_photo_path));
        }

        $dir = public_path('uploads/profile_photos');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $request->file('profile_photo');
        $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);

        $relativePath = 'uploads/profile_photos/' . $filename;
        $user->update([
            'profile_photo_path' => $relativePath,
        ]);

        Cache::forget("user_avatar_url_{$user->id}");

        ActivityLog::log(
            action: 'profile_photo_updated',
            description: sprintf('%s (%s) updated their profile picture', $user->name, $user->isTL() ? 'Team Lead' : 'Staff Member'),
            entityType: 'User',
            entityId: $user->id
        );

        return back()->with('success', 'Profile photo updated successfully!');
    }

    /**
     * Request update for sensitive info (Email, Mobile, Password, Name).
     * Dispatches a 6-digit OTP to current registered email.
     */
    public function requestUpdate(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email,' . $user->id,
            'mobile_number'         => 'nullable|string|max:20',
            'current_password'      => 'required|string',
            'new_password'          => 'nullable|string|min:8|confirmed',
        ]);

        // Verify current password first
        if (!Hash::check($request->current_password, $user->password)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided current password does not match our records.',
                    'errors' => ['current_password' => ['The provided current password does not match our records.']]
                ], 422);
            }
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.'])->withInput();
        }

        $pendingData = [
            'name'          => $request->name,
            'email'         => strtolower(trim($request->email)),
            'mobile_number' => $request->mobile_number,
        ];

        if ($request->filled('new_password')) {
            $pendingData['new_password_hashed'] = Hash::make($request->new_password);
        }

        // Determine which fields are being modified
        $changedFields = [];
        if ($pendingData['name'] !== $user->name) $changedFields[] = 'Name';
        if ($pendingData['email'] !== $user->email) $changedFields[] = 'Email Address';
        if ($pendingData['mobile_number'] !== $user->mobile_number) $changedFields[] = 'Mobile Number';
        if (isset($pendingData['new_password_hashed'])) $changedFields[] = 'Account Password';

        if (empty($changedFields)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No changes were detected in your account settings.',
                ], 422);
            }
            return back()->with('info', 'No changes were detected in your account settings.');
        }

        // Generate secure 6-digit OTP
        $otp = (string) random_int(100000, 999999);
        $user->update([
            'security_otp'            => $otp,
            'security_otp_expires_at' => now()->addMinutes(10),
            'pending_profile_update'  => $pendingData,
        ]);

        // Send OTP to user's registered email
        try {
            \App\Services\BrevoMailService::sendSecurityOtp($user, $otp, $changedFields);
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch security OTP email to {$user->email}: " . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'       => true,
                'message'       => "Verification code has been dispatched to {$user->email}.",
                'otp_sent'      => true,
                'email'         => $user->email,
                'changedFields' => $changedFields,
            ]);
        }

        return redirect()->route('settings.index')->with([
            'success'       => "Verification code has been dispatched to {$user->email}.",
            'otp_sent'      => true,
            'changedFields' => $changedFields,
        ]);
    }

    /**
     * Show the OTP verification view (redirects to in-page settings).
     */
    public function showOtpVerification()
    {
        return redirect()->route('settings.index');
    }

    /**
     * Verify the 6-digit OTP and commit the changes.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (empty($user->security_otp) || empty($user->pending_profile_update)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active verification session found. Please submit your update again.',
                ], 422);
            }
            return redirect()->route('settings.index')->with('error', 'No active verification session found. Please submit your update again.');
        }

        if ($user->security_otp_expires_at && $user->security_otp_expires_at->isPast()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This verification code has expired. Please request a new code.',
                    'errors' => ['otp' => ['This verification code has expired. Please request a new code.']]
                ], 422);
            }
            return back()->withErrors(['otp' => 'This verification code has expired. Please request a new code.']);
        }

        if ($user->security_otp !== trim($request->otp)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code. Please check your email and try again.',
                    'errors' => ['otp' => ['Invalid verification code. Please check your email and try again.']]
                ], 422);
            }
            return back()->withErrors(['otp' => 'Invalid verification code. Please check your email and try again.']);
        }

        // Commit pending changes
        $pending = $user->pending_profile_update;
        $updates = [
            'name'                    => $pending['name'],
            'email'                   => $pending['email'],
            'mobile_number'           => $pending['mobile_number'] ?? null,
            'security_otp'            => null,
            'security_otp_expires_at' => null,
            'pending_profile_update'  => null,
        ];

        if (isset($pending['new_password_hashed'])) {
            $updates['password'] = $pending['new_password_hashed'];
        }

        $user->update($updates);

        // Notify Team Lead via ActivityLog so it reflects in History and TL Member Details
        $roleLabel = $user->isTL() ? 'Team Lead' : 'Employee';
        ActivityLog::log(
            action: 'profile_settings_updated',
            description: sprintf('%s (%s) updated their account settings with OTP verification (Email: %s, Mobile: %s%s)',
                $user->name,
                $roleLabel,
                $user->email,
                $user->mobile_number ?? 'N/A',
                isset($pending['new_password_hashed']) ? ', Password Changed' : ''
            ),
            entityType: 'User',
            entityId: $user->id
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your account settings have been successfully verified and updated!',
                'redirect' => route('settings.index'),
            ]);
        }

        return redirect()->route('settings.index')->with('success', 'Your account settings have been successfully verified and updated!');
    }

    /**
     * Resend a fresh OTP.
     */
    public function resendOtp(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (empty($user->pending_profile_update)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending settings update.',
                ], 422);
            }
            return redirect()->route('settings.index')->with('error', 'No pending settings update.');
        }

        $otp = (string) random_int(100000, 999999);
        $user->update([
            'security_otp'            => $otp,
            'security_otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            \App\Services\BrevoMailService::sendSecurityOtp($user, $otp, ['Account Settings Update']);
        } catch (\Throwable $e) {
            Log::error("Failed to resend security OTP to {$user->email}: " . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'A fresh verification code has been dispatched to your email.',
            ]);
        }

        return back()->with('success', 'A fresh verification code has been dispatched to your email.');
    }

    /**
     * Cancel pending update request.
     */
    public function cancelPendingUpdate(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'security_otp'            => null,
            'security_otp_expires_at' => null,
            'pending_profile_update'  => null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pending update request has been cancelled.',
            ]);
        }

        return redirect()->route('settings.index')->with('info', 'Pending update request has been cancelled.');
    }
}

