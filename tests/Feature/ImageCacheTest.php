<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TeamThought;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImageCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_avatar_url_is_cached_and_versioned(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // Put a fake avatar file in public/uploads/profile_photos
        $dir = public_path('uploads/profile_photos');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $testFile = $dir . '/test_avatar_' . $user->id . '.jpg';
        file_put_contents($testFile, 'fake-avatar-content');

        $user->update([
            'profile_photo_path' => 'uploads/profile_photos/test_avatar_' . $user->id . '.jpg',
        ]);

        // First call should resolve and store in Cache
        $avatarUrl = $user->avatar_url;
        $this->assertNotNull($avatarUrl);
        $this->assertStringContainsString('?v=', $avatarUrl);
        $this->assertTrue(Cache::has("user_avatar_url_{$user->id}"));
        $this->assertEquals($avatarUrl, Cache::get("user_avatar_url_{$user->id}"));

        // Clean up file
        if (file_exists($testFile)) {
            @unlink($testFile);
        }
    }

    public function test_updating_avatar_invalidates_cache(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $dir = public_path('uploads/profile_photos');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $testFile1 = $dir . '/avatar1_' . $user->id . '.jpg';
        file_put_contents($testFile1, 'fake-1');

        $user->update(['profile_photo_path' => 'uploads/profile_photos/avatar1_' . $user->id . '.jpg']);
        $url1 = $user->avatar_url;
        $this->assertTrue(Cache::has("user_avatar_url_{$user->id}"));

        // Simulate upload via settings controller
        $file = UploadedFile::fake()->create('new_avatar.jpg', 100, 'image/jpeg');
        $response = $this->actingAs($user)->post(route('settings.avatar'), [
            'profile_photo' => $file,
        ]);
        $response->assertRedirect();

        $user->refresh();
        $url2 = $user->avatar_url;
        $this->assertNotEquals($url1, $url2);
        $this->assertEquals($url2, Cache::get("user_avatar_url_{$user->id}"));

        // Clean up
        if (file_exists($testFile1)) {
            @unlink($testFile1);
        }
        if ($user->profile_photo_path && file_exists(public_path($user->profile_photo_path))) {
            @unlink(public_path($user->profile_photo_path));
        }
    }

    public function test_media_upload_endpoint_serves_with_cache_control_and_etag(): void
    {
        $dir = public_path('uploads/test_media');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'cached_image_' . time() . '.png';
        $fullPath = $dir . '/' . $filename;
        file_put_contents($fullPath, 'png-image-binary-data');

        // Request file via the uploads route
        $response = $this->get('/uploads/test_media/' . $filename);

        $response->assertStatus(200);
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=31536000', $cacheControl);
        $this->assertStringContainsString('immutable', $cacheControl);
        $this->assertNotNull($response->headers->get('ETag'));

        $etag = $response->headers->get('ETag');

        // Conditional request with If-None-Match should return 304
        $condResponse = $this->withHeaders([
            'If-None-Match' => $etag,
        ])->get('/uploads/test_media/' . $filename);

        $condResponse->assertStatus(304);

        // Clean up
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}
