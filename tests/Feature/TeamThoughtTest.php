<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TeamThought;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamThoughtTest extends TestCase
{
    use RefreshDatabase;

    protected User $tl;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::create([
            'name' => 'Team Lead',
            'username' => 'tl_thought_user',
            'email' => 'tl_thought@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'tl',
            'must_change_password' => false,
        ]);

        $this->member = User::create([
            'name' => 'John Member',
            'username' => 'john_thought_user',
            'email' => 'john_thought@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'member',
            'created_by' => $this->tl->id,
            'must_change_password' => false,
        ]);
    }

    public function test_user_can_view_thoughts_page(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'content'    => 'Design system token review',
            'media_type' => 'none',
        ]);

        $this->member->update(['designation' => 'Lead UI Designer']);

        $response = $this->actingAs($this->member)->get(route('thoughts.index'));
        $response->assertStatus(200);
        $response->assertSee('Team Chat', false);
        $response->assertSee('John Member', false);
        $response->assertSee('Lead UI Designer', false);
        $response->assertSee('Design system token review', false);
        $response->assertSee($thought->created_at->format('h:i A'), false);
    }

    public function test_member_can_post_thought_with_text_and_link(): void
    {
        $response = $this->actingAs($this->member)->post(route('thoughts.store'), [
            'content'  => 'Checking out this awesome new prototype!',
            'link_url' => 'https://example.com/prototype',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('team_thoughts', [
            'user_id'  => $this->member->id,
            'content'  => 'Checking out this awesome new prototype!',
            'link_url' => 'https://example.com/prototype',
        ]);
    }

    public function test_member_can_post_thought_with_image_retaining_local_storage_and_7_day_expiry(): void
    {
        Storage::fake('public');
        // Use create() with mimeType instead of image() which requires GD extension
        $fakeImage = UploadedFile::fake()->create('design_concept.png', 500, 'image/png');

        $response = $this->actingAs($this->member)->post(route('thoughts.store'), [
            'content' => 'Concept art attached',
            'media'   => $fakeImage,
        ]);

        $response->assertRedirect();

        $thought = TeamThought::where('user_id', $this->member->id)->first();
        $this->assertNotNull($thought);
        $this->assertEquals('image', $thought->media_type);
        $this->assertNotNull($thought->media_path);
        $this->assertNull($thought->drive_url); // NOT uploaded to Drive initially!
        $this->assertFalse($thought->is_expired);
        $this->assertNotNull($thought->expires_at);

        // Check that expires_at is roughly 7 days from now
        $this->assertTrue($thought->expires_at->greaterThan(now()->addDays(6)));
        $this->assertTrue($thought->expires_at->lessThanOrEqualTo(now()->addDays(7)->addMinute()));
    }

    public function test_regular_member_cannot_upload_media_to_drive(): void
    {
        $thought = TeamThought::create([
            'user_id'             => $this->member->id,
            'content'             => 'Local image',
            'media_path'          => 'uploads/chat_media/test.png',
            'media_type'          => 'image',
            'media_original_name' => 'test.png',
            'expires_at'          => now()->addDays(7),
        ]);

        // Regular member attempts to trigger Drive upload
        $response = $this->actingAs($this->member)->post(route('thoughts.drive.upload', $thought));
        $response->assertStatus(403);
    }

    public function test_team_lead_can_upload_media_to_drive(): void
    {
        // Place a real temp file in public/uploads/chat_media/
        $testDir = public_path('uploads/chat_media');
        if (!file_exists($testDir)) {
            mkdir($testDir, 0755, true);
        }
        $testFilePath = $testDir . '/test_sync.png';
        file_put_contents($testFilePath, 'dummy-image-bytes');

        $thought = TeamThought::create([
            'user_id'             => $this->member->id,
            'content'             => 'Need TL approval for drive backup',
            'media_path'          => 'uploads/chat_media/test_sync.png',
            'media_type'          => 'image',
            'media_original_name' => 'test_sync.png',
            'expires_at'          => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->tl)->post(route('thoughts.drive.upload', $thought));
        $response->assertRedirect();

        $thought->refresh();
        $this->assertNotNull($thought->drive_url);
        $this->assertNotNull($thought->uploaded_to_drive_at);
        $this->assertEquals($this->tl->id, $thought->uploaded_to_drive_by);
        $this->assertNull($thought->media_path); // Removed from local storage!
        $this->assertFalse(file_exists($testFilePath)); // Local file deleted!
    }

    public function test_cleanup_command_removes_unapproved_media_older_than_7_days(): void
    {
        $testDir = public_path('uploads/chat_media');
        if (!file_exists($testDir)) {
            mkdir($testDir, 0755, true);
        }
        $expiredFilePath = $testDir . '/expired_test.png';
        file_put_contents($expiredFilePath, 'dummy-bytes');

        $thought = TeamThought::create([
            'user_id'             => $this->member->id,
            'content'             => 'Old unapproved media',
            'media_path'          => 'uploads/chat_media/expired_test.png',
            'media_type'          => 'image',
            'media_original_name' => 'expired_test.png',
            'expires_at'          => now()->subMinute(), // Expired!
            'is_expired'          => false,
        ]);

        // Run the cleanup artisan command
        $this->artisan('chat:cleanup-expired')->assertSuccessful();

        $thought->refresh();
        $this->assertTrue($thought->is_expired);
        $this->assertNull($thought->media_path);
        $this->assertFalse(file_exists($expiredFilePath)); // Deleted from disk!
    }

    public function test_shared_link_never_expires_and_remains_intact(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'content'    => 'Shared permanent research article',
            'link_url'   => 'https://example.com/permanent-article',
            'media_type' => 'none',
            'expires_at' => null, // Links never have an expiry timestamp
        ]);

        // Run cleanup
        $this->artisan('chat:cleanup-expired')->assertSuccessful();

        $thought->refresh();
        $this->assertFalse($thought->is_expired);
        $this->assertEquals('https://example.com/permanent-article', $thought->link_url);
        $this->assertDatabaseHas('team_thoughts', [
            'id'       => $thought->id,
            'link_url' => 'https://example.com/permanent-article',
        ]);
    }

    public function test_only_team_lead_can_delete_thoughts_and_links(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'content'    => 'Member shared link to tool',
            'link_url'   => 'https://example.com/tool',
            'media_type' => 'none',
        ]);

        // 1. Regular member attempts to delete thought/link -> 403 Forbidden
        $memberResponse = $this->actingAs($this->member)->delete(route('thoughts.destroy', $thought));
        $memberResponse->assertStatus(403);
        $this->assertDatabaseHas('team_thoughts', ['id' => $thought->id]);

        // 2. Team Lead deletes thought/link -> Permitted
        $tlResponse = $this->actingAs($this->tl)->delete(route('thoughts.destroy', $thought));
        $tlResponse->assertRedirect();
        $this->assertDatabaseMissing('team_thoughts', ['id' => $thought->id]);
    }

    public function test_team_chat_is_strictly_private_and_cannot_be_seen_by_hr_and_ceo(): void
    {
        $hr = User::create([
            'name' => 'HR Manager',
            'username' => 'hr_chat_tester',
            'email' => 'hr_chat@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'hr',
            'must_change_password' => false,
        ]);

        $ceo = User::create([
            'name' => 'CEO Executive',
            'username' => 'ceo_chat_tester',
            'email' => 'ceo_chat@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'ceo',
            'must_change_password' => false,
        ]);

        // Private team message
        $privateThought = TeamThought::create([
            'user_id'    => $this->member->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Confidential team internal discussion',
        ]);

        // 1. HR tries to access team chat -> Redirected to company group
        $hrResponse = $this->actingAs($hr)->get('/thoughts?group=team');
        $hrResponse->assertRedirect('/thoughts?group=company');

        // HR AJAX poll for team chat -> 403 Forbidden
        $hrAjaxResponse = $this->actingAs($hr)->getJson('/thoughts/messages?group=team');
        $hrAjaxResponse->assertStatus(403);

        // 2. CEO tries to access team chat -> Redirected to company group
        $ceoResponse = $this->actingAs($ceo)->get('/thoughts?group=team');
        $ceoResponse->assertRedirect('/thoughts?group=company');

        $ceoAjaxResponse = $this->actingAs($ceo)->getJson('/thoughts/messages?group=team');
        $ceoAjaxResponse->assertStatus(403);

        // 3. Team Member and TL can access team chat
        $memberResponse = $this->actingAs($this->member)->get('/thoughts?group=team');
        $memberResponse->assertStatus(200);
        $memberResponse->assertSee('Confidential team internal discussion');
    }

    public function test_sender_can_unsend_message_within_24_hours(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'I made a typo here',
        ]);
        $thought->created_at = now()->subHours(2);
        $thought->save();

        $response = $this->actingAs($this->member)->postJson(route('thoughts.unsend', $thought));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $thought->refresh();
        $this->assertTrue($thought->is_deleted);
        $this->assertNull($thought->content);
        $this->assertEquals($this->member->id, $thought->deleted_by);
    }

    public function test_sender_cannot_unsend_message_after_24_hours(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Yesterday discussion',
        ]);
        $thought->created_at = now()->subHours(25);
        $thought->save();

        $response = $this->actingAs($this->member)->postJson(route('thoughts.unsend', $thought));
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);

        $thought->refresh();
        $this->assertFalse($thought->is_deleted);
        $this->assertEquals('Yesterday discussion', $thought->content);
    }

    public function test_user_cannot_unsend_another_users_message(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->tl->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'TL announcement',
            'created_at' => now()->subHour(),
        ]);

        // Member attempts to unsend TL's message -> 403
        $response = $this->actingAs($this->member)->postJson(route('thoughts.unsend', $thought));
        $response->assertStatus(403);

        $thought->refresh();
        $this->assertFalse($thought->is_deleted);
    }

    public function test_team_lead_can_delete_anyones_message_via_ajax(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Inappropriate remark to be moderated',
            'created_at' => now()->subHours(30), // Even older than 24h, TL has admin power
        ]);

        $response = $this->actingAs($this->tl)->deleteJson(route('thoughts.destroy', $thought));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $thought->refresh();
        $this->assertTrue($thought->is_deleted);
        $this->assertEquals($this->tl->id, $thought->deleted_by);
    }

    public function test_read_receipts_seen_status_tracking(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->tl->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Please read this update',
        ]);

        $this->assertEmpty($thought->seen_by ?: []);

        // Member polls messages
        $this->actingAs($this->member)->getJson('/thoughts/messages?group=team');

        $thought->refresh();
        $this->assertNotEmpty($thought->seen_by);
        $this->assertEquals($this->member->id, $thought->seen_by[0]['user_id']);
        $this->assertEquals($this->member->name, $thought->seen_by[0]['user_name']);

        // Check message info endpoint
        $infoResponse = $this->actingAs($this->tl)->getJson(route('thoughts.info', $thought));
        $infoResponse->assertStatus(200);
        $infoResponse->assertJson(['success' => true]);
        $this->assertCount(1, $infoResponse->json('seen_by'));
    }

    public function test_message_reactions_toggle(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->tl->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Great work this week!',
        ]);

        // 1. Add reaction ❤️
        $reactResponse = $this->actingAs($this->member)->postJson(route('thoughts.react', $thought), [
            'emoji' => '❤️',
        ]);
        $reactResponse->assertStatus(200);
        $thought->refresh();
        $this->assertCount(1, $thought->reactions);
        $this->assertEquals('❤️', $thought->reactions[0]['emoji']);

        // 2. Click same reaction again -> toggles off
        $toggleOffResponse = $this->actingAs($this->member)->postJson(route('thoughts.react', $thought), [
            'emoji' => '❤️',
        ]);
        $toggleOffResponse->assertStatus(200);
        $thought->refresh();
        $this->assertCount(0, $thought->reactions);
    }

    public function test_tl_can_add_and_remove_members_from_team_chat_group(): void
    {
        $newMember = User::create([
            'name' => 'Jane Colleague',
            'username' => 'jane_chat_colleague',
            'email' => 'jane_chat@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'member',
            'must_change_password' => false,
        ]);

        // 1. TL adds member to group
        $addResponse = $this->actingAs($this->tl)->postJson(route('thoughts.members.add'), [
            'user_id' => $newMember->id,
        ]);
        $addResponse->assertStatus(200);
        $addResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('chat_group_members', [
            'tl_id'     => $this->tl->id,
            'user_id'   => $newMember->id,
            'is_active' => true,
        ]);

        // 2. TL removes member from group
        $removeResponse = $this->actingAs($this->tl)->deleteJson(route('thoughts.members.remove', $newMember));
        $removeResponse->assertStatus(200);
        $removeResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('chat_group_members', [
            'tl_id'     => $this->tl->id,
            'user_id'   => $newMember->id,
            'is_active' => false,
        ]);

        // 3. Regular member cannot add members
        $unauthResponse = $this->actingAs($this->member)->postJson(route('thoughts.members.add'), [
            'user_id' => $newMember->id,
        ]);
        $unauthResponse->assertStatus(403);
    }

    public function test_member_can_post_thought_with_document_and_audio(): void
    {
        Storage::fake('public');

        // 1. Upload PDF document
        $pdfFile = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');
        $pdfResponse = $this->actingAs($this->member)->postJson(route('thoughts.store'), [
            'content' => 'Here is the project PDF',
            'media' => $pdfFile,
            'group_type' => 'team',
        ]);

        $pdfResponse->assertStatus(200);
        $pdfResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('team_thoughts', [
            'user_id' => $this->member->id,
            'media_type' => 'document',
            'media_original_name' => 'contract.pdf',
        ]);

        // 2. Upload MP3 audio
        $audioFile = UploadedFile::fake()->create('voice_note.mp3', 200, 'audio/mpeg');
        $audioResponse = $this->actingAs($this->member)->postJson(route('thoughts.store'), [
            'content' => 'Listen to this note',
            'media' => $audioFile,
            'group_type' => 'team',
        ]);

        $audioResponse->assertStatus(200);
        $audioResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('team_thoughts', [
            'user_id' => $this->member->id,
            'media_type' => 'audio',
            'media_original_name' => 'voice_note.mp3',
        ]);
    }
}

