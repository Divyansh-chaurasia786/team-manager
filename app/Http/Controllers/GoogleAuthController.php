<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Oauth2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public static function getTokenPath(): string
    {
        return storage_path('app/google_drive_token.json');
    }

    public static function getServiceAccountData(): ?array
    {
        $envJson = env('GOOGLE_SERVICE_ACCOUNT_JSON');
        if (!empty($envJson)) {
            $data = json_decode($envJson, true);
            if (is_array($data) && !empty($data['client_email'])) {
                return $data;
            }
            $decoded = base64_decode($envJson, true);
            if ($decoded) {
                $data = json_decode($decoded, true);
                if (is_array($data) && !empty($data['client_email'])) {
                    return $data;
                }
            }
        }

        $credPath = base_path('credentials.json');
        if (file_exists($credPath)) {
            $data = json_decode(file_get_contents($credPath), true);
            if (is_array($data) && ($data['type'] ?? '') === 'service_account') {
                return $data;
            }
        }

        return null;
    }

    public static function isConnected(): bool
    {
        $path = self::getTokenPath();
        if (file_exists($path)) {
            $tokenData = json_decode(file_get_contents($path), true);
            if (!empty($tokenData['access_token']) || !empty($tokenData['refresh_token'])) {
                return true;
            }
        }

        return false;
    }

    public static function getConnectedAccount(): ?array
    {
        $path = self::getTokenPath();
        if (file_exists($path)) {
            $tokenData = json_decode(file_get_contents($path), true);
            if (!empty($tokenData['user_info'])) {
                return $tokenData['user_info'];
            }
            if (!empty($tokenData['access_token'])) {
                return [
                    'email' => $tokenData['email'] ?? 'Google Drive Account',
                    'name'  => 'Connected Google Account',
                    'type'  => 'OAuth Account',
                ];
            }
        }

        $sa = self::getServiceAccountData();
        if ($sa) {
            return [
                'email' => $sa['client_email'],
                'name'  => 'EcoFone Drive Bot (' . ($sa['project_id'] ?? 'Google Cloud') . ')',
                'type'  => 'Service Account',
                'is_service_account' => true,
            ];
        }

        return null;
    }

    public function getClient(): Client
    {
        $client = new Client();
        
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri');

        // Check if client credentials are in an oauth_credentials.json file or in .env
        $oauthJson = base_path('oauth_credentials.json');
        if (file_exists($oauthJson)) {
            $client->setAuthConfig($oauthJson);
        } elseif (!empty($clientId) && !empty($clientSecret)) {
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri($redirectUri);
        } else {
            // Fallback to credentials.json if it contains web credentials
            $credJson = base_path('credentials.json');
            if (file_exists($credJson)) {
                $data = json_decode(file_get_contents($credJson), true);
                if (isset($data['web']) || isset($data['installed'])) {
                    $client->setAuthConfig($credJson);
                }
            }
        }

        $client->setRedirectUri($redirectUri);
        $client->addScope([
            Drive::DRIVE,
            'email',
            'profile',
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $caPath = base_path('cacert.pem');
        $guzzle = new \GuzzleHttp\Client([
            'verify' => file_exists($caPath) ? $caPath : true,
        ]);
        $client->setHttpClient($guzzle);

        return $client;
    }

    public function connect()
    {
        $client = $this->getClient();

        if (empty($client->getClientId())) {
            return back()->with('error', 'Google OAuth Client ID is not configured yet. Please configure your OAuth credentials.');
        }

        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('upload.index')->with('error', 'Google connection was cancelled: ' . $request->get('error'));
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('upload.index')->with('error', 'No authorization code received from Google.');
        }

        try {
            $client = $this->getClient();
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                return redirect()->route('upload.index')->with('error', 'OAuth Token Error: ' . ($token['error_description'] ?? $token['error']));
            }

            $client->setAccessToken($token);

            // Fetch user info from Google
            $oauth2 = new Oauth2($client);
            $googleUser = $oauth2->userinfo->get();

            $tokenPayload = array_merge($token, [
                'user_info' => [
                    'email'   => $googleUser->getEmail(),
                    'name'    => $googleUser->getName(),
                    'picture' => $googleUser->getPicture(),
                ],
                'connected_at' => now()->toIso8601String(),
                'connected_by' => Auth::user()?->name ?? 'System',
            ]);

            // Ensure directory exists
            $dir = dirname(self::getTokenPath());
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents(self::getTokenPath(), json_encode($tokenPayload, JSON_PRETTY_PRINT));

            // Log activity
            ActivityLog::log(
                action: 'google_drive_connected',
                description: sprintf('%s connected Google Drive account (%s)', Auth::user()?->name ?? 'User', $googleUser->getEmail()),
                entityType: 'System'
            );

            // Trigger root folder auto-creation in the connected user's Drive!
            try {
                $driveService = new \App\Services\DriveService();
                $driveService->resolveRootFolderId();
            } catch (\Exception $e) {
                Log::info('Drive root init notice: ' . $e->getMessage());
            }

            return redirect()->route('upload.index')->with('success', "Google Drive connected successfully as {$googleUser->getEmail()}! All folders will now be created automatically.");
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed: ' . $e->getMessage());
            return redirect()->route('upload.index')->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        $path = self::getTokenPath();
        if (file_exists($path)) {
            @unlink($path);
        }

        ActivityLog::log(
            action: 'google_drive_disconnected',
            description: sprintf('%s disconnected Google Drive account', Auth::user()?->name ?? 'User'),
            entityType: 'System'
        );

        return back()->with('success', 'Google Drive has been disconnected.');
    }
}
