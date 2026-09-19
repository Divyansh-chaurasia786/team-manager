<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveQuota extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'casual_quota',
        'sick_quota',
        'emergency_quota',
        'privilege_quota',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create current year quota for a user with standard defaults.
     */
    public static function getOrCreateForUser(int $userId, ?int $year = null): self
    {
        $year = $year ?: (int) date('Y');

        return static::firstOrCreate(
            ['user_id' => $userId, 'year' => $year],
            [
                'casual_quota'    => 12,
                'sick_quota'      => 8,
                'emergency_quota' => 5,
                'privilege_quota' => 15,
            ]
        );
    }

    /**
     * Total allocated leaves across all categories.
     */
    public function getTotalQuotaAttribute(): int
    {
        return $this->casual_quota + $this->sick_quota + $this->emergency_quota + $this->privilege_quota;
    }
}
