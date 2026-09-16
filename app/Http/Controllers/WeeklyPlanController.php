<?php

namespace App\Http\Controllers;

use App\Models\WeeklyPlan;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WeeklyPlanController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isTL()) {
            $plans = WeeklyPlan::with(['employee', 'creator'])
                ->where('created_by', $user->id)
                ->latest()
                ->get();
            $members = User::where('created_by', $user->id)->get();

            return view('plans.tl_index', compact('plans', 'members'));
        }

        // Employee view: sees only plans shared with them
        $plans = WeeklyPlan::with('creator')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->latest()
            ->get();

        return view('plans.member_index', compact('plans'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isTL()) abort(403);

        $request->validate([
            'user_id'         => 'required|exists:users,id',
            'title'           => 'required|string|max:255',
            'week_start_date' => 'required|date',
            'week_end_date'   => 'required|date|after_or_equal:week_start_date',
            'priority'        => 'required|in:low,medium,high,critical',
            'goals'           => 'nullable|string',
            'tl_notes'        => 'nullable|string',
            'monday'          => 'nullable|string',
            'tuesday'         => 'nullable|string',
            'wednesday'       => 'nullable|string',
            'thursday'        => 'nullable|string',
            'friday'          => 'nullable|string',
        ]);

        $daysBreakdown = [
            'monday'    => $request->monday,
            'tuesday'   => $request->tuesday,
            'wednesday' => $request->wednesday,
            'thursday'  => $request->thursday,
            'friday'    => $request->friday,
        ];

        $plan = WeeklyPlan::create([
            'created_by'      => $user->id,
            'user_id'         => $request->user_id,
            'title'           => $request->title,
            'goals'           => $request->goals,
            'week_start_date' => $request->week_start_date,
            'week_end_date'   => $request->week_end_date,
            'priority'        => $request->priority,
            'status'          => 'shared', // Instantly shared with employee
            'days_breakdown'  => $daysBreakdown,
            'tl_notes'        => $request->tl_notes,
        ]);

        $employee = User::find($request->user_id);

        ActivityLog::log(
            action: 'weekly_plan_assigned',
            description: sprintf('%s assigned weekly plan "%s" (%s to %s) to %s',
                $user->name,
                $plan->title,
                $plan->week_start_date->format('d M'),
                $plan->week_end_date->format('d M Y'),
                $employee->name
            ),
            entityType: 'WeeklyPlan',
            entityId: $plan->id
        );

        return redirect()->route('plans.index')->with('success', "Weekly plan successfully created and shared with {$employee->name}!");
    }

    public function show(WeeklyPlan $plan)
    {
        $user = Auth::user();

        // Check permission: TL who created or assigned Employee
        if ($plan->created_by !== $user->id && $plan->user_id !== $user->id) {
            abort(403);
        }

        $plan->load(['creator', 'employee']);

        return view('plans.show', compact('plan'));
    }

    public function updateStatus(Request $request, WeeklyPlan $plan)
    {
        $user = Auth::user();

        // Check permission
        if ($plan->created_by !== $user->id && $plan->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'status'            => 'required|in:draft,shared,in-progress,completed',
            'employee_feedback' => 'nullable|string',
        ]);

        $plan->update([
            'status'            => $request->status,
            'employee_feedback' => $request->filled('employee_feedback') ? $request->employee_feedback : $plan->employee_feedback,
        ]);

        ActivityLog::log(
            action: 'weekly_plan_status_updated',
            description: sprintf('%s updated weekly plan "%s" status to %s',
                $user->name,
                $plan->title,
                strtoupper($request->status)
            ),
            entityType: 'WeeklyPlan',
            entityId: $plan->id
        );

        return back()->with('success', 'Weekly plan progress updated successfully.');
    }

    public function destroy(WeeklyPlan $plan)
    {
        $user = Auth::user();
        if ($plan->created_by !== $user->id) abort(403);

        $title = $plan->title;
        $plan->delete();

        ActivityLog::log(
            action: 'weekly_plan_deleted',
            description: sprintf('%s deleted weekly plan "%s"', $user->name, $title),
            entityType: 'WeeklyPlan',
            entityId: null
        );

        return redirect()->route('plans.index')->with('success', 'Weekly plan removed.');
    }
}
