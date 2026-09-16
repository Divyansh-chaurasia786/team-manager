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

    public function test_assigned_task_cannot_be_deleted(): void
    {
        $response = $this->actingAs($this->tl)
            ->delete(route('tasks.destroy', $this->task));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Assigned tasks are permanent records and cannot be deleted.');

        $this->assertDatabaseHas('tasks', [
            'id' => $this->task->id,
            'title' => 'Design Landing Page UI',
        ]);
    }
}

