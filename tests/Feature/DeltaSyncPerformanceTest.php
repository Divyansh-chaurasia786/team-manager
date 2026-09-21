<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\TeamThought;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeltaSyncPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $tl;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::factory()->create([
            'role'     => 'tl',
            'password' => Hash::make('password123'),
        ]);

        $this->member = User::factory()->create([
            'role'       => 'member',
            'created_by' => $this->tl->id,
            'password'   => Hash::make('password123'),
        ]);
    }

    public function test_tasks_sync_returns_changed_false_when_data_unchanged(): void
    {
        $task = Task::create([
            'title'       => 'Performance Test Task',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline'    => now()->addDays(2),
            'status'      => 'pending',
        ]);

        $latestTimestamp = strtotime($task->updated_at);

        // Client passes matching since timestamp and count
        $response = $this->actingAs($this->tl)->getJson(
            route('tasks.sync', [
                'since'       => $latestTimestamp,
                'known_count' => 1,
            ])
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'changed' => false,
        ]);
        $this->assertArrayNotHasKey('tasks', $response->json());
    }

    public function test_tasks_sync_returns_tasks_when_data_changes(): void
    {
        $task = Task::create([
            'title'       => 'Performance Test Task',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline'    => now()->addDays(2),
            'status'      => 'pending',
        ]);

        // Client passes stale timestamp
        $response = $this->actingAs($this->tl)->getJson(
            route('tasks.sync', [
                'since'       => 0,
                'known_count' => 0,
            ])
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertArrayHasKey('tasks', $response->json());
        $this->assertCount(1, $response->json('tasks'));
    }

    public function test_thoughts_messages_returns_changed_false_when_no_new_messages(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Hello Team!',
        ]);

        $sinceTime = now()->timestamp;

        // Poll with after_id equal to existing latest thought ID
        $response = $this->actingAs($this->member)->getJson(
            route('thoughts.messages', [
                'group'    => 'team',
                'after_id' => $thought->id,
                'since'    => $sinceTime,
            ])
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success'   => true,
            'changed'   => false,
            'messages'  => [],
            'latest_id' => $thought->id,
        ]);
    }

    public function test_unread_count_is_cached(): void
    {
        $response1 = $this->actingAs($this->member)->getJson(route('thoughts.unread_count'));
        $response1->assertStatus(200);
        $response1->assertJson(['success' => true]);

        $this->assertTrue(Cache::has("user_{$this->member->id}_unread_thoughts_count"));
    }
}
