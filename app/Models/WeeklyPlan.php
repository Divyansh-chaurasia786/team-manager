<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'user_id',
        'title',
        'goals',
        'week_start_date',
        'week_end_date',
        'priority',
        'status',
        'days_breakdown',
        'tl_notes',
        'employee_feedback',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date'   => 'date',
        'days_breakdown'  => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match($this->priority) {
            'critical' => 'rose',
            'high'     => 'orange',
            'medium'   => 'indigo',
            'low'      => 'slate',
            default    => 'slate',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft'       => 'slate',
            'shared'      => 'indigo',
            'in-progress' => 'amber',
            'completed'   => 'emerald',
            default       => 'slate',
        };
    }
}
