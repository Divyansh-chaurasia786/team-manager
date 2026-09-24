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
    protected Client $client;
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

        $this->client = $client;
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

    /**
     * Create a folder in Google Drive.
     */
    public function createDriveFolder(string $name, ?string $parentDriveId = null): string
    {
        $targetParent = !empty($parentDriveId) ? $parentDriveId : $this->rootFolderId;

        $folderMetadata = new GoogleDriveFile([
            'name'     => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents'  => !empty($targetParent) ? [$targetParent] : [],
        ]);

        $folder = $this->drive->files->create($folderMetadata, ['fields' => 'id']);
        $folderId = $folder->getId();

        try {
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $this->drive->permissions->create($folderId, $permission);
        } catch (\Exception $e) {
            Log::info('Drive folder share permission notice: ' . $e->getMessage());
        }

        return $folderId;
    }

    /**
     * Upload an UploadedFile into Google Drive.
     * Optionally places into a specific Google Drive target folder.
     */
    public function uploadFile(UploadedFile $file, int $userId, ?string $targetDriveFolderId = null): array
    {
        $today = now()->format('Y-m-d');
        $fileType = $this->detectFileType($file);
        $fileSize = $file->getSize() ?: 0;
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';

        if (!empty($targetDriveFolderId)) {
            $destinationFolderId = $targetDriveFolderId;
        } else {
            $typeFolderName = ucfirst($fileType) . 's'; // Photos, Videos, Documents
            $dateFolderId = $this->getOrCreateFolder($today, $this->rootFolderId);
            $destinationFolderId = $this->getOrCreateFolder($typeFolderName, $dateFolderId);
        }

        $fileMetadata = new GoogleDriveFile([
            'name'    => $file->getClientOriginalName(),
            'parents' => !empty($destinationFolderId) ? [$destinationFolderId] : [],
        ]);

        $result = $this->executeUpload($fileMetadata, $file->getRealPath(), $mimeType);

        try {
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $this->drive->permissions->create($result->getId(), $permission);
        } catch (\Exception $e) {
            Log::info('Drive file share permission notice: ' . $e->getMessage());
        }

        return [
            'drive_file_id' => $result->getId(),
            'drive_url'     => $result->getWebViewLink() ?? "https://drive.google.com/file/d/{$result->getId()}/view",
            'file_type'     => $fileType,
            'file_size'     => $fileSize,
            'mime_type'     => $mimeType,
            'upload_date'   => $today,
        ];
    }

    /**
     * Upload an existing file from a local filesystem path.
     */
    public function uploadFromPath(string $absolutePath, string $originalName, int $userId, ?string $targetDriveFolderId = null): array
    {
        $today = now()->format('Y-m-d');
        $fileType = $this->detectTypeFromName($originalName, $absolutePath);
        $fileSize = file_exists($absolutePath) ? (int) filesize($absolutePath) : 0;
        $mimeType = mime_content_type($absolutePath) ?: 'application/octet-stream';

        if (!empty($targetDriveFolderId)) {
            $destinationFolderId = $targetDriveFolderId;
        } else {
            $typeFolderName = ucfirst($fileType) . 's';
            $dateFolderId = $this->getOrCreateFolder($today, $this->rootFolderId);
            $destinationFolderId = $this->getOrCreateFolder($typeFolderName, $dateFolderId);
        }

        $fileMetadata = new GoogleDriveFile([
            'name'    => $originalName,
            'parents' => !empty($destinationFolderId) ? [$destinationFolderId] : [],
        ]);

        $result = $this->executeUpload($fileMetadata, $absolutePath, $mimeType);

        try {
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $this->drive->permissions->create($result->getId(), $permission);
        } catch (\Exception $e) {
            Log::info('Drive file share permission notice: ' . $e->getMessage());
        }

        return [
            'drive_file_id' => $result->getId(),
            'drive_url'     => $result->getWebViewLink() ?? "https://drive.google.com/file/d/{$result->getId()}/view",
            'file_type'     => $fileType,
            'file_size'     => $fileSize,
            'mime_type'     => $mimeType,
            'upload_date'   => $today,
        ];
    }

    /**
     * Create a file with direct content (e.g. Note / Document) in Google Drive.
     */
    public function createDocFile(string $originalName, string $content, int $userId, ?string $targetDriveFolderId = null): array
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'drive_doc_');
        file_put_contents($tempPath, $content);

        try {
            $result = $this->uploadFromPath($tempPath, $originalName, $userId, $targetDriveFolderId);
            @unlink($tempPath);
            return $result;
        } catch (\Throwable $e) {
            @unlink($tempPath);
            throw $e;
        }
    }

    /**
     * Rename file or folder in Google Drive.
     */
    public function renameItem(string $driveId, string $newName): bool
    {
        try {
            $fileMetadata = new GoogleDriveFile(['name' => $newName]);
            $this->drive->files->update($driveId, $fileMetadata);
            return true;
        } catch (\Exception $e) {
            Log::warning('Drive rename failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Move a file or folder in Google Drive to a new parent folder.
     */
    public function moveItem(string $driveId, string $newParentDriveId): bool
    {
        try {
            $file = $this->drive->files->get($driveId, ['fields' => 'parents']);
            $previousParents = join(',', $file->getParents() ?? []);
            $this->drive->files->update($driveId, new GoogleDriveFile(), [
                'addParents'    => $newParentDriveId,
                'removeParents' => $previousParents,
                'fields'        => 'id, parents',
            ]);
            return true;
        } catch (\Exception $e) {
            Log::warning('Drive move item failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Executes upload to Google Drive.
     * Uses optimized 16MB resumable chunks for large files (> 5MB) to drastically reduce roundtrips.
     */
    protected function executeUpload(GoogleDriveFile $fileMetadata, string $filePath, string $mimeType)
    {
        $fileSize = file_exists($filePath) ? (int) filesize($filePath) : 0;

        // If file is large (> 5MB), upload in resumable 16MB chunks (multiple of 256KB)
        if ($fileSize > 5 * 1024 * 1024 && isset($this->client)) {
            try {
                $chunkSizeBytes = 16 * 1024 * 1024; // 16MB per chunk for high network throughput
                $this->client->setDefer(true);
                $request = $this->drive->files->create($fileMetadata, ['fields' => 'id, webViewLink, webContentLink']);
                $media = new \Google\Http\MediaFileUpload(
                    $this->client,
                    $request,
                    $mimeType,
                    null,
                    true,
                    $chunkSizeBytes
                );
                $media->setFileSize($fileSize);

                $status = false;
                $handle = fopen($filePath, 'rb');
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, $chunkSizeBytes);
                    $status = $media->nextChunk($chunk);
                }
                fclose($handle);
                $this->client->setDefer(false);

                if ($status && is_object($status) && method_exists($status, 'getId')) {
                    return $status;
                }
            } catch (\Throwable $e) {
                if (isset($this->client)) {
                    $this->client->setDefer(false);
                }
                Log::warning('Resumable chunked upload failed, falling back to standard upload: ' . $e->getMessage());
            }
        }

        // Standard multipart upload
        return $this->drive->files->create($fileMetadata, [
            'data'       => file_get_contents($filePath),
            'mimeType'   => $mimeType,
            'uploadType' => 'multipart',
            'fields'     => 'id, webViewLink, webContentLink',
        ]);
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

    public function getOrCreateFolder(string $name, string $parentId): string
    {
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