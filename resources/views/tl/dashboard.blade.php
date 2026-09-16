@extends('layouts.app')
@section('title', 'Team Lead Dashboard')
@section('content')

<div class="space-y-6">

    <!-- 🌟 UNIFIED EXECUTIVE COMMAND HERO (Exact HRMS Design) -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 border border-indigo-500/20 shadow-xl">
        <!-- Ambient Glow Decorators -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <!-- Left: TL Profile & Status -->
            <div class="flex items-start sm:items-center gap-4">
                <div class="relative shrink-0">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl object-cover shadow-lg shadow-indigo-600/30 border border-white/20">
                    @else
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-extrabold text-xl sm:text-2xl flex items-center justify-center shadow-lg shadow-indigo-600/30 border border-white/20">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full border-2 border-slate-900"></div>
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                            Welcome back, {{ explode(' ', auth()->user()->name)[0] }} 👋
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                            Team Lead
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-300 mt-1 flex items-center gap-2 flex-wrap">
                        <span>EcoFone Operations</span>
                        <span class="text-slate-500">•</span>
                        <span class="text-emerald-400 font-semibold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span> Live Team Active
                        </span>
                    </p>
                </div>
            </div>

            <!-- Right: High-Priority Executive Actions -->
            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-2.5 w-full lg:w-auto">
                <a href="{{ route('plans.index') }}" class="px-3.5 py-2.5 bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-200 text-xs font-bold rounded-xl backdrop-blur-md border border-indigo-400/30 transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i data-lucide="calendar-range" class="w-4 h-4 text-indigo-300"></i>
                    <span>Weekly Plan</span>
                </a>
                <a href="{{ route('tasks.index') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2 cursor-pointer border border-indigo-400/30">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-200"></i>
                    <span>Assign Task</span>
                </a>
                <a href="{{ route('tl.members') }}" class="px-3.5 py-2.5 bg-white/10 hover:bg-white/15 text-white text-xs font-bold rounded-xl backdrop-blur-md border border-white/15 transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i data-lucide="user-plus" class="w-4 h-4 text-indigo-300"></i>
                    <span>Add Member</span>
                </a>
                <a href="{{ route('upload.index') }}" class="px-3.5 py-2.5 bg-emerald-600/90 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer border border-emerald-400/30">
                    <i data-lucide="cloud-upload" class="w-4 h-4 text-emerald-200"></i>
                    <span>Upload Drive</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ⏰ SCHEDULED 2-DAY DEADLINE & SHOOT REMINDERS -->
    @if($upcomingTaskReminders->count() > 0 || $upcomingShootReminders->count() > 0)
        <div class="bg-gradient-to-r from-amber-500/10 via-rose-500/5 to-indigo-500/10 rounded-3xl p-5 sm:p-6 border border-amber-300/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-500/30 shrink-0">
                        <i data-lucide="alarm-clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm sm:text-base font-black text-slate-900">Scheduled Reminders (Due Within 2 Days)</h2>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-900 border border-amber-300">
                                {{ $upcomingTaskReminders->count() + $upcomingShootReminders->count() }} Urgent Item(s)
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-0.5">
                            High-priority deliverables and video shoots scheduled within the next 48 hours or requiring immediate review.
                        </p>
                    </div>
                </div>

                <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-700 hover:text-indigo-600 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs">
                    <span>Manage All Tasks</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <!-- Reminder Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                <!-- Task Reminders -->
                @foreach($upcomingTaskReminders as $task)
                    @php $rem = $task->deadline_reminder; @endphp
                    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-black border uppercase tracking-wider {{ $rem['class'] }}">
                                    {{ $rem['badge'] }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-slate-100 text-slate-600">
                                    {{ ucfirst($task->status) }}
                                </span>
                            </div>

                            <h3 class="font-bold text-slate-900 text-sm leading-snug line-clamp-2">
                                {{ $task->title }}
                            </h3>

                            <div class="mt-2.5 space-y-1 text-xs text-slate-500">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Assigned to: <strong class="text-slate-800">{{ $task->assignedTo->name }}</strong></span>
                                </div>
                                <div class="flex items-center gap-1.5 font-semibold text-slate-700">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 {{ $rem['is_urgent'] ? 'text-amber-600' : 'text-slate-400' }}"></i>
                                    <span class="{{ $rem['is_urgent'] ? 'text-amber-800 font-bold' : '' }}">{{ $rem['label'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-medium">Task Deliverable</span>
                            <a href="{{ route('tasks.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1">
                                <span>Review Task</span>
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                    </div>
                @endforeach

                <!-- Shoot Reminders -->
                @foreach($upcomingShootReminders as $shoot)
                    <div class="bg-white rounded-2xl p-4 border border-pink-200/80 shadow-2xs hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-pink-50 text-pink-700 border border-pink-200 uppercase tracking-wider flex items-center gap-1">
                                    <i data-lucide="video" class="w-3 h-3 text-pink-600"></i>
                                    <span>Shoot in 2 Days</span>
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase {{ $shoot->status_badge['class'] }}">
                                    {{ $shoot->status_badge['label'] }}
                                </span>
                            </div>

                            <h3 class="font-bold text-slate-900 text-sm leading-snug line-clamp-2">
                                <a href="{{ route('shoots.show', $shoot) }}" class="hover:text-indigo-600">{{ $shoot->title }}</a>
                            </h3>

                            <div class="mt-2.5 space-y-1 text-xs text-slate-500">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-pink-500"></i>
                                    <span>Shoot: <strong class="text-slate-800">{{ $shoot->shoot_date->format('d M, h:i A') }}</strong></span>
                                    <span class="text-[10px] text-slate-400">({{ $shoot->shoot_date->diffForHumans() }})</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-700">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 {{ $shoot->managing_member_id ? 'text-indigo-600' : 'text-slate-400' }}"></i>
                                    <span>Manager: <strong class="{{ $shoot->managing_member_id ? 'text-indigo-700' : 'text-slate-700' }}">{{ $shoot->managing_member_name }}</strong></span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-500 text-[11px]">
                                    <i data-lucide="users" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Cast: {{ $shoot->model_display_name }} &bull; Cam: {{ $shoot->camera_name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-medium">
                                {{ $shoot->platform_info['label'] }}
                                @if($shoot->instagram_handle) &bull; <strong class="text-slate-600">{{ $shoot->instagram_handle }}</strong> @elseif($shoot->youtube_channel) &bull; <strong class="text-slate-600">{{ $shoot->youtube_channel }}</strong> @endif
                            </span>
                            <a href="{{ route('shoots.show', $shoot) }}" class="text-xs font-bold text-pink-600 hover:text-pink-800 inline-flex items-center gap-1">
                                <span>Open Call Sheet</span>
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 📊 TEAM VITALS & WORKFORCE METRICS (Authentic HRMS Cards) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('tl.members') }}" class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md hover:border-indigo-300 hover:-translate-y-0.5 transition-all group">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 group-hover:bg-indigo-50 group-hover:text-indigo-700">Staff</span>
            </div>
            <div class="mt-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Team Strength</div>
                <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $members->count() }} <span class="text-xs font-medium text-slate-400">members</span></div>
            </div>
        </a>

        <a href="{{ route('tasks.index') }}" class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md hover:border-emerald-300 hover:-translate-y-0.5 transition-all group">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Completed</span>
            </div>
            <div class="mt-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tasks Done</div>
                <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $statusCounts['completed'] }} <span class="text-xs font-medium text-slate-400">/ {{ $tasks->count() }}</span></div>
            </div>
        </a>

        <a href="{{ route('tasks.index') }}" class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all group">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Active</span>
            </div>
            <div class="mt-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">In Progress</div>
                <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $statusCounts['in-progress'] }} <span class="text-xs font-medium text-slate-400">active</span></div>
            </div>
        </a>

        <a href="{{ route('tasks.index') }}" class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md hover:border-purple-300 hover:-translate-y-0.5 transition-all group">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="inbox" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700">Review</span>
            </div>
            <div class="mt-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Submitted Work</div>
                <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $statusCounts['submitted'] }} <span class="text-xs font-medium text-slate-400">items</span></div>
            </div>
        </a>
    </div>

    <!-- 📈 CHARTS & ANALYTICS SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart: Status Breakdown -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="pie-chart" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Task Status Overview</h3>
                        <p class="text-[11px] text-slate-400">Live operational status</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col items-center justify-center p-2">
                @if($tasks->count() === 0)
                    <div class="h-[200px] w-full flex flex-col items-center justify-center text-center p-4">
                        <div class="w-16 h-16 rounded-full border-4 border-dashed border-slate-200 flex items-center justify-center text-slate-400 mb-2">
                            <i data-lucide="pie-chart" class="w-7 h-7 text-slate-300"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-600">No Tasks Assigned Yet</span>
                        <span class="text-[11px] text-slate-400 mt-0.5">Assign tasks to view status graph</span>
                    </div>
                @else
                    <div style="width: 100%; max-width: 220px; height: 200px; position: relative;">
                        <canvas id="statusDonut"></canvas>
                    </div>
                @endif
                <div class="grid grid-cols-2 gap-3 mt-4 text-xs font-medium text-slate-600 w-full">
                    <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span> Pending: <strong>{{ $statusCounts['pending'] }}</strong></div>
                    <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> In Progress: <strong>{{ $statusCounts['in-progress'] }}</strong></div>
                    <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span> Submitted: <strong>{{ $statusCounts['submitted'] }}</strong></div>
                    <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Completed: <strong>{{ $statusCounts['completed'] }}</strong></div>
                </div>
            </div>
        </div>

        <!-- Chart: Workload per Member -->
        <div class="lg:col-span-2 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Team Workload Distribution</h3>
                        <p class="text-[11px] text-slate-400">Tasks assigned across members</p>
                    </div>
                </div>
            </div>
            <div style="position: relative; height: 200px; width: 100%;">
                <canvas id="memberBar"></canvas>
            </div>
        </div>
    </div>

    <!-- 👥 TEAM LIVE ROSTER & RECENT ACTIVITIES -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Live Tasks Table -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Recent Task Stream</h3>
                        <p class="text-[11px] text-slate-400">Current work in progress and submissions</p>
                    </div>
                </div>
                <a href="{{ route('tasks.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                    View All Tasks <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 uppercase font-extrabold tracking-wider border-b border-slate-100 pb-2">
                            <th class="py-2.5">Task Name</th>
                            <th class="py-2.5">Assignee</th>
                            <th class="py-2.5">Deadline</th>
                            <th class="py-2.5">Status</th>
                            <th class="py-2.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($tasks->take(5) as $task)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3">
                                    <div class="font-bold text-slate-900">{{ $task->title }}</div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ $task->description }}</div>
                                </td>
                                <td class="py-3">
                                    <span class="font-semibold text-slate-700">{{ $task->assignedTo->name }}</span>
                                </td>
                                <td class="py-3 font-medium text-slate-500">
                                    {{ $task->deadline->format('d M, h:i A') }}
                                </td>
                                <td class="py-3">
                                    @if($task->status === 'completed')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">Done</span>
                                    @elseif($task->status === 'submitted')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">Review</span>
                                    @elseif($task->status === 'in-progress')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">Active</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-600">Pending</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('tasks.index') }}" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg inline-block transition">
                                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">No tasks created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right 1 Col: Cloud Drive Overview -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="cloud-upload" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Google Drive Cloud</h3>
                        <p class="text-[11px] text-slate-400">Automated date-wise archives</p>
                    </div>
                </div>
                <a href="{{ route('upload.index') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-800">
                    Open <i data-lucide="external-link" class="w-3.5 h-3.5 inline"></i>
                </a>
            </div>

            <!-- Drive File Types -->
            <div class="space-y-3 pt-2">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                            <i data-lucide="image" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-800">Photos Archive</div>
                            <div class="text-[10px] text-slate-400">JPG, PNG, WEBP</div>
                        </div>
                    </div>
                    <span class="text-sm font-black text-slate-800">{{ $driveStats['photos'] }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center">
                            <i data-lucide="video" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-800">Videos Archive</div>
                            <div class="text-[10px] text-slate-400">MP4, MOV</div>
                        </div>
                    </div>
                    <span class="text-sm font-black text-slate-800">{{ $driveStats['videos'] }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-800">Documents Archive</div>
                            <div class="text-[10px] text-slate-400">PDF, DOCX, XLSX</div>
                        </div>
                    </div>
                    <span class="text-sm font-black text-slate-800">{{ $driveStats['documents'] }}</span>
                </div>
            </div>

            <div class="pt-2">
                <a href="{{ route('upload.index') }}" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition">
                    <i data-lucide="upload" class="w-4 h-4"></i> Upload Media Files
                </a>
            </div>
        </div>
    </div>

    <!-- 📜 REAL-TIME ACTIVITY AUDIT LOG (Downloads, Uploads, Tasks) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="history" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Live Activity & Download Audit Trail</h3>
                    <p class="text-[11px] text-slate-400">Track file downloads, uploads, and member submissions in real-time</p>
                </div>
            </div>
            <a href="{{ route('history.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                View Full Audit History <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($recentActivities as $act)
                @php $badge = $act->action_badge; @endphp
                <div class="py-3 flex items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 font-bold {{ $badge['bg'] }} border shadow-2xs">
                            <i data-lucide="{{ $badge['icon'] }}" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-slate-800 truncate">{{ $act->description }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">By {{ $act->user->name ?? 'User' }} &bull; {{ $badge['label'] }}</div>
                        </div>
                    </div>
                    <div class="text-right text-[10px] text-slate-400 shrink-0 font-mono">
                        {{ $act->created_at->diffForHumans() }}
                    </div>
                </div>
            @empty
                <div class="py-6 text-center text-slate-400 text-xs font-semibold">
                    No activity recorded yet. Files downloaded, uploaded or tasks updated will automatically display here.
                </div>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Status Donut Chart
    const statusEl = document.getElementById('statusDonut');
    if (statusEl && typeof Chart !== 'undefined') {
        new Chart(statusEl, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Submitted', 'Completed'],
                datasets: [{
                    data: [{{ $statusCounts['pending'] }}, {{ $statusCounts['in-progress'] }}, {{ $statusCounts['submitted'] }}, {{ $statusCounts['completed'] }}],
                    backgroundColor: ['#94A3B8', '#F59E0B', '#4F46E5', '#10B981'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ` ${ctx.label}: ${ctx.raw} task(s)`;
                            }
                        }
                    }
                },
                cutout: '72%'
            }
        });
    }

    // 2. Member Workload Bar Chart
    const memberEl = document.getElementById('memberBar');
    if (memberEl && typeof Chart !== 'undefined') {
        new Chart(memberEl, {
            type: 'bar',
            data: {
                labels: {!! json_encode($taskPerMember->pluck('name')) !!},
                datasets: [{
                    label: 'Tasks Assigned',
                    data: {!! json_encode($taskPerMember->pluck('count')) !!},
                    backgroundColor: '#4F46E5',
                    borderRadius: 8,
                    barThickness: 28,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ` Tasks: ${ctx.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#94A3B8' },
                        grid: { color: '#F1F5F9' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#475569', font: { weight: '600', size: 11 } }
                    }
                }
            }
        });
    }
});
</script>
@endpush