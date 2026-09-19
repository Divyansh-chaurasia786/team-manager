<?php
namespace App\Services;

use App\Models\DriveFile;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile as GoogleDriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class DriveService
{
    protected Drive $drive;
    protected string $rootFolderId;

    public function __construct()
    {
        $client = new Client();
        $caPath = base_path('cacert.pem');
        $guzzle = new \GuzzleHttp\Client([
            'verify' => file_exists($caPath) ? $caPath : true,
        ]);
        $client->setHttpClient($guzzle);
        $client->addScope(Drive::DRIVE);

        $tokenPath = \App\Http\Controllers\GoogleAuthController::getTokenPath();
        if (file_exists($tokenPath)) {
            $tokenData = json_decode(file_get_contents($tokenPath), true);

            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');
            $oauthJson = base_path('oauth_credentials.json');

            if (file_exists($oauthJson)) {
                $client->setAuthConfig($oauthJson);
            } elseif (!empty($clientId) && !empty($clientSecret)) {
                $client->setClientId($clientId);
                $client->setClientSecret($clientSecret);
            }

            $client->setAccessToken($tokenData);

            if ($client->isAccessTokenExpired() && !empty($tokenData['refresh_token'])) {
                try {
                    $newToken = $client->fetchAccessTokenWithRefreshToken($tokenData['refresh_token']);
                    if (!isset($newToken['error'])) {
                        $updatedPayload = array_merge($tokenData, $newToken);
                        file_put_contents($tokenPath, json_encode($updatedPayload, JSON_PRETTY_PRINT));
                        $client->setAccessToken($updatedPayload);
                    }
                } catch (\Exception $e) {
                    Log::warning('Drive token auto-refresh failed: ' . $e->getMessage());
                }
            }
        } else {
            $sa = \App\Http\Controllers\GoogleAuthController::getServiceAccountData();
            if ($sa) {
                $client->setAuthConfig($sa);
            }
        }

        $this->drive = new Drive($client);
        $this->rootFolderId = $this->resolveRootFolderId();
    }

    public function resolveRootFolderId(): string
    {
        $configuredId = config('services.google.drive_folder_id');
        if (!empty($configuredId)) {
            return $configuredId;
        }

        try {
            $query = "name='EcoFone Operations Drive' and mimeType='application/vnd.google-apps.folder' and trashed=false";
            $res = $this->drive->files->listFiles(['q' => $query, 'fields' => 'files(id, name)']);
            if (count($res->getFiles()) > 0) {
                return $res->getFiles()[0]->getId();
            }

            $folderMetadata = new GoogleDriveFile([
                'name'     => 'EcoFone Operations Drive',
                'mimeType' => 'application/vnd.google-apps.folder',
            ]);
            $created = $this->drive->files->create($folderMetadata, ['fields' => 'id']);
            $rootId = $created->getId();

            try {
                $userPermission = new \Google\Service\Drive\Permission([
                    'type'         => 'user',
                    'role'         => 'writer',
                    'emailAddress' => 'divyanshecofone@gmail.com',
                ]);
                $this->drive->permissions->create($rootId, $userPermission, ['sendNotificationEmail' => false]);
            } catch (\Exception $e) {
                Log::info('Drive auto-share user notice: ' . $e->getMessage());
            }

            try {
                $linkPermission = new \Google\Service\Drive\Permission([
                    'type' => 'anyone',
                    'role' => 'reader',
                ]);
                $this->drive->permissions->create($rootId, $linkPermission);
            } catch (\Exception $e) {
                Log::info('Drive auto-share link notice: ' . $e->getMessage());
            }

            return $rootId;
        } catch (\Exception $e) {
            Log::warning('Drive auto-folder resolution failed: ' . $e->getMessage());
            return '';
        }
    }

    public function uploadFile(UploadedFile $file, int $userId): array
    {
        $today = now()->format('Y-m-d');
        $fileType = $this->detectFileType($file);
        $typeFolderName = ucfirst($fileType) . 's'; // Photos, Videos, Documents

        // Get or create date folder
        $dateFolderId = $this->getOrCreateFolder($today, $this->rootFolderId);

        // Get or create type subfolder inside date folder
        $typeFolderId = $this->getOrCreateFolder($typeFolderName, $dateFolderId);

        // Upload file to type folder
        $fileMetadata = new GoogleDriveFile([
            'name'    => $file->getClientOriginalName(),
            'parents' => [$typeFolderId],
        ]);

        $result = $this->drive->files->create($fileMetadata, [
            'data'       => file_get_contents($file->getRealPath()),
            'mimeType'   => $file->getMimeType(),
            'uploadType' => 'multipart',
            'fields'     => 'id, webViewLink, webContentLink',
        ]);

        // Make file viewable and downloadable by anyone with link
        $permission = new \Google\Service\Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);
        $this->drive->permissions->create($result->getId(), $permission);

        return [
            'drive_file_id' => $result->getId(),
            'drive_url'     => $result->getWebViewLink() ?? "https://drive.google.com/file/d/{$result->getId()}/view",
            'file_type'     => $fileType,
            'upload_date'   => $today,
        ];
    }

    public function uploadFromPath(string $absolutePath, string $originalName, int $userId): array
    {
        $today = now()->format('Y-m-d');
        $fileType = $this->detectTypeFromName($originalName, $absolutePath);
        $typeFolderName = ucfirst($fileType) . 's'; // Photos, Videos, Documents

        // Get or create date folder
        $dateFolderId = $this->getOrCreateFolder($today, $this->rootFolderId);

        // Get or create type subfolder inside date folder
        $typeFolderId = $this->getOrCreateFolder($typeFolderName, $dateFolderId);

        $mimeType = mime_content_type($absolutePath) ?: 'application/octet-stream';

        // Upload file to type folder
        $fileMetadata = new GoogleDriveFile([
            'name'    => $originalName,
            'parents' => [$typeFolderId],
        ]);

        $result = $this->drive->files->create($fileMetadata, [
            'data'       => file_get_contents($absolutePath),
            'mimeType'   => $mimeType,
            'uploadType' => 'multipart',
            'fields'     => 'id, webViewLink, webContentLink',
        ]);

        // Make file viewable and downloadable by anyone with link
        $permission = new \Google\Service\Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);
        $this->drive->permissions->create($result->getId(), $permission);

        return [
            'drive_file_id' => $result->getId(),
            'drive_url'     => $result->getWebViewLink() ?? "https://drive.google.com/file/d/{$result->getId()}/view",
            'file_type'     => $fileType,
            'upload_date'   => $today,
        ];
    }

    public function getDirectDownloadUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?export=download&id={$fileId}";
    }

    public function downloadContent(string $fileId)
    {
        $response = $this->drive->files->get($fileId, ['alt' => 'media']);
        return $response->getBody()->getContents();
    }

    public function deleteFile(string $fileId): bool
    {
        try {
            $this->drive->files->delete($fileId);
            return true;
        } catch (\Exception $e) {
            Log::warning("Drive delete file failed: " . $e->getMessage());
            return false;
        }
    }

    protected function getOrCreateFolder(string $name, string $parentId): string
    {
        // Check if folder already exists
        $query = sprintf(
            "name='%s' and mimeType='application/vnd.google-apps.folder' and '%s' in parents and trashed=false",
            addslashes($name),
            $parentId
        );

        $results = $this->drive->files->listFiles([
            'q'      => $query,
            'fields' => 'files(id, name)',
        ]);

        if (count($results->getFiles()) > 0) {
            return $results->getFiles()[0]->getId();
        }

        // Create new folder
        $folderMetadata = new GoogleDriveFile([
            'name'     => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents'  => [$parentId],
        ]);

        $folder = $this->drive->files->create($folderMetadata, ['fields' => 'id']);
        return $folder->getId();
    }

    public function detectTypeFromName(string $filename, ?string $path = null): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = ($path && file_exists($path)) ? (mime_content_type($path) ?: '') : '';

        if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','svg'])) {
            return 'photo';
        }
        if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4','mov','avi','mkv','webm','flv','wmv'])) {
            return 'video';
        }
        return 'document';
    }

    protected function detectFileType(UploadedFile $file): string
    {
        return $this->detectTypeFromName($file->getClientOriginalName(), $file->getRealPath());
    }
}