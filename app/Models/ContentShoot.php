<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentShoot extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'managing_member_id',
        'title',
        'platform',
        'instagram_handle',
        'youtube_channel',
        'shoot_date',
        'location',
        'camera_person_id',
        'camera_person_name',
        'model_id',
        'model_name',
        'editor_id',
        'editor_name',
        'director_id',
        'director_name',
        'other_crew',
        'hook',
        'script',
        'concept_notes',
        'reference_links',
        'status',
        'target_publish_date',
        'published_url',
    ];

    protected $casts = [
        'shoot_date'          => 'datetime',
        'target_publish_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function managingMember()
    {
        return $this->belongsTo(User::class, 'managing_member_id');
    }

    public function cameraPerson()
    {
        return $this->belongsTo(User::class, 'camera_person_id');
    }

    public function model()
    {
        return $this->belongsTo(User::class, 'model_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    /**
     * Helpers for resolved crew names (picks assigned user or custom name).
     */
    public function getCameraNameAttribute(): string
    {
        return $this->cameraPerson ? $this->cameraPerson->name : ($this->camera_person_name ?: 'Unassigned');
    }

    public function getModelDisplayNameAttribute(): string
    {
        return $this->model ? $this->model->name : ($this->model_name ?: 'Unassigned');
    }

    public function getEditorDisplayNameAttribute(): string
    {
        return $this->editor ? $this->editor->name : ($this->editor_name ?: 'Unassigned');
    }

    public function getDirectorDisplayNameAttribute(): string
    {
        return $this->director ? $this->director->name : ($this->director_name ?: 'Unassigned');
    }

    public function getManagingMemberNameAttribute(): string
    {
        return $this->managingMember ? $this->managingMember->name : ($this->creator ? $this->creator->name . ' (TL)' : 'Team Lead');
    }

    /**
     * Check if a specific user is the managing member of this shoot.
     */
    public function isManager(User $user): bool
    {
        // If an explicit managing member is assigned, check if user is that member
        if ($this->managing_member_id) {
            return $this->managing_member_id === $user->id;
        }

        // If no managing member is assigned, the shoot is managed by the TL
        return $user->isTL() || $this->created_by === $user->id;
    }

    /**
     * Check who is authorized to update production status (shooting completed or not, etc.).
     * Rule: If a managing member is assigned, that member (and TL) can update status.
     * If no managing member is assigned, only the TL can update status.
     */
    public function canUpdateStatus(User $user): bool
    {
        if ($user->isTL() || $user->isCEO()) {
            return true;
        }

        if ($this->managing_member_id) {
            return $this->managing_member_id === $user->id;
        }

        return false;
    }

    /**
     * Check if a specific user is part of this shoot.
     */
    public function isAssignedUser(User $user): bool
    {
        return $this->created_by === $user->id ||
               $this->managing_member_id === $user->id ||
               $this->camera_person_id === $user->id ||
               $this->model_id === $user->id ||
               $this->editor_id === $user->id ||
               $this->director_id === $user->id;
    }

    /**
     * Human-readable status badges and styling.
     */
    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'planning'   => ['label' => 'Planning', 'class' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'sparkles'],
            'scripting'  => ['label' => 'Scripting', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'icon' => 'file-text'],
            'scheduled'  => ['label' => 'Scheduled', 'class' => 'bg-sky-50 text-sky-700 border-sky-200', 'icon' => 'calendar-check'],
            'shooting'   => ['label' => 'Shooting', 'class' => 'bg-amber-50 text-amber-800 border-amber-300 animate-pulse', 'icon' => 'video'],
            'editing'    => ['label' => 'In Editing', 'class' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'scissors'],
            'review'     => ['label' => 'Under Review', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'eye'],
            'published'  => ['label' => 'Published', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'check-circle-2'],
            default      => ['label' => ucfirst($this->status), 'class' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'circle'],
        };
    }

    /**
     * Platform badge helper.
     */
    public function getPlatformInfoAttribute(): array
    {
        return match($this->platform) {
            'instagram' => ['label' => 'Instagram', 'class' => 'bg-pink-50 text-pink-700 border-pink-200', 'icon' => 'instagram'],
            'youtube'   => ['label' => 'YouTube', 'class' => 'bg-red-50 text-red-700 border-red-200', 'icon' => 'youtube'],
            'both'      => ['label' => 'IG & YouTube', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'icon' => 'share-2'],
            default     => ['label' => ucfirst($this->platform), 'class' => 'bg-slate-50 text-slate-700 border-slate-200', 'icon' => 'film'],
        };
    }
}
