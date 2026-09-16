<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    protected $fillable = [
        'user_id',
        'reviewed_by',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'review_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'reviewed_at' => 'datetime',
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
        return match($this->status) {
            'approved' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'Approved', 'icon' => 'check-circle-2'],
            'rejected' => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'label' => 'Rejected', 'icon' => 'x-circle'],
            'pending'  => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'Pending Review', 'icon' => 'clock'],
            default    => ['bg' => 'bg-slate-100 text-slate-600 border-slate-200', 'label' => ucfirst($this->status), 'icon' => 'help-circle']
        };
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return match($this->leave_type) {
            'casual'    => 'Casual Leave',
            'sick'      => 'Sick Leave',
            'emergency' => 'Emergency Leave',
            'privilege' => 'Privilege Leave',
            default     => ucfirst($this->leave_type)
        };
    }
}