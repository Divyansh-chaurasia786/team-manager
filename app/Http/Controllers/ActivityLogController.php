<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ActivityLog::with('user')->latest();

        if ($user->isTL()) {
            // TL sees activities from themselves and all team members they created
            $memberIds = User::where('created_by', $user->id)->pluck('id')->push($user->id);
            $query->whereIn('user_id', $memberIds);
        } else {
            // Members see their own activities plus any task assigned to them
            $assignedTaskIds = Task::where('assigned_to', $user->id)->pluck('id');
            $query->where(function($q) use ($user, $assignedTaskIds) {
                $q->where('user_id', $user->id)
                  ->orWhere(function($sub) use ($assignedTaskIds) {
                      $sub->where('entity_type', 'Task')->whereIn('entity_id', $assignedTaskIds);
                  });
            });
        }

        // Action filter
        if ($request->filled('action')) {
            if ($request->action === 'all_tasks') {
                $query->whereIn('action', ['task_assigned', 'task_updated', 'task_submitted', 'task_completed', 'task_deleted']);
            } else {
                $query->where('action', $request->action);
            }
        }

        // Search description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(25)->withQueryString();

        // Stats
        $totalDownloads = (clone $query)->where('action', 'file_downloaded')->count();
        $totalUploads   = (clone $query)->where('action', 'file_uploaded')->count();

        return view('shared.history', compact('logs', 'totalDownloads', 'totalUploads'));
    }
}