<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TaskSubmissionAndReassignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $tl;
    private User $member;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::create([
            'name' => 'Team Lead',
            'username' => 'tl_user',
            'email' => 'tl@example.com',
            'password' => Hash::make('password123'),
            'role' => 'tl',
            'must_change_password' => false,
        ]);

        $this->member = User::create([
            'name' => 'John Doe',
            'username' => 'johndoe_123',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'member',
            'created_by' => $this->tl->id,
            'must_change_password' => false,
        ]);

        $this->task = Task::create([
            'title' => 'Design Landing Page UI',
            'description' => 'Create Figma mockup and submit exports',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(2),
            'status' => 'in-progress',
        ]);
    }

    public function test_employee_can_submit_task_with_link_and_file_and_remarks(): void
    {
        $file = UploadedFile::fake()->create('mockup.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->member)
            ->put(route('tasks.submit', $this->task), [
                'submission_remarks' => 'Completed the initial draft of the landing page hero and features section.',
                'submission_link' => 'https://figma.com/file/sample-project-mockup',
                'submission_file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->task->refresh();
        $this->assertEquals('submitted', $this->task->status);
        $this->assertNotNull($this->task->submitted_at);
        $this->assertEquals('https://figma.com/file/sample-project-mockup', $this->task->submission_link);
        $this->assertEquals('Completed the initial draft of the landing page hero and features section.', $this->task->submission_remarks);
        $this->assertEquals('pdf', $this->task->submission_file_type);
        $this->assertNotNull($this->task->submission_file);
        $this->assertFileExists(public_path($this->task->submission_file));

        if (file_exists(public_path($this->task->submission_file))) {
            unlink(public_path($this->task->submission_file));
        }
    }

    public function test_tl_can_reassign_task_with_revision_notes_and_new_deadline(): void
    {
        $originalDeadline = $this->task->deadline;
        $newDeadline = now()->addDays(5)->format('Y-m-d H:i:s');

        $this->task->update([
            'status' => 'submitted',
            'submission_remarks' => 'Here is my work',
            'submission_link' => 'https://github.com/repo/pr/1',
        ]);

        $response = $this->actingAs($this->tl)
            ->put(route('tasks.reassign', $this->task), [
                'deadline' => $newDeadline,
                'revision_notes' => 'Hero section needs more contrast and mobile responsive adjustments are required.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->task->refresh();

        $this->assertEquals('in-progress', $this->task->status);
        $this->assertEquals(1, $this->task->reassignment_count);
        $this->assertTrue($this->task->isReassigned());
        $this->assertNotNull($this->task->previous_deadline);
        $this->assertEquals($originalDeadline->toDateTimeString(), $this->task->previous_deadline->toDateTimeString());
        $this->assertEquals(now()->addDays(5)->startOfMinute()->toDateTimeString(), $this->task->deadline->startOfMinute()->toDateTimeString());
        $this->assertEquals('Hero section needs more contrast and mobile responsive adjustments are required.', $this->task->revision_notes);

        $this->assertDatabaseHas('task_updates', [
            'task_id' => $this->task->id,
        ]);
    }

    public function test_member_cannot_reassign_task(): void
    {
        $response = $this->actingAs($this->member)
            ->put(route('tasks.reassign', $this->task), [
                'deadline' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'revision_notes' => 'Attempting self reassign',
            ]);

        $response->assertStatus(403);
    }

    public function test_deliverable_stays_local_before_approval_and_tl_complete_approves_task(): void
    {
        // Create fake local file in uploads
        $subDir = public_path('uploads/task_submissions');
        if (!file_exists($subDir)) {
            mkdir($subDir, 0755, true);
        }
        $testFileName = 'test_deliverable_' . time() . '.pdf';
        $fullPath = $subDir . DIRECTORY_SEPARATOR . $testFileName;
        file_put_contents($fullPath, 'fake-deliverable-pdf-content');

        $this->task->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submission_remarks' => 'Review my document please',
            'submission_file' => 'uploads/task_submissions/' . $testFileName,
            'submission_file_type' => 'pdf',
        ]);

        $this->assertFileExists($fullPath);

        // TL approves task
        $response = $this->actingAs($this->tl)
            ->put(route('tasks.complete', $this->task));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->task->refresh();
        $this->assertEquals('completed', $this->task->status);

        // Clean up test file if it exists
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    public function test_assigned_task_cannot_be_deleted_by_non_ceo(): void
    {
        // TL cannot delete
        $response = $this->actingAs($this->tl)
            ->delete(route('tasks.destroy', $this->task));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Only the CEO has permission to delete task history records.');

        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);

        // Member cannot delete
        $responseMember = $this->actingAs($this->member)
            ->delete(route('tasks.destroy', $this->task));

        $responseMember->assertRedirect();
        $responseMember->assertSessionHas('error', 'Only the CEO has permission to delete task history records.');

        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);
    }

    public function test_ceo_can_delete_task_individually(): void
    {
        $ceo = User::create([
            'name' => 'CEO User',
            'username' => 'ceo_user',
            'email' => 'ceo@example.com',
            'password' => Hash::make('password123'),
            'role' => 'ceo',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($ceo)
            ->delete(route('tasks.destroy', $this->task));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('tasks', ['id' => $this->task->id]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'task_deleted',
            'user_id' => $ceo->id,
        ]);
    }

    public function test_ceo_can_bulk_delete_tasks(): void
    {
        $ceo = User::create([
            'name' => 'CEO User',
            'username' => 'ceo_user',
            'email' => 'ceo@example.com',
            'password' => Hash::make('password123'),
            'role' => 'ceo',
            'must_change_password' => false,
        ]);

        $task2 = Task::create([
            'title' => 'Task Two',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(3),
            'status' => 'pending',
        ]);

        $task3 = Task::create([
            'title' => 'Task Three',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(4),
            'status' => 'pending',
        ]);

        // Bulk delete task 1 & 2 only
        $response = $this->actingAs($ceo)
            ->post(route('tasks.bulk_destroy'), [
                'task_ids' => [$this->task->id, $task2->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('tasks', ['id' => $this->task->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task2->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task3->id]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'tasks_bulk_deleted',
            'user_id' => $ceo->id,
        ]);
    }

    public function test_ceo_can_delete_all_tasks_at_once(): void
    {
        $ceo = User::create([
            'name' => 'CEO User',
            'username' => 'ceo_user',
            'email' => 'ceo@example.com',
            'password' => Hash::make('password123'),
            'role' => 'ceo',
            'must_change_password' => false,
        ]);

        $task2 = Task::create([
            'title' => 'Task Two',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(3),
            'status' => 'pending',
        ]);

        $allIds = Task::pluck('id')->toArray();

        $response = $this->actingAs($ceo)
            ->post(route('tasks.bulk_destroy'), [
                'task_ids' => $allIds,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(0, Task::count());
    }

    public function test_non_ceo_cannot_bulk_delete_tasks(): void
    {
        $response = $this->actingAs($this->tl)
            ->post(route('tasks.bulk_destroy'), [
                'task_ids' => [$this->task->id],
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);
    }

    public function test_tasks_sync_endpoint_returns_live_state_and_counts_for_member(): void
    {
        $response = $this->actingAs($this->member)
            ->get(route('tasks.sync'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'role',
            'counts' => ['total', 'pending', 'submitted', 'completed', 'overdue'],
            'tasks' => [
                '*' => ['id', 'title', 'status', 'is_overdue', 'assignee_name']
            ]
        ]);
        $response->assertJson([
            'success' => true,
            'counts' => [
                'total' => 1,
                'pending' => 1,
                'submitted' => 0,
                'completed' => 0,
                'overdue' => 0,
            ]
        ]);
    }

    public function test_tl_dashboard_displays_overdue_tasks_section_and_counter(): void
    {
        // Create an overdue task
        Task::create([
            'title' => 'Critical Overdue Deliverable',
            'description' => 'Should have been submitted yesterday',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->subDay(),
            'status' => 'in-progress',
        ]);

        $response = $this->actingAs($this->tl)
            ->get(route('tl.dashboard'));

        $response->assertOk();
        $response->assertSee('Overdue Deliverables');
        $response->assertSee('Critical Overdue Deliverable');
        $response->assertSee('Overdue Tasks');
    }
}

