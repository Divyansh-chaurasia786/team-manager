@extends('layouts.app')
@section('title', 'Weekly Planning')
@section('content')

<div class="space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Weekly Planning & Schedules</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Formulate weekly objectives, day-by-day itineraries, and assign them directly to employees</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('history.index', ['action' => 'all_tasks']) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                <i data-lucide="history" class="w-4 h-4"></i>
                <span>Planning History</span>
            </a>
            <button type="button" onclick="document.getElementById('createPlanModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer border border-indigo-400/30">
                <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                <span>Create Weekly Plan</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Total Plans</div>
            <div class="text-2xl font-black text-slate-800 mt-1">{{ $plans->count() }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-500">Shared / Active</div>
            <div class="text-2xl font-black text-indigo-600 mt-1">{{ $plans->where('status', 'shared')->count() }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-amber-500">In Progress</div>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ $plans->where('status', 'in-progress')->count() }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-500">Completed</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $plans->where('status', 'completed')->count() }}</div>
        </div>
    </div>

    <!-- Weekly Plans List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar-range" class="w-5 h-5 text-indigo-600"></i>
                <h2 class="font-extrabold text-slate-900 text-sm sm:text-base">Assigned Weekly Plans</h2>
            </div>
            <span class="text-xs text-slate-400 font-medium">{{ $plans->count() }} records</span>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-extrabold tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4">Plan Title & Goals</th>
                        <th class="py-3.5 px-4">Assigned Member</th>
                        <th class="py-3.5 px-4">Week Range</th>
                        <th class="py-3.5 px-4">Priority</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Daily Breakdown</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($plans as $plan)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-4 px-4 max-w-xs">
                                <a href="{{ route('plans.show', $plan) }}" class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition block">
                                    {{ $plan->title }}
                                </a>
                                @if($plan->goals)
                                    <div class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{{ $plan->goals }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-bold text-[10px] flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($plan->employee->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ $plan->employee->name ?? 'Unknown' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $plan->employee->designation ?? 'Team Member' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 font-medium text-slate-700 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $plan->week_start_date->format('d M') }} – {{ $plan->week_end_date->format('d M Y') }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if($plan->priority === 'critical')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">Critical</span>
                                @elseif($plan->priority === 'high')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">High</span>
                                @elseif($plan->priority === 'medium')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">Medium</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-600 border border-slate-200">Low</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if($plan->status === 'completed')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">Completed</span>
                                @elseif($plan->status === 'in-progress')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">In Progress</span>
                                @elseif($plan->status === 'shared')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">Shared</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-600 border border-slate-200">Draft</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-1">
                                    @php
                                        $days = ['monday' => 'M', 'tuesday' => 'T', 'wednesday' => 'W', 'thursday' => 'T', 'friday' => 'F'];
                                    @endphp
                                    @foreach($days as $key => $letter)
                                        @php $hasContent = !empty($plan->days_breakdown[$key]); @endphp
                                        <span class="w-5 h-5 rounded-md text-[10px] font-black flex items-center justify-center {{ $hasContent ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400' }}" title="{{ ucfirst($key) }}: {{ $hasContent ? $plan->days_breakdown[$key] : 'Not specified' }}">
                                            {{ $letter }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('plans.show', $plan) }}" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg font-bold text-[11px] transition flex items-center gap-1">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>View</span>
                                    </a>
                                    <form method="POST" action="{{ route('plans.destroy', $plan) }}" onsubmit="return confirm('Delete this weekly plan?')" class="m-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete Plan">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <i data-lucide="calendar-range" class="w-6 h-6"></i>
                                </div>
                                <div class="font-bold text-slate-700 text-sm">No weekly plans created yet</div>
                                <div class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Create weekly targets, daily schedules, and share them directly with your team members.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Stacked Card View -->
        <div class="md:hidden divide-y divide-slate-100">
            @forelse($plans as $plan)
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <a href="{{ route('plans.show', $plan) }}" class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition block">
                                {{ $plan->title }}
                            </a>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                {{ $plan->week_start_date->format('d M') }} – {{ $plan->week_end_date->format('d M Y') }}
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            @if($plan->priority === 'critical')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">Critical</span>
                            @elseif($plan->priority === 'high')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">High</span>
                            @endif

                            @if($plan->status === 'completed')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">Done</span>
                            @elseif($plan->status === 'in-progress')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">In Progress</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">Shared</span>
                            @endif
                        </div>
                    </div>

                    @if($plan->goals)
                        <div class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            {{ $plan->goals }}
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 font-bold text-[9px] flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($plan->employee->name ?? '?', 0, 1)) }}
                            </div>
                            <span class="text-xs font-bold text-slate-700">{{ $plan->employee->name ?? 'Unknown' }}</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('plans.show', $plan) }}" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg font-bold text-[11px] transition flex items-center gap-1">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                                <span>View</span>
                            </a>
                            <form method="POST" action="{{ route('plans.destroy', $plan) }}" onsubmit="return confirm('Delete this weekly plan?')" class="m-0">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete Plan">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">
                    No weekly plans created yet.
                </div>
            @endforelse
        </div>
    </div>

