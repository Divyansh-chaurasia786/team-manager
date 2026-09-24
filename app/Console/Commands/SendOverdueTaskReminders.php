<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendOverdueTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:send-overdue-reminders {--force : Send reminder even if one was sent recently}';

    protected $description = 'Scan unsubmitted overdue tasks and dispatch formal reminder emails to assigned employees';

    public function handle(): int
    {
        $now = now();
        $this->info("Scanning overdue unsubmitted tasks as of {$now->toDateTimeString()}...");

        // Find tasks where:
        // 1. deadline is past
        // 2. status is not 'submitted' or 'completed'
        $force = (bool) $this->option('force');
        $query = \App\Models\Task::with(['assignedTo', 'assignedBy'])
            ->whereNotNull('assigned_to')
            ->where('deadline', '<', $now)
            ->whereNotIn('status', ['submitted', 'completed']);

        if (!$force) {
            $query->where(function ($q) use ($now) {
                $q->whereNull('overdue_reminder_sent_at')
                  ->orWhere('overdue_reminder_sent_at', '<', $now->copy()->subHours(24));
            });
        }

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            $this->info('No overdue unsubmitted tasks require reminder dispatch.');
            return self::SUCCESS;
        }

        $this->info("Found {$tasks->count()} overdue task(s). Dispatching formal email reminders...");

        $sentCount = 0;
        foreach ($tasks as $task) {
            $employee = $task->assignedTo;
            if (!$employee || empty($employee->email)) {
                $this->warn("Task #{$task->id} ({$task->title}) has no assigned employee email. Skipping.");
                continue;
            }

            try {
                \App\Services\BrevoMailService::sendOverdueTaskReminder($task);

                $task->update([
                    'overdue_reminder_sent_at' => now(),
                    'overdue_reminder_count'   => $task->overdue_reminder_count + 1,
                    'overdue_reminder_type'    => 'automatic',
                ]);

                \App\Models\ActivityLog::log(
                    action: 'overdue_reminder_sent',
                    description: sprintf(
                        'Formal overdue reminder #%d dispatched to %s (%s) for task "%s" (Deadline: %s).',
                        $task->overdue_reminder_count,
                        $employee->name,
                        $employee->email,
                        $task->title,
                        $task->deadline->format('d M Y, h:i A')
                    ),
                    entityType: 'Task',
                    entityId: $task->id,
                    userId: $task->assigned_by ?? $employee->id
                );

                $this->line(" ✓ Reminder sent to {$employee->name} ({$employee->email}) for task: {$task->title}");
                $sentCount++;
            } catch (\Throwable $e) {
                $this->error(" ✕ Failed sending to {$employee->email}: " . $e->getMessage());
                \Illuminate\Support\Facades\Log::error("Overdue task reminder email error (Task #{$task->id}): " . $e->getMessage());
            }
        }

        $this->info("Successfully dispatched {$sentCount} overdue task reminder(s).");
        return self::SUCCESS;
    }
}
