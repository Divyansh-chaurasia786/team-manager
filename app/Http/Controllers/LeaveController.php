<?php
namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveQuota;
use App\Models\LeaveSetting;
use App\Models\Attendance;
use App\Models\User;
use App\Models\ActivityLog;
use App\Mail\LeaveNotificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * Display Leave Portal with role-specific views and controls.
     */
    public function index()
    {
        $user = Auth::user();
        $year = (int) date('Y');

        // Personal leave summary (available for everyone including members, TL, HR, CEO)
        $leaveSummary = $user->getLeaveSummary($year);

        // Personal applications
        $myLeaves = LeaveApplication::with(['reviewer'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        // TL Visibility Setting for this user
        $tlId = $user->isTL() ? $user->id : ($user->created_by ?? null);
        $isLeaveEnabled = LeaveSetting::isLeaveEnabledFor($tlId);

        // Review collections
        $pendingLeaves = collect();
        $reviewedLeaves = collect();
        $allStaffSummaries = collect();

        if ($user->isTL()) {
            // TL can view team members' leaves, but CANNOT approve/reject
            $teamMemberIds = User::where('created_by', $user->id)->pluck('id');

            $pendingLeaves = LeaveApplication::with(['user', 'reviewer'])
                ->whereIn('user_id', $teamMemberIds)
                ->where('status', 'pending')
                ->latest()
                ->get();

            $reviewedLeaves = LeaveApplication::with(['user', 'reviewer'])
                ->whereIn('user_id', $teamMemberIds)
                ->where('status', '!=', 'pending')
                ->latest()
                ->take(30)
                ->get();

        } elseif ($user->isHR()) {
            // HR can review all member and TL leaves
            $candidateUserIds = User::whereIn('role', ['member', 'tl'])->pluck('id');

            $pendingLeaves = LeaveApplication::with(['user', 'reviewer'])
                ->whereIn('user_id', $candidateUserIds)
                ->where('status', 'pending')
                ->latest()
                ->get();

            $reviewedLeaves = LeaveApplication::with(['user', 'reviewer'])
                ->whereIn('user_id', $candidateUserIds)
                ->where('status', '!=', 'pending')
                ->latest()
                ->take(40)
                ->get();

            // Staff summaries for HR leave planning and quotas
            $allStaffSummaries = User::whereIn('role', ['member', 'tl'])
                ->orderBy('name')
                ->get()
                ->map(fn($u) => array_merge(['user' => $u], $u->getLeaveSummary($year)));

        } elseif ($user->isCEO()) {
            // CEO can review all leaves across the organization
            $allStaffIds = User::where('id', '!=', $user->id)->pluck('id');

            $pendingLeaves = LeaveApplication::with(['user', 'reviewer'])
                ->whereIn('user_id', $allStaffIds)
                ->where('status', 'pending')
                ->latest()
                ->get();

            $reviewedLeaves = LeaveApplication::with(['user', 'reviewer'])
                ->whereIn('user_id', $allStaffIds)
                ->where('status', '!=', 'pending')
                ->latest()
                ->take(50)
                ->get();

            // All staff summaries for CEO oversight
            $allStaffSummaries = User::where('id', '!=', $user->id)
                ->orderBy('name')
                ->get()
                ->map(fn($u) => array_merge(['user' => $u], $u->getLeaveSummary($year)));
        }

        return view('shared.leaves', compact(
            'pendingLeaves',
            'reviewedLeaves',
            'myLeaves',
            'leaveSummary',
            'isLeaveEnabled',
            'allStaffSummaries',
            'year'
        ));
    }

    /**
     * Submit a new leave application.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if leave applications are toggled OFF by TL
        if ($user->role === 'member') {
            $tlId = $user->created_by;
            if (!LeaveSetting::isLeaveEnabledFor($tlId)) {
                return back()->with('error', 'Leave applications are currently closed by Team Leadership.');
            }
        }

        $request->validate([
            'leave_type' => 'required|in:casual,sick,emergency,privilege',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|min:6',
        ]);

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);
        $totalDays = $start->diffInDays($end) + 1;

        $leave = LeaveApplication::create([
            'user_id'    => $user->id,
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
                $user->name,
                $totalDays,
                $leave->leave_type_label,
                $request->start_date,
                $request->end_date,
                \Illuminate\Support\Str::limit($request->reason, 60)
            ),
            entityType: 'LeaveApplication',
            entityId: $leave->id
        );

        // Deliver email notification to approvers (HR and CEO)
        $this->sendNotificationSafely(
            recipients: $this->getApproverEmailsFor($user),
            mailable: new LeaveNotificationMail($leave, 'applied')
        );

        return back()->with('success', 'Leave application submitted successfully and forwarded to HR for evaluation.');
    }

    /**
     * Toggle leave application availability (ON/OFF).
     */
    public function toggle(Request $request)
    {
        $user = Auth::user();
        if (!$user->isTL() && !$user->isHR() && !$user->isCEO()) {
            return back()->with('error', 'Unauthorized action.');
        }

        $tlId = null;
        if ($user->isTL()) {
            $tlId = $user->id;
        } elseif ($request->has('tl_id')) {
            $tlId = $request->input('tl_id');
        }

        $currentStatus = LeaveSetting::isLeaveEnabledFor($tlId);
        $newStatus = !$currentStatus;

        LeaveSetting::updateOrCreate(
            ['tl_id' => $tlId],
            ['is_leave_enabled' => $newStatus, 'toggled_by' => $user->id]
        );

        $statusText = $newStatus ? 'ENABLED (Applications open for members)' : 'DISABLED (Applications hidden from members)';
        
        ActivityLog::log(
            action: 'leave_toggle_updated',
            description: sprintf('%s switched leave applications to %s', $user->name, $statusText),
            entityType: 'LeaveSetting',
            entityId: $tlId ?: 0
        );

        return back()->with('success', "Leave applications are now {$statusText}.");
    }

    /**
     * Approve leave application (Strictly HR or CEO; TL cannot approve).
     */
    public function approve(Request $request, LeaveApplication $leave)
    {
        $reviewer = Auth::user();

        // 1. TL CANNOT approve
        if ($reviewer->isTL()) {
            return back()->with('error', 'Team Leads can view leave requests, but approvals must be granted by HR or CEO.');
        }

        // 2. HR applicant can only be approved by CEO
        if ($leave->user && $leave->user->isHR() && !$reviewer->isCEO()) {
            return back()->with('error', 'HR staff leave requests must be approved by the CEO.');
        }

        $requestCategory = $request->input('approved_leave_type', $leave->leave_type);
        $reviewNotes = $request->input('review_notes');
        $grantWithoutBalance = $request->boolean('grant_without_balance');

        // 3. Balance verification
        $applicant = $leave->user;
        $summary = $applicant->getLeaveSummary((int) $leave->start_date->format('Y'));
        $totalRemaining = $summary['total_remaining'];

        $isCeoGranted = false;

        if ($leave->total_days > $totalRemaining) {
            if (!$reviewer->isCEO()) {
                return back()->with('error', "Cannot grant leave: {$applicant->name} has insufficient leave balance (Remaining balance: {$totalRemaining} days). Under company policy, only the CEO has authorization to grant leave without balance.");
            }

            // CEO approval with negative balance
            $isCeoGranted = true;
        } elseif ($reviewer->isCEO() && $grantWithoutBalance) {
            $isCeoGranted = true;
        }

        $leave->update([
            'status'              => 'approved',
            'approved_leave_type' => $requestCategory,
            'is_ceo_granted'      => $isCeoGranted,
            'reviewed_by'         => $reviewer->id,
            'review_notes'        => $reviewNotes,
            'reviewed_at'         => now(),
        ]);

        // 4. Synchronize attendance records to 'on_leave'
        $cur = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);
        while ($cur->lte($end)) {
            Attendance::updateOrCreate(
                ['user_id' => $leave->user_id, 'date' => $cur->format('Y-m-d')],
                [
                    'marked_by' => $reviewer->id,
                    'status'    => 'on_leave',
                    'notes'     => "Approved Leave: {$leave->leave_type_label}" . ($isCeoGranted ? ' (CEO Special Grant - Negative Balance)' : ''),
                ]
            );
            $cur->addDay();
        }

        ActivityLog::log(
            action: 'leave_approved',
            description: sprintf('%s approved %s\'s %s for %s to %s%s',
                $reviewer->name,
                $leave->user->name,
                $leave->leave_type_label,
                $leave->start_date->format('Y-m-d'),
                $leave->end_date->format('Y-m-d'),
                $isCeoGranted ? ' (CEO Granted without balance - Negative Balance)' : ''
            ),
            entityType: 'LeaveApplication',
            entityId: $leave->id
        );

        // 5. Send email update to applicant
        $updatedSummary = $applicant->getLeaveSummary((int) $leave->start_date->format('Y'));
        $this->sendNotificationSafely(
            recipients: [$applicant->email],
            mailable: new LeaveNotificationMail(
                leave: $leave,
                eventType: $isCeoGranted ? 'ceo_granted' : 'approved',
                remarks: $reviewNotes,
                leaveSummary: $updatedSummary
            )
        );

        $msg = $isCeoGranted
            ? "Approved leave by CEO Executive Grant for {$leave->user->name}. Negative balance applied to account."
            : "Approved leave for {$leave->user->name}. Attendance synchronized to On Leave.";

        return back()->with('success', $msg);
    }

    /**
     * Reject leave application (HR or CEO; TL cannot reject).
     */
    public function reject(Request $request, LeaveApplication $leave)
    {
        $reviewer = Auth::user();

        // 1. TL CANNOT reject
        if ($reviewer->isTL()) {
            return back()->with('error', 'Team Leads can view leave requests, but rejection decisions must be made by HR or CEO.');
        }

        // 2. HR applicant can only be reviewed by CEO
        if ($leave->user && $leave->user->isHR() && !$reviewer->isCEO()) {
            return back()->with('error', 'HR staff leave requests must be decided by the CEO.');
        }

        $request->validate(['review_notes' => 'nullable|string']);
        $notes = $request->input('review_notes', 'Application rejected.');

        $leave->update([
            'status'       => 'rejected',
            'reviewed_by'  => $reviewer->id,
            'review_notes' => $notes,
            'reviewed_at'  => now(),
        ]);

        // 3. Keep attendance as absent or set absent if not marked present
        $cur = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);
        while ($cur->lte($end)) {
            $existing = Attendance::where('user_id', $leave->user_id)
                ->where('date', $cur->format('Y-m-d'))
                ->first();

            if (!$existing || $existing->status === 'on_leave') {
                Attendance::updateOrCreate(
                    ['user_id' => $leave->user_id, 'date' => $cur->format('Y-m-d')],
                    [
                        'marked_by' => $reviewer->id,
                        'status'    => 'absent',
                        'notes'     => 'Leave application rejected: ' . $notes,
                    ]
                );
            }
            $cur->addDay();
        }

        ActivityLog::log(
            action: 'leave_rejected',
            description: sprintf('%s rejected %s\'s %s. Remarks: %s',
                $reviewer->name,
                $leave->user->name,
                $leave->leave_type_label,
                $notes
            ),
            entityType: 'LeaveApplication',
            entityId: $leave->id
        );

        // 4. Send email update to applicant
        $this->sendNotificationSafely(
            recipients: [$leave->user->email],
            mailable: new LeaveNotificationMail(
                leave: $leave,
                eventType: 'rejected',
                remarks: $notes,
                leaveSummary: $leave->user->getLeaveSummary()
            )
        );

        return back()->with('success', "Rejected leave application for {$leave->user->name}.");
    }

    /**
     * Update leave quotas for an employee (HR or CEO).
     */
    public function updateQuota(Request $request)
    {
        $user = Auth::user();
        if (!$user->isHR() && !$user->isCEO()) {
            return back()->with('error', 'Only HR and CEO can manage leave quotas.');
        }

        $request->validate([
            'user_id'         => 'required|exists:users,id',
            'year'            => 'required|integer|min:2025|max:2035',
            'casual_quota'    => 'required|integer|min:0|max:100',
            'sick_quota'      => 'required|integer|min:0|max:100',
            'emergency_quota' => 'required|integer|min:0|max:100',
            'privilege_quota' => 'required|integer|min:0|max:100',
        ]);

        $quota = LeaveQuota::updateOrCreate(
            ['user_id' => $request->user_id, 'year' => $request->year],
            [
                'casual_quota'    => $request->casual_quota,
                'sick_quota'      => $request->sick_quota,
                'emergency_quota' => $request->emergency_quota,
                'privilege_quota' => $request->privilege_quota,
            ]
        );

        $targetUser = User::find($request->user_id);

        ActivityLog::log(
            action: 'leave_quota_updated',
            description: sprintf('%s updated %d leave quota for %s (Casual: %d, Sick: %d, Emergency: %d, Privilege: %d)',
                $user->name,
                $request->year,
                $targetUser->name,
                $request->casual_quota,
                $request->sick_quota,
                $request->emergency_quota,
                $request->privilege_quota
            ),
            entityType: 'LeaveQuota',
            entityId: $quota->id
        );

        return back()->with('success', "Updated {$request->year} leave quota for {$targetUser->name} successfully.");
    }

    /**
     * Safe email dispatch helper.
     */
    protected function sendNotificationSafely(array $recipients, $mailable): void
    {
        try {
            $filtered = array_filter(array_unique($recipients));
            if (!empty($filtered)) {
                Mail::to($filtered)->send($mailable);
            }
        } catch (\Throwable $e) {
            Log::warning('Leave email delivery notice: ' . $e->getMessage());
        }
    }

    /**
     * Helper to get approver emails for an applicant.
     */
    protected function getApproverEmailsFor(User $applicant): array
    {
        $emails = [];

        // HR emails
        $hrEmails = User::where('role', 'hr')->pluck('email')->toArray();
        $emails = array_merge($emails, $hrEmails);

        // CEO emails
        $ceoEmails = User::where('role', 'ceo')->pluck('email')->toArray();
        $emails = array_merge($emails, $ceoEmails);

        // If member, include TL for visibility
        if ($applicant->role === 'member' && $applicant->created_by) {
            $tl = User::find($applicant->created_by);
            if ($tl && $tl->email) {
                $emails[] = $tl->email;
            }
        }

        return array_values(array_unique(array_filter($emails)));
    }
}