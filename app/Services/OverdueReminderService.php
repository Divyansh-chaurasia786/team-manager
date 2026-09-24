<?php
namespace App\Services;

use App\Models\Task;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OverdueReminderService
{
    /**
     * Scan overdue tasks and automatically dispatch reminder emails.
     * Throttled by default so web requests/sync calls don't repeatedly query the DB.
     */
    public static function scanAndDispatchAutomaticReminders(bool $force = false): int
    {
        $lockKey = 'overdue_reminders_auto_scan_ts';
        if (!$force) {
            $lastScan = Cache::get($lockKey);
            // Run at most once every 5 minutes during web requests
            if ($lastScan && now()->diffInSeconds($lastScan) < 300) {
                return 0;
            }
        }
        Cache::put($lockKey, now(), 300);

        $now = now();
        $tasks = Task::with(['assignedTo', 'assignedBy'])
            ->whereNotNull('assigned_to')
            ->where('deadline', '<', $now)
            ->whereNotIn('status', ['submitted', 'completed'])
            ->where(function ($q) use ($now) {
                $q->whereNull('overdue_reminder_sent_at')
                  ->orWhere('overdue_reminder_sent_at', '<', $now->copy()->subHours(24));
            })
            ->get();

        if ($tasks->isEmpty()) {
            return 0;
        }

        $sentCount = 0;
        foreach ($tasks as $task) {
            $employee = $task->assignedTo;
            if (!$employee || empty($employee->email)) {
                continue;
            }

            try {
                $sent = BrevoMailService::sendOverdueTaskReminder($task);
                if ($sent) {
                    $newCount = $task->overdue_reminder_count + 1;
                    $task->update([
                        'overdue_reminder_sent_at' => now(),
                        'overdue_reminder_count'   => $newCount,
                        'overdue_reminder_type'    => 'automatic',
                    ]);

                    ActivityLog::log(
                        action: 'overdue_reminder_sent',
                        description: sprintf(
                            'Automatic overdue reminder #%d dispatched to %s (%s) for task "%s" (Deadline: %s).',
                            $newCount,
                            $employee->name,
                            $employee->email,
                            $task->title,
                            $task->deadline->format('d M Y, h:i A')
                        ),
                        entityType: 'Task',
                        entityId: $task->id,
                        userId: $task->assigned_by ?? $employee->id
                    );

                    $sentCount++;
                }
            } catch (\Throwable $e) {
                Log::error(sprintf('Automatic overdue reminder error (Task #%d): %s', $task->id, $e->getMessage()));
            }
        }

        return $sentCount;
    }
}
