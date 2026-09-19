<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LeaveApplication;
use App\Models\LeaveQuota;
use App\Models\LeaveSetting;
use App\Models\Attendance;
use App\Mail\LeaveNotificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class LeaveManagementSystemTest extends TestCase
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
            'name' => 'CEO Boss',
            'role' => 'ceo',
            'email' => 'ceo@ecofone.test',
        ]);

        $this->hr = User::factory()->create([
            'name' => 'Pooja HR',
            'role' => 'hr',
            'email' => 'hr@ecofone.test',
        ]);

        $this->tl = User::factory()->create([
            'name' => 'Sumit TL',
            'role' => 'tl',
            'email' => 'tl@ecofone.test',
        ]);

        $this->member = User::factory()->create([
            'name' => 'Ritik Member',
            'role' => 'member',
            'created_by' => $this->tl->id,
            'email' => 'ritik@ecofone.test',
        ]);

        // Default quotas for testing
        LeaveQuota::create([
            'user_id' => $this->member->id,
            'year' => (int) date('Y'),
            'casual_quota' => 12,
            'sick_quota' => 8,
            'emergency_quota' => 5,
            'privilege_quota' => 15,
        ]);
    }

    public function test_employee_can_apply_when_leave_toggle_is_on(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->member)->post(route('leaves.store'), [
            'leave_type' => 'casual',
            'start_date' => now()->addDays(2)->format('Y-m-d'),
            'end_date'   => now()->addDays(3)->format('Y-m-d'),
            'reason'     => 'Family wedding attendance',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leave_applications', [
            'user_id'    => $this->member->id,
            'leave_type' => 'casual',
            'total_days' => 2,
            'status'     => 'pending',
        ]);

        Mail::assertSent(LeaveNotificationMail::class);
    }

    public function test_employee_cannot_apply_when_leave_toggle_is_off(): void
    {
        Mail::fake();

        // Turn OFF leave for this TL's team
        LeaveSetting::create([
            'tl_id' => $this->tl->id,
            'is_leave_enabled' => false,
            'toggled_by' => $this->tl->id,
        ]);

        $response = $this->actingAs($this->member)->post(route('leaves.store'), [
            'leave_type' => 'casual',
            'start_date' => now()->addDays(2)->format('Y-m-d'),
            'end_date'   => now()->addDays(3)->format('Y-m-d'),
            'reason'     => 'Family trip request',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, LeaveApplication::count());
    }

    public function test_tl_can_toggle_leave_on_and_off(): void
    {
        $response = $this->actingAs($this->tl)->post(route('leaves.toggle'), [
            'tl_id' => $this->tl->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_settings', [
            'tl_id' => $this->tl->id,
            'is_leave_enabled' => false,
        ]);

        // Toggle back ON
        $this->actingAs($this->tl)->post(route('leaves.toggle'), [
            'tl_id' => $this->tl->id,
        ]);

        $this->assertDatabaseHas('leave_settings', [
            'tl_id' => $this->tl->id,
            'is_leave_enabled' => true,
        ]);
    }

    public function test_tl_can_view_leaves_but_cannot_approve_or_reject(): void
    {
        $leave = LeaveApplication::create([
            'user_id'    => $this->member->id,
            'leave_type' => 'sick',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date'   => now()->addDays(1)->format('Y-m-d'),
            'total_days' => 1,
            'reason'     => 'Viral fever recovery',
            'status'     => 'pending',
        ]);

        // View works
        $response = $this->actingAs($this->tl)->get(route('leaves.index'));
        $response->assertOk();
        $response->assertSee('Viral fever recovery');

        // Approve blocked for TL
        $approveResp = $this->actingAs($this->tl)->post(route('leaves.approve', $leave));
        $approveResp->assertSessionHas('error');
        $this->assertEquals('pending', $leave->fresh()->status);

        // Reject blocked for TL
        $rejectResp = $this->actingAs($this->tl)->post(route('leaves.reject', $leave), [
            'review_notes' => 'Not allowed by TL',
        ]);
        $rejectResp->assertSessionHas('error');
        $this->assertEquals('pending', $leave->fresh()->status);
    }

    public function test_hr_can_approve_leave_when_balance_available(): void
    {
        Mail::fake();

        $leave = LeaveApplication::create([
            'user_id'    => $this->member->id,
            'leave_type' => 'casual',
            'start_date' => now()->addDays(2)->format('Y-m-d'),
            'end_date'   => now()->addDays(3)->format('Y-m-d'),
            'total_days' => 2,
            'reason'     => 'Personal appointment',
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->hr)->post(route('leaves.approve', $leave), [
            'approved_leave_type' => 'casual',
            'review_notes'        => 'Approved by HR. Take care.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('approved', $leave->fresh()->status);
        $this->assertEquals($this->hr->id, $leave->fresh()->reviewed_by);
        $this->assertFalse($leave->fresh()->is_ceo_granted);

        // Attendance synchronized
        $attendance = Attendance::where('user_id', $this->member->id)
            ->whereDate('date', now()->addDays(2))
            ->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('on_leave', $attendance->status);

        Mail::assertSent(LeaveNotificationMail::class);
    }

    public function test_hr_cannot_approve_leave_when_balance_insufficient(): void
    {
        // Set member's quota to only 1 day total
        LeaveQuota::where('user_id', $this->member->id)->update([
            'casual_quota' => 1,
            'sick_quota' => 0,
            'emergency_quota' => 0,
            'privilege_quota' => 0,
        ]);

        // Request 5 days
        $leave = LeaveApplication::create([
            'user_id'    => $this->member->id,
            'leave_type' => 'casual',
            'start_date' => now()->addDays(2)->format('Y-m-d'),
            'end_date'   => now()->addDays(6)->format('Y-m-d'),
            'total_days' => 5,
            'reason'     => 'Long holiday without balance',
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->hr)->post(route('leaves.approve', $leave));

        // HR is blocked
        $response->assertSessionHas('error');
        $this->assertEquals('pending', $leave->fresh()->status);
    }

    public function test_ceo_can_grant_leave_without_balance_resulting_in_negative_balance(): void
    {
        Mail::fake();

        // Member has 0 quota
        LeaveQuota::where('user_id', $this->member->id)->update([
            'casual_quota' => 0,
            'sick_quota' => 0,
            'emergency_quota' => 0,
            'privilege_quota' => 0,
        ]);

        $leave = LeaveApplication::create([
            'user_id'    => $this->member->id,
            'leave_type' => 'emergency',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date'   => now()->addDays(2)->format('Y-m-d'),
            'total_days' => 2,
            'reason'     => 'Severe family emergency without balance',
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->ceo)->post(route('leaves.approve', $leave), [
            'grant_without_balance' => 1,
            'review_notes'          => 'CEO Executive Exception Granted.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('approved', $leave->fresh()->status);
        $this->assertTrue($leave->fresh()->is_ceo_granted);

        // Check user leave summary has negative balance
        $summary = $this->member->getLeaveSummary();
        $this->assertTrue($summary['is_negative']);
        $this->assertEquals(-2, $summary['total_remaining']);

        // Attendance synchronized
        $attendance = Attendance::where('user_id', $this->member->id)
            ->whereDate('date', now()->addDays(1))
            ->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('on_leave', $attendance->status);
    }

    public function test_hr_can_update_leave_quotas_for_employee(): void
    {
        $response = $this->actingAs($this->hr)->post(route('leaves.quotas.update'), [
            'user_id'         => $this->member->id,
            'year'            => (int) date('Y'),
            'casual_quota'    => 15,
            'sick_quota'      => 10,
            'emergency_quota' => 7,
            'privilege_quota' => 20,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leave_quotas', [
            'user_id'         => $this->member->id,
            'casual_quota'    => 15,
            'sick_quota'      => 10,
            'emergency_quota' => 7,
            'privilege_quota' => 20,
        ]);
    }
}
