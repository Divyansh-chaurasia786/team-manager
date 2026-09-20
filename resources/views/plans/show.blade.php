@extends('layouts.app')
@section('title', 'Weekly Plan: ' . $plan->title)
@section('content')

<div class="space-y-6 max-w-5xl mx-auto">

    <!-- Breadcrumb & Back Link -->
    <div class="flex items-center justify-between">
        <a href="{{ route('plans.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-indigo-600 transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back to Weekly Plans</span>
        </a>

        @if(auth()->user()->isTL() && $plan->created_by === auth()->id())
            <form method="POST" action="{{ route('plans.destroy', $plan) }}" onsubmit="return confirm('Are you sure you want to delete this plan?')" class="m-0">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition flex items-center gap-1 border border-rose-200">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Delete Plan</span>
                </button>
            </form>
        @endif
    </div>

    <!-- Main Plan Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-7">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ $plan->title }}</h1>
                    
                    @if($plan->priority === 'critical')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200 uppercase">Critical Priority</span>
                    @elseif($plan->priority === 'high')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200 uppercase">High Priority</span>
                    @elseif($plan->priority === 'medium')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase">Medium Priority</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-600 border border-slate-200 uppercase">Low Priority</span>
                    @endif

                    @if($plan->status === 'completed')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">Completed</span>
                    @elseif($plan->status === 'in-progress')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200 uppercase">In Progress</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase">Shared / Active</span>
                    @endif
                </div>

                <div class="flex items-center gap-4 text-xs text-slate-500 mt-2.5 flex-wrap">
                    <span class="flex items-center gap-1.5 font-medium">
                        <i data-lucide="calendar" class="w-4 h-4 text-indigo-600"></i>
                        <span>{{ $plan->week_start_date->format('l, d M Y') }} – {{ $plan->week_end_date->format('l, d M Y') }}</span>
                    </span>
                    <span>&bull;</span>
                    <span class="flex items-center gap-1.5 font-medium">
                        <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
                        <span>Created {{ $plan->created_at->diffForHumans() }}</span>
                    </span>
                </div>
            </div>

            <!-- Stakeholders Info Pill -->
            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-200/80 shrink-0 flex-wrap sm:flex-nowrap">
                <!-- TL -->
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0">
                        {{ strtoupper(substr($plan->creator->name ?? 'TL', 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 font-extrabold uppercase">Team Lead</div>
                        <div class="text-xs font-bold text-slate-800">{{ $plan->creator->name ?? 'N/A' }}</div>
                    </div>
                </div>
                
                <i data-lucide="arrow-right" class="w-4 h-4 text-slate-400 hidden sm:block"></i>

                <!-- Employee -->
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0">
                        {{ strtoupper(substr($plan->employee->name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-[10px] text-indigo-500 font-extrabold uppercase">Assigned Employee</div>
                        <div class="text-xs font-bold text-slate-800">{{ $plan->employee->name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Goals Box -->
        @if($plan->goals)
            <div class="mt-6 p-4 rounded-xl bg-indigo-50/50 border border-indigo-100">
                <div class="flex items-center gap-2 text-xs font-extrabold text-indigo-900 uppercase tracking-wider mb-1.5">
                    <i data-lucide="target" class="w-4 h-4 text-indigo-600"></i>
                    <span>Weekly Strategic Goals & Outcomes</span>
                </div>
                <div class="text-xs sm:text-sm text-indigo-950/80 whitespace-pre-line leading-relaxed">
                    {{ $plan->goals }}
                </div>
            </div>
        @endif
    </div>

    <!-- Day-by-Day Plan Breakdown -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-7">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar-check" class="w-5 h-5 text-indigo-600"></i>
                <h2 class="font-black text-slate-900 text-base">Day-by-Day Focus & Itinerary</h2>
            </div>
            <span class="text-xs text-slate-400 font-medium">Monday through Sunday (All 7 Days)</span>
        </div>

        <div class="space-y-3">
            @php
                $daysConfig = [
                    'monday'    => ['name' => 'Monday', 'color' => 'indigo', 'step' => 1],
                    'tuesday'   => ['name' => 'Tuesday', 'color' => 'indigo', 'step' => 2],
                    'wednesday' => ['name' => 'Wednesday', 'color' => 'indigo', 'step' => 3],
                    'thursday'  => ['name' => 'Thursday', 'color' => 'indigo', 'step' => 4],
                    'friday'    => ['name' => 'Friday', 'color' => 'indigo', 'step' => 5],
                    'saturday'  => ['name' => 'Saturday', 'color' => 'amber', 'step' => 6],
                    'sunday'    => ['name' => 'Sunday', 'color' => 'amber', 'step' => 7],
                ];
            @endphp

            @foreach($daysConfig as $dKey => $dConf)
                @php $taskItem = $plan->days_breakdown[$dKey] ?? null; @endphp
                <div class="p-4 rounded-xl border {{ $taskItem ? 'bg-white border-slate-200 hover:border-indigo-300' : 'bg-slate-50/40 border-slate-100' }} transition flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="w-28 shrink-0 flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-full {{ $taskItem ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-500' }} text-[11px] font-black flex items-center justify-center">
                            {{ $dConf['step'] }}
                        </span>
                        <span class="font-bold text-xs {{ $taskItem ? 'text-slate-800' : 'text-slate-400' }}">
                            {{ $dConf['name'] }}
                        </span>
                    </div>

                    <div class="flex-grow text-xs {{ $taskItem ? 'text-slate-700 font-medium' : 'text-slate-400 italic' }}">
                        {{ $taskItem ?: 'No specific focus items assigned for this day.' }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- TL Guidance & Employee Feedback Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- TL Guidance / Notes -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="message-square-quote" class="w-5 h-5 text-indigo-600"></i>
                <h3 class="font-extrabold text-slate-900 text-sm">Team Lead Directives & Notes</h3>
            </div>
            
            @if($plan->tl_notes)
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-700 leading-relaxed whitespace-pre-line">
                    {{ $plan->tl_notes }}
                </div>
            @else
                <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-100 text-xs text-slate-400 italic">
                    No specific guidance notes attached by Team Lead.
                </div>
            @endif
        </div>

        <!-- Employee Progress & Feedback Update -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                <h3 class="font-extrabold text-slate-900 text-sm">Execution Progress & Feedback</h3>
            </div>

            <form method="POST" action="{{ route('plans.status.update', $plan) }}" class="space-y-3">
                @csrf @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Update Plan Status</label>
                    <select name="status" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-800 focus:bg-white focus:border-indigo-500 outline-hidden">
                        <option value="shared" {{ $plan->status === 'shared' ? 'selected' : '' }}>Shared / Active</option>
                        <option value="in-progress" {{ $plan->status === 'in-progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ $plan->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Employee Feedback / Summary</label>
                    <textarea name="employee_feedback" rows="3" placeholder="Share completion remarks, blockers faced, milestones reached..." class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-hidden transition">{{ $plan->employee_feedback }}</textarea>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                        <i data-lucide="save" class="w-3.5 h-3.5"></i>
                        <span>Save Progress & Feedback</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

@endsection

