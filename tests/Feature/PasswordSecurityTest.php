<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    use RefreshDatabase;
    public function test_all_existing_users_have_bcrypt_hashed_passwords_and_no_plain_passwords(): void
    {
        $this->seed();
        $users = User::all();
        $this->assertNotEmpty($users);

        foreach ($users as $user) {
            $this->assertNull($user->temp_password_plain, "User {$user->email} has plain text password stored!");
            $this->assertTrue(
                str_starts_with($user->password, '$2y$') || str_starts_with($user->password, '$argon2'),
                "User {$user->email} password is not securely hashed!"
            );
        }
    }

    public function test_login_page_does_not_contain_plaintext_password_hints(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertDontSee('password123');
        $response->assertDontSee('tl@ecofone.com / password123');
        $response->assertSee('Cryptographic Hash Security');
        $response->assertSee('name="password"', false);
    }

    public function test_registering_member_stores_only_hashed_password(): void
    {
        $tl = User::where('role', 'tl')->first();
        if (!$tl) {
            $tl = User::create([
                'name' => 'Team Lead',
                'username' => 'tl.security',
                'email' => 'tl_sec@ecofone.com',
                'password' => Hash::make('password123'),
                'role' => 'tl',
            ]);
        }

        $response = $this->actingAs($tl)->post(route('tl.members.store'), [
            'name'          => 'Security Member',
            'email'         => 'sec_member_' . time() . '@ecofone.com',
            'mobile_number' => '+91 9988776655',
            'designation'   => 'Security Tester',
        ]);

        $response->assertSessionHas('new_member');
        $newMember = User::where('email', 'like', 'sec_member_%')->latest()->first();

        $this->assertNotNull($newMember);
        $this->assertNull($newMember->temp_password_plain);
        $this->assertTrue(Hash::check(session('new_member')['otp'], $newMember->password));
        $this->assertTrue(str_starts_with($newMember->password, '$2y$'));
    }

    public function test_force_change_password_page_has_hidden_password_fields(): void
    {
        $member = User::where('role', 'member')->first();
        if (!$member) {
            $member = User::create([
                'name'                 => 'Member Test',
                'username'             => 'member.test',
                'email'                => 'member_test@ecofone.com',
                'password'             => Hash::make('secret123'),
                'must_change_password' => true,
                'role'                 => 'member',
            ]);
        } else {
            $member->update(['must_change_password' => true]);
        }

        $response = $this->actingAs($member)->get(route('password.force_change'));
        $response->assertStatus(200);
        $response->assertSee('name="current_password"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('showCurrent');
        $response->assertSee('showNew');
        $response->assertSee('showConfirm');
    }
}
