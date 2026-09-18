<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamLeadHierarchyAndAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_tl_creating_member_automatically_assigns_created_tl(): void
    {
        $tl = User::create([
            'name'     => 'Team Lead One',
            'username' => 'tl.one',
            'email'    => 'tl1@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $response = $this->actingAs($tl)->post(route('tl.members.store'), [
            'name'          => 'Rahul Sharma',
            'mobile_number' => '+91 98765 11111',
            'designation'   => 'Android Developer',
            'email'         => 'rahul@ecofone.com',
        ]);

        $response->assertRedirect(route('tl.members'));
        $newMember = User::where('email', 'rahul@ecofone.com')->first();
        $this->assertNotNull($newMember);
        $this->assertEquals('member', $newMember->role);
        $this->assertEquals($tl->id, $newMember->created_by);
        $this->assertTrue($newMember->must_change_password);
        $this->assertNotNull($newMember->otp_expires_at);
        $this->assertTrue($newMember->otp_expires_at->isFuture());
    }

    public function test_hr_can_create_a_new_team_lead(): void
    {
        $hr = User::create([
            'name'     => 'HR Manager',
            'username' => 'hr.manager',
            'email'    => 'hr@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'hr',
        ]);

        $response = $this->actingAs($hr)->post(route('hr.members.store'), [
            'name'          => 'Vikram Rathore',
            'mobile_number' => '+91 98765 22222',
            'designation'   => 'Creative Lead',
            'email'         => 'vikram.tl@ecofone.com',
            'role'          => 'tl',
        ]);

        $response->assertRedirect(route('hr.members'));
        $newTl = User::where('email', 'vikram.tl@ecofone.com')->first();
        $this->assertNotNull($newTl);
        $this->assertEquals('tl', $newTl->role);
        $this->assertTrue($newTl->isTL());
    }

    public function test_hr_creating_member_requires_and_assigns_reporting_tl(): void
    {
        $hr = User::create([
            'name'     => 'HR Manager',
            'username' => 'hr.manager',
            'email'    => 'hr@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'hr',
        ]);

        $tl1 = User::create([
            'name'     => 'Sumit Lead',
            'username' => 'sumit.lead',
            'email'    => 'sumit@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $tl2 = User::create([
            'name'     => 'Anita Lead',
            'username' => 'anita.lead',
            'email'    => 'anita@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        // Creating member assigning specifically to TL2
        $res = $this->actingAs($hr)->post(route('hr.members.store'), [
            'name'          => 'Priya Patel',
            'mobile_number' => '+91 98765 33333',
            'designation'   => 'Content Creator',
            'email'         => 'priya@ecofone.com',
            'role'          => 'member',
            'team_lead_id'  => $tl2->id,
        ]);
        $res->assertRedirect(route('hr.members'));

        $member = User::where('email', 'priya@ecofone.com')->first();
        $this->assertNotNull($member);
        $this->assertEquals('member', $member->role);
        $this->assertEquals($tl2->id, $member->created_by);
        $this->assertEquals('Anita Lead', $member->creator->name);
    }

    public function test_hr_and_ceo_can_reassign_member_to_different_tl(): void
    {
        $hr = User::create([
            'name'     => 'HR Lead',
            'username' => 'hr.lead',
            'email'    => 'hr_lead@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'hr',
        ]);

        $tl1 = User::create([
            'name'     => 'TL 1',
            'username' => 'tl1',
            'email'    => 'tl1@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $tl2 = User::create([
            'name'     => 'TL 2',
            'username' => 'tl2',
            'email'    => 'tl2@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $member = User::create([
            'name'        => 'Aman Verma',
            'username'    => 'aman.v',
            'email'       => 'aman@ecofone.com',
            'password'    => Hash::make('secret'),
            'role'        => 'member',
            'created_by'  => $tl1->id,
            'designation' => 'Editor',
        ]);

        // HR updates member's assigned TL from TL1 to TL2
        $response = $this->actingAs($hr)->put(route('hr.members.update', $member), [
            'name'          => 'Aman Verma',
            'username'      => 'aman.v',
            'email'         => 'aman@ecofone.com',
            'mobile_number' => '+91 99887 76655',
            'designation'   => 'Senior Editor',
            'team_lead_id'  => $tl2->id,
        ]);

        $response->assertSessionHas('success');
        $member->refresh();
        $this->assertEquals($tl2->id, $member->created_by);
    }

    public function test_tl_can_only_assign_tasks_to_their_own_assigned_members(): void
    {
        $tl1 = User::create([
            'name'     => 'TL 1',
            'username' => 'tl1.task',
            'email'    => 'tl1_task@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $tl2 = User::create([
            'name'     => 'TL 2',
            'username' => 'tl2.task',
            'email'    => 'tl2_task@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $memberOfTl1 = User::create([
            'name'       => 'Member Of TL1',
            'username'   => 'm.tl1',
            'email'      => 'mtl1@ecofone.com',
            'password'   => Hash::make('password123'),
            'role'       => 'member',
            'created_by' => $tl1->id,
        ]);

        $memberOfTl2 = User::create([
            'name'       => 'Member Of TL2',
            'username'   => 'm.tl2',
            'email'      => 'mtl2@ecofone.com',
            'password'   => Hash::make('password123'),
            'role'       => 'member',
            'created_by' => $tl2->id,
        ]);

        // 1. TL1 tries to assign task to Member of TL2 -> Forbidden (403)
        $forbiddenResponse = $this->actingAs($tl1)->post(route('tasks.store'), [
            'title'       => 'Cross-team Task',
            'description' => 'Should fail',
            'assigned_to' => $memberOfTl2->id,
            'deadline'    => now()->addDays(2)->toDateTimeString(),
        ]);
        $forbiddenResponse->assertStatus(403);

        // 2. TL1 assigns task to their own member -> Succeeds
        $successResponse = $this->actingAs($tl1)->post(route('tasks.store'), [
            'title'       => 'Proper Team Task',
            'description' => 'Should succeed',
            'assigned_to' => $memberOfTl1->id,
            'deadline'    => now()->addDays(2)->toDateTimeString(),
        ]);
        $successResponse->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title'       => 'Proper Team Task',
            'assigned_to' => $memberOfTl1->id,
            'assigned_by' => $tl1->id,
        ]);
    }

    public function test_ceo_can_assign_tasks_to_anyone_across_all_roles(): void
    {
        $ceo = User::create([
            'name'     => 'Company CEO',
            'username' => 'ceo.master',
            'email'    => 'ceo_master@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'ceo',
        ]);

        $tl = User::create([
            'name'     => 'Ops TL',
            'username' => 'ops.tl',
            'email'    => 'ops_tl@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $hr = User::create([
            'name'     => 'Chief HR',
            'username' => 'chief.hr',
            'email'    => 'chief_hr@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'hr',
        ]);

        $member = User::create([
            'name'       => 'Staff Dev',
            'username'   => 'staff.dev',
            'email'      => 'staff_dev@ecofone.com',
            'password'   => Hash::make('password123'),
            'role'       => 'member',
            'created_by' => $tl->id,
        ]);

        // CEO assigns to TL
        $resTl = $this->actingAs($ceo)->post(route('tasks.store'), [
            'title'       => 'Executive Directive to TL',
            'description' => 'Prepare quarterly report',
            'assigned_to' => $tl->id,
            'deadline'    => now()->addDays(3)->toDateTimeString(),
        ]);
        $resTl->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', ['title' => 'Executive Directive to TL', 'assigned_to' => $tl->id]);

        // CEO assigns to HR
        $resHr = $this->actingAs($ceo)->post(route('tasks.store'), [
            'title'       => 'Hiring Plan Request',
            'description' => 'Scale engineering team',
            'assigned_to' => $hr->id,
            'deadline'    => now()->addDays(5)->toDateTimeString(),
        ]);
        $resHr->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', ['title' => 'Hiring Plan Request', 'assigned_to' => $hr->id]);

        // CEO assigns directly to member of TL
        $resMember = $this->actingAs($ceo)->post(route('tasks.store'), [
            'title'       => 'High-Priority Client Bug',
            'description' => 'Hotfix required ASAP',
            'assigned_to' => $member->id,
            'deadline'    => now()->addHours(12)->toDateTimeString(),
        ]);
        $resMember->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', ['title' => 'High-Priority Client Bug', 'assigned_to' => $member->id]);
    }

    public function test_task_assignment_instantly_dispatches_email_to_assignee(): void
    {
        $tl = User::create([
            'name'     => 'Direct TL',
            'username' => 'direct.tl',
            'email'    => 'direct_tl@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $member = User::create([
            'name'       => 'Assignee Dev',
            'username'   => 'assignee.dev',
            'email'      => 'assignee@ecofone.com',
            'password'   => Hash::make('password123'),
            'role'       => 'member',
            'created_by' => $tl->id,
        ]);

        $deadline = now()->addHours(6);

        $res = $this->actingAs($tl)->post(route('tasks.store'), [
            'title'       => 'Production Deploy Sprint',
            'description' => 'Deploy the latest release to production',
            'assigned_to' => $member->id,
            'deadline'    => $deadline->toDateTimeString(),
        ]);

        $res->assertRedirect(route('tasks.index'));

        Mail::assertSent(\App\Mail\TaskAssignedMail::class, function ($mail) use ($member) {
            return $mail->hasTo('assignee@ecofone.com') &&
                   $mail->task->title === 'Production Deploy Sprint' &&
                   $mail->isReassignment === false;
        });
    }

    public function test_task_reassignment_dispatches_revision_email(): void
    {
        $tl = User::create([
            'name'     => 'Direct TL 2',
            'username' => 'direct.tl2',
            'email'    => 'direct_tl2@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $member = User::create([
            'name'       => 'Assignee Dev 2',
            'username'   => 'assignee.dev2',
            'email'      => 'assignee2@ecofone.com',
            'password'   => Hash::make('password123'),
            'role'       => 'member',
            'created_by' => $tl->id,
        ]);

        $task = Task::create([
            'title'       => 'Feature Integration',
            'description' => 'Integrate API endpoints',
            'assigned_to' => $member->id,
            'assigned_by' => $tl->id,
            'deadline'    => now()->addHours(2),
            'status'      => 'submitted',
        ]);

        $newDeadline = now()->addHours(8);

        $res = $this->actingAs($tl)->put(route('tasks.reassign', $task), [
            'deadline'       => $newDeadline->toDateTimeString(),
            'revision_notes' => 'Please resolve edge case handling on auth timeout',
        ]);

        $res->assertSessionHas('success');

        Mail::assertSent(\App\Mail\TaskAssignedMail::class, function ($mail) use ($member) {
            return $mail->hasTo('assignee2@ecofone.com') &&
                   $mail->task->title === 'Feature Integration' &&
                   $mail->isReassignment === true;
        });
    }
}
