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
        // HR & CEO see all members and TLs; TL sees only their own assigned team
        $members = ($user->isHR() || $user->isCEO())
            ? User::whereIn('role', ['member', 'tl'])->with('creator')->latest()->get()
            : User::where('created_by', Auth::id())->with('creator')->latest()->get();

        $teamLeads = User::where('role', 'tl')->latest()->get();

        return view('tl.members', compact('members', 'teamLeads'));
    }

    public function store(Request $request)
    {
        $actor = Auth::user();

        $rules = [
            'name'          => 'required|string|max:255',
            'mobile_number' => 'required|string|max:20',
            'designation'   => 'required|string|max:100',
            'email'         => 'required|email|unique:users',
        ];

        if ($actor->isHR() || $actor->isCEO()) {
            $rules['role'] = 'nullable|in:member,tl';
        }

        $request->validate($rules);

        // Determine role and reporting TL (created_by)
        if ($actor->isTL()) {
            $role = 'member';
            $createdBy = $actor->id; // Automatically selected as its created TL!
        } elseif ($actor->isHR() || $actor->isCEO()) {
            $role = $request->input('role', 'member');
            if ($role === 'member') {
                if ($request->filled('team_lead_id')) {
                    $tl = User::where('id', $request->team_lead_id)->where('role', 'tl')->first();
                    $createdBy = $tl ? $tl->id : (User::where('role', 'tl')->first()?->id ?? $actor->id);
                } else {
                    $createdBy = User::where('role', 'tl')->first()?->id ?? $actor->id;
                }
            } else {
                $createdBy = $actor->id;
            }
        } else {
            abort(403);
        }

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

        // Auto-generate secure One-Time Password (OTP) e.g. ECO-928415
        $randomCode = strtoupper(Str::random(6));
        $oneTimePassword = 'ECO-' . $randomCode;

        $user = User::create([
            'name'                  => $request->name,
            'username'              => $username,
            'email'                 => strtolower(trim($request->email)),
            'mobile_number'         => $request->mobile_number,
            'designation'           => $request->designation,
            'password'              => Hash::make($oneTimePassword),
            'must_change_password'  => true,
            'otp_expires_at'        => now()->addDays(10), // 10-day validity
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'temp_password_plain'   => null, // Strictly never store plain text passwords
            'role'                  => $role,
            'created_by'            => $createdBy,
        ]);

        // Dispatch Welcome Email with Unique Username and One-Time Password
        try {
            \App\Services\BrevoMailService::sendWelcomeMail($user, $oneTimePassword);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Welcome email delivery error: ' . $e->getMessage());
        }

        // Audit Log
        $assignedTlName = ($role === 'member' && $user->creator) ? $user->creator->name : 'N/A (Team Lead)';
        ActivityLog::log(
            action: 'member_added',
            description: sprintf('%s registered %s "%s" (Username: @%s, Assigned TL: %s, Mobile: %s, Email: %s). 10-day setup OTP generated and emailed.',
                $actor->name,
                $role === 'tl' ? 'Team Lead' : 'Team Member',
                $user->name,
                $user->username,
                $assignedTlName,
                $user->mobile_number,
                $user->email
            ),
            entityType: 'User',
            entityId: $user->id,
            userId: $actor->id
        );

        $redirectRoute = ($actor->isHR() || $actor->isCEO()) ? 'hr.members' : 'tl.members';

        return redirect()->route($redirectRoute)->with([
            'success' => ($role === 'tl' ? 'Team Lead' : 'Employee') . " {$user->name} registered successfully with unique username '{$user->username}'! A 10-day setup temporary password was emailed to {$user->email}.",
            'new_member' => [
                'name'        => $user->name,
                'username'    => $user->username,
                'email'       => $user->email,
                'mobile'      => $user->mobile_number,
                'designation' => $user->designation,
                'otp'         => $oneTimePassword,
                'role'        => $role,
            ]
        ]);
    }

    public function show(User $user)
    {
        $viewer = Auth::user();
        if (!$viewer->isHR() && !$viewer->isCEO() && $user->created_by !== $viewer->id) abort(403);

        $user->load(['tasks.updates', 'driveFiles', 'creator']);
        $tasks = $user->tasks()->latest()->get();
        $leaves = \App\Models\LeaveApplication::where('user_id', $user->id)->latest()->get();
        $attendances = \App\Models\Attendance::where('user_id', $user->id)->latest()->take(14)->get();
        $teamLeads = User::where('role', 'tl')->latest()->get();

        return view('tl.member_details', compact('user', 'tasks', 'leaves', 'attendances', 'teamLeads'));
    }

    public function update(Request $request, User $user)
    {
        $editor = Auth::user();
        if (!$editor->isHR() && !$editor->isCEO() && $user->created_by !== $editor->id) abort(403);

        $rules = [
            'name'          => 'required|string|max:255',
            'username'      => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username,' . $user->id],
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'mobile_number' => 'nullable|string|max:20',
            'designation'   => 'nullable|string|max:100',
        ];

        if (($editor->isHR() || $editor->isCEO()) && $user->role === 'member' && $request->filled('team_lead_id')) {
            $rules['team_lead_id'] = 'required|exists:users,id';
        }

        $request->validate($rules, [
            'username.regex' => 'The username may only contain letters, numbers, dots, dashes, and underscores.',
        ]);

        $updateData = [
            'name'          => $request->name,
            'username'      => strtolower(trim($request->username)),
            'email'         => strtolower(trim($request->email)),
            'mobile_number' => $request->mobile_number,
            'designation'   => $request->designation,
        ];

        // HR or CEO editing the assigned Team Lead
        if (($editor->isHR() || $editor->isCEO()) && $user->role === 'member' && $request->filled('team_lead_id')) {
            $newTl = User::where('id', $request->team_lead_id)->where('role', 'tl')->firstOrFail();
            if ($user->created_by !== $newTl->id) {
                $oldTlName = $user->creator?->name ?? 'None';
                $updateData['created_by'] = $newTl->id;

                ActivityLog::log(
                    action: 'member_tl_reassigned',
                    description: sprintf('%s reassigned employee "%s" from TL %s to TL %s',
                        $editor->name,
                        $user->name,
                        $oldTlName,
                        $newTl->name
                    ),
                    entityType: 'User',
                    entityId: $user->id,
                    userId: $editor->id
                );
            }
        }

        $user->update($updateData);

        ActivityLog::log(
            action: 'member_updated',
            description: sprintf('%s updated details for employee "%s" (Username: @%s, Designation: %s, Mobile: %s)',
                $editor->name,
                $user->name,
                $user->username,
                $user->designation ?? 'N/A',
                $user->mobile_number ?? 'N/A'
            ),
            entityType: 'User',
            entityId: $user->id,
            userId: $editor->id
        );

        return back()->with('success', "Employee {$user->name}'s profile has been updated successfully.");
    }

    public function resetOtp(User $user)
    {
        $actor = Auth::user();
        if (!$actor->isHR() && !$actor->isCEO() && $user->created_by !== $actor->id) abort(403);

        $newOtp = sprintf('%06d', random_int(100000, 999999));

        $user->update([
            'password'              => Hash::make($newOtp),
            'must_change_password'  => true,
            'otp_expires_at'        => now()->addDays(10),
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'temp_password_plain'   => null,
        ]);

        try {
            \App\Services\BrevoMailService::sendWelcomeMail($user, $newOtp);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('OTP Reset email delivery error: ' . $e->getMessage());
        }

        ActivityLog::log(
            action: 'member_otp_reset',
            description: sprintf('%s generated a new 10-day One-Time Password for employee "%s" (@%s) and dispatched credentials.',
                $actor->name,
                $user->name,
                $user->username
            ),
            entityType: 'User',
            entityId: $user->id,
            userId: $actor->id
        );

        return back()->with([
            'success' => "New 10-day One-Time Password has been generated and emailed to {$user->email}.",
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
        if (!$actor->isHR() && !$actor->isCEO() && $user->created_by !== $actor->id) abort(403);
        $name = $user->name;
        $email = $user->email;
        $user->delete();

        // Audit Log
        ActivityLog::log(
            action: 'member_removed',
            description: sprintf('%s removed employee "%s" (%s)', $actor->name, $name, $email),
            entityType: 'User',
            entityId: null,
            userId: $actor->id
        );

        $redirectRoute = ($actor->isHR() || $actor->isCEO()) ? 'hr.members' : 'tl.members';

        return redirect()->route($redirectRoute)->with('success', 'Member removed.');
    }
}