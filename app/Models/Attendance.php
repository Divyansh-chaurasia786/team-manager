<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'marked_by',
        'date',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'present'   => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'Present', 'icon' => 'check-circle-2'],
            'absent'    => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'label' => 'Absent', 'icon' => 'x-circle'],
            'half_day'  => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'Half Day', 'icon' => 'clock'],
            'wfh'       => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200', 'label' => 'WFH', 'icon' => 'home'],
            'on_leave'  => ['bg' => 'bg-purple-50 text-purple-700 border-purple-200', 'label' => 'On Leave', 'icon' => 'calendar-off'],
            default     => ['bg' => 'bg-slate-100 text-slate-600 border-slate-200', 'label' => ucfirst($this->status), 'icon' => 'help-circle']
        };
    }
}