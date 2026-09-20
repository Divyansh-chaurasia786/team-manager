<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    private User $tl;
    private User $hr;
    private User $member;

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

        $this->hr = User::create([
            'name' => 'Helen HR',
            'username' => 'helen_hr',
            'email' => 'helen.hr@ecofone.org',
            'password' => Hash::make('Secret123!'),
            'role' => 'hr',
            'designation' => 'HR Manager',
            'must_change_password' => false,
        ]);

        $this->member = User::create([
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

    public function test_no_one_can_mark_future_attendance(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        // 1. TL trying to mark future attendance -> BLOCKED
        $responseTL = $this->actingAs($this->tl)->post(route('attendance.mark'), [
            'user_id' => $this->member->id,
            'date'    => $tomorrow,
            'status'  => 'present',
        ]);
        $responseTL->assertSessionHas('error');
        $this->assertNull(
            Attendance::where('user_id', $this->member->id)->whereDate('date', $tomorrow)->first()
        );

        // 2. HR trying to mark future attendance -> BLOCKED
        $responseHR = $this->actingAs($this->hr)->post(route('attendance.mark'), [
            'user_id' => $this->member->id,
            'date'    => $tomorrow,
            'status'  => 'present',
        ]);
        $responseHR->assertSessionHas('error');
        $this->assertNull(
            Attendance::where('user_id', $this->member->id)->whereDate('date', $tomorrow)->first()
        );

        // 3. Bulk mark on future date -> BLOCKED
        $responseBulk = $this->actingAs($this->tl)->post(route('attendance.bulk'), [
            'date' => $tomorrow,
        ]);
        $responseBulk->assertSessionHas('error');
    }

    public function test_tl_can_mark_same_day_attendance(): void
    {
        $today = now()->format('Y-m-d');

        $response = $this->actingAs($this->tl)->post(route('attendance.mark'), [
            'user_id' => $this->member->id,
            'date'    => $today,
            'status'  => 'present',
        ]);

        $response->assertSessionHas('success');
        $att = Attendance::where('user_id', $this->member->id)->whereDate('date', $today)->first();
        $this->assertNotNull($att);
        $this->assertEquals('present', $att->status);
        $this->assertEquals($this->tl->id, $att->marked_by);
    }

    public function test_tl_cannot_mark_past_attendance(): void
    {
        $yesterday = now()->subDay()->format('Y-m-d');

        $response = $this->actingAs($this->tl)->post(route('attendance.mark'), [
            'user_id' => $this->member->id,
            'date'    => $yesterday,
            'status'  => 'present',
        ]);

        $response->assertSessionHas('error');
        $this->assertNull(
            Attendance::where('user_id', $this->member->id)->whereDate('date', $yesterday)->first()
        );

        // Bulk mark on past date by TL -> BLOCKED
        $responseBulk = $this->actingAs($this->tl)->post(route('attendance.bulk'), [
            'date' => $yesterday,
        ]);
        $responseBulk->assertSessionHas('error');
    }

    public function test_tl_can_view_past_attendance_in_read_only_mode(): void
    {
        $yesterday = now()->subDay()->format('Y-m-d');

        // Pre-existing attendance created on past date
        Attendance::create([
            'user_id'   => $this->member->id,
            'date'      => $yesterday,
            'status'    => 'present',
            'marked_by' => $this->tl->id,
        ]);

        $response = $this->actingAs($this->tl)->get(route('attendance.index', ['date' => $yesterday]));

        $response->assertOk();
        $response->assertSee('Past Attendance Record (Read-Only)');
        $response->assertSee('Read-Only • Auditable by HR');
        $response->assertDontSee('Audit Bulk Present');
        $response->assertDontSee(route('attendance.bulk'));
    }

    public function test_hr_can_audit_and_mark_past_attendance(): void
    {
        $pastDate = now()->subDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->hr)->post(route('attendance.mark'), [
            'user_id' => $this->member->id,
            'date'    => $pastDate,
            'status'  => 'half_day',
            'notes'   => 'HR rectified attendance after doctor note verification',
        ]);

        $response->assertSessionHas('success');
        $att = Attendance::where('user_id', $this->member->id)->whereDate('date', $pastDate)->first();
        $this->assertNotNull($att);
        $this->assertEquals('half_day', $att->status);
        $this->assertEquals($this->hr->id, $att->marked_by);
        $this->assertEquals('HR rectified attendance after doctor note verification', $att->notes);

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'attendance_audited',
            'entity_type' => 'Attendance',
            'user_id'     => $this->hr->id,
        ]);
    }

    public function test_hr_can_view_attendance_audit_mode_on_past_date(): void
    {
        $pastDate = now()->subDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->hr)->get(route('attendance.index', ['date' => $pastDate]));

        $response->assertOk();
        $response->assertSee('HR Attendance Audit Mode');
        $response->assertSee('Audit Bulk Present');
    }

    public function test_regular_member_cannot_mark_attendance(): void
    {
        $today = now()->format('Y-m-d');

        $response = $this->actingAs($this->member)->post(route('attendance.mark'), [
            'user_id' => $this->member->id,
            'date'    => $today,
            'status'  => 'present',
        ]);

        $response->assertStatus(403);
    }
}
