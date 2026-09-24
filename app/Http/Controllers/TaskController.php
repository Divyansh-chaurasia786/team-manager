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

            // 1. Live Tasks: ONLY actively assigned deliverables currently in progress, pending, or submitted
            $liveTasks = $tasks->filter(function($task) {
                return !empty($task->assigned_to) && $task->status !== 'completed';
            })->values();

            // 2. History Tasks: ALL historical records (Completed tasks, Unassigned tasks, past archives)
            $historyTasks = $tasks->filter(function($task) {
                return empty($task->assigned_to) || $task->status === 'completed';
            })->values();

            $completedTasks = $tasks->where('status', 'completed')->values();
            $unassignedTasks = $tasks->whereNull('assigned_to')->values();

            $members = $user->isTL()
                ? User::where('created_by', $user->id)->get()
                : User::whereIn('role', ['member', 'tl', 'hr'])->get(); // CEO can assign to anyone

            // Fetch today's attendance for all relevant members
            $todayAttendances = Attendance::whereIn('user_id', $members->pluck('id'))
                ->whereDate('date', $today)
                ->get()
                ->keyBy('user_id');

            return view('tl.tasks', compact(
                'tasks', 'liveTasks', 'historyTasks', 'completedTasks', 'unassignedTasks',
                'members', 'todayAttendances', 'today'
            ));
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
            $msg = "Cannot assign task: {$assignee->name} is marked " . ucfirst(str_replace('_', ' ', $todayAttendance->status)) . " today. Tasks can only be delegated to active/present team members.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Task successfully assigned to {$assignee->name} and dispatched via email.",
                'task'    => [
                    'id'            => $task->id,
                    'title'         => $task->title,
                    'description'   => $task->description,
                    'assigned_to'   => $task->assigned_to,
                    'assignee_name' => $assignee->name,
                    'deadline'      => $task->deadline->format('d M Y, h:i A'),
                    'deadline_iso'  => $task->deadline->toISOString(),
                    'status'        => $task->status,
                ],
            ]);
        }

        return redirect()->route('tasks.index')->with('success', "Task successfully assigned to {$assignee->name}.");
    }

    public function update(Request $request, Task $task)
    {
        $actor = Auth::user();

        // 1. Authorization: Only the assigner TL or CEO can edit task specifications
        if ($task->assigned_by !== $actor->id && !$actor->isCEO()) {
            abort(403, 'Unauthorized: Only the supervisor who assigned this task can edit its specifications.');
        }

        // 2. Strict Submission Lock: Once task is submitted by employee, it CANNOT be edited by anyone!
        if (in_array($task->status, ['submitted', 'completed']) || !is_null($task->submitted_at)) {
            $msg = 'Task cannot be edited after submission by employee. Task specifications are locked once work is submitted.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'deadline'    => 'required|date',
        ]);

        $newAssignee = $request->filled('assigned_to') ? User::findOrFail($request->assigned_to) : null;

        if ($newAssignee) {
            // Security check: TL can only assign tasks to their own squad members
            if ($actor->isTL() && $newAssignee->created_by !== $actor->id) {
                abort(403, 'Unauthorized: Team Leads can only assign tasks to members in their own assigned team.');
            }

            // Attendance check if assignee changed
            if ($task->assigned_to != $newAssignee->id) {
                $today = now()->format('Y-m-d');
                $todayAttendance = Attendance::where('user_id', $newAssignee->id)
                    ->whereDate('date', $today)
                    ->first();

                if ($todayAttendance && in_array($todayAttendance->status, ['absent', 'on_leave'])) {
                    $msg = "Cannot reassign task: {$newAssignee->name} is marked " . ucfirst(str_replace('_', ' ', $todayAttendance->status)) . " today.";
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withInput()->with('error', $msg);
                }
            }
        }

        $oldTitle = $task->title;
        $task->update([
            'title'       => $request->title,
            'description' => $request->description,
            'assigned_to' => $newAssignee ? $newAssignee->id : null,
            'deadline'    => $request->deadline,
        ]);

        ActivityLog::log(
            action: 'task_updated',
            description: sprintf('%s edited task "%s" (Assignee: %s, Deadline: %s)',
                $actor->name,
                $task->title,
                $newAssignee ? $newAssignee->name : 'Unassigned',
                $task->deadline->format('d M Y, h:i A')
            ),
            entityType: 'Task',
            entityId: $task->id,
            userId: $actor->id
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task specifications updated successfully!',
                'task'    => [
                    'id'            => $task->id,
                    'title'         => $task->title,
                    'description'   => $task->description,
                    'assigned_to'   => $task->assigned_to,
                    'assignee_name' => $newAssignee ? $newAssignee->name : 'Unassigned',
                    'deadline'      => $task->deadline->format('d M Y, h:i A'),
                    'deadline_iso'  => $task->deadline->toISOString(),
                    'status'        => $task->status,
                ],
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Task specifications updated successfully.');
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Progress update logged successfully!',
                'task_id' => $task->id,
                'status'  => $task->status,
                'note'    => [
                    'message'    => $request->message,
                    'created_at' => now()->format('d M, h:i A'),
                ],
            ]);
        }

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
            'reviewed_at'          => null,
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

        // Instantly dispatch email notification to the employee's assigned Team Lead
        \App\Services\BrevoMailService::sendTaskSubmittedMail($task);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'              => true,
                'message'              => 'Deliverables successfully submitted to Team Lead for review!',
                'task_id'              => $task->id,
                'status'               => 'submitted',
                'status_label'         => 'Under TL Review',
                'submitted_at'         => $task->submitted_at->format('d M Y, h:i A'),
                'submitted_at_iso'     => $task->submitted_at->toISOString(),
                'submission_formatted' => $task->submission_formatted,
                'timer_config'         => $task->timer_config,
            ]);
        }

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
            'reviewed_at'        => now(),
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'          => true,
                'message'          => 'Task has been reassigned to member with new deadline and revision directives.',
                'task_id'          => $task->id,
                'status'           => 'in-progress',
                'new_deadline'     => \Carbon\Carbon::parse($newDeadline)->format('d M Y, h:i A'),
                'new_deadline_iso' => \Carbon\Carbon::parse($newDeadline)->toISOString(),
                'revision_notes'   => $request->revision_notes,
            ]);
        }

        return back()->with('success', 'Task has been reassigned to member with new deadline and revision directives.');
    }

    public function complete(Request $request, Task $task)
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
                            'reviewed_at'     => now(),
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
                        $task->update([
                            'status'      => 'completed',
                            'reviewed_at' => now(),
                        ]);
                        $uploadMessage = ' Note: Local file could not be uploaded to Google Drive (' . $e->getMessage() . ').';
                    }
                } else {
                    // Credentials not yet placed; delete from local storage as approved or keep with notice
                    $task->update([
                        'status'      => 'completed',
                        'reviewed_at' => now(),
                    ]);
                    $uploadMessage = ' Approved. (Google Drive credentials.json not found in root, local file kept).';
                }
            } else {
                $task->update([
                    'status'      => 'completed',
                    'reviewed_at' => now(),
                ]);
            }
        } else {
            $task->update([
                'status'      => 'completed',
                'reviewed_at' => now(),
            ]);
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'           => true,
                'message'           => 'Task approved and marked as completed!' . $uploadMessage,
                'task_id'           => $task->id,
                'task_title'        => $task->title,
                'status'            => 'completed',
                'reviewed_at'       => $task->reviewed_at ? $task->reviewed_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A'),
                'reviewed_at_iso'   => $task->reviewed_at ? $task->reviewed_at->toISOString() : now()->toISOString(),
                'review_duration'   => $task->review_duration,
                'timer_config'      => $task->timer_config,
            ]);
        }

        return back()->with('success', 'Task approved and marked as completed!' . $uploadMessage);
    }

    public function unassign(Request $request, Task $task)
    {
        $actor = Auth::user();

        // Only assigner TL or CEO can unassign
        if ($task->assigned_by !== $actor->id && !$actor->isCEO()) {
            abort(403, 'Unauthorized: Only the supervisor who assigned this task can unassign it.');
        }

        // Cannot unassign submitted or completed tasks
        if (in_array($task->status, ['submitted', 'completed']) || !is_null($task->submitted_at)) {
            $msg = 'Task cannot be unassigned after submission or completion.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $oldAssignee = $task->assignedTo;
        $oldAssigneeName = $oldAssignee ? $oldAssignee->name : 'Member';
        $isOverdue = $task->isOverdue();

        $task->update([
            'assigned_to' => null,
            'status'      => 'pending',
        ]);

        TaskUpdate::create([
            'task_id' => $task->id,
            'message' => sprintf('Task unassigned from %s by %s%s.',
                $oldAssigneeName,
                $actor->name,
                $isOverdue ? ' due to overdue status' : ''
            ),
        ]);

        ActivityLog::log(
            action: 'task_unassigned',
            description: sprintf('%s unassigned task "%s" from %s%s',
                $actor->name,
                $task->title,
                $oldAssigneeName,
                $isOverdue ? ' (Overdue)' : ''
            ),
            entityType: 'Task',
            entityId: $task->id,
            userId: $actor->id
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Task \"{$task->title}\" successfully unassigned from {$oldAssigneeName}.",
                'task_id' => $task->id,
            ]);
        }

        return back()->with('success', "Task \"{$task->title}\" successfully unassigned from {$oldAssigneeName}.");
    }

    public function assignMember(Request $request, Task $task)
    {
        $actor = Auth::user();

        if ($task->assigned_by !== $actor->id && !$actor->isCEO()) {
            abort(403, 'Unauthorized: Only the supervisor who assigned this task can assign members.');
        }

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'deadline'    => 'nullable|date',
        ]);

        $newAssignee = User::findOrFail($request->assigned_to);

        // Security check
        if ($actor->isTL() && $newAssignee->created_by !== $actor->id) {
            abort(403, 'Unauthorized: Team Leads can only assign tasks to members in their own assigned team.');
        }

        // Attendance check
        $today = now()->format('Y-m-d');
        $todayAttendance = Attendance::where('user_id', $newAssignee->id)
            ->whereDate('date', $today)
            ->first();

        if ($todayAttendance && in_array($todayAttendance->status, ['absent', 'on_leave'])) {
            $msg = "Cannot assign task: {$newAssignee->name} is marked " . ucfirst(str_replace('_', ' ', $todayAttendance->status)) . " today.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
        }

        $updateData = [
            'assigned_to' => $newAssignee->id,
            'status'      => 'pending',
        ];
        if ($request->filled('deadline')) {
            $updateData['deadline'] = $request->deadline;
        }

        $task->update($updateData);

        TaskUpdate::create([
            'task_id' => $task->id,
            'message' => sprintf('Task assigned to %s by %s.', $newAssignee->name, $actor->name),
        ]);

        ActivityLog::log(
            action: 'task_assigned',
            description: sprintf('%s assigned task "%s" to %s', $actor->name, $task->title, $newAssignee->name),
            entityType: 'Task',
            entityId: $task->id,
            userId: $actor->id
        );

        // Instantly dispatch email notification to the new assignee
        \App\Services\BrevoMailService::sendTaskAssignedMail($task);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Task successfully assigned to {$newAssignee->name}.",
                'task_id' => $task->id,
            ]);
        }

        return back()->with('success', "Task successfully assigned to {$newAssignee->name}.");
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

    public function sendOverdueReminder(Request $request, Task $task)
    {
        $actor = Auth::user();
        if (!$actor->isTL() && !$actor->isCEO() && !$actor->isHR() && $actor->id !== $task->assigned_by) {
            abort(403, 'Unauthorized: Only supervisors can dispatch overdue task reminders.');
        }

        $employee = $task->assignedTo;
        if (!$employee || empty($employee->email)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Assigned employee has no email address.'], 422);
            }
            return back()->with('error', 'Assigned employee has no valid email address.');
        }

        $sent = \App\Services\BrevoMailService::sendOverdueTaskReminder($task);

        if ($sent) {
            $task->update([
                'overdue_reminder_sent_at' => now(),
                'overdue_reminder_count'   => $task->overdue_reminder_count + 1,
                'overdue_reminder_type'    => 'manual',
            ]);

            ActivityLog::log(
                action: 'overdue_reminder_sent',
                description: sprintf(
                    '%s dispatched formal overdue reminder #%d to %s (%s) for task "%s"',
                    $actor->name,
                    $task->overdue_reminder_count,
                    $employee->name,
                    $employee->email,
                    $task->title
                ),
                entityType: 'Task',
                entityId: $task->id,
                userId: $actor->id
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Overdue email reminder dispatched to {$employee->name} ({$employee->email})!",
                    'sent_at' => now()->format('d M, h:i A'),
                    'count'   => $task->overdue_reminder_count,
                    'type'    => 'manual',
                ]);
            }

            return back()->with('success', "Overdue reminder email successfully dispatched to {$employee->name} ({$employee->email}).");
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => 'Email dispatch failed. Please check mail settings.'], 500);
        }

        return back()->with('error', 'Failed to dispatch email reminder. Please check email configuration.');
    }

    /**
     * Live synchronization endpoint for real-time task status updates (zero refresh).
     */
    public function sync(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Opportunistic automated overdue scan (throttled internally to 5 mins via Cache)
        if ($user->isOperationsLead()) {
            try {
                \App\Services\OverdueReminderService::scanAndDispatchAutomaticReminders();
            } catch (\Throwable $e) {
                // Silently pass
            }
        }

        // Base query for tasks matching user scope
        $baseQuery = Task::query();
        if ($user->isOperationsLead()) {
            if ($user->isTL()) {
                $memberIds = User::where('created_by', $user->id)->pluck('id');
                $baseQuery->where(function ($q) use ($user, $memberIds) {
                    $q->where('assigned_by', $user->id)
                      ->orWhereIn('assigned_to', $memberIds);
                });
            }
        } else {
            $baseQuery->where('assigned_to', $user->id);
        }

        $latestTaskUpdate = (clone $baseQuery)->max('updated_at');
        $latestTimestamp = $latestTaskUpdate ? strtotime($latestTaskUpdate) : 0;
        $totalCount = (clone $baseQuery)->count();

        $clientSince = (int) $request->get('since', 0);
        $clientCount = (int) $request->get('known_count', -1);

        // Fast-path: If client has latest timestamp and total count has not changed, return 0 heavy relations!
        if ($clientSince > 0 && $clientSince >= $latestTimestamp && ($clientCount === -1 || $clientCount === $totalCount)) {
            return response()->json([
                'success'   => true,
                'changed'   => false,
                'timestamp' => $latestTimestamp,
            ]);
        }

        // Slow-path: changes occurred or initial sync -> load relations and full task payload
        if ($user->isOperationsLead()) {
            $tasksQuery = (clone $baseQuery)->with(['assignedTo', 'assignedBy', 'updates']);
            $tasks = $tasksQuery->latest()->get();
        } else {
            $tasks = (clone $baseQuery)->with(['assignedBy', 'updates'])->latest()->get();
        }

        $tasksData = $tasks->map(function ($t) {
            return [
                'id'                       => $t->id,
                'title'                    => $t->title,
                'description'              => $t->description,
                'status'                   => $t->status,
                'is_overdue'               => $t->isOverdue(),
                'overdue_reminder_sent_at' => $t->overdue_reminder_sent_at ? $t->overdue_reminder_sent_at->format('d M, h:i A') : null,
                'overdue_reminder_count'   => $t->overdue_reminder_count ?? 0,
                'overdue_reminder_type'    => $t->overdue_reminder_type,
                'is_reassigned'            => $t->isReassigned(),
                'reassignment_count'       => $t->reassignment_count ?? 0,
                'revision_notes'           => $t->revision_notes,
                'deadline_iso'             => $t->deadline ? $t->deadline->toISOString() : null,
                'deadline_formatted'       => $t->deadline ? $t->deadline->format('d M, h:i A') : 'None',
                'submitted_at_iso'         => $t->submitted_at ? $t->submitted_at->toISOString() : null,
                'submitted_at_formatted'   => $t->submitted_at ? $t->submitted_at->format('d M, h:i A') : null,
                'reviewed_at_iso'          => $t->reviewed_at ? $t->reviewed_at->toISOString() : null,
                'reviewed_at_formatted'    => $t->reviewed_at ? $t->reviewed_at->format('d M, h:i A') : null,
                'review_duration'          => $t->review_duration,
                'due_label'                => $t->due_label,
                'updates_count'            => $t->updates->count(),
                'assignee_name'            => $t->assignedTo?->name ?? 'Unassigned',
                'assigner_name'            => $t->assignedBy?->name ?? 'Team Lead',
                'updated_at_timestamp'     => $t->updated_at?->timestamp,
            ];
        });

        $counts = [
            'total'       => $tasks->count(),
            'pending'     => $tasks->whereIn('status', ['pending', 'in-progress'])->count(),
            'submitted'   => $tasks->where('status', 'submitted')->count(),
            'completed'   => $tasks->where('status', 'completed')->count(),
            'overdue'     => $tasks->filter(fn($t) => $t->isOverdue())->count(),
        ];

        return response()->json([
            'success'   => true,
            'role'      => $user->role,
            'counts'    => $counts,
            'tasks'     => $tasksData,
            'timestamp' => now()->timestamp,
        ]);
    }
}