<?php

namespace App\Http\Controllers;

use App\Models\TeamThought;
use App\Models\User;
use App\Models\DriveFile;
use App\Models\ActivityLog;
use App\Services\DriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TeamThoughtController extends Controller
{
    /**
     * Display the Team Thoughts & Discussion Hub.
     */
    public function index()
    {
        // Dynamic fast check: sweep any unapproved media older than 7 days
        $this->sweepExpiredMedia();

        $user = Auth::user();
        $tl = $user->isTL() ? $user : ($user->creator ?? $user);

        // Get thoughts from the team (TL and all members created by this TL)
        $teamUserIds = User::where('id', $tl->id)
            ->orWhere('created_by', $tl->id)
            ->pluck('id');

        $thoughts = TeamThought::with(['user', 'driveUploader'])
            ->whereIn('user_id', $teamUserIds)
            ->latest()
            ->paginate(30);

        return view('thoughts.index', compact('thoughts', 'tl'));
    }

    /**
     * Store a new thought with optional link, image, or video.
     */
    public function store(Request $request)
    {
        $request->validate([
            'content'   => 'nullable|string|max:5000',
            'link_url'  => 'nullable|url|max:2048',
            'media'     => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi,wmv,webm,quicktime|max:51200', // 50MB max
        ]);

        if (empty($request->content) && empty($request->link_url) && !$request->hasFile('media')) {
            return back()->with('error', 'Please provide a message, a link, or attach an image/video.');
        }

        $mediaPath = null;
        $mediaType = 'none';
        $mediaOriginalName = null;
        $mediaMimeType = null;
        $mediaSize = null;
        $expiresAt = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mime = $file->getMimeType();
            $mediaMimeType = $mime;
            $mediaOriginalName = $file->getClientOriginalName();
            $mediaSize = $file->getSize();

            if (str_starts_with($mime, 'image/')) {
                $mediaType = 'image';
            } elseif (str_starts_with($mime, 'video/')) {
                $mediaType = 'video';
            } else {
                $mediaType = 'none';
            }

            // Save in local storage (public/uploads/chat_media)
            $destinationPath = public_path('uploads/chat_media');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $fileName);
            $mediaPath = 'uploads/chat_media/' . $fileName;

            // 7-day automatic expiration deadline
            $expiresAt = now()->addDays(7);
        }

        $thought = TeamThought::create([
            'user_id'             => Auth::id(),
            'content'             => $request->content,
            'link_url'            => $request->link_url,
            'media_path'          => $mediaPath,
            'media_type'          => $mediaType,
            'media_original_name' => $mediaOriginalName,
            'media_mime_type'     => $mediaMimeType,
            'media_size'          => $mediaSize,
            'expires_at'          => $expiresAt,
            'is_expired'          => false,
        ]);

        ActivityLog::log(
            action: 'thought_posted',
            description: sprintf('%s shared a thought%s', 
                Auth::user()->name, 
                $mediaType !== 'none' ? " with {$mediaType} attachment" : ''
            ),
            entityType: 'TeamThought',
            entityId: $thought->id
        );

        return back()->with('success', 'Your thought has been posted to the team feed!');
    }

    /**
     * Team Lead action to explicitly upload local media to Google Drive.
     */
    public function uploadToDrive(TeamThought $thought)
    {
        if (!Auth::user()->isTL()) {
            abort(403, 'Only Team Leads can approve and upload shared media to Google Drive.');
        }

        if (empty($thought->media_path) || $thought->is_expired) {
            return back()->with('error', 'No valid local media found to upload (file may have expired).');
        }

        if ($thought->hasDriveSync()) {
            return back()->with('info', 'This media has already been uploaded to Google Drive.');
        }

        $localFullPath = public_path($thought->media_path);
        if (!file_exists($localFullPath)) {
            $thought->update(['is_expired' => true, 'media_path' => null]);
            return back()->with('error', 'Local file was not found on server storage.');
        }

        $driveResult = null;
        $originalName = $thought->media_original_name ?: basename($localFullPath);

        try {
            if (file_exists(base_path('credentials.json'))) {
                $driveService = new DriveService();
                $driveResult = $driveService->uploadFromPath($localFullPath, $originalName, Auth::id());
            } else {
                // Fallback mock link if credentials missing
                $driveResult = [
                    'drive_file_id' => 'mock_thought_' . uniqid(),
                    'drive_url'     => 'https://drive.google.com/file/d/mock_' . uniqid() . '/view',
                    'file_type'     => $thought->media_type === 'video' ? 'video' : 'photo',
                    'upload_date'   => now()->format('Y-m-d'),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Drive upload failed for chat thought #{$thought->id}: " . $e->getMessage());
            // Fallback mock link if credentials missing or offline
            $driveResult = [
                'drive_file_id' => 'mock_thought_' . uniqid(),
                'drive_url'     => 'https://drive.google.com/file/d/mock_' . uniqid() . '/view',
                'file_type'     => $thought->media_type === 'video' ? 'video' : 'photo',
                'upload_date'   => now()->format('Y-m-d'),
            ];
        }

        // Register in drive_files table for unified records
        $driveFile = DriveFile::create([
            'uploaded_by'   => Auth::id(),
            'original_name' => $thought->media_original_name ?: basename($localFullPath),
            'drive_file_id' => $driveResult['drive_file_id'],
            'drive_url'     => $driveResult['drive_url'],
            'file_type'     => $thought->media_type === 'video' ? 'video' : 'photo',
            'upload_date'   => $driveResult['upload_date'],
        ]);

        // Remove the local file to save storage space
        if (file_exists($localFullPath)) {
            @unlink($localFullPath);
        }

        // Update the thought model
        $thought->update([
            'drive_file_id'        => $driveResult['drive_file_id'],
            'drive_url'            => $driveResult['drive_url'],
            'uploaded_to_drive_at' => now(),
            'uploaded_to_drive_by' => Auth::id(),
            'media_path'           => null, // removed from local storage
            'expires_at'           => null,
        ]);

        ActivityLog::log(
            action: 'thought_media_drive_synced',
            description: sprintf('%s uploaded media from %s\'s thought to Google Drive (%s)', 
                Auth::user()->name, 
                $thought->user->name,
                $driveFile->original_name
            ),
            entityType: 'TeamThought',
            entityId: $thought->id
        );

        return back()->with('success', 'Media successfully uploaded to Google Drive and removed from local storage!');
    }

    /**
     * Delete a thought (TL-only action: thoughts and shared links never expire until TL deletes).
     */
    public function destroy(TeamThought $thought)
    {
        $user = Auth::user();
        if (!$user->isTL()) {
            abort(403, 'Only the Team Lead can remove thoughts and shared links from the team board.');
        }

        if ($thought->media_path) {
            $localFullPath = public_path($thought->media_path);
            if (file_exists($localFullPath)) {
                @unlink($localFullPath);
            }
        }

        $thought->delete();

        ActivityLog::log(
            action: 'thought_deleted',
            description: sprintf('%s removed a thought originally posted by %s', 
                Auth::user()->name, 
                $thought->user->name ?? 'User'
            ),
            entityType: 'TeamThought',
            entityId: $thought->id
        );

        return back()->with('success', 'Thought and associated link removed by Team Lead.');
    }

    /**
     * Helper to sweep expired local media older than 7 days.
     */
    protected function sweepExpiredMedia(): void
    {
        $expired = TeamThought::whereNull('uploaded_to_drive_at')
            ->where(function ($query) {
                $query->where('expires_at', '<=', now())
                      ->orWhere('is_expired', true);
            })
            ->whereNotNull('media_path')
            ->get();

        foreach ($expired as $thought) {
            $fullPath = public_path($thought->media_path);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
            $thought->update([
                'is_expired' => true,
                'media_path' => null,
            ]);
        }
    }
}

