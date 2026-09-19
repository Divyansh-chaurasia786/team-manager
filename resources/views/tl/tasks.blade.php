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
                            @if(auth()->user()->isCEO())
                                <button type="button" onclick="toggleMemberTasks({{ $memberId }})" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1 border border-slate-200 cursor-pointer" title="Select/Deselect all tasks for this member">
                                    <i data-lucide="check-square" class="w-3.5 h-3.5 text-slate-500"></i>
                                    <span>Select Member Tasks</span>
                                </button>
                            @endif
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

                                            <!-- Meta tags: Deadline, Active Countdown, Submitted timestamp, and TL Review status -->
                                            <div class="flex items-center gap-2.5 text-[11px] text-slate-500 flex-wrap pt-1.5 task-meta-container-{{ $task->id }}">
                                                <!-- Deadline -->
                                                <div class="flex items-center gap-1 font-semibold text-slate-700">
                                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                                    <span>Deadline: {{ $task->deadline->format('d M Y, h:i A') }}</span>
                                                </div>

                                                @if($task->isReassigned() && $task->previous_deadline)
                                                    <span class="text-slate-400 line-through text-[10px]">
                                                        Prev: {{ $task->previous_deadline->format('d M, h:i A') }}
                                                    </span>
                                                @endif

                                                <!-- 1. Employee Active Countdown (Only when in progress) -->
                                                @if($task->status === 'pending' || $task->status === 'in-progress')
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
                                                        $tlServerElapsed = sprintf('%02dh %02dm %02ds', $tlH, $tlM, $tlS);
                                                    @endphp
                                                    <div class="tl-review-timer-container inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-purple-100 text-purple-900 text-[10px] font-extrabold border border-purple-300 task-tl-review-timer-{{ $task->id }}" data-task-id="{{ $task->id }}" data-submitted-at="{{ ($task->submitted_at ?? $task->updated_at ?? now())->toISOString() }}">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-ping"></span>
                                                        <span>Awaiting Your Review: <span class="font-mono font-black text-purple-950 tl-review-timer-val">{{ $tlServerElapsed }}</span></span>
                                                    </div>
                                                @elseif($task->status === 'completed')
                                                    <div class="flex items-center gap-1 font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200 task-tl-reviewed-badge-{{ $task->id }}">
                                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                        <span>Reviewed by TL: <strong class="font-mono text-emerald-950">{{ $task->reviewed_at ? $task->reviewed_at->format('d M Y, h:i A') : ($task->updated_at ? $task->updated_at->format('d M Y, h:i A') : 'Approved') }}</strong></span>
                                                        @if($task->review_duration)
                                                            <span class="text-[10px] text-emerald-600 font-semibold">({{ $task->review_duration }})</span>
                                                        @endif
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
                                    </div>

                                    <!-- Right: Clean Unified Single Button Group -->
                                    <div class="flex items-center gap-2 shrink-0 sm:self-center w-full sm:w-auto justify-end task-actions-row-{{ $task->id }}">
                                        @if($task->isOverdue())
                                            <button type="button" onclick="sendOverdueAlertAjax(this, {{ $task->id }})" class="px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold text-xs transition shadow-2xs flex items-center gap-1.5 cursor-pointer" title="Dispatch Formal Overdue Reminder Email to Assignee">
                                                <i data-lucide="mail-warning" class="w-3.5 h-3.5"></i>
                                                <span>Send Alert</span>
                                            </button>
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
    @endif

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
                <select id="editTaskAssignee" name="assigned_to" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
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

    // Submission Timestamp & Employee Timer Status
    const subTimestampEl = document.getElementById('detailSubmissionTimestamp');
    const empTimerStatusEl = document.getElementById('detailEmployeeTimerStatus');
    if (subTimestampEl && empTimerStatusEl) {
        if (task.submitted_at) {
            subTimestampEl.innerText = new Date(task.submitted_at).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
            empTimerStatusEl.innerText = 'Delivered & Submitted for TL Review';
        } else {
            subTimestampEl.innerText = 'Not submitted yet';
            empTimerStatusEl.innerText = task.status === 'completed' ? 'Marked complete' : '⏱️ Active work in progress';
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
            assigned_to: typeof task.assigned_to === 'object' ? task.assigned_to.id : task.assigned_to,
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
            btn.innerHTML = '✓ Alert Sent';
            btn.className = 'px-3 py-2 bg-slate-100 text-slate-500 rounded-xl font-bold text-xs border border-slate-200 cursor-default';
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
    const now = new Date().getTime();

    // 1. Employee Active Countdown Timers (Freeze on submission/completion)
    document.querySelectorAll('.employee-timer-container').forEach(el => {
        const deadlineStr = el.getAttribute('data-deadline');
        if (!deadlineStr) return;
        const deadlineMs = new Date(deadlineStr).getTime();
        const diff = deadlineMs - now;
        const valEl = el.querySelector('.employee-countdown-val');
        if (!valEl) return;

        const time = formatTimeDiff(diff);
        if (time.isNegative) {
            el.className = el.className.replace('bg-indigo-50', 'bg-rose-50').replace('text-indigo-700', 'text-rose-700').replace('border-indigo-200', 'border-rose-200');
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

// Tick every second for live real-time updates
setInterval(updateAllTaskTimers, 1000);

// ⚡ Live Sync Polling for TL Task Stream (Detect Member Submissions in Real-Time)
let lastTLTasksSyncState = {};
async function liveSyncTLTasks() {
    try {
        const res = await fetch('/tasks/sync', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success || !Array.isArray(data.tasks)) return;

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

// Poll every 3.5 seconds
setInterval(liveSyncTLTasks, 3500);

document.addEventListener('DOMContentLoaded', () => {
    updateAllTaskTimers();
    liveSyncTLTasks();
});
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    updateAllTaskTimers();
    liveSyncTLTasks();
}
</script>

@endsection