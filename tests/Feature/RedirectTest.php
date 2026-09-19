<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_visiting_root_redirects_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    public function test_guest_visiting_dashboard_redirects_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('login'));
    }

    public function test_tl_visiting_root_and_login_redirects_to_tl_dashboard(): void
    {
        $tl = User::factory()->create(['role' => 'tl', 'must_change_password' => false]);
        $this->actingAs($tl)->get('/')->assertRedirect(route('tl.dashboard'));
        $this->actingAs($tl)->get('/login')->assertRedirect(route('tl.dashboard'));
    }

    public function test_hr_visiting_root_and_login_redirects_to_hr_dashboard(): void
    {
        $hr = User::factory()->create(['role' => 'hr', 'must_change_password' => false]);
        $this->actingAs($hr)->get('/')->assertRedirect(route('hr.dashboard'));
        $this->actingAs($hr)->get('/login')->assertRedirect(route('hr.dashboard'));
    }

    public function test_ceo_visiting_root_and_login_redirects_to_ceo_dashboard(): void
    {
        $ceo = User::factory()->create(['role' => 'ceo', 'must_change_password' => false]);
        $this->actingAs($ceo)->get('/')->assertRedirect(route('ceo.dashboard'));
        $this->actingAs($ceo)->get('/login')->assertRedirect(route('ceo.dashboard'));
    }

    public function test_member_visiting_root_and_login_redirects_to_member_dashboard(): void
    {
        $member = User::factory()->create(['role' => 'member', 'must_change_password' => false]);
        $this->actingAs($member)->get('/')->assertRedirect(route('member.dashboard'));
        $this->actingAs($member)->get('/login')->assertRedirect(route('member.dashboard'));
    }

    public function test_user_with_must_change_password_redirects_to_force_change(): void
    {
        $user = User::factory()->create(['role' => 'member', 'must_change_password' => true]);
        $this->actingAs($user)->get('/')->assertRedirect(route('password.force_change'));
        $this->actingAs($user)->get('/login')->assertRedirect(route('password.force_change'));
    }
}
