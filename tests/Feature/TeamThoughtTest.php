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
}

