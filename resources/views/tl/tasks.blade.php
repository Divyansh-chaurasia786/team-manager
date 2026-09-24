@extends('layouts.app')
@section('title', 'Task Management')
@section('content')

<div x-data="{
    activeTab: '{{ request('tab', 'live') }}',
    historyFilter: '{{ request('filter', 'all') }}',
    historySearch: ''
}" class="space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Task Assignment & Review</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Delegate deliverables, track deadlines, and review member submissions</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('history.index', ['action' => 'all_tasks']) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                <i data-lucide="history" class="w-4 h-4"></i>
                <span>Task History Log</span>
            </a>
            <button type="button" onclick="document.getElementById('assignTaskModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer border border-indigo-400/30">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Assign New Task</span>
            </button>
        </div>
    </div>

    <!-- Main Navigation Tabs: Live Tasks vs Task History -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-2 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
            <button type="button"
                @click="activeTab = 'live'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="activeTab === 'live' ? 'bg-white text-indigo-700 shadow-sm font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                class="px-4 py-2 rounded-lg text-xs sm:text-sm transition flex items-center gap-2 cursor-pointer">
                <i data-lucide="zap" class="w-4 h-4" :class="activeTab === 'live' ? 'text-indigo-600' : 'text-slate-400'"></i>
                <span>Live Tasks</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                    :class="activeTab === 'live' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600'">
                    {{ $liveTasks->count() }}
                </span>
            </button>

            <button type="button"
                @click="activeTab = 'history'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="activeTab === 'history' ? 'bg-white text-indigo-700 shadow-sm font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                class="px-4 py-2 rounded-lg text-xs sm:text-sm transition flex items-center gap-2 cursor-pointer">
                <i data-lucide="history" class="w-4 h-4" :class="activeTab === 'history' ? 'text-indigo-600' : 'text-slate-400'"></i>
                <span>Task History</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                    :class="activeTab === 'history' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600'">
                    {{ $historyTasks->count() }}
                </span>
                @if($unassignedTasks->count() > 0)
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse" title="{{ $unassignedTasks->count() }} Unassigned Tasks Awaiting Delegation"></span>
                @endif
            </button>
        </div>

        <!-- History Sub-filters: visible when activeTab === 'history' -->
        <div x-show="activeTab === 'history'" x-cloak class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs font-bold text-slate-400 hidden md:inline mr-1">Filter:</span>
            <button type="button"
                @click="historyFilter = 'all'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="historyFilter === 'all' ? 'bg-slate-900 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium'"
                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5">
                <span>All History</span>
                <span class="text-[10px] opacity-80 font-bold">({{ $historyTasks->count() }})</span>
            </button>

            <button type="button"
                @click="historyFilter = 'unassigned'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="historyFilter === 'unassigned' ? 'bg-amber-600 text-white font-bold shadow-sm' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 font-medium'"
                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5">
                <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                <span>Unassigned</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-200/70 font-black">({{ $unassignedTasks->count() }})</span>
            </button>

            <button type="button"
                @click="historyFilter = 'completed'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="historyFilter === 'completed' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 font-medium'"
                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5">
                <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                <span>Completed</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-200/70 font-black">({{ $completedTasks->count() }})</span>
            </button>

            <button type="button"
                @click="historyFilter = 'all_tasks'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="historyFilter === 'all_tasks' ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium'"
                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5"
                title="View all tasks across the entire system">
                <i data-lucide="list" class="w-3.5 h-3.5"></i>
                <span>All Tasks</span>
                <span class="text-[10px] opacity-80 font-bold">({{ $tasks->count() }})</span>
            </button>
        </div>
    </div>

    @if(auth()->user()->isCEO())
        <!-- CEO Task Management Toolbar -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-4 sm:p-5 shadow-lg border border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center justify-center shrink-0">
                    <i data-lucide="shield-alert" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-black tracking-tight">CEO Task History Management</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-300 border border-rose-500/30">CEO Only</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">Select individual tasks or all tasks at once to permanently delete from task history records.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <!-- Select All Checkbox Control -->
                <label class="inline-flex items-center gap-2 px-3 py-2 bg-slate-800 hover:bg-slate-700/80 rounded-xl border border-slate-700 text-xs font-bold text-slate-200 cursor-pointer select-none transition shadow-xs">
                    <input type="checkbox" id="selectAllTasksCheckbox" onchange="toggleSelectAll(this)" class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-slate-600 bg-slate-700 cursor-pointer">
                    <span id="selectAllLabel">Select All ({{ $tasks->count() }})</span>
                </label>

                <!-- Bulk Delete Trigger Button -->
                <button type="button" id="bulkDeleteBtn" onclick="confirmBulkDelete()" disabled class="px-4 py-2 bg-slate-800 text-slate-500 cursor-not-allowed text-xs font-bold rounded-xl border border-slate-700 transition flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span id="bulkDeleteBtnText">Delete Selected (0)</span>
                </button>
            </div>
        </div>

        <!-- Hidden Bulk Delete Form -->
        <form id="bulkDeleteForm" method="POST" action="{{ route('tasks.bulk_destroy') }}" class="hidden">
            @csrf
            <div id="bulkDeleteInputsContainer"></div>
        </form>
    @endif

    @php
        $liveTasksByDate = $liveTasks->groupBy(function($task) {
            return $task->created_at ? $task->created_at->format('Y-m-d') : now()->format('Y-m-d');
        })->sortKeysDesc();
    @endphp

    <!-- TAB 1: LIVE TASKS (Active, Pending, In-Progress Deliverables) -->
    <div x-show="activeTab === 'live'" class="space-y-6">
        @if($liveTasks->isEmpty())
            <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="check-circle" class="w-8 h-8 text-emerald-600"></i>
                </div>
                <h3 class="text-base font-black text-slate-800">No Live Tasks In Progress</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-5">
                    @if($historyTasks->count() > 0)
                        All tasks are completed or awaiting delegation in <button type="button" @click="activeTab = 'history'" class="text-indigo-600 font-bold hover:underline">Task History</button>.
                    @else
                        Start delegating deliverables and projects to present team members.
                    @endif
                </p>
                <div class="flex items-center justify-center gap-3">
                    <button type="button" onclick="document.getElementById('assignTaskModal').classList.remove('hidden')" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition inline-flex items-center gap-2">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Assign New Task</span>
                    </button>
                    @if($unassignedTasks->count() > 0)
                        <button type="button" @click="activeTab = 'history'; historyFilter = 'unassigned'" class="px-4 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-xs font-bold rounded-xl transition inline-flex items-center gap-2">
                            <i data-lucide="user-x" class="w-4 h-4"></i>
                            <span>View {{ $unassignedTasks->count() }} Unassigned</span>
                        </button>
                    @endif
                </div>
            </div>
        @else
            <div class="space-y-8">
                @foreach($liveTasksByDate as $dateStr => $dateTasks)
                @php
                    $dateCarbon = \Carbon\Carbon::parse($dateStr);
                    $isToday = $dateCarbon->isToday();
                    $isYesterday = $dateCarbon->isYesterday();
                    $dateTitle = $isToday ? 'Today' : ($isYesterday ? 'Yesterday' : $dateCarbon->format('l'));
                    $dateSubtitle = $dateCarbon->format('d M Y');
                    $dateTaskCount = $dateTasks->count();
                    $dateOverdueCount = $dateTasks->filter->isOverdue()->count();
                    $tasksByMemberOnDate = $dateTasks->groupBy('assigned_to');
                @endphp

                <!-- Assigned Date Section Card -->
                <div class="space-y-4">
                    <!-- Assigned Date Banner Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-3.5 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-2xl shadow-sm border border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center justify-center font-bold shrink-0">
                                <i data-lucide="calendar" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm sm:text-base font-black tracking-tight">Assigned Date: {{ $dateSubtitle }}</span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $isToday ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-700/60 text-slate-300 border border-slate-600' }}">
                                        {{ $dateTitle }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">Tasks delegated to team members on {{ $dateSubtitle }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
                            <span class="px-3 py-1 rounded-xl text-xs font-bold bg-white/10 text-white border border-white/10">
                                {{ $dateTaskCount }} {{ \Illuminate\Support\Str::plural('Task', $dateTaskCount) }} Delegated
                            </span>
                            @if($dateOverdueCount > 0)
                                <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30 flex items-center gap-1">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                    <span>{{ $dateOverdueCount }} Overdue</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Members and their tasks for this date -->
                    <div class="space-y-4">
                        @foreach($tasksByMemberOnDate as $memberId => $memberTasks)
                            @php
                                $assignee = $memberTasks->first()->assignedTo;
                                $isUnassigned = empty($memberId) || is_null($assignee);
                                $att = $isUnassigned ? null : $todayAttendances->get($memberId);
                                $attStatus = $att ? $att->status : 'present';
                                $pendingCount = $memberTasks->whereIn('status', ['pending', 'in-progress'])->count();
                                $submittedCount = $memberTasks->where('status', 'submitted')->count();
                                $completedCount = $memberTasks->where('status', 'completed')->count();
                            @endphp

                            <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
                                <!-- Assigned Employee Header Card on this date -->
                                <div class="px-5 py-4 bg-gradient-to-r from-slate-50 via-indigo-50/20 to-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        @if($isUnassigned)
                                            <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm shadow-sm border border-amber-200">
                                                <i data-lucide="user-x" class="w-5 h-5"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h2 class="text-sm sm:text-base font-black text-amber-900">Unassigned Tasks</h2>
                                                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300">
                                                        Needs Delegation
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-amber-700/80 mt-0.5">Tasks created on {{ $dateSubtitle }} waiting to be delegated to a team member</p>
                                            </div>
                                        @else
                                            <div class="relative">
                                                @if($assignee->avatar_url)
                                                    <img src="{{ $assignee->avatar_url }}" alt="{{ $assignee->name }}" class="w-11 h-11 rounded-2xl object-cover shadow-md shadow-indigo-600/20">
                                                @else
                                                    <div class="w-11 h-11 rounded-2xl bg-indigo-600 text-white font-black text-sm flex items-center justify-center shadow-md shadow-indigo-600/20">
                                                        {{ strtoupper(substr($assignee->name ?? 'U', 0, 1)) }}
                                                    </div>
                                                @endif
                                                @if($attStatus === 'present' || $attStatus === 'wfh')
                                                    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full" title="Active / Present"></span>
                                                @elseif($attStatus === 'half_day')
                                                    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-amber-500 border-2 border-white rounded-full" title="Half-Day"></span>
                                                @else
                                                    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-rose-500 border-2 border-white rounded-full" title="Absent/Leave"></span>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h2 class="text-sm sm:text-base font-black text-slate-900">{{ $assignee->name }}</h2>
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200/80 text-slate-700">
                                                        {{ $assignee->designation ?? 'Team Member' }}
                                                    </span>
                                                </div>
                                                <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                                    <span>{{ $assignee->email }}</span>
                                                    <span>•</span>
                                                    <span class="font-medium text-slate-600">Username: {{ $assignee->username ?? '—' }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Member's Task Status Badges & Quick Assign Task Header Action -->
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if(!$isUnassigned)
                                            @if(auth()->user()->isCEO())
                                                <button type="button" onclick="toggleMemberTasks({{ $memberId }})" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1 border border-slate-200 cursor-pointer" title="Select/Deselect all tasks for this member">
                                                    <i data-lucide="check-square" class="w-3.5 h-3.5 text-slate-500"></i>
                                                    <span>Select</span>
                                                </button>
                                            @endif
                                            <button type="button" onclick="openAssignTaskForMember({{ $memberId }})" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-indigo-600/20 cursor-pointer">
                                                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                                                <span>Assign Task</span>
                                            </button>
                                        @endif

                                        <span class="px-2.5 py-1 rounded-xl text-[11px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200/70">
                                            {{ $memberTasks->count() }} {{ \Illuminate\Support\Str::plural('Task', $memberTasks->count()) }} on {{ $dateSubtitle }}
                                        </span>
                                        @if($submittedCount > 0)
                                            <span class="px-2.5 py-1 rounded-xl text-[11px] font-extrabold bg-indigo-100 text-indigo-800 border border-indigo-200 animate-pulse flex items-center gap-1">
                                                <i data-lucide="bell" class="w-3 h-3"></i>
                                                <span>{{ $submittedCount }} Needs Review</span>
                                            </span>
                                        @endif
                                        @if($pendingCount > 0)
                                            <span class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                {{ $pendingCount }} In Progress / Pending
                                            </span>
                                        @endif
                                        @if($completedCount > 0)
                                            <span class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                {{ $completedCount }} Completed
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Tasks List Under This Employee on this Date -->
                                <div class="divide-y divide-slate-100">
                                    @foreach($memberTasks as $task)
                                        <div class="p-4 sm:p-5 hover:bg-slate-50/70 transition {{ $task->isReassigned() ? 'bg-amber-50/20' : '' }}" id="task-row-{{ $task->id }}">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                                <!-- Left: Task Info & Checkbox (for CEO) -->
                                                <div class="flex items-start gap-3 flex-1 min-w-0">
                                                    @if(auth()->user()->isCEO())
                                                        <div class="pt-0.5 shrink-0">
                                                            <input type="checkbox" name="selected_task_ids[]" value="{{ $task->id }}" data-member-id="{{ $memberId }}" class="task-select-checkbox w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300 cursor-pointer" onchange="updateSelectedCount()">
                                                        </div>
                                                    @endif
                                                    <div class="space-y-1.5 flex-1 min-w-0">
                                                        <div class="flex items-center gap-2 flex-wrap task-status-container-{{ $task->id }}">
                                                            <h3 class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition">{{ $task->title }}</h3>

                                                            <!-- Status Badge -->
                                                            @if(is_null($task->assigned_to))
                                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1">
                                                                    <i data-lucide="user-x" class="w-3 h-3"></i>
                                                                    <span>Unassigned</span>
                                                                </span>
                                                            @elseif($task->status === 'completed')
                                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                                    ✓ Completed
                                                                </span>
                                                            @elseif($task->status === 'submitted')
                                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-300 flex items-center gap-1">
                                                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                                                    <span>Submitted • In Review</span>
                                                                </span>
                                                            @elseif($task->status === 'in-progress')
                                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
                                                                    {{ $task->isReassigned() ? '⚡ Revisions Active' : '⚙️ In Progress' }}
                                                                </span>
                                                            @else
                                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200">
                                                                    ⏳ Pending Start
                                                                </span>
                                                            @endif

                                                            @if($task->isReassigned())
                                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-amber-50 text-amber-900 border border-amber-300">
                                                                    Rev #{{ $task->reassignment_count }}
                                                                </span>
                                                            @endif

                                                            @if($task->isOverdue())
                                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300">
                                                                    ⚠️ Overdue
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <p class="text-xs text-slate-500 line-clamp-2 max-w-2xl">{{ $task->description ?: 'No description provided.' }}</p>

                                                        <!-- Meta tags: Deadline, Active Countdown, Submitted timestamp, and TL Review status -->
                                                        <div class="flex items-center gap-2.5 text-[11px] text-slate-500 flex-wrap pt-1.5 task-meta-container-{{ $task->id }}">
                                                            <!-- Deadline -->
                                                            <div class="flex items-center gap-1 font-semibold text-slate-700">
                                                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                                                <span>Deadline: {{ $task->deadline ? $task->deadline->format('d M Y, h:i A') : 'No deadline' }}</span>
                                                            </div>

                                                            @if($task->isReassigned() && $task->previous_deadline)
                                                                <span class="text-slate-400 line-through text-[10px]">
                                                                    Prev: {{ $task->previous_deadline->format('d M, h:i A') }}
                                                                </span>
                                                            @endif

                                                            <!-- 1. Employee Active Countdown (Only when in progress) -->
                                                            @if(($task->status === 'pending' || $task->status === 'in-progress') && $task->assigned_to)
                                                                <div class="employee-timer-container inline-flex items-center gap-1.5 text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-200" data-deadline="{{ $task->deadline?->toISOString() }}">
                                                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                                                    <span>Time Left: <span class="font-mono font-black employee-countdown-val">{{ $task->due_label }}</span></span>
                                                                </div>
                                                            @endif

                                                            <!-- 2. Delivered Timestamp (When submitted or completed) -->
                                                            @if($task->submitted_at)
                                                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50/70 border border-indigo-200/80 text-indigo-900 text-[11px] font-semibold">
                                                                    <i data-lucide="send" class="w-3.5 h-3.5 text-indigo-600 shrink-0"></i>
                                                                    <span>Delivered: <strong class="font-mono text-indigo-950 font-bold">{{ $task->submitted_at->format('d M Y, h:i A') }}</strong></span>
                                                                </div>
                                                            @endif

                                                            <!-- TL Review Timer (Live when submitted) / Timestamp (when completed) -->
                                                            @if($task->status === 'submitted')
                                                                @php
                                                                    $tlSubAt = $task->submitted_at ?? $task->updated_at;
                                                                    $tlElapsedSecs = $tlSubAt ? max(0, (int) now()->diffInSeconds($tlSubAt)) : 0;
                                                                    $tlH = floor($tlElapsedSecs / 3600);
                                                                    $tlM = floor(($tlElapsedSecs % 3600) / 60);
                                                                    $tlS = $tlElapsedSecs % 60;
                                                                @endphp
                                                                <div class="tl-review-timer-container inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-purple-50 border border-purple-200 text-purple-900 text-[11px] font-semibold" data-submitted-at="{{ $tlSubAt?->toISOString() }}">
                                                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-ping"></span>
                                                                    <span>Reviewing: <strong class="font-mono text-purple-950 font-bold tl-review-timer-val">{{ sprintf('%02dh %02dm %02ds', $tlH, $tlM, $tlS) }}</strong></span>
                                                                </div>
                                                            @elseif($task->status === 'completed' && $task->reviewed_at)
                                                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-[11px] font-semibold">
                                                                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                                                                    <span>Approved: <strong class="font-mono text-emerald-950 font-bold">{{ $task->reviewed_at->format('d M Y, h:i A') }}</strong></span>
                                                                    @if($task->review_duration)
                                                                        <span class="text-[10px] text-emerald-700">({{ $task->review_duration }})</span>
                                                                    @endif
                                                                </div>
                                                            @endif

                                                            @if($task->submission_link)
                                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200/60">
                                                                    <i data-lucide="link" class="w-3 h-3 text-indigo-500"></i> Link Attached
                                                                </span>
                                                            @endif

                                                            @if($task->submission_file)
                                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/60">
                                                                    <i data-lucide="file" class="w-3 h-3 text-amber-600"></i> {{ strtoupper($task->submission_file_type ?? 'File') }} Attached
                                                                </span>
                                                            @endif

                                                            @if($task->drive_url)
                                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                                                                    <i data-lucide="cloud" class="w-3 h-3 text-emerald-600"></i> Synced to Drive
                                                                </span>
                                                            @endif

                                                            @if($task->overdue_reminder_sent_at)
                                                                @if($task->overdue_reminder_type === 'automatic')
                                                                    <span id="overdue-reminder-pill-{{ $task->id }}" class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-900 bg-amber-100 px-2.5 py-0.5 rounded-md border border-amber-300" title="Automatic reminder sent to employee at {{ $task->overdue_reminder_sent_at->format('d M Y, h:i A') }}">
                                                                        <i data-lucide="bot" class="w-3 h-3 text-amber-700"></i>
                                                                        <span>Automatic Reminder Sent: {{ $task->overdue_reminder_sent_at->format('d M, h:i A') }}</span>
                                                                        @if($task->overdue_reminder_count > 1)
                                                                            <span class="px-1 py-0.2 bg-amber-200/80 rounded text-[9px] font-black text-amber-950">({{ $task->overdue_reminder_count }}x)</span>
                                                                        @endif
                                                                    </span>
                                                                @else
                                                                    <span id="overdue-reminder-pill-{{ $task->id }}" class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-900 bg-blue-100 px-2.5 py-0.5 rounded-md border border-blue-300" title="Manual reminder sent by TL at {{ $task->overdue_reminder_sent_at->format('d M Y, h:i A') }}">
                                                                        <i data-lucide="bell" class="w-3 h-3 text-blue-700"></i>
                                                                        <span>Reminder Sent: {{ $task->overdue_reminder_sent_at->format('d M, h:i A') }}</span>
                                                                        @if($task->overdue_reminder_count > 1)
                                                                            <span class="px-1 py-0.2 bg-blue-200/80 rounded text-[9px] font-black text-blue-950">({{ $task->overdue_reminder_count }}x)</span>
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                            @else
                                                                <span id="overdue-reminder-pill-{{ $task->id }}" class="hidden inline-flex items-center gap-1 text-[10px] font-bold text-blue-900 bg-blue-100 px-2.5 py-0.5 rounded-md border border-blue-300"></span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Right: Clean Unified Single Button Group -->
                                                <div class="flex items-center gap-2 shrink-0 sm:self-center w-full sm:w-auto justify-end task-actions-row-{{ $task->id }}">
                                                    @if($task->isOverdue())
                                                        <button type="button" id="overdue-alert-btn-{{ $task->id }}" onclick="sendOverdueAlertAjax(this, {{ $task->id }})" class="px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold text-xs transition shadow-2xs flex items-center gap-1.5 cursor-pointer" title="{{ $task->overdue_reminder_sent_at ? 'Resend Overdue Reminder Email to Assignee' : 'Dispatch Formal Overdue Reminder Email to Assignee' }}">
                                                            <i data-lucide="mail-warning" class="w-3.5 h-3.5"></i>
                                                            <span>{{ $task->overdue_reminder_sent_at ? 'Resend Alert' : 'Send Alert' }}</span>
                                                        </button>
                                                    @endif

                                                    {{-- If task is unassigned, show quick Assign Member button --}}
                                                    @if(is_null($task->assigned_to))
                                                        <button type="button" onclick="openAssignMemberModal({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ $task->deadline ? $task->deadline->format('Y-m-d\TH:i') : '' }}')" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-indigo-600/20 cursor-pointer" title="Assign this unassigned task to a team member">
                                                            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                                            <span>Assign Member</span>
                                                        </button>
                                                    @else
                                                        {{-- Option to Unassign task (overdue or active) --}}
                                                        @if(!in_array($task->status, ['submitted', 'completed']) && is_null($task->submitted_at))
                                                            @if($task->isOverdue())
                                                                <button type="button" onclick="confirmUnassignTask({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ addslashes($assignee->name ?? 'Member') }}')" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl font-bold text-xs transition flex items-center gap-1.5 shadow-2xs cursor-pointer" title="Unassign this overdue task from {{ $assignee->name ?? 'member' }}">
                                                                    <i data-lucide="user-x" class="w-3.5 h-3.5 text-rose-600"></i>
                                                                    <span>Unassign (Overdue)</span>
                                                                </button>
                                                            @else
                                                                <button type="button" onclick="confirmUnassignTask({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ addslashes($assignee->name ?? 'Member') }}')" class="px-2.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-800 border border-slate-200 rounded-xl font-bold text-xs transition flex items-center gap-1 shadow-2xs cursor-pointer" title="Unassign task from member">
                                                                    <i data-lucide="user-x" class="w-3.5 h-3.5 text-slate-500"></i>
                                                                    <span>Unassign</span>
                                                                </button>
                                                            @endif
                                                        @endif
                                                    @endif

                                                    @if(!in_array($task->status, ['submitted', 'completed']) && is_null($task->submitted_at))
                                                        <button type="button" onclick="openEditTaskModal({{ json_encode([
                                                            'id' => $task->id,
                                                            'title' => $task->title,
                                                            'description' => $task->description ?? '',
                                                            'assigned_to' => $task->assigned_to,
                                                            'deadline' => $task->deadline ? $task->deadline->format('Y-m-d\TH:i') : '',
                                                        ]) }})" class="task-edit-btn-{{ $task->id }} px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition flex items-center gap-1.5 shadow-2xs border border-slate-200 cursor-pointer" title="Edit Task Specifications (Before Employee Submission)">
                                                            <i data-lucide="pencil" class="w-3.5 h-3.5 text-slate-600"></i>
                                                            <span>Edit</span>
                                                        </button>
                                                    @endif

                                                    <button type="button" onclick="openDetailsModal({{ json_encode($task->id) }})" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs transition flex items-center gap-1.5 shadow-2xs border border-indigo-200/70 cursor-pointer">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-600"></i>
                                                        <span>View Details</span>
                                                    </button>

                                                    @if($task->status === 'submitted')
                                                        <form method="POST" action="{{ route('tasks.complete', $task) }}" class="m-0" onsubmit="approveTaskAjax(event, {{ $task->id }})">
                                                            @csrf @method('PUT')
                                                            <button type="submit" id="approve-btn-{{ $task->id }}" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs transition shadow-md shadow-emerald-600/20 flex items-center gap-1 cursor-pointer" title="Approve Task & Sync Deliverable to Drive">
                                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                                <span>Approve</span>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if(auth()->user()->isCEO())
                                                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="m-0" onsubmit="return confirm('Are you sure you want to permanently delete task &quot;{{ addslashes($task->title) }}&quot; from history? This action cannot be undone.');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 rounded-xl font-bold text-xs transition shadow-2xs border border-rose-200/70 flex items-center gap-1 cursor-pointer" title="Permanently Delete Task from History">
                                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                                <span class="hidden sm:inline">Delete</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
    <!-- END TAB 1: LIVE TASKS -->

    <!-- TAB 2: TASK HISTORY (Completed, Unassigned, All History) -->
    <div x-show="activeTab === 'history'" x-cloak class="space-y-6">
        @if($tasks->isEmpty())
            <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="archive" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-black text-slate-800">Task History is Empty</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-5">Completed tasks and unassigned tasks awaiting delegation will appear here.</p>
            </div>
        @else
            <!-- Unassigned Tasks Alert Banner (if any) -->
            @if($unassignedTasks->count() > 0)
                <div class="p-4 sm:p-5 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-300/80 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-amber-500/20">
                            <i data-lucide="user-x" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-black text-amber-950">{{ $unassignedTasks->count() }} Unassigned {{ \Illuminate\Support\Str::plural('Task', $unassignedTasks->count()) }} Awaiting Delegation</h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-200 text-amber-900 border border-amber-300">Action Required</span>
                            </div>
                            <p class="text-xs text-amber-800/90 mt-0.5">These tasks are saved in history and do not show as overdue on the dashboard until delegated to an active member.</p>
                        </div>
                    </div>
                    <button type="button" @click="historyFilter = 'unassigned'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition shrink-0 self-start sm:self-auto shadow-xs flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                        <span>View {{ $unassignedTasks->count() }} Unassigned</span>
                    </button>
                </div>
            @endif

            <!-- History Tasks List Grouped by Date -->
            @php
                $allTasksByDate = $tasks->groupBy(function($task) {
                    return $task->created_at ? $task->created_at->format('Y-m-d') : now()->format('Y-m-d');
                })->sortKeysDesc();
            @endphp

            <div class="space-y-6">
                @foreach($allTasksByDate as $dateStr => $dateTasks)
                    @php
                        $dateCarbon = \Carbon\Carbon::parse($dateStr);
                        $isToday = $dateCarbon->isToday();
                        $isYesterday = $dateCarbon->isYesterday();
                        $dateTitle = $isToday ? 'Today' : ($isYesterday ? 'Yesterday' : $dateCarbon->format('l'));
                        $dateSubtitle = $dateCarbon->format('d M Y');
                        
                        $dateHistoryTasks = $dateTasks->filter(function($t) {
                            return is_null($t->assigned_to) || $t->status === 'completed';
                        });
                    @endphp

                    @if($dateTasks->count() > 0)
                        <div class="space-y-3"
                             x-show="(historyFilter === 'all' && {{ $dateHistoryTasks->count() > 0 ? 'true' : 'false' }}) ||
                                     (historyFilter === 'unassigned' && {{ $dateTasks->whereNull('assigned_to')->count() > 0 ? 'true' : 'false' }}) ||
                                     (historyFilter === 'completed' && {{ $dateTasks->where('status', 'completed')->count() > 0 ? 'true' : 'false' }}) ||
                                     (historyFilter === 'all_tasks')">
                            
                            <!-- Date Header -->
                            <div class="flex items-center justify-between px-4 py-2.5 bg-slate-100 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-4 h-4 text-slate-500"></i>
                                    <span>{{ $dateSubtitle }} ({{ $dateTitle }})</span>
                                </div>
                                <span class="text-[11px] text-slate-500">
                                    {{ $dateTasks->count() }} {{ \Illuminate\Support\Str::plural('Task', $dateTasks->count()) }} Recorded
                                </span>
                            </div>

                            <!-- Tasks on this Date -->
                            <div class="space-y-3">
                                @foreach($dateTasks as $task)
                                    @php
                                        $isUnassigned = is_null($task->assigned_to);
                                        $isCompleted = $task->status === 'completed';
                                        $assignee = $task->assignedTo;
                                    @endphp

                                    <div x-show="(historyFilter === 'all' && ({{ $isUnassigned ? 'true' : 'false' }} || {{ $isCompleted ? 'true' : 'false' }})) ||
                                                (historyFilter === 'unassigned' && {{ $isUnassigned ? 'true' : 'false' }}) ||
                                                (historyFilter === 'completed' && {{ $isCompleted ? 'true' : 'false' }}) ||
                                                (historyFilter === 'all_tasks')"
                                         class="bg-white rounded-2xl border {{ $isUnassigned ? 'border-amber-300 bg-amber-50/20' : ($isCompleted ? 'border-emerald-200/80 bg-emerald-50/10' : 'border-slate-200') }} p-4 sm:p-5 shadow-xs hover:shadow-sm transition"
                                         id="history-task-row-{{ $task->id }}">
                                        
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                            <!-- Left: Checkbox (CEO) & Task Details -->
                                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                                @if(auth()->user()->isCEO())
                                                    <div class="pt-0.5 shrink-0">
                                                        <input type="checkbox" name="selected_task_ids[]" value="{{ $task->id }}" class="task-select-checkbox w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300 cursor-pointer" onchange="updateSelectedCount()">
                                                    </div>
                                                @endif

                                                <div class="space-y-1.5 flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <h3 class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition">{{ $task->title }}</h3>

                                                        @if($isUnassigned)
                                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1">
                                                                <i data-lucide="user-x" class="w-3 h-3"></i>
                                                                <span>Unassigned • Awaiting Delegation</span>
                                                            </span>
                                                        @elseif($isCompleted)
                                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                                                                <i data-lucide="check" class="w-3 h-3"></i>
                                                                <span>Completed</span>
                                                            </span>
                                                        @elseif($task->status === 'submitted')
                                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-300 flex items-center gap-1">
                                                                <i data-lucide="clock" class="w-3 h-3"></i>
                                                                <span>Submitted • In Review</span>
                                                            </span>
                                                        @elseif($task->status === 'in-progress')
                                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
                                                                ⚙️ In Progress
                                                            </span>
                                                        @else
                                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200">
                                                                ⏳ Pending Start
                                                            </span>
                                                        @endif

                                                        @if($task->isOverdue())
                                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300">
                                                                ⚠️ Overdue
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <p class="text-xs text-slate-500 line-clamp-2 max-w-2xl">{{ $task->description ?: 'No instructions provided.' }}</p>

                                                    <div class="flex items-center gap-3 text-[11px] text-slate-500 flex-wrap pt-1">
                                                        @if($isUnassigned)
                                                            <span class="px-2 py-0.5 rounded-md font-bold text-amber-800 bg-amber-100/70 border border-amber-200 flex items-center gap-1">
                                                                <i data-lucide="user-x" class="w-3 h-3"></i>
                                                                <span>Unassigned (Not Overdue)</span>
                                                            </span>
                                                        @elseif($assignee)
                                                            <span class="font-bold text-slate-700 flex items-center gap-1">
                                                                <i data-lucide="user" class="w-3 h-3 text-slate-400"></i>
                                                                <span>Assignee: {{ $assignee->name }} ({{ strtoupper($assignee->role) }})</span>
                                                            </span>
                                                        @endif

                                                        <span class="flex items-center gap-1 font-semibold text-slate-700">
                                                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                                            <span>Deadline: {{ $task->deadline ? $task->deadline->format('d M Y, h:i A') : 'No deadline' }}</span>
                                                        </span>

                                                        @if($task->completed_at)
                                                            <span class="text-emerald-700 font-semibold flex items-center gap-1">
                                                                <i data-lucide="check-circle" class="w-3 h-3 text-emerald-600"></i>
                                                                <span>Completed: {{ \Carbon\Carbon::parse($task->completed_at)->format('d M Y, h:i A') }}</span>
                                                            </span>
                                                        @elseif($task->submitted_at)
                                                            <span class="text-indigo-700 font-semibold flex items-center gap-1">
                                                                <i data-lucide="send" class="w-3 h-3 text-indigo-600"></i>
                                                                <span>Submitted: {{ \Carbon\Carbon::parse($task->submitted_at)->format('d M Y, h:i A') }}</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Right: Action Buttons -->
                                            <div class="flex items-center gap-2 shrink-0 sm:self-center w-full sm:w-auto justify-end">
                                                @if($isUnassigned)
                                                    <button type="button" onclick="openAssignMemberModal({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ $task->deadline ? $task->deadline->format('Y-m-d\TH:i') : '' }}')" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-indigo-600/20 cursor-pointer" title="Assign this unassigned task to a team member">
                                                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                                        <span>Assign Member</span>
                                                    </button>
                                                    <button type="button" onclick="openEditTaskModal({{ json_encode([
                                                        'id' => $task->id,
                                                        'title' => $task->title,
                                                        'description' => $task->description ?? '',
                                                        'assigned_to' => $task->assigned_to,
                                                        'deadline' => $task->deadline ? $task->deadline->format('Y-m-d\TH:i') : '',
                                                    ]) }})" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition flex items-center gap-1.5 border border-slate-200 cursor-pointer">
                                                        <i data-lucide="pencil" class="w-3.5 h-3.5 text-slate-600"></i>
                                                        <span>Edit</span>
                                                    </button>
                                                @endif

                                                <button type="button" onclick="openDetailsModal({{ json_encode($task->id) }})" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs transition flex items-center gap-1.5 shadow-2xs border border-indigo-200/70 cursor-pointer">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-600"></i>
                                                    <span>View Details</span>
                                                </button>

                                                @if(auth()->user()->isCEO())
                                                    <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="m-0" onsubmit="return confirm('Are you sure you want to permanently delete task &quot;{{ addslashes($task->title) }}&quot; from history?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl font-bold text-xs transition shadow-2xs border border-rose-200/70 flex items-center gap-1 cursor-pointer" title="Delete Task">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                            <span class="hidden sm:inline">Delete</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
    <!-- END TAB 2: TASK HISTORY -->

