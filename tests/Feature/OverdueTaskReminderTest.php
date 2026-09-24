<?php

namespace Tests\Feature;

use App\Mail\OverdueTaskReminderMail;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OverdueTaskReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $tl;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::create([
            'name' => 'Sarah Supervisor',
            'username' => 'sarah_sup',
            'email' => 'sarah.sup@company.org',
            'password' => Hash::make('Secret123!'),
            'role' => 'tl',
            'designation' => 'Engineering Lead',
            'must_change_password' => false,
        ]);

        $this->employee = User::create([
            'name' => 'Alex Developer',
            'username' => 'alex_dev',
            'email' => 'alex.dev@company.org',
            'password' => Hash::make('Secret123!'),
            'role' => 'member',
            'designation' => 'Frontend Developer',
            'created_by' => $this->tl->id,
            'must_change_password' => false,
        ]);
    }

    public function test_overdue_task_dispatches_formal_email_reminder(): void
    {
        Mail::fake();

        $task = Task::create([
            'title' => 'Deliver Payment Gateway Integration',
            'description' => 'Complete Stripe webhook endpoints and automated unit tests.',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subHours(5),
            'status' => 'in-progress',
        ]);

        $this->artisan('tasks:send-overdue-reminders')
            ->assertExitCode(0);

        Mail::assertSent(OverdueTaskReminderMail::class, function ($mail) use ($task) {
            return $mail->hasTo('alex.dev@company.org') &&
                   $mail->task->id === $task->id;
        });

        $task->refresh();
        $this->assertNotNull($task->overdue_reminder_sent_at);
        $this->assertEquals(1, $task->overdue_reminder_count);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'overdue_reminder_sent',
            'entity_type' => 'Task',
            'entity_id' => $task->id,
        ]);
    }

    public function test_completed_and_submitted_tasks_are_not_reminded(): void
    {
        Mail::fake();

        // Completed task past deadline
        Task::create([
            'title' => 'Past Deadline But Completed',
            'description' => 'All done already',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subDays(2),
            'status' => 'completed',
        ]);

        // Submitted task past deadline
        Task::create([
            'title' => 'Past Deadline But Submitted',
            'description' => 'Awaiting TL review',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subHours(12),
            'status' => 'submitted',
            'submitted_at' => now()->subHours(1),
        ]);

        // Future task
        Task::create([
            'title' => 'Future Task Still On Time',
            'description' => 'Still has plenty of time',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(2),
            'status' => 'pending',
        ]);

        $this->artisan('tasks:send-overdue-reminders')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_reminders_rate_limited_to_24_hours_unless_forced(): void
    {
        Mail::fake();

        $task = Task::create([
            'title' => 'Rate Limited Task',
            'description' => 'Should only receive 1 email per 24 hours',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subDays(1),
            'status' => 'pending',
            'overdue_reminder_sent_at' => now()->subHours(4),
            'overdue_reminder_count' => 1,
        ]);

        // 1st run without force -> skipped
        $this->artisan('tasks:send-overdue-reminders')
            ->assertExitCode(0);

        Mail::assertNothingSent();

        // 2nd run with --force -> sent
        $this->artisan('tasks:send-overdue-reminders', ['--force' => true])
            ->assertExitCode(0);

        Mail::assertSent(OverdueTaskReminderMail::class, 1);

        $task->refresh();
        $this->assertEquals(2, $task->overdue_reminder_count);
    }

    public function test_overdue_email_renders_detailed_and_formal_content(): void
    {
        $task = Task::create([
            'title' => 'Audit Security Compliance',
            'description' => 'Review all user permission gates and audit trail exports.',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subHours(48),
            'status' => 'in-progress',
            'revision_notes' => 'Please include CSV export check as well.',
        ]);

        $mailable = new OverdueTaskReminderMail($task);
        $rendered = $mailable->render();

        $this->assertStringContainsString('Formal Overdue Deliverable Notification', $rendered);
        $this->assertStringContainsString('Audit Security Compliance', $rendered);
        $this->assertStringContainsString('Sarah Supervisor', $rendered);
        $this->assertStringContainsString('sarah.sup@company.org', $rendered);
        $this->assertStringContainsString('Review all user permission gates and audit trail exports.', $rendered);
        $this->assertStringContainsString('Please include CSV export check as well.', $rendered);
        $this->assertStringContainsString('View & Submit Deliverable', $rendered);
    }

    public function test_unassigned_tasks_past_deadline_do_not_trigger_overdue_reminders(): void
    {
        Mail::fake();

        Task::create([
            'title' => 'Unassigned Project Awaiting Delegation',
            'description' => 'Has no assigned team member',
            'assigned_to' => null,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subDays(3),
            'status' => 'pending',
        ]);

        $this->artisan('tasks:send-overdue-reminders')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }
}