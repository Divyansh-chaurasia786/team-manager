<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\ActivityLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoTaskSeeder extends Seeder
{
    public function run(): void
    {
        $tl = User::where('role', 'tl')->first();
        $member = User::where('role', 'member')->first();

        if (!$tl || !$member) {
            $this->command->error('Ensure TL and at least one member exist.');
            return;
        }

        $tasks = [
            [
                'title' => 'Design EcoFone Landing Page & UI Components',
                'description' => 'Create high-converting landing page layouts and interactive UI components in Figma.',
                'assigned_to' => $member->id,
                'assigned_by' => $tl->id,
                'deadline' => now()->addDays(2),
                'status' => 'completed',
                'submitted_at' => now()->subDay(),
            ],
            [
                'title' => 'Build REST API for Attendance Verification',
                'description' => 'Implement endpoints for check-in validation, role verification, and attendance logs.',
                'assigned_to' => $member->id,
                'assigned_by' => $tl->id,
                'deadline' => now()->addDays(4),
                'status' => 'in-progress',
                'submitted_at' => null,
            ],
            [
                'title' => 'Google Drive Cloud Storage Sync Integration',
                'description' => 'Test file upload chunks, mime-type validation, and download streaming for PDF and video assets.',
                'assigned_to' => $member->id,
                'assigned_by' => $tl->id,
                'deadline' => now()->addDays(1),
                'status' => 'submitted',
                'submitted_at' => now()->subHours(3),
            ],
            [
                'title' => 'Q3 Sprint Review & Documentation Audit',
                'description' => 'Prepare weekly planning review deck and audit deliverables with stakeholders.',
                'assigned_to' => $member->id,
                'assigned_by' => $tl->id,
                'deadline' => now()->addDays(5),
                'status' => 'pending',
                'submitted_at' => null,
            ],
        ];

        foreach ($tasks as $data) {
            $task = Task::create($data);

            if (in_array($task->status, ['in-progress', 'submitted', 'completed'])) {
                TaskUpdate::create([
                    'task_id' => $task->id,
                    'message' => 'Progress checkpoint verified with Team Lead.',
                ]);
            }

            ActivityLog::log(
                action: 'task_assigned',
                description: "Assigned demo deliverable '{$task->title}' to {$member->name}",
                entityType: 'Task',
                entityId: $task->id,
                userId: $tl->id
            );
        }

        $this->command->info('Created 4 demo tasks for ' . $member->name);
    }
}
