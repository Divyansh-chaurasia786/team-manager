<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $today = now()->format('Y-m-d');
        $rawDate = $request->input('date', $today);

        // Rule 1: No one can view or mark future attendance
        if ($rawDate > $today) {
            $selectedDate = $today;
            session()->flash('error', 'Cannot view or mark future attendance. Date has been reset to today.');
        } else {
            $selectedDate = $rawDate;
        }

        $isToday = ($selectedDate === $today);
        $isPast  = ($selectedDate < $today);

        // Management Roles (TL, HR, CEO)
        if ($user->isTL() || $user->isHR() || $user->isCEO()) {
            if ($user->isTL()) {
                $members = User::where('created_by', $user->id)->get();
                // TL can ONLY mark same day attendance; past dates are strictly read-only for TL
                $canMark  = $isToday;
                $canAudit = false;
            } else {
                // HR and CEO can view all members and audit past attendance
                $members = User::where('role', 'member')->with('creator')->get();
                $canMark  = true; // HR can mark today and audit past dates
                $canAudit = true; // HR has authorized audit capability
            }

            $memberIds = $members->pluck('id');

            // Fetch attendance for selected date
            $attendances = Attendance::whereIn('user_id', $memberIds)
                ->whereDate('date', $selectedDate)
                ->get()
                ->keyBy('user_id');

            // Quick counts for selected date
            $presentCount  = $attendances->whereIn('status', ['present', 'wfh'])->count();
            $absentCount   = $attendances->where('status', 'absent')->count();
            $onLeaveCount  = $attendances->where('status', 'on_leave')->count();
            $unmarkedCount = max(0, $members->count() - $attendances->count());

            return view('tl.attendance', compact(
                'members', 'attendances', 'selectedDate',
                'presentCount', 'absentCount', 'onLeaveCount', 'unmarkedCount',
                'isToday', 'isPast', 'canMark', 'canAudit'
            ));
        }

        // For Members: Show their monthly personal attendance sheet
        $month = $request->input('month', now()->format('Y-m'));
        $myAttendances = Attendance::where('user_id', $user->id)
            ->where('date', 'like', "$month%")
            ->latest('date')
            ->get();

        $presentDays = $myAttendances->whereIn('status', ['present', 'wfh'])->count();
        $absentDays  = $myAttendances->where('status', 'absent')->count();
        $leaveDays   = $myAttendances->where('status', 'on_leave')->count();

        return view('member.attendance', compact('myAttendances', 'month', 'presentDays', 'absentDays', 'leaveDays'));
    }

    public function mark(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date'    => 'required|date',
            'status'  => 'required|in:present,absent,half_day,wfh,on_leave',
            'notes'   => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $targetUser = User::findOrFail($request->user_id);
        $date = Carbon::parse($request->date)->format('Y-m-d');
        $today = now()->format('Y-m-d');

        // Rule 1: No one can mark future attendance
        if ($date > $today) {
            $msg = 'Cannot mark future attendance. Attendance cannot be recorded for upcoming dates.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        // Rule 2: TL can ONLY mark same day attendance; past dates are restricted to HR audit
        if ($user->isTL()) {
            if ($date !== $today) {
                $msg = 'Team Leads can only mark attendance for today. Only HR can audit or modify past attendance records.';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 403);
                }
                return back()->with('error', $msg);
            }

            if ($targetUser->created_by !== $user->id && $targetUser->id !== $user->id) {
                abort(403, 'Unauthorized. Target user does not belong to your team.');
            }
        } elseif ($user->isHR() || $user->isCEO()) {
            // HR & CEO can audit attendance for any member for today or past dates
        } else {
            abort(403, 'Unauthorized. Only Team Leads and HR can mark attendance.');
        }

        $isAudit = ($date < $today);

        $att = Attendance::updateOrCreate(
            ['user_id' => $targetUser->id, 'date' => $date],
            [
                'marked_by' => $user->id,
                'status'    => $request->status,
                'notes'     => $request->notes,
            ]
        );

        // Audit Log
        ActivityLog::log(
            action: $isAudit ? 'attendance_audited' : 'attendance_marked',
            description: sprintf('%s (%s) %s %s as %s for %s',
                $user->name,
                strtoupper($user->role),
                $isAudit ? 'audited and updated' : 'marked',
                $targetUser->name,
                ucfirst(str_replace('_', ' ', $request->status)),
                $date
            ),
            entityType: 'Attendance',
            entityId: $att->id,
            userId: $user->id
        );

        $successMsg = $isAudit
            ? "Attendance audited and updated for {$targetUser->name} ({$request->status})."
            : "Attendance updated for {$targetUser->name} ({$request->status}).";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'is_audit' => $isAudit,
            ]);
        }

        return back()->with('success', $successMsg);
    }

    public function bulkMarkPresent(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $user = Auth::user();
        $date = Carbon::parse($request->date)->format('Y-m-d');
        $today = now()->format('Y-m-d');

        // Rule 1: No one can mark future attendance
        if ($date > $today) {
            return back()->with('error', 'Cannot mark future attendance. Attendance cannot be recorded for upcoming dates.');
        }

        // Rule 2: TL can only mark same day attendance
        if ($user->isTL()) {
            if ($date !== $today) {
                return back()->with('error', 'Team Leads can only mark attendance for today. Only HR can audit past attendance.');
            }
            $members = User::where('created_by', $user->id)->get();
        } elseif ($user->isHR() || $user->isCEO()) {
            $members = User::where('role', 'member')->get();
        } else {
            abort(403, 'Unauthorized.');
        }

        $isAudit = ($date < $today);

        foreach ($members as $m) {
            // Do not overwrite on_leave
            $existing = Attendance::where('user_id', $m->id)->whereDate('date', $date)->first();
            if (!$existing || $existing->status !== 'on_leave') {
                Attendance::updateOrCreate(
                    ['user_id' => $m->id, 'date' => $date],
                    ['marked_by' => $user->id, 'status' => 'present']
                );
            }
        }

        ActivityLog::log(
            action: $isAudit ? 'attendance_audited' : 'attendance_marked',
            description: sprintf('%s (%s) %s team members as Present for %s',
                $user->name,
                strtoupper($user->role),
                $isAudit ? 'audited and bulk marked' : 'bulk marked all',
                $date
            ),
            entityType: 'Attendance',
            entityId: null,
            userId: $user->id
        );

        $msg = $isAudit
            ? "Audited and marked active team members as Present for {$date}."
            : "Marked all active team members as Present for {$date}.";

        return back()->with('success', $msg);
    }
}