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

        // ⏰ 2-Day Scheduled Task Reminders for Member
        $upcomingTaskReminders = Task::with(['assignedBy'])
            ->where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->where('deadline', '<=', now()->addDays(2))
            ->orderBy('deadline', 'asc')
            ->get();

        // ⏰ 2-Day Scheduled Shoots for Member (as manager or crew)
        $upcomingShootReminders = ContentShoot::with(['creator', 'managingMember', 'cameraPerson', 'model', 'editor'])
            ->where('status', '!=', 'published')
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

        // Deadline chart - next 7 tasks by deadline
        $upcomingTasks = $tasks->where('status', '!=', 'completed')
            ->sortBy('deadline')
            ->take(7)
            ->map(fn($t) => [
                'title'    => $t->title,
                'deadline' => $t->deadline->format('M d'),
                'overdue'  => $t->isOverdue(),
            ])->values();

        $driveFiles = DriveFile::where('uploaded_by', $user->id)->latest()->take(10)->get();

        // 🎬 All active reel shoots managed by this team member
        $assignedShoots = ContentShoot::with(['creator', 'managingMember', 'cameraPerson', 'model', 'editor'])
            ->where('managing_member_id', $user->id)
            ->where('status', '!=', 'published')
            ->orderBy('shoot_date', 'asc')
            ->get();

        // Recent personal activity history
        $recentActivities = ActivityLog::where('user_id', $user->id)->latest()->take(5)->get();

        return view('member.dashboard', compact(
            'tasks', 'statusCounts', 'upcomingTasks', 'driveFiles', 'recentActivities',
            'upcomingTaskReminders', 'upcomingShootReminders', 'assignedShoots'
        ));
    }
}