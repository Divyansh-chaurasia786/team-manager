<?php
namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\DriveFile;
use App\Models\ContentShoot;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TLDashboardController extends Controller
{
    public function index()
    {
        $tl = Auth::user();

        // Opportunistic automated overdue scan (throttled to 5 minutes via OverdueReminderService)
        try {
            \App\Services\OverdueReminderService::scanAndDispatchAutomaticReminders();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Automatic overdue scan error: ' . $e->getMessage());
        }

        $members = User::where('created_by', $tl->id)->get();
        $memberIds = $members->pluck('id');

        $tasks = Task::with(['assignedTo', 'updates'])
            ->where(function ($q) use ($tl, $memberIds) {
                $q->where('assigned_by', $tl->id)
                  ->orWhereIn('assigned_to', $memberIds);
            })
            ->latest()
            ->get();

        // Overdue tasks calculation (Only assigned tasks can be overdue)
        $overdueTasks = $tasks->whereNotNull('assigned_to')->filter(fn($t) => $t->isOverdue())->values();
        $overdueCount = $overdueTasks->count();

        // ⏰ 2-Day Scheduled Task Reminders (Tasks due within 2 days or overdue, actionable assigned only)
        $upcomingTaskReminders = Task::with(['assignedTo'])
            ->where(function ($q) use ($tl, $memberIds) {
                $q->where('assigned_by', $tl->id)
                  ->orWhereIn('assigned_to', $memberIds);
            })
            ->whereNotNull('assigned_to')
            ->whereNotIn('status', ['completed', 'submitted'])
            ->where('deadline', '<=', now()->addDays(2))
            ->orderBy('deadline', 'asc')
            ->get();

        // ⏰ 2-Day Scheduled Shoot Reminders (Shoots in the next 48 hours)
        $upcomingShootReminders = ContentShoot::with(['managingMember', 'cameraPerson', 'model', 'editor'])
            ->where('status', '!=', 'published')
            ->whereNotNull('shoot_date')
            ->where('shoot_date', '<=', now()->addDays(2))
            ->orderBy('shoot_date', 'asc')
            ->get();

        // Chart Data
        $statusCounts = [
            'pending'     => $tasks->whereNotNull('assigned_to')->where('status', 'pending')->count(),
            'in-progress' => $tasks->whereNotNull('assigned_to')->where('status', 'in-progress')->count(),
            'submitted'   => $tasks->where('status', 'submitted')->count(),
            'completed'   => $tasks->where('status', 'completed')->count(),
            'overdue'     => $overdueCount,
        ];

        // Tasks per member
        $taskPerMember = $members->map(fn($m) => [
            'name'  => $m->name,
            'count' => Task::where('assigned_to', $m->id)->where('assigned_by', $tl->id)->count(),
        ]);

        // Completion trend - last 7 days
        $completionTrend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $completionTrend->push([
                'date'  => $date->format('M d'),
                'count' => Task::where('assigned_by', $tl->id)
                    ->where('status', 'completed')
                    ->whereDate('updated_at', $date)
                    ->count(),
            ]);
        }

        // Drive file type counts
        $driveStats = [
            'photos'    => DriveFile::where('uploaded_by', $tl->id)->orWhereHas('uploader', fn($q) => $q->where('created_by', $tl->id))->where('file_type', 'photo')->count(),
            'videos'    => DriveFile::whereHas('uploader', fn($q) => $q->where('created_by', $tl->id)->orWhere('id', $tl->id))->where('file_type', 'video')->count(),
            'documents' => DriveFile::whereHas('uploader', fn($q) => $q->where('created_by', $tl->id)->orWhere('id', $tl->id))->where('file_type', 'document')->count(),
        ];

        $driveFiles = DriveFile::with('uploader')
            ->whereHas('uploader', fn($q) => $q->where('created_by', $tl->id)->orWhere('id', $tl->id))
            ->latest()->take(10)->get();

        // Recent Activity History Audit Stream
        $memberIds = $members->pluck('id')->push($tl->id);
        $recentActivities = ActivityLog::with('user')
            ->whereIn('user_id', $memberIds)
            ->latest()->take(6)->get();

        return view('tl.dashboard', compact(
            'members', 'tasks', 'statusCounts', 'taskPerMember',
            'completionTrend', 'driveStats', 'driveFiles', 'recentActivities',
            'upcomingTaskReminders', 'upcomingShootReminders',
            'overdueTasks', 'overdueCount'
        ));
    }
}