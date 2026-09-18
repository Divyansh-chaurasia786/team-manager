<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonalTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'due_date',
        'priority',
        'status',
        'is_shared',
        'shared_to',
    ];

    protected $casts = [
        'due_date'  => 'date',
        'is_shared' => 'boolean',
    ];

    /** The user who owns this task */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The user this task has been shared to (higher authority) */
    public function sharedToUser()
    {
        return $this->belongsTo(User::class, 'shared_to');
    }

    /** Priority badge color helper */
    public function priorityColor(): string
    {
        return match ($this->priority) {
            'high'   => 'rose',
            'medium' => 'amber',
            'low'    => 'emerald',
            default  => 'slate',
        };
    }

    /** Status badge color helper */
    public function statusColor(): string
    {
        return match ($this->status) {
            'completed'   => 'emerald',
            'in-progress' => 'blue',
            'pending'     => 'slate',
            default       => 'slate',
        };
    }
}
