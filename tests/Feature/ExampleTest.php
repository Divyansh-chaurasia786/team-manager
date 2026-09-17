<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_db_health_endpoint(): void
    {
        $response = $this->get('/db-health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'connected',
            'default_connection',
            'database_name',
            'tables_count',
            'users_count',
        ]);
    }
}
