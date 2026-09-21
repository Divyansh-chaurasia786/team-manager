<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class TeamThought extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function ($thought) {
            if ($thought->isDirty('media_path') || $thought->isDirty('is_deleted')) {
                Cache::forget("thought_media_url_{$thought->id}");
            }
        });

        static::deleted(function ($thought) {
            Cache::forget("thought_media_url_{$thought->id}");
        });
    }

    protected $fillable = [
        'user_id',
        'group_type',
        'tl_id',
        'content',
        'link_url',
        'media_path',
        'media_type',
        'media_original_name',
        'media_mime_type',
        'media_size',
        'drive_file_id',
        'drive_url',
        'uploaded_to_drive_at',
        'uploaded_to_drive_by',
        'expires_at',
        'is_expired',
        'is_deleted',
        'deleted_by',
        'deleted_at',
        'seen_by',
        'reactions',
    ];

    protected $casts = [
        'uploaded_to_drive_at' => 'datetime',
        'expires_at'           => 'datetime',
        'is_expired'           => 'boolean',
        'is_deleted'           => 'boolean',
        'deleted_at'           => 'datetime',
        'seen_by'              => 'array',
        'reactions'            => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tl_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function driveUploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_to_drive_by');
    }

    public function isUnsendableBy(User $user): bool
    {
        if ($this->is_deleted) {
            return false;
        }
        // Sender can unsend under 24 hours
        return $this->user_id === $user->id && $this->created_at->diffInHours(now()) <= 24;
    }

    public function isDeletableBy(User $user): bool
    {
        if ($this->is_deleted) {
            return false;
        }
        // TL can delete anyone's message
        if ($user->isTL() || $this->tl_id === $user->id) {
            return true;
        }
        // Sender can unsend/delete within 24h
        return $this->isUnsendableBy($user);
    }

    public function hasDriveSync(): bool
    {
        return !empty($this->drive_url);
    }

    public function hasLocalMedia(): bool
    {
        return !empty($this->media_path) && !$this->is_expired;
    }

    public function isExpired(): bool
    {
        if ($this->is_expired) {
            return true;
        }
        if (!$this->hasDriveSync() && $this->expires_at && $this->expires_at->isPast()) {
            return true;
        }
        return false;
    }

    public function daysRemaining(): int
    {
        if (!$this->expires_at || $this->hasDriveSync() || $this->is_expired) {
            return 0;
        }
        return (int) max(0, ceil(now()->diffInRealHours($this->expires_at, false) / 24));
    }
}

