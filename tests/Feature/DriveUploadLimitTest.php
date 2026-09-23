<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriveUploadLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $tl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::create([
            'name'     => 'Team Lead',
            'username' => 'tl_drive_test',
            'email'    => 'tl_drive@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
            'must_change_password' => false,
        ]);
    }

    public function test_drive_upload_page_displays_no_upload_limit_badge(): void
    {
        $response = $this->actingAs($this->tl)->get(route('upload.index'));
        $response->assertStatus(200);
        $response->assertSee('No upload limit', false);
        $response->assertSee('Unlimited file size', false);
        $response->assertDontSee('Max file size 100MB');
    }

    public function test_drive_upload_has_no_file_size_limit_validation(): void
    {
        Storage::fake('local');

        // Create a 150MB fake file (153600 KB) which exceeds previous 100MB (102400 KB) limit
        $largeFile = UploadedFile::fake()->create('large_footage.mp4', 153600, 'video/mp4');

        $response = $this->actingAs($this->tl)->post(route('upload.store'), [
            'file' => $largeFile,
        ]);

        // It should NOT fail validation (e.g. 422 with 'The file field must not be greater than 102400 kilobytes')
        $response->assertSessionDoesntHaveErrors(['file']);
    }

    public function test_chat_media_upload_retains_50mb_size_limit(): void
    {
        Storage::fake('public');

        // 60MB file (61440 KB), exceeding chat 50MB (51200 KB) limit
        $oversizedChatFile = UploadedFile::fake()->create('huge_chat_file.zip', 61440);

        $response = $this->actingAs($this->tl)->post(route('thoughts.store'), [
            'content' => 'Trying to post huge file in chat',
            'media'   => $oversizedChatFile,
        ]);

        // Chat MUST fail validation on oversized file
        $response->assertSessionHasErrors(['media']);
    }
}
