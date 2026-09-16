<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Models\LeaveApplication;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class HRDashboardController extends Controller
{
    public function index()
    {
        $today = now()->format('Y-m-d');

        // All members (excluding CEO)
        $allMembers = User::whereIn('role', ['member', 'tl', 'hr'])->latest()->get();

        // Today's attendance
        $todayAttendances = Attendance::whereIn('user_id', $allMembers->pluck('id'))
            ->whereDate('date', $today)
            ->get()
            ->keyBy('user_id');

        $presentCount  = $todayAttendances->whereIn('status', ['present', 'wfh', 'half_day'])->count();
        $absentCount   = $todayAttendances->whereIn('status', ['absent', 'on_leave'])->count();
        $unmarkedCount = $allMembers->count() - $todayAttendances->count();

        // Pending leave requests
        $pendingLeaves = LeaveApplication::with('user')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $recentLeaves = LeaveApplication::with(['user', 'reviewer'])
            ->where('status', '!=', 'pending')
            ->latest()
            ->take(10)
            ->get();

        // Per-member performance overview (read-only for HR)
        $memberStats = $allMembers->map(function ($member) use ($todayAttendances) {
            $tasks = Task::where('assigned_to', $member->id)->get();
            $att   = $todayAttendances->get($member->id);
            return [
                'member'     => $member,
                'total'      => $tasks->count(),
                'completed'  => $tasks->where('status', 'completed')->count(),
                'pending'    => $tasks->whereIn('status', ['pending', 'in-progress'])->count(),
                'att_status' => $att ? $att->status : 'unmarked',
            ];
        });

        return view('hr.dashboard', compact(
            'allMembers',
            'presentCount', 'absentCount', 'unmarkedCount',
            'pendingLeaves', 'recentLeaves',
            'memberStats',
            'today'
        ));
    }
}
