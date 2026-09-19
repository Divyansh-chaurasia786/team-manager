<?php
namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\ContentShoot;
use App\Models\DriveFile;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class MemberDashboardController extends Controller
{
    public function index() {
        $user = Auth::user();
        $tasks = Task::with(['assignedBy', 'updates'])->where('assigned_to', $user->id)->latest()->get();

        // ⏰ 2-Day Scheduled Task Reminders for Member (Actionable only: excludes completed and submitted)
        $upcomingTaskReminders = Task::with(['assignedBy'])
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['completed', 'submitted'])
            ->where('deadline', '<=', now()->addDays(2))
            ->orderBy('deadline', 'asc')
            ->get();

        // ⏰ 2-Day Scheduled Shoots for Member (as manager or crew)
        $upcomingShootReminders = ContentShoot::with(['creator', 'managingMember', 'cameraPerson', 'model', 'editor'])
            ->where('status', '!=', 'published')
            ->whereNotNull('shoot_date')
            ->where('shoot_date', '<=', now()->addDays(2))
            ->where(function ($q) use ($user) {
                $q->where('managing_member_id', $user->id)
                  ->orWhere('camera_person_id', $user->id)
                  ->orWhere('model_id', $user->id)
                  ->orWhere('editor_id', $user->id);
            })
            ->orderBy('shoot_date', 'asc')
            ->get();

        $statusCounts = [
            'pending'     => $tasks->where('status', 'pending')->count(),
            'in-progress' => $tasks->where('status', 'in-progress')->count(),
            'submitted'   => $tasks->where('status', 'submitted')->count(),
            'completed'   => $tasks->where('status', 'completed')->count(),
        ];

        // Deadline chart - upcoming active tasks by deadline
        $upcomingTasks = $tasks->whereNotIn('status', ['completed', 'submitted'])
            ->sortBy('deadline')
            ->take(7)
            ->map(fn($t) => [
                'title'    => $t->title,
                'deadline' => $t->deadline->format('M d'),
                'overdue'  => $t->isOverdue(),
            ])->values();

        // 7-day productivity & work output trend (Real data for weekly velocity chart)
        $weeklyTrend = collect(range(6, 0))->map(function($daysAgo) use ($user) {
            $dayCarbon = now()->subDays($daysAgo);
            $date = $dayCarbon->format('Y-m-d');
            
            $completed = Task::where('assigned_to', $user->id)
                ->where('status', 'completed')
                ->whereDate('updated_at', $date)
                ->count();
                
            $submitted = Task::where('assigned_to', $user->id)
                ->whereDate('submitted_at', $date)
                ->count();

            $active = Task::where('assigned_to', $user->id)
                ->whereIn('status', ['in-progress', 'pending'])
                ->whereDate('created_at', '<=', $date)
                ->count();

            return [
                'label'       => $dayCarbon->format('D, M d'),
                'short'       => $dayCarbon->format('D'),
                'day_num'     => $dayCarbon->format('j'),
                'date'        => $date,
                'is_today'    => $daysAgo === 0,
                'completed'   => $completed,
                'submitted'   => $submitted,
                'active'      => $active,
            ];
        });

        $driveFiles = DriveFile::where('uploaded_by', $user->id)->latest()->take(10)->get();

        // 🎬 All active reel shoots managed by this team member
        $assignedShoots = ContentShoot::with(['creator', 'managingMember', 'cameraPerson', 'model', 'editor'])
            ->where('managing_member_id', $user->id)
            ->where('status', '!=', 'published')
            ->latest('id')
            ->get();

        // Recent personal activity history
        $recentActivities = ActivityLog::where('user_id', $user->id)->latest()->take(5)->get();

        return view('member.dashboard', compact(
            'tasks', 'statusCounts', 'upcomingTasks', 'weeklyTrend', 'driveFiles', 'recentActivities',
            'upcomingTaskReminders', 'upcomingShootReminders', 'assignedShoots'
        ));
    }
}