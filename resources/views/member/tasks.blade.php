@extends('layouts.app')
@section('title', 'My Deliverables')
@section('content')

<div class="space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">My Deliverables Queue</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Track your assigned tasks, post progress notes, and submit completed work</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('history.index', ['action' => 'all_tasks']) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1.5 transition shadow-2xs">
                <i data-lucide="history" class="w-4 h-4"></i>
                <span>My Task History</span>
            </a>
            <span class="px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold flex items-center gap-1.5">
                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                <span>{{ $tasks->count() }} Tasks Assigned</span>
            </span>
        </div>
    </div>

    <!-- Deliverables Container (Responsive Cards on Mobile + Table on Desktop) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        
        <!-- Mobile Card View (< 768px) -->
        <div class="md:hidden divide-y divide-slate-100">
            @forelse($tasks as $task)
                <div class="p-4 space-y-3 hover:bg-slate-50/70 transition {{ $task->isReassigned() ? 'bg-amber-50/30' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="font-black text-slate-900 text-sm leading-snug">{{ $task->title }}</h3>
                            <div class="text-[11px] text-slate-500 mt-1 line-clamp-2">{{ $task->description }}</div>
                        </div>
                        @if($task->status === 'completed')
                            <span class="task-status-badge-mobile-{{ $task->id }} px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 shrink-0">
                                ✓ Done
                            </span>
                        @elseif($task->status === 'submitted')
                            <span class="task-status-badge-mobile-{{ $task->id }} px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-100 text-indigo-800 border border-indigo-300 shrink-0">
                                In Review
                            </span>
                        @elseif($task->status === 'in-progress')
                            <span class="task-status-badge-mobile-{{ $task->id }} px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-800 border border-blue-300 shrink-0">
                                In Progress
                            </span>
                        @else
                            <span class="task-status-badge-mobile-{{ $task->id }} px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-300 shrink-0">
                                Pending
                            </span>
                        @endif
                    </div>

                    @if($task->isReassigned() && $task->revision_notes)
                        <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                            <div class="flex items-center gap-1 font-bold text-[10px] text-amber-800 uppercase tracking-wider mb-0.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-amber-600"></i>
                                <span>TL Change Request:</span>
                            </div>
                            <div class="italic text-[11px] leading-relaxed">"{{ $task->revision_notes }}"</div>
                        </div>
                    @endif

                    <!-- Timestamps & Timer Lifecycle Section (Mobile) -->
                    <div class="space-y-1.5 pt-1.5 border-t border-slate-100 task-lifecycle-mobile-{{ $task->id }}">
                        <!-- Target Deadline -->
                        <div class="flex items-center justify-between text-[11px] text-slate-500">
                            <span class="flex items-center gap-1 font-medium text-slate-600">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>Deadline:</span>
                            </span>
                            <span class="font-bold text-slate-700">{{ $task->deadline ? $task->deadline->format('d M Y, h:i A') : 'None' }}</span>
                        </div>

                        <!-- Active Employee Countdown or Stopped Indicator -->
                        @if($task->status === 'pending' || $task->status === 'in-progress')
                            <div class="flex items-center justify-between text-[11px] px-2.5 py-1.5 rounded-xl bg-indigo-50/90 border border-indigo-200 text-indigo-800 font-bold employee-timer-container" data-task-id="{{ $task->id }}" data-deadline="{{ $task->deadline?->toISOString() }}">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                    <span>Time Left:</span>
                                </span>
                                <span class="font-mono text-xs font-black employee-countdown-val">{{ $task->due_label }}</span>
                            </div>
                        @else
                            <div class="flex items-center justify-between text-[11px] px-2.5 py-1 rounded-xl bg-slate-100 text-slate-600 border border-slate-200 font-semibold">
                                <span class="flex items-center gap-1">
                                    <span>⏹️ Employee Timer:</span>
                                </span>
                                <span class="text-slate-800 font-bold">Stopped at Submission</span>
                            </div>
                        @endif

                        <!-- Submission Timestamp with Date & Time -->
                        @if($task->submitted_at)
                            <div class="flex items-center justify-between text-[11px] px-2.5 py-1.5 rounded-xl bg-indigo-50/50 border border-indigo-100 text-indigo-900 font-semibold">
                                <span class="flex items-center gap-1.5 font-bold text-indigo-700">
                                    <i data-lucide="upload-cloud" class="w-3.5 h-3.5 text-indigo-600"></i>
                                    <span>Submitted to TL:</span>
                                </span>
                                <span class="font-bold font-mono text-[11px] text-indigo-950">{{ $task->submitted_at->format('d M Y, h:i A') }}</span>
                            </div>
                        @endif

                        <!-- TL Review Timer & Timestamp -->
                        @if($task->status === 'submitted')
                            @php
                                $mSubAt = $task->submitted_at ?? $task->updated_at;
                                $mElapsedSecs = $mSubAt ? max(0, (int) now()->diffInSeconds($mSubAt)) : 0;
                                $mH = floor($mElapsedSecs / 3600);
                                $mM = floor(($mElapsedSecs % 3600) / 60);
                                $mS = $mElapsedSecs % 60;
                                $mServerElapsed = sprintf('%02dh %02dm %02ds', $mH, $mM, $mS);
                            @endphp
                            <div class="flex items-center justify-between text-[11px] px-2.5 py-1.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-800 font-bold tl-review-timer-container" data-task-id="{{ $task->id }}" data-submitted-at="{{ ($task->submitted_at ?? $task->updated_at ?? now())->toISOString() }}">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping"></span>
                                    <span>Awaiting TL Review:</span>
                                </span>
                                <span class="font-mono text-xs font-black text-purple-900 tl-review-timer-val">{{ $mServerElapsed }}</span>
                            </div>
                        @elseif($task->status === 'completed')
                            <div class="flex items-center justify-between text-[11px] px-2.5 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                                <span class="flex items-center gap-1.5 font-bold text-emerald-700">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Reviewed by TL:</span>
                                </span>
                                <span class="font-bold font-mono text-[11px] text-emerald-950">{{ $task->reviewed_at ? $task->reviewed_at->format('d M Y, h:i A') : ($task->updated_at ? $task->updated_at->format('d M Y, h:i A') : 'Approved') }}</span>
                            </div>
                            @if($task->review_duration)
                                <div class="text-[10px] text-emerald-700 text-right font-medium">Turnaround: {{ $task->review_duration }}</div>
                            @endif
                        @endif

                        <div class="flex items-center justify-between text-[10px] text-slate-400 pt-0.5">
                            <span>Delegated by: {{ $task->assignedBy->name }}</span>
                            @if($task->isReassigned() && $task->previous_deadline)
                                <span class="line-through">Prev: {{ $task->previous_deadline->format('d M, h:i A') }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Action and Progress Details for Mobile -->
                    <div class="pt-2 flex items-center justify-between gap-2">
                        <details class="group flex-1">
                            <summary class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 cursor-pointer flex items-center gap-1 select-none">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                <span>Add Note (<span class="note-counter-{{ $task->id }}">{{ $task->updates->count() }}</span>)</span>
                            </summary>
                            <form method="POST" action="{{ route('tasks.update.add', $task) }}" onsubmit="saveTaskNoteAjax(event, {{ $task->id }})" class="mt-2 space-y-1.5">
                                @csrf @method('PUT')
                                <input type="text" name="message" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="What progress did you make?" required>
                                <button type="submit" class="w-full py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[10px] rounded-md transition shadow-xs">
                                    Save Note
                                </button>
                            </form>
                        </details>

                        <div class="task-action-container-{{ $task->id }}">
                            @if(!in_array($task->status, ['submitted', 'completed']))
                                <button type="button" onclick="openSubmissionModal({{ $task->id }}, '{{ addslashes($task->title) }}')" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs transition shadow-xs flex items-center gap-1 cursor-pointer shrink-0">
                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                    <span>Submit Work</span>
                                </button>
                            @elseif($task->status === 'submitted')
                                <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-200">
                                    In Review
                                </span>
                            @else
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                                    Done
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-slate-400">
                    <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                    <p class="text-xs font-semibold">No tasks assigned to you right now. Great job!</p>
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View (>= 768px) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-extrabold tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4" style="width: 25%;">Task Deliverable</th>
                        <th class="py-3.5 px-4" style="width: 13%;">Team Lead</th>
                        <th class="py-3.5 px-4" style="width: 19%;">Deadline & Timers</th>
                        <th class="py-3.5 px-4" style="width: 12%;">Status</th>
                        <th class="py-3.5 px-4" style="width: 16%;">Updates & Notes</th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap" style="width: 15%;">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tasks as $task)
                        <tr class="hover:bg-slate-50/80 transition {{ $task->isReassigned() ? 'bg-amber-50/30' : '' }}">
                            <td class="py-4 px-4 align-top">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-slate-900 text-sm">{{ $task->title }}</span>
                                    @if($task->isReassigned())
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-300">
                                            ⚠️ Reassigned (Rev #{{ $task->reassignment_count }})
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 max-w-xs mt-0.5">{{ $task->description }}</div>

                                <!-- If Reassigned: Show TL Revision directives -->
                                @if($task->isReassigned() && $task->revision_notes)
                                    <div class="mt-2.5 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                                        <div class="flex items-center gap-1 font-bold text-[11px] text-amber-800 uppercase tracking-wider mb-0.5">
                                            <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                            <span>TL Change Request & Feedback:</span>
                                        </div>
                                        <div class="italic text-[11px] leading-relaxed">"{{ $task->revision_notes }}"</div>
                                    </div>
                                @endif

                                <!-- If Submitted: Show what was submitted -->
                                @if($task->submitted_at)
                                    <div class="mt-2 p-2 rounded-xl bg-slate-50 border border-slate-200 text-[11px] space-y-1">
                                        <div class="font-bold text-slate-700 flex items-center gap-1">
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-600"></i>
                                            <span>My Submission:</span>
                                        </div>
                                        @if($task->submission_remarks)
                                            <div class="text-slate-600 italic">"{{ $task->submission_remarks }}"</div>
                                        @endif
                                        <div class="flex items-center gap-2 flex-wrap pt-0.5">
                                            @if($task->submission_link)
                                                <a href="{{ $task->submission_link }}" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 hover:underline font-bold">
                                                    <i data-lucide="external-link" class="w-3 h-3"></i> Attached Link
                                                </a>
                                            @endif
                                            @if($task->submission_file)
                                                <a href="{{ asset($task->submission_file) }}" target="_blank" class="inline-flex items-center gap-1 text-emerald-600 hover:underline font-bold">
                                                    <i data-lucide="file-check" class="w-3 h-3"></i> View Deliverable ({{ strtoupper($task->submission_file_type ?? 'File') }})
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </td>

                            <td class="py-4 px-4 align-top">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 font-bold text-[10px] flex items-center justify-center">
                                        {{ strtoupper(substr($task->assignedBy->name, 0, 1)) }}
                                    </div>
                                    <span class="font-bold text-slate-800">{{ $task->assignedBy->name }}</span>
                                </div>
                            </td>

                            <!-- Deadline & Lifecycle Timers Column -->
                            <td class="py-4 px-4 align-top">
                                <div class="space-y-1.5 task-deadline-desktop-{{ $task->id }}">
                                    <!-- Target Deadline -->
                                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-slate-700">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                        <span>Deadline: {{ $task->deadline ? $task->deadline->format('d M Y, h:i A') : 'No deadline' }}</span>
                                    </div>
                                    @if($task->isReassigned() && $task->previous_deadline)
                                        <div class="text-[10px] text-slate-400 line-through pl-5">
                                            Prev: {{ $task->previous_deadline->format('d M, h:i A') }}
                                        </div>
                                    @endif

                                    <!-- Employee Timer (Active Countdown or Stopped) -->
                                    @if($task->status === 'pending' || $task->status === 'in-progress')
                                        <div class="employee-timer-container inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-indigo-50 border border-indigo-200 text-indigo-700 text-[10px] font-bold" data-task-id="{{ $task->id }}" data-deadline="{{ $task->deadline?->toISOString() }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                            <span>Time Left:</span>
                                            <span class="font-mono font-black employee-countdown-val">{{ $task->due_label }}</span>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold">
                                            <span>⏹️ Employee Timer Stopped</span>
                                        </div>
                                    @endif

                                    <!-- Submission Timestamp with Date & Time -->
                                    @if($task->submitted_at)
                                        <div class="text-[11px] text-indigo-900 font-semibold flex items-center gap-1.5">
                                            <i data-lucide="upload" class="w-3 h-3 text-indigo-600 shrink-0"></i>
                                            <span>Submitted: <strong class="font-mono">{{ $task->submitted_at->format('d M Y, h:i A') }}</strong></span>
                                        </div>
                                    @endif

                                    <!-- TL Review Timer / Timestamp -->
                                    @if($task->status === 'submitted')
                                        @php
                                            $dtSubAt = $task->submitted_at ?? $task->updated_at;
                                            $dtElapsedSecs = $dtSubAt ? max(0, (int) now()->diffInSeconds($dtSubAt)) : 0;
                                            $dtH = floor($dtElapsedSecs / 3600);
                                            $dtM = floor(($dtElapsedSecs % 3600) / 60);
                                            $dtS = $dtElapsedSecs % 60;
                                            $dtServerElapsed = sprintf('%02dh %02dm %02ds', $dtH, $dtM, $dtS);
                                        @endphp
                                        <div class="tl-review-timer-container inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-purple-50 border border-purple-200 text-purple-800 text-[10px] font-bold" data-task-id="{{ $task->id }}" data-submitted-at="{{ ($task->submitted_at ?? $task->updated_at ?? now())->toISOString() }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-ping"></span>
                                            <span>Awaiting TL Review:</span>
                                            <span class="font-mono font-black text-purple-900 tl-review-timer-val">{{ $dtServerElapsed }}</span>
                                        </div>
                                    @elseif($task->status === 'completed')
                                        <div class="text-[11px] text-emerald-900 font-semibold flex items-center gap-1.5">
                                            <i data-lucide="check-circle" class="w-3 h-3 text-emerald-600 shrink-0"></i>
                                            <span>Reviewed by TL: <strong class="font-mono">{{ $task->reviewed_at ? $task->reviewed_at->format('d M Y, h:i A') : ($task->updated_at ? $task->updated_at->format('d M Y, h:i A') : 'Approved') }}</strong></span>
                                        </div>
                                        @if($task->review_duration)
                                            <div class="text-[10px] text-emerald-700 pl-4.5 font-medium">Turnaround: {{ $task->review_duration }}</div>
                                        @endif
                                    @endif
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-4 px-4 align-top task-status-col-{{ $task->id }}">
                                @if($task->status === 'completed')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1">
                                        <i data-lucide="check-circle" class="w-3 h-3"></i> Completed
                                    </span>
                                @elseif($task->status === 'submitted')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-300 inline-flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3 h-3"></i> Submitted
                                    </span>
                                @elseif($task->status === 'in-progress')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-300 inline-flex items-center gap-1">
                                        <i data-lucide="play-circle" class="w-3 h-3"></i> In Progress
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300 inline-flex items-center gap-1">
                                        <i data-lucide="hourglass" class="w-3 h-3"></i> Pending
                                    </span>
                                @endif
                            </td>

                            <!-- Updates column -->
                            <td class="py-4 px-4 align-top">
                                <div class="space-y-1 mb-2 task-updates-list-{{ $task->id }}">
                                    @forelse($task->updates->take(2) as $update)
                                        <div class="text-[11px] text-slate-600 flex items-start gap-1">
                                            <span class="text-slate-400">&bull;</span>
                                            <span class="truncate">{{ $update->message }}</span>
                                        </div>
                                    @empty
                                        <span class="text-[11px] text-slate-400 italic no-notes-placeholder-{{ $task->id }}">No notes posted yet</span>
                                    @endforelse
                                </div>

                                @if(!in_array($task->status, ['submitted', 'completed']))
                                    <details class="group">
                                        <summary class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 cursor-pointer flex items-center gap-1 select-none">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            <span>Add Progress Note</span>
                                        </summary>
                                        <form method="POST" action="{{ route('tasks.update.add', $task) }}" onsubmit="saveTaskNoteAjax(event, {{ $task->id }})" class="mt-2 space-y-1.5">
                                            @csrf @method('PUT')
                                            <input type="text" name="message" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="What progress did you make?" required>
                                            <button type="submit" class="w-full py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[10px] rounded-md transition shadow-xs">
                                                Save Note
                                            </button>
                                        </form>
                                    </details>
                                @endif
                            </td>

                            <td class="py-4 px-4 align-top text-right whitespace-nowrap">
                                <div class="task-action-container-desktop-{{ $task->id }}">
                                    @if(!in_array($task->status, ['submitted', 'completed']))
                                        <button type="button" onclick="openSubmissionModal({{ $task->id }}, '{{ addslashes($task->title) }}')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold text-xs transition shadow-xs flex items-center gap-1.5 ml-auto cursor-pointer whitespace-nowrap">
                                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                            <span>Submit Work</span>
                                        </button>
                                    @elseif($task->status === 'submitted')
                                        <span class="inline-flex items-center justify-center whitespace-nowrap text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-200">
                                            Under TL Review
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center whitespace-nowrap text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                                            Approved
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                <p class="text-xs font-semibold">No tasks assigned to you right now. Great job!</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Employee Deliverable Submission -->
<div id="submissionModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3.5 sm:p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Submit Deliverables</h3>
                <p id="modalTaskTitle" class="text-xs text-slate-500 font-medium truncate max-w-sm"></p>
            </div>
            <button type="button" onclick="closeSubmissionModal()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer">✕</button>
        </div>

        <form id="submissionForm" method="POST" action="" enctype="multipart/form-data" class="space-y-4" onsubmit="submitDeliverableAjax(event)">
            @csrf @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Remarks / Explanation</label>
                <textarea name="submission_remarks" rows="3" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:outline-none" placeholder="Provide notes, summary of work completed, or instructions for the TL..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deliverable Link (URL)</label>
                <div class="relative">
                    <input type="url" name="submission_link" placeholder="https://github.com/..., https://figma.com/..., or Google Drive link" class="w-full pl-9 pr-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:outline-none">
                    <i data-lucide="link" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Attach File (Image, Video, PDF, or Document)</label>
                <input type="file" name="submission_file" accept="image/*,video/*,.pdf,.doc,.docx,.zip" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                <p class="text-[10px] text-slate-400 mt-1">Supports screenshots, recordings (MP4/MOV), PDF reports, and archives (up to 50MB).</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeSubmissionModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit" id="submissionSubmitBtn" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-md shadow-indigo-600/20 flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span id="submissionSubmitText">Submit to Team Lead</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Floating Instant Toast Notification -->
<div id="instantToast" class="hidden fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border text-sm font-semibold transition-all transform duration-300 translate-y-4">
    <span id="toastIcon"></span>
    <span id="toastMessage"></span>
</div>

@push('scripts')
<script>
let currentActiveTaskId = null;

function openSubmissionModal(taskId, taskTitle) {
    currentActiveTaskId = taskId;
    document.getElementById('modalTaskTitle').innerText = taskTitle;
    document.getElementById('submissionForm').action = '/tasks/' + taskId + '/submit';
    document.getElementById('submissionModal').classList.remove('hidden');
}

function closeSubmissionModal() {
    document.getElementById('submissionModal').classList.add('hidden');
    document.getElementById('submissionForm').reset();
    currentActiveTaskId = null;
}

function showInstantToast(message, type = 'success') {
    const toast = document.getElementById('instantToast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');

    if (!toast) return;

    if (type === 'success') {
        toast.className = 'fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-2xl shadow-xl border bg-emerald-900 text-white border-emerald-500/30 text-xs font-bold transition-all transform duration-300 translate-y-0';
        toastIcon.innerHTML = '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
    } else {
        toast.className = 'fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-2xl shadow-xl border bg-rose-900 text-white border-rose-500/30 text-xs font-bold transition-all transform duration-300 translate-y-0';
        toastIcon.innerHTML = '<svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
    }

    toastMessage.textContent = message;
    toast.classList.remove('hidden');

    setTimeout(() => {
        toast.classList.add('hidden');
    }, 4000);
}

// 1. Submit Deliverable via AJAX (Zero Full Page Reload)
async function submitDeliverableAjax(event) {
    event.preventDefault();
    const form = event.target;
    const btn = document.getElementById('submissionSubmitBtn');
    const btnText = document.getElementById('submissionSubmitText');
    const taskId = currentActiveTaskId;

    btn.disabled = true;
    btnText.textContent = 'Submitting...';

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
            showInstantToast(data.message || 'Deliverables submitted for review!');
            closeSubmissionModal();

            // 1. Instant DOM update for mobile action button
            const mobileActionContainer = document.querySelector(`.task-action-container-${taskId}`);
            if (mobileActionContainer) {
                mobileActionContainer.innerHTML = '<span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-200">In Review</span>';
            }

            // 2. Instant DOM update for desktop action button
            const desktopActionContainer = document.querySelector(`.task-action-container-desktop-${taskId}`);
            if (desktopActionContainer) {
                desktopActionContainer.innerHTML = '<span class="inline-flex items-center justify-center whitespace-nowrap text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-200">Under TL Review</span>';
            }

            // 3. Instant status badge updates
            const mobileStatusBadge = document.querySelector(`.task-status-badge-mobile-${taskId}`);
            if (mobileStatusBadge) {
                mobileStatusBadge.className = `task-status-badge-mobile-${taskId} px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-100 text-indigo-800 border border-indigo-300 shrink-0`;
                mobileStatusBadge.textContent = 'In Review';
            }

            const desktopStatusCol = document.querySelector(`.task-status-col-${taskId}`);
            if (desktopStatusCol) {
                desktopStatusCol.innerHTML = '<span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-300 inline-flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> Submitted</span>';
            }

            // 4. Instant deadline & timer updates (freezes employee timer, records submission time, starts TL review timer)
            const submissionFormatted = data.submitted_at || new Date().toLocaleString();
            const submittedIso = data.submitted_at_iso || new Date().toISOString();

            // 4a. Mobile Lifecycle update
            const mobileLifecycle = document.querySelector(`.task-lifecycle-mobile-${taskId}`);
            if (mobileLifecycle) {
                mobileLifecycle.innerHTML = `
                    <div class="flex items-center justify-between text-[11px] px-2.5 py-1 rounded-xl bg-slate-100 text-slate-600 border border-slate-200 font-semibold">
                        <span>⏹️ Employee Timer:</span>
                        <span class="text-slate-800 font-bold">Stopped at Submission</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] px-2.5 py-1.5 rounded-xl bg-indigo-50/50 border border-indigo-100 text-indigo-900 font-semibold">
                        <span class="flex items-center gap-1.5 font-bold text-indigo-700">
                            <i data-lucide="upload-cloud" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Submitted to TL:</span>
                        </span>
                        <span class="font-bold font-mono text-[11px] text-indigo-950">${submissionFormatted}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] px-2.5 py-1.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-800 font-bold tl-review-timer-container" data-task-id="${taskId}" data-submitted-at="${submittedIso}">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping"></span>
                            <span>Awaiting TL Review:</span>
                        </span>
                        <span class="font-mono text-xs font-black text-purple-900 tl-review-timer-val">00h 00m 01s</span>
                    </div>
                `;
            }

            // 4b. Desktop Deadline & Timers Column update
            const desktopDeadlineContainer = document.querySelector(`.task-deadline-desktop-${taskId}`);
            if (desktopDeadlineContainer) {
                desktopDeadlineContainer.innerHTML = `
                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold">
                        <span>⏹️ Employee Timer Stopped</span>
                    </div>
                    <div class="text-[11px] text-indigo-900 font-semibold flex items-center gap-1.5">
                        <i data-lucide="upload" class="w-3 h-3 text-indigo-600 shrink-0"></i>
                        <span>Submitted: <strong class="font-mono">${submissionFormatted}</strong></span>
                    </div>
                    <div class="tl-review-timer-container inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-purple-50 border border-purple-200 text-purple-800 text-[10px] font-bold" data-task-id="${taskId}" data-submitted-at="${submittedIso}">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-ping"></span>
                        <span>Awaiting TL Review:</span>
                        <span class="font-mono font-black text-purple-900 tl-review-timer-val">00h 00m 01s</span>
                    </div>
                `;
            }

            // Trigger timer recalculation immediately
            updateAllTaskTimers();

            if (window.lucide) {
                lucide.createIcons();
            }
        } else {
            showInstantToast(data.message || 'Submission failed. Please check fields.', 'error');
        }
    } catch (err) {
        showInstantToast('Connection error: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Submit to Team Lead';
    }
}

// 2. Add Task Progress Note via AJAX (Zero Full Page Reload)
async function saveTaskNoteAjax(event, taskId) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const input = form.querySelector('input[name="message"]');
    const message = input.value.trim();

    if (!message) return;

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
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
            showInstantToast('Progress note logged!');
            input.value = '';

            // Update desktop note list
            const updatesContainer = document.querySelector(`.task-updates-list-${taskId}`);
            if (updatesContainer) {
                const placeholder = updatesContainer.querySelector(`.no-notes-placeholder-${taskId}`);
                if (placeholder) placeholder.remove();

                const noteEl = document.createElement('div');
                noteEl.className = 'text-[11px] text-slate-800 flex items-start gap-1 font-semibold animate-in fade-in';
                noteEl.innerHTML = `<span class="text-indigo-500 font-black">&bull;</span> <span class="truncate">${data.note.message}</span>`;
                updatesContainer.prepend(noteEl);
            }

            // Increment note counter
            const counters = document.querySelectorAll(`.note-counter-${taskId}`);
            counters.forEach(c => {
                const cur = parseInt(c.textContent) || 0;
                c.textContent = cur + 1;
            });
        } else {
            showInstantToast(data.message || 'Failed to save note.', 'error');
        }
    } catch (err) {
        showInstantToast('Error saving note: ' + err.message, 'error');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Note';
        }
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
        if (isNaN(deadlineMs)) return;
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

// Tick every second for live update
setInterval(updateAllTaskTimers, 1000);
document.addEventListener('DOMContentLoaded', updateAllTaskTimers);
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    updateAllTaskTimers();
}
</script>
@endpush

@endsection