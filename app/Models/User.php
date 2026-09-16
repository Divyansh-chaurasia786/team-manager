<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'mobile_number',
        'designation',
        'password',
        'must_change_password',
        'temp_password_plain',
        'role',
        'created_by',
        'profile_photo_path',
        'security_otp',
        'security_otp_expires_at',
        'pending_profile_update',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'temp_password_plain',
        'security_otp',
    ];

    protected function casts(): array {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'must_change_password'    => 'boolean',
            'security_otp_expires_at' => 'datetime',
            'pending_profile_update'  => 'array',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->profile_photo_path && file_exists(public_path($this->profile_photo_path))) {
            return asset($this->profile_photo_path);
        }
        return null;
    }

    public function tasks() { return $this->hasMany(Task::class, 'assigned_to'); }
    public function assignedTasks() { return $this->hasMany(Task::class, 'assigned_by'); }
    public function weeklyPlans() { return $this->hasMany(WeeklyPlan::class, 'user_id'); }
    public function createdWeeklyPlans() { return $this->hasMany(WeeklyPlan::class, 'created_by'); }
    public function driveFiles() { return $this->hasMany(DriveFile::class, 'uploaded_by'); }
    public function teamMembers() { return $this->hasMany(User::class, 'created_by'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function thoughts() { return $this->hasMany(TeamThought::class, 'user_id'); }
    public function shootsCreated() { return $this->hasMany(ContentShoot::class, 'created_by'); }
    public function shootsAsCamera() { return $this->hasMany(ContentShoot::class, 'camera_person_id'); }
    public function shootsAsModel() { return $this->hasMany(ContentShoot::class, 'model_id'); }
    public function shootsAsEditor() { return $this->hasMany(ContentShoot::class, 'editor_id'); }
    public function isTL(): bool  { return $this->role === 'tl'; }
    public function isHR(): bool  { return $this->role === 'hr'; }
    public function isCEO(): bool { return $this->role === 'ceo'; }

    /** True for TL, HR, and CEO — any management-level role */
    public function isAdmin(): bool { return in_array($this->role, ['tl', 'hr', 'ceo']); }

    /** True for TL or CEO — those who can manage tasks/shoots at the operational level */
    public function isOperationsLead(): bool { return in_array($this->role, ['tl', 'ceo']); }
}