@extends('layouts.app')
@section('title', 'My Weekly Plans')
@section('content')

<div class="space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">My Weekly Plans & Roadmaps</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Directives, daily focus areas, and goals assigned and shared by your Team Lead</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('history.index', ['action' => 'all_tasks']) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                <i data-lucide="history" class="w-4 h-4"></i>
                <span>Action History</span>
            </a>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Assigned Plans</div>
            <div class="text-2xl font-black text-slate-800 mt-1">{{ $plans->count() }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-500">Active / Shared</div>
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

    <!-- Plans Cards Feed -->
    <div class="space-y-4">
        @forelse($plans as $plan)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6 transition hover:border-slate-300">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 class="text-base sm:text-lg font-black text-slate-900">{{ $plan->title }}</h2>
                            
                            @if($plan->priority === 'critical')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">Critical Priority</span>
                            @elseif($plan->priority === 'high')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">High Priority</span>
                            @elseif($plan->priority === 'medium')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">Medium Priority</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-600 border border-slate-200">Low Priority</span>
                            @endif

                            @if($plan->status === 'completed')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">Completed</span>
                            @elseif($plan->status === 'in-progress')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">In Progress</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">Shared with You</span>
                            @endif
                        </div>
                        
                        <div class="flex items-center gap-3 text-xs text-slate-500 mt-1.5 flex-wrap">
                            <span class="flex items-center gap-1 font-medium">
                                <i data-lucide="user" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Planned by: {{ $plan->creator->name ?? 'Team Lead' }}</span>
                            </span>
                            <span>&bull;</span>
                            <span class="flex items-center gap-1 font-medium">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>{{ $plan->week_start_date->format('D, d M') }} – {{ $plan->week_end_date->format('D, d M Y') }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('plans.show', $plan) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                            <i data-lucide="folder-open" class="w-4 h-4"></i>
                            <span>Open Plan Details</span>
                        </a>
                    </div>
                </div>

                <!-- Goals Section -->
                @if($plan->goals)
                    <div class="mt-4 p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-1">Key Weekly Objectives</div>
                        <div class="text-xs text-slate-700 leading-relaxed">{{ $plan->goals }}</div>
                    </div>
                @endif

                <!-- Daily Breakdown Pills -->
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
                    @php
                        $dayNames = [
                            'monday'    => 'Monday',
                            'tuesday'   => 'Tuesday',
                            'wednesday' => 'Wednesday',
                            'thursday'  => 'Thursday',
                            'friday'    => 'Friday',
                            'saturday'  => 'Saturday',
                            'sunday'    => 'Sunday',
                        ];
                    @endphp
                    @foreach($dayNames as $dKey => $dLabel)
                        @php $dayTask = $plan->days_breakdown[$dKey] ?? null; @endphp
                        <div class="p-3 rounded-xl border {{ $dayTask ? 'bg-indigo-50/40 border-indigo-100' : 'bg-slate-50/50 border-slate-100' }}">
                            <div class="text-[10px] font-extrabold {{ $dayTask ? 'text-indigo-700' : 'text-slate-400' }} uppercase tracking-wider">
                                {{ $dLabel }}
                            </div>
                            <div class="text-xs {{ $dayTask ? 'text-slate-800 font-medium' : 'text-slate-400 italic' }} mt-1 line-clamp-2" title="{{ $dayTask }}">
                                {{ $dayTask ?: 'No specific task assigned' }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Quick Progress Form if in-progress / shared -->
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="text-xs text-slate-500 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-4 h-4 text-slate-400"></i>
                        <span>Keep your TL informed by updating status as you complete daily goals</span>
                    </div>

                    <form method="POST" action="{{ route('plans.status.update', $plan) }}" class="flex items-center gap-2 m-0">
                        @csrf @method('PUT')
                        <select name="status" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 font-bold text-slate-700 focus:bg-white outline-hidden">
                            <option value="shared" {{ $plan->status === 'shared' ? 'selected' : '' }}>Shared</option>
                            <option value="in-progress" {{ $plan->status === 'in-progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ $plan->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                        <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition">
                            Update
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400 shadow-xs">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="calendar-range" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-slate-700 text-sm">No weekly plans assigned to you yet</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Your Team Lead will formulate and share weekly itineraries, schedules, and targets here.</p>
            </div>
        @endforelse
    </div>

</div>

@endsection

