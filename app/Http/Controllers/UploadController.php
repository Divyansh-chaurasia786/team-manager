<?php
namespace App\Http\Controllers;

use App\Models\DriveFile;
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
        $tl = $user->isTL() ? $user : $user->creator ?? $user;

        $query = DriveFile::with('uploader')
            ->whereHas('uploader', function($q) use ($tl) {
                $q->where('id', $tl->id)->orWhere('created_by', $tl->id);
            });

        if ($request->filled('type') && in_array($request->type, ['photo', 'video', 'document'])) {
            $query->where('file_type', $request->type);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('original_name', 'like', "%{$term}%")
                  ->orWhere('upload_date', 'like', "%{$term}%");
            });
        }

        $recentFiles = $query->latest()->take(50)->get();

        return view('shared.upload', compact('recentFiles'));
    }

    public function store(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $request->validate([
            'file' => 'required|file', // No file size limit on Google Drive upload
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $today = now()->format('Y-m-d');

        // Detect file type
        $driveService = null;
        try {
            $driveService = new DriveService();
            $fileType = $driveService->detectTypeFromName($originalName, $uploadedFile->getRealPath());
        } catch (\Throwable $e) {
            $ext = strtolower($uploadedFile->getClientOriginalExtension());
            $fileType = 'document';
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'])) $fileType = 'photo';
            if (in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv'])) $fileType = 'video';
        }

        // 1. If Google Drive OAuth is connected, upload directly to Google Drive
        if (\App\Http\Controllers\GoogleAuthController::isConnected()) {
            try {
                if (!$driveService) {
                    $driveService = new DriveService();
                }
                $result = $driveService->uploadFile($uploadedFile, Auth::id());

                $driveFile = DriveFile::create([
                    'uploaded_by'   => Auth::id(),
                    'original_name' => $originalName,
                    'drive_file_id' => $result['drive_file_id'],
                    'drive_url'     => $result['drive_url'],
                    'file_type'     => $result['file_type'],
                    'upload_date'   => $result['upload_date'],
                ]);

                // Audit Log
                ActivityLog::log(
                    action: 'file_uploaded',
                    description: sprintf('%s uploaded "%s" (%s) to Google Drive folder %s', 
                        Auth::user()->name, 
                        $driveFile->original_name, 
                        ucfirst($driveFile->file_type), 
                        $driveFile->upload_date
                    ),
                    entityType: 'DriveFile',
                    entityId: $driveFile->id
                );

                return back()->with('success', 'File uploaded to Google Drive successfully! ' . ucfirst($result['file_type']) . ' saved to ' . $result['upload_date'] . ' folder.');
            } catch (\Exception $e) {
                Log::error('Drive upload failed, falling back to local storage pipeline: ' . $e->getMessage());
            }
        }

        // 2. Reliable Local Storage Pipeline: Saves file immediately so no upload is ever lost
        $destination = public_path('uploads/drive/' . $today);
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $safeFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $uploadedFile->move($destination, $safeFileName);
        $localRelativePath = 'uploads/drive/' . $today . '/' . $safeFileName;

        $driveFile = DriveFile::create([
            'uploaded_by'   => Auth::id(),
            'original_name' => $originalName,
            'drive_file_id' => 'local_' . uniqid(),
            'drive_url'     => url($localRelativePath),
            'file_type'     => $fileType,
            'upload_date'   => $today,
        ]);

        ActivityLog::log(
            action: 'file_uploaded',
            description: sprintf('%s uploaded "%s" (%s) to local storage (folder %s)', 
                Auth::user()->name, 
                $driveFile->original_name, 
                ucfirst($driveFile->file_type), 
                $driveFile->upload_date
            ),
            entityType: 'DriveFile',
            entityId: $driveFile->id
        );

        $msg = 'File uploaded successfully and saved! ';
        if (!\App\Http\Controllers\GoogleAuthController::isConnected()) {
            $msg .= 'To sync directly to your Google Drive cloud, please click "Connect Google Drive" above.';
        }

        return back()->with('success', $msg);
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
        if (!$user->isTL() && $file->uploaded_by !== $user->id) {
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

        return back()->with('success', 'File deleted from records.');
    }
}