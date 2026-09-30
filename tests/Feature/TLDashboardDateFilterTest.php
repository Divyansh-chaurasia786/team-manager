<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TLDashboardDateFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $tl;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::create([
            'name' => 'Team Lead Boss',
            'username' => 'tl_boss',
            'email' => 'tl@teammanager.com',
            'password' => Hash::make('password123'),
            'role' => 'tl',
            'must_change_password' => false,
        ]);

        $this->member = User::create([
            'name' => 'Alice Member',
            'username' => 'alice_m',
            'email' => 'alice@example.com',
            'password' => Hash::make('password123'),
            'role' => 'member',
            'created_by' => $this->tl->id,
            'must_change_password' => false,
        ]);
    }

    public function test_tl_dashboard_shows_today_tasks_by_default(): void
    {
        // Task created today
        $todayTask = Task::create([
            'title' => 'Task Created Today',
            'description' => 'Important task for today',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(5),
            'status' => 'pending',
        ]);

        // Task created 5 days ago
        $pastTask = Task::create([
            'title' => 'Task Created Past Day',
            'description' => 'Old task description',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(10),
            'status' => 'completed',
        ]);
        $pastTask->created_at = now()->subDays(5);
        $pastTask->saveQuietly();

        $response = $this->actingAs($this->tl)->get(route('tl.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Task Created Today');
        $response->assertDontSee('Task Created Past Day');
    }

    public function test_tl_dashboard_filters_tasks_by_past_date_query_param(): void
    {
        $pastDate = now()->subDays(3)->format('Y-m-d');

        // Past task
        $pastTask = Task::create([
            'title' => 'Task Created Three Days Ago',
            'description' => 'Historical task detail',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(10),
            'status' => 'in-progress',
        ]);
        $pastTask->created_at = now()->subDays(3);
        $pastTask->saveQuietly();

        // Today task
        $todayTask = Task::create([
            'title' => 'Current Day Task',
            'description' => 'Not today',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(10),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->tl)->get(route('tl.dashboard', ['date' => $pastDate]));

        $response->assertStatus(200);
        $response->assertSee('Task Created Three Days Ago');
        $response->assertDontSee('Current Day Task');
    }

    public function test_tl_dashboard_ajax_request_returns_json_with_day_stats_and_tasks(): void
    {
        $targetDate = now()->subDays(2)->format('Y-m-d');

        $task = Task::create([
            'title' => 'Design Sprint Review',
            'description' => 'Deliver wireframes',
            'assigned_to' => $this->member->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(5),
            'status' => 'submitted',
        ]);
        $task->created_at = now()->subDays(2);
        $task->saveQuietly();

        $response = $this->actingAs($this->tl)->json('GET', route('tl.dashboard', ['date' => $targetDate]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'selectedDate' => $targetDate,
            'dayStats' => [
                'total' => 1,
                'submitted' => 1,
                'completed' => 0,
            ],
        ]);

        $data = $response->json();
        $this->assertCount(1, $data['tasks']);
        $this->assertEquals('Design Sprint Review', $data['tasks'][0]['title']);
        $this->assertEquals('Alice Member', $data['tasks'][0]['assigned_to']);
    }
}
