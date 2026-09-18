<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PersonalTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalTaskTest extends TestCase
{
    use RefreshDatabase;

    private function makeMember(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'member'], $attrs));
    }

    private function makeTL(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'tl'], $attrs));
    }

    private function makeCEO(): User
    {
        return User::factory()->create(['role' => 'ceo']);
    }

    // ─── INDEX ──────────────────────────────────────────────────────────────

    public function test_user_can_view_my_tasks_page(): void
    {
        $user = $this->makeMember();
        $this->actingAs($user)->get(route('my-tasks.index'))->assertOk();
    }

    public function test_guest_cannot_view_my_tasks(): void
    {
        $this->get(route('my-tasks.index'))->assertRedirect(route('login'));
    }

    // ─── CREATE ─────────────────────────────────────────────────────────────

    public function test_user_can_create_personal_task(): void
    {
        $user = $this->makeMember();

        $this->actingAs($user)->post(route('my-tasks.store'), [
            'title'    => 'My private task',
            'priority' => 'high',
        ])->assertRedirect();

        $this->assertDatabaseHas('personal_tasks', [
            'user_id'   => $user->id,
            'title'     => 'My private task',
            'priority'  => 'high',
            'is_shared' => false,
        ]);
    }

    public function test_task_creation_requires_title(): void
    {
        $user = $this->makeMember();
        $this->actingAs($user)->post(route('my-tasks.store'), [])->assertSessionHasErrors('title');
    }

    // ─── PRIVATE BY DEFAULT ─────────────────────────────────────────────────

    public function test_personal_task_is_not_visible_to_other_users(): void
    {
        $owner  = $this->makeMember();
        $other  = $this->makeMember();

        PersonalTask::create([
            'user_id' => $owner->id,
            'title'   => 'Secret task',
            'priority'=> 'low',
            'status'  => 'pending',
        ]);

        // Other user's My Tasks page should show 0 tasks
        $response = $this->actingAs($other)->get(route('my-tasks.index'));
        $response->assertOk();
        $response->assertDontSee('Secret task');
    }

    // ─── UPDATE ─────────────────────────────────────────────────────────────

    public function test_owner_can_update_task(): void
    {
        $user = $this->makeMember();
        $task = PersonalTask::create(['user_id' => $user->id, 'title' => 'Old title', 'priority' => 'low', 'status' => 'pending']);

        $this->actingAs($user)->patch(route('my-tasks.update', $task), [
            'title'    => 'New title',
            'priority' => 'high',
            'status'   => 'in-progress',
        ])->assertRedirect();

        $this->assertDatabaseHas('personal_tasks', ['id' => $task->id, 'title' => 'New title', 'status' => 'in-progress']);
    }

    public function test_non_owner_cannot_update_task(): void
    {
        $owner = $this->makeMember();
        $other = $this->makeMember();
        $task  = PersonalTask::create(['user_id' => $owner->id, 'title' => 'Task', 'priority' => 'low', 'status' => 'pending']);

        $this->actingAs($other)->patch(route('my-tasks.update', $task), [
            'title' => 'Hacked',
        ])->assertForbidden();
    }

    // ─── DELETE ─────────────────────────────────────────────────────────────

    public function test_owner_can_delete_task(): void
    {
        $user = $this->makeMember();
        $task = PersonalTask::create(['user_id' => $user->id, 'title' => 'Delete me', 'priority' => 'low', 'status' => 'pending']);

        $this->actingAs($user)->delete(route('my-tasks.destroy', $task))->assertRedirect();
        $this->assertDatabaseMissing('personal_tasks', ['id' => $task->id]);
    }

    public function test_non_owner_cannot_delete_task(): void
    {
        $owner = $this->makeMember();
        $other = $this->makeMember();
        $task  = PersonalTask::create(['user_id' => $owner->id, 'title' => 'Task', 'priority' => 'low', 'status' => 'pending']);

        $this->actingAs($other)->delete(route('my-tasks.destroy', $task))->assertForbidden();
    }

    // ─── SHARE ──────────────────────────────────────────────────────────────

    public function test_member_can_share_task_to_tl(): void
    {
        $tl     = $this->makeTL();
        $member = $this->makeMember(['created_by' => $tl->id]);
        $task   = PersonalTask::create(['user_id' => $member->id, 'title' => 'My work', 'priority' => 'medium', 'status' => 'pending']);

        $this->actingAs($member)->post(route('my-tasks.share', $task))->assertRedirect();

        $task->refresh();
        $this->assertTrue($task->is_shared);
        $this->assertEquals($tl->id, $task->shared_to);
    }

    public function test_tl_can_share_task_to_ceo(): void
    {
        $ceo  = $this->makeCEO();
        $tl   = $this->makeTL();
        $task = PersonalTask::create(['user_id' => $tl->id, 'title' => 'TL task', 'priority' => 'high', 'status' => 'pending']);

        $this->actingAs($tl)->post(route('my-tasks.share', $task))->assertRedirect();

        $task->refresh();
        $this->assertTrue($task->is_shared);
        $this->assertEquals($ceo->id, $task->shared_to);
    }

    public function test_ceo_shared_task_appears_in_their_shared_with_me(): void
    {
        $ceo  = $this->makeCEO();
        $tl   = $this->makeTL();
        $task = PersonalTask::create([
            'user_id'   => $tl->id,
            'title'     => 'Shared to CEO',
            'priority'  => 'high',
            'status'    => 'pending',
            'is_shared' => true,
            'shared_to' => $ceo->id,
        ]);

        $response = $this->actingAs($ceo)->get(route('my-tasks.index'));
        $response->assertOk()->assertSee('Shared to CEO');
    }

    // ─── UNSHARE ────────────────────────────────────────────────────────────

    public function test_owner_can_unshare_task(): void
    {
        $ceo  = $this->makeCEO();
        $tl   = $this->makeTL();
        $task = PersonalTask::create([
            'user_id'   => $tl->id,
            'title'     => 'Task',
            'priority'  => 'medium',
            'status'    => 'pending',
            'is_shared' => true,
            'shared_to' => $ceo->id,
        ]);

        $this->actingAs($tl)->post(route('my-tasks.unshare', $task))->assertRedirect();

        $task->refresh();
        $this->assertFalse($task->is_shared);
        $this->assertNull($task->shared_to);
    }

    public function test_ceo_has_no_higher_authority_to_share_to(): void
    {
        $ceo  = $this->makeCEO();
        $task = PersonalTask::create(['user_id' => $ceo->id, 'title' => 'CEO task', 'priority' => 'high', 'status' => 'pending']);

        // Should redirect back with error (no higher authority)
        $this->actingAs($ceo)->post(route('my-tasks.share', $task))
            ->assertRedirect();

        $task->refresh();
        $this->assertFalse($task->is_shared);
    }
}