</div>

<!-- Modal: Create Weekly Plan -->
<div id="createPlanModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <i data-lucide="calendar-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-base">Create Weekly Plan & Schedule</h3>
                    <p class="text-xs text-slate-400">Plan is automatically shared with the assigned employee upon creation</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('createPlanModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form method="POST" action="{{ route('plans.store') }}" class="p-4 sm:p-6 overflow-y-auto space-y-4 sm:space-y-5">
            @csrf

            <!-- Basic Details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Plan Title / Objective <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Sprint 37: Customer Acquisition & Onboarding Flow" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-hidden transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Assign To Member <span class="text-rose-500">*</span></label>
                    <select name="user_id" required class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-hidden transition">
                        <option value="">Select an employee...</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->designation ?? 'Member' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Priority Level <span class="text-rose-500">*</span></label>
                    <select name="priority" required class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-hidden transition">
                        <option value="medium" selected>Medium</option>
                        <option value="low">Low</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Week Start Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="week_start_date" value="{{ now()->startOfWeek()->format('Y-m-d') }}" required class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-hidden transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Week End Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="week_end_date" value="{{ now()->endOfWeek()->format('Y-m-d') }}" required class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-hidden transition">
                </div>
            </div>

            <!-- Key Goals & Focus Areas -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Weekly Goals & Deliverables</label>
                <textarea name="goals" rows="3" placeholder="Outline the high-level milestones and key outcomes expected by the end of this week..." class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-hidden transition"></textarea>
            </div>

            <!-- Day-by-Day Planning Breakdown -->
            <div class="border-t border-slate-100 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Day-by-Day Execution Breakdown</span>
                    <span class="text-[11px] text-slate-400">Optional daily guidance</span>
                </div>

                <div class="space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                        <span class="w-full sm:w-24 shrink-0 py-1.5 sm:py-2.5 px-3 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-black text-center">Monday</span>
                        <input type="text" name="monday" placeholder="Monday priorities, standup, research..." class="w-full flex-grow px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-hidden transition">
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                        <span class="w-full sm:w-24 shrink-0 py-1.5 sm:py-2.5 px-3 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-black text-center">Tuesday</span>
                        <input type="text" name="tuesday" placeholder="Core implementation, testing..." class="w-full flex-grow px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-hidden transition">
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                        <span class="w-full sm:w-24 shrink-0 py-1.5 sm:py-2.5 px-3 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-black text-center">Wednesday</span>
                        <input type="text" name="wednesday" placeholder="Mid-week checkpoint, review..." class="w-full flex-grow px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-hidden transition">
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                        <span class="w-full sm:w-24 shrink-0 py-1.5 sm:py-2.5 px-3 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-black text-center">Thursday</span>
                        <input type="text" name="thursday" placeholder="Performance testing, docs..." class="w-full flex-grow px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-hidden transition">
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                        <span class="w-full sm:w-24 shrink-0 py-1.5 sm:py-2.5 px-3 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-black text-center">Friday</span>
                        <input type="text" name="friday" placeholder="Final delivery, sprint review, retro..." class="w-full flex-grow px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-hidden transition">
                    </div>
                </div>
            </div>

            <!-- TL Guidance Notes -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">TL Guidance / Notes for Member</label>
                <textarea name="tl_notes" rows="2" placeholder="Any additional tips, links, blockers to watch out for, or special instructions..." class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-hidden transition"></textarea>
            </div>

            <!-- Modal Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('createPlanModal').classList.add('hidden')" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5">
                    <i data-lucide="share-2" class="w-4 h-4"></i>
                    <span>Create & Share Plan</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

