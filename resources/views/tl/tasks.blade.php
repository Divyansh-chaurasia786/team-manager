@extends('layouts.app')
@section('title', 'Task Management')
@section('content')

<div class="space-y-6">

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

    <!-- Grouped Tasks by Employee Header -->
    @php
        $tasksByMember = $tasks->groupBy('assigned_to');
    @endphp

    @if($tasks->isEmpty())
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="list-checks" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-black text-slate-800">No Tasks Assigned Yet</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-5">Start delegating deliverables and projects to present team members.</p>
            <button type="button" onclick="document.getElementById('assignTaskModal').classList.remove('hidden')" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition inline-flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Assign First Task</span>
            </button>
        </div>
    @else
        <div class="space-y-6">
            @foreach($tasksByMember as $memberId => $memberTasks)
                @php
                    $assignee = $memberTasks->first()->assignedTo;
                    $att = $todayAttendances->get($memberId);
                    $attStatus = $att ? $att->status : 'present';
                    $pendingCount = $memberTasks->whereIn('status', ['pending', 'in-progress'])->count();
                    $submittedCount = $memberTasks->where('status', 'submitted')->count();
                    $completedCount = $memberTasks->where('status', 'completed')->count();
                @endphp

                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <!-- Assigned Employee Header Card -->
                    <div class="px-5 py-4 bg-gradient-to-r from-slate-50 via-indigo-50/20 to-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                @if(isset($assignee) && $assignee->avatar_url)
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
                                    <h2 class="text-sm sm:text-base font-black text-slate-900">{{ $assignee->name ?? 'Unassigned Member' }}</h2>
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
                        </div>

                        <!-- Member's Task Status Badges & Quick Assign Task Header Action -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button" onclick="openAssignTaskForMember({{ $memberId }})" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-indigo-600/20 cursor-pointer">
                                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                                <span>Assign Task</span>
                            </button>
                            <span class="px-2.5 py-1 rounded-xl text-[11px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200/70">
                                {{ $memberTasks->count() }} Tasks
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

                    <!-- Tasks List Under This Employee Header -->
                    <div class="divide-y divide-slate-100">
                        @foreach($memberTasks as $task)
                            <div class="p-4 sm:p-5 hover:bg-slate-50/70 transition {{ $task->isReassigned() ? 'bg-amber-50/20' : '' }}">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <!-- Left: Task Info & Meta -->
                                    <div class="space-y-1.5 flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition">{{ $task->title }}</h3>

                                            <!-- Status Badge -->
                                            @if($task->status === 'completed')
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

                                        <p class="text-xs text-slate-500 line-clamp-2 max-w-2xl">{{ $task->description }}</p>

                                        <!-- Meta tags: Deadline, Submitted time, and Deliverable badges -->
                                        <div class="flex items-center gap-3 text-[11px] text-slate-500 flex-wrap pt-1">
                                            <div class="flex items-center gap-1 font-semibold text-slate-700">
                                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                                <span>Deadline: {{ $task->deadline->format('d M Y, h:i A') }}</span>
                                            </div>

                                            @if($task->isReassigned() && $task->previous_deadline)
                                                <span class="text-slate-400 line-through text-[10px]">
                                                    Prev: {{ $task->previous_deadline->format('d M, h:i A') }}
                                                </span>
                                            @endif

                                            @if($task->submitted_at)
                                                <div class="flex items-center gap-1 font-semibold text-indigo-700">
                                                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-indigo-500"></i>
                                                    <span>Submitted {{ $task->submitted_at->diffForHumans() }}</span>
                                                </div>
                                            @endif

                                            <!-- Deliverables indicator tags (no duplicate buttons) -->
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
                                        </div>
                                    </div>

                                    <!-- Right: Clean Unified Single Button Group -->
                                    <div class="flex items-center gap-2 shrink-0 sm:self-center w-full sm:w-auto justify-end">
                                        <button type="button" onclick="openDetailsModal({{ json_encode($task->id) }})" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs transition flex items-center gap-1.5 shadow-2xs border border-indigo-200/70 cursor-pointer">
                                            <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-600"></i>
                                            <span>View Details</span>
                                        </button>

                                        @if($task->status === 'submitted')
                                            <form method="POST" action="{{ route('tasks.complete', $task) }}" class="m-0">
                                                @csrf @method('PUT')
                                                <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs transition shadow-md shadow-emerald-600/20 flex items-center gap-1 cursor-pointer" title="Approve Task & Sync Deliverable to Drive">
                                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                    <span>Approve</span>
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
    @endif

</div>

<!-- Modal: Assign Task -->
<div id="assignTaskModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3.5 sm:p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="font-bold text-slate-900 text-base">Assign New Task</h3>
            <button type="button" onclick="document.getElementById('assignTaskModal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer">✕</button>
        </div>

        <form method="POST" action="{{ route('tasks.store') }}" class="space-y-4">
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

        <form id="reassignTaskForm" method="POST" action="" class="space-y-4">
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
            <!-- Key Metrics Grid: Status, Deadline, Previous Deadline -->
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
    document.getElementById('detailAssigneeName').innerText = task.assigned_to_user ? task.assigned_to_user.name : (task.assigned_to ? (task.assigned_to.name || 'Member') : 'Member');
    document.getElementById('detailDescription').innerText = task.description || 'No instructions provided.';

    // Status Badge
    let badgeHtml = '';
    if (task.status === 'completed') {
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
    document.getElementById('detailDeadline').innerText = new Date(task.deadline).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });

    if (task.previous_deadline) {
        document.getElementById('detailPrevDeadlineContainer').classList.remove('hidden');
        document.getElementById('detailPrevDeadline').innerText = new Date(task.previous_deadline).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
    } else {
        document.getElementById('detailPrevDeadlineContainer').classList.add('hidden');
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

    // 2. Assign another task to this member
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
</script>

@endsection