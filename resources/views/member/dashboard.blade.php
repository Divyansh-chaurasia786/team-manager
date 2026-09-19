@extends('layouts.app')
@section('title', 'Staff Dashboard')
@section('content')

<div class="space-y-6 pb-28 lg:pb-16">

    <!-- 🌟 EXECUTIVE COMMAND HERO (Exact EcoFone HRMS Design) -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 border border-indigo-500/20 shadow-xl">
        <!-- Ambient Glow Decorators -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <!-- Left: Profile & Role Pill -->
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
                            Staff Member
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-300 mt-1 flex items-center gap-2 flex-wrap">
                        <span>EcoFone Operations</span>
                        <span class="text-slate-500">•</span>
                        <span class="text-emerald-400 font-semibold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span> Active Workstream
                        </span>
                    </p>
                </div>
            </div>

            <!-- Right: Quick Actions -->
            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-2.5 w-full lg:w-auto">
                <a href="{{ route('plans.index') }}" class="px-3.5 py-2.5 bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-200 text-xs font-bold rounded-xl backdrop-blur-md border border-indigo-400/30 transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i data-lucide="calendar-range" class="w-4 h-4 text-indigo-300"></i>
                    <span>Weekly Plan</span>
                </a>
                <a href="{{ route('tasks.index') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2 cursor-pointer border border-indigo-400/30">
                    <i data-lucide="check-square" class="w-4 h-4 text-indigo-200"></i>
                    <span>My Deliverables</span>
                </a>
                <a href="{{ route('upload.index') }}" class="col-span-2 sm:col-span-1 px-3.5 py-2.5 bg-emerald-600/90 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer border border-emerald-400/30">
                    <i data-lucide="cloud-upload" class="w-4 h-4 text-emerald-200"></i>
                    <span>Upload Drive</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ⏰ PERSONAL SCHEDULED 2-DAY DEADLINE & SHOOT REMINDERS -->
    @if($upcomingTaskReminders->count() > 0 || $upcomingShootReminders->count() > 0)
        <div class="bg-gradient-to-r from-amber-500/10 via-rose-500/5 to-indigo-500/10 rounded-3xl p-5 sm:p-6 border border-amber-300/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-500/30 shrink-0">
                        <i data-lucide="alarm-clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm sm:text-base font-black text-slate-900">Your Scheduled Reminders (Due Within 2 Days)</h2>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-900 border border-amber-300">
                                {{ $upcomingTaskReminders->count() + $upcomingShootReminders->count() }} Action Item(s)
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-0.5">
                            You have tasks or social media shoots scheduled within the next 48 hours requiring submission or preparation.
                        </p>
                    </div>
                </div>

                <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-700 hover:text-indigo-600 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs">
                    <span>View All Tasks</span>
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
                                    <span>Assigned by: <strong class="text-slate-800">{{ $task->assignedBy->name }}</strong></span>
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                    <i data-lucide="calendar-plus" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Assigned: <strong class="text-slate-600">{{ $task->created_at->format('d M, h:i A') }}</strong></span>
                                </div>
                                @if($task->submitted_at)
                                    <div class="flex items-center gap-1.5 text-[11px] text-indigo-700 font-semibold">
                                        <i data-lucide="upload" class="w-3.5 h-3.5 text-indigo-500"></i>
                                        <span>Submitted: <strong class="text-indigo-900">{{ $task->submitted_at->format('d M, h:i A') }}</strong></span>
                                    </div>
                                @endif
                                @if($task->reviewed_at)
                                    <div class="flex items-center gap-1.5 text-[11px] text-emerald-700 font-semibold">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-500"></i>
                                        <span>Reviewed by TL: <strong class="text-emerald-900">{{ $task->reviewed_at->format('d M, h:i A') }}</strong></span>
                                    </div>
                                @endif
                                <div class="flex items-center gap-1.5 font-semibold text-slate-700">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 {{ $rem['is_urgent'] ? 'text-amber-600' : 'text-slate-400' }}"></i>
                                    <span class="{{ $rem['is_urgent'] ? 'text-amber-800 font-bold' : '' }}">{{ $rem['label'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-medium">Deliverable</span>
                            @if($task->status === 'submitted')
                                <a href="{{ route('tasks.index') }}" class="text-xs font-bold text-purple-600 hover:text-purple-800 inline-flex items-center gap-1">
                                    <span>View Submission</span>
                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                </a>
                            @else
                                <a href="{{ route('tasks.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1">
                                    <span>Submit Work</span>
                                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach

                <!-- Shoot Reminders -->
                @foreach($upcomingShootReminders as $shoot)
                    @php
                        $sDate = $shoot->shoot_date;
                        $sBadge = 'Shoot in 2 Days';
                        $sBadgeClass = 'bg-pink-50 text-pink-700 border-pink-200';
                        $sTimeRel = '';
                        if ($sDate) {
                            if ($sDate->isPast()) {
                                $sBadge = 'Past Shoot';
                                $sBadgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
                                $sTimeRel = 'Finished ' . $sDate->diffForHumans();
                            } elseif ($sDate->isToday()) {
                                $sBadge = 'Shoot Today';
                                $sBadgeClass = 'bg-rose-50 text-rose-700 border-rose-300';
                                $diffMin = (int) max(0, now()->diffInMinutes($sDate, false));
                                $sh = intdiv($diffMin, 60);
                                $sm = $diffMin % 60;
                                $sTimeRel = ($sh > 0 ? $sh . 'h ' : '') . $sm . 'm left';
                            } elseif ($sDate->isTomorrow()) {
                                $sBadge = 'Shoot Tomorrow';
                                $sBadgeClass = 'bg-amber-50 text-amber-800 border-amber-300';
                                $diffH = (int) ceil(now()->diffInRealHours($sDate, false));
                                $sTimeRel = 'in ~' . $diffH . 'h';
                            } else {
                                $sTimeRel = $sDate->diffForHumans();
                            }
                        }
                    @endphp
                    <div class="bg-white rounded-2xl p-4 border border-pink-200/80 shadow-2xs hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider flex items-center gap-1 border {{ $sBadgeClass }}">
                                    <i data-lucide="video" class="w-3 h-3"></i>
                                    <span>{{ $sBadge }}</span>
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
                                    <span>Shooting Date: <strong class="text-slate-800">{{ $shoot->shoot_date ? $shoot->shoot_date->format('d M, h:i A') : 'Schedule TBD' }}</strong></span>
                                    @if($sTimeRel)
                                        <span class="text-[10px] font-semibold {{ $sDate && $sDate->isToday() ? 'text-rose-600' : 'text-slate-400' }}">({{ $sTimeRel }})</span>
                                    @endif
                                </div>
                                @if($shoot->location)
                                    <div class="flex items-center gap-1.5 text-slate-700 truncate">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $shoot->location }}</span>
                                    </div>
                                @endif
                                @if($shoot->instagram_handle || $shoot->youtube_channel)
                                    <div class="flex items-center gap-1.5 text-slate-500 text-[11px] truncate">
                                        <i data-lucide="at-sign" class="w-3.5 h-3.5 text-pink-400"></i>
                                        <span>{{ $shoot->instagram_handle ?: $shoot->youtube_channel }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] text-indigo-700 font-bold bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200/60">
                                Assigned Shoot Lead
                            </span>
                            <a href="{{ route('shoots.show', $shoot) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1">
                                <span>Manage Shoot</span>
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 📊 VITALS METRIC CARDS (5 Core Delivery Columns) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-all group">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="list-todo" class="w-4 h-4"></i>
                </div>
                <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Total</span>
            </div>
            <div class="mt-3">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Assigned</div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ $tasks->count() }} <span class="text-xs font-medium text-slate-400">tasks</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-all group">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="hourglass" class="w-4 h-4"></i>
                </div>
                <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Pending</span>
            </div>
            <div class="mt-3">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">To Start</div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ $statusCounts['pending'] }} <span class="text-xs font-medium text-slate-400">waiting</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-all group">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="activity" class="w-4 h-4"></i>
                </div>
                <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">Active</span>
            </div>
            <div class="mt-3">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">In Progress</div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ $statusCounts['in-progress'] }} <span class="text-xs font-medium text-slate-400">active</span></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-purple-200/80 shadow-xs hover:shadow-md transition-all group">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="send" class="w-4 h-4"></i>
                </div>
                <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Under Review</span>
            </div>
            <div class="mt-3">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Submitted</div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ $statusCounts['submitted'] }} <span class="text-xs font-medium text-purple-500 font-bold">in review</span></div>
            </div>
        </div>

        <div class="col-span-2 sm:col-span-1 bg-white p-4 rounded-2xl border border-emerald-200/80 shadow-xs hover:shadow-md transition-all group">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
                <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Done</span>
            </div>
            <div class="mt-3">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Completed</div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ $statusCounts['completed'] }} <span class="text-xs font-medium text-emerald-600 font-bold">approved</span></div>
            </div>
        </div>
    </div>

    @php
        $totalTasksCount = max(1, $tasks->count());
        $donePct = round(($statusCounts['completed'] / $totalTasksCount) * 100);
        $reviewPct = round(($statusCounts['submitted'] / $totalTasksCount) * 100);
        $activePct = round(($statusCounts['in-progress'] / $totalTasksCount) * 100);
        $pendingPct = round(($statusCounts['pending'] / $totalTasksCount) * 100);
        $weekApprovedTotal = $weeklyTrend->sum('completed');
        $weekSubmittedTotal = $weeklyTrend->sum('submitted');
        $weekActiveTotal = $weeklyTrend->sum('active');
    @endphp

    <!-- 📈 CHARTS ROW (Executive Redesign: Modern Analytics & Velocity) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- Status Donut & Progress Breakdown -->
        <div class="lg:col-span-5 bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-xl bg-indigo-50 text-indigo-600">
                        <i data-lucide="pie-chart" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Task Status Overview</h3>
                        <p class="text-[11px] text-slate-400">Distribution of workload</p>
                    </div>
                </div>
                <span class="text-xs font-black px-2.5 py-1 rounded-xl bg-slate-100 text-slate-800 border border-slate-200/80">
                    {{ $tasks->count() }} Total
                </span>
            </div>

            <!-- Sleek Side-by-Side Donut & Metrics Progress -->
            <div class="py-4 flex flex-col sm:flex-row items-center justify-between gap-5">
                <!-- Donut Chart Canvas Container -->
                <div class="relative w-36 h-36 shrink-0 flex items-center justify-center">
                    <canvas id="myStatusDonut"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none select-none">
                        @if($tasks->count() > 0)
                            <span class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $tasks->count() }}</span>
                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400 mt-0.5">Tasks</span>
                        @else
                            <i data-lucide="check" class="w-6 h-6 text-emerald-500 mb-0.5"></i>
                            <span class="text-[10px] font-black uppercase text-slate-400">Clear</span>
                        @endif
                    </div>
                </div>

                <!-- Structured Metric Progress Bars -->
                <div class="w-full flex-1 space-y-2.5">
                    <!-- Completed -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-bold text-slate-700 flex items-center gap-1.5 text-[11px]">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Completed
                            </span>
                            <span class="text-[11px] font-mono text-slate-500 font-bold">
                                <strong>{{ $statusCounts['completed'] }}</strong> <span class="text-slate-400 font-normal">({{ $donePct }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $donePct }}%"></div>
                        </div>
                    </div>

                    <!-- In Review -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-bold text-slate-700 flex items-center gap-1.5 text-[11px]">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span> In Review
                            </span>
                            <span class="text-[11px] font-mono text-slate-500 font-bold">
                                <strong>{{ $statusCounts['submitted'] }}</strong> <span class="text-slate-400 font-normal">({{ $reviewPct }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-purple-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $reviewPct }}%"></div>
                        </div>
                    </div>

                    <!-- In Progress -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-bold text-slate-700 flex items-center gap-1.5 text-[11px]">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span> In Progress
                            </span>
                            <span class="text-[11px] font-mono text-slate-500 font-bold">
                                <strong>{{ $statusCounts['in-progress'] }}</strong> <span class="text-slate-400 font-normal">({{ $activePct }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $activePct }}%"></div>
                        </div>
                    </div>

                    <!-- Pending -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-bold text-slate-700 flex items-center gap-1.5 text-[11px]">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span> Pending Start
                            </span>
                            <span class="text-[11px] font-mono text-slate-500 font-bold">
                                <strong>{{ $statusCounts['pending'] }}</strong> <span class="text-slate-400 font-normal">({{ $pendingPct }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-slate-400 h-1.5 rounded-full transition-all duration-500" style="width: {{ $pendingPct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span>Task Distribution</span>
                <a href="{{ route('tasks.index') }}" class="font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-0.5">
                    <span>Manage Tasks</span> <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>

        <!-- 7-Day Deliverables Velocity Stacked Modern Bar Chart -->
        <div class="lg:col-span-7 bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-xl bg-emerald-50 text-emerald-600">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">7-Day Deliverable Velocity</h3>
                        <p class="text-[11px] text-slate-400">Daily productivity & submission velocity</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 text-[11px] font-black border border-emerald-200/70">
                        {{ $weekApprovedTotal }} Completed This Week
                    </span>
                </div>
            </div>

            <!-- Stacked Chart Canvas -->
            <div class="h-44 sm:h-48 relative my-2 w-full">
                <canvas id="weeklyVelocityChart"></canvas>
            </div>

            <!-- Clean Legend & Summary -->
            <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] flex-wrap gap-2">
                <div class="flex items-center gap-3.5 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Done: <strong class="text-emerald-700 font-mono">{{ $weekApprovedTotal }}</strong></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        <span>Submitted: <strong class="text-purple-700 font-mono">{{ $weekSubmittedTotal }}</strong></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700">
                        <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                        <span>Active: <strong class="text-indigo-700 font-mono">{{ $weekActiveTotal }}</strong></span>
                    </span>
                </div>
                <a href="{{ route('tasks.index') }}" class="font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-0.5">
                    <span>Task Stream</span> <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 🎬 MY ASSIGNED REEL SHOOTS & PRODUCTION PIPELINE -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center">
                    <i data-lucide="clapperboard" class="w-4 h-4"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-slate-900 text-sm">Reel Shoots Managed by You</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ $assignedShoots->count() }} managed
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400">Shoots assigned to you to manage — update shooting completed, move to editing, and review</p>
                </div>
            </div>
            <a href="{{ route('shoots.index', ['tab' => 'mine']) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                View In Console <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($assignedShoots as $shoot)
                <div class="bg-slate-50/70 hover:bg-white rounded-2xl p-4 border border-slate-200/80 hover:border-indigo-300 transition-all shadow-2xs hover:shadow-md flex flex-col justify-between group">
                    <div class="space-y-3">
                        <!-- Top Row: Managing Member Badge & Current Status -->
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <!-- Managing Member Badge -->
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black bg-indigo-600 text-white shadow-2xs">
                                <i data-lucide="shield-check" class="w-3 h-3"></i>
                                <span>Managing Member</span>
                            </span>

                            <!-- Production Status Badge -->
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase {{ $shoot->status_badge['class'] }}">
                                <i data-lucide="{{ $shoot->status_badge['icon'] }}" class="w-3 h-3"></i>
                                <span>{{ $shoot->status_badge['label'] }}</span>
                            </span>
                        </div>

                        <!-- Shoot Title -->
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm leading-snug group-hover:text-indigo-600 transition line-clamp-2">
                                <a href="{{ route('shoots.show', $shoot) }}">{{ $shoot->title }}</a>
                            </h4>
                            @if($shoot->hook)
                                <p class="text-[11px] text-slate-500 italic line-clamp-2 mt-1">"{{ $shoot->hook }}"</p>
                            @endif
                        </div>

                        <!-- Logistics -->
                        <div class="space-y-1 text-xs text-slate-600 bg-white/80 p-2.5 rounded-xl border border-slate-200/60">
                            <div class="flex items-center gap-1.5 font-bold text-slate-800">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-orange-500"></i>
                                <span>Shooting Date: {{ $shoot->shoot_date ? $shoot->shoot_date->format('d M, h:i A') : 'Schedule TBD' }}</span>
                            </div>
                            @if($shoot->location)
                                <div class="flex items-center gap-1.5 text-slate-500 truncate text-[11px]">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $shoot->location }}</span>
                                </div>
                            @endif
                            <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                                <span>Assigned by TL: <strong class="text-slate-700">{{ $shoot->creator->name }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Direct 1-Click Status Transitions & Script Reader -->
                    <div class="mt-4 pt-3 border-t border-slate-200/70 space-y-2">
                        <!-- Quick Status Changers -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @if($shoot->status === 'scheduled' || $shoot->status === 'planning' || $shoot->status === 'scripting')
                                <form method="POST" action="{{ route('shoots.status.update', $shoot) }}" class="m-0 flex-1">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="shooting">
                                    <button type="submit" class="w-full py-1.5 px-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-[11px] font-extrabold flex items-center justify-center gap-1 transition shadow-2xs cursor-pointer">
                                        <i data-lucide="video" class="w-3 h-3"></i>
                                        <span>Start Shooting</span>
                                    </button>
                                </form>
                            @elseif($shoot->status === 'shooting')
                                <form method="POST" action="{{ route('shoots.status.update', $shoot) }}" class="m-0 flex-1">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="editing">
                                    <button type="submit" class="w-full py-1.5 px-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-[11px] font-extrabold flex items-center justify-center gap-1 transition shadow-2xs cursor-pointer">
                                        <i data-lucide="scissors" class="w-3 h-3"></i>
                                        <span>Move to Editing</span>
                                    </button>
                                </form>
                            @elseif($shoot->status === 'editing')
                                <form method="POST" action="{{ route('shoots.status.update', $shoot) }}" class="m-0 flex-1">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="review">
                                    <button type="submit" class="w-full py-1.5 px-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[11px] font-extrabold flex items-center justify-center gap-1 transition shadow-2xs cursor-pointer">
                                        <i data-lucide="eye" class="w-3 h-3"></i>
                                        <span>Submit for Review</span>
                                    </button>
                                </form>
                            @elseif($shoot->status === 'review')
                                <div class="text-[11px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-200 flex items-center gap-1 flex-1 justify-center">
                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                    <span>Under Review</span>
                                </div>
                            @endif

                            <!-- Script Reader Button -->
                            <a href="{{ route('shoots.show', $shoot) }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold inline-flex items-center gap-1 transition" title="Read shoot dialogue and call sheet">
                                <i data-lucide="file-text" class="w-3 h-3 text-slate-500"></i>
                                <span>Read Script</span>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-slate-400 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                    <i data-lucide="clapperboard" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                    <p class="text-xs font-bold text-slate-600">No reel shoots currently assigned to you</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">When your Team Lead assigns you as Camera, Model, or Editor, they will appear here with 1-click status changers.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- 📋 RECENT DELIVERABLES & DRIVE MODULE -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Deliverables List -->
        <div class="lg:col-span-8 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">My Active Deliverables</h3>
                </div>
                <a href="{{ route('tasks.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 transition">All Tasks</a>
            </div>

            <div class="space-y-3">
                @forelse($tasks->take(5) as $task)
                    <div class="p-4 rounded-xl border border-slate-100 hover:border-slate-200 hover:bg-slate-50/50 transition flex items-center justify-between gap-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center font-bold text-xs {{ $task->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($task->status === 'submitted' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700') }}">
                                <i data-lucide="{{ $task->status === 'completed' ? 'check' : ($task->status === 'submitted' ? 'send' : 'clock') }}" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ $task->title }}</h4>
                                <div class="flex items-center gap-2 text-[11px] mt-0.5 flex-wrap">
                                    <span class="text-slate-500">From: {{ $task->assignedBy->name }}</span>
                                    <span class="text-slate-400">•</span>
                                    <span class="font-semibold {{ $task->isOverdue() ? 'text-rose-600' : ($task->deadline->isToday() ? 'text-rose-600' : ($task->deadline->isTomorrow() ? 'text-amber-700' : 'text-slate-600')) }}">
                                        {{ $task->due_label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if($task->status === 'completed')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">Completed</span>
                            @elseif($task->status === 'submitted')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">In Review</span>
                            @elseif($task->status === 'in-progress')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">In Progress</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-600">Pending</span>
                            @endif

                            @if($task->isOverdue())
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">Overdue</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-slate-400">
                        <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold">No deliverables assigned yet</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: My Recent Drive Uploads -->
        <div class="lg:col-span-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                        <i data-lucide="cloud" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Recent Cloud Uploads</h3>
                </div>
                <a href="{{ route('upload.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 transition">Upload</a>
            </div>

            <div class="space-y-2.5">
                @forelse($driveFiles->take(5) as $file)
                    <a href="{{ $file->drive_url }}" target="_blank" class="p-3 rounded-xl border border-slate-100 hover:border-indigo-200 hover:bg-indigo-50/20 transition flex items-center justify-between gap-3 group">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 text-slate-600 group-hover:bg-indigo-600 group-hover:text-white flex items-center justify-center shrink-0 transition-colors">
                                <i data-lucide="{{ $file->file_type === 'photo' ? 'image' : ($file->file_type === 'video' ? 'video' : 'file-text') }}" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-800 truncate group-hover:text-indigo-600">{{ $file->original_name }}</div>
                                <div class="text-[10px] text-slate-400 capitalize">{{ $file->file_type }} &bull; {{ $file->upload_date }}</div>
                            </div>
                        </div>
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600 shrink-0"></i>
                    </a>
                @empty
                    <div class="py-8 text-center text-slate-400">
                        <i data-lucide="cloud-off" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold">No files uploaded yet</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 📜 MY RECENT ACTIVITY AUDIT TRAIL -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="history" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">My Recent Activity History</h3>
                    <p class="text-[11px] text-slate-400">Your downloads, uploads, and deliverable updates</p>
                </div>
            </div>
            <a href="{{ route('history.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                View All History <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
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
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $badge['label'] }}</div>
                        </div>
                    </div>
                    <div class="text-right text-[10px] text-slate-400 shrink-0 font-mono">
                        {{ $act->created_at->diffForHumans() }}
                    </div>
                </div>
            @empty
                <div class="py-6 text-center text-slate-400 text-xs font-semibold">
                    No activity recorded yet. Downloads, uploads and task notes will appear here.
                </div>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Task Status Donut (Sleek Compact Ring)
    const donutCtx = document.getElementById('myStatusDonut');
    if (donutCtx) {
        const hasTasks = {{ $tasks->count() > 0 ? 'true' : 'false' }};
        const statusData = hasTasks 
            ? [
                {{ $statusCounts['completed'] }},
                {{ $statusCounts['submitted'] }},
                {{ $statusCounts['in-progress'] }},
                {{ $statusCounts['pending'] }}
              ]
            : [1];

        const bgColors = hasTasks 
            ? ['#10b981', '#a855f7', '#3b82f6', '#94a3b8']
            : ['#e2e8f0'];

        const labels = hasTasks
            ? ['Completed', 'In Review', 'In Progress', 'Pending Start']
            : ['No Tasks'];

        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: statusData,
                    backgroundColor: bgColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: hasTasks ? 4 : 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: hasTasks,
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Inter', size: 12, weight: '700' },
                        bodyFont: { family: 'Inter', size: 11 },
                        padding: 8,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(ctx) {
                                const val = ctx.raw || 0;
                                const total = {{ max(1, $tasks->count()) }};
                                const pct = Math.round((val / total) * 100);
                                return ` ${ctx.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: '78%'
            }
        });
    }

    // 2. 7-Day Deliverable Velocity Chart (Stacked Pill Bars)
    const velocityCtx = document.getElementById('weeklyVelocityChart');
    if (velocityCtx) {
        const trendData = {!! json_encode($weeklyTrend) !!};
        
        new Chart(velocityCtx, {
            type: 'bar',
            data: {
                labels: trendData.map(d => d.short),
                datasets: [
                    {
                        label: 'Approved & Completed',
                        data: trendData.map(d => d.completed),
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 26
                    },
                    {
                        label: 'Submitted for Review',
                        data: trendData.map(d => d.submitted),
                        backgroundColor: '#a855f7',
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 26
                    },
                    {
                        label: 'Active In Flight',
                        data: trendData.map(d => d.active),
                        backgroundColor: '#818cf8',
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 26
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            font: { family: 'Inter', size: 11, weight: '600' },
                            color: '#64748b'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        suggestedMax: 3,
                        ticks: {
                            stepSize: 1,
                            precision: 0,
                            font: { family: 'Inter', size: 10, weight: '600' },
                            color: '#94a3b8'
                        },
                        grid: {
                            color: '#f8fafc',
                            drawBorder: false
                        },
                        border: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Inter', size: 12, weight: '700' },
                        bodyFont: { family: 'Inter', size: 11 },
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            title: function(items) {
                                const idx = items[0].dataIndex;
                                return trendData[idx] ? trendData[idx].label : '';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush