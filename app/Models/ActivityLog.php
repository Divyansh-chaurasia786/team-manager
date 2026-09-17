<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, string $description, ?string $entityType = null, ?int $entityId = null, ?int $userId = null): ?self
    {
        $resolvedUserId = $userId ?? auth()->id() ?? ($entityType === 'User' ? $entityId : null);
        if (!$resolvedUserId) {
            return null;
        }

        return self::create([
            'user_id'     => $resolvedUserId,
            'action'      => $action,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }

    public function getActionBadgeAttribute(): array
    {
        return match($this->action) {
            'file_uploaded'   => ['bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'icon' => 'upload-cloud', 'label' => 'Uploaded File'],
            'file_downloaded' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'download', 'label' => 'Downloaded File'],
            'file_deleted'    => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'trash-2', 'label' => 'Deleted File'],
            'task_assigned'   => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'plus-circle', 'label' => 'Assigned Task'],
            'task_updated'    => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'edit-3', 'label' => 'Added Note'],
            'task_submitted'  => ['bg' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'send', 'label' => 'Submitted Task'],
            'task_completed'  => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'check-circle-2', 'label' => 'Approved Task'],
            'task_deleted'    => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'trash-2', 'label' => 'Removed Task'],
            'member_added'    => ['bg' => 'bg-teal-50 text-teal-700 border-teal-200', 'icon' => 'user-plus', 'label' => 'Added Member'],
            'member_removed'  => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'user-minus', 'label' => 'Removed Member'],
            default           => ['bg' => 'bg-slate-100 text-slate-600 border-slate-200', 'icon' => 'activity', 'label' => ucfirst(str_replace('_', ' ', $this->action))]
        };
    }
}