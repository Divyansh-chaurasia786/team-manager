<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ActivityLog;
use App\Mail\SecurityOtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Clean up test uploaded files if any
        $testUploadDir = public_path('uploads/profile_photos');
        if (File::exists($testUploadDir)) {
            foreach (File::files($testUploadDir) as $file) {
                if (str_starts_with($file->getFilename(), 'user_')) {
                    @unlink($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    public function test_user_can_access_settings_page(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->get(route('settings.index'));
        $response->assertStatus(200);
        $response->assertSee('Account Settings');
        $response->assertSee($user->email);
    }

    public function test_user_can_upload_profile_photo(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $response = $this->from(route('settings.index'))
            ->actingAs($user)
            ->post(route('settings.avatar'), [
                'profile_photo' => $file,
            ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->profile_photo_path);
        $this->assertStringStartsWith('uploads/profile_photos/', $user->profile_photo_path);
        $this->assertNotNull($user->avatar_url);

        // Check activity log created
        $log = ActivityLog::where('user_id', $user->id)
            ->where('action', 'profile_photo_updated')
            ->first();
        $this->assertNotNull($log);
    }

    public function test_request_update_requires_valid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post(route('settings.request_update'), [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
            'current_password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $user->refresh();
        $this->assertNull($user->security_otp);
    }

    public function test_request_update_stages_changes_and_sends_otp_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'original@example.com',
            'mobile_number' => '+91 9123456780',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post(route('settings.request_update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'mobile_number' => '+91 9999888877',
            'new_password' => 'newsecretpassword123',
            'new_password_confirmation' => 'newsecretpassword123',
            'current_password' => 'password123',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('otp_sent');

        $user->refresh();
        $this->assertNotNull($user->security_otp);
        $this->assertEquals(6, strlen($user->security_otp));
        $this->assertNotNull($user->security_otp_expires_at);
        $this->assertTrue($user->security_otp_expires_at->isFuture());

        $this->assertIsArray($user->pending_profile_update);
        $this->assertEquals('Updated Name', $user->pending_profile_update['name']);
        $this->assertEquals('updated@example.com', $user->pending_profile_update['email']);
        $this->assertEquals('+91 9999888877', $user->pending_profile_update['mobile_number']);
        $this->assertTrue(Hash::check('newsecretpassword123', $user->pending_profile_update['new_password_hashed']));

        Mail::assertSent(SecurityOtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->otp === $user->security_otp;
        });
    }

    public function test_invalid_otp_fails_verification(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'security_otp' => '123456',
            'security_otp_expires_at' => now()->addMinutes(10),
            'pending_profile_update' => ['name' => 'New Name'],
        ]);

        $response = $this->actingAs($user)->post(route('settings.verify_otp'), [
            'otp' => '999999',
        ]);

        $response->assertSessionHasErrors(['otp']);
        $user->refresh();
        $this->assertNotNull($user->security_otp);
    }

    public function test_valid_otp_applies_changes_and_clears_otp(): void
    {
        $tl = User::factory()->create([
            'role' => 'tl',
            'email' => 'tl@example.com',
        ]);

        $member = User::factory()->create([
            'role' => 'member',
            'created_by' => $tl->id,
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'mobile_number' => '+91 1111111111',
            'password' => Hash::make('oldpassword'),
            'security_otp' => '654321',
            'security_otp_expires_at' => now()->addMinutes(10),
            'pending_profile_update' => [
                'name' => 'Brand New Name',
                'email' => 'newemail@example.com',
                'mobile_number' => '+91 9876543210',
                'new_password_hashed' => Hash::make('brandnewpass123'),
            ],
        ]);

        $response = $this->actingAs($member)->post(route('settings.verify_otp'), [
            'otp' => '654321',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        $member->refresh();
        $this->assertEquals('Brand New Name', $member->name);
        $this->assertEquals('newemail@example.com', $member->email);
        $this->assertEquals('+91 9876543210', $member->mobile_number);
        $this->assertTrue(Hash::check('brandnewpass123', $member->password));
        $this->assertNull($member->security_otp);
        $this->assertNull($member->security_otp_expires_at);
        $this->assertNull($member->pending_profile_update);

        $log = ActivityLog::where('user_id', $member->id)
            ->where('action', 'profile_settings_updated')
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('updated their account settings with OTP verification', $log->description);
        $this->assertStringContainsString('newemail@example.com', $log->description);
    }

    public function test_in_page_ajax_otp_flow_and_cancellation(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'ajax_test@example.com',
            'password' => Hash::make('mypassword123'),
        ]);

        // 1. AJAX request update
        $response = $this->actingAs($user)->postJson(route('settings.request_update'), [
            'name' => 'Ajax Name',
            'email' => 'ajax_new@example.com',
            'mobile_number' => '+91 8888877777',
            'current_password' => 'mypassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'otp_sent' => true,
        ]);

        $user->refresh();
        $otp = $user->security_otp;
        $this->assertNotNull($otp);

        // 2. User can cancel pending update
        $cancelResponse = $this->actingAs($user)->postJson(route('settings.cancel_pending'));
        $cancelResponse->assertStatus(200);
        $cancelResponse->assertJson(['success' => true]);

        $user->refresh();
        $this->assertNull($user->security_otp);
        $this->assertNull($user->pending_profile_update);
    }
}