</div>

<!-- Modal: Assign Task -->
<div id="assignTaskModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3.5 sm:p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="font-bold text-slate-900 text-base">Assign New Task</h3>
            <button type="button" onclick="document.getElementById('assignTaskModal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer">✕</button>
        </div>

        <form id="assignTaskForm" method="POST" action="{{ route('tasks.store') }}" onsubmit="assignTaskAjax(event)" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Task Title</label>
                <input type="text" name="title" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required placeholder="e.g. EcoFone Product Photoshoot Review">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Instructions / Description</label>
                <textarea name="description" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="Detail specifications, requirements..."></textarea>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Assign To Member</label>
                    <span class="text-[10px] text-emerald-600 font-bold">🟢 Present Members Only</span>
                </div>
                <select name="assigned_to" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
                    <option value="">-- Select Active Present Member --</option>
                    @foreach($members as $m)
                        @php
                            $att = $todayAttendances->get($m->id);
                            $attStatus = $att ? $att->status : 'present';
                            $isAbsent = in_array($attStatus, ['absent', 'on_leave']);
                        @endphp
                        @php
                            $roleLabel = match($m->role) {
                                'tl' => 'Team Lead',
                                'hr' => 'HR Manager',
                                'ceo' => 'CEO',
                                default => 'Member'
                            };
                        @endphp
                        <option value="{{ $m->id }}" {{ $isAbsent ? 'disabled class=text-slate-400' : '' }}>
                            @if($attStatus === 'absent')
                                🔴 {{ $m->name }} ({{ $roleLabel }}) - ABSENT
                            @elseif($attStatus === 'on_leave')
                                🟡 {{ $m->name }} ({{ $roleLabel }}) - ON LEAVE
                            @elseif($attStatus === 'wfh')
                                🔵 {{ $m->name }} ({{ $roleLabel }}) - WFH
                            @elseif($attStatus === 'half_day')
                                🟠 {{ $m->name }} ({{ $roleLabel }}) - Half-Day
                            @else
                                🟢 {{ $m->name }} ({{ $roleLabel }}) - Present
                            @endif
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">
                    Absent or On-Leave team members cannot receive new tasks. Manage presence in <a href="{{ route('attendance.index') }}" class="text-indigo-600 font-bold hover:underline" target="_blank">Attendance</a>.
                </p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deadline Date & Time</label>
                <input type="datetime-local" name="deadline" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('assignTaskModal').classList.add('hidden')" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition">Assign Task</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Task Specifications (Permitted only before employee submission) -->
