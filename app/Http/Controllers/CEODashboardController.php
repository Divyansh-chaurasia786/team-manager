<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Models\ContentShoot;
use App\Models\LeaveApplication;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class CEODashboardController extends Controller
{
    public function index()
    {
        $today = now()->format('Y-m-d');

        // All team members (non-CEO)
        $allMembers = User::whereIn('role', ['member', 'tl', 'hr'])->get();

        // Today's attendance summary
        $todayAttendances = Attendance::whereIn('user_id', $allMembers->pluck('id'))
            ->whereDate('date', $today)
            ->get()
            ->keyBy('user_id');

        $presentCount = $todayAttendances->whereIn('status', ['present', 'wfh', 'half_day'])->count();
        $absentCount  = $todayAttendances->whereIn('status', ['absent', 'on_leave'])->count();
        $unmarkedCount = $allMembers->count() - $todayAttendances->count();

        // Task overview
        $allTasks         = Task::all();
        $pendingTasks     = $allTasks->whereIn('status', ['pending', 'in-progress'])->count();
        $submittedTasks   = $allTasks->where('status', 'submitted')->count();
        $completedTasks   = $allTasks->where('status', 'completed')->count();

        // Tasks needing CEO review (submitted by anyone)
        $tasksNeedingReview = Task::with(['assignedTo', 'assignedBy', 'updates'])
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->get();

        // Shoots overview
        $shootsByStatus = ContentShoot::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Pending leaves
        $pendingLeaves = LeaveApplication::with('user')
            ->where('status', 'pending')
            ->latest()
            ->get();

        // Per-member task stats for performance table
        $memberStats = $allMembers->map(function ($member) use ($todayAttendances) {
            $tasks = Task::where('assigned_to', $member->id)->get();
            $att   = $todayAttendances->get($member->id);
            return [
                'member'       => $member,
                'total'        => $tasks->count(),
                'pending'      => $tasks->whereIn('status', ['pending', 'in-progress'])->count(),
                'submitted'    => $tasks->where('status', 'submitted')->count(),
                'completed'    => $tasks->where('status', 'completed')->count(),
                'att_status'   => $att ? $att->status : 'unmarked',
            ];
        });

        return view('ceo.dashboard', compact(
            'allMembers',
            'presentCount', 'absentCount', 'unmarkedCount',
            'pendingTasks', 'submittedTasks', 'completedTasks',
            'tasksNeedingReview',
            'shootsByStatus',
            'pendingLeaves',
            'memberStats',
            'today'
        ));
    }
}
