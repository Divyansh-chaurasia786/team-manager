<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'assigned_to',
        'assigned_by',
        'deadline',
        'previous_deadline',
        'status',
        'submitted_at',
        'reviewed_at',
        'submission_remarks',
        'submission_link',
        'submission_file',
        'submission_file_type',
        'drive_file_id',
        'drive_url',
        'revision_notes',
        'reassignment_count',
        'overdue_reminder_sent_at',
        'overdue_reminder_count',
        'overdue_reminder_type',
    ];

    protected $casts = [
        'deadline'                 => 'datetime',
        'previous_deadline'        => 'datetime',
        'submitted_at'             => 'datetime',
        'reviewed_at'              => 'datetime',
        'overdue_reminder_sent_at' => 'datetime',
    ];

    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
    public function updates() { return $this->hasMany(TaskUpdate::class); }

    public function isReassigned(): bool {
        return $this->previous_deadline !== null && $this->reassignment_count > 0;
    }

    public function getStatusBadgeAttribute(): string {
        return match($this->status) {
            'pending'     => 'secondary',
            'in-progress' => 'warning',
            'submitted'   => 'info',
            'completed'   => 'success',
            default       => 'secondary'
        };
    }

    public function isOverdue(): bool {
        return !in_array($this->status, ['completed', 'submitted']) && $this->deadline && $this->deadline->isPast();
    }

    public function isDueSoon(int $days = 2): bool {
        return !in_array($this->status, ['completed', 'submitted']) && $this->deadline && $this->deadline->isFuture() && $this->deadline->lte(now()->addDays($days));
    }

    public function getSubmissionFormattedAttribute(): ?string
    {
        return $this->submitted_at ? $this->submitted_at->format('d M Y, h:i A') : null;
    }

    public function getReviewFormattedAttribute(): ?string
    {
        return $this->reviewed_at ? $this->reviewed_at->format('d M Y, h:i A') : null;
    }

    public function getReviewDurationAttribute(): ?string
    {
        if (!$this->submitted_at) return null;
        $end = $this->reviewed_at ?? now();
        $diffM = (int) $this->submitted_at->diffInMinutes($end);
        if ($diffM < 1) return 'less than a min';
        if ($diffM < 60) return $diffM . ' min' . ($diffM > 1 ? 's' : '');
        $h = intdiv($diffM, 60);
        $m = $diffM % 60;
        return $h . 'h' . ($m > 0 ? ' ' . $m . 'm' : '');
    }

    public function getTimerConfigAttribute(): array
    {
        return [
            'id'                     => $this->id,
            'status'                 => $this->status,
            'deadline_iso'           => $this->deadline ? $this->deadline->toISOString() : null,
            'submitted_at_iso'       => $this->submitted_at ? $this->submitted_at->toISOString() : null,
            'reviewed_at_iso'        => $this->reviewed_at ? $this->reviewed_at->toISOString() : null,
            'submission_formatted'   => $this->submission_formatted,
            'review_formatted'       => $this->review_formatted,
            'review_duration'        => $this->review_duration,
            'employee_timer_stopped' => in_array($this->status, ['submitted', 'completed']),
            'review_timer_running'   => ($this->status === 'submitted' && $this->submitted_at && !$this->reviewed_at),
        ];
    }

    /**
     * Human-friendly due label used everywhere in views.
     * Returns: "Overdue by X", "Due Today", "Due Tomorrow", "Due in X hours", "Due in X days", or status-based labels
     */
    public function getDueLabelAttribute(): string
    {
        if ($this->status === 'completed') {
            return 'Completed & Approved' . ($this->reviewed_at ? ' (' . $this->reviewed_at->format('d M, h:i A') . ')' : '');
        }
        if ($this->status === 'submitted') {
            return 'Submitted (' . ($this->submitted_at ? $this->submitted_at->format('d M, h:i A') : 'Under Review') . ')';
        }

        if (!$this->deadline) {
            return 'No Deadline Set';
        }

        $now      = now();
        $deadline = $this->deadline;

        // Already past
        if ($deadline->isPast()) {
            $diffM = (int) abs($deadline->diffInMinutes($now));
            if ($diffM < 60) {
                return 'Overdue by ' . max(1, $diffM) . 'm';
            }
            $h = intdiv($diffM, 60);
            $m = $diffM % 60;
            if ($h < 24) {
                return 'Overdue by ' . $h . 'h' . ($m > 0 ? ' ' . $m . 'm' : '');
            }
            $d = intdiv($h, 24);
            $remH = $h % 24;
            return 'Overdue by ' . $d . ' day' . ($d > 1 ? 's' : '') . ($remH > 0 ? ' ' . $remH . 'h' : '');
        }

        // Due today (same calendar day)
        if ($deadline->isToday()) {
            $diffM = (int) max(0, $now->diffInMinutes($deadline, false));
            $h = intdiv($diffM, 60);
            $m = $diffM % 60;
            if ($diffM <= 5) {
                return 'Due Today — in less than 5 mins (' . $deadline->format('h:i A') . ')';
            }
            if ($h === 0) {
                return 'Due Today — ' . $diffM . 'm left (' . $deadline->format('h:i A') . ')';
            }
            if ($m === 0) {
                return 'Due Today — ' . $h . 'h left (' . $deadline->format('h:i A') . ')';
            }
            return 'Due Today — ' . $h . 'h ' . $m . 'm left (' . $deadline->format('h:i A') . ')';
        }

        // Due tomorrow (next calendar day)
        if ($deadline->isTomorrow()) {
            $diffM = (int) max(0, $now->diffInMinutes($deadline, false));
            $h = intdiv($diffM, 60);
            $m = $diffM % 60;
            if ($m === 0) {
                return 'Due Tomorrow — ' . $h . 'h left (' . $deadline->format('h:i A') . ')';
            }
            return 'Due Tomorrow — ' . $h . 'h ' . $m . 'm left (' . $deadline->format('h:i A') . ')';
        }

        // More than 1 day away — show whole hours and minutes if < 48h, else days
        $diffM = (int) max(0, $now->diffInMinutes($deadline, false));
        $diffTotalHours = intdiv($diffM, 60);
        $diffRemM = $diffM % 60;
        if ($diffTotalHours < 48) {
            return 'Due in ' . $diffTotalHours . 'h' . ($diffRemM > 0 ? ' ' . $diffRemM . 'm' : '') . ' (' . $deadline->format('d M, h:i A') . ')';
        }

        $diffDays = (int) floor($now->diffInDays($deadline));
        return 'Due in ' . max(1, $diffDays) . ' day' . ($diffDays > 1 ? 's' : '') . ' (' . $deadline->format('d M, h:i A') . ')';
    }

    /**
     * Full reminder card data used in 2-day dashboard reminder section.
     */
    public function getDeadlineReminderAttribute(): array
    {
        $label    = $this->due_label;
        $deadline = $this->deadline;
        $now      = now();

        if ($this->status === 'submitted') {
            return [
                'label'     => 'Deliverables Submitted — Awaiting TL Review',
                'class'     => 'bg-purple-50 text-purple-700 border-purple-200',
                'badge'     => 'Submitted',
                'is_urgent' => false,
            ];
        }

        if ($this->status === 'completed') {
            return [
                'label'     => 'Completed & Approved',
                'class'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'badge'     => 'Completed',
                'is_urgent' => false,
            ];
        }

        // Overdue
        if ($deadline->isPast()) {
            return [
                'label'     => $label,
                'class'     => 'bg-rose-50 text-rose-700 border-rose-200',
                'badge'     => 'Overdue',
                'is_urgent' => true,
            ];
        }

        // Due today
        if ($deadline->isToday()) {
            return [
                'label'     => $label,
                'class'     => 'bg-rose-50 text-rose-800 border-rose-300',
                'badge'     => 'Due Today',
                'is_urgent' => true,
            ];
        }

        // Due tomorrow
        if ($deadline->isTomorrow()) {
            return [
                'label'     => $label,
                'class'     => 'bg-amber-50 text-amber-800 border-amber-300',
                'badge'     => 'Due Tomorrow',
                'is_urgent' => true,
            ];
        }

        // Within 48 hours but not today/tomorrow edge case
        $diffTotalHours = (int) ceil($now->diffInRealHours($deadline, false));
        if ($diffTotalHours <= 48) {
            return [
                'label'     => $label,
                'class'     => 'bg-amber-50 text-amber-800 border-amber-300',
                'badge'     => 'Due in 2 Days',
                'is_urgent' => true,
            ];
        }

        return [
            'label'     => $label,
            'class'     => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'badge'     => 'Upcoming',
            'is_urgent' => false,
        ];
    }
}