<div id="editTaskModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3.5 sm:p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3.5">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Edit Task Specifications</h3>
                    <p class="text-[11px] text-slate-400">Update instructions, assignee, or deadline prior to submission</p>
                </div>
            </div>
            <button type="button" onclick="closeEditTaskModal()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer">✕</button>
        </div>

        <div class="mb-3.5 p-2.5 rounded-xl bg-amber-50/70 border border-amber-200/80 text-[11px] text-amber-900 flex items-center gap-2">
            <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600 shrink-0"></i>
            <span><strong>Policy:</strong> Tasks can only be edited before submission by employee. Once submitted, specifications are locked.</span>
        </div>

        <form id="editTaskForm" method="POST" action="" onsubmit="updateTaskAjax(event)" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" id="editTaskId" name="task_id" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Task Title <span class="text-rose-500">*</span></label>
                <input type="text" id="editTaskTitle" name="title" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Instructions / Description</label>
                <textarea id="editTaskDescription" name="description" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Assignee <span class="text-rose-500">*</span></label>
                    <span class="text-[10px] text-emerald-600 font-bold">🟢 Present Members Only</span>
                </div>
                <select id="editTaskAssignee" name="assigned_to" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">-- Unassigned (No Assignee) --</option>
                    @foreach($members as $m)
                        @php
                            $att = $todayAttendances->get($m->id);
                            $attStatus = $att ? $att->status : 'present';
                            $isAbsent = in_array($attStatus, ['absent', 'on_leave']);
                        @endphp
                        <option value="{{ $m->id }}" {{ $isAbsent ? 'disabled class=text-slate-400' : '' }}>
                            {{ $m->name }} ({{ strtoupper($m->role) }}) {{ $isAbsent ? '- ' . strtoupper(str_replace('_', ' ', $attStatus)) : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deadline Date & Time <span class="text-rose-500">*</span></label>
                <input type="datetime-local" id="editTaskDeadline" name="deadline" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditTaskModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">Cancel</button>
                <button type="submit" id="editTaskSubmitBtn" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition cursor-pointer">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Confirm Unassign Task -->
<div id="unassignTaskModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3.5 sm:p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center font-bold">
                    <i data-lucide="user-x" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Unassign Task</h3>
                    <p class="text-[11px] text-slate-400">Detach member from this deliverable</p>
                </div>
            </div>
            <button type="button" onclick="closeUnassignModal()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer">✕</button>
        </div>

        <form id="unassignTaskForm" method="POST" action="" onsubmit="unassignTaskAjax(event)" class="space-y-4">
            @csrf
            <input type="hidden" id="unassignTaskId" value="">

            <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-xs text-amber-900 leading-relaxed">
                Are you sure you want to unassign <strong id="unassignTaskTitle" class="text-slate-950">Task</strong> from <strong id="unassignMemberName" class="text-slate-950">Member</strong>?
                <p class="mt-2 text-[11px] text-amber-700">The task will be removed from the member's assigned workload and kept as an unassigned task that you can delegate to another member at any time.</p>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeUnassignModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">Cancel</button>
                <button type="submit" id="unassignSubmitBtn" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-amber-600/30 transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                    <span>Confirm Unassign</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Assign Member to Unassigned Task -->
<div id="assignMemberModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3.5 sm:p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 flex items-center justify-center font-bold">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Assign Task to Member</h3>
                    <p class="text-[11px] text-slate-400">Delegate this deliverable to an active team member</p>
                </div>
            </div>
            <button type="button" onclick="closeAssignMemberModal()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer">✕</button>
        </div>

        <form id="assignMemberForm" method="POST" action="" onsubmit="assignMemberAjax(event)" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" id="assignMemberTaskId" value="">

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Task Title</span>
                <span id="assignMemberTaskTitle" class="font-bold text-slate-800 text-sm block mt-0.5">Task Title</span>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Select Team Member <span class="text-rose-500">*</span></label>
                    <span class="text-[10px] text-emerald-600 font-bold">🟢 Present Members Only</span>
                </div>
                <select id="assignMemberSelect" name="assigned_to" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
                    <option value="">-- Select Active Present Member --</option>
                    @foreach($members as $m)
                        @php
                            $att = $todayAttendances->get($m->id);
                            $attStatus = $att ? $att->status : 'present';
                            $isAbsent = in_array($attStatus, ['absent', 'on_leave']);
                        @endphp
                        <option value="{{ $m->id }}" {{ $isAbsent ? 'disabled class=text-slate-400' : '' }}>
                            {{ $m->name }} ({{ strtoupper($m->role) }}) {{ $isAbsent ? '- ' . strtoupper(str_replace('_', ' ', $attStatus)) : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Update Deadline (Optional)</label>
                <input type="datetime-local" id="assignMemberDeadline" name="deadline" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeAssignMemberModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">Cancel</button>
                <button type="submit" id="assignMemberSubmitBtn" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Assign Task</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reassign Task Modal -->
<div id="reassignTaskModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3.5 sm:p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-lg p-5 sm:p-6 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-900 text-base">Reassign Task / Request Changes</h3>
                    <p class="text-xs text-slate-400" id="reassignModalSubtitle">Provide revision directions and an updated deadline</p>
                </div>
            </div>
            <button type="button" onclick="closeReassignModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="reassignTaskForm" method="POST" action="" onsubmit="reassignTaskAjax(event)" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl">
                <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider mb-0.5">Task Being Revised</div>
                <div class="text-sm font-extrabold text-slate-800 truncate" id="reassignTaskTitleDisplay"></div>
                <div class="text-xs text-amber-900/80 mt-1 flex items-center gap-2">
                    <span>Assigned to: <strong id="reassignMemberDisplay"></strong></span>
                    <span>•</span>
                    <span>Current Deadline: <strong id="reassignCurrentDeadlineDisplay"></strong></span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    New / Extended Deadline <span class="text-rose-500">*</span>
                </label>
                <input type="datetime-local" id="reassignDeadlineInput" name="deadline" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition" required>
                <p class="text-[11px] text-slate-400 mt-1">The member will be held to this new deadline for their revised submission.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Revision Notes / Required Changes <span class="text-rose-500">*</span>
                </label>
                <textarea name="revision_notes" rows="4" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition" placeholder="Explain what changes are needed, what was missing from the previous submission, or what fixes to implement..." required></textarea>
                <p class="text-[11px] text-slate-400 mt-1">These notes will be prominently highlighted to the employee on their dashboard and task view.</p>
            </div>

            <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeReassignModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-amber-600/30 transition flex items-center gap-1.5">
                    <i data-lucide="corner-down-left" class="w-4 h-4"></i>
                    <span>Reassign with New Deadline</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Task All Details Modal (Executive Modern Redesign) -->
<div id="taskDetailsModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6">
    <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-200">
        <!-- Modal Top Header -->
        <div class="px-6 py-5 bg-gradient-to-r from-slate-50 via-indigo-50/30 to-slate-50 border-b border-slate-100 flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/20 shrink-0">
                    <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-900 text-base leading-snug" id="detailTaskTitle">Task Deliverable Overview</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Assigned to <span id="detailAssigneeName" class="font-bold text-slate-800"></span></p>
                </div>
            </div>
            <button type="button" onclick="closeDetailsModal()" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition shadow-2xs shrink-0 cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Modal Body Content (Scrollable) -->
        <div class="p-6 space-y-5 overflow-y-auto">
            <!-- Key Metrics Grid: Status, Deadline, Submission, Review -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Status Card -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Workflow Status</span>
                        <div id="detailStatusBadge" class="mt-1"></div>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-400 flex items-center justify-center shadow-2xs">
                        <i data-lucide="activity" class="w-4 h-4 text-indigo-500"></i>
                    </div>
                </div>

                <!-- Deadline Card -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Target Deadline</span>
                        <div id="detailDeadline" class="text-xs font-black text-slate-800 mt-1"></div>
                        <div id="detailPrevDeadlineContainer" class="hidden text-[10px] text-slate-400 line-through mt-0.5">
                            Prev: <span id="detailPrevDeadline"></span>
                        </div>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-400 flex items-center justify-center shadow-2xs">
                        <i data-lucide="calendar" class="w-4 h-4 text-slate-600"></i>
                    </div>
                </div>

                <!-- Submission Timestamp Card -->
                <div class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-indigo-700 block">Task Submission Time</span>
                        <div id="detailSubmissionTimestamp" class="text-xs font-black text-indigo-950 mt-1 font-mono">Not submitted yet</div>
                        <span id="detailEmployeeTimerStatus" class="text-[10px] text-slate-500 block mt-0.5"></span>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-white border border-indigo-200 text-indigo-600 flex items-center justify-center shadow-2xs">
                        <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                    </div>
                </div>

                <!-- TL Review Timestamp Card -->
                <div class="p-3.5 rounded-2xl bg-purple-50/60 border border-purple-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-purple-700 block">TL Review Status & Time</span>
                        <div id="detailReviewTimestamp" class="text-xs font-black text-purple-950 mt-1 font-mono">Pending review</div>
                        <span id="detailReviewDuration" class="text-[10px] text-purple-600 font-semibold block mt-0.5"></span>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-white border border-purple-200 text-purple-600 flex items-center justify-center shadow-2xs">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>

            <!-- Overdue Reminder Status Banner (Shown when reminder email sent) -->
            <div id="detailOverdueReminderContainer" class="hidden p-3.5 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                        <span id="detailOverdueReminderIcon" class="text-sm">🤖</span>
                        <span id="detailOverdueReminderTitle">Automatic Overdue Reminder Sent</span>
                    </span>
                    <div id="detailOverdueReminderTimestamp" class="text-xs font-bold text-amber-950 mt-1 font-mono"></div>
                    <span id="detailOverdueReminderNote" class="text-[10px] text-amber-700 block mt-0.5">Dispatched to employee's registered email address.</span>
                </div>
                <div class="px-2.5 py-1 rounded-xl bg-amber-200/80 border border-amber-300 text-amber-950 text-xs font-black shrink-0" id="detailOverdueReminderBadge">
                    Sent
                </div>
            </div>

            <!-- Task Instructions & Requirements -->
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5 flex items-center gap-1.5">
                    <i data-lucide="align-left" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Task Description & Requirements</span>
                </span>
                <p id="detailDescription" class="text-xs text-slate-700 leading-relaxed whitespace-pre-line"></p>
            </div>

            <!-- Revision Directive Box (If task has revisions) -->
            <div id="detailRevisionContainer" class="hidden p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900">
                <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 flex items-center gap-1.5 mb-1">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-600"></i>
                    <span>Team Lead Revision Directive (Revision #<span id="detailRevCount"></span>)</span>
                </span>
                <p id="detailRevisionNotes" class="text-xs italic text-amber-950 leading-relaxed"></p>
            </div>

            <!-- Deliverables Section -->
            <div class="space-y-3 pt-1">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="package" class="w-4 h-4 text-indigo-600"></i>
                        <span>Submitted Deliverables</span>
                    </h4>
                    <span id="detailSubmittedTime" class="text-[10px] text-slate-400 font-semibold"></span>
                </div>

                <!-- Empty State -->
                <div id="detailNoSubmission" class="p-6 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-slate-400 text-xs">
                    <i data-lucide="inbox" class="w-6 h-6 mx-auto mb-1 text-slate-300"></i>
                    <span>No deliverables submitted yet by the employee.</span>
                </div>

                <!-- Deliverables Cards -->
                <div id="detailSubmissionContent" class="space-y-2.5 hidden">
                    <!-- Remarks -->
                    <div id="detailRemarksBox" class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100">
                        <span class="text-[10px] font-black text-indigo-700 uppercase tracking-wider block mb-1">Employee Remarks</span>
                        <div id="detailRemarks" class="text-xs text-slate-700 italic leading-relaxed"></div>
                    </div>

                    <!-- Deliverable Link -->
                    <div id="detailLinkBox" class="hidden p-3.5 rounded-2xl bg-white border border-slate-200 shadow-2xs flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                <i data-lucide="link-2" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Submitted URL / Resource</span>
                                <span class="text-xs text-slate-800 font-semibold truncate block max-w-sm" id="detailLinkText"></span>
                            </div>
                        </div>
                        <a id="detailLinkBtn" href="#" target="_blank" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm shadow-indigo-600/20 shrink-0">
                            <span>Open URL</span>
                            <i data-lucide="external-link" class="w-3 h-3"></i>
                        </a>
                    </div>

                    <!-- Local Submitted File (Awaiting TL Approval) -->
                    <div id="detailLocalFileBox" class="hidden p-3.5 rounded-2xl bg-amber-50/60 border border-amber-200 shadow-2xs flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                <i data-lucide="file" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider block" id="detailLocalFileName">Submitted File</span>
                                <span class="text-[11px] text-amber-700/90 block">Temporary local server storage. Automatically moves to Google Drive on approval.</span>
                            </div>
                        </div>
                        <a id="detailLocalFileBtn" href="#" target="_blank" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm shadow-amber-600/20 shrink-0">
                            <i data-lucide="download" class="w-3 h-3"></i>
                            <span>Preview</span>
                        </a>
                    </div>

                    <!-- Google Drive File (Approved & Synced) -->
                    <div id="detailDriveFileBox" class="hidden p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-2xs flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <i data-lucide="cloud" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-emerald-900 uppercase tracking-wider block">Synced to Google Drive</span>
                                <span class="text-[11px] text-emerald-700 block">Verified and permanently archived in cloud storage.</span>
                            </div>
                        </div>
                        <a id="detailDriveFileBtn" href="#" target="_blank" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm shadow-emerald-600/20 shrink-0">
                            <i data-lucide="external-link" class="w-3 h-3"></i>
                            <span>View on Drive</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Member Progress Notes Log -->
            <div class="border-t border-slate-100 pt-3">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-2 flex items-center gap-1.5">
                    <i data-lucide="message-square" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Member Progress Updates (<span id="detailUpdatesCount">0</span>)</span>
                </span>
                <div id="detailUpdatesList" class="space-y-1.5 max-h-32 overflow-y-auto"></div>
            </div>
        </div>

        <!-- Modal Footer: Action Bar -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div id="detailActionSlot" class="flex items-center gap-2 flex-wrap"></div>
            <button type="button" onclick="closeDetailsModal()" class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs self-end sm:self-auto cursor-pointer">
                Close
            </button>
        </div>
    </div>
</div>

<script>
const allTasksData = @json($tasks->load(['assignedTo', 'updates']));

function openDetailsModal(taskId) {
    const task = allTasksData.find(t => t.id === taskId);
    if (!task) return;

    document.getElementById('detailTaskTitle').innerText = task.title;
    document.getElementById('detailAssigneeName').innerText = task.assigned_to_user ? task.assigned_to_user.name : (task.assigned_to ? (task.assigned_to.name || 'Member') : 'Unassigned (No Member)');
    document.getElementById('detailDescription').innerText = task.description || 'No instructions provided.';

    // Status Badge
    let badgeHtml = '';
    if (!task.assigned_to) {
        badgeHtml = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1">⚠️ Unassigned</span>';
    } else if (task.status === 'completed') {
        badgeHtml = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">✓ Completed</span>';
    } else if (task.status === 'submitted') {
        badgeHtml = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-100 text-indigo-800 border border-indigo-300">Submitted • In Review</span>';
    } else if (task.status === 'in-progress') {
        badgeHtml = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-300">' + (task.reassignment_count > 0 ? '⚡ Revisions Active' : '⚙️ In Progress') + '</span>';
    } else {
        badgeHtml = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-100 text-slate-700 border border-slate-200">⏳ Pending Start</span>';
    }
    document.getElementById('detailStatusBadge').innerHTML = badgeHtml;

    // Deadline
    document.getElementById('detailDeadline').innerText = task.deadline ? new Date(task.deadline).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' }) : 'No deadline';

    if (task.previous_deadline) {
        document.getElementById('detailPrevDeadlineContainer').classList.remove('hidden');
        document.getElementById('detailPrevDeadline').innerText = new Date(task.previous_deadline).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
    } else {
        document.getElementById('detailPrevDeadlineContainer').classList.add('hidden');
    }

    // Submission Timestamp & Employee Timer Status
    const subTimestampEl = document.getElementById('detailSubmissionTimestamp');
    const empTimerStatusEl = document.getElementById('detailEmployeeTimerStatus');
    if (subTimestampEl && empTimerStatusEl) {
        if (task.submitted_at) {
            subTimestampEl.innerText = new Date(task.submitted_at).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
            empTimerStatusEl.innerText = 'Delivered & Submitted for TL Review';
        } else {
            subTimestampEl.innerText = 'Not submitted yet';
            empTimerStatusEl.innerText = task.status === 'completed' ? 'Marked complete' : (task.assigned_to ? '⏱️ Active work in progress' : 'Waiting to be assigned');
        }
    }

    // TL Review Timestamp & Duration
    const revTimestampEl = document.getElementById('detailReviewTimestamp');
    const revDurationEl = document.getElementById('detailReviewDuration');
    if (revTimestampEl && revDurationEl) {
        if (task.reviewed_at) {
            revTimestampEl.innerText = new Date(task.reviewed_at).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
            revDurationEl.innerText = task.review_duration ? '✓ Review Turnaround: ' + task.review_duration : '✓ Approved';
        } else if (task.status === 'submitted') {
            revTimestampEl.innerText = '⏳ Under Review (Action Required)';
            revDurationEl.innerText = 'Review timer is currently running';
        } else if (task.status === 'completed') {
            revTimestampEl.innerText = new Date(task.updated_at).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
            revDurationEl.innerText = '✓ Completed & Approved';
        } else {
            revTimestampEl.innerText = 'Awaiting member submission';
            revDurationEl.innerText = '';
        }
    }

    // Overdue Reminder Notification Box
    const reminderBox = document.getElementById('detailOverdueReminderContainer');
    if (reminderBox) {
        if (task.overdue_reminder_sent_at) {
            reminderBox.classList.remove('hidden');
            const isAuto = task.overdue_reminder_type === 'automatic';
            document.getElementById('detailOverdueReminderIcon').innerText = isAuto ? '🤖' : '🔔';
            document.getElementById('detailOverdueReminderTitle').innerText = isAuto ? 'Automatic Overdue Reminder Sent' : 'Supervisor Overdue Alert Sent';
            const sentFormatted = new Date(task.overdue_reminder_sent_at).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
            document.getElementById('detailOverdueReminderTimestamp').innerText = 'Dispatched: ' + (sentFormatted !== 'Invalid Date' ? sentFormatted : task.overdue_reminder_sent_at);
            document.getElementById('detailOverdueReminderBadge').innerText = (task.overdue_reminder_count > 1 ? `${task.overdue_reminder_count}x Dispatched` : 'Dispatched');
        } else {
            reminderBox.classList.add('hidden');
        }
    }

    // Revisions
    if (task.reassignment_count > 0 && task.revision_notes) {
        document.getElementById('detailRevisionContainer').classList.remove('hidden');
        document.getElementById('detailRevCount').innerText = task.reassignment_count;
        document.getElementById('detailRevisionNotes').innerText = '"' + task.revision_notes + '"';
    } else {
        document.getElementById('detailRevisionContainer').classList.add('hidden');
    }

    // Deliverables
    const hasDeliverables = task.submitted_at || task.submission_link || task.submission_file || task.drive_url;
    if (hasDeliverables) {
        document.getElementById('detailNoSubmission').classList.add('hidden');
        document.getElementById('detailSubmissionContent').classList.remove('hidden');

        if (task.submitted_at) {
            document.getElementById('detailSubmittedTime').innerText = 'Submitted ' + new Date(task.submitted_at).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
        } else {
            document.getElementById('detailSubmittedTime').innerText = '';
        }

        // Remarks
        if (task.submission_remarks) {
            document.getElementById('detailRemarksBox').classList.remove('hidden');
            document.getElementById('detailRemarks').innerText = '"' + task.submission_remarks + '"';
        } else {
            document.getElementById('detailRemarksBox').classList.add('hidden');
        }

        // Link
        if (task.submission_link) {
            document.getElementById('detailLinkBox').classList.remove('hidden');
            document.getElementById('detailLinkText').innerText = task.submission_link;
            document.getElementById('detailLinkBtn').href = task.submission_link;
        } else {
            document.getElementById('detailLinkBox').classList.add('hidden');
        }

        // Local file
        if (task.submission_file) {
            document.getElementById('detailLocalFileBox').classList.remove('hidden');
            document.getElementById('detailLocalFileName').innerText = 'Attached Deliverable (' + (task.submission_file_type ? task.submission_file_type.toUpperCase() : 'FILE') + ')';
            document.getElementById('detailLocalFileBtn').href = '/' + task.submission_file;
        } else {
            document.getElementById('detailLocalFileBox').classList.add('hidden');
        }

        // Drive File
        if (task.drive_url) {
            document.getElementById('detailDriveFileBox').classList.remove('hidden');
            document.getElementById('detailDriveFileBtn').href = task.drive_url;
        } else {
            document.getElementById('detailDriveFileBox').classList.add('hidden');
        }
    } else {
        document.getElementById('detailNoSubmission').classList.remove('hidden');
        document.getElementById('detailSubmissionContent').classList.add('hidden');
        document.getElementById('detailSubmittedTime').innerText = '';
    }

    // Updates
    const updatesList = document.getElementById('detailUpdatesList');
    updatesList.innerHTML = '';
    const updates = task.updates || [];
    document.getElementById('detailUpdatesCount').innerText = updates.length;
    if (updates.length > 0) {
        updates.forEach(u => {
            const el = document.createElement('div');
            el.className = 'p-2 rounded-xl bg-slate-50 border border-slate-200/70 text-xs text-slate-700 flex justify-between gap-2';
            el.innerHTML = `<span>&bull; ${u.message}</span><span class="text-[10px] text-slate-400 shrink-0">${new Date(u.created_at).toLocaleDateString()}</span>`;
            updatesList.appendChild(el);
        });
    } else {
        updatesList.innerHTML = '<span class="text-slate-400 italic text-xs">No progress notes posted yet.</span>';
    }

    // Action buttons inside View Task Details modal footer
    const actionSlot = document.getElementById('detailActionSlot');
    actionSlot.innerHTML = '';
    
    let buttonsHtml = '';

    // 0. Edit Task Specifications (Permitted only BEFORE employee submission)
    if (task.status !== 'submitted' && task.status !== 'completed' && !task.submitted_at) {
        const editPayload = JSON.stringify({
            id: task.id,
            title: task.title || '',
            description: task.description || '',
            assigned_to: typeof task.assigned_to === 'object' ? (task.assigned_to ? task.assigned_to.id : '') : (task.assigned_to || ''),
            deadline: task.deadline ? task.deadline.substring(0, 16) : ''
        }).replace(/"/g, '&quot;');

        buttonsHtml += `
            <button type="button" onclick="closeDetailsModal(); openEditTaskModal(${editPayload})" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer border border-slate-200" title="Edit Task Specifications (Prior to Submission)">
                <i data-lucide="edit-3" class="w-3.5 h-3.5 text-slate-600"></i>
                <span>Edit Specifications</span>
            </button>
        `;
    }

    // 1. Reassign Task button (available for tasks that are in progress or submitted)
    if (task.status === 'submitted' || task.status === 'in-progress') {
        const assignedName = (task.assigned_to_user ? task.assigned_to_user.name : (task.assigned_to ? (task.assigned_to.name || 'Member') : 'Member')).replace(/'/g, "\\'");
        const cleanTitle = (task.title || '').replace(/'/g, "\\'");
        const deadlineIso = task.deadline ? task.deadline.substring(0, 16) : '';

        buttonsHtml += `
            <button type="button" onclick="triggerReassignFromDetails(${task.id}, '${cleanTitle}', '${deadlineIso}', '${assignedName}')" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm shadow-amber-500/20 cursor-pointer">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>Reassign Task</span>
            </button>
        `;
    }

    // 2. Unassign Task button (if assigned and active)
    if (task.assigned_to && task.status !== 'submitted' && task.status !== 'completed' && !task.submitted_at) {
        const assignedName = (task.assigned_to_user ? task.assigned_to_user.name : (task.assigned_to ? (task.assigned_to.name || 'Member') : 'Member')).replace(/'/g, "\\'");
        const cleanTitle = (task.title || '').replace(/'/g, "\\'");
        buttonsHtml += `
            <button type="button" onclick="closeDetailsModal(); confirmUnassignTask(${task.id}, '${cleanTitle}', '${assignedName}')" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer" title="Unassign this task from member">
                <i data-lucide="user-x" class="w-3.5 h-3.5 text-rose-600"></i>
                <span>Unassign Task</span>
            </button>
        `;
    }

    // 3. Assign Member button (if currently unassigned)
    if (!task.assigned_to) {
        const cleanTitle = (task.title || '').replace(/'/g, "\\'");
        const deadlineIso = task.deadline ? task.deadline.substring(0, 16) : '';
        buttonsHtml += `
            <button type="button" onclick="closeDetailsModal(); openAssignMemberModal(${task.id}, '${cleanTitle}', '${deadlineIso}')" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-indigo-600/20 cursor-pointer">
                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                <span>Assign to Member</span>
            </button>
        `;
    }

    // 4. Assign another task to this member
    if (task.assigned_to) {
        const targetMemberId = typeof task.assigned_to === 'object' ? task.assigned_to.id : task.assigned_to;
        buttonsHtml += `
            <button type="button" onclick="triggerAssignFromDetails(${targetMemberId})" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer border border-slate-200">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-indigo-600"></i>
                <span>Assign New Task</span>
            </button>
        `;
    }

    // 3. Approve & Sync to Drive option
    if (task.status === 'submitted') {
        buttonsHtml += `
            <form method="POST" action="/tasks/${task.id}/complete" class="m-0">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="_method" value="PUT">
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-emerald-600/20 cursor-pointer">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Approve & Sync to Drive</span>
                </button>
            </form>
        `;
    }

    // 4. WhatsApp Share — only when there is a Drive URL or submission link to share
    const shareableLink = task.drive_url || task.submission_link || null;
    if (shareableLink) {
        const memberName = task.assigned_to_user ? task.assigned_to_user.name : (task.assigned_to ? (task.assigned_to.name || 'Team Member') : 'Team Member');
        const fileLabel = task.drive_url
            ? '📁 Google Drive File'
            : (task.submission_link ? '🔗 Reference Link' : '📎 File');

        const waMessage = encodeURIComponent(
            `📋 *Task Deliverable Shared*\n` +
            `━━━━━━━━━━━━━━━━\n` +
            `📌 *Task:* ${task.title}\n` +
            `👤 *Submitted By:* ${memberName}\n` +
            (task.submission_remarks ? `💬 *Remarks:* ${task.submission_remarks}\n` : '') +
            `${fileLabel}:\n${shareableLink}\n` +
            `━━━━━━━━━━━━━━━━\n` +
            `_Shared via EcoFone Team Manager_`
        );

        buttonsHtml += `
            <a href="https://wa.me/?text=${waMessage}" target="_blank"
               class="px-3.5 py-2 bg-[#25D366] hover:bg-[#1ebe5d] text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-green-600/20 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                <span>Share on WhatsApp</span>
            </a>
        `;
    }

    // 5. CEO Permanent Delete Option
    @if(auth()->user()->isCEO())
        buttonsHtml += `
            <form method="POST" action="/tasks/${task.id}" class="m-0" onsubmit="return confirm('Are you sure you want to permanently delete task &quot;${cleanTitle}&quot; from history? This action cannot be undone.');">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs border border-rose-200 cursor-pointer" title="Permanently Delete Task from History">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Delete Task</span>
                </button>
            </form>
        `;
    @endif

    actionSlot.innerHTML = buttonsHtml;

    document.getElementById('taskDetailsModal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
}

function closeDetailsModal() {
    document.getElementById('taskDetailsModal').classList.add('hidden');
}

function triggerReassignFromDetails(taskId, title, deadlineIso, memberName) {
    closeDetailsModal();
    openReassignModal(taskId, title, deadlineIso, memberName);
}

function triggerAssignFromDetails(memberId) {
    closeDetailsModal();
    openAssignTaskForMember(memberId);
}

function openAssignTaskForMember(memberId) {
    const select = document.querySelector('#assignTaskModal select[name="assigned_to"]');
    if (select && memberId) {
        select.value = memberId;
    }
    document.getElementById('assignTaskModal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
}

function openReassignModal(taskId, taskTitle, currentDeadlineIso, memberName) {
    const form = document.getElementById('reassignTaskForm');
    form.action = `/tasks/${taskId}/reassign`;

    document.getElementById('reassignTaskTitleDisplay').innerText = taskTitle;
    document.getElementById('reassignMemberDisplay').innerText = memberName;
    document.getElementById('reassignCurrentDeadlineDisplay').innerText = currentDeadlineIso.replace('T', ' ');
    
    // Default the new deadline input to the current deadline or slightly after
    document.getElementById('reassignDeadlineInput').value = currentDeadlineIso;

    const modal = document.getElementById('reassignTaskModal');
    modal.classList.remove('hidden');

    if (window.lucide) {
        window.lucide.createIcons();
    }
}

function closeReassignModal() {
    document.getElementById('reassignTaskModal').classList.add('hidden');
}

function updateSelectedCount() {
    const allCheckboxes = document.querySelectorAll('.task-select-checkbox');
    const checkedCheckboxes = document.querySelectorAll('.task-select-checkbox:checked');
    const count = checkedCheckboxes.length;
    const total = allCheckboxes.length;

    const selectAllCb = document.getElementById('selectAllTasksCheckbox');
    if (selectAllCb) {
        selectAllCb.checked = (total > 0 && count === total);
        selectAllCb.indeterminate = (count > 0 && count < total);
    }

    const btn = document.getElementById('bulkDeleteBtn');
    const btnText = document.getElementById('bulkDeleteBtnText');
    if (btn && btnText) {
        if (count > 0) {
            btn.disabled = false;
            btn.className = 'px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white cursor-pointer text-xs font-bold rounded-xl shadow-md shadow-rose-600/30 transition flex items-center gap-2';
            btnText.innerText = `Delete Selected (${count})`;
        } else {
            btn.disabled = true;
            btn.className = 'px-4 py-2 bg-slate-800 text-slate-500 cursor-not-allowed text-xs font-bold rounded-xl border border-slate-700 transition flex items-center gap-2';
            btnText.innerText = 'Delete Selected (0)';
        }
    }
}

function toggleSelectAll(masterCheckbox) {
    const isChecked = masterCheckbox.checked;
    document.querySelectorAll('.task-select-checkbox').forEach(cb => {
        cb.checked = isChecked;
    });
    updateSelectedCount();
}

function toggleMemberTasks(memberId) {
    const memberCheckboxes = document.querySelectorAll(`.task-select-checkbox[data-member-id="${memberId}"]`);
    if (memberCheckboxes.length === 0) return;
    
    const allChecked = Array.from(memberCheckboxes).every(cb => cb.checked);
    memberCheckboxes.forEach(cb => {
        cb.checked = !allChecked;
    });
    updateSelectedCount();
}

function confirmBulkDelete() {
    const checkboxes = document.querySelectorAll('.task-select-checkbox:checked');
    if (checkboxes.length === 0) return;

    const count = checkboxes.length;
    const allCheckboxes = document.querySelectorAll('.task-select-checkbox');
    const isAll = (allCheckboxes.length > 0 && count === allCheckboxes.length);

    const msg = isAll
        ? `Are you sure you want to permanently delete ALL ${count} tasks from history? This action cannot be undone.`
        : (count === 1
            ? 'Are you sure you want to permanently delete the 1 selected task from history? This action cannot be undone.'
            : `Are you sure you want to permanently delete all ${count} selected tasks from history? This action cannot be undone.`);

    if (confirm(msg)) {
        const container = document.getElementById('bulkDeleteInputsContainer');
        if (!container) return;
        container.innerHTML = '';
        checkboxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'task_ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('bulkDeleteForm').submit();
    }
}

// 🟢 Floating Instant Toast Helper
function showInstantToast(message, type = 'success') {
    let toast = document.getElementById('instantFloatingToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'instantFloatingToast';
        toast.className = 'fixed bottom-6 right-6 z-50 transform transition-all duration-300';
        document.body.appendChild(toast);
    }
    const bgColor = type === 'success' ? 'bg-slate-900 text-white border-emerald-500' : 'bg-rose-900 text-white border-rose-500';
    const icon = type === 'success' ? '✓' : '✕';
    toast.innerHTML = `
        <div class="flex items-center gap-2.5 px-4 py-3 rounded-2xl ${bgColor} border shadow-2xl text-xs font-bold animate-in slide-in-from-bottom-5">
            <span class="w-5 h-5 rounded-full ${type === 'success' ? 'bg-emerald-500' : 'bg-rose-500'} text-white flex items-center justify-center text-[10px] font-black">${icon}</span>
            <span>${message}</span>
        </div>
    `;
    toast.classList.remove('hidden');
    setTimeout(() => { toast.classList.add('hidden'); }, 4500);
}

// ⚡ Instant AJAX Task Approval (Zero Page Reload)
async function approveTaskAjax(event, taskId) {
    event.preventDefault();
    const form = event.target;
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> Approving...';
    }

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showInstantToast(data.message || 'Task approved and marked as completed!');

            // 1. Update task row status badge
            const statusBadgeContainer = document.querySelector(`.task-status-container-${taskId}`);
            if (statusBadgeContainer) {
                statusBadgeContainer.innerHTML = `
                    <h3 class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition">${data.task_title || document.querySelector(`.task-status-container-${taskId} h3`)?.innerText || 'Task'}</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
                        ✓ Completed
                    </span>
                `;
            }

            // 1b. Stop review timer and display Reviewed by TL timestamp
            const reviewTimerEl = document.querySelector(`.task-tl-review-timer-${taskId}`);
            const reviewedFormatted = data.reviewed_at || new Date().toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
            if (reviewTimerEl) {
                reviewTimerEl.outerHTML = `
                    <div class="flex items-center gap-1 font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200 task-tl-reviewed-badge-${taskId}">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Reviewed by TL: <strong class="font-mono text-emerald-950">${reviewedFormatted}</strong></span>
                        ${data.review_duration ? `<span class="text-[10px] text-emerald-600 font-semibold">(${data.review_duration})</span>` : ''}
                    </div>
                `;
            }

            // 2. Update action button in row and remove edit button
            const editBtn = document.querySelector('.task-edit-btn-' + taskId);
            if (editBtn) editBtn.remove();
            form.outerHTML = '<span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200">✓ Completed & Approved</span>';

            // 3. Update task in allTasksData
            const t = allTasksData.find(x => x.id === taskId);
            if (t) {
                t.status = 'completed';
                t.reviewed_at = data.reviewed_at_iso || new Date().toISOString();
                t.review_duration = data.review_duration;
            }

            // 4. Update modal if open
            const modalBadge = document.getElementById('detailStatusBadge');
            if (modalBadge) {
                modalBadge.innerHTML = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">✓ Completed</span>';
            }
            const modalReviewTs = document.getElementById('detailReviewTimestamp');
            if (modalReviewTs) {
                modalReviewTs.innerText = reviewedFormatted;
            }
            const modalReviewDur = document.getElementById('detailReviewDuration');
            if (modalReviewDur) {
                modalReviewDur.innerText = data.review_duration ? '✓ Review Turnaround: ' + data.review_duration : '✓ Approved';
            }
            const modalActionSlot = document.getElementById('detailActionSlot');
            if (modalActionSlot) {
                modalActionSlot.innerHTML = '';
            }

            if (window.lucide) lucide.createIcons();
        } else {
            showInstantToast(data.message || 'Failed to approve task.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> <span>Approve</span>';
            }
        }
    } catch (err) {
        showInstantToast('Error: ' + err.message, 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> <span>Approve</span>';
        }
    }
}

// 🔔 Instant Overdue Email Alert Dispatch (Zero Page Reload)
async function sendOverdueAlertAjax(btn, taskId) {
    if (!confirm('Dispatch formal overdue reminder email alert to the assigned employee?')) return;

    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> Sending...';

    try {
        const response = await fetch(`/tasks/${taskId}/send-overdue-reminder`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showInstantToast(data.message || 'Overdue alert email dispatched successfully!');
            btn.innerHTML = '<i data-lucide="mail-warning" class="w-3.5 h-3.5"></i> <span>Resend Alert</span>';
            btn.title = 'Resend Overdue Reminder Email to Assignee';
            btn.disabled = false;
            btn.className = 'px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold text-xs transition shadow-2xs flex items-center gap-1.5 cursor-pointer';

            // Update UI pill tag
            const pill = document.getElementById(`overdue-reminder-pill-${taskId}`);
            if (pill) {
                pill.className = 'inline-flex items-center gap-1 text-[10px] font-bold text-blue-900 bg-blue-100 px-2.5 py-0.5 rounded-md border border-blue-300';
                pill.title = `Manual reminder sent by TL at ${data.sent_at || 'just now'}`;
                const countBadge = data.count > 1 ? `<span class="px-1 py-0.2 bg-blue-200/80 rounded text-[9px] font-black text-blue-950">(${data.count}x)</span>` : '';
                pill.innerHTML = `<i data-lucide="bell" class="w-3 h-3 text-blue-700"></i><span>Reminder Sent: ${data.sent_at || 'Just now'}</span>` + countBadge;
                pill.classList.remove('hidden');
            }

            // Update cached in-memory task
            const cachedTask = typeof allTasksData !== 'undefined' ? allTasksData.find(t => t.id === taskId) : null;
            if (cachedTask) {
                cachedTask.overdue_reminder_sent_at = new Date().toISOString();
                cachedTask.overdue_reminder_count = data.count || (cachedTask.overdue_reminder_count || 0) + 1;
                cachedTask.overdue_reminder_type = data.type || 'manual';
            }

            if (window.lucide) lucide.createIcons();
        } else {
            showInstantToast(data.message || 'Failed to dispatch alert.', 'error');
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    } catch (err) {
        showInstantToast('Error: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}
// ✏️ Edit Task Specifications (Permitted only before employee submission)
function openEditTaskModal(taskData) {
    if (!taskData) return;
    document.getElementById('editTaskId').value = taskData.id;
    document.getElementById('editTaskTitle').value = taskData.title || '';
    document.getElementById('editTaskDescription').value = taskData.description || '';
    document.getElementById('editTaskAssignee').value = taskData.assigned_to || '';
    document.getElementById('editTaskDeadline').value = taskData.deadline || '';
    document.getElementById('editTaskForm').action = `/tasks/${taskData.id}`;
    document.getElementById('editTaskModal').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

function closeEditTaskModal() {
    document.getElementById('editTaskModal').classList.add('hidden');
    document.getElementById('editTaskForm').reset();
}

async function updateTaskAjax(event) {
    event.preventDefault();
    const form = event.target;
    const btn = document.getElementById('editTaskSubmitBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> Saving...';

    const formData = new FormData(form);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showInstantToast(data.message || 'Task specifications updated!');
            closeEditTaskModal();

            const taskId = data.task.id;
            const row = document.getElementById(`task-row-${taskId}`);
            if (row) {
                // 1. Update title in status container
                const titleH3 = row.querySelector(`.task-status-container-${taskId} h3`);
                if (titleH3) titleH3.innerText = data.task.title;

                // 2. Update description
                const descP = row.querySelector(`p.text-xs.text-slate-500`);
                if (descP) descP.innerText = data.task.description || 'No instructions provided.';

                // 3. Update deadline
                const metaContainer = row.querySelector(`.task-meta-container-${taskId}`);
                if (metaContainer) {
                    const firstSpan = metaContainer.querySelector('span');
                    if (firstSpan) firstSpan.innerText = `Deadline: ${data.task.deadline}`;
                }
            }

            // Update in local cache
            const t = allTasksData.find(x => x.id === taskId);
            if (t) {
                t.title = data.task.title;
                t.description = data.task.description;
                t.assigned_to = data.task.assigned_to;
                t.deadline = data.task.deadline_iso;
            }

            if (window.lucide) lucide.createIcons();
        } else {
            showInstantToast(data.message || 'Failed to update task specifications.', 'error');
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    } catch (err) {
        showInstantToast('Connection error: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}

// 👤 Unassign Task Modals & Handlers
function confirmUnassignTask(taskId, taskTitle, memberName) {
    document.getElementById('unassignTaskId').value = taskId;
    document.getElementById('unassignTaskTitle').innerText = taskTitle;
    document.getElementById('unassignMemberName').innerText = memberName;
    document.getElementById('unassignTaskForm').action = `/tasks/${taskId}/unassign`;
    document.getElementById('unassignTaskModal').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

function closeUnassignModal() {
    document.getElementById('unassignTaskModal').classList.add('hidden');
}

async function unassignTaskAjax(event) {
    event.preventDefault();
    const form = event.target;
    const btn = document.getElementById('unassignSubmitBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> Unassigning...';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showInstantToast(data.message || 'Task unassigned successfully!');
            closeUnassignModal();
            setTimeout(() => {
                window.location.href = '{{ route('tasks.index') }}?tab=history&filter=unassigned';
            }, 500);
        } else {
            showInstantToast(data.message || 'Failed to unassign task.', 'error');
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    } catch (err) {
        showInstantToast('Connection error: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}

// ➕ Assign Unassigned Task to Member
function openAssignMemberModal(taskId, taskTitle, currentDeadline) {
    document.getElementById('assignMemberTaskId').value = taskId;
    document.getElementById('assignMemberTaskTitle').innerText = taskTitle;
    if (currentDeadline) {
        document.getElementById('assignMemberDeadline').value = currentDeadline;
    }
    document.getElementById('assignMemberForm').action = `/tasks/${taskId}/assign-member`;
    document.getElementById('assignMemberModal').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

function closeAssignMemberModal() {
    document.getElementById('assignMemberModal').classList.add('hidden');
    document.getElementById('assignMemberForm').reset();
}

async function assignMemberAjax(event) {
    event.preventDefault();
    const form = event.target;
    const btn = document.getElementById('assignMemberSubmitBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> Assigning...';

    const formData = new FormData(form);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showInstantToast(data.message || 'Task successfully assigned to member!');
            closeAssignMemberModal();
            setTimeout(() => {
                window.location.href = '{{ route('tasks.index') }}?tab=live';
            }, 500);
        } else {
            showInstantToast(data.message || 'Failed to assign task.', 'error');
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    } catch (err) {
        showInstantToast('Connection error: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}

// ➕ Instant Task Assignment via AJAX (Zero Full Page Reload)
async function assignTaskAjax(event) {
    event.preventDefault();
    const form = event.target;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> Assigning...';

    const formData = new FormData(form);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showInstantToast(data.message || 'Task successfully assigned!');
            form.reset();
            document.getElementById('assignTaskModal').classList.add('hidden');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } else {
            showInstantToast(data.message || 'Failed to assign task.', 'error');
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    } catch (err) {
        showInstantToast('Connection error: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}

// 🔁 Instant Task Reassignment via AJAX (Zero Full Page Reload)
async function reassignTaskAjax(event) {
    event.preventDefault();
    const form = event.target;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> Reassigning...';

    const formData = new FormData(form);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showInstantToast(data.message || 'Task reassigned with new deadline!');
            closeReassignModal();

            const taskId = data.task_id;
            const statusContainer = document.querySelector(`.task-status-container-${taskId}`);
            if (statusContainer) {
                const titleEl = statusContainer.querySelector('h3');
                const title = titleEl ? titleEl.innerText : 'Task';
                statusContainer.innerHTML = `
                    <h3 class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition">${title}</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
                        ⚡ In Progress (Reassigned)
                    </span>
                `;
            }

            const deadlineDisplay = document.querySelector(`.task-deadline-display-${taskId}`);
            if (deadlineDisplay) {
                deadlineDisplay.innerText = data.new_deadline;
            }

            const t = allTasksData.find(x => x.id === taskId);
            if (t) {
                t.status = 'in-progress';
                t.deadline = data.new_deadline_iso;
                t.revision_notes = data.revision_notes;
            }

            if (window.lucide) lucide.createIcons();
        } else {
            showInstantToast(data.message || 'Failed to reassign task.', 'error');
        }
    } catch (err) {
        showInstantToast('Connection error: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}

// ⏱️ Live Real-Time Timers (Employee Countdown & TL Review Elapsed Timer)
function formatTimeDiff(ms) {
    const isNegative = ms < 0;
    const absMs = Math.abs(ms);
    const totalSecs = Math.floor(absMs / 1000);
    const hours = Math.floor(totalSecs / 3600);
    const mins = Math.floor((totalSecs % 3600) / 60);
    const secs = totalSecs % 60;
    
    const hStr = hours < 10 ? '0' + hours : '' + hours;
    const mStr = mins < 10 ? '0' + mins : '' + mins;
    const sStr = secs < 10 ? '0' + secs : '' + secs;
    
    return {
        formatted: `${hStr}h ${mStr}m ${sStr}s`,
        isNegative,
        hours, mins, secs
    };
}

function updateAllTaskTimers() {
    if (document.hidden) return;
    const now = new Date().getTime();

    // 1. Task Countdown Timers (Freeze on submission/completion)
    document.querySelectorAll('.task-timer-container').forEach(el => {
        const deadlineStr = el.getAttribute('data-deadline');
        if (!deadlineStr) return;
        const deadlineMs = new Date(deadlineStr).getTime();
        if (isNaN(deadlineMs)) return;
        const diff = deadlineMs - now;
        const valEl = el.querySelector('.task-timer-val');
        if (!valEl) return;

        const time = formatTimeDiff(diff);
        if (time.isNegative) {
            el.className = el.className.replace('text-indigo-700', 'text-rose-600');
            valEl.textContent = `Overdue: ${time.formatted}`;
        } else {
            valEl.textContent = time.formatted;
        }
    });

    // 2. TL Review Live Elapsed Timers (Counting upward from submission until TL reviews)
    document.querySelectorAll('.tl-review-timer-container').forEach(el => {
        const submittedStr = el.getAttribute('data-submitted-at');
        if (!submittedStr) return;
        const submittedMs = new Date(submittedStr).getTime();
        if (isNaN(submittedMs)) return;
        const elapsed = Math.max(0, now - submittedMs);
        const valEl = el.querySelector('.tl-review-timer-val');
        if (!valEl) return;

        const time = formatTimeDiff(elapsed);
        valEl.textContent = time.formatted;
    });
}

// Tick every 1 second for smooth countdown
setInterval(updateAllTaskTimers, 1000);

// ⚡ Live Sync Polling for TL Task Stream (Detect Member Submissions in Real-Time)
let lastTLTasksSyncState = {};
let lastTLSyncTimestamp = {{ now()->timestamp }};
let lastTLKnownCount = {{ $tasks->count() }};

async function liveSyncTLTasks() {
    // Dormant when tab is inactive
    if (document.hidden) return;

    try {
        const currentCount = document.querySelectorAll('[id^="task-row-"]').length;
        if (lastTLKnownCount === -1) lastTLKnownCount = currentCount;

        const url = `/tasks/sync?since=${lastTLSyncTimestamp}&known_count=${lastTLKnownCount}`;
        const res = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        // Fast-path: no changes on server
        if (data.changed === false) return;

        if (data.timestamp) lastTLSyncTimestamp = data.timestamp;
        if (data.counts && typeof data.counts.total !== 'undefined') lastTLKnownCount = data.counts.total;
        if (!Array.isArray(data.tasks)) return;

        let needsIconRefresh = false;

        data.tasks.forEach(t => {
            const row = document.getElementById(`task-row-${t.id}`);
            if (!row) return;

            const prev = lastTLTasksSyncState[t.id];
            if (prev && prev.status !== t.status) {
                // Status changed!
                if (t.status === 'submitted') {
                    showInstantToast(`📥 Member submitted deliverable for "${t.title}"! Ready for review.`);
                    // Remove edit button as task is now submitted
                    const editBtn = row.querySelector(`.task-edit-btn-${t.id}`);
                    if (editBtn) editBtn.remove();

                    // Update actions container to include approve button if missing
                    const actionsRow = row.querySelector(`.task-actions-row-${t.id}`);
                    if (actionsRow && !actionsRow.querySelector(`#approve-btn-${t.id}`)) {
                        const approveForm = document.createElement('form');
                        approveForm.method = 'POST';
                        approveForm.action = `/tasks/${t.id}/complete`;
                        approveForm.className = 'm-0';
                        approveForm.onsubmit = function(ev) { approveTaskAjax(ev, t.id); };
                        approveForm.innerHTML = `
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="PUT">
                            <button type="submit" id="approve-btn-${t.id}" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs transition shadow-md shadow-emerald-600/20 flex items-center gap-1 cursor-pointer" title="Approve Task & Sync Deliverable to Drive">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Approve</span>
                            </button>
                        `;
                        actionsRow.appendChild(approveForm);
                    }

                    // Update status badge
                    const statusContainer = row.querySelector(`.task-status-container-${t.id}`);
                    if (statusContainer) {
                        const titleH3 = statusContainer.querySelector('h3');
                        const titleText = titleH3 ? titleH3.innerText : t.title;
                        statusContainer.innerHTML = `
                            <h3 class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition">${titleText}</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-300 flex items-center gap-1">
                                <i data-lucide="clock" class="w-3 h-3"></i>
                                <span>Submitted • In Review</span>
                            </span>
                        `;
                    }
                    needsIconRefresh = true;
                }
            }

            // Overdue reminder live badge update
            if (t.overdue_reminder_sent_at) {
                const pill = document.getElementById(`overdue-reminder-pill-${t.id}`);
                if (pill) {
                    const isAuto = t.overdue_reminder_type === 'automatic';
                    pill.className = isAuto
                        ? 'inline-flex items-center gap-1 text-[10px] font-bold text-amber-900 bg-amber-100 px-2.5 py-0.5 rounded-md border border-amber-300'
                        : 'inline-flex items-center gap-1 text-[10px] font-bold text-blue-900 bg-blue-100 px-2.5 py-0.5 rounded-md border border-blue-300';
                    pill.title = isAuto ? `Automatic reminder sent to employee at ${t.overdue_reminder_sent_at}` : `Reminder sent by TL at ${t.overdue_reminder_sent_at}`;
                    const countTxt = (t.overdue_reminder_count > 1 ? `<span class="px-1 py-0.2 ${isAuto ? 'bg-amber-200/80 text-amber-950' : 'bg-blue-200/80 text-blue-950'} rounded text-[9px] font-black">(${t.overdue_reminder_count}x)</span>` : '');
                    pill.innerHTML = `<i data-lucide="${isAuto ? 'bot' : 'bell'}" class="w-3 h-3 ${isAuto ? 'text-amber-700' : 'text-blue-700'}"></i><span>${isAuto ? 'Automatic Reminder Sent: ' : 'Reminder Sent: '}${t.overdue_reminder_sent_at}</span>` + countTxt;
                    pill.classList.remove('hidden');
                    needsIconRefresh = true;
                }
                const alertBtn = document.getElementById(`overdue-alert-btn-${t.id}`);
                if (alertBtn && alertBtn.querySelector('span')) {
                    alertBtn.querySelector('span').innerText = 'Resend Alert';
                    alertBtn.title = 'Resend Overdue Reminder Email to Assignee';
                }
            }

            // Sync with in-memory allTasksData for modal views
            if (typeof allTasksData !== 'undefined') {
                const cached = allTasksData.find(m => m.id === t.id);
                if (cached) {
                    cached.status = t.status;
                    cached.overdue_reminder_sent_at = t.overdue_reminder_sent_at;
                    cached.overdue_reminder_count = t.overdue_reminder_count;
                    cached.overdue_reminder_type = t.overdue_reminder_type;
                }
            }

            lastTLTasksSyncState[t.id] = {
                status: t.status,
                is_overdue: t.is_overdue
            };
        });

        if (needsIconRefresh && window.lucide) {
            lucide.createIcons();
        }
    } catch (e) {
        // Silently handle transient errors
    }
}

// Poll every 6 seconds (paused automatically when tab is in background)
setInterval(liveSyncTLTasks, 6000);

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        liveSyncTLTasks();
        updateAllTaskTimers();
    }
});

let tlTasksInitialized = false;
function initTLTasksView() {
    if (tlTasksInitialized) return;
    tlTasksInitialized = true;
    updateAllTaskTimers();
    liveSyncTLTasks();
    if (window.lucide) lucide.createIcons();
}

document.addEventListener('DOMContentLoaded', initTLTasksView);
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initTLTasksView();
}
</script>

@endsection