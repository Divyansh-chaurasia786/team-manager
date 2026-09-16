<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamThought extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
    ];

    protected $casts = [
        'uploaded_to_drive_at' => 'datetime',
        'expires_at'           => 'datetime',
        'is_expired'           => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function driveUploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_to_drive_by');
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

