<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedDate = $request->input('date', now()->format('Y-m-d'));

        if ($user->isTL()) {
            $members = User::where('created_by', $user->id)->get();
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
            $unmarkedCount = $members->count() - $attendances->count();

            return view('tl.attendance', compact(
                'members', 'attendances', 'selectedDate',
                'presentCount', 'absentCount', 'onLeaveCount', 'unmarkedCount'
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

        $tl = Auth::user();
        $targetUser = User::findOrFail($request->user_id);
        if ($targetUser->created_by !== $tl->id && $targetUser->id !== $tl->id) {
            abort(403);
        }

        $att = Attendance::updateOrCreate(
            ['user_id' => $targetUser->id, 'date' => $request->date],
            [
                'marked_by' => $tl->id,
                'status'    => $request->status,
                'notes'     => $request->notes,
            ]
        );

        // Audit Log
        ActivityLog::log(
            action: 'attendance_marked',
            description: sprintf('%s marked %s as %s for %s',
                $tl->name,
                $targetUser->name,
                ucfirst(str_replace('_', ' ', $request->status)),
                $request->date
            ),
            entityType: 'Attendance',
            entityId: $att->id
        );

        return back()->with('success', "Attendance updated for {$targetUser->name} ({$request->status}).");
    }

    public function bulkMarkPresent(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $tl = Auth::user();
        $members = User::where('created_by', $tl->id)->get();
        $date = $request->date;

        foreach ($members as $m) {
            // Do not overwrite on_leave
            $existing = Attendance::where('user_id', $m->id)->whereDate('date', $date)->first();
            if (!$existing || $existing->status !== 'on_leave') {
                Attendance::updateOrCreate(
                    ['user_id' => $m->id, 'date' => $date],
                    ['marked_by' => $tl->id, 'status' => 'present']
                );
            }
        }

        ActivityLog::log(
            action: 'attendance_marked',
            description: sprintf('%s bulk marked all team members as Present for %s', $tl->name, $date),
            entityType: 'Attendance',
            entityId: null
        );

        return back()->with('success', "Marked all active team members as Present for {$date}.");
    }
}