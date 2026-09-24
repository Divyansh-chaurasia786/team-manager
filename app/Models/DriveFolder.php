<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriveFolder extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'drive_folder_id',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DriveFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DriveFolder::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DriveFile::class, 'folder_id');
    }

    /**
     * Get ancestors from root down to this folder.
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $current = $this;

        while ($current) {
            array_unshift($breadcrumbs, [
                'id'   => $current->id,
                'name' => $current->name,
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }
}
