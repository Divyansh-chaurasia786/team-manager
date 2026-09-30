<?php

namespace Tests\Feature;

use App\Models\DriveFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MediaStreamAndDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email'    => 'tester@test.com',
            'role'     => 'tl',
            'password' => Hash::make('password123'),
        ]);

        $this->testDir = public_path('uploads/drive/test_date');
        if (!file_exists($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $f) {
                @unlink($f);
            }
            @rmdir($this->testDir);
        }

        parent::tearDown();
    }

    public function test_local_image_can_be_streamed_and_downloaded(): void
    {
        $fileName = 'sample_photo.jpg';
        $filePath = $this->testDir . '/' . $fileName;
        file_put_contents($filePath, 'fake-jpeg-binary-image-data');

        $driveFile = DriveFile::create([
            'uploaded_by'   => $this->user->id,
            'original_name' => $fileName,
            'drive_file_id' => 'local_sample_123',
            'drive_url'     => 'uploads/drive/test_date/' . $fileName,
            'file_type'     => 'photo',
            'file_size'     => filesize($filePath),
            'mime_type'     => 'image/jpeg',
            'upload_date'   => 'test_date',
        ]);

        // Test stream endpoint
        $streamResponse = $this->actingAs($this->user)->get(route('drive.stream', $driveFile));
        $streamResponse->assertStatus(200);
        $this->assertEquals('image/jpeg', $streamResponse->headers->get('Content-Type'));
        $this->assertEquals('bytes', $streamResponse->headers->get('Accept-Ranges'));

        // Test download endpoint
        $downloadResponse = $this->actingAs($this->user)->get(route('drive.download', $driveFile));
        $downloadResponse->assertStatus(200);
        $this->assertStringContainsString('attachment', $downloadResponse->headers->get('Content-Disposition'));
        $this->assertStringContainsString($fileName, $downloadResponse->headers->get('Content-Disposition'));
    }

    public function test_local_video_can_be_streamed_with_byte_ranges(): void
    {
        $fileName = 'clip.mp4';
        $filePath = $this->testDir . '/' . $fileName;
        file_put_contents($filePath, str_repeat('A', 1000)); // 1000 bytes

        $driveFile = DriveFile::create([
            'uploaded_by'   => $this->user->id,
            'original_name' => $fileName,
            'drive_file_id' => 'local_vid_456',
            'drive_url'     => url('uploads/drive/test_date/' . $fileName),
            'file_type'     => 'video',
            'file_size'     => 1000,
            'mime_type'     => 'video/mp4',
            'upload_date'   => 'test_date',
        ]);

        // Request partial range: bytes=0-499
        $response = $this->actingAs($this->user)->withHeaders([
            'Range' => 'bytes=0-499',
        ])->get(route('drive.stream', $driveFile));

        $response->assertStatus(206);
        $this->assertEquals('bytes 0-499/1000', $response->headers->get('Content-Range'));
        $this->assertEquals('500', $response->headers->get('Content-Length'));
    }

    public function test_nested_uploads_route_serves_media_directly(): void
    {
        $fileName = 'nested_img.png';
        $filePath = $this->testDir . '/' . $fileName;
        file_put_contents($filePath, 'png-binary-data');

        // Request via /uploads/{path}
        $response = $this->get('/uploads/drive/test_date/' . $fileName);
        $response->assertStatus(200);
        $this->assertStringContainsString('image/png', $response->headers->get('Content-Type'));
    }

    public function test_fallback_download_and_stream_never_redirects_to_deprecated_uc_export(): void
    {
        // DriveFile on Google Drive but not stored locally
        $driveFile = DriveFile::create([
            'uploaded_by'   => $this->user->id,
            'original_name' => 'cloud_doc.pdf',
            'drive_file_id' => '1AbCdEfGhIjKlMnOpQrStUvWxYz',
            'drive_url'     => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz/view',
            'file_type'     => 'document',
            'file_size'     => 5000,
            'mime_type'     => 'application/pdf',
            'upload_date'   => '2026-09-30',
        ]);

        $downloadRes = $this->actingAs($this->user)->get(route('drive.download', $driveFile));
        // Should redirect to clean drive_url, never uc?export=download
        $this->assertTrue($downloadRes->isRedirect());
        $target = $downloadRes->headers->get('Location');
        $this->assertStringNotContainsString('uc?export=download', $target);
        $this->assertStringContainsString('https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz/view', $target);

        $streamRes = $this->actingAs($this->user)->get(route('drive.stream', $driveFile));
        $this->assertTrue($streamRes->isRedirect());
        $streamTarget = $streamRes->headers->get('Location');
        $this->assertStringNotContainsString('uc?export=download', $streamTarget);
    }
}
