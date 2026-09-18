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
    ];

    protected $casts = [
        'deadline'                 => 'datetime',
        'previous_deadline'        => 'datetime',
        'submitted_at'             => 'datetime',
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
        return $this->status !== 'completed' && $this->deadline->isPast();
    }

    public function isDueSoon(int $days = 2): bool {
        return $this->status !== 'completed' && $this->deadline->isFuture() && $this->deadline->lte(now()->addDays($days));
    }

    /**
     * Human-friendly due label used everywhere in views.
     * Returns: "Overdue by X", "Due Today", "Due Tomorrow", "Due in X hours", "Due in X days"
     */
    public function getDueLabelAttribute(): string
    {
        $now      = now();
        $deadline = $this->deadline;

        // Already past
        if ($deadline->isPast()) {
            $diffH = (int) abs($deadline->diffInHours($now));
            $diffD = (int) abs($deadline->diffInDays($now));
            if ($diffH < 24) {
                return 'Overdue by ' . ($diffH < 1 ? 'less than 1 hr' : $diffH . 'h');
            }
            return 'Overdue by ' . $diffD . ' day' . ($diffD > 1 ? 's' : '');
        }

        // Due today (same calendar day)
        if ($deadline->isToday()) {
            $diffH = (int) ceil($now->diffInRealHours($deadline, false));
            if ($diffH <= 1) {
                return 'Due Today — in less than 1h';
            }
            return 'Due Today — ' . $diffH . 'h left (' . $deadline->format('h:i A') . ')';
        }

        // Due tomorrow (next calendar day)
        if ($deadline->isTomorrow()) {
            $diffH = (int) ceil($now->diffInRealHours($deadline, false));
            return 'Due Tomorrow — ' . $diffH . 'h left (' . $deadline->format('h:i A') . ')';
        }

        // More than 1 day away — show whole hours if < 48h, else days
        $diffTotalHours = (int) ceil($now->diffInRealHours($deadline, false));
        if ($diffTotalHours < 48) {
            return 'Due in ' . $diffTotalHours . 'h (' . $deadline->format('d M, h:i A') . ')';
        }

        $diffDays = (int) floor($now->diffInDays($deadline));
        return 'Due in ' . $diffDays . ' day' . ($diffDays > 1 ? 's' : '') . ' (' . $deadline->format('d M, h:i A') . ')';
    }

    /**
     * Full reminder card data used in 2-day dashboard reminder section.
     */
    public function getDeadlineReminderAttribute(): array
    {
        $label    = $this->due_label;
        $deadline = $this->deadline;
        $now      = now();

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
