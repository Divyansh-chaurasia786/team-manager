<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveFile extends Model
{
    protected $fillable = [
        'uploaded_by',
        'folder_id',
        'task_id',
        'content_shoot_id',
        'account_handle',
        'platform',
        'original_name',
        'drive_file_id',
        'drive_url',
        'file_type',
        'file_size',
        'mime_type',
        'upload_date',
    ];

    protected $appends = [
        'formatted_size',
        'is_image',
        'is_video',
        'is_google_drive',
        'preview_embed_url',
        'thumbnail_url',
        'stream_url',
        'target_account',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DriveFolder::class, 'folder_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function contentShoot(): BelongsTo
    {
        return $this->belongsTo(ContentShoot::class, 'content_shoot_id');
    }

    public function getTargetAccountAttribute(): string
    {
        if (!empty($this->account_handle)) {
            return $this->account_handle;
        }
        if ($this->contentShoot) {
            return $this->contentShoot->instagram_handle 
                ?: ($this->contentShoot->youtube_channel ?: ('Reel #' . $this->contentShoot->id));
        }
        return 'General / No Account';
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if (empty($bytes)) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / pow(1024, $power), ($power > 1 ? 2 : 0), '.', '') . ' ' . ($units[$power] ?? 'B');
    }

    public function getIsImageAttribute(): bool
    {
        if ($this->file_type === 'photo') {
            return true;
        }
        $ext = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
    }

    public function getIsVideoAttribute(): bool
    {
        if ($this->file_type === 'video') {
            return true;
        }
        $ext = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'mov', 'webm', 'ogg', 'mkv', 'avi', 'flv']);
    }

    public function getIsGoogleDriveAttribute(): bool
    {
        return !empty($this->drive_file_id) && !str_starts_with($this->drive_file_id, 'local_');
    }

    public function getStreamUrlAttribute(): string
    {
        return route('drive.stream', $this);
    }

    public function getPreviewEmbedUrlAttribute(): string
    {
        if ($this->is_google_drive) {
            return "https://drive.google.com/file/d/{$this->drive_file_id}/preview";
        }
        return route('drive.stream', $this);
    }

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->is_google_drive) {
            return "https://drive.google.com/thumbnail?id={$this->drive_file_id}&sz=w500";
        }
        if ($this->is_image) {
            return route('drive.stream', $this);
        }
        return '';
    }
}
