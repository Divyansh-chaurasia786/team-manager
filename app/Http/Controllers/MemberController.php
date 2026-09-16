<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use App\Mail\EmployeeWelcomeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // HR sees all members; TL sees their own team
        $members = $user->isHR()
            ? User::whereIn('role', ['member', 'tl', 'hr'])->latest()->get()
            : User::where('created_by', Auth::id())->latest()->get();

        return view('tl.members', compact('members'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'mobile_number' => 'required|string|max:20',
            'designation'   => 'required|string|max:100',
            'email'         => 'required|email|unique:users',
        ]);

        // Auto-generate Unique Username from employee's name (e.g. rahul.sharma, or rahul.sharma2)
        $cleanName = Str::slug($request->name, '.');
        if (empty($cleanName)) {
            $cleanName = 'user';
        }
        $baseUsername = $cleanName;
        $username = $baseUsername;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $counter++;
            $username = $baseUsername . $counter;
        }

        // Auto-generate secure 8-character One-Time Password (OTP) e.g. ECO-928415
        $randomCode = strtoupper(Str::random(6));
        $oneTimePassword = 'ECO-' . $randomCode;

        $user = User::create([
            'name'                 => $request->name,
            'username'             => $username,
            'email'                => $request->email,
            'mobile_number'        => $request->mobile_number,
            'designation'          => $request->designation,
            'password'             => Hash::make($oneTimePassword),
            'must_change_password' => true,
            'temp_password_plain'  => null, // Strictly never store plain text passwords
            'role'                 => 'member',
            'created_by'           => Auth::id(),
        ]);

        // Dispatch Welcome Email with Unique Username and One-Time Password
        try {
            \App\Services\BrevoMailService::sendWelcomeMail($user, $oneTimePassword);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Welcome email delivery error: ' . $e->getMessage());
        }

        // Audit Log
        ActivityLog::log(
            action: 'member_added',
            description: sprintf('%s registered employee "%s" (Username: %s, %s, Mobile: %s, Email: %s). One-Time Password generated and emailed.',
                Auth::user()->name,
                $user->name,
                $user->username,
                $user->designation,
                $user->mobile_number,
                $user->email
            ),
            entityType: 'User',
            entityId: $user->id
        );

        $redirectRoute = Auth::user()->isHR() ? 'hr.members' : 'tl.members';

        return redirect()->route($redirectRoute)->with([
            'success' => "Employee {$user->name} registered successfully with unique username '{$user->username}'! A one-time temporary password was automatically emailed to {$user->email}.",
            'new_member' => [
                'name'        => $user->name,
                'username'    => $user->username,
                'email'       => $user->email,
                'mobile'      => $user->mobile_number,
                'designation' => $user->designation,
                'otp'         => $oneTimePassword,
            ]
        ]);
    }

    public function show(User $user)
    {
        $viewer = Auth::user();
        if (!$viewer->isHR() && $user->created_by !== $viewer->id) abort(403);

        $user->load(['tasks.updates', 'driveFiles']);
        $tasks = $user->tasks()->latest()->get();
        $leaves = \App\Models\LeaveApplication::where('user_id', $user->id)->latest()->get();
        $attendances = \App\Models\Attendance::where('user_id', $user->id)->latest()->take(14)->get();

        return view('tl.member_details', compact('user', 'tasks', 'leaves', 'attendances'));
    }

    public function update(Request $request, User $user)
    {
        $editor = Auth::user();
        if (!$editor->isHR() && $user->created_by !== $editor->id) abort(403);

        $request->validate([
            'name'          => 'required|string|max:255',
            'username'      => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username,' . $user->id],
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'mobile_number' => 'nullable|string|max:20',
            'designation'   => 'nullable|string|max:100',
        ], [
            'username.regex' => 'The username may only contain letters, numbers, dots, dashes, and underscores.',
        ]);

        $oldName = $user->name;
        $user->update([
            'name'          => $request->name,
            'username'      => strtolower(trim($request->username)),
            'email'         => strtolower(trim($request->email)),
            'mobile_number' => $request->mobile_number,
            'designation'   => $request->designation,
        ]);

        ActivityLog::log(
            action: 'member_updated',
            description: sprintf('%s updated details for employee "%s" (Username: @%s, Designation: %s, Mobile: %s)',
                Auth::user()->name,
                $user->name,
                $user->username,
                $user->designation ?? 'N/A',
                $user->mobile_number ?? 'N/A'
            ),
            entityType: 'User',
            entityId: $user->id
        );

        return back()->with('success', "Employee {$user->name}'s profile has been updated successfully.");
    }

    public function resetOtp(User $user)
    {
        $actor = Auth::user();
        if (!$actor->isHR() && $user->created_by !== $actor->id) abort(403);

        $randomCode = strtoupper(Str::random(6));
        $newOtp = 'ECO-' . $randomCode;

        $user->update([
            'password'             => Hash::make($newOtp),
            'must_change_password' => true,
            'temp_password_plain'  => null, // Strictly store only cryptographic hash
        ]);

        try {
            \App\Services\BrevoMailService::sendWelcomeMail($user, $newOtp);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('OTP Reset email delivery error: ' . $e->getMessage());
        }

        ActivityLog::log(
            action: 'member_otp_reset',
            description: sprintf('%s generated a new One-Time Password for employee "%s" (@%s) and dispatched credentials.',
                Auth::user()->name,
                $user->name,
                $user->username
            ),
            entityType: 'User',
            entityId: $user->id
        );

        return back()->with([
            'success' => "New One-Time Password has been generated and emailed to {$user->email}.",
            'new_member' => [
                'name'        => $user->name,
                'username'    => $user->username,
                'email'       => $user->email,
                'mobile'      => $user->mobile_number,
                'designation' => $user->designation,
                'otp'         => $newOtp,
            ]
        ]);
    }

    public function destroy(User $user)
    {
        $actor = Auth::user();
        if (!$actor->isHR() && $user->created_by !== $actor->id) abort(403);
        $name = $user->name;
        $email = $user->email;
        $user->delete();

        // Audit Log
        ActivityLog::log(
            action: 'member_removed',
            description: sprintf('%s removed employee "%s" (%s)', Auth::user()->name, $name, $email),
            entityType: 'User',
            entityId: null
        );

        $redirectRoute = Auth::user()->isHR() ? 'hr.members' : 'tl.members';

        return redirect()->route($redirectRoute)->with('success', 'Member removed.');
    }
}