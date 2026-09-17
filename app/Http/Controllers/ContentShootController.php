<?php

namespace App\Http\Controllers;

use App\Models\ContentShoot;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentShootController extends Controller
{
    /**
     * Display a listing of scheduled shoots & production planner.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Query builder
        $query = ContentShoot::with(['creator', 'managingMember', 'cameraPerson', 'model', 'editor', 'director'])->latest('id');

        // Filter tab
        $tab = $request->query('tab', 'all');

        if ($tab === 'mine') {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('managing_member_id', $user->id);
            });
        } elseif ($tab === 'instagram') {
            $query->whereIn('platform', ['instagram', 'both']);
        } elseif ($tab === 'youtube') {
            $query->whereIn('platform', ['youtube', 'both']);
        } elseif (in_array($tab, ['planning', 'scripting', 'scheduled', 'shooting', 'editing', 'review', 'published'])) {
            $query->where('status', $tab);
        }

        $shoots = $query->paginate(12)->withQueryString();

        // Production Statistics
        $allShoots = ContentShoot::all();
        $stats = [
            'total'     => $allShoots->count(),
            'scheduled' => $allShoots->where('status', 'scheduled')->count(),
            'shooting'  => $allShoots->where('status', 'shooting')->count(),
            'editing'   => $allShoots->where('status', 'editing')->count(),
            'published' => $allShoots->where('status', 'published')->count(),
            'mine'      => $allShoots->filter(fn($s) => $s->isManager($user))->count(),
        ];

        // Fetch team members to assign crew roles
        $members = User::where('role', 'member')->orWhere('id', Auth::id())->orderBy('name')->get();

        return view('shoots.index', compact('shoots', 'stats', 'members', 'tab'));
    }

    /**
     * Store a newly scheduled shoot and script.
     * Shoot title & Instagram handle are the only mandatory fields.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isTL() && !Auth::user()->isCEO() && !Auth::user()->isHR()) {
            abort(403, 'Unauthorized. Only management roles can schedule and assign shoots.');
        }

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'instagram_handle'    => 'required|string|max:100',
            'managing_member_id'  => 'nullable|exists:users,id',
            'platform'            => 'nullable|in:instagram,youtube,both,other',
            'youtube_channel'     => 'nullable|string|max:100',
            'shoot_date'          => 'nullable|date',
            'location'            => 'nullable|string|max:255',
            'camera_person_id'    => 'nullable|exists:users,id',
            'camera_person_name'  => 'nullable|string|max:255',
            'model_id'            => 'nullable|exists:users,id',
            'model_name'          => 'nullable|string|max:255',
            'editor_id'           => 'nullable|exists:users,id',
            'editor_name'         => 'nullable|string|max:255',
            'director_id'         => 'nullable|exists:users,id',
            'director_name'       => 'nullable|string|max:255',
            'other_crew'          => 'nullable|string|max:255',
            'hook'                => 'nullable|string',
            'script'              => 'nullable|string',
            'concept_notes'       => 'nullable|string',
            'reference_links'     => 'nullable|string',
            'status'              => 'nullable|in:planning,scripting,scheduled,shooting,editing,review,published',
            'target_publish_date' => 'nullable|date',
        ]);

        if (empty($validated['platform'])) {
            $validated['platform'] = 'instagram';
        }
        if (empty($validated['status'])) {
            $validated['status'] = 'scheduled';
        }
        if (empty($validated['shoot_date'])) {
            $validated['shoot_date'] = null;
        }

        $validated['created_by'] = Auth::id();

        $shoot = ContentShoot::create($validated);

        $shootDateStr = $shoot->shoot_date ? $shoot->shoot_date->format('d M Y, h:i A') : 'Schedule TBD';

        ActivityLog::log(
            action: 'shoot_scheduled',
            description: sprintf('%s scheduled a new shoot "%s" for %s (Status: %s)',
                Auth::user()->name,
                $shoot->title,
                $shootDateStr,
                ucfirst($shoot->status)
            ),
            entityType: 'ContentShoot',
            entityId: $shoot->id
        );

        return redirect()->route('shoots.show', $shoot)->with('success', 'Production shoot scheduled successfully!');
    }

    /**
     * Quick crew & manager assignment endpoint for Team Leads.
     */
    public function assignCrew(Request $request, ContentShoot $shoot)
    {
        if (!Auth::user()->isTL() && !Auth::user()->isCEO() && !Auth::user()->isHR()) {
            abort(403, 'Unauthorized. Only management roles can assign crew members and managing members.');
        }

        $validated = $request->validate([
            'managing_member_id' => 'nullable|exists:users,id',
            'camera_person_id'   => 'nullable|exists:users,id',
            'camera_person_name' => 'nullable|string|max:255',
            'model_id'           => 'nullable|exists:users,id',
            'model_name'         => 'nullable|string|max:255',
            'editor_id'          => 'nullable|exists:users,id',
            'editor_name'        => 'nullable|string|max:255',
            'other_crew'         => 'nullable|string|max:255',
        ]);

        $shoot->update($validated);

        ActivityLog::log(
            action: 'shoot_crew_reassigned',
            description: sprintf('%s updated assignments for shoot "%s"', Auth::user()->name, $shoot->title),
            entityType: 'ContentShoot',
            entityId: $shoot->id
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Assignments updated successfully.',
                'manager'  => $shoot->managing_member_name,
                'camera'   => $shoot->camera_name,
                'model'    => $shoot->model_display_name,
                'editor'   => $shoot->editor_display_name,
            ]);
        }

        return back()->with('success', 'Shoot assignments updated successfully for: ' . $shoot->title);
    }

    /**
     * Display the call-sheet, script, and production pipeline for a shoot.
     */
    public function show(ContentShoot $shoot)
    {
        $shoot->load(['creator', 'managingMember', 'cameraPerson', 'model', 'editor', 'director']);
        $members = User::where('role', 'member')->orWhere('id', Auth::id())->orderBy('name')->get();

        return view('shoots.show', compact('shoot', 'members'));
    }

    /**
     * Legacy print route redirected to show view.
     */
    public function printSheet(ContentShoot $shoot)
    {
        return redirect()->route('shoots.show', $shoot);
    }

    /**
     * Update shoot details, cast/crew, or script.
     * Shoot title & Instagram handle are mandatory.
     */
    public function update(Request $request, ContentShoot $shoot)
    {
        $user = Auth::user();
        if (!$shoot->canUpdateStatus($user) && $shoot->created_by !== $user->id && !$user->isCEO()) {
            abort(403, 'Unauthorized. Only the Team Lead, CEO, or assigned managing member can update this shoot.');
        }

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'instagram_handle'    => 'required|string|max:100',
            'managing_member_id'  => 'nullable|exists:users,id',
            'platform'            => 'nullable|in:instagram,youtube,both,other',
            'youtube_channel'     => 'nullable|string|max:100',
            'shoot_date'          => 'nullable|date',
            'location'            => 'nullable|string|max:255',
            'camera_person_id'    => 'nullable|exists:users,id',
            'camera_person_name'  => 'nullable|string|max:255',
            'model_id'            => 'nullable|exists:users,id',
            'model_name'          => 'nullable|string|max:255',
            'editor_id'           => 'nullable|exists:users,id',
            'editor_name'         => 'nullable|string|max:255',
            'director_id'         => 'nullable|exists:users,id',
            'director_name'       => 'nullable|string|max:255',
            'other_crew'          => 'nullable|string|max:255',
            'hook'                => 'nullable|string',
            'script'              => 'nullable|string',
            'concept_notes'       => 'nullable|string',
            'reference_links'     => 'nullable|string',
            'status'              => 'nullable|in:planning,scripting,scheduled,shooting,editing,review,published',
            'target_publish_date' => 'nullable|date',
            'published_url'       => 'nullable|url|max:255',
        ]);

        if (empty($validated['platform'])) {
            $validated['platform'] = $shoot->platform ?: 'instagram';
        }
        if (empty($validated['status'])) {
            $validated['status'] = $shoot->status ?: 'scheduled';
        }
        if (empty($validated['shoot_date'])) {
            $validated['shoot_date'] = null;
        }

        $shoot->update($validated);

        ActivityLog::log(
            action: 'shoot_updated',
            description: sprintf('%s updated shoot production details for "%s"', Auth::user()->name, $shoot->title),
            entityType: 'ContentShoot',
            entityId: $shoot->id
        );

        return back()->with('success', 'Shoot details and script updated successfully!');
    }

    /**
     * Quick status transition (e.g. Shooting -> Editing -> Published).
     * Rule: Only the managing member (or Team Lead if unassigned/TL) can update shooting status.
     */
    public function updateStatus(Request $request, ContentShoot $shoot)
    {
        $user = Auth::user();

        if (!$shoot->canUpdateStatus($user)) {
            abort(403, 'Unauthorized. Only the managing member assigned to this shoot or the Team Lead can update production status.');
        }

        $validated = $request->validate([
            'status'        => 'required|in:planning,scripting,scheduled,shooting,editing,review,published',
            'published_url' => 'nullable|url|max:255',
        ]);

        $oldStatus = $shoot->status;
        $shoot->update($validated);

        ActivityLog::log(
            action: 'shoot_status_changed',
            description: sprintf('%s updated shoot "%s" status from %s to %s',
                Auth::user()->name,
                $shoot->title,
                ucfirst($oldStatus),
                ucfirst($shoot->status)
            ),
            entityType: 'ContentShoot',
            entityId: $shoot->id
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Shoot status updated to " . ucfirst($shoot->status),
                'status'  => $shoot->status,
                'badge'   => $shoot->status_badge,
            ]);
        }

        return back()->with('success', "Production status updated to " . ucfirst($shoot->status));
    }

    /**
     * Remove the specified shoot from storage.
     */
    public function destroy(ContentShoot $shoot)
    {
        if (!Auth::user()->isTL() && $shoot->created_by !== Auth::id()) {
            abort(403, 'Unauthorized action. Only Team Leads can delete shoots.');
        }

        $title = $shoot->title;
        $shoot->delete();

        ActivityLog::log(
            action: 'shoot_deleted',
            description: sprintf('%s deleted production shoot "%s"', Auth::user()->name, $title),
            entityType: 'ContentShoot',
            entityId: null
        );

        return redirect()->route('shoots.index')->with('success', "Shoot \"{$title}\" has been removed.");
    }
}
