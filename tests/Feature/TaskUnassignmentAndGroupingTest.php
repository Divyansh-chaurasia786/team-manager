<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TaskUnassignmentAndGroupingTest extends TestCase
{
    use RefreshDatabase;

    private User $tl;
    private User $member1;
    private User $member2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::create([
            'name' => 'Team Lead Alice',
            'username' => 'tl_alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('password123'),
            'role' => 'tl',
            'must_change_password' => false,
        ]);

        $this->member1 = User::create([
            'name' => 'Bob Member',
            'username' => 'bob_member',
            'email' => 'bob@example.com',
            'password' => Hash::make('password123'),
            'role' => 'member',
            'created_by' => $this->tl->id,
            'must_change_password' => false,
        ]);

        $this->member2 = User::create([
            'name' => 'Charlie Member',
            'username' => 'charlie_member',
            'email' => 'charlie@example.com',
            'password' => Hash::make('password123'),
            'role' => 'member',
            'created_by' => $this->tl->id,
            'must_change_password' => false,
        ]);

        // Mark members present today
        Attendance::create([
            'user_id' => $this->member1->id,
            'marked_by' => $this->tl->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'present',
        ]);
        Attendance::create([
            'user_id' => $this->member2->id,
            'marked_by' => $this->tl->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'present',
        ]);
    }

    public function test_tasks_page_renders_grouped_by_assigned_date_and_members(): void
    {
        // Task assigned today to member 1
        $taskToday = Task::create([
            'title' => 'Landing Page Design',
            'description' => 'Create Figma mockup',
            'assigned_to' => $this->member1->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(2),
            'status' => 'pending',
            'created_at' => now(),
        ]);

        // Task assigned yesterday to member 2
        $taskYesterday = Task::create([
            'title' => 'Backend API Testing',
            'description' => 'Test auth endpoints',
            'assigned_to' => $this->member2->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subDay(), // Overdue
            'status' => 'in-progress',
        ]);
        $taskYesterday->created_at = now()->subDay();
        $taskYesterday->save();

        $response = $this->actingAs($this->tl)->get(route('tasks.index'));

        $response->assertStatus(200);
        $response->assertSee('Assigned Date: ' . now()->format('d M Y'), false);
        $response->assertSee('Assigned Date: ' . now()->subDay()->format('d M Y'), false);
        $response->assertSee('Bob Member', false);
        $response->assertSee('Charlie Member', false);
        $response->assertSee('Landing Page Design', false);
        $response->assertSee('Backend API Testing', false);
        $response->assertSee('Unassign', false);
    }

    public function test_tl_can_unassign_overdue_task(): void
    {
        // Overdue task assigned to member 1
        $overdueTask = Task::create([
            'title' => 'Overdue Deployment Fix',
            'description' => 'Fix Dockerfile config',
            'assigned_to' => $this->member1->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subHours(5), // overdue
            'status' => 'in-progress',
        ]);

        $this->assertTrue($overdueTask->isOverdue());

        $response = $this->actingAs($this->tl)
            ->post(route('tasks.unassign', $overdueTask));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $overdueTask->refresh();
        $this->assertNull($overdueTask->assigned_to);
        $this->assertEquals('pending', $overdueTask->status);

        // Verify task update log
        $this->assertDatabaseHas('task_updates', [
            'task_id' => $overdueTask->id,
        ]);

        // Verify activity log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'task_unassigned',
            'entity_id' => $overdueTask->id,
        ]);
    }

    public function test_tl_can_unassign_active_task_via_ajax(): void
    {
        $task = Task::create([
            'title' => 'Active UI Polish',
            'description' => 'Fix button margins',
            'assigned_to' => $this->member2->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(3),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->tl)
            ->postJson(route('tasks.unassign', $task));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'task_id' => $task->id,
        ]);

        $task->refresh();
        $this->assertNull($task->assigned_to);
    }

    public function test_cannot_unassign_submitted_or_completed_tasks(): void
    {
        $submittedTask = Task::create([
            'title' => 'Submitted Work',
            'assigned_to' => $this->member1->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(1),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->tl)
            ->postJson(route('tasks.unassign', $submittedTask));

        $response->assertStatus(422);
        $submittedTask->refresh();
        $this->assertEquals($this->member1->id, $submittedTask->assigned_to);
    }

    public function test_unauthorized_user_cannot_unassign_task(): void
    {
        $otherTL = User::create([
            'name' => 'Other TL',
            'username' => 'other_tl',
            'email' => 'other@example.com',
            'password' => Hash::make('password123'),
            'role' => 'tl',
            'must_change_password' => false,
        ]);

        $task = Task::create([
            'title' => 'Confidential Research',
            'assigned_to' => $this->member1->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subDay(),
            'status' => 'in-progress',
        ]);

        $response = $this->actingAs($otherTL)
            ->post(route('tasks.unassign', $task));

        $response->assertStatus(403);
    }

    public function test_tl_can_assign_unassigned_task_to_member(): void
    {
        // Unassigned task
        $task = Task::create([
            'title' => 'Orphaned Task Waiting for Delegation',
            'description' => 'Needs an owner',
            'assigned_to' => null,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(4),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->tl)
            ->putJson(route('tasks.assign_member', $task), [
                'assigned_to' => $this->member2->id,
                'deadline' => now()->addDays(5)->format('Y-m-d\TH:i'),
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'task_id' => $task->id,
        ]);

        $task->refresh();
        $this->assertEquals($this->member2->id, $task->assigned_to);
    }
}
