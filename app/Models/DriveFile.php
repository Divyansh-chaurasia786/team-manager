<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveFile extends Model
{
    protected $fillable = [
        'uploaded_by',
        'folder_id',
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
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DriveFolder::class, 'folder_id');
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
        return in_array($ext, ['mp4', 'mov', 'webm', 'ogg', 'mkv']);
    }
}
