<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    protected $fillable = [
        'user_id',
        'reviewed_by',
        'leave_type',
        'approved_leave_type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'is_ceo_granted',
        'review_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'reviewed_at'    => 'datetime',
        'is_ceo_granted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusBadgeAttribute(): array
    {
        if ($this->status === 'approved' && $this->is_ceo_granted) {
            return [
                'bg'    => 'bg-violet-50 text-violet-700 border-violet-200',
                'label' => 'CEO Approved (Negative Balance)',
                'icon'  => 'shield-check',
            ];
        }

        return match($this->status) {
            'approved' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'Approved', 'icon' => 'check-circle-2'],
            'rejected' => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'label' => 'Rejected', 'icon' => 'x-circle'],
            'pending'  => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'Pending Review', 'icon' => 'clock'],
            default    => ['bg' => 'bg-slate-100 text-slate-600 border-slate-200', 'label' => ucfirst($this->status), 'icon' => 'help-circle']
        };
    }

    public function getEffectiveLeaveTypeAttribute(): string
    {
        return $this->approved_leave_type ?: $this->leave_type;
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return match($this->effective_leave_type) {
            'casual'    => 'Casual Leave',
            'sick'      => 'Sick Leave',
            'emergency' => 'Emergency Leave',
            'privilege' => 'Privilege Leave',
            default     => ucfirst($this->effective_leave_type)
        };
    }
}