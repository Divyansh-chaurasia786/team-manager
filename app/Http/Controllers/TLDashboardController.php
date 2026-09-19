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
        $members = User::where('created_by', $tl->id)->get();
        $tasks = Task::with(['assignedTo', 'updates'])->where('assigned_by', $tl->id)->latest()->get();

        // ⏰ 2-Day Scheduled Task Reminders (Tasks due within 2 days or overdue, actionable only)
        $upcomingTaskReminders = Task::with(['assignedTo'])
            ->where('assigned_by', $tl->id)
            ->whereNotIn('status', ['completed', 'submitted'])
            ->where('deadline', '<=', now()->addDays(2))
            ->orderBy('deadline', 'asc')
            ->get();

        // Opportunistic automated overdue scan (throttled to once every 15 minutes)
        \Illuminate\Support\Facades\Cache::remember('tl_overdue_scan_' . $tl->id, 900, function () {
            try {
                \Illuminate\Support\Facades\Artisan::call('tasks:send-overdue-reminders');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Opportunistic overdue scan error: ' . $e->getMessage());
            }
            return now()->timestamp;
        });

        // ⏰ 2-Day Scheduled Shoot Reminders (Shoots in the next 48 hours)
        $upcomingShootReminders = ContentShoot::with(['managingMember', 'cameraPerson', 'model', 'editor'])
            ->where('status', '!=', 'published')
            ->whereNotNull('shoot_date')
            ->where('shoot_date', '<=', now()->addDays(2))
            ->orderBy('shoot_date', 'asc')
            ->get();

        // Chart Data
        $statusCounts = [
            'pending'     => $tasks->where('status', 'pending')->count(),
            'in-progress' => $tasks->where('status', 'in-progress')->count(),
            'submitted'   => $tasks->where('status', 'submitted')->count(),
            'completed'   => $tasks->where('status', 'completed')->count(),
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
            'upcomingTaskReminders', 'upcomingShootReminders'
        ));
    }
}