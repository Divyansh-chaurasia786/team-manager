<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WeeklyPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_tl_can_view_weekly_plans_page(): void
    {
        $tl = User::factory()->create(['role' => 'tl', 'username' => 'tl.test']);

        $response = $this->actingAs($tl)->get(route('plans.index'));

        $response->assertStatus(200);
        $response->assertSee('Weekly Planning & Schedules', false);
        $response->assertSee('Create Weekly Plan', false);
    }

    public function test_tl_can_create_and_share_weekly_plan_with_member(): void
    {
        $tl = User::factory()->create(['role' => 'tl', 'username' => 'tl.test2']);
        $member = User::factory()->create([
            'role' => 'member',
            'created_by' => $tl->id,
            'username' => 'member.test',
            'name' => 'John Employee'
        ]);

        $postData = [
            'user_id' => $member->id,
            'title' => 'Sprint 40 Architecture & QA',
            'week_start_date' => now()->startOfWeek()->format('Y-m-d'),
            'week_end_date' => now()->endOfWeek()->format('Y-m-d'),
            'priority' => 'high',
            'goals' => 'Complete sprint deliverables and pass regression testing.',
            'monday' => 'Setup CI pipeline',
            'tuesday' => 'Build authentication module',
            'wednesday' => 'Integrate cloud storage',
            'thursday' => 'Run unit & e2e tests',
            'friday' => 'Deliver demo to stakeholders',
            'tl_notes' => 'Reach out on Slack if blocked by DB migrations.',
        ];

        $response = $this->actingAs($tl)->post(route('plans.store'), $postData);

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('weekly_plans', [
            'created_by' => $tl->id,
            'user_id' => $member->id,
            'title' => 'Sprint 40 Architecture & QA',
            'priority' => 'high',
            'status' => 'shared',
        ]);
    }

    public function test_member_can_view_their_shared_weekly_plans(): void
    {
        $tl = User::factory()->create(['role' => 'tl', 'username' => 'tl.test3']);
        $member = User::factory()->create(['role' => 'member', 'created_by' => $tl->id, 'username' => 'member.test3']);
        $otherMember = User::factory()->create(['role' => 'member', 'created_by' => $tl->id, 'username' => 'member.test4']);

        $plan = WeeklyPlan::create([
            'created_by' => $tl->id,
            'user_id' => $member->id,
            'title' => 'Member Personal Plan',
            'week_start_date' => now()->startOfWeek(),
            'week_end_date' => now()->endOfWeek(),
            'priority' => 'medium',
            'status' => 'shared',
            'days_breakdown' => ['monday' => 'Read documentation'],
        ]);

        $otherPlan = WeeklyPlan::create([
            'created_by' => $tl->id,
            'user_id' => $otherMember->id,
            'title' => 'Secret Other Plan',
            'week_start_date' => now()->startOfWeek(),
            'week_end_date' => now()->endOfWeek(),
            'priority' => 'medium',
            'status' => 'shared',
            'days_breakdown' => ['monday' => 'Private tasks'],
        ]);

        $response = $this->actingAs($member)->get(route('plans.index'));

        $response->assertStatus(200);
        $response->assertSee('Member Personal Plan');
        $response->assertDontSee('Secret Other Plan');
    }

    public function test_member_can_update_plan_status_and_feedback(): void
    {
        $tl = User::factory()->create(['role' => 'tl', 'username' => 'tl.test5']);
        $member = User::factory()->create(['role' => 'member', 'created_by' => $tl->id, 'username' => 'member.test5']);

        $plan = WeeklyPlan::create([
            'created_by' => $tl->id,
            'user_id' => $member->id,
            'title' => 'Member Execution Plan',
            'week_start_date' => now()->startOfWeek(),
            'week_end_date' => now()->endOfWeek(),
            'priority' => 'medium',
            'status' => 'shared',
        ]);

        $response = $this->actingAs($member)->put(route('plans.status.update', $plan), [
            'status' => 'in-progress',
            'employee_feedback' => 'Started working on Monday goals smoothly.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('weekly_plans', [
            'id' => $plan->id,
            'status' => 'in-progress',
            'employee_feedback' => 'Started working on Monday goals smoothly.',
        ]);
    }

    public function test_unauthorized_user_cannot_view_or_modify_other_plan(): void
    {
        $tl = User::factory()->create(['role' => 'tl', 'username' => 'tl.test6']);
        $member1 = User::factory()->create(['role' => 'member', 'created_by' => $tl->id, 'username' => 'member1']);
        $member2 = User::factory()->create(['role' => 'member', 'created_by' => $tl->id, 'username' => 'member2']);

        $plan = WeeklyPlan::create([
            'created_by' => $tl->id,
            'user_id' => $member1->id,
            'title' => 'Confidential Plan',
            'week_start_date' => now()->startOfWeek(),
            'week_end_date' => now()->endOfWeek(),
            'priority' => 'medium',
            'status' => 'shared',
        ]);

        // member2 cannot view plan of member1
        $response = $this->actingAs($member2)->get(route('plans.show', $plan));
        $response->assertStatus(403);

        // member2 cannot update status of member1
        $updateResponse = $this->actingAs($member2)->put(route('plans.status.update', $plan), [
            'status' => 'completed',
        ]);
        $updateResponse->assertStatus(403);
    }
}

