<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $ceo;
    protected User $hr;
    protected User $tl;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ceo = User::factory()->create(['name' => 'CEO User', 'role' => 'ceo', 'email' => 'ceo@example.com']);
        $this->hr = User::factory()->create(['name' => 'HR User', 'role' => 'hr', 'email' => 'hr@example.com']);
        $this->tl = User::factory()->create(['name' => 'TL User', 'role' => 'tl', 'email' => 'tl@example.com']);
        $this->member = User::factory()->create(['name' => 'Member User', 'role' => 'member', 'email' => 'member@example.com', 'created_by' => $this->tl->id]);
    }

    public function test_public_and_guest_routes(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_ceo_portal_routes(): void
    {
        $this->actingAs($this->ceo)->get('/ceo/dashboard')->assertOk();
        $this->actingAs($this->ceo)->get('/tasks')->assertOk();
        $this->actingAs($this->ceo)->get('/leaves')->assertOk();
        $this->actingAs($this->ceo)->get('/shoots')->assertOk();
        $this->actingAs($this->ceo)->get('/plans')->assertOk();
        $this->actingAs($this->ceo)->get('/thoughts')->assertOk();
        $this->actingAs($this->ceo)->get('/history')->assertOk();
        $this->actingAs($this->ceo)->get('/settings')->assertOk();
    }

    public function test_hr_portal_routes(): void
    {
        $this->actingAs($this->hr)->get('/hr/dashboard')->assertOk();
        $this->actingAs($this->hr)->get('/hr/members')->assertOk();
        $this->actingAs($this->hr)->get('/leaves')->assertOk();
        $this->actingAs($this->hr)->get('/attendance')->assertOk();
        $this->actingAs($this->hr)->get('/settings')->assertOk();
    }

    public function test_tl_portal_routes(): void
    {
        $this->actingAs($this->tl)->get('/tl/dashboard')->assertOk();
        $this->actingAs($this->tl)->get('/tl/members')->assertOk();
        $this->actingAs($this->tl)->get('/tasks')->assertOk();
        $this->actingAs($this->tl)->get('/attendance')->assertOk();
        $this->actingAs($this->tl)->get('/leaves')->assertOk();
        $this->actingAs($this->tl)->get('/shoots')->assertOk();
        $this->actingAs($this->tl)->get('/plans')->assertOk();
        $this->actingAs($this->tl)->get('/thoughts')->assertOk();
        $this->actingAs($this->tl)->get('/history')->assertOk();
        $this->actingAs($this->tl)->get('/settings')->assertOk();
    }

    public function test_member_portal_routes(): void
    {
        $this->actingAs($this->member)->get('/member/dashboard')->assertOk();
        $this->actingAs($this->member)->get('/tasks')->assertOk();
        $this->actingAs($this->member)->get('/attendance')->assertOk();
        $this->actingAs($this->member)->get('/leaves')->assertOk();
        $this->actingAs($this->member)->get('/shoots')->assertOk();
        $this->actingAs($this->member)->get('/plans')->assertOk();
        $this->actingAs($this->member)->get('/thoughts')->assertOk();
        $this->actingAs($this->member)->get('/settings')->assertOk();
    }
}
