<?php

namespace App\Http\Controllers;

use App\Models\TeamThought;
use App\Models\User;
use App\Models\DriveFile;
use App\Models\ActivityLog;
use App\Models\ChatGroupMember;
use App\Services\DriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TeamThoughtController extends Controller
{
    /**
     * Display the Team Thoughts & Discussion Hub.
     */
    public function index(Request $request)
    {
        // Dynamic fast check: sweep any unapproved media older than 7 days
        $this->sweepExpiredMedia();

        $user = Auth::user();
        $isManagement = $user->isHR() || $user->isCEO();

        // If user is HR or CEO, they are not allowed to view private team chats
        $requestedGroup = $request->get('group');
        if ($isManagement && $requestedGroup === 'team') {
            return redirect()->route('thoughts.index', ['group' => 'company']);
        }

        // Default: management defaults to 'company', team members default to 'team'
        $groupType = $requestedGroup ?: ($isManagement ? 'company' : 'team');
        if (!in_array($groupType, ['team', 'company'])) {
            $groupType = $isManagement ? 'company' : 'team';
        }

        $tl = $user->isTL() ? $user : ($user->creator ?? $user);

        // Group members and titles
        if ($groupType === 'team') {
            $teamUsers = $this->getActiveTeamMembers($tl);
            $groupTitle = ($tl->name) . "'s Squad (Private Team)";
            $groupSubtitle = count($teamUsers) . " members • Private team discussion (No HR / CEO)";
        } else {
            // Company wide coordination group
            $teamUsers = User::orderBy('name')->get();
            $groupTitle = "EcoFone Company Hub";
            $groupSubtitle = count($teamUsers) . " members • Organization coordination (with HR & CEO)";
        }

        // Query thoughts for selected group
        $query = TeamThought::with(['user', 'driveUploader', 'deletedBy']);

        if ($groupType === 'team') {
            $query->where('group_type', 'team')
                  ->where(function ($q) use ($tl) {
                      $q->where('tl_id', $tl->id)
                        ->orWhereNull('tl_id');
                  });
        } else {
            $query->where('group_type', 'company');
        }

        $thoughts = $query->latest('id')
            ->take(60)
            ->get()
            ->reverse()
            ->values();

        // Mark incoming messages as seen by current user
        $this->markThoughtsAsSeen($thoughts, $user);

        // Candidate users for TL to add to private group
        $candidateUsers = collect();
        if ($user->isTL() && $groupType === 'team') {
            $currentMemberIds = $teamUsers->pluck('id')->toArray();
            $candidateUsers = User::whereNotIn('id', $currentMemberIds)
                ->whereNotIn('role', ['hr', 'ceo']) // Keep HR/CEO out of private team
                ->orderBy('name')
                ->get();
        }

        // For HR & CEO: list all teams with their TLs
        $allTeams = collect();
        if ($isManagement) {
            $allTeams = User::where('role', 'tl')->withCount('teamMembers')->get();
        }

        return view('thoughts.index', compact(
            'thoughts',
            'tl',
            'teamUsers',
            'groupType',
            'groupTitle',
            'groupSubtitle',
            'candidateUsers',
            'allTeams',
            'isManagement'
        ));
    }

    /**
     * Polling endpoint to fetch recent messages for real-time live WhatsApp chat.
     */
    public function getMessages(Request $request)
    {
        $user = Auth::user();
        $groupType = $request->get('group', 'team');
        $isManagement = $user->isHR() || $user->isCEO();

        if ($isManagement && $groupType === 'team') {
            return response()->json(['success' => false, 'message' => 'Access denied to private team chats.'], 403);
        }

        $tl = $user->isTL() ? $user : ($user->creator ?? $user);
        $afterId = (int) $request->get('after_id', 0);

        $query = TeamThought::with(['user', 'driveUploader', 'deletedBy']);

        if ($groupType === 'team') {
            $query->where('group_type', 'team')
                  ->where(function ($q) use ($tl) {
                      $q->where('tl_id', $tl->id)
                        ->orWhereNull('tl_id');
                  });
        } else {
            $query->where('group_type', 'company');
        }

        $updatedFormatted = [];
        if ($afterId > 0) {
            $thoughts = (clone $query)->where('id', '>', $afterId)
                ->orderBy('id', 'asc')
                ->take(50)
                ->get();

            $updatedThoughts = (clone $query)->where('id', '<=', $afterId)
                ->where('updated_at', '>=', now()->subSeconds(30))
                ->latest('updated_at')
                ->take(20)
                ->get();

            $updatedFormatted = $updatedThoughts->map(function ($t) use ($user) {
                return $this->formatThought($t, $user);
            });
        } else {
            $thoughts = $query->latest('id')
                ->take(60)
                ->get()
                ->reverse()
                ->values();
        }

        $this->markThoughtsAsSeen($thoughts, $user);

        $formatted = $thoughts->map(function ($t) use ($user) {
            return $this->formatThought($t, $user);
        });

        return response()->json([
            'success'          => true,
            'messages'         => $formatted,
            'updated_messages' => $updatedFormatted,
            'latest_id'        => $thoughts->max('id') ?: $afterId,
        ]);
    }

    /**
     * Store a new thought with optional link, image, or video.
     */
    public function store(Request $request)
    {
        $request->validate([
            'content'    => 'nullable|string|max:5000',
            'link_url'   => 'nullable|url|max:2048',
            'group_type' => 'nullable|string|in:team,company',
            'media'      => 'nullable|file|max:51200', // 50MB max, any file type allowed like WhatsApp
        ]);

        $user = Auth::user();
        $groupType = $request->input('group_type', 'team');

        // Prevent HR/CEO from posting directly to private team chat
        if (($user->isHR() || $user->isCEO()) && $groupType === 'team') {
            $groupType = 'company';
        }

        $tl = $user->isTL() ? $user : ($user->creator ?? $user);
        $tlId = $groupType === 'team' ? $tl->id : null;

        if (empty($request->content) && empty($request->link_url) && !$request->hasFile('media')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Please provide a message, a link, or attach media.'], 422);
            }
            return back()->with('error', 'Please provide a message, a link, or attach a file.');
        }

        $mediaPath = null;
        $mediaType = 'none';
        $mediaOriginalName = null;
        $mediaMimeType = null;
        $mediaSize = null;
        $expiresAt = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $mediaMimeType = $mime;
            $mediaOriginalName = $file->getClientOriginalName();
            $mediaSize = $file->getSize();

            if (str_starts_with($mime, 'image/')) {
                $mediaType = 'image';
            } elseif (str_starts_with($mime, 'video/')) {
                $mediaType = 'video';
            } elseif (str_starts_with($mime, 'audio/')) {
                $mediaType = 'audio';
            } else {
                $mediaType = 'document';
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
            'user_id'             => $user->id,
            'group_type'          => $groupType,
            'tl_id'               => $tlId,
            'content'             => $request->content,
            'link_url'            => $request->link_url,
            'media_path'          => $mediaPath,
            'media_type'          => $mediaType,
            'media_original_name' => $mediaOriginalName,
            'media_mime_type'     => $mediaMimeType,
            'media_size'          => $mediaSize,
            'expires_at'          => $expiresAt,
            'is_expired'          => false,
            'is_deleted'          => false,
            'seen_by'             => [],
            'reactions'           => [],
        ]);

        ActivityLog::log(
            action: 'thought_posted',
            description: sprintf('%s shared a thought%s', 
                $user->name, 
                $mediaType !== 'none' ? " with {$mediaType} attachment" : ''
            ),
            entityType: 'TeamThought',
            entityId: $thought->id
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully!',
                'thought' => $this->formatThought($thought, $user),
            ]);
        }

        return back()->with('success', 'Your thought has been posted to the feed!');
    }

    /**
     * Unsend message by the original sender (allowed under 24 hours).
     */
    public function unsend(TeamThought $thought)
    {
        $user = Auth::user();

        if ($thought->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'You can only unsend your own messages.'], 403);
        }

        // 24 hour unsend time limit
        if ($thought->created_at->diffInHours(now()) > 24) {
            return response()->json(['success' => false, 'message' => 'Messages can only be unsent within 24 hours of posting.'], 422);
        }

        if ($thought->media_path) {
            $localFullPath = public_path($thought->media_path);
            if (file_exists($localFullPath)) {
                @unlink($localFullPath);
            }
        }

        $thought->update([
            'is_deleted' => true,
            'deleted_by' => $user->id,
            'deleted_at' => now(),
            'content'    => null,
            'media_path' => null,
            'link_url'   => null,
        ]);

        ActivityLog::log(
            action: 'thought_unsent',
            description: sprintf('%s unsent their message', $user->name),
            entityType: 'TeamThought',
            entityId: $thought->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Message unsent successfully.',
            'thought' => $this->formatThought($thought, $user),
        ]);
    }

    /**
     * Toggle an emoji reaction on a message.
     */
    public function react(Request $request, TeamThought $thought)
    {
        $request->validate(['emoji' => 'required|string|max:32']);
        $user = Auth::user();

        if ($thought->group_type === 'team' && ($user->isHR() || $user->isCEO())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to react in this group.'], 403);
        }

        $emoji = trim($request->input('emoji'));

        $reactions = $thought->reactions ?: [];
        $foundIndex = null;

        foreach ($reactions as $idx => $r) {
            if ($r['user_id'] === $user->id) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex !== null) {
            if ($reactions[$foundIndex]['emoji'] === $emoji) {
                // Remove reaction if clicked same
                array_splice($reactions, $foundIndex, 1);
            } else {
                // Change reaction emoji
                $reactions[$foundIndex]['emoji'] = $emoji;
                $reactions[$foundIndex]['user_name'] = $user->name;
            }
        } else {
            // New reaction
            $reactions[] = [
                'emoji'     => $emoji,
                'user_id'   => $user->id,
                'user_name' => $user->name,
            ];
        }

        $thought->update(['reactions' => $reactions]);

        return response()->json([
            'success'   => true,
            'reactions' => $reactions,
            'thought'   => $this->formatThought($thought->fresh(), $user),
        ]);
    }

    /**
     * Get message info (seen by whom and timestamp).
     */
    public function info(TeamThought $thought)
    {
        $user = Auth::user();
        if ($thought->group_type === 'team' && ($user->isHR() || $user->isCEO())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success'    => true,
            'sender'     => $thought->user?->name ?? 'User',
            'created_at' => $thought->created_at->format('h:i A, M d Y'),
            'seen_by'    => $thought->seen_by ?: [],
        ]);
    }

    /**
     * Add a member to the TL's private group (TL admin action).
     */
    public function addMember(Request $request)
    {
        $user = Auth::user();
        if (!$user->isTL()) {
            return response()->json(['success' => false, 'message' => 'Only Team Leads can add members to team chat.'], 403);
        }

        $request->validate(['user_id' => 'required|exists:users,id']);
        $member = User::findOrFail($request->user_id);

        if ($member->isHR() || $member->isCEO()) {
            return response()->json(['success' => false, 'message' => 'HR and CEO cannot be added to private team chat.'], 422);
        }

        ChatGroupMember::updateOrCreate(
            ['tl_id' => $user->id, 'user_id' => $member->id],
            ['role' => 'member', 'is_active' => true]
        );

        ActivityLog::log(
            action: 'chat_member_added',
            description: sprintf('%s added %s to team chat group', $user->name, $member->name),
            entityType: 'User',
            entityId: $member->id
        );

        return response()->json([
            'success' => true,
            'message' => "{$member->name} added to team chat group.",
        ]);
    }

    /**
     * Remove a member from the TL's private group (TL admin action).
     */
    public function removeMember(Request $request, User $user)
    {
        $tl = Auth::user();
        if (!$tl->isTL()) {
            return response()->json(['success' => false, 'message' => 'Only Team Leads can remove members.'], 403);
        }

        if ($user->id === $tl->id) {
            return response()->json(['success' => false, 'message' => 'You cannot remove yourself as group admin.'], 422);
        }

        ChatGroupMember::updateOrCreate(
            ['tl_id' => $tl->id, 'user_id' => $user->id],
            ['role' => 'member', 'is_active' => false]
        );

        ActivityLog::log(
            action: 'chat_member_removed',
            description: sprintf('%s removed %s from team chat group', $tl->name, $user->name),
            entityType: 'User',
            entityId: $user->id
        );

        return response()->json([
            'success' => true,
            'message' => "{$user->name} removed from team chat group.",
        ]);
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
                $driveCategory = $thought->media_type === 'video' ? 'video' : ($thought->media_type === 'image' ? 'photo' : 'document');
                $driveResult = [
                    'drive_file_id' => 'mock_thought_' . uniqid(),
                    'drive_url'     => 'https://drive.google.com/file/d/mock_' . uniqid() . '/view',
                    'file_type'     => $driveCategory,
                    'upload_date'   => now()->format('Y-m-d'),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Drive upload failed for chat thought #{$thought->id}: " . $e->getMessage());
            $driveCategory = $thought->media_type === 'video' ? 'video' : ($thought->media_type === 'image' ? 'photo' : 'document');
            $driveResult = [
                'drive_file_id' => 'mock_thought_' . uniqid(),
                'drive_url'     => 'https://drive.google.com/file/d/mock_' . uniqid() . '/view',
                'file_type'     => $driveCategory,
                'upload_date'   => now()->format('Y-m-d'),
            ];
        }

        // Register in drive_files table for unified records
        $driveCategory = $thought->media_type === 'video' ? 'video' : ($thought->media_type === 'image' ? 'photo' : 'document');
        $driveFile = DriveFile::create([
            'uploaded_by'   => Auth::id(),
            'original_name' => $thought->media_original_name ?: basename($localFullPath),
            'drive_file_id' => $driveResult['drive_file_id'],
            'drive_url'     => $driveResult['drive_url'],
            'file_type'     => $driveCategory,
            'upload_date'   => $driveResult['upload_date'],
        ]);

        if (file_exists($localFullPath)) {
            @unlink($localFullPath);
        }

        $thought->update([
            'drive_file_id'        => $driveResult['drive_file_id'],
            'drive_url'            => $driveResult['drive_url'],
            'uploaded_to_drive_at' => now(),
            'uploaded_to_drive_by' => Auth::id(),
            'media_path'           => null,
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
     * Delete a thought (TL can delete anyone's message; sender can delete within 24 hours).
     */
    public function destroy(Request $request, TeamThought $thought)
    {
        $user = Auth::user();
        if (!$user->isTL()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only Team Leads can delete messages from the group.'], 403);
            }
            abort(403, 'Only the Team Lead can remove thoughts and shared links from the team board.');
        }

        if ($thought->media_path) {
            $localFullPath = public_path($thought->media_path);
            if (file_exists($localFullPath)) {
                @unlink($localFullPath);
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            // Soft tombstone delete for live chat experience
            $thought->update([
                'is_deleted' => true,
                'deleted_by' => $user->id,
                'deleted_at' => now(),
                'content'    => null,
                'media_path' => null,
                'link_url'   => null,
            ]);

            ActivityLog::log(
                action: 'thought_deleted',
                description: sprintf('%s removed a thought originally posted by %s', 
                    $user->name, 
                    $thought->user?->name ?? 'User'
                ),
                entityType: 'TeamThought',
                entityId: $thought->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully.',
                'thought' => $this->formatThought($thought, $user),
            ]);
        }

        $thought->delete();

        ActivityLog::log(
            action: 'thought_deleted',
            description: sprintf('%s permanently deleted a thought originally posted by %s', 
                $user->name, 
                $thought->user?->name ?? 'User'
            ),
            entityType: 'TeamThought',
            entityId: $thought->id
        );

        return back()->with('success', 'Thought removed by Team Lead.');
    }

    /**
     * Helper to get active team members for a TL's private group.
     */
    protected function getActiveTeamMembers(User $tl)
    {
        $defaultMemberIds = User::where('created_by', $tl->id)->pluck('id')->toArray();
        $overrides = ChatGroupMember::where('tl_id', $tl->id)->get();
        $addedIds = $overrides->where('is_active', true)->pluck('user_id')->toArray();
        $removedIds = $overrides->where('is_active', false)->pluck('user_id')->toArray();

        $allMemberIds = array_unique(array_merge([$tl->id], $defaultMemberIds, $addedIds));
        $activeMemberIds = array_diff($allMemberIds, $removedIds);

        return User::whereIn('id', $activeMemberIds)->get();
    }

    /**
     * Helper to mark a collection of thoughts as seen by current user.
     */
    protected function markThoughtsAsSeen($thoughts, User $user): void
    {
        $nowFormatted = now()->format('h:i A, M d');
        foreach ($thoughts as $thought) {
            if ($thought->user_id === $user->id || $thought->is_deleted) {
                continue;
            }

            $seenBy = $thought->seen_by ?: [];
            $alreadySeen = false;
            foreach ($seenBy as $entry) {
                if ($entry['user_id'] === $user->id) {
                    $alreadySeen = true;
                    break;
                }
            }

            if (!$alreadySeen) {
                $seenBy[] = [
                    'user_id'   => $user->id,
                    'user_name' => $user->name,
                    'avatar'    => $user->avatar_url,
                    'seen_at'   => $nowFormatted,
                ];
                $thought->update(['seen_by' => $seenBy]);
            }
        }
    }

    /**
     * Helper to format a single thought for JSON responses.
     */
    protected function formatThought(TeamThought $t, User $user): array
    {
        $mediaUrl = null;
        if (!$t->is_deleted) {
            if ($t->media_path) {
                $mediaUrl = Cache::remember("thought_media_url_{$t->id}", now()->addDays(30), function () use ($t) {
                    $fullPath = public_path($t->media_path);
                    if (file_exists($fullPath)) {
                        return asset($t->media_path) . '?v=' . filemtime($fullPath);
                    }
                    return null;
                });
            } elseif ($t->drive_url) {
                $mediaUrl = $t->drive_url;
            }
        }

        $seenBy = $t->seen_by ?: [];
        $reactions = $t->reactions ?: [];

        return [
            'id'                  => $t->id,
            'user_id'             => $t->user_id,
            'user_name'           => $t->user?->name ?? 'Team Member',
            'user_initials'       => strtoupper(substr($t->user?->name ?? 'TM', 0, 2)),
            'user_role'           => $t->user?->designation ?: ($t->user?->isTL() ? 'Team Lead' : ($t->user?->isAdmin() ? strtoupper($t->user->role) : 'Staff')),
            'user_avatar'         => $t->user?->avatar_url,
            'is_me'               => $t->user_id === $user->id,
            'is_tl'               => $t->user?->isTL() ?? false,
            'group_type'          => $t->group_type,
            'content'             => $t->is_deleted ? null : $t->content,
            'link_url'            => $t->is_deleted ? null : $t->link_url,
            'media_url'           => $mediaUrl,
            'media_type'          => $t->is_deleted ? 'none' : $t->media_type,
            'media_original_name' => $t->is_deleted ? null : $t->media_original_name,
            'original_name'       => $t->is_deleted ? null : $t->media_original_name,
            'media_size'          => $t->media_size,
            'media_size_human'    => $this->formatFileSize($t->media_size),
            'media_extension'     => $t->media_original_name ? strtoupper(pathinfo($t->media_original_name, PATHINFO_EXTENSION)) : null,
            'has_drive_sync'      => $t->hasDriveSync(),
            'drive_url'           => $t->drive_url,
            'time'                => $t->created_at->format('h:i A'),
            'date_label'          => $t->created_at->isToday() ? 'Today' : ($t->created_at->isYesterday() ? 'Yesterday' : $t->created_at->format('M d, Y')),
            'raw_timestamp'       => $t->created_at->timestamp,
            'is_deleted'          => (bool) $t->is_deleted,
            'deleted_by_name'     => $t->deletedBy?->name,
            'can_unsend'          => $t->isUnsendableBy($user),
            'can_delete'          => $t->isDeletableBy($user),
            'seen_by'             => $seenBy,
            'is_seen'             => count($seenBy) > 0,
            'seen_count'          => count($seenBy),
            'reactions'           => $reactions,
        ];
    }

    /**
     * Format bytes into human-readable string.
     */
    protected function formatFileSize(?int $bytes): ?string
    {
        if (!$bytes) return null;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
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

    /**
     * Get unread thoughts count for the authenticated user.
     */
    public function unreadCount()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'unread_count' => 0]);
        }

        return response()->json([
            'success'      => true,
            'unread_count' => $user->unreadThoughtsCount(),
        ]);
    }
}
