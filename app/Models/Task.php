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

    public function getDeadlineReminderAttribute(): array {
        if ($this->isOverdue()) {
            return [
                'label'     => 'Overdue by ' . $this->deadline->diffForHumans(null, true),
                'class'     => 'bg-rose-50 text-rose-700 border-rose-200',
                'badge'     => 'Overdue',
                'is_urgent' => true,
            ];
        }

        $diffHours = (int) ceil(now()->diffInHours($this->deadline, false));
        if ($diffHours <= 24) {
            return [
                'label'     => 'Due in ' . max(1, $diffHours) . 'h (' . $this->deadline->format('h:i A') . ')',
                'class'     => 'bg-amber-50 text-amber-800 border-amber-300',
                'badge'     => 'Due in 24h',
                'is_urgent' => true,
            ];
        }

        $diffDays = (int) ceil(now()->diffInDays($this->deadline, false));
        return [
            'label'     => 'Due in ' . max(1, $diffDays) . ' day' . ($diffDays > 1 ? 's' : '') . ' (' . $this->deadline->format('d M, h:i A') . ')',
            'class'     => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'badge'     => 'Due in 2 Days',
            'is_urgent' => false,
        ];
    }
}
