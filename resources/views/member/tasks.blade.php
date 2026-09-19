@extends('layouts.app')
@section('title', 'My Deliverables')
@section('content')

<div class="space-y-4 max-w-7xl mx-auto w-full overflow-x-hidden pb-28 sm:pb-16">

    @php
        $pendingCount = $tasks->whereIn('status', ['pending', 'in-progress'])->count();
        $submittedCount = $tasks->where('status', 'submitted')->count();
        $completedCount = $tasks->where('status', 'completed')->count();
    @endphp

    <!-- Compact Header Bar (Zero Horizontal Overflow) -->
    <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-200/80">
        <div class="flex items-center gap-2 min-w-0">
            <span class="p-1.5 sm:p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 shrink-0">
                <i data-lucide="layers" class="w-4 h-4 sm:w-5 sm:h-5"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-base sm:text-xl font-black text-slate-900 tracking-tight truncate">My Deliverables</h1>
                <p class="text-[11px] text-slate-400 hidden sm:block truncate">Track assigned work, review TL feedback, and submit tasks</p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            <a href="{{ route('history.index', ['action' => 'all_tasks']) }}" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1 transition shadow-2xs">
                <i data-lucide="history" class="w-3.5 h-3.5 text-slate-500"></i>
                <span class="hidden xs:inline">History</span>
            </a>
            <span class="px-2.5 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                <span>{{ $tasks->count() }} <span class="hidden xs:inline">Tasks</span></span>
            </span>
        </div>
    </div>

    <!-- Filter Pills & Search Bar (Mobile Optimized Horizontal Strip) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-white p-2.5 rounded-2xl border border-slate-200/80 shadow-2xs">
        <!-- Filter Tabs (Smooth Horizontal Scroll) -->
        <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none pb-1 sm:pb-0" id="deliverableTabs">
            <button type="button" onclick="setDeliverableFilter('all')" data-filter="all" class="filter-tab-btn active px-3 py-1 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-900 text-white shadow-xs shrink-0 cursor-pointer">
                <span>All</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-700 text-slate-200">{{ $tasks->count() }}</span>
            </button>
            <button type="button" onclick="setDeliverableFilter('active')" data-filter="active" class="filter-tab-btn px-3 py-1 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0 cursor-pointer">
                <span>Action Needed</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-100 text-amber-800 font-extrabold">{{ $pendingCount }}</span>
            </button>
            <button type="button" onclick="setDeliverableFilter('submitted')" data-filter="submitted" class="filter-tab-btn px-3 py-1 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0 cursor-pointer">
                <span>In Review</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-100 text-purple-800 font-extrabold">{{ $submittedCount }}</span>
            </button>
            <button type="button" onclick="setDeliverableFilter('completed')" data-filter="completed" class="filter-tab-btn px-3 py-1 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0 cursor-pointer">
                <span>Completed</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-extrabold">{{ $completedCount }}</span>
            </button>
        </div>

        <!-- Search Input -->
        <div class="relative w-full sm:w-60">
            <input type="text" id="deliverablesSearch" oninput="filterDeliverablesCards()" placeholder="Search tasks..." class="w-full pl-8 pr-2.5 py-1 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:outline-none transition">
            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2"></i>
        </div>
    </div>

    <!-- Deliverables Feed (Ultra-Clean, Scannable Cards) -->
    <div id="deliverablesFeed" class="space-y-2.5">
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
                 class="task-deliverable-card bg-white rounded-xl sm:rounded-2xl border {{ $isReassigned ? 'border-amber-200 bg-amber-50/15' : 'border-slate-200/90' }} shadow-2xs hover:shadow-xs transition-all duration-150 p-3 sm:p-4.5"
                 data-task-id="{{ $task->id }}"
                 data-status="{{ $task->status }}"
                 data-category="{{ $isPending ? 'active' : ($isSubmitted ? 'submitted' : 'completed') }}"
                 data-search-text="{{ strtolower($task->title . ' ' . $task->description . ' ' . ($task->assignedBy->name ?? '')) }}">

                <!-- Row 1: Status Badge, Assigned By, and Quick Action Button -->
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                        <!-- Single Status Badge (No Duplicate Button) -->
                        <div class="task-status-col-{{ $task->id }} task-status-badge-mobile-{{ $task->id }} task-status-badge-{{ $task->id }}">
                            @if($isCompleted)
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                    <i data-lucide="check-circle" class="w-3 h-3 text-emerald-600"></i> Done
                                </span>
                            @elseif($isSubmitted)
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-ping"></span> In Review
                                </span>
                            @elseif($task->status === 'in-progress')
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> In Progress
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 inline-flex items-center gap-1">
                                    <i data-lucide="hourglass" class="w-3 h-3 text-amber-600"></i> Pending
                                </span>
                            @endif
                        </div>

                        <!-- Revision Badge -->
                        @if($isReassigned)
                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                                <span>⚠️ Rev #{{ $task->reassignment_count }}</span>
                            </span>
                        @endif

                        <!-- Assigned By TL Chip -->
                        <span class="text-[11px] text-slate-500 font-medium truncate">
                            by <strong class="text-slate-800 font-bold">{{ $task->assignedBy->name ?? 'Team Lead' }}</strong>
                        </span>
                    </div>

                    <!-- Action Button Container (Clean CTA or Live Review Timer) -->
                    <div class="task-action-container-{{ $task->id }} task-action-container-desktop-{{ $task->id }} shrink-0">
                        @if($isPending)
                            <button type="button" onclick="openSubmissionModal({{ $task->id }}, '{{ addslashes($task->title) }}')" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold text-xs transition shadow-2xs flex items-center gap-1 cursor-pointer">
                                <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i>
                                <span>Submit Work</span>
                            </button>
                        @elseif($isSubmitted)
                            <div class="tl-review-timer-container inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-purple-50 border border-purple-200 text-purple-800 text-[11px] font-bold whitespace-nowrap" data-task-id="{{ $task->id }}" data-submitted-at="{{ ($task->submitted_at ?? $task->updated_at ?? now())->toISOString() }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-ping"></span>
                                <span>TL Review:</span>
                                <span class="font-mono font-black text-purple-950 tl-review-timer-val">{{ $serverReviewElapsed }}</span>
                            </div>
                        @else
                            <span class="inline-flex items-center text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200 gap-1">
                                <i data-lucide="check-circle-2" class="w-3 h-3 text-emerald-600"></i>
                                <span>Approved</span>
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Row 2: Title & Quick Expand Button -->
                <div class="pt-1.5 pb-1 flex items-start justify-between gap-2 cursor-pointer select-none" onclick="toggleTaskDetails({{ $task->id }})">
                    <h2 class="text-xs sm:text-sm font-bold text-slate-900 leading-snug break-words flex-1">
                        {{ $task->title }}
                    </h2>
                    <button type="button" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-0.5 shrink-0 pt-0.5">
                        <span id="expand-label-{{ $task->id }}">Details</span>
                        <i data-lucide="chevron-down" id="expand-icon-{{ $task->id }}" class="w-3.5 h-3.5 transition-transform duration-200"></i>
                    </button>
                </div>

                <!-- Row 3: Scannable Compact Metadata Strip -->
                <div class="flex items-center gap-2 flex-wrap text-[11px] text-slate-500 pt-0.5">
                    <!-- Deadline -->
                    <span class="inline-flex items-center gap-1 font-medium">
                        <i data-lucide="calendar" class="w-3 h-3 text-slate-400 shrink-0"></i>
                        <span>Due: <strong class="text-slate-700">{{ $task->deadline ? $task->deadline->format('d M, h:i A') : 'None' }}</strong></span>
                    </span>

                    <!-- Active Countdown (when pending) -->
                    @if($isPending)
                        <span class="employee-timer-container inline-flex items-center gap-1 font-bold text-indigo-700" data-task-id="{{ $task->id }}" data-deadline="{{ $task->deadline?->toISOString() }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                            <span>Left:</span>
                            <span class="font-mono employee-countdown-val">{{ $task->due_label }}</span>
                        </span>
                    @endif

                    <!-- Delivered Timestamp (when submitted or completed) -->
                    @if($task->submitted_at)
                        <span class="inline-flex items-center gap-1 text-slate-600 font-medium">
                            <i data-lucide="send" class="w-3 h-3 text-indigo-500"></i>
                            <span>Delivered: <strong class="font-mono text-slate-800">{{ $task->submitted_at->format('d M, h:i A') }}</strong></span>
                        </span>
                    @endif

                    <!-- Notes count preview -->
                    @if($task->updates->count() > 0)
                        <span class="inline-flex items-center gap-1 text-slate-400">
                            <i data-lucide="message-square" class="w-3 h-3"></i>
                            <span>{{ $task->updates->count() }} note{{ $task->updates->count() > 1 ? 's' : '' }}</span>
                        </span>
                    @endif
                </div>

                <!-- Expandable Deep Details Drawer (Collapsed by default, single-open accordion) -->
                <div id="task-details-{{ $task->id }}" class="task-details-drawer hidden mt-2.5 pt-2.5 border-t border-slate-100 space-y-2.5">
                    
                    <!-- Full Description -->
                    @if($task->description)
                        <div class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-2.5 rounded-xl border border-slate-100 break-words whitespace-pre-line">
                            {{ $task->description }}
                        </div>
                    @endif

                    <!-- Revision Notes Directive (If reassigned) -->
                    @if($isReassigned && $task->revision_notes)
                        <div class="p-2.5 sm:p-3 rounded-xl bg-amber-50 border border-amber-200/90 text-amber-950 text-xs">
                            <div class="flex items-center gap-1 font-bold text-amber-800 uppercase tracking-wider text-[10px] mb-0.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-amber-600 shrink-0"></i>
                                <span>TL Change Request & Feedback:</span>
                            </div>
                            <div class="italic text-xs text-amber-900 font-medium pl-4 leading-relaxed break-words">
                                "{{ $task->revision_notes }}"
                            </div>
                        </div>
                    @endif

                    <!-- Showcase: Submitter Deliverables -->
                    @if($task->submitted_at)
                        <div class="p-2.5 sm:p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                            <div class="flex items-center justify-between gap-1 flex-wrap text-xs">
                                <span class="font-bold text-slate-800 flex items-center gap-1 text-[11px]">
                                    <i data-lucide="file-check-2" class="w-3.5 h-3.5 text-indigo-600"></i>
                                    <span>Submitted Deliverables:</span>
                                </span>
                                <span class="text-[10px] text-slate-500 font-medium">
                                    {{ $task->submitted_at->format('d M Y, h:i A') }}
                                </span>
                            </div>

                            @if($task->submission_remarks)
                                <div class="p-2 rounded-lg bg-white border border-slate-200/70 text-xs text-slate-700 italic break-words">
                                    "{{ $task->submission_remarks }}"
                                </div>
                            @endif

                            <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                                @if($task->submission_link)
                                    <a href="{{ $task->submission_link }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold transition">
                                        <i data-lucide="external-link" class="w-3 h-3 text-indigo-600"></i>
                                        <span>Attached Link</span>
                                    </a>
                                @endif

                                @if($task->submission_file)
                                    <a href="{{ asset($task->submission_file) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold transition">
                                        <i data-lucide="download" class="w-3 h-3 text-emerald-600"></i>
                                        <span>Deliverable File ({{ strtoupper($task->submission_file_type ?? 'File') }})</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Full Lifecycle Milestones Strip (DOM Target for AJAX Updates) -->
                    <div class="task-lifecycle-mobile-{{ $task->id }} task-deadline-desktop-{{ $task->id }} task-lifecycle-{{ $task->id }} flex items-center gap-1.5 flex-wrap text-xs pt-1">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold text-[11px]">
                            <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                            <span>Target: {{ $task->deadline ? $task->deadline->format('d M Y, h:i A') : 'None' }}</span>
                        </span>

                        @if($task->submitted_at)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-indigo-50 text-indigo-900 font-semibold text-[11px]">
                                <i data-lucide="send" class="w-3 h-3 text-indigo-600"></i>
                                <span>Delivered: {{ $task->submitted_at->format('d M Y, h:i A') }}</span>
                            </span>
                        @endif

                        @if($isCompleted)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-50 text-emerald-900 font-semibold text-[11px]">
                                <i data-lucide="check-circle-2" class="w-3 h-3 text-emerald-600"></i>
                                <span>Approved: {{ $task->reviewed_at ? $task->reviewed_at->format('d M Y, h:i A') : 'Completed' }}</span>
                            </span>
                        @endif
                    </div>

                    <!-- Progress Notes & Quick Log Form -->
                    <div class="pt-1.5 border-t border-slate-100 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                            <span class="flex items-center gap-1 text-[11px]">
                                <i data-lucide="message-square" class="w-3 h-3 text-slate-400"></i>
                                <span>Progress Notes (<span class="note-counter-{{ $task->id }}">{{ $task->updates->count() }}</span>)</span>
                            </span>
                        </div>

                        <!-- Notes List -->
                        <div class="space-y-1 task-updates-list-{{ $task->id }} max-h-40 overflow-y-auto">
                            @forelse($task->updates as $update)
                                <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 text-[11px] text-slate-700 flex items-start justify-between gap-1.5">
                                    <div class="flex items-start gap-1.5 break-words flex-1">
                                        <span class="text-indigo-500 font-black">•</span>
                                        <span>{{ $update->message }}</span>
                                    </div>
                                    <span class="text-[9px] text-slate-400 shrink-0 whitespace-nowrap">
                                        {{ $update->created_at ? $update->created_at->format('d M, h:i A') : '' }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-[11px] text-slate-400 italic no-notes-placeholder-{{ $task->id }}">No progress notes logged yet.</p>
                            @endforelse
                        </div>

                        <!-- Add Note Form -->
                        @if($isPending)
                            <form method="POST" action="{{ route('tasks.update.add', $task) }}" onsubmit="saveTaskNoteAjax(event, {{ $task->id }})" class="flex items-center gap-1.5 pt-1">
                                @csrf @method('PUT')
                                <input type="text" name="message" class="flex-1 px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:ring-1 focus:ring-indigo-500 focus:outline-none" placeholder="Log quick update..." required>
                                <button type="submit" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-lg transition shadow-2xs shrink-0 cursor-pointer">
                                    Post
                                </button>
                            </form>
                        @endif
                    </div>

                </div>

            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center text-slate-400 shadow-2xs">
                <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                <h3 class="text-xs font-bold text-slate-700">All Caught Up!</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">No tasks in your queue right now. Great job!</p>
            </div>
        @endforelse

        <!-- Empty state placeholder for search/filters -->
        <div id="noFilterResults" class="hidden bg-white rounded-2xl border border-slate-200/80 p-8 text-center text-slate-400 shadow-2xs">
            <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
            <h3 class="text-xs font-bold text-slate-700">No deliverables found</h3>
            <p class="text-[11px] text-slate-400 mt-0.5">Try adjusting your filter or search query</p>
        </div>
    </div>

</div>

<!-- Modal: Employee Deliverable Submission -->
<div id="submissionModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3.5 sm:p-4">
    <div class="bg-white rounded-2xl sm:rounded-3xl max-w-lg w-full p-4 sm:p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 mb-3.5">
            <div>
                <h3 class="font-bold text-slate-900 text-sm sm:text-base">Submit Deliverables</h3>
                <p id="modalTaskTitle" class="text-xs text-slate-500 font-medium truncate max-w-xs sm:max-w-sm"></p>
            </div>
            <button type="button" onclick="closeSubmissionModal()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer">✕</button>
        </div>

        <form id="submissionForm" method="POST" action="" enctype="multipart/form-data" class="space-y-3.5" onsubmit="submitDeliverableAjax(event)">
            @csrf @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Remarks / Explanation</label>
                <textarea name="submission_remarks" rows="2" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:outline-none" placeholder="Provide notes or summary of work completed..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deliverable Link (URL)</label>
                <div class="relative">
                    <input type="url" name="submission_link" placeholder="https://github.com/..., https://figma.com/..., or Google Drive" class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:outline-none">
                    <i data-lucide="link" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Attach File (Up to 50MB)</label>
                <input type="file" name="submission_file" accept="image/*,video/*,.pdf,.doc,.docx,.zip" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                <p class="text-[10px] text-slate-400 mt-1">Supports screenshots, videos, documents, and zip files.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-slate-100">
                <button type="button" onclick="closeSubmissionModal()" class="px-3.5 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="submissionSubmitBtn" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span id="submissionSubmitText">Submit Deliverables</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Floating Instant Toast Notification -->
<div id="instantToast" class="hidden fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-2xl shadow-xl border text-xs font-bold transition-all transform duration-300 translate-y-4">
    <span id="toastIcon"></span>
    <span id="toastMessage"></span>
</div>

@push('scripts')
<script>
let currentActiveTaskId = null;
let currentFilter = 'all';

// Toggle details for a single task card (Single-open accordion: only one task open at a time)
function toggleTaskDetails(taskId) {
    const targetDrawer = document.getElementById(`task-details-${taskId}`);
    if (!targetDrawer) return;

    const willOpen = targetDrawer.classList.contains('hidden');

    // Close all other drawers so only ONE task can be open at any time
    document.querySelectorAll('.task-details-drawer').forEach(drawer => {
        if (drawer.id !== `task-details-${taskId}`) {
            drawer.classList.add('hidden');
            const otherId = drawer.id.replace('task-details-', '');
            const otherIcon = document.getElementById(`expand-icon-${otherId}`);
            const otherLabel = document.getElementById(`expand-label-${otherId}`);
            if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
            if (otherLabel) otherLabel.textContent = 'Details';
        }
    });

    const targetIcon = document.getElementById(`expand-icon-${taskId}`);
    const targetLabel = document.getElementById(`expand-label-${taskId}`);

    if (willOpen) {
        targetDrawer.classList.remove('hidden');
        if (targetIcon) targetIcon.style.transform = 'rotate(180deg)';
        if (targetLabel) targetLabel.textContent = 'Hide';
    } else {
        targetDrawer.classList.add('hidden');
        if (targetIcon) targetIcon.style.transform = 'rotate(0deg)';
        if (targetLabel) targetLabel.textContent = 'Details';
    }
}

// Quick Tabs Filter
function setDeliverableFilter(category) {
    currentFilter = category;
    document.querySelectorAll('.filter-tab-btn').forEach(btn => {
        if (btn.getAttribute('data-filter') === category) {
            btn.className = 'filter-tab-btn active px-3 py-1 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-900 text-white shadow-xs shrink-0 cursor-pointer';
        } else {
            btn.className = 'filter-tab-btn px-3 py-1 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0 cursor-pointer';
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
        toast.className = 'fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-2xl shadow-xl border bg-emerald-900 text-white border-emerald-500/30 text-xs font-bold transition-all transform duration-300 translate-y-0';
        toastIcon.innerHTML = '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
    } else {
        toast.className = 'fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-2xl shadow-xl border bg-rose-900 text-white border-rose-500/30 text-xs font-bold transition-all transform duration-300 translate-y-0';
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

            const submissionFormatted = data.submitted_at || new Date().toLocaleString();
            const submittedIso = data.submitted_at_iso || new Date().toISOString();

            // 1. Update action container to live TL review timer
            document.querySelectorAll(`.task-action-container-${taskId}, .task-action-container-desktop-${taskId}`).forEach(container => {
                container.innerHTML = `
                    <div class="tl-review-timer-container inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-purple-50 border border-purple-200 text-purple-800 text-[11px] font-bold whitespace-nowrap" data-task-id="${taskId}" data-submitted-at="${submittedIso}">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-ping"></span>
                        <span>TL Review:</span>
                        <span class="font-mono font-black text-purple-950 tl-review-timer-val">00h 00m 01s</span>
                    </div>
                `;
            });

            // 2. Update status badges
            document.querySelectorAll(`.task-status-col-${taskId}, .task-status-badge-mobile-${taskId}, .task-status-badge-${taskId}`).forEach(badge => {
                badge.innerHTML = `
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200 inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-ping"></span> In Review
                    </span>
                `;
            });

            // 3. Update category data attribute on card for filters
            const card = document.getElementById(`task-card-${taskId}`);
            if (card) {
                card.setAttribute('data-category', 'submitted');
                card.setAttribute('data-status', 'submitted');
            }

            // 4. Update lifecycle strip
            document.querySelectorAll(`.task-lifecycle-${taskId}, .task-lifecycle-mobile-${taskId}, .task-deadline-desktop-${taskId}`).forEach(lifecycle => {
                lifecycle.innerHTML = `
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-indigo-50 text-indigo-900 font-semibold text-[11px]">
                        <i data-lucide="send" class="w-3 h-3 text-indigo-600"></i>
                        <span>Delivered: ${submissionFormatted}</span>
                    </span>
                    <div class="tl-review-timer-container inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-purple-50 border border-purple-200 text-purple-800 text-[11px] font-bold whitespace-nowrap" data-task-id="${taskId}" data-submitted-at="${submittedIso}">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-ping"></span>
                        <span>TL Review:</span>
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
        btnText.textContent = 'Submit Deliverables';
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
        submitBtn.textContent = '...';
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
                noteEl.className = 'p-2 rounded-lg bg-slate-50 border border-slate-100 text-[11px] text-slate-700 flex items-start justify-between gap-1.5 animate-in fade-in';
                noteEl.innerHTML = `
                    <div class="flex items-start gap-1.5 break-words flex-1">
                        <span class="text-indigo-500 font-black">•</span>
                        <span>${data.note.message}</span>
                    </div>
                    <span class="text-[9px] text-slate-400 shrink-0 whitespace-nowrap">Just now</span>
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
            submitBtn.textContent = 'Post';
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