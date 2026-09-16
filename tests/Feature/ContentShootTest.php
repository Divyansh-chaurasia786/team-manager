<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ContentShoot;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContentShootTest extends TestCase
{
    use RefreshDatabase;

    protected User $tl;
    protected User $cameraPerson;
    protected User $modelUser;
    protected User $editorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::factory()->create([
            'name' => 'Production Lead',
            'role' => 'tl',
            'email' => 'tl_prod@example.com',
        ]);

        $this->cameraPerson = User::factory()->create([
            'name' => 'Alex Camera',
            'role' => 'member',
            'email' => 'alex@example.com',
            'created_by' => $this->tl->id,
        ]);

        $this->modelUser = User::factory()->create([
            'name' => 'Sophia Model',
            'role' => 'member',
            'email' => 'sophia@example.com',
            'created_by' => $this->tl->id,
        ]);

        $this->editorUser = User::factory()->create([
            'name' => 'Leo Editor',
            'role' => 'member',
            'email' => 'leo@example.com',
            'created_by' => $this->tl->id,
        ]);
    }

    public function test_user_can_view_shoots_index(): void
    {
        $response = $this->actingAs($this->tl)->get(route('shoots.index'));
        $response->assertStatus(200);
        $response->assertSee('Social Media Shoots', false);
        $response->assertSee('Schedule New Shoot');
    }

    public function test_tl_can_schedule_shoot_with_channels_crew_and_script(): void
    {
        $response = $this->actingAs($this->tl)->post(route('shoots.store'), [
            'title'              => 'Top 5 AI Tools in 2026',
            'platform'           => 'both',
            'instagram_handle'   => '@ecofone_official',
            'youtube_channel'    => '@ecofonetech',
            'shoot_date'         => now()->addDays(2)->format('Y-m-d H:i'),
            'location'           => 'Studio Room B',
            'camera_person_id'   => $this->cameraPerson->id,
            'model_id'           => $this->modelUser->id,
            'editor_id'          => $this->editorUser->id,
            'hook'               => '90% of developers do not know about this AI tool...',
            'script'             => "Scene 1: Model holds up phone.\nModel: 'Stop scrolling!'\nCut to screen recording of the app.",
            'concept_notes'      => 'Cyberpunk lighting, high-contrast angle',
            'reference_links'    => 'https://instagram.com/reel/12345',
            'status'             => 'scheduled',
        ]);

        $shoot = ContentShoot::where('title', 'Top 5 AI Tools in 2026')->first();
        $this->assertNotNull($shoot);
        $this->assertEquals('@ecofone_official', $shoot->instagram_handle);
        $this->assertEquals('@ecofonetech', $shoot->youtube_channel);
        $this->assertEquals($this->cameraPerson->id, $shoot->camera_person_id);
        $this->assertEquals($this->modelUser->id, $shoot->model_id);
        $this->assertEquals($this->editorUser->id, $shoot->editor_id);
        $this->assertEquals('scheduled', $shoot->status);

        $response->assertRedirect(route('shoots.show', $shoot));

        // Verify activity log was written
        $log = ActivityLog::where('action', 'shoot_scheduled')->where('entity_id', $shoot->id)->first();
        $this->assertNotNull($log);
    }

    public function test_assigned_crew_can_view_call_sheet_and_script(): void
    {
        $shoot = ContentShoot::create([
            'created_by'         => $this->tl->id,
            'title'              => 'iPhone 16 Pro Battery Test',
            'platform'           => 'instagram',
            'instagram_handle'   => '@ecofone_official',
            'shoot_date'         => now()->addDay(),
            'location'           => 'Main Studio',
            'camera_person_id'   => $this->cameraPerson->id,
            'model_id'           => $this->modelUser->id,
            'editor_id'          => $this->editorUser->id,
            'hook'               => 'Does it actually last 24 hours?',
            'script'             => 'Talking point 1: Unboxing and charging speed.',
            'status'             => 'shooting',
        ]);

        // Camera operator views call sheet
        $response = $this->actingAs($this->cameraPerson)->get(route('shoots.show', $shoot));
        $response->assertStatus(200);
        $response->assertSee('iPhone 16 Pro Battery Test');
        $response->assertSee('@ecofone_official');
        $response->assertSee($this->cameraPerson->name);
        $response->assertSee($this->modelUser->name);
        $response->assertSee('Does it actually last 24 hours?');
        $response->assertSee('Talking point 1: Unboxing and charging speed.');
    }

    public function test_managing_member_can_update_shoot_status(): void
    {
        $shoot = ContentShoot::create([
            'created_by'         => $this->tl->id,
            'managing_member_id' => $this->cameraPerson->id, // Camera person assigned as managing member
            'title'              => 'Desk Setup Tour',
            'platform'           => 'youtube',
            'youtube_channel'    => '@ecofonetech',
            'shoot_date'         => now()->subHour(),
            'camera_person_id'   => $this->cameraPerson->id,
            'model_id'           => $this->modelUser->id,
            'editor_id'          => $this->editorUser->id,
            'status'             => 'shooting',
        ]);

        // Managing member marks shoot as wrapped and moved to editing
        $response = $this->actingAs($this->cameraPerson)->patch(route('shoots.status.update', $shoot), [
            'status' => 'editing',
        ]);

        $response->assertSessionHas('success');
        $shoot->refresh();
        $this->assertEquals('editing', $shoot->status);

        // Non-managing crew member (editor) tries to update status -> 403 Forbidden!
        $unauthorizedResponse = $this->actingAs($this->editorUser)->patch(route('shoots.status.update', $shoot), [
            'status' => 'review',
        ]);
        $unauthorizedResponse->assertStatus(403);

        // TL can always update status or publish with live URL
        $response2 = $this->actingAs($this->tl)->patch(route('shoots.status.update', $shoot), [
            'status'        => 'published',
            'published_url' => 'https://youtube.com/watch?v=123456789',
        ]);

        $shoot->refresh();
        $this->assertEquals('published', $shoot->status);
        $this->assertEquals('https://youtube.com/watch?v=123456789', $shoot->published_url);
    }

    public function test_tl_manages_shoot_when_no_member_assigned(): void
    {
        $shoot = ContentShoot::create([
            'created_by'         => $this->tl->id,
            'managing_member_id' => null, // Unassigned
            'title'              => 'Unassigned Shoot',
            'platform'           => 'instagram',
            'shoot_date'         => now()->addDay(),
            'camera_person_id'   => $this->cameraPerson->id,
            'status'             => 'scheduled',
        ]);

        // Camera person is not managing member -> 403 Forbidden
        $response = $this->actingAs($this->cameraPerson)->patch(route('shoots.status.update', $shoot), [
            'status' => 'shooting',
        ]);
        $response->assertStatus(403);

        // TL updates status directly
        $tlResponse = $this->actingAs($this->tl)->patch(route('shoots.status.update', $shoot), [
            'status' => 'shooting',
        ]);
        $tlResponse->assertSessionHas('success');
        $shoot->refresh();
        $this->assertEquals('shooting', $shoot->status);
    }

    public function test_shoot_details_and_script_can_be_updated(): void
    {
        $shoot = ContentShoot::create([
            'created_by'         => $this->tl->id,
            'title'              => 'Old Draft Title',
            'platform'           => 'both',
            'shoot_date'         => now()->addDays(3),
            'status'             => 'planning',
        ]);

        $response = $this->actingAs($this->tl)->put(route('shoots.update', $shoot), [
            'title'              => 'Final Revised Title: MacBook Air M3',
            'platform'           => 'youtube',
            'instagram_handle'   => null,
            'youtube_channel'    => '@ecofonetech',
            'shoot_date'         => now()->addDays(4)->format('Y-m-d H:i'),
            'location'           => 'Studio 3',
            'camera_person_id'   => $this->cameraPerson->id,
            'model_id'           => $this->modelUser->id,
            'editor_id'          => $this->editorUser->id,
            'hook'               => 'Is 8GB unified memory still enough?',
            'script'             => 'Detailed benchmark tests running Xcode and Premiere Pro.',
            'status'             => 'scripting',
        ]);

        $response->assertSessionHas('success');
        $shoot->refresh();
        $this->assertEquals('Final Revised Title: MacBook Air M3', $shoot->title);
        $this->assertEquals('scripting', $shoot->status);
        $this->assertEquals('Is 8GB unified memory still enough?', $shoot->hook);
    }

    public function test_only_authorized_user_can_delete_shoot(): void
    {
        $shoot = ContentShoot::create([
            'created_by'         => $this->tl->id,
            'title'              => 'Temporary Shoot to Delete',
            'platform'           => 'instagram',
            'shoot_date'         => now()->addDays(5),
            'status'             => 'planning',
        ]);

        $unauthorizedMember = User::factory()->create([
            'role' => 'member',
            'created_by' => $this->tl->id,
        ]);

        // Member cannot delete shoot created by TL
        $response = $this->actingAs($unauthorizedMember)->delete(route('shoots.destroy', $shoot));
        $response->assertStatus(403);
        $this->assertDatabaseHas('content_shoots', ['id' => $shoot->id]);

        // Team Lead can delete shoot
        $response2 = $this->actingAs($this->tl)->delete(route('shoots.destroy', $shoot));
        $response2->assertRedirect(route('shoots.index'));
        $this->assertDatabaseMissing('content_shoots', ['id' => $shoot->id]);
    }

    public function test_user_can_view_redesigned_shoot_with_logo_and_share_on_whatsapp_or_chat(): void
    {
        $shoot = ContentShoot::create([
            'created_by'         => $this->tl->id,
            'title'              => 'EcoFone Flagship Launch Shoot',
            'platform'           => 'both',
            'instagram_handle'   => '@ecofone_official',
            'youtube_channel'    => '@ecofonetech',
            'shoot_date'         => now()->addDays(2),
            'location'           => 'Studio Stage A - Main Cyclorama',
            'camera_person_id'   => $this->cameraPerson->id,
            'model_id'           => $this->modelUser->id,
            'editor_id'          => $this->editorUser->id,
            'hook'               => 'This phone has a hidden camera feature nobody is talking about!',
            'script'             => "Scene 1 [00:00 - 00:05]: Close up on unboxing.\nScene 2 [00:05 - 00:20]: Model tests camera outdoors.",
            'concept_notes'      => 'Clean 5600K daylight lighting with soft fill.',
            'status'             => 'scheduled',
        ]);

        // 1. Check shoots.show view has ONLY WhatsApp share button, Shooting Date, Upload Platform, and pure EcoFone logo
        $showResponse = $this->actingAs($this->tl)->get(route('shoots.show', $shoot));
        $showResponse->assertOk();
        $showResponse->assertSee('Share on WhatsApp');
        $showResponse->assertSee('Shooting Date:');
        $showResponse->assertSee('Upload Platform:');
        $showResponse->assertSee('images/logo.png');
        $showResponse->assertDontSee('Call Time:');
        $showResponse->assertDontSee('Copy for Chat'); // Extra buttons removed
        $showResponse->assertDontSee('Copy Link');
        $showResponse->assertDontSee('Print Call Sheet'); // Print removed
        $showResponse->assertDontSee('Open Print Sheet');
        $showResponse->assertDontSee('printableCallSheet');

        // Verify crew roster
        $showResponse->assertSee('Alex Camera');
        $showResponse->assertSee('Sophia Model');
        $showResponse->assertSee('Leo Editor');
        $showResponse->assertSee('Camera / Videographer');
        $showResponse->assertSee('Model / Presenter / Creator');
        $showResponse->assertSee('Video Editor');
        $showResponse->assertSee('This phone has a hidden camera feature nobody is talking about!');
        $showResponse->assertSee('Scene 1 [00:00 - 00:05]: Close up on unboxing.');

        // 2. Legacy print route redirects cleanly to show
        $printResponse = $this->actingAs($this->cameraPerson)->get(route('shoots.print', $shoot));
        $printResponse->assertRedirect(route('shoots.show', $shoot));

        // 3. When shoot status is NOT scheduled (e.g., planning), WhatsApp share button must NOT be visible
        $shoot->update(['status' => 'planning']);
        $planningResponse = $this->actingAs($this->tl)->get(route('shoots.show', $shoot));
        $planningResponse->assertOk();
        $planningResponse->assertDontSee('Share on WhatsApp');
    }

    public function test_non_tl_member_cannot_schedule_shoot(): void
    {
        $response = $this->actingAs($this->cameraPerson)->post(route('shoots.store'), [
            'title'      => 'Unauthorized Member Shoot',
            'platform'   => 'instagram',
            'shoot_date' => now()->addDays(2)->format('Y-m-d H:i'),
            'status'     => 'scheduled',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('content_shoots', ['title' => 'Unauthorized Member Shoot']);
    }

    public function test_tl_can_reassign_crew_from_shoot_card(): void
    {
        $shoot = ContentShoot::create([
            'created_by' => $this->tl->id,
            'title'      => 'Reassign Crew Test Shoot',
            'platform'   => 'instagram',
            'shoot_date' => now()->addDays(3),
            'status'     => 'scheduled',
        ]);

        // TL reassigns camera person to Leo and model to Alex
        $response = $this->actingAs($this->tl)->patch(route('shoots.assign_crew', $shoot), [
            'camera_person_id' => $this->editorUser->id,
            'model_id'         => $this->cameraPerson->id,
            'editor_id'        => $this->modelUser->id,
            'other_crew'       => 'Boom Mic Operator',
        ]);

        $response->assertSessionHas('success');
        $shoot->refresh();
        $this->assertEquals($this->editorUser->id, $shoot->camera_person_id);
        $this->assertEquals($this->cameraPerson->id, $shoot->model_id);
        $this->assertEquals($this->modelUser->id, $shoot->editor_id);
        $this->assertEquals('Boom Mic Operator', $shoot->other_crew);

        // Verify activity log was created
        $log = ActivityLog::where('action', 'shoot_crew_reassigned')->where('entity_id', $shoot->id)->first();
        $this->assertNotNull($log);
    }

    public function test_non_tl_member_cannot_reassign_crew(): void
    {
        $shoot = ContentShoot::create([
            'created_by' => $this->tl->id,
            'title'      => 'Restricted Reassignment Shoot',
            'platform'   => 'instagram',
            'shoot_date' => now()->addDays(3),
            'status'     => 'scheduled',
        ]);

        $response = $this->actingAs($this->cameraPerson)->patch(route('shoots.assign_crew', $shoot), [
            'camera_person_id' => $this->cameraPerson->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_member_dashboard_shows_assigned_shoots_and_updates_status(): void
    {
        $shoot = ContentShoot::create([
            'created_by'         => $this->tl->id,
            'managing_member_id' => $this->cameraPerson->id, // Assigned to manage
            'title'              => 'Member Dashboard Shoot Production',
            'platform'           => 'both',
            'shoot_date'         => now()->addDays(2),
            'camera_person_id'   => $this->cameraPerson->id,
            'hook'               => 'Secret feature in Android 16',
            'status'             => 'scheduled',
        ]);

        // 1. Managing member views member dashboard
        $response = $this->actingAs($this->cameraPerson)->get(route('member.dashboard'));
        $response->assertOk();
        $response->assertSee('Reel Shoots Managed by You');
        $response->assertSee('Member Dashboard Shoot Production');
        $response->assertSee('Managing Member');
        $response->assertSee('Start Shooting');

        // 2. Managing member advances status to "shooting" from their dashboard
        $statusResponse = $this->actingAs($this->cameraPerson)->patch(route('shoots.status.update', $shoot), [
            'status' => 'shooting',
        ]);
        $statusResponse->assertSessionHas('success');

        $shoot->refresh();
        $this->assertEquals('shooting', $shoot->status);

        // 3. Check dashboard now offers "Move to Editing"
        $response2 = $this->actingAs($this->cameraPerson)->get(route('member.dashboard'));
        $response2->assertOk();
        $response2->assertSee('Move to Editing');
    }
}

