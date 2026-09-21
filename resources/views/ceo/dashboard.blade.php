@extends('layouts.app')
@section('title', 'CEO Dashboard')
@section('content')

<div class="space-y-6">

    {{-- HERO --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-violet-950 to-slate-900 text-white p-6 sm:p-8 border border-violet-500/20 shadow-xl">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-violet-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 sm:gap-6">
            <div class="flex items-start sm:items-center gap-3.5 sm:gap-4">
                <div class="relative shrink-0">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl object-cover border border-white/20 shadow-lg">
                    @else
                        <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-tr from-violet-600 to-indigo-500 text-white font-extrabold text-lg sm:text-xl flex items-center justify-center shadow-lg border border-white/20">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 sm:w-4 sm:h-4 bg-emerald-500 rounded-full border-2 border-slate-900"></div>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-lg sm:text-2xl font-black text-white tracking-tight">
                            Welcome, {{ explode(' ', auth()->user()->name)[0] }} 👑
                        </h1>
                        <span class="px-2 sm:px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-extrabold uppercase tracking-wide bg-violet-500/20 text-violet-300 border border-violet-400/30">CEO</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-300 mt-0.5 flex items-center gap-2">
                        <span>EcoFone — Executive Command</span>
                        <span class="text-slate-500">•</span>
                        <span class="text-emerald-400 font-semibold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span> Live
                        </span>
                    </p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-2.5 w-full lg:w-auto">
                <a href="{{ route('tasks.index') }}" class="col-span-2 sm:col-span-1 px-4 py-2.5 bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-violet-600/30 transition flex items-center justify-center gap-2 border border-violet-400/30">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Assign Task</span>
                </a>
                <a href="{{ route('shoots.index') }}" class="px-3.5 py-2.5 bg-white/10 hover:bg-white/15 text-white text-xs font-bold rounded-xl border border-white/15 transition flex items-center justify-center gap-1.5">
                    <i data-lucide="video" class="w-4 h-4 text-violet-300"></i>
                    <span>Shoots</span>
                </a>
                <a href="{{ route('leaves.index') }}" class="px-3.5 py-2.5 bg-white/10 hover:bg-white/15 text-white text-xs font-bold rounded-xl border border-white/15 transition flex items-center justify-center gap-1.5">
                    <i data-lucide="calendar-off" class="w-4 h-4 text-rose-300"></i>
                    <span>Leaves</span>
                </a>
            </div>
        </div>
    </div>

    {{-- KPI STAT CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {{-- Attendance Today --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-3.5 sm:p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Present Today</span>
                <div class="w-7 h-7 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900">{{ $presentCount }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">{{ $absentCount }} absent · {{ $unmarkedCount }} unmarked</div>
        </div>

        {{-- Pending Tasks --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Active Tasks</span>
                <div class="w-7 h-7 rounded-xl bg-amber-50 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900">{{ $pendingTasks }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Pending + In progress</div>
        </div>

        {{-- Needs Review --}}
        <div class="bg-white rounded-2xl border border-indigo-200 p-4 shadow-xs {{ $submittedTasks > 0 ? 'ring-1 ring-indigo-200' : '' }}">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Needs Review</span>
                <div class="w-7 h-7 rounded-xl bg-indigo-50 flex items-center justify-center">
                    <i data-lucide="inbox" class="w-4 h-4 text-indigo-600"></i>
                </div>
            </div>
            <div class="text-2xl font-black {{ $submittedTasks > 0 ? 'text-indigo-700' : 'text-slate-900' }}">{{ $submittedTasks }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Submitted for approval</div>
        </div>

        {{-- Completed Tasks --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Completed</span>
                <div class="w-7 h-7 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-700">{{ $completedTasks }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">All time</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- LEFT: Tasks Needing CEO Review --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center">
                            <i data-lucide="inbox" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900">Tasks Needing Review</h2>
                            <p class="text-[10px] text-slate-400">Submitted by team — approve or request revisions</p>
                        </div>
                    </div>
                    @if($tasksNeedingReview->count() > 0)
                        <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-indigo-100 text-indigo-800 border border-indigo-200 animate-pulse">
                            {{ $tasksNeedingReview->count() }} Pending
                        </span>
                    @endif
                </div>

                @if($tasksNeedingReview->isEmpty())
                    <div class="p-10 text-center">
                        <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-400 mx-auto mb-2"></i>
                        <p class="text-xs text-slate-400 font-semibold">All clear — no pending reviews.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($tasksNeedingReview as $task)
                            <div class="p-4 sm:p-5 hover:bg-slate-50/70 transition">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="space-y-1.5 flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-bold text-slate-900 text-sm">{{ $task->title }}</h3>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-100 text-indigo-800 border border-indigo-200">Submitted</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 flex items-center gap-3 flex-wrap">
                                            <span class="flex items-center gap-1">
                                                <i data-lucide="user" class="w-3 h-3"></i>
                                                {{ $task->assignedTo?->name ?? 'Unassigned' }}
                                            </span>
                                            @if($task->assignedBy)
                                                <span class="flex items-center gap-1">
                                                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                                    Assigned by {{ $task->assignedBy?->name ?? 'Supervisor' }}
                                                </span>
                                            @endif
                                            @if($task->submitted_at)
                                                <span>Submitted {{ $task->submitted_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                        @if($task->submission_remarks)
                                            <p class="text-xs text-slate-600 italic">"{{ Str::limit($task->submission_remarks, 80) }}"</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form method="POST" action="{{ route('tasks.complete', $task) }}" class="m-0">
                                            @csrf @method('PUT')
                                            <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm shadow-emerald-600/20 cursor-pointer">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Team Performance Overview --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                        <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900">Team Performance</h2>
                        <p class="text-[10px] text-slate-400">Task completion per member</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($memberStats as $stat)
                        <div class="px-5 py-3.5 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($stat['member']->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-slate-800">{{ $stat['member']->name }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $stat['member']->designation ?? ucfirst($stat['member']->role) }}</span>
                                    @php $attStatus = $stat['att_status']; @endphp
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md
                                        {{ in_array($attStatus, ['present','wfh','half_day']) ? 'bg-emerald-50 text-emerald-700' : ($attStatus === 'unmarked' ? 'bg-slate-100 text-slate-500' : 'bg-rose-50 text-rose-700') }}">
                                        {{ ucfirst(str_replace('_',' ',$attStatus)) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-[10px] text-slate-500">
                                    <span class="text-emerald-700 font-bold">✓ {{ $stat['completed'] }} done</span>
                                    <span>{{ $stat['pending'] }} active</span>
                                    @if($stat['submitted'] > 0)
                                        <span class="text-indigo-700 font-bold animate-pulse">{{ $stat['submitted'] }} awaiting review</span>
                                    @endif
                                </div>
                            </div>
                            @if($stat['total'] > 0)
                                <div class="text-right shrink-0">
                                    <div class="text-xs font-black text-slate-700">{{ round(($stat['completed'] / max($stat['total'],1)) * 100) }}%</div>
                                    <div class="text-[10px] text-slate-400">completion</div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center text-xs text-slate-400">No team members found.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT: Pending Leaves + Shoot Status --}}
        <div class="space-y-4">

            {{-- Pending Leaves --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <i data-lucide="calendar-off" class="w-3.5 h-3.5"></i>
                        </div>
                        <h3 class="text-xs font-black text-slate-900">Pending Leaves</h3>
                    </div>
                    @if($pendingLeaves->count() > 0)
                        <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-200">{{ $pendingLeaves->count() }}</span>
                    @endif
                </div>
                @if($pendingLeaves->isEmpty())
                    <div class="p-6 text-center text-xs text-slate-400">No pending leave requests.</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($pendingLeaves->take(5) as $leave)
                            <div class="px-4 py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-slate-800 truncate">{{ $leave->user->name }}</div>
                                        <div class="text-[10px] text-slate-500">{{ $leave->leave_type_label ?? ucfirst($leave->leave_type) }} · {{ $leave->total_days }}d</div>
                                        <div class="text-[10px] text-slate-400">{{ $leave->start_date->format('d M') }} – {{ $leave->end_date->format('d M') }}</div>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <form method="POST" action="{{ route('ceo.leaves.approve', $leave) }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[10px] font-bold cursor-pointer transition">✓</button>
                                        </form>
                                        <form method="POST" action="{{ route('ceo.leaves.reject', $leave) }}" class="m-0">
                                            @csrf
                                            <input type="hidden" name="review_notes" value="Rejected by CEO.">
                                            <button type="submit" class="px-2 py-1 bg-rose-500 hover:bg-rose-400 text-white rounded-lg text-[10px] font-bold cursor-pointer transition">✕</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($pendingLeaves->count() > 5)
                        <div class="px-4 py-2 border-t border-slate-100">
                            <a href="{{ route('leaves.index') }}" class="text-[10px] text-indigo-600 font-bold hover:underline">View all {{ $pendingLeaves->count() }} pending →</a>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Shoot Pipeline Summary --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center">
                        <i data-lucide="video" class="w-3.5 h-3.5"></i>
                    </div>
                    <h3 class="text-xs font-black text-slate-900">Shoot Pipeline</h3>
                </div>
                <div class="p-4 space-y-2">
                    @php
                        $shootLabels = [
                            'scheduled' => ['Scheduled',  'bg-blue-100 text-blue-800'],
                            'shooting'  => ['Shooting',   'bg-amber-100 text-amber-800'],
                            'editing'   => ['In Editing', 'bg-purple-100 text-purple-800'],
                            'review'    => ['In Review',  'bg-indigo-100 text-indigo-800'],
                            'published' => ['Published',  'bg-emerald-100 text-emerald-800'],
                        ];
                    @endphp
                    @foreach($shootLabels as $key => [$label, $cls])
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-600 font-semibold">{{ $label }}</span>
                            <span class="text-[11px] font-black px-2 py-0.5 rounded-full {{ $cls }}">{{ $shootsByStatus[$key] ?? 0 }}</span>
                        </div>
                    @endforeach
                    <a href="{{ route('shoots.index') }}" class="mt-2 block text-[10px] text-indigo-600 font-bold hover:underline text-center">View all shoots →</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
