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
                                        <span>Reviewed: <strong class="text-emerald-900">{{ $task->reviewed_at->format('d M, h:i A') }}</strong></span>
                                    </div>
                                @endif
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
                                    <span>Shoot: <strong class="text-slate-800">{{ $shoot->shoot_date ? $shoot->shoot_date->format('d M, h:i A') : 'Schedule TBD' }}</strong></span>
                                    @if($sTimeRel)
                                        <span class="text-[10px] font-semibold {{ $sDate && $sDate->isToday() ? 'text-rose-600' : 'text-slate-400' }}">({{ $sTimeRel }})</span>
                                    @endif
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

    <!-- 🚨 OVERDUE DELIVERABLES REQUIRING IMMEDIATE ATTENTION -->
    @if(isset($overdueCount) && $overdueCount > 0)
        <div class="bg-gradient-to-r from-rose-500/15 via-red-500/10 to-rose-500/5 rounded-3xl p-5 sm:p-6 border-2 border-rose-300 shadow-sm space-y-3.5">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center shadow-md shadow-rose-600/30 shrink-0">
                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm sm:text-base font-black text-rose-950">🚨 Overdue Deliverables (Immediate Attention Required)</h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-rose-100 text-rose-800 border border-rose-300 animate-pulse">
                                {{ $overdueCount }} Overdue Task(s)
                            </span>
                        </div>
                        <p class="text-xs text-rose-700 mt-0.5">
                            These deliverables have exceeded their deadline without being completed. Please review, follow up, or extend deadlines.
                        </p>
                    </div>
                </div>

                <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 text-white hover:bg-rose-700 rounded-xl text-xs font-bold transition shadow-xs">
                    <span>Manage in Tasks</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <!-- Overdue Task Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                @foreach($overdueTasks->take(6) as $otask)
                    <div class="bg-white rounded-2xl p-4 border border-rose-200 shadow-2xs hover:border-rose-400 hover:shadow-sm transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-1.5 mb-2">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3 h-3 text-rose-600"></i>
                                    <span>{{ $otask->due_label }}</span>
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-slate-100 text-slate-600">
                                    {{ ucfirst($otask->status) }}
                                </span>
                            </div>

                            <h3 class="font-bold text-slate-900 text-sm leading-snug line-clamp-2">
                                {{ $otask->title }}
                            </h3>

                            <div class="mt-2.5 space-y-1 text-xs text-slate-500">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Assigned to: <strong class="text-slate-800">{{ $otask->assignedTo->name ?? 'Member' }}</strong></span>
                                </div>
                                <div class="flex items-center gap-1.5 text-rose-600 font-semibold text-[11px]">
                                    <i data-lucide="calendar-x" class="w-3.5 h-3.5 text-rose-500"></i>
                                    <span>Deadline: <strong>{{ $otask->deadline ? $otask->deadline->format('d M, h:i A') : 'None' }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-medium">Overdue task</span>
                            <a href="{{ route('tasks.index') }}" class="text-xs font-bold text-rose-600 hover:text-rose-800 inline-flex items-center gap-1">
                                <span>Review Task</span>
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 📊 TEAM VITALS & WORKFORCE METRICS (Authentic HRMS Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
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

        <!-- Overdue Tasks Card -->
        <a href="{{ route('tasks.index') }}" class="bg-white p-4 sm:p-5 rounded-2xl border {{ ($overdueCount ?? 0) > 0 ? 'border-rose-300 bg-rose-50/10' : 'border-slate-200' }} shadow-xs hover:shadow-md hover:border-rose-400 hover:-translate-y-0.5 transition-all group">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl {{ ($overdueCount ?? 0) > 0 ? 'bg-rose-100 text-rose-600 group-hover:bg-rose-600 group-hover:text-white' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ ($overdueCount ?? 0) > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ ($overdueCount ?? 0) > 0 ? 'Urgent' : 'Clear' }}
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xs font-semibold {{ ($overdueCount ?? 0) > 0 ? 'text-rose-600' : 'text-slate-500' }} uppercase tracking-wider">Overdue Tasks</div>
                <div class="text-2xl font-black {{ ($overdueCount ?? 0) > 0 ? 'text-rose-600' : 'text-slate-900' }} mt-0.5" id="tlMetricOverdue">
                    {{ $overdueCount ?? 0 }} <span class="text-xs font-medium text-slate-400">tasks</span>
                </div>
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
                <div class="text-2xl font-black text-slate-900 mt-0.5" id="tlMetricCompleted">{{ $statusCounts['completed'] }} <span class="text-xs font-medium text-slate-400">/ {{ $tasks->count() }}</span></div>
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
                <div class="text-2xl font-black text-slate-900 mt-0.5" id="tlMetricInProgress">{{ $statusCounts['in-progress'] }} <span class="text-xs font-medium text-slate-400">active</span></div>
            </div>
        </a>

        <a href="{{ route('tasks.index') }}" class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md hover:border-purple-300 hover:-translate-y-0.5 transition-all group col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                    <i data-lucide="inbox" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700">Review</span>
            </div>
            <div class="mt-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Submitted Work</div>
                <div class="text-2xl font-black text-slate-900 mt-0.5" id="tlMetricSubmitted">{{ $statusCounts['submitted'] }} <span class="text-xs font-medium text-slate-400">items</span></div>
            </div>
        </a>
    </div>

    @php
        $totalTlTasks = max(1, $tasks->count());
        $tlDonePct = round(($statusCounts['completed'] / $totalTlTasks) * 100);
        $tlReviewPct = round(($statusCounts['submitted'] / $totalTlTasks) * 100);
        $tlActivePct = round(($statusCounts['in-progress'] / $totalTlTasks) * 100);
        $tlPendingPct = round(($statusCounts['pending'] / $totalTlTasks) * 100);
    @endphp

    <!-- 📈 CHARTS & ANALYTICS SECTION (Executive Modern Redesign) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Card 1: Live Status & Workflow Allocation (5 cols) -->
        <div class="lg:col-span-5 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div>
                <!-- Header -->
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="pie-chart" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm leading-tight">Task Status Distribution</h3>
                            <p class="text-[11px] text-slate-400">Live operational workload across team</p>
                        </div>
                    </div>
                    <span class="text-xs font-black px-2.5 py-1 rounded-xl bg-slate-100 text-slate-800 border border-slate-200/80">
                        {{ $tasks->count() }} Total
                    </span>
                </div>

                <!-- Multi-Segmented Proportional Distribution Track -->
                <div class="pt-4 pb-2">
                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 mb-1.5">
                        <span>Workflow Allocation</span>
                        <span class="font-mono text-slate-700 font-bold">{{ $statusCounts['completed'] }}/{{ $tasks->count() }} Completed</span>
                    </div>
                    @if($tasks->count() > 0)
                        <div class="h-3 w-full bg-slate-100 rounded-full flex overflow-hidden p-0.5 gap-0.5 shadow-inner">
                            @if($tlDonePct > 0)
                                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ $tlDonePct }}%" title="Completed: {{ $statusCounts['completed'] }} ({{ $tlDonePct }}%)"></div>
                            @endif
                            @if($tlReviewPct > 0)
                                <div class="bg-purple-500 h-full rounded-full transition-all duration-500" style="width: {{ $tlReviewPct }}%" title="Submitted: {{ $statusCounts['submitted'] }} ({{ $tlReviewPct }}%)"></div>
                            @endif
                            @if($tlActivePct > 0)
                                <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ $tlActivePct }}%" title="In Progress: {{ $statusCounts['in-progress'] }} ({{ $tlActivePct }}%)"></div>
                            @endif
                            @if($tlPendingPct > 0)
                                <div class="bg-slate-400 h-full rounded-full transition-all duration-500" style="width: {{ $tlPendingPct }}%" title="Pending: {{ $statusCounts['pending'] }} ({{ $tlPendingPct }}%)"></div>
                            @endif
                        </div>
                    @else
                        <div class="h-3 w-full bg-slate-100 rounded-full flex items-center justify-center">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">No active team tasks assigned</span>
                        </div>
                    @endif
                </div>

                <!-- High-Density Executive KPI Grid -->
                <div class="grid grid-cols-2 gap-2.5 pt-2 pb-1">
                    <!-- Completed -->
                    <div class="p-3 rounded-xl bg-emerald-50/50 hover:bg-emerald-50 border border-emerald-100/80 transition group">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-bold text-emerald-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-emerald-200"></span> Completed
                            </span>
                            <span class="text-[10px] font-black px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-mono">{{ $tlDonePct }}%</span>
                        </div>
                        <div class="text-xl font-black text-slate-900 tracking-tight" id="tlChartCompleted">{{ $statusCounts['completed'] }}</div>
                        <div class="text-[10px] text-slate-500 font-medium">Approved & closed</div>
                    </div>

                    <!-- Submitted / In Review -->
                    <div class="p-3 rounded-xl bg-purple-50/50 hover:bg-purple-50 border border-purple-100/80 transition group">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-bold text-purple-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-purple-500 ring-2 ring-purple-200"></span> In Review
                            </span>
                            <span class="text-[10px] font-black px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 font-mono">{{ $tlReviewPct }}%</span>
                        </div>
                        <div class="text-xl font-black text-slate-900 tracking-tight" id="tlChartSubmitted">{{ $statusCounts['submitted'] }}</div>
                        <div class="text-[10px] text-slate-500 font-medium">Awaiting your sign-off</div>
                    </div>

                    <!-- In Progress -->
                    <div class="p-3 rounded-xl bg-amber-50/50 hover:bg-amber-50 border border-amber-100/80 transition group">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-bold text-amber-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500 ring-2 ring-amber-200"></span> In Progress
                            </span>
                            <span class="text-[10px] font-black px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-mono">{{ $tlActivePct }}%</span>
                        </div>
                        <div class="text-xl font-black text-slate-900 tracking-tight" id="tlChartInProgress">{{ $statusCounts['in-progress'] }}</div>
                        <div class="text-[10px] text-slate-500 font-medium">Being worked on</div>
                    </div>

                    <!-- Pending Start -->
                    <div class="p-3 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 transition group">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-bold text-slate-700 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-slate-400 ring-2 ring-slate-200"></span> Pending
                            </span>
                            <span class="text-[10px] font-black px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-mono">{{ $tlPendingPct }}%</span>
                        </div>
                        <div class="text-xl font-black text-slate-900 tracking-tight" id="tlChartPending">{{ $statusCounts['pending'] }}</div>
                        <div class="text-[10px] text-slate-500 font-medium">Not started yet</div>
                    </div>

                    <!-- Overdue Bar Alert if any -->
                    @if(($overdueCount ?? 0) > 0)
                        <div class="col-span-2 p-2.5 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-between">
                            <span class="text-xs font-bold text-rose-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> Overdue Deliverables
                            </span>
                            <span class="text-xs font-black px-2 py-0.5 rounded-md bg-rose-600 text-white font-mono">
                                {{ $overdueCount }} Overdue
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span class="flex items-center gap-1">
                    <i data-lucide="activity" class="w-3.5 h-3.5 text-indigo-500"></i>
                    <span>Real-time squad pulse</span>
                </span>
                <a href="{{ route('tasks.index') }}" class="font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-0.5 transition">
                    <span>Task Console</span> <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>

        <!-- Card 2: Team Workload & 7-Day Velocity (7 cols) -->
        <div class="lg:col-span-7 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div>
                <!-- Header -->
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 flex-wrap gap-2">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm leading-tight">Team Workload & 7-Day Velocity</h3>
                            <p class="text-[11px] text-slate-400">Deliverable distribution & 7-day completion curve</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 text-[11px] font-black border border-emerald-200/70 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            {{ $completionTrend->sum('count') }} Approved (7 Days)
                        </span>
                    </div>
                </div>

                <!-- Team Workload Distribution Bars -->
                <div class="my-3">
                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 mb-2">
                        <span>Workload Allocation Per Member</span>
                        <span class="text-slate-400">{{ $members->count() }} Team Staff</span>
                    </div>
                    @if($members->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($taskPerMember->take(4) as $memberStat)
                                @php
                                    $mCount = $memberStat['count'] ?? 0;
                                    $mPct = min(100, round(($mCount / max(1, $tasks->count())) * 100));
                                @endphp
                                <div class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-100 hover:bg-slate-50 transition">
                                    <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                                        <span class="text-slate-800 truncate max-w-[140px]">{{ $memberStat['name'] }}</span>
                                        <span class="font-mono text-indigo-600 font-black">{{ $mCount }} <span class="text-[10px] text-slate-400 font-normal">tasks</span></span>
                                    </div>
                                    <div class="w-full bg-slate-200/80 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-500" style="width: {{ $mPct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-4 text-center text-xs text-slate-400 font-semibold">
                            No team members registered yet.
                        </div>
                    @endif
                </div>

                <!-- 7-Day Completion Velocity Line Chart -->
                <div class="h-32 sm:h-36 relative mt-2 w-full">
                    <canvas id="tlVelocityChart"></canvas>
                </div>
            </div>

            <!-- Clean Legend & Summary -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] flex-wrap gap-2">
                <div class="flex items-center gap-3.5 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>7-Day Completions: <strong class="text-emerald-700 font-mono">{{ $completionTrend->sum('count') }}</strong></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <span>Active Assigned: <strong class="text-indigo-700 font-mono">{{ $statusCounts['pending'] + $statusCounts['in-progress'] }}</strong></span>
                    </span>
                </div>
                <a href="{{ route('tasks.index') }}" class="font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-0.5 transition">
                    <span>Task Stream</span> <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
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
                    <tbody class="divide-y divide-slate-100" id="tlRecentTasksTbody">
                        @forelse($tasks->take(6) as $task)
                            <tr class="hover:bg-slate-50/80 transition" id="tl-task-row-{{ $task->id }}">
                                <td class="py-3">
                                    <div class="font-bold text-slate-900">{{ $task->title }}</div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ $task->description }}</div>
                                </td>
                                <td class="py-3">
                                    <span class="font-semibold text-slate-700">{{ $task->assignedTo->name ?? 'Member' }}</span>
                                </td>
                                <td class="py-3 font-medium {{ $task->isOverdue() ? 'text-rose-600 font-bold' : 'text-slate-500' }}">
                                    <div class="flex items-center gap-1">
                                        @if($task->isOverdue())
                                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-500 shrink-0"></i>
                                        @endif
                                        <span>{{ $task->deadline->format('d M, h:i A') }}</span>
                                    </div>
                                </td>
                                <td class="py-3">
                                    @if($task->isOverdue())
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1 animate-pulse">
                                            <i data-lucide="alert-triangle" class="w-3 h-3 text-rose-600"></i> Overdue
                                        </span>
                                    @elseif($task->status === 'completed')
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
    // 1. 7-Day Completion Velocity Spline Chart
    const velocityEl = document.getElementById('tlVelocityChart');
    if (velocityEl && typeof Chart !== 'undefined') {
        const trendData = {!! json_encode($completionTrend) !!};
        const ctx2d = velocityEl.getContext('2d');

        const emeraldGradient = ctx2d.createLinearGradient(0, 0, 0, 140);
        emeraldGradient.addColorStop(0, 'rgba(16, 185, 129, 0.28)');
        emeraldGradient.addColorStop(1, 'rgba(16, 185, 129, 0.00)');

        new Chart(velocityEl, {
            type: 'line',
            data: {
                labels: trendData.map(d => d.date),
                datasets: [{
                    label: 'Approved Deliverables',
                    data: trendData.map(d => d.count),
                    borderColor: '#10b981',
                    borderWidth: 2.5,
                    backgroundColor: emeraldGradient,
                    fill: true,
                    tension: 0.38,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            font: { family: 'Inter', size: 10, weight: '700' },
                            color: '#64748b'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 3,
                        ticks: {
                            stepSize: 1,
                            precision: 0,
                            font: { family: 'Inter', size: 10, weight: '600' },
                            color: '#94a3b8'
                        },
                        grid: {
                            color: '#f1f5f9',
                            borderDash: [3, 3],
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
                    }
                }
            }
        });
    }

    // ⚡ 2. Live Sync Polling for TL Dashboard (Zero Page Reload - Smart Conditional Polling)
    let lastTlTaskHash = '';
    let lastTlDashboardSyncTimestamp = 0;
    let lastTlDashboardTotalCount = -1;

    async function syncTLDashboardLive() {
        if (document.hidden) return;

        try {
            const url = `/tasks/sync?since=${lastTlDashboardSyncTimestamp}&known_count=${lastTlDashboardTotalCount}`;
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            // Fast-path: Nothing changed
            if (data.changed === false) return;

            if (data.timestamp) lastTlDashboardSyncTimestamp = data.timestamp;
            if (data.counts && typeof data.counts.total !== 'undefined') lastTlDashboardTotalCount = data.counts.total;

            // Check if anything changed
            const taskHash = JSON.stringify(data.counts) + '_' + (data.tasks ? data.tasks.map(t => `${t.id}:${t.status}:${t.is_overdue}`).join('|') : '');
            if (lastTlTaskHash && lastTlTaskHash !== taskHash) {
                // Update Top Metric Counters
                const overdueEl = document.getElementById('tlMetricOverdue');
                if (overdueEl) overdueEl.innerHTML = `${data.counts.overdue} <span class="text-xs font-medium text-slate-400">tasks</span>`;

                const completedEl = document.getElementById('tlMetricCompleted');
                if (completedEl) completedEl.innerHTML = `${data.counts.completed} <span class="text-xs font-medium text-slate-400">/ ${data.counts.total}</span>`;

                const progressEl = document.getElementById('tlMetricInProgress');
                if (progressEl) progressEl.innerHTML = `${data.counts.pending} <span class="text-xs font-medium text-slate-400">active</span>`;

                const submittedEl = document.getElementById('tlMetricSubmitted');
                if (submittedEl) submittedEl.innerHTML = `${data.counts.submitted} <span class="text-xs font-medium text-slate-400">items</span>`;

                // Update Executive Grid Counters
                const chartCompleted = document.getElementById('tlChartCompleted');
                if (chartCompleted) chartCompleted.textContent = data.counts.completed;

                const chartSubmitted = document.getElementById('tlChartSubmitted');
                if (chartSubmitted) chartSubmitted.textContent = data.counts.submitted;

                const chartInProgress = document.getElementById('tlChartInProgress');
                if (chartInProgress) chartInProgress.textContent = data.counts.pending;

                if (window.lucide) lucide.createIcons();
            }
            lastTlTaskHash = taskHash;
        } catch (e) {
            // Silently handle transient network hiccup
        }
    }

    // Poll every 8 seconds (automatically paused when tab is in background)
    setInterval(syncTLDashboardLive, 8000);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) syncTLDashboardLive();
    });
});
</script>
@endpush