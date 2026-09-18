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
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 shrink-0">
                                ✓ Done
                            </span>
                        @elseif($task->status === 'submitted')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-100 text-indigo-800 border border-indigo-300 shrink-0">
                                In Review
                            </span>
                        @elseif($task->status === 'in-progress')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-800 border border-blue-300 shrink-0">
                                Active
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-300 shrink-0">
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

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 flex-wrap gap-2">
                        <div class="flex items-center gap-1.5 font-medium">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 {{ $task->isOverdue() ? 'text-rose-500' : ($task->deadline->isToday() ? 'text-rose-400' : 'text-slate-400') }}"></i>
                            <span class="{{ $task->isOverdue() ? 'text-rose-600 font-bold' : ($task->deadline->isToday() ? 'text-rose-600 font-bold' : ($task->deadline->isTomorrow() ? 'text-amber-700 font-bold' : 'text-slate-700 font-semibold') ) }}">
                                {{ $task->due_label }}
                            </span>
                        </div>
                        <div class="text-[10px] text-slate-400">By {{ $task->assignedBy->name }}</div>
                    </div>

                    <!-- Action and Progress Details for Mobile -->
                    <div class="pt-2 flex items-center justify-between gap-2">
                        <details class="group flex-1">
                            <summary class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 cursor-pointer flex items-center gap-1 select-none">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                <span>Add Note ({{ $task->updates->count() }})</span>
                            </summary>
                            <form method="POST" action="{{ route('tasks.update.add', $task) }}" class="mt-2 space-y-1.5">
                                @csrf @method('PUT')
                                <input type="text" name="message" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="What progress did you make?" required>
                                <button type="submit" class="w-full py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[10px] rounded-md transition shadow-xs">
                                    Save Note
                                </button>
                            </form>
                        </details>

                        @if(!in_array($task->status, ['submitted', 'completed']))
                            <button type="button" onclick="openSubmissionModal({{ $task->id }}, '{{ addslashes($task->title) }}')" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs transition shadow-xs flex items-center gap-1 cursor-pointer shrink-0">
                                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                <span>Submit Work</span>
                            </button>
                        @endif
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
                        <th class="py-3.5 px-4" style="width: 14%;">Team Lead</th>
                        <th class="py-3.5 px-4" style="width: 14%;">Deadline</th>
                        <th class="py-3.5 px-4" style="width: 12%;">Status</th>
                        <th class="py-3.5 px-4" style="width: 20%;">Updates & Notes</th>
                        <th class="py-3.5 px-4 text-right" style="width: 15%;">Action</th>
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

                            <!-- Deadline Column: Distinctly shows Previous vs New if Reassigned -->
                            <td class="py-4 px-4 align-top">
                                <div class="space-y-0.5">
                                    {{-- Smart due label --}}
                                    <div class="flex items-center gap-1 font-bold
                                        {{ $task->isOverdue() ? 'text-rose-600' : ($task->deadline->isToday() ? 'text-rose-600' : ($task->deadline->isTomorrow() ? 'text-amber-700' : 'text-slate-700')) }}">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 shrink-0
                                            {{ $task->isOverdue() ? 'text-rose-500' : ($task->deadline->isToday() ? 'text-rose-400' : ($task->deadline->isTomorrow() ? 'text-amber-500' : 'text-slate-400')) }}"></i>
                                        <span>{{ $task->due_label }}</span>
                                    </div>
                                    {{-- Raw date/time for reference --}}
                                    <div class="text-[11px] text-slate-400 pl-4.5">{{ $task->deadline->format('d M Y, h:i A') }}</div>
                                    @if($task->isReassigned() && $task->previous_deadline)
                                        <div class="text-[10px] text-slate-400 line-through pl-4.5">
                                            Prev: {{ $task->previous_deadline->format('d M, h:i A') }}
                                        </div>
                                    @endif
                                    @if($task->isOverdue())
                                        <span class="inline-block mt-0.5 text-[9px] font-black uppercase text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded">Overdue</span>
                                    @elseif($task->deadline->isToday())
                                        <span class="inline-block mt-0.5 text-[9px] font-black uppercase text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded">Due Today</span>
                                    @elseif($task->deadline->isTomorrow())
                                        <span class="inline-block mt-0.5 text-[9px] font-black uppercase text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded">Due Tomorrow</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-4 px-4 align-top">
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
                                        <i data-lucide="loader" class="w-3 h-3"></i> In Progress
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300 inline-flex items-center gap-1">
                                        <i data-lucide="hourglass" class="w-3 h-3"></i> Pending
                                    </span>
                                @endif
                            </td>

                            <!-- Updates column -->
                            <td class="py-4 px-4 align-top">
                                <div class="space-y-1 mb-2">
                                    @forelse($task->updates->take(2) as $update)
                                        <div class="text-[11px] text-slate-600 flex items-start gap-1">
                                            <span class="text-slate-400">&bull;</span>
                                            <span class="truncate">{{ $update->message }}</span>
                                        </div>
                                    @empty
                                        <span class="text-[11px] text-slate-400 italic">No notes posted yet</span>
                                    @endforelse
                                </div>

                                @if(!in_array($task->status, ['submitted', 'completed']))
                                    <details class="group">
                                        <summary class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 cursor-pointer flex items-center gap-1 select-none">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            <span>Add Progress Note</span>
                                        </summary>
                                        <form method="POST" action="{{ route('tasks.update.add', $task) }}" class="mt-2 space-y-1.5">
                                            @csrf @method('PUT')
                                            <input type="text" name="message" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="What progress did you make?" required>
                                            <button type="submit" class="w-full py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[10px] rounded-md transition shadow-xs">
                                                Save Note
                                            </button>
                                        </form>
                                    </details>
                                @endif
                            </td>

                            <td class="py-4 px-4 align-top text-right">
                                @if(!in_array($task->status, ['submitted', 'completed']))
                                    <button type="button" onclick="openSubmissionModal({{ $task->id }}, '{{ addslashes($task->title) }}')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold text-xs transition shadow-xs flex items-center gap-1.5 ml-auto cursor-pointer">
                                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                        <span>Submit Work</span>
                                    </button>
                                @elseif($task->status === 'submitted')
                                    <span class="text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-200">
                                        Under TL Review
                                    </span>
                                @else
                                    <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                                        Approved
                                    </span>
                                @endif
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

        <form id="submissionForm" method="POST" action="" enctype="multipart/form-data" class="space-y-4">
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
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-md shadow-indigo-600/20 flex items-center gap-1.5">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Submit to Team Lead</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openSubmissionModal(taskId, taskTitle) {
    document.getElementById('modalTaskTitle').innerText = taskTitle;
    document.getElementById('submissionForm').action = '/tasks/' + taskId + '/submit';
    document.getElementById('submissionModal').classList.remove('hidden');
}

function closeSubmissionModal() {
    document.getElementById('submissionModal').classList.add('hidden');
}
</script>
@endpush

@endsection