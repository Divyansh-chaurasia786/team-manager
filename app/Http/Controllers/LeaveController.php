<?php
namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\Attendance;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Personal leaves (for member self-view, or if TL/HR/CEO wants to see their own requests)
        $myLeaves = LeaveApplication::where('user_id', $user->id)
            ->latest()
            ->get();

        // 2. Reviewer leaves (for TL, HR, CEO)
        $pendingLeaves = collect();
        $reviewedLeaves = collect();

        if ($user->isAdmin()) {
            if ($user->isTL()) {
                $memberIds = User::where('created_by', $user->id)->pluck('id');
            } else {
                $memberIds = User::whereIn('role', ['member', 'tl', 'hr'])->pluck('id');
            }

            $pendingLeaves = LeaveApplication::with('user')
                ->whereIn('user_id', $memberIds)
                ->where('status', 'pending')
                ->latest()
                ->get();

            $reviewedLeaves = LeaveApplication::with(['user', 'reviewer'])
                ->whereIn('user_id', $memberIds)
                ->where('status', '!=', 'pending')
                ->latest()
                ->take(20)
                ->get();
        }

        return view('shared.leaves', compact('pendingLeaves', 'reviewedLeaves', 'myLeaves'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type' => 'required|in:casual,sick,emergency,privilege',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|min:10',
        ]);

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);
        $totalDays = $start->diffInDays($end) + 1;

        $leave = LeaveApplication::create([
            'user_id'    => Auth::id(),
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'total_days' => $totalDays,
            'reason'     => $request->reason,
            'status'     => 'pending',
        ]);

        ActivityLog::log(
            action: 'leave_applied',
            description: sprintf('%s applied for %d day(s) %s (%s to %s). Reason: %s',
                Auth::user()->name,
                $totalDays,
                $leave->leave_type_label,
                $request->start_date,
                $request->end_date,
                \Illuminate\Support\Str::limit($request->reason, 60)
            ),
            entityType: 'LeaveApplication',
            entityId: $leave->id
        );

        return back()->with('success', 'Leave application submitted successfully for Team Lead review.');
    }

    public function approve(Request $request, LeaveApplication $leave)
    {
        $reviewer = Auth::user();
        if (!$reviewer->isTL() && !$reviewer->isHR() && !$reviewer->isCEO()) abort(403);

        $leave->update([
            'status'       => 'approved',
            'reviewed_by'  => $reviewer->id,
            'review_notes' => $request->input('review_notes'),
            'reviewed_at'  => now(),
        ]);

        // Automatically mark on_leave in attendance for dates
        $cur = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);
        while ($cur->lte($end)) {
            Attendance::updateOrCreate(
                ['user_id' => $leave->user_id, 'date' => $cur->format('Y-m-d')],
                [
                    'marked_by' => $reviewer->id,
                    'status'    => 'on_leave',
                    'notes'     => "Approved Leave: {$leave->leave_type_label}",
                ]
            );
            $cur->addDay();
        }

        ActivityLog::log(
            action: 'leave_approved',
            description: sprintf('%s approved %s\'s %s for %s to %s',
                $reviewer->name,
                $leave->user->name,
                $leave->leave_type_label,
                $leave->start_date->format('Y-m-d'),
                $leave->end_date->format('Y-m-d')
            ),
            entityType: 'LeaveApplication',
            entityId: $leave->id
        );

        return back()->with('success', "Approved leave for {$leave->user->name}. Attendance marked On Leave.");
    }

    public function reject(Request $request, LeaveApplication $leave)
    {
        $reviewer = Auth::user();
        if (!$reviewer->isTL() && !$reviewer->isHR() && !$reviewer->isCEO()) abort(403);

        $request->validate(['review_notes' => 'required|string']);

        $leave->update([
            'status'       => 'rejected',
            'reviewed_by'  => $reviewer->id,
            'review_notes' => $request->review_notes,
            'reviewed_at'  => now(),
        ]);

        ActivityLog::log(
            action: 'leave_rejected',
            description: sprintf('%s rejected %s\'s %s. Reason: %s',
                $reviewer->name,
                $leave->user->name,
                $leave->leave_type_label,
                $request->review_notes
            ),
            entityType: 'LeaveApplication',
            entityId: $leave->id
        );

        return back()->with('success', "Rejected leave application for {$leave->user->name}.");
    }
}