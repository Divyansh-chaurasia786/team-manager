<?php

namespace App\Http\Controllers;

use App\Models\PersonalTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalTaskController extends Controller
{
    /**
     * Display the user's personal tasks + tasks shared to them.
     */
    public function index()
    {
        $user = Auth::user();

        // My own tasks (ordered newest first)
        $myTasks = PersonalTask::where('user_id', $user->id)
            ->latest()
            ->get();

        // Tasks shared TO me by subordinates
        $sharedWithMe = PersonalTask::where('shared_to', $user->id)
            ->with('owner')
            ->latest()
            ->get();

        return view('personal_tasks.index', compact('myTasks', 'sharedWithMe'));
    }

    /**
     * Store a new personal task.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date'    => 'nullable|date',
            'priority'    => 'nullable|in:low,medium,high',
        ]);

        PersonalTask::create([
            'user_id'     => Auth::id(),
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date'    => $validated['due_date'] ?? null,
            'priority'    => $validated['priority'] ?? 'medium',
            'status'      => 'pending',
            'is_shared'   => false,
        ]);

        return back()->with('success', 'Task created successfully!');
    }

    /**
     * Update a personal task (title, description, due_date, priority, status).
     * Only owner can update.
     */
    public function update(Request $request, PersonalTask $personalTask)
    {
        abort_if($personalTask->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date'    => 'nullable|date',
            'priority'    => 'nullable|in:low,medium,high',
            'status'      => 'nullable|in:pending,in-progress,completed',
        ]);

        $personalTask->update([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date'    => $validated['due_date'] ?? null,
            'priority'    => $validated['priority'] ?? $personalTask->priority,
            'status'      => $validated['status'] ?? $personalTask->status,
        ]);

        return back()->with('success', 'Task updated successfully!');
    }

    /**
     * Delete a personal task. Only owner can delete.
     */
    public function destroy(PersonalTask $personalTask)
    {
        abort_if($personalTask->user_id !== Auth::id(), 403);
        $personalTask->delete();

        return back()->with('success', 'Task deleted.');
    }

    /**
     * Quick status toggle (AJAX-friendly, returns JSON).
     */
    public function updateStatus(Request $request, PersonalTask $personalTask)
    {
        abort_if($personalTask->user_id !== Auth::id(), 403);

        $request->validate(['status' => 'required|in:pending,in-progress,completed']);
        $personalTask->update(['status' => $request->status]);

        return back()->with('success', 'Status updated.');
    }

    /**
     * Share a task to the user's higher authority.
     */
    public function share(PersonalTask $personalTask)
    {
        abort_if($personalTask->user_id !== Auth::id(), 403);

        $user      = Auth::user();
        $authority = $this->getHigherAuthority($user);

        if (!$authority) {
            return back()->with('error', 'No higher authority to share with.');
        }

        $personalTask->update([
            'is_shared' => true,
            'shared_to' => $authority->id,
        ]);

        return back()->with('success', "Task shared with {$authority->name}.");
    }

    /**
     * Unshare a task (make private again).
     */
    public function unshare(PersonalTask $personalTask)
    {
        abort_if($personalTask->user_id !== Auth::id(), 403);

        $personalTask->update([
            'is_shared' => false,
            'shared_to' => null,
        ]);

        return back()->with('success', 'Task is now private.');
    }

    /**
     * Determine the higher authority for a user.
     * Member → their TL (created_by), TL → CEO, HR → CEO, CEO → null
     */
    private function getHigherAuthority(User $user): ?User
    {
        if ($user->isCEO()) {
            return null;
        }

        if ($user->isTL() || $user->isHR()) {
            // TL and HR report to CEO
            return User::where('role', 'ceo')->first();
        }

        // Member → their TL (the user who created them)
        if ($user->created_by) {
            $creator = User::find($user->created_by);
            if ($creator && $creator->isTL()) {
                return $creator;
            }
        }

        // Fallback: any TL
        return User::where('role', 'tl')->first();
    }
}
