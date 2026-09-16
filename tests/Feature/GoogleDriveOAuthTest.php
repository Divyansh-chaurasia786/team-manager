<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GoogleDriveOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_connect_redirects_to_accounts_google_com(): void
    {
        $tl = User::create([
            'name'     => 'Team Lead',
            'username' => 'tl.test',
            'email'    => 'tl_test@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $response = $this->actingAs($tl)->get(route('google.connect'));

        // Assert redirect to accounts.google.com with correct client_id and scopes
        $response->assertStatus(302);
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com', $redirectUrl);
        $this->assertStringContainsString('889086733617-qgjkcasatnh0k1obs15im8m299j71ltp.apps.googleusercontent.com', $redirectUrl);
        $this->assertStringContainsString('drive', $redirectUrl);
    }

    public function test_upload_page_displays_connect_google_drive_button_when_not_connected(): void
    {
        $tl = User::create([
            'name'     => 'Team Lead',
            'username' => 'tl.test2',
            'email'    => 'tl_test2@ecofone.com',
            'password' => Hash::make('password123'),
            'role'     => 'tl',
        ]);

        $response = $this->actingAs($tl)->get(route('upload.index'));
        $response->assertStatus(200);
        $response->assertSee('Connect Your Google Drive');
        $response->assertSee(route('google.connect'));
    }
}
