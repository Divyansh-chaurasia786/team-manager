<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveSetting extends Model
{
    protected $fillable = [
        'tl_id',
        'is_leave_enabled',
        'toggled_by',
    ];

    protected function casts(): array
    {
        return [
            'is_leave_enabled' => 'boolean',
        ];
    }

    public function teamLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tl_id');
    }

    public function toggledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'toggled_by');
    }

    /**
     * Check if leave application is enabled for a given TL's team (or global).
     */
    public static function isLeaveEnabledFor(?int $tlId = null): bool
    {
        if ($tlId) {
            $setting = static::where('tl_id', $tlId)->first();
            if ($setting !== null) {
                return (bool) $setting->is_leave_enabled;
            }
        }

        $global = static::whereNull('tl_id')->first();
        if ($global !== null) {
            return (bool) $global->is_leave_enabled;
        }

        return true; // default enabled
    }
}
