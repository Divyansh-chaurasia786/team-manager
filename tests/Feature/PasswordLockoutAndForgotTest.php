<?php

namespace Tests\Feature;

use App\Mail\ForgotPasswordMail;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\Task;
use App\Models\User;
use App\Models\WeeklyPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordLockoutAndForgotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_failed_login_tracks_attempts_and_locks_after_five_failures(): void
    {
        $user = User::create([
            'name'                  => 'Test Officer',
            'username'              => 'test.officer',
            'email'                 => 'officer@ecofone.com',
            'password'              => Hash::make('correct_password'),
            'role'                  => 'member',
            'failed_login_attempts' => 0,
        ]);

        // Attempt 1 to 4: should fail and decrement remaining attempts
        for ($i = 1; $i <= 4; $i++) {
            $response = $this->post(route('login'), [
                'login'    => 'officer@ecofone.com',
                'password' => 'wrong_pass_' . $i,
            ]);

            $response->assertSessionHasErrors('login');
            $user->refresh();
            $this->assertEquals($i, $user->failed_login_attempts);
            $this->assertFalse($user->isLocked());
        }

        // Attempt 5: triggers 24-hour lockout
        $response5 = $this->post(route('login'), [
            'login'    => 'officer@ecofone.com',
            'password' => 'wrong_pass_5',
        ]);

        $response5->assertSessionHasErrors('login');
        $user->refresh();
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertTrue($user->isLocked());
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());

        // Attempt 6 (even with correct password) while locked: must be blocked
        $response6 = $this->post(route('login'), [
            'login'    => 'officer@ecofone.com',
            'password' => 'correct_password',
        ]);

        $response6->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_successful_login_resets_failed_attempts(): void
    {
        $user = User::create([
            'name'                  => 'Reset User',
            'username'              => 'reset.user',
            'email'                 => 'resetuser@ecofone.com',
            'password'              => Hash::make('valid_password'),
            'role'                  => 'member',
            'failed_login_attempts' => 3,
        ]);

        $response = $this->post(route('login'), [
            'login'    => 'resetuser@ecofone.com',
            'password' => 'valid_password',
        ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_ten_day_otp_expiration_blocks_expired_passwords(): void
    {
        // Expired OTP user (created 11 days ago)
        $expiredUser = User::create([
            'name'                 => 'Expired User',
            'username'             => 'expired.user',
            'email'                => 'expired@ecofone.com',
            'password'             => Hash::make('temp_otp_123'),
            'role'                 => 'member',
            'must_change_password' => true,
            'otp_expires_at'       => now()->subDay(),
        ]);

        $response = $this->post(route('login'), [
            'login'    => 'expired.user',
            'password' => 'temp_otp_123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();

        // Valid OTP user (within 10 days)
        $validUser = User::create([
            'name'                 => 'Valid User',
            'username'             => 'valid.user',
            'email'                => 'valid@ecofone.com',
            'password'             => Hash::make('temp_otp_456'),
            'role'                 => 'member',
            'must_change_password' => true,
            'otp_expires_at'       => now()->addDays(9),
        ]);

        $responseValid = $this->post(route('login'), [
            'login'    => 'valid.user',
            'password' => 'temp_otp_456',
        ]);

        $responseValid->assertRedirect(route('password.force_change'));
        $this->assertAuthenticatedAs($validUser);
    }

    public function test_forgot_password_generates_otp_and_emails_user(): void
    {
        $user = User::create([
            'name'     => 'Sarah Connor',
            'username' => 'sarah.c',
            'email'    => 'sarah@ecofone.com',
            'password' => Hash::make('old_secret'),
            'role'     => 'member',
        ]);

        $response = $this->post(route('password.email'), [
            'login' => 'sarah.c',
        ]);

        $response->assertRedirect(route('password.reset'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->password_reset_otp);
        $this->assertEquals(6, strlen($user->password_reset_otp));
        $this->assertNotNull($user->password_reset_otp_expires_at);
        $this->assertTrue($user->password_reset_otp_expires_at->isFuture());

        Mail::assertSent(ForgotPasswordMail::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });
    }

    public function test_password_reset_unlocks_account_and_preserves_all_historical_data(): void
    {
        $tl = User::create([
            'name'     => 'Lead User',
            'username' => 'lead.user',
            'email'    => 'lead@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $user = User::create([
            'name'                  => 'John Member',
            'username'              => 'john.member',
            'email'                 => 'john@ecofone.com',
            'password'              => Hash::make('initial_secret'),
            'role'                  => 'member',
            'failed_login_attempts' => 5,
            'locked_until'          => now()->addHours(24),
            'password_reset_otp'    => '987654',
            'password_reset_otp_expires_at' => now()->addMinutes(25),
        ]);

        // Attach historical tasks, attendance, leaves, weekly plans
        $task = Task::create([
            'title'       => 'Crucial Production Task',
            'description' => 'Important operations report',
            'assigned_to' => $user->id,
            'assigned_by' => $tl->id,
            'deadline'    => now()->addDays(3),
            'status'      => 'in-progress',
        ]);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'marked_by' => $tl->id,
            'date'      => now()->toDateString(),
            'status'    => 'present',
        ]);

        $leave = LeaveApplication::create([
            'user_id'    => $user->id,
            'leave_type' => 'sick',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date'   => now()->addDays(12)->toDateString(),
            'total_days' => 3,
            'reason'     => 'Medical checkup',
            'status'     => 'pending',
        ]);

        // Post new password with valid OTP
        $response = $this->post(route('password.update'), [
            'email'                 => 'john@ecofone.com',
            'otp'                   => '987654',
            'password'              => 'NewSecurePass@2026',
            'password_confirmation' => 'NewSecurePass@2026',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $user->refresh();

        // 1. Verify Password & Security status
        $this->assertTrue(Hash::check('NewSecurePass@2026', $user->password));
        $this->assertNull($user->password_reset_otp);
        $this->assertNull($user->password_reset_otp_expires_at);
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
        $this->assertFalse($user->isLocked());

        // 2. CRITICAL: Verify ZERO DATA LAPSE - all related records intact
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'assigned_to' => $user->id]);
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('leave_applications', ['id' => $leave->id, 'user_id' => $user->id]);

        // 3. User can now login immediately with new password
        $loginResponse = $this->post(route('login'), [
            'login'    => 'john.member',
            'password' => 'NewSecurePass@2026',
        ]);

        $loginResponse->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_reset_with_invalid_or_expired_otp_fails(): void
    {
        $user = User::create([
            'name'                  => 'OTP User',
            'username'              => 'otp.user',
            'email'                 => 'otpuser@ecofone.com',
            'password'              => Hash::make('validpass'),
            'role'                  => 'member',
            'password_reset_otp'    => '112233',
            'password_reset_otp_expires_at' => now()->subMinutes(5), // expired
        ]);

        // Expired OTP
        $resExpired = $this->post(route('password.update'), [
            'email'                 => 'otpuser@ecofone.com',
            'otp'                   => '112233',
            'password'              => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $resExpired->assertSessionHasErrors('otp');

        // Invalid OTP
        $resInvalid = $this->post(route('password.update'), [
            'email'                 => 'otpuser@ecofone.com',
            'otp'                   => '999999',
            'password'              => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $resInvalid->assertSessionHasErrors('otp');
    }
}
