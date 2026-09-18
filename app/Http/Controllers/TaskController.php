<?php
namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\User;
use App\Models\Attendance;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = now()->format('Y-m-d');

        if ($user->isOperationsLead()) {
            // TL sees tasks they assigned; CEO sees ALL tasks across everyone
            $tasksQuery = Task::with(['assignedTo', 'updates']);
            if ($user->isTL()) {
                $tasksQuery->where('assigned_by', $user->id);
            }
            // CEO sees all tasks
            $tasks = $tasksQuery->latest()->get();

            $members = $user->isTL()
                ? User::where('created_by', $user->id)->get()
                : User::whereIn('role', ['member', 'tl', 'hr'])->get(); // CEO can assign to anyone

            // Fetch today's attendance for all relevant members
            $todayAttendances = Attendance::whereIn('user_id', $members->pluck('id'))
                ->whereDate('date', $today)
                ->get()
                ->keyBy('user_id');

            return view('tl.tasks', compact('tasks', 'members', 'todayAttendances', 'today'));
        }

        $tasks = Task::with(['assignedBy', 'updates'])->where('assigned_to', $user->id)->latest()->get();
        return view('member.tasks', compact('tasks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'deadline'    => 'required|date|after:now',
        ]);

        $today = now()->format('Y-m-d');
        $actor = Auth::user();
        $assignee = User::findOrFail($request->assigned_to);

        // Security check: Team Leads can ONLY assign tasks to their own assigned team members
        if ($actor->isTL() && $assignee->created_by !== $actor->id) {
            abort(403, 'Unauthorized: Team Leads can only assign tasks to members in their own assigned team.');
        }

        // Attendance check: Is the member absent or on leave today?
        $todayAttendance = Attendance::where('user_id', $assignee->id)
            ->whereDate('date', $today)
            ->first();

        if ($todayAttendance && in_array($todayAttendance->status, ['absent', 'on_leave'])) {
            return back()->withInput()->with('error', "Cannot assign task: {$assignee->name} is marked " . ucfirst(str_replace('_', ' ', $todayAttendance->status)) . " today. Tasks can only be delegated to active/present team members.");
        }

        $task = Task::create([
            'title'       => $request->title,
            'description' => $request->description,
            'assigned_to' => $request->assigned_to,
            'assigned_by' => Auth::id(),
            'deadline'    => $request->deadline,
            'status'      => 'pending',
        ]);

        // Audit Log
        ActivityLog::log(
            action: 'task_assigned',
            description: sprintf('%s assigned task "%s" to %s (Attendance: %s) with deadline %s',
                Auth::user()->name,
                $task->title,
                $assignee->name,
                $todayAttendance ? ucfirst($todayAttendance->status) : 'Present',
                $task->deadline->format('d M Y, h:i A')
            ),
            entityType: 'Task',
            entityId: $task->id
        );

        // Instantly dispatch email notification to the assignee
        \App\Services\BrevoMailService::sendTaskAssignedMail($task);

        return redirect()->route('tasks.index')->with('success', "Task successfully assigned to {$assignee->name}.");
    }

    public function addUpdate(Request $request, Task $task)
    {
        $request->validate(['message' => 'required|string']);
        if ($task->assigned_to !== Auth::id()) abort(403);

        TaskUpdate::create([
            'task_id' => $task->id,
            'message' => $request->message,
        ]);

        if ($task->status === 'pending') {
            $task->update(['status' => 'in-progress']);
        }

        // Audit Log
        ActivityLog::log(
            action: 'task_updated',
            description: sprintf('%s logged progress note on "%s": "%s"',
                Auth::user()->name,
                $task->title,
                \Illuminate\Support\Str::limit($request->message, 80)
            ),
            entityType: 'Task',
            entityId: $task->id
        );

        return back()->with('success', 'Update added!');
    }

    public function submit(Request $request, Task $task)
    {
        if ($task->assigned_to !== Auth::id()) abort(403);

        $request->validate([
            'submission_remarks' => 'nullable|string',
            'submission_link'    => 'nullable|url',
            'submission_file'    => 'nullable|file|max:51200|mimes:jpeg,png,jpg,webp,gif,mp4,mov,avi,mkv,pdf,doc,docx,zip',
        ]);

        $filePath = $task->submission_file;
        $fileType = $task->submission_file_type;

        if ($request->hasFile('submission_file')) {
            $file = $request->file('submission_file');
            $extension = strtolower($file->getClientOriginalExtension());

            if (in_array($extension, ['jpeg', 'png', 'jpg', 'webp', 'gif'])) {
                $fileType = 'image';
            } elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])) {
                $fileType = 'video';
            } elseif ($extension === 'pdf') {
                $fileType = 'pdf';
            } else {
                $fileType = 'document';
            }

            $fileName = 'task_' . $task->id . '_' . time() . '.' . $extension;
            $destination = public_path('uploads/task_submissions');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $fileName);
            $filePath = 'uploads/task_submissions/' . $fileName;
        }

        $task->update([
            'status'               => 'submitted',
            'submitted_at'         => now(),
            'submission_remarks'   => $request->submission_remarks,
            'submission_link'      => $request->submission_link,
            'submission_file'      => $filePath,
            'submission_file_type' => $fileType,
        ]);

        // Audit Log
        ActivityLog::log(
            action: 'task_submitted',
            description: sprintf('%s submitted task "%s" with deliverables (%s) for Team Lead review',
                Auth::user()->name,
                $task->title,
                $fileType ? strtoupper($fileType) . ($request->submission_link ? ' + Link' : '') : ($request->submission_link ? 'Link attached' : 'Notes attached')
            ),
            entityType: 'Task',
            entityId: $task->id
        );

        return back()->with('success', 'Deliverables successfully submitted to Team Lead for review!');
    }

    public function reassign(Request $request, Task $task)
    {
        $user = Auth::user();
        // Only the original assigner or CEO can reassign
        if ($task->assigned_by !== $user->id && !$user->isCEO()) abort(403);

        $request->validate([
            'deadline'       => 'required|date|after:now',
            'revision_notes' => 'required|string',
        ]);

        $oldDeadline = $task->deadline;
        $newDeadline = $request->deadline;

        $task->update([
            'previous_deadline'  => $oldDeadline,
            'deadline'           => $newDeadline,
            'status'             => 'in-progress',
            'revision_notes'     => $request->revision_notes,
            'reassignment_count' => $task->reassignment_count + 1,
            'submitted_at'       => null,
        ]);

        // Add a task update history note
        TaskUpdate::create([
            'task_id' => $task->id,
            'message' => 'Task reassigned by TL with revisions requested: ' . $request->revision_notes,
        ]);

        // Audit Log
        ActivityLog::log(
            action: 'task_reassigned',
            description: sprintf('%s requested changes and reassigned task "%s" (Prev: %s, New: %s)',
                Auth::user()->name,
                $task->title,
                $oldDeadline->format('d M, h:i A'),
                \Carbon\Carbon::parse($newDeadline)->format('d M Y, h:i A')
            ),
            entityType: 'Task',
            entityId: $task->id
        );

        // Dispatch reassignment email notification to assignee
        \App\Services\BrevoMailService::sendTaskAssignedMail($task, isReassignment: true);

        return back()->with('success', 'Task has been reassigned to member with new deadline and revision directives.');
    }

    public function complete(Task $task)
    {
        $user = Auth::user();
        // Original assigner or CEO can approve tasks
        if ($task->assigned_by !== $user->id && !$user->isCEO()) abort(403);

        $uploadMessage = '';

        // Requirement: Deliverable remains in database/local storage until TL approval.
        // Once TL approves, if local submission_file exists, upload it to Google Drive and delete from local storage.
        if ($task->submission_file) {
            $localFilePath = public_path($task->submission_file);

            if (file_exists($localFilePath)) {
                $originalName = basename($localFilePath);
                $uploaderId = $task->assigned_to;

                if (file_exists(base_path('credentials.json'))) {
                    try {
                        $driveService = new \App\Services\DriveService();
                        $uploadResult = $driveService->uploadFromPath($localFilePath, $originalName, $uploaderId);

                        // Save in DriveFile table
                        $driveFile = \App\Models\DriveFile::create([
                            'uploaded_by'   => $uploaderId,
                            'original_name' => $originalName,
                            'drive_file_id' => $uploadResult['drive_file_id'],
                            'drive_url'     => $uploadResult['drive_url'],
                            'file_type'     => $uploadResult['file_type'],
                            'upload_date'   => $uploadResult['upload_date'],
                        ]);

                        // Delete local file to free disk space
                        @unlink($localFilePath);

                        // Update task with drive reference and clear local path
                        $task->update([
                            'status'          => 'completed',
                            'drive_file_id'   => $uploadResult['drive_file_id'],
                            'drive_url'       => $uploadResult['drive_url'],
                            'submission_file' => null, // cleaned from local storage
                        ]);

                        $uploadMessage = ' Deliverable automatically uploaded to Google Drive and cleared from local storage.';

                        ActivityLog::log(
                            action: 'file_uploaded',
                            description: sprintf('Task "%s" deliverable automatically synced to Google Drive (%s) on approval by %s',
                                $task->title,
                                $driveFile->original_name,
                                Auth::user()->name
                            ),
                            entityType: 'DriveFile',
                            entityId: $driveFile->id
                        );
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Automated Drive upload on task approval failed: ' . $e->getMessage());
                        $task->update(['status' => 'completed']);
                        $uploadMessage = ' Note: Local file could not be uploaded to Google Drive (' . $e->getMessage() . ').';
                    }
                } else {
                    // Credentials not yet placed; delete from local storage as approved or keep with notice
                    $task->update(['status' => 'completed']);
                    $uploadMessage = ' Approved. (Google Drive credentials.json not found in root, local file kept).';
                }
            } else {
                $task->update(['status' => 'completed']);
            }
        } else {
            $task->update(['status' => 'completed']);
        }

        // Audit Log
        ActivityLog::log(
            action: 'task_completed',
            description: sprintf('%s approved and marked task "%s" as Completed',
                Auth::user()->name,
                $task->title
            ),
            entityType: 'Task',
            entityId: $task->id
        );

        return back()->with('success', 'Task approved and marked as completed!' . $uploadMessage);
    }

    public function destroy(Task $task)
    {
        $actor = Auth::user();
        if (!$actor->isCEO()) {
            return back()->with('error', 'Only the CEO has permission to delete task history records.');
        }

        if ($task->submission_file && file_exists(public_path($task->submission_file))) {
            @unlink(public_path($task->submission_file));
        }

        $taskTitle = $task->title;
        $taskId = $task->id;
        $task->delete();

        ActivityLog::log(
            action: 'task_deleted',
            description: sprintf('%s (CEO) permanently deleted task "%s" (ID: #%d)', $actor->name, $taskTitle, $taskId),
            entityType: 'Task',
            entityId: $taskId,
            userId: $actor->id
        );

        return back()->with('success', "Task \"{$taskTitle}\" has been permanently deleted from history.");
    }

    public function bulkDestroy(Request $request)
    {
        $actor = Auth::user();
        if (!$actor->isCEO()) {
            abort(403, 'Unauthorized: Only the CEO has permission to delete task history records.');
        }

        $request->validate([
            'task_ids'   => 'required|array|min:1',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        $tasks = Task::whereIn('id', $request->task_ids)->get();
        foreach ($tasks as $t) {
            if ($t->submission_file && file_exists(public_path($t->submission_file))) {
                @unlink(public_path($t->submission_file));
            }
        }

        $count = Task::whereIn('id', $request->task_ids)->delete();

        ActivityLog::log(
            action: 'tasks_bulk_deleted',
            description: sprintf('%s (CEO) permanently deleted %d task history record(s)', $actor->name, $count),
            entityType: 'Task',
            userId: $actor->id
        );

        return back()->with('success', "Successfully deleted {$count} task(s) from history.");
    }
}