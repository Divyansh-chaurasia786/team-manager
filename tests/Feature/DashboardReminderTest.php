<?php

namespace Tests\Feature;

use App\Models\ContentShoot;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $tl;
    private User $member1;
    private User $member2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::create([
            'name' => 'Team Lead Boss',
            'username' => 'tl_boss',
            'email' => 'boss@example.com',
            'password' => Hash::make('password123'),
            'role' => 'tl',
            'must_change_password' => false,
        ]);

        $this->member1 = User::create([
            'name' => 'Alice Camera',
            'username' => 'alice_cam',
            'email' => 'alice@example.com',
            'password' => Hash::make('password123'),
            'role' => 'member',
            'created_by' => $this->tl->id,
            'must_change_password' => false,
        ]);

        $this->member2 = User::create([
            'name' => 'Bob Model',
            'username' => 'bob_model',
            'email' => 'bob@example.com',
            'password' => Hash::make('password123'),
            'role' => 'member',
            'created_by' => $this->tl->id,
            'must_change_password' => false,
        ]);
    }

    public function test_tl_dashboard_displays_tasks_and_shoots_within_2_days(): void
    {
        // 1. Task due in 20 hours (should appear in 2-day reminder console)
        Task::create([
            'title' => 'Urgent Landing Page Polish',
            'description' => 'Fix CSS breakpoints',
            'assigned_to' => $this->member1->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addHours(20),
            'status' => 'in-progress',
        ]);

        // 2. Task due in 40 hours (~2 days) (should appear in 2-day reminder console)
        Task::create([
            'title' => 'Database Backup Automation',
            'description' => 'Configure cron',
            'assigned_to' => $this->member2->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addHours(40),
            'status' => 'pending',
        ]);

        // 3. Shoot scheduled for tomorrow (should appear in reminder)
        ContentShoot::create([
            'title' => 'Instagram Reel: Autumn Lookbook',
            'platform' => 'instagram',
            'instagram_handle' => '@fashion_brand',
            'shoot_date' => now()->addHours(24),
            'location' => 'Central Park, NY',
            'camera_person_id' => $this->member1->id,
            'model_id' => $this->member2->id,
            'status' => 'planning',
            'script' => 'Hook: Look at this autumn fit! Transition to slow mo walks.',
            'created_by' => $this->tl->id,
        ]);

        $response = $this->actingAs($this->tl)->get(route('tl.dashboard'));

        $response->assertOk();
        $response->assertSee('Scheduled Reminders (Due Within 2 Days)');

        // Urgent Task (20h) assertions — badge will say "Due Today", "Due Tomorrow", or "Due in 2 Days"
        // depending on the time of execution; we check the title is present in reminder section
        $response->assertSee('Urgent Landing Page Polish');

        // 2-Day Task assertions
        $response->assertSee('Database Backup Automation');

        // Shoot assertions
        $response->assertSee('Instagram Reel: Autumn Lookbook');
        $response->assertSee('@fashion_brand');
    }

    public function test_tl_dashboard_hides_reminder_banner_when_no_tasks_or_shoots_due_within_2_days(): void
    {
        // Task due in 6 days (more than 2 days)
        Task::create([
            'title' => 'Quarterly Planning Sprint',
            'description' => 'Draft roadmaps',
            'assigned_to' => $this->member1->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDays(6),
            'status' => 'pending',
        ]);

        // Completed task due in 1 day (completed tasks should NOT be in reminders)
        Task::create([
            'title' => 'Completed Feature Delivery',
            'description' => 'Done',
            'assigned_to' => $this->member2->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addDay(),
            'status' => 'completed',
        ]);

        // Shoot scheduled in 7 days
        ContentShoot::create([
            'title' => 'Future YouTube Podcast Ep 10',
            'platform' => 'youtube',
            'youtube_channel' => '@tech_talks',
            'shoot_date' => now()->addDays(7),
            'camera_person_id' => $this->member1->id,
            'status' => 'planning',
            'created_by' => $this->tl->id,
        ]);

        $response = $this->actingAs($this->tl)->get(route('tl.dashboard'));

        $response->assertOk();
        // The 2-day reminder console banner must NOT appear
        $response->assertDontSee('Scheduled Reminders (Due Within 2 Days)');
    }

    public function test_member_dashboard_only_displays_their_own_2_day_reminders(): void
    {
        // Member 1 task due in 18 hours
        Task::create([
            'title' => 'Member 1 Urgent Video Edit',
            'description' => 'Export final render',
            'assigned_to' => $this->member1->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addHours(18),
            'status' => 'in-progress',
        ]);

        // Member 2 task due in 20 hours (should NOT appear on Member 1 dashboard)
        Task::create([
            'title' => 'Member 2 Solo Copywriting',
            'description' => 'Write ad copy',
            'assigned_to' => $this->member2->id,
            'assigned_by' => $this->tl->id,
            'deadline' => now()->addHours(20),
            'status' => 'pending',
        ]);

        // Shoot where Member 1 is camera person scheduled tomorrow
        ContentShoot::create([
            'title' => 'Viral Challenge Shoot',
            'platform' => 'other',
            'shoot_date' => now()->addHours(30),
            'camera_person_id' => $this->member1->id,
            'status' => 'shooting',
            'script' => 'Dance routine script details',
            'created_by' => $this->tl->id,
        ]);

        // Member 1 checks their dashboard
        $response = $this->actingAs($this->member1)->get(route('member.dashboard'));

        $response->assertOk();
        $response->assertSee('Your Scheduled Reminders (Due Within 2 Days)');
        $response->assertSee('Member 1 Urgent Video Edit');
        // Badge text is dynamic ("Due Today"/"Due Tomorrow"/"Due in 2 Days") based on calendar day
        $response->assertSee('Viral Challenge Shoot');

        // Member 2's task must NOT appear
        $response->assertDontSee('Member 2 Solo Copywriting');
    }

    public function test_published_shoots_do_not_trigger_2_day_reminders(): void
    {
        ContentShoot::create([
            'title' => 'Already Published Product Teaser',
            'platform' => 'instagram',
            'instagram_handle' => '@shop_now',
            'shoot_date' => now()->addHours(10),
            'camera_person_id' => $this->member1->id,
            'status' => 'published',
            'script' => 'Published content',
            'created_by' => $this->tl->id,
        ]);

        $response = $this->actingAs($this->tl)->get(route('tl.dashboard'));
        $response->assertOk();
        $response->assertDontSee('Scheduled Reminders (Due Within 2 Days)');
    }
}
