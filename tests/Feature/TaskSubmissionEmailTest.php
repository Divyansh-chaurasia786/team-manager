<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Mail\TaskSubmittedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskSubmissionEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_submission_delivers_email_to_assigned_tl_of_employee(): void
    {
        Mail::fake();
        Storage::fake('local');

        // Create assigned TL
        $tl = User::create([
            'name'                  => 'Vikram TeamLead',
            'username'              => 'vikram.tl',
            'email'                 => 'vikram.tl@ecofone.com',
            'password'              => Hash::make('password123'),
            'role'                  => 'tl',
            'must_change_password'  => false,
        ]);

        // Create Employee assigned to Vikram TL
        $employee = User::create([
            'name'                  => 'Rohan Employee',
            'username'              => 'rohan.emp',
            'email'                 => 'rohan.emp@ecofone.com',
            'password'              => Hash::make('password123'),
            'role'                  => 'member',
            'created_by'            => $tl->id,
            'must_change_password'  => false,
        ]);

        // Create a task assigned to Rohan
        $task = Task::create([
            'title'        => 'Build Landing Page Video',
            'description'  => 'Create 60s video showcasing new portal',
            'assigned_to'  => $employee->id,
            'assigned_by'  => $tl->id,
            'deadline'     => now()->addDays(2),
            'status'       => 'in-progress',
        ]);

        $file = UploadedFile::fake()->create('final_cut.mp4', 5000, 'video/mp4');

        $response = $this->actingAs($employee)->put(route('tasks.submit', $task), [
            'submission_remarks' => 'Completed the full edit with sound effects',
            'submission_link'    => 'https://drive.google.com/file/d/test-video-link',
            'submission_file'    => $file,
        ]);

        $response->assertSessionHas('success');

        // Assert that the email was sent specifically to Vikram TL
        Mail::assertSent(TaskSubmittedMail::class, function ($mail) use ($tl, $task, $employee) {
            return $mail->hasTo($tl->email)
                && $mail->task->id === $task->id
                && $mail->tl->id === $tl->id
                && str_contains($mail->envelope()->subject, 'Build Landing Page Video')
                && str_contains($mail->envelope()->subject, 'Rohan Employee');
        });

        // Ensure email was not sent to the employee themselves
        Mail::assertNotSent(TaskSubmittedMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email);
        });
    }

    public function test_task_submission_email_renders_all_deliverables_and_remarks(): void
    {
        Mail::fake();

        $tl = User::create([
            'name'                  => 'Priya Lead',
            'username'              => 'priya.tl',
            'email'                 => 'priya.lead@ecofone.com',
            'password'              => Hash::make('password123'),
            'role'                  => 'tl',
            'must_change_password'  => false,
        ]);

        $employee = User::create([
            'name'                  => 'Sunil Member',
            'username'              => 'sunil.member',
            'email'                 => 'sunil.member@ecofone.com',
            'password'              => Hash::make('password123'),
            'role'                  => 'member',
            'created_by'            => $tl->id,
            'must_change_password'  => false,
        ]);

        $task = Task::create([
            'title'              => 'Design Marketing Banners',
            'description'        => 'Prepare 3 banner variants for Diwali campaign',
            'assigned_to'        => $employee->id,
            'assigned_by'        => $tl->id,
            'deadline'           => now()->addDay(),
            'status'             => 'submitted',
            'submitted_at'       => now(),
            'submission_remarks' => 'All three banner formats attached with Figma source link',
            'submission_link'    => 'https://figma.com/design/diwali-campaign-banners',
            'submission_file'    => 'task_submissions/banners.zip',
            'submission_file_type' => 'document',
        ]);

        $mailable = new TaskSubmittedMail($task, $tl);
        $rendered = $mailable->render();

        $this->assertStringContainsString('Priya Lead', $rendered);
        $this->assertStringContainsString('Sunil Member', $rendered);
        $this->assertStringContainsString('Design Marketing Banners', $rendered);
        $this->assertStringContainsString('All three banner formats attached with Figma source link', $rendered);
        $this->assertStringContainsString('https://figma.com/design/diwali-campaign-banners', $rendered);
        $this->assertStringContainsString('banners.zip', $rendered);
        $this->assertStringContainsString('Submitted On Time', $rendered);
    }
}
