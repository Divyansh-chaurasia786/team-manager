<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TeamThought;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeamThoughtReactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $tl;
    protected User $member;
    protected User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tl = User::create([
            'name'                 => 'Team Lead',
            'username'             => 'tl_reaction_user',
            'email'                => 'tl_reaction@example.com',
            'password'             => Hash::make('password123'),
            'role'                 => 'tl',
            'must_change_password' => false,
        ]);

        $this->member = User::create([
            'name'                 => 'John Member',
            'username'             => 'john_reaction_user',
            'email'                => 'john_reaction@example.com',
            'password'             => Hash::make('password123'),
            'role'                 => 'member',
            'created_by'           => $this->tl->id,
            'must_change_password' => false,
        ]);

        $this->hr = User::create([
            'name'                 => 'HR Manager',
            'username'             => 'hr_reaction_user',
            'email'                => 'hr_reaction@example.com',
            'password'             => Hash::make('password123'),
            'role'                 => 'hr',
            'must_change_password' => false,
        ]);
    }

    public function test_user_can_react_with_quick_emoji_and_toggle_off(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Hello world message',
            'media_type' => 'none',
        ]);

        // 1. Add reaction 👍
        $response = $this->actingAs($this->member)->postJson("/thoughts/{$thought->id}/react", [
            'emoji' => '👍',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('reactions.0.emoji', '👍');
        $response->assertJsonPath('reactions.0.user_id', $this->member->id);
        $response->assertJsonPath('thought.reactions.0.emoji', '👍');

        // 2. Toggle same reaction to remove it
        $response2 = $this->actingAs($this->member)->postJson("/thoughts/{$thought->id}/react", [
            'emoji' => '👍',
        ]);

        $response2->assertStatus(200);
        $response2->assertJsonPath('success', true);
        $this->assertEmpty($response2->json('reactions'));
    }

    public function test_user_can_react_with_custom_emoji_via_plus_picker(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Great deployment today!',
            'media_type' => 'none',
        ]);

        // React with custom rocket emoji 🚀
        $response = $this->actingAs($this->tl)->postJson("/thoughts/{$thought->id}/react", [
            'emoji' => '🚀',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('reactions.0.emoji', '🚀');
        $response->assertJsonPath('reactions.0.user_id', $this->tl->id);
    }

    public function test_user_can_switch_reaction_to_different_emoji(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Check this design update',
            'media_type' => 'none',
        ]);

        // First react with ❤️
        $this->actingAs($this->member)->postJson("/thoughts/{$thought->id}/react", [
            'emoji' => '❤️',
        ]);

        // Switch reaction to 🔥
        $response = $this->actingAs($this->member)->postJson("/thoughts/{$thought->id}/react", [
            'emoji' => '🔥',
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('reactions'));
        $response->assertJsonPath('reactions.0.emoji', '🔥');
    }

    public function test_polling_returns_updated_messages_for_live_reaction_sync(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Testing live reaction updates',
            'media_type' => 'none',
        ]);

        // User reacts
        $this->actingAs($this->tl)->postJson("/thoughts/{$thought->id}/react", [
            'emoji' => '🙏',
        ]);

        // Polling from another client with after_id >= thought->id
        $pollResponse = $this->actingAs($this->member)->getJson(
            route('thoughts.messages', ['group' => 'team', 'after_id' => $thought->id])
        );

        $pollResponse->assertStatus(200);
        $pollResponse->assertJsonPath('success', true);
        $updated = $pollResponse->json('updated_messages');
        $this->assertNotEmpty($updated);
        $this->assertEquals($thought->id, $updated[0]['id']);
        $this->assertEquals('🙏', $updated[0]['reactions'][0]['emoji']);
    }

    public function test_hr_cannot_react_in_private_team_chat(): void
    {
        $thought = TeamThought::create([
            'user_id'    => $this->member->id,
            'tl_id'      => $this->tl->id,
            'group_type' => 'team',
            'content'    => 'Private internal team discussion',
            'media_type' => 'none',
        ]);

        $response = $this->actingAs($this->hr)->postJson("/thoughts/{$thought->id}/react", [
            'emoji' => '👍',
        ]);

        $response->assertStatus(403);
    }
}
