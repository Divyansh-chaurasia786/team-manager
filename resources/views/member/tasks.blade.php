@extends('layouts.app')
@section('title', 'My Deliverables')
@section('content')

<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/80">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">My Deliverables Queue</h1>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 pl-0.5">Track assigned work, log progress notes, review TL feedback, and submit your deliverables</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('history.index', ['action' => 'all_tasks']) }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1.5 transition shadow-2xs">
                <i data-lucide="history" class="w-4 h-4 text-slate-500"></i>
                <span>Task History</span>
            </a>
            <span class="px-3.5 py-2 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold flex items-center gap-1.5">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-indigo-600"></i>
                <span>{{ $tasks->count() }} Total Assigned</span>
            </span>
        </div>
    </div>

    @php
        $pendingCount = $tasks->whereIn('status', ['pending', 'in-progress'])->count();
        $submittedCount = $tasks->where('status', 'submitted')->count();
        $completedCount = $tasks->where('status', 'completed')->count();
    @endphp

    <!-- Filter Pills & Search Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200/80 shadow-2xs">
        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none" id="deliverableTabs">
            <button type="button" onclick="setDeliverableFilter('all')" data-filter="all" class="filter-tab-btn active px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-900 text-white shadow-xs">
                <span>All Tasks</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-700 text-slate-200">{{ $tasks->count() }}</span>
            </button>
            <button type="button" onclick="setDeliverableFilter('active')" data-filter="active" class="filter-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700">
                <span>Action Needed</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-100 text-amber-800 font-extrabold">{{ $pendingCount }}</span>
            </button>
            <button type="button" onclick="setDeliverableFilter('submitted')" data-filter="submitted" class="filter-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700">
                <span>Under TL Review</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-100 text-purple-800 font-extrabold">{{ $submittedCount }}</span>
            </button>
            <button type="button" onclick="setDeliverableFilter('completed')" data-filter="completed" class="filter-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700">
                <span>Completed</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-extrabold">{{ $completedCount }}</span>
            </button>
        </div>

        <!-- Quick Live Search -->
        <div class="relative min-w-[220px] sm:w-64">
            <input type="text" id="deliverablesSearch" oninput="filterDeliverablesCards()" placeholder="Search deliverables..." class="w-full pl-9 pr-3.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:outline-none transition">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2"></i>
        </div>
    </div>

    <!-- Deliverables Feed (Modern Responsive Card Workspace) -->
    <div id="deliverablesFeed" class="space-y-4">
        @forelse($tasks as $task)
            @php
                $isPending = in_array($task->status, ['pending', 'in-progress']);
                $isSubmitted = $task->status === 'submitted';
                $isCompleted = $task->status === 'completed';
                $isReassigned = $task->isReassigned();

                // Compute TL review elapsed time
                $subAt = $task->submitted_at ?? $task->updated_at;
                $elapsedSecs = $subAt ? max(0, (int) now()->diffInSeconds($subAt)) : 0;
                $tH = floor($elapsedSecs / 3600);
                $tM = floor(($elapsedSecs % 3600) / 60);
                $tS = $elapsedSecs % 60;
                $serverReviewElapsed = sprintf('%02dh %02dm %02ds', $tH, $tM, $tS);
            @endphp

            <div id="task-card-{{ $task->id }}" 
                 class="task-deliverable-card bg-white rounded-2xl sm:rounded-3xl border {{ $isReassigned ? 'border-amber-200 bg-amber-50/15' : 'border-slate-200/80' }} shadow-xs hover:shadow-md transition-all duration-200 p-4 sm:p-6"
                 data-task-id="{{ $task->id }}"
                 data-status="{{ $task->status }}"
                 data-category="{{ $isPending ? 'active' : ($isSubmitted ? 'submitted' : 'completed') }}"
                 data-search-text="{{ strtolower($task->title . ' ' . $task->description . ' ' . ($task->assignedBy->name ?? '')) }}">

                <!-- Card Header Strip: Status Badges, Assignee, and Primary Action CTA -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3.5 border-b border-slate-100">
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Status Badge (Responsive) -->
                        <div class="task-status-col-{{ $task->id }} task-status-badge-mobile-{{ $task->id }} task-status-badge-{{ $task->id }}">
                            @if($isCompleted)
                                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1.5">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-600"></i> Completed
                                </span>
                            @elseif($isSubmitted)
                                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200 inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping"></span> Under TL Review
                                </span>
                            @elseif($task->status === 'in-progress')
                                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200 inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span> In Progress
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200 inline-flex items-center gap-1.5">
                                    <i data-lucide="hourglass" class="w-3.5 h-3.5 text-amber-600"></i> Pending Action
                                </span>
                            @endif
                        </div>

                        <!-- Reassignment / Revision Counter Tag -->
                        @if($isReassigned)
                            <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-700"></i>
                                <span>Revision #{{ $task->reassignment_count }}</span>
                            </span>
                        @endif

                        <!-- Assigned By TL Chip -->
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold">
                            <div class="w-4 h-4 rounded-full bg-indigo-600 text-white font-black text-[9px] flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($task->assignedBy->name ?? 'T', 0, 1)) }}
                            </div>
                            <span>Assigned by <strong class="text-slate-900 font-bold">{{ $task->assignedBy->name ?? 'Team Lead' }}</strong></span>
                        </div>
                    </div>

                    <!-- Top Right Action CTA Button -->
                    <div class="task-action-container-{{ $task->id }} task-action-container-desktop-{{ $task->id }} shrink-0">
                        @if($isPending)
                            <button type="button" onclick="openSubmissionModal({{ $task->id }}, '{{ addslashes($task->title) }}')" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs transition shadow-xs flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                                <span>Submit Deliverables</span>
                            </button>
                        @elseif($isSubmitted)
                            <span class="inline-flex items-center justify-center whitespace-nowrap text-xs font-bold text-purple-700 bg-purple-50 px-3.5 py-1.5 rounded-xl border border-purple-200 gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping"></span>
                                <span>Awaiting TL Review</span>
                            </span>
                        @else
                            <span class="inline-flex items-center justify-center whitespace-nowrap text-xs font-bold text-emerald-700 bg-emerald-50 px-3.5 py-1.5 rounded-xl border border-emerald-200 gap-1.5">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                                <span>Approved</span>
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Deliverable Title & Description -->
                <div class="pt-3.5 pb-2">
                    <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-snug">{{ $task->title }}</h2>
                    @if($task->description)
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mt-1 whitespace-pre-line">{{ $task->description }}</p>
                    @endif
                </div>

                <!-- Revision Directive Banner (If reassigned by TL) -->
                @if($isReassigned && $task->revision_notes)
                    <div class="my-3 p-3.5 sm:p-4 rounded-2xl bg-amber-50 border border-amber-200/90 text-amber-950 text-xs shadow-2xs">
                        <div class="flex items-center gap-1.5 font-black text-amber-800 uppercase tracking-wider mb-1">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                            <span>Team Lead Revision Directive & Feedback:</span>
                        </div>
                        <div class="italic text-xs sm:text-sm text-amber-900 font-medium pl-5 leading-relaxed">
                            "{{ $task->revision_notes }}"
                        </div>
                    </div>
                @endif

                <!-- Showcase: Submitter Deliverables (Remarks, URL link, attached file) -->
                @if($task->submitted_at)
                    <div class="my-3 p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-2.5">
                        <div class="flex items-center justify-between gap-2 flex-wrap text-xs">
                            <span class="font-bold text-slate-800 flex items-center gap-1.5">
                                <i data-lucide="file-check-2" class="w-4 h-4 text-indigo-600"></i>
                                <span>My Submitted Deliverables:</span>
                            </span>
                            <span class="text-[11px] text-slate-500 font-semibold">
                                Delivered: {{ $task->submitted_at->format('d M Y, h:i A') }}
                            </span>
                        </div>

                        @if($task->submission_remarks)
                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/70 text-xs text-slate-700 italic">
                                "{{ $task->submission_remarks }}"
                            </div>
                        @endif

                        <div class="flex items-center gap-2 flex-wrap pt-0.5">
                            @if($task->submission_link)
                                <a href="{{ $task->submission_link }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold transition shadow-2xs">
                                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-indigo-600"></i>
                                    <span>Open Attached Link</span>
                                </a>
                            @endif

                            @if($task->submission_file)
                                <a href="{{ asset($task->submission_file) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold transition shadow-2xs">
                                    <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Download Deliverable ({{ strtoupper($task->submission_file_type ?? 'File') }})</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Horizontal Milestones & Live Timers Strip (Formatted on Single Horizontal Line per requirement) -->
                <div class="task-lifecycle-mobile-{{ $task->id }} task-deadline-desktop-{{ $task->id }} task-lifecycle-{{ $task->id }} flex items-center gap-2 sm:gap-3 flex-wrap py-2.5 border-y border-slate-100 my-2">
                    
                    <!-- 1. Target Deadline Chip -->
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold whitespace-nowrap">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                        <span>Deadline: <strong class="text-slate-900">{{ $task->deadline ? $task->deadline->format('d M Y, h:i A') : 'None' }}</strong></span>
                    </div>

                    <!-- If Reassigned: Previous Deadline reference -->
                    @if($isReassigned && $task->previous_deadline)
                        <div class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-50 text-slate-400 text-xs line-through whitespace-nowrap">
                            <span>Prev: {{ $task->previous_deadline->format('d M, h:i A') }}</span>
                        </div>
                    @endif

                    <!-- 2. Active Employee Countdown (Only when in-progress/pending) -->
                    @if($isPending)
                        <div class="employee-timer-container inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold whitespace-nowrap" data-task-id="{{ $task->id }}" data-deadline="{{ $task->deadline?->toISOString() }}">
                            <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse shrink-0"></span>
                            <span>Time Left:</span>
                            <span class="font-mono font-black employee-countdown-val">{{ $task->due_label }}</span>
                        </div>
                    @endif

                    <!-- 3. Delivered Timestamp (When submitted or completed) -->
                    @if($task->submitted_at)
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80 text-indigo-900 text-xs font-semibold whitespace-nowrap">
                            <i data-lucide="send" class="w-3.5 h-3.5 text-indigo-600 shrink-0"></i>
                            <span>Delivered: <strong class="font-mono text-indigo-950 font-bold">{{ $task->submitted_at->format('d M Y, h:i A') }}</strong></span>
                        </div>
                    @endif

                    <!-- 4. Live TL Review Pending Timer (When awaiting review) -->
                    @if($isSubmitted)
                        <div class="tl-review-timer-container inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-800 text-xs font-bold whitespace-nowrap" data-task-id="{{ $task->id }}" data-submitted-at="{{ ($task->submitted_at ?? $task->updated_at ?? now())->toISOString() }}">
                            <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping shrink-0"></span>
                            <span>TL Review Pending:</span>
                            <span class="font-mono font-black text-purple-950 tl-review-timer-val">{{ $serverReviewElapsed }}</span>
                        </div>
                    @elseif($isCompleted)
                        <!-- 5. Reviewed Timestamp (When completed) -->
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold whitespace-nowrap">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Reviewed by TL: <strong class="font-mono text-emerald-950 font-bold">{{ $task->reviewed_at ? $task->reviewed_at->format('d M Y, h:i A') : ($task->updated_at ? $task->updated_at->format('d M Y, h:i A') : 'Approved') }}</strong></span>
                        </div>
                        @if($task->review_duration)
                            <div class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-emerald-100/70 text-emerald-800 text-xs font-bold whitespace-nowrap">
                                <span>Turnaround: {{ $task->review_duration }}</span>
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Footer: Progress Notes & Interactive Logger Drawer -->
                <div class="pt-2">
                    <details class="group">
                        <summary class="inline-flex items-center gap-2 text-xs font-bold text-indigo-600 hover:text-indigo-700 cursor-pointer select-none py-1 transition">
                            <i data-lucide="message-square" class="w-4 h-4"></i>
                            <span>Progress Notes (<span class="note-counter-{{ $task->id }}">{{ $task->updates->count() }}</span>)</span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform group-open:rotate-180"></i>
                        </summary>

                        <div class="mt-3 pt-3 border-t border-slate-100 space-y-3">
                            <!-- Notes Timeline List -->
                            <div class="space-y-1.5 task-updates-list-{{ $task->id }}">
                                @forelse($task->updates as $update)
                                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-700 flex items-start justify-between gap-2">
                                        <div class="flex items-start gap-2">
                                            <span class="text-indigo-500 font-black mt-0.5">•</span>
                                            <span class="leading-relaxed">{{ $update->message }}</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 shrink-0 whitespace-nowrap font-medium">
                                            {{ $update->created_at ? $update->created_at->format('d M, h:i A') : '' }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 italic no-notes-placeholder-{{ $task->id }}">No progress notes logged yet.</p>
                                @endforelse
                            </div>

                            <!-- Inline Progress Note Form -->
                            @if($isPending)
                                <form method="POST" action="{{ route('tasks.update.add', $task) }}" onsubmit="saveTaskNoteAjax(event, {{ $task->id }})" class="flex items-center gap-2 pt-1">
                                    @csrf @method('PUT')
                                    <input type="text" name="message" class="flex-1 px-3.5 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none" placeholder="Add an update (e.g. Completed initial wireframe, ready for feedback)..." required>
                                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition shadow-xs shrink-0 flex items-center gap-1.5 cursor-pointer">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span>Save Note</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </details>
                </div>

            </div>
        @empty
            <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center text-slate-400 shadow-2xs">
                <i data-lucide="check-circle" class="w-10 h-10 mx-auto mb-3 text-slate-300"></i>
                <h3 class="text-sm font-bold text-slate-700">All Caught Up!</h3>
                <p class="text-xs text-slate-400 mt-1">No tasks assigned to your queue right now. Great job!</p>
            </div>
        @endforelse

        <!-- Empty state placeholder for when filters yield 0 results -->
        <div id="noFilterResults" class="hidden bg-white rounded-3xl border border-slate-200/80 p-12 text-center text-slate-400 shadow-2xs">
            <i data-lucide="search-x" class="w-10 h-10 mx-auto mb-3 text-slate-300"></i>
            <h3 class="text-sm font-bold text-slate-700">No deliverables found</h3>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your filter or search query</p>
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
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Remarks / Summary of Work</label>
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
                <button type="button" onclick="closeSubmissionModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
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
let currentFilter = 'all';

// Quick Tabs Filter
function setDeliverableFilter(category) {
    currentFilter = category;
    document.querySelectorAll('.filter-tab-btn').forEach(btn => {
        if (btn.getAttribute('data-filter') === category) {
            btn.className = 'filter-tab-btn active px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-900 text-white shadow-xs';
        } else {
            btn.className = 'filter-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700';
        }
    });
    filterDeliverablesCards();
}

// Live Search & Filter Logic
function filterDeliverablesCards() {
    const query = (document.getElementById('deliverablesSearch')?.value || '').toLowerCase().trim();
    const cards = document.querySelectorAll('.task-deliverable-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const category = card.getAttribute('data-category');
        const searchTarget = card.getAttribute('data-search-text') || '';

        const matchesCategory = (currentFilter === 'all') || (category === currentFilter);
        const matchesSearch = !query || searchTarget.includes(query);

        if (matchesCategory && matchesSearch) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });

    const noResults = document.getElementById('noFilterResults');
    if (noResults) {
        if (visibleCount === 0 && cards.length > 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    }
}

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

            // 1. Update action containers
            document.querySelectorAll(`.task-action-container-${taskId}, .task-action-container-desktop-${taskId}`).forEach(container => {
                container.innerHTML = `
                    <span class="inline-flex items-center justify-center whitespace-nowrap text-xs font-bold text-purple-700 bg-purple-50 px-3.5 py-1.5 rounded-xl border border-purple-200 gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping"></span>
                        <span>Awaiting TL Review</span>
                    </span>
                `;
            });

            // 2. Update status badges
            document.querySelectorAll(`.task-status-col-${taskId}, .task-status-badge-mobile-${taskId}, .task-status-badge-${taskId}`).forEach(badge => {
                badge.innerHTML = `
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200 inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping"></span> Under TL Review
                    </span>
                `;
            });

            // 3. Update category data attribute on card for filters
            const card = document.getElementById(`task-card-${taskId}`);
            if (card) {
                card.setAttribute('data-category', 'submitted');
                card.setAttribute('data-status', 'submitted');
            }

            // 4. Update lifecycle strip (freeze employee countdown, add delivered & start live TL review timer)
            const submissionFormatted = data.submitted_at || new Date().toLocaleString();
            const submittedIso = data.submitted_at_iso || new Date().toISOString();

            document.querySelectorAll(`.task-lifecycle-${taskId}, .task-lifecycle-mobile-${taskId}, .task-deadline-desktop-${taskId}`).forEach(lifecycle => {
                lifecycle.innerHTML = `
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80 text-indigo-900 text-xs font-semibold whitespace-nowrap">
                        <i data-lucide="send" class="w-3.5 h-3.5 text-indigo-600 shrink-0"></i>
                        <span>Delivered: <strong class="font-mono text-indigo-950 font-bold">${submissionFormatted}</strong></span>
                    </div>
                    <div class="tl-review-timer-container inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-800 text-xs font-bold whitespace-nowrap" data-task-id="${taskId}" data-submitted-at="${submittedIso}">
                        <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping shrink-0"></span>
                        <span>TL Review Pending:</span>
                        <span class="font-mono font-black text-purple-950 tl-review-timer-val">00h 00m 01s</span>
                    </div>
                `;
            });

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
        submitBtn.innerHTML = '<span>Saving...</span>';
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

            // Update notes list
            const updatesContainer = document.querySelector(`.task-updates-list-${taskId}`);
            if (updatesContainer) {
                const placeholder = updatesContainer.querySelector(`.no-notes-placeholder-${taskId}`);
                if (placeholder) placeholder.remove();

                const noteEl = document.createElement('div');
                noteEl.className = 'p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-700 flex items-start justify-between gap-2 animate-in fade-in';
                noteEl.innerHTML = `
                    <div class="flex items-start gap-2">
                        <span class="text-indigo-500 font-black mt-0.5">•</span>
                        <span class="leading-relaxed">${data.note.message}</span>
                    </div>
                    <span class="text-[10px] text-slate-400 shrink-0 whitespace-nowrap font-medium">Just now</span>
                `;
                updatesContainer.prepend(noteEl);
            }

            // Increment note counter
            document.querySelectorAll(`.note-counter-${taskId}`).forEach(c => {
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
            submitBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg><span>Save Note</span>';
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
document.addEventListener('DOMContentLoaded', () => {
    updateAllTaskTimers();
    if (window.lucide) {
        lucide.createIcons();
    }
});
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    updateAllTaskTimers();
}
</script>
@endpush

@endsection