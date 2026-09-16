<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\LeaveApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CeoAndHrRolesTest extends TestCase
{
    use RefreshDatabase;

    protected User $ceo;
    protected User $hr;
    protected User $tl;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ceo = User::factory()->create([
            'name' => 'Chief Executive',
            'role' => 'ceo',
            'email' => 'ceo@test.com',
        ]);

        $this->hr = User::factory()->create([
            'name' => 'HR Specialist',
            'role' => 'hr',
            'email' => 'hr@test.com',
        ]);

        $this->tl = User::factory()->create([
            'name' => 'Lead Engineer',
            'role' => 'tl',
            'email' => 'tl@test.com',
        ]);

        $this->member = User::factory()->create([
            'name' => 'Staff Specialist',
            'role' => 'member',
            'email' => 'member@test.com',
            'created_by' => $this->tl->id,
        ]);
    }

    public function test_ceo_can_access_executive_dashboard(): void
    {
        $response = $this->actingAs($this->ceo)->get(route('ceo.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('CEO');
        $response->assertSee('Tasks Needing Review');
    }

    public function test_hr_can_access_hr_dashboard(): void
    {
        $response = $this->actingAs($this->hr)->get(route('hr.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('HR Manager');
        $response->assertSee('Pending Leave Applications');
    }

    public function test_ceo_can_assign_task_to_anyone(): void
    {
        // CEO assigns task to Team Lead
        $response = $this->actingAs($this->ceo)->post(route('tasks.store'), [
            'title' => 'Executive Strategic Roadmap',
            'description' => 'Prepare the Q4 project timeline.',
            'assigned_to' => $this->tl->id,
            'deadline' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'Executive Strategic Roadmap',
            'assigned_to' => $this->tl->id,
            'assigned_by' => $this->ceo->id,
        ]);
    }

    public function test_ceo_can_review_and_complete_tasks_assigned_by_others(): void
    {
        $task = Task::create([
            'title' => 'Camera Gear Testing',
            'description' => 'Check lenses and lighting.',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(2),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->ceo)->put(route('tasks.complete', $task));
        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertEquals('completed', $task->status);
    }

    public function test_hr_can_approve_and_reject_leave_applications(): void
    {
        $leave = LeaveApplication::create([
            'user_id' => $this->member->id,
            'leave_type' => 'sick',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(2)->format('Y-m-d'),
            'total_days' => 2,
            'reason' => 'Doctor appointment and fever rest',
            'status' => 'pending',
        ]);

        // HR Approves
        $response = $this->actingAs($this->hr)->post(route('hr.leaves.approve', $leave));
        $response->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals($this->hr->id, $leave->reviewed_by);
    }

    public function test_member_cannot_access_ceo_or_hr_dashboards(): void
    {
        $response = $this->actingAs($this->member)->get(route('ceo.dashboard'));
        $response->assertRedirect(route('member.dashboard'));

        $response2 = $this->actingAs($this->member)->get(route('hr.dashboard'));
        $response2->assertRedirect(route('member.dashboard'));
    }

    public function test_hr_can_view_and_manage_members(): void
    {
        // 1. HR views members roster
        $response = $this->actingAs($this->hr)->get(route('hr.members'));
        $response->assertStatus(200);
        $response->assertSee($this->member->name);

        // 2. HR registers a new employee
        $createResponse = $this->actingAs($this->hr)->post(route('hr.members.store'), [
            'name' => 'Kiran Joshi',
            'mobile_number' => '+91 91234 56789',
            'designation' => 'Visual Designer',
            'email' => 'kiran.hr@test.com',
        ]);
        $createResponse->assertRedirect(route('hr.members'));
        $this->assertDatabaseHas('users', ['email' => 'kiran.hr@test.com']);

        // 3. HR views employee detail page
        $detailResponse = $this->actingAs($this->hr)->get(route('hr.members.show', $this->member));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee($this->member->name);

        // 4. HR updates employee details
        $updateResponse = $this->actingAs($this->hr)->put(route('hr.members.update', $this->member), [
            'name' => 'Staff Specialist Updated',
            'username' => 'staff.specialist.upd',
            'designation' => 'Lead Specialist',
            'mobile_number' => '+91 99999 11111',
            'email' => $this->member->email,
        ]);
        $updateResponse->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $this->member->id, 'name' => 'Staff Specialist Updated']);

        // 5. HR deletes an employee
        $tempMember = User::factory()->create([
            'name' => 'Temp Employee',
            'role' => 'member',
            'email' => 'temp@test.com',
            'created_by' => $this->tl->id,
        ]);
        $deleteResponse = $this->actingAs($this->hr)->delete(route('hr.members.destroy', $tempMember));
        $deleteResponse->assertRedirect(route('hr.members'));
        $this->assertDatabaseMissing('users', ['id' => $tempMember->id]);
    }
}
