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
    public function index()
    {
        $user = Auth::user();
        $tl = $user->isTL() ? $user : $user->creator ?? $user;

        $recentFiles = DriveFile::with('uploader')
            ->whereHas('uploader', function($q) use ($tl) {
                $q->where('id', $tl->id)->orWhere('created_by', $tl->id);
            })
            ->latest()->take(25)->get();

        return view('shared.upload', compact('recentFiles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
        ]);

        $isConfigured = \App\Http\Controllers\GoogleAuthController::isConnected() 
            || file_exists(base_path('credentials.json'))
            || file_exists(base_path('oauth_credentials.json'));

        if (!$isConfigured) {
            return back()->with('error', 'Google Drive is not connected yet. Please click "Connect Google Drive" below.');
        }

        try {
            $driveService = new DriveService();
            $result = $driveService->uploadFile($request->file('file'), Auth::id());

            $driveFile = DriveFile::create([
                'uploaded_by'   => Auth::id(),
                'original_name' => $request->file('file')->getClientOriginalName(),
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
            Log::error('Drive upload failed: ' . $e->getMessage());
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function download(DriveFile $file)
    {
        $user = Auth::user();

        // Audit Log Download Action
        ActivityLog::log(
            action: 'file_downloaded',
            description: sprintf('%s downloaded "%s" (%s) from Google Drive folder %s',
                $user->name,
                $file->original_name,
                ucfirst($file->file_type),
                $file->upload_date
            ),
            entityType: 'DriveFile',
            entityId: $file->id
        );

        // If credentials exist, attempt to stream or direct redirect
        if (file_exists(base_path('credentials.json'))) {
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

        if (file_exists(base_path('credentials.json'))) {
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
            description: sprintf('%s deleted "%s" from Google Drive folder %s', $user->name, $fileName, $folder),
            entityType: 'DriveFile',
            entityId: null
        );

        return back()->with('success', 'File deleted from records.');
    }
}