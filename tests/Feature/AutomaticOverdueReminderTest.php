<?php

namespace Tests\Feature;

use App\Mail\OverdueTaskReminderMail;
use App\Models\Task;
use App\Models\User;
use App\Services\OverdueReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AutomaticOverdueReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $tl;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::create([
            'name' => 'Sarah Lead',
            'username' => 'sarah_lead',
            'email' => 'sarah.lead@ecofone.org',
            'password' => Hash::make('Secret123!'),
            'role' => 'tl',
            'designation' => 'Team Lead',
            'must_change_password' => false,
        ]);

        $this->employee = User::create([
            'name' => 'Dev John',
            'username' => 'dev_john',
            'email' => 'john.dev@ecofone.org',
            'password' => Hash::make('Secret123!'),
            'role' => 'member',
            'designation' => 'Software Engineer',
            'created_by' => $this->tl->id,
            'must_change_password' => false,
        ]);
    }

    public function test_automatic_reminder_service_dispatches_email_and_marks_type_automatic(): void
    {
        Mail::fake();

        $task = Task::create([
            'title' => 'Critical Bugfix in Checkout Flow',
            'description' => 'Resolve Stripe webhook failure for EU customers.',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subHours(2),
            'status' => 'in-progress',
        ]);

        $sentCount = OverdueReminderService::scanAndDispatchAutomaticReminders(force: true);

        $this->assertEquals(1, $sentCount);

        Mail::assertSent(OverdueTaskReminderMail::class, function ($mail) use ($task) {
            return $mail->hasTo('john.dev@ecofone.org') &&
                   $mail->task->id === $task->id;
        });

        $task->refresh();
        $this->assertNotNull($task->overdue_reminder_sent_at);
        $this->assertEquals(1, $task->overdue_reminder_count);
        $this->assertEquals('automatic', $task->overdue_reminder_type);
    }

    public function test_cron_endpoint_dispatches_automatic_reminders(): void
    {
        Mail::fake();

        $task = Task::create([
            'title' => 'Overdue Task For Cron Webhook',
            'description' => 'Test dispatch via public cron route.',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subHours(3),
            'status' => 'pending',
        ]);

        $response = $this->getJson('/cron/send-overdue-reminders');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'sent'   => 1,
            ]);

        $task->refresh();
        $this->assertEquals('automatic', $task->overdue_reminder_type);
        $this->assertEquals(1, $task->overdue_reminder_count);
    }

    public function test_tl_dashboard_displays_automatic_reminder_sent_badge(): void
    {
        $task = Task::create([
            'title' => 'Scheduled Campaign Video',
            'description' => 'Final cut rendering and audio sync.',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subHours(4),
            'status' => 'in-progress',
            'overdue_reminder_sent_at' => now()->subMinutes(15),
            'overdue_reminder_count' => 1,
            'overdue_reminder_type' => 'automatic',
        ]);

        $response = $this->actingAs($this->tl)->get(route('tl.dashboard'));

        $response->assertOk();
        $response->assertSee('Automatic reminder sent:');
        $response->assertSee('Auto-reminded');
    }

    public function test_tl_tasks_page_displays_automatic_reminder_sent_badge_and_resend_alert(): void
    {
        $task = Task::create([
            'title' => 'Deploy Microservice API',
            'description' => 'Ensure zero-downtime deployment on container cluster.',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subHours(6),
            'status' => 'in-progress',
            'overdue_reminder_sent_at' => now()->subMinutes(30),
            'overdue_reminder_count' => 1,
            'overdue_reminder_type' => 'automatic',
        ]);

        $response = $this->actingAs($this->tl)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertSee('Automatic Reminder Sent:');
        $response->assertSee('Resend Alert');
    }

    public function test_manual_reminder_dispatch_records_type_manual(): void
    {
        Mail::fake();

        $task = Task::create([
            'title' => 'Manual Alert Inspection',
            'description' => 'Testing manual alert action by TL.',
            'assigned_to' => $this->employee->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subHours(10),
            'status' => 'in-progress',
        ]);

        $response = $this->actingAs($this->tl)->postJson(route('tasks.send_overdue_reminder', $task));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'count' => 1,
                'type' => 'manual',
            ]);

        $task->refresh();
        $this->assertEquals('manual', $task->overdue_reminder_type);
        $this->assertEquals(1, $task->overdue_reminder_count);
    }
}
