<?php
namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\ActivityLog;
use App\Services\DriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tl = $user->isTL() ? $user : ($user->creator ?? $user);

        $currentFolderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $currentFolder = null;
        $breadcrumbs = [];

        if ($currentFolderId) {
            $currentFolder = DriveFolder::with('parent')->find($currentFolderId);
            if ($currentFolder) {
                $breadcrumbs = $currentFolder->getBreadcrumbs();
            } else {
                $currentFolderId = null;
            }
        }

        // Subfolders in current scope
        $foldersQuery = DriveFolder::with('creator')
            ->withCount('files')
            ->where('parent_id', $currentFolderId);

        // Files in current scope
        $filesQuery = DriveFile::with('uploader')
            ->where(function ($q) use ($tl) {
                $q->where('uploaded_by', $tl->id)
                  ->orWhereHas('uploader', function ($uq) use ($tl) {
                      $uq->where('created_by', $tl->id)->orWhere('id', $tl->id);
                  });
            });

        // Filter files by folder
        if ($currentFolderId) {
            $filesQuery->where('folder_id', $currentFolderId);
        } else {
            // At root, show files with no folder assigned or all root files
            $filesQuery->whereNull('folder_id');
        }

        // Type filter
        if ($request->filled('type') && in_array($request->type, ['photo', 'video', 'document'])) {
            $filesQuery->where('file_type', $request->type);
        }

        // Search filter
        if ($request->filled('search')) {
            $term = $request->search;
            $filesQuery->where(function ($q) use ($term) {
                $q->where('original_name', 'like', "%{$term}%")
                  ->orWhere('upload_date', 'like', "%{$term}%");
            });

            $foldersQuery->where('name', 'like', "%{$term}%");
        }

        $folders = $foldersQuery->orderBy('name')->get();
        $recentFiles = $filesQuery->latest()->get();

        // All folders for Move modal
        $allFolders = DriveFolder::orderBy('name')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'currentFolder' => $currentFolder,
                'breadcrumbs'   => $breadcrumbs,
                'folders'       => $folders,
                'files'         => $recentFiles,
            ]);
        }

        return view('shared.upload', compact('recentFiles', 'folders', 'currentFolder', 'breadcrumbs', 'allFolders'));
    }

    public function store(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '2048M');

        $request->validate([
            'file'      => 'nullable|file', // No file size limit on Google Drive upload
            'files.*'   => 'nullable|file',
            'folder_id' => 'nullable|exists:drive_folders,id',
        ]);

        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $filesToUpload = [];

        if ($request->hasFile('files')) {
            $filesToUpload = $request->file('files');
        } elseif ($request->hasFile('file')) {
            $filesToUpload = [$request->file('file')];
        }

        if (empty($filesToUpload)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'No file was provided.'], 422);
            }
            return back()->with('error', 'Please choose a file to upload.');
        }

        $today = now()->format('Y-m-d');
        $uploadedRecords = [];

        // Resolve Drive service and target Google Drive folder ID
        $driveService = null;
        $targetDriveFolderId = null;

        if (\App\Http\Controllers\GoogleAuthController::isConnected()) {
            try {
                $driveService = new DriveService();
                if ($folderId) {
                    $folder = DriveFolder::find($folderId);
                    if ($folder) {
                        if (empty($folder->drive_folder_id)) {
                            // Automatically sync folder to Google Drive
                            $parentDriveId = $folder->parent?->drive_folder_id;
                            $folder->drive_folder_id = $driveService->createDriveFolder($folder->name, $parentDriveId);
                            $folder->save();
                        }
                        $targetDriveFolderId = $folder->drive_folder_id;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Google Drive folder setup warning: ' . $e->getMessage());
            }
        }

        foreach ($filesToUpload as $uploadedFile) {
            $originalName = $uploadedFile->getClientOriginalName();
            $fileSize = $uploadedFile->getSize() ?: 0;
            $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';

            // Detect file type
            try {
                if (!$driveService) {
                    $driveService = new DriveService();
                }
                $fileType = $driveService->detectTypeFromName($originalName, $uploadedFile->getRealPath());
            } catch (\Throwable $e) {
                $ext = strtolower($uploadedFile->getClientOriginalExtension());
                $fileType = 'document';
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'])) $fileType = 'photo';
                if (in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv'])) $fileType = 'video';
            }

            $driveFile = null;

            // 1. Google Drive Cloud Upload
            if (\App\Http\Controllers\GoogleAuthController::isConnected()) {
                try {
                    if (!$driveService) {
                        $driveService = new DriveService();
                    }
                    $result = $driveService->uploadFile($uploadedFile, Auth::id(), $targetDriveFolderId);

                    $driveFile = DriveFile::create([
                        'uploaded_by'   => Auth::id(),
                        'folder_id'     => $folderId,
                        'original_name' => $originalName,
                        'drive_file_id' => $result['drive_file_id'],
                        'drive_url'     => $result['drive_url'],
                        'file_type'     => $result['file_type'],
                        'file_size'     => $fileSize,
                        'mime_type'     => $mimeType,
                        'upload_date'   => $result['upload_date'],
                    ]);

                    ActivityLog::log(
                        action: 'file_uploaded',
                        description: sprintf('%s uploaded "%s" (%s) to Google Drive%s',
                            Auth::user()->name,
                            $driveFile->original_name,
                            ucfirst($driveFile->file_type),
                            $folderId ? ' folder' : ' cloud'
                        ),
                        entityType: 'DriveFile',
                        entityId: $driveFile->id
                    );
                } catch (\Exception $e) {
                    Log::error('Drive upload failed, falling back to local storage pipeline: ' . $e->getMessage());
                }
            }

            // 2. Reliable Local Storage Pipeline fallback
            if (!$driveFile) {
                $destination = public_path('uploads/drive/' . $today);
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }

                $safeFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $uploadedFile->move($destination, $safeFileName);
                $localRelativePath = 'uploads/drive/' . $today . '/' . $safeFileName;

                $driveFile = DriveFile::create([
                    'uploaded_by'   => Auth::id(),
                    'folder_id'     => $folderId,
                    'original_name' => $originalName,
                    'drive_file_id' => 'local_' . uniqid(),
                    'drive_url'     => url($localRelativePath),
                    'file_type'     => $fileType,
                    'file_size'     => $fileSize,
                    'mime_type'     => $mimeType,
                    'upload_date'   => $today,
                ]);

                ActivityLog::log(
                    action: 'file_uploaded',
                    description: sprintf('%s uploaded "%s" (%s) to local storage pipeline',
                        Auth::user()->name,
                        $driveFile->original_name,
                        ucfirst($driveFile->file_type)
                    ),
                    entityType: 'DriveFile',
                    entityId: $driveFile->id
                );
            }

            $uploadedRecords[] = $driveFile;
        }

        if ($request->ajax() || $request->wantsJson()) {
            $formatted = array_map(function ($f) {
                return [
                    'id'             => $f->id,
                    'original_name'  => $f->original_name,
                    'file_type'      => $f->file_type,
                    'file_size'      => $f->file_size,
                    'formatted_size' => $f->formatted_size,
                    'drive_url'      => $f->drive_url,
                    'download_url'   => route('drive.download', $f),
                    'upload_date'    => $f->upload_date,
                    'uploader_name'  => Auth::user()->name,
                    'folder_id'      => $f->folder_id,
                    'is_image'       => $f->is_image,
                    'is_video'       => $f->is_video,
                ];
            }, $uploadedRecords);

            return response()->json([
                'success' => true,
                'message' => count($uploadedRecords) === 1
                    ? 'File uploaded successfully!'
                    : count($uploadedRecords) . ' files uploaded successfully!',
                'files'   => $formatted,
                'file'    => $formatted[0] ?? null,
            ]);
        }

        $msg = count($uploadedRecords) === 1
            ? 'File uploaded to Google Drive successfully! ' . ucfirst($uploadedRecords[0]->file_type) . ' saved.'
            : count($uploadedRecords) . ' files uploaded to Google Drive successfully!';

        return back()->with('success', $msg);
    }

    /**
     * Create a new folder (Drive + DB)
     */
    public function createFolder(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:drive_folders,id',
        ]);

        $name = trim($request->name);
        $parentId = $request->filled('parent_id') ? (int) $request->parent_id : null;
        $driveFolderId = null;

        if (\App\Http\Controllers\GoogleAuthController::isConnected()) {
            try {
                $driveService = new DriveService();
                $parentDriveId = null;
                if ($parentId) {
                    $parentFolder = DriveFolder::find($parentId);
                    $parentDriveId = $parentFolder?->drive_folder_id;
                }
                $driveFolderId = $driveService->createDriveFolder($name, $parentDriveId);
            } catch (\Exception $e) {
                Log::warning('Drive cloud folder creation warning: ' . $e->getMessage());
            }
        }

        $folder = DriveFolder::create([
            'name'            => $name,
            'parent_id'       => $parentId,
            'drive_folder_id' => $driveFolderId,
            'created_by'      => Auth::id(),
        ]);

        ActivityLog::log(
            action: 'folder_created',
            description: sprintf('%s created folder "%s"', Auth::user()->name, $name),
            entityType: 'DriveFolder',
            entityId: $folder->id
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Folder '{$name}' created successfully!",
                'folder'  => $folder->loadCount('files'),
            ]);
        }

        return back()->with('success', "Folder '{$name}' created successfully!");
    }

    /**
     * Create a text/document note file directly in Drive
     */
    public function createFile(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'content'   => 'nullable|string',
            'folder_id' => 'nullable|exists:drive_folders,id',
            'extension' => 'nullable|string|in:txt,md,doc,html,json,csv',
        ]);

        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $extension = $request->input('extension', 'txt');
        $rawName = trim($request->name);

        if (!str_ends_with(strtolower($rawName), '.' . $extension)) {
            $originalName = $rawName . '.' . $extension;
        } else {
            $originalName = $rawName;
        }

        $content = (string) $request->input('content', '');
        $fileSize = strlen($content);
        $today = now()->format('Y-m-d');
        $driveFile = null;

        if (\App\Http\Controllers\GoogleAuthController::isConnected()) {
            try {
                $driveService = new DriveService();
                $targetDriveFolderId = null;
                if ($folderId) {
                    $folder = DriveFolder::find($folderId);
                    if ($folder) {
                        if (empty($folder->drive_folder_id)) {
                            $parentDriveId = $folder->parent?->drive_folder_id;
                            $folder->drive_folder_id = $driveService->createDriveFolder($folder->name, $parentDriveId);
                            $folder->save();
                        }
                        $targetDriveFolderId = $folder->drive_folder_id;
                    }
                }

                $result = $driveService->createDocFile($originalName, $content, Auth::id(), $targetDriveFolderId);

                $driveFile = DriveFile::create([
                    'uploaded_by'   => Auth::id(),
                    'folder_id'     => $folderId,
                    'original_name' => $originalName,
                    'drive_file_id' => $result['drive_file_id'],
                    'drive_url'     => $result['drive_url'],
                    'file_type'     => 'document',
                    'file_size'     => $fileSize,
                    'mime_type'     => 'text/plain',
                    'upload_date'   => $result['upload_date'],
                ]);
            } catch (\Exception $e) {
                Log::warning('Drive createDocFile failed, using local pipeline: ' . $e->getMessage());
            }
        }

        if (!$driveFile) {
            $destination = public_path('uploads/drive/' . $today);
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            $safeFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            file_put_contents($destination . '/' . $safeFileName, $content);
            $localRelativePath = 'uploads/drive/' . $today . '/' . $safeFileName;

            $driveFile = DriveFile::create([
                'uploaded_by'   => Auth::id(),
                'folder_id'     => $folderId,
                'original_name' => $originalName,
                'drive_file_id' => 'local_' . uniqid(),
                'drive_url'     => url($localRelativePath),
                'file_type'     => 'document',
                'file_size'     => $fileSize,
                'mime_type'     => 'text/plain',
                'upload_date'   => $today,
            ]);
        }

        ActivityLog::log(
            action: 'file_created',
            description: sprintf('%s created document "%s"', Auth::user()->name, $originalName),
            entityType: 'DriveFile',
            entityId: $driveFile->id
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Document '{$originalName}' created successfully!",
                'file'    => [
                    'id'             => $driveFile->id,
                    'original_name'  => $driveFile->original_name,
                    'file_type'      => $driveFile->file_type,
                    'file_size'      => $driveFile->file_size,
                    'formatted_size' => $driveFile->formatted_size,
                    'drive_url'      => $driveFile->drive_url,
                    'download_url'   => route('drive.download', $driveFile),
                    'upload_date'    => $driveFile->upload_date,
                    'uploader_name'  => Auth::user()->name,
                    'folder_id'      => $driveFile->folder_id,
                    'is_image'       => $driveFile->is_image,
                    'is_video'       => $driveFile->is_video,
                ],
            ]);
        }

        return back()->with('success', "Document '{$originalName}' created successfully!");
    }

    /**
     * Rename a file
     */
    public function rename(Request $request, DriveFile $file)
    {
        $user = Auth::user();
        if (!$user->isTL() && $user->role !== 'hr' && $user->role !== 'ceo' && $file->uploaded_by !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $oldName = $file->original_name;
        $newName = trim($request->name);

        $file->original_name = $newName;
        $file->save();

        if (\App\Http\Controllers\GoogleAuthController::isConnected() && !str_starts_with($file->drive_file_id, 'local_')) {
            try {
                $driveService = new DriveService();
                $driveService->renameItem($file->drive_file_id, $newName);
            } catch (\Exception $e) {
                Log::warning('Drive cloud rename failed: ' . $e->getMessage());
            }
        }

        ActivityLog::log(
            action: 'file_renamed',
            description: sprintf('%s renamed file "%s" to "%s"', $user->name, $oldName, $newName),
            entityType: 'DriveFile',
            entityId: $file->id
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'File renamed successfully.',
                'file'    => $file,
            ]);
        }

        return back()->with('success', 'File renamed successfully.');
    }

    /**
     * Rename a folder
     */
    public function renameFolder(Request $request, DriveFolder $folder)
    {
        $user = Auth::user();
        if (!$user->isTL() && $user->role !== 'hr' && $user->role !== 'ceo' && $folder->created_by !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $oldName = $folder->name;
        $newName = trim($request->name);

        $folder->name = $newName;
        $folder->save();

        if (\App\Http\Controllers\GoogleAuthController::isConnected() && !empty($folder->drive_folder_id)) {
            try {
                $driveService = new DriveService();
                $driveService->renameItem($folder->drive_folder_id, $newName);
            } catch (\Exception $e) {
                Log::warning('Drive cloud folder rename failed: ' . $e->getMessage());
            }
        }

        ActivityLog::log(
            action: 'folder_renamed',
            description: sprintf('%s renamed folder "%s" to "%s"', $user->name, $oldName, $newName),
            entityType: 'DriveFolder',
            entityId: $folder->id
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Folder renamed successfully.',
                'folder'  => $folder,
            ]);
        }

        return back()->with('success', 'Folder renamed successfully.');
    }

    /**
     * Move a file into another folder
     */
    public function moveFile(Request $request, DriveFile $file)
    {
        $user = Auth::user();
        if (!$user->isTL() && $user->role !== 'hr' && $user->role !== 'ceo' && $file->uploaded_by !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'folder_id' => 'nullable|exists:drive_folders,id',
        ]);

        $targetFolderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $targetFolder = $targetFolderId ? DriveFolder::find($targetFolderId) : null;

        $file->folder_id = $targetFolderId;
        $file->save();

        if (\App\Http\Controllers\GoogleAuthController::isConnected() && !str_starts_with($file->drive_file_id, 'local_')) {
            try {
                $driveService = new DriveService();
                $targetDriveParentId = $targetFolder?->drive_folder_id ?: $driveService->resolveRootFolderId();
                if ($targetDriveParentId) {
                    $driveService->moveItem($file->drive_file_id, $targetDriveParentId);
                }
            } catch (\Exception $e) {
                Log::warning('Drive cloud move failed: ' . $e->getMessage());
            }
        }

        ActivityLog::log(
            action: 'file_moved',
            description: sprintf('%s moved file "%s" to folder "%s"', $user->name, $file->original_name, $targetFolder ? $targetFolder->name : 'Root'),
            entityType: 'DriveFile',
            entityId: $file->id
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'File moved successfully.',
                'file'    => $file,
            ]);
        }

        return back()->with('success', 'File moved successfully.');
    }

    /**
     * Delete a folder and its contents
     */
    public function destroyFolder(DriveFolder $folder)
    {
        $user = Auth::user();
        if (!$user->isTL() && $user->role !== 'hr' && $user->role !== 'ceo' && $folder->created_by !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $folderName = $folder->name;

        // Delete contained files
        $driveService = null;
        if (\App\Http\Controllers\GoogleAuthController::isConnected()) {
            try {
                $driveService = new DriveService();
            } catch (\Exception $e) {}
        }

        foreach ($folder->files as $file) {
            if (str_starts_with($file->drive_file_id, 'local_') || str_contains($file->drive_url, '/uploads/drive/')) {
                $parsedPath = parse_url($file->drive_url, PHP_URL_PATH);
                $localPath = public_path(ltrim($parsedPath, '/'));
                if (file_exists($localPath)) {
                    @unlink($localPath);
                }
            }
            if ($driveService && !str_starts_with($file->drive_file_id, 'local_')) {
                try {
                    $driveService->deleteFile($file->drive_file_id);
                } catch (\Exception $e) {}
            }
            $file->delete();
        }

        // Delete folder from Drive
        if ($driveService && !empty($folder->drive_folder_id)) {
            try {
                $driveService->deleteFile($folder->drive_folder_id);
            } catch (\Exception $e) {}
        }

        $parentId = $folder->parent_id;
        $folder->delete();

        ActivityLog::log(
            action: 'folder_deleted',
            description: sprintf('%s deleted folder "%s"', $user->name, $folderName),
            entityType: 'DriveFolder',
            entityId: null
        );

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Folder '{$folderName}' deleted successfully.",
            ]);
        }

        $redirect = $parentId ? route('upload.index', ['folder_id' => $parentId]) : route('upload.index');
        return redirect($redirect)->with('success', "Folder '{$folderName}' deleted successfully.");
    }

    public function download(DriveFile $file)
    {
        $user = Auth::user();

        // Audit Log Download Action
        ActivityLog::log(
            action: 'file_downloaded',
            description: sprintf('%s downloaded "%s" (%s) from folder %s',
                $user->name,
                $file->original_name,
                ucfirst($file->file_type),
                $file->upload_date
            ),
            entityType: 'DriveFile',
            entityId: $file->id
        );

        // Check if file is stored locally
        if (str_starts_with($file->drive_file_id, 'local_') || str_contains($file->drive_url, '/uploads/drive/')) {
            $parsedPath = parse_url($file->drive_url, PHP_URL_PATH);
            $localPath = public_path(ltrim($parsedPath, '/'));
            if (file_exists($localPath)) {
                return response()->download($localPath, $file->original_name);
            }
        }

        if (\App\Http\Controllers\GoogleAuthController::isConnected()) {
            try {
                $driveService = new DriveService();
                $downloadUrl = $driveService->getDirectDownloadUrl($file->drive_file_id);
                return redirect()->away($downloadUrl);
            } catch (\Exception $e) {
                Log::warning('Drive download fallback: ' . $e->getMessage());
            }
        }

        // Direct Google Drive download link
        return redirect()->away("https://drive.google.com/uc?export=download&id={$file->drive_file_id}");
    }

    public function destroy(DriveFile $file)
    {
        $user = Auth::user();
        if (!$user->isTL() && $user->role !== 'hr' && $user->role !== 'ceo' && $file->uploaded_by !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $fileName = $file->original_name;
        $folder = $file->upload_date;

        // Delete local file if present
        if (str_starts_with($file->drive_file_id, 'local_') || str_contains($file->drive_url, '/uploads/drive/')) {
            $parsedPath = parse_url($file->drive_url, PHP_URL_PATH);
            $localPath = public_path(ltrim($parsedPath, '/'));
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        }

        if (\App\Http\Controllers\GoogleAuthController::isConnected() && !str_starts_with($file->drive_file_id, 'local_')) {
            try {
                $driveService = new DriveService();
                $driveService->deleteFile($file->drive_file_id);
            } catch (\Exception $e) {
                Log::warning('Drive delete error: ' . $e->getMessage());
            }
        }

        $file->delete();

        // Audit Log
        ActivityLog::log(
            action: 'file_deleted',
            description: sprintf('%s deleted "%s" from folder %s', $user->name, $fileName, $folder),
            entityType: 'DriveFile',
            entityId: null
        );

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'File deleted from records.',
            ]);
        }

        return back()->with('success', 'File deleted from records.');
    }
}