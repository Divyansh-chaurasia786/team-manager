<?php

namespace Tests\Feature;

use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DriveFolderAndDynamicUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $tl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::create([
            'name'     => 'Team Lead',
            'username' => 'tl_drive_feature',
            'email'    => 'tl_feature@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
            'must_change_password' => false,
        ]);
    }

    public function test_can_create_drive_folder(): void
    {
        $response = $this->actingAs($this->tl)->postJson(route('drive.folders.store'), [
            'name' => 'Marketing Shoots',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('drive_folders', [
            'name'       => 'Marketing Shoots',
            'created_by' => $this->tl->id,
        ]);
    }

    public function test_can_create_document_file_directly(): void
    {
        $folder = DriveFolder::create([
            'name'       => 'Briefs',
            'created_by' => $this->tl->id,
        ]);

        $response = $this->actingAs($this->tl)->postJson(route('drive.files.create'), [
            'name'      => 'Campaign_Notes',
            'extension' => 'txt',
            'content'   => 'Detailed shoot agenda and script.',
            'folder_id' => $folder->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('drive_files', [
            'original_name' => 'Campaign_Notes.txt',
            'folder_id'     => $folder->id,
            'file_type'     => 'document',
        ]);
    }

    public function test_ajax_file_upload_returns_json(): void
    {
        $file = UploadedFile::fake()->image('test_preview.jpg');

        $response = $this->actingAs($this->tl)->post(route('upload.store'), [
            'file' => $file,
        ], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertDatabaseHas('drive_files', [
            'original_name' => 'test_preview.jpg',
            'file_type'     => 'photo',
        ]);
    }

    public function test_can_rename_drive_file(): void
    {
        $file = DriveFile::create([
            'uploaded_by'   => $this->tl->id,
            'original_name' => 'old_name.jpg',
            'drive_file_id' => 'local_123',
            'drive_url'     => 'https://example.com/test.jpg',
            'file_type'     => 'photo',
            'upload_date'   => '2026-09-24',
        ]);

        $response = $this->actingAs($this->tl)->putJson(route('drive.files.rename', $file), [
            'name' => 'new_renamed.jpg',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('drive_files', [
            'id'            => $file->id,
            'original_name' => 'new_renamed.jpg',
        ]);
    }

    public function test_can_move_drive_file_to_folder(): void
    {
        $folder = DriveFolder::create([
            'name'       => 'Archive',
            'created_by' => $this->tl->id,
        ]);

        $file = DriveFile::create([
            'uploaded_by'   => $this->tl->id,
            'original_name' => 'document.pdf',
            'drive_file_id' => 'local_456',
            'drive_url'     => 'https://example.com/doc.pdf',
            'file_type'     => 'document',
            'upload_date'   => '2026-09-24',
        ]);

        $response = $this->actingAs($this->tl)->putJson(route('drive.files.move', $file), [
            'folder_id' => $folder->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('drive_files', [
            'id'        => $file->id,
            'folder_id' => $folder->id,
        ]);
    }
}
