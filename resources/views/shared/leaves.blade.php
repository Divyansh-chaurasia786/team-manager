@extends('layouts.app')
@section('title', 'Leave Management')
@section('content')

<div class="space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                {{ auth()->user()->role === 'member' ? 'My Leave Applications' : 'Team Leave Applications & Reviews' }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                {{ auth()->user()->role === 'member' ? 'Apply for time-off with reasons and track review status' : 'Review team time-off requests with reasons and automatically sync attendance' }}
            </p>
        </div>

        @if(auth()->user()->role === 'member')
            <button type="button" onclick="document.getElementById('applyLeaveModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer border border-indigo-400/30">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Apply for Leave</span>
            </button>
        @endif
    </div>

    @if(auth()->user()->role !== 'member')
        <!-- REVIEWER VIEW (TL, HR, CEO): PENDING REVIEWS -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Awaiting Your Review ({{ $pendingLeaves->count() }})</h3>
                        <p class="text-[11px] text-slate-400">Team requests requiring your approval or rejection</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($pendingLeaves as $leave)
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1.5 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-slate-900 text-sm">{{ $leave->user->name }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ $leave->leave_type_label }}
                                </span>
                                <span class="text-xs font-bold text-slate-500">
                                    📅 {{ $leave->start_date->format('d M Y') }} &rarr; {{ $leave->end_date->format('d M Y') }} ({{ $leave->total_days }} day(s))
                                </span>
                            </div>
                            <div class="text-xs text-slate-700 bg-white p-3 rounded-lg border border-slate-200/80 leading-relaxed">
                                <strong class="text-slate-900 block text-[10px] uppercase tracking-wider mb-0.5">Reason:</strong>
                                {{ $leave->reason }}
                            </div>
                            <div class="text-[10px] text-slate-400">
                                Submitted {{ $leave->created_at->diffForHumans() }}
                            </div>
                        </div>

                        <!-- Action Form: Approve or Reject -->
                        <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end">
                            <!-- Approve -->
                            <form method="POST" action="{{ route('leaves.approve', $leave) }}" onsubmit="return confirm('Approve leave for {{ $leave->user->name }}? This will mark attendance as On Leave for the dates.')" class="m-0">
                                @csrf
                                <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition flex items-center gap-1 cursor-pointer">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Approve</span>
                                </button>
                            </form>

                            <!-- Reject Toggle Modal Button -->
                            <button type="button" onclick="document.getElementById('rejectModal-{{ $leave->id }}').classList.remove('hidden')" class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 font-bold text-xs transition flex items-center gap-1 cursor-pointer">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                <span>Reject</span>
                            </button>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div id="rejectModal-{{ $leave->id }}" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                        <div class="bg-white rounded-2xl max-w-sm w-full p-4 sm:p-5 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
                            <h4 class="text-sm font-bold text-slate-900 mb-2">Reject Leave Request</h4>
                            <p class="text-xs text-slate-500 mb-3">Provide a clear note or feedback for {{ $leave->user->name }}.</p>
                            <form method="POST" action="{{ route('leaves.reject', $leave) }}" class="space-y-3">
                                @csrf
                                <textarea name="review_notes" rows="3" required class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 focus:outline-none" placeholder="Reason for rejecting this request..."></textarea>
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" onclick="document.getElementById('rejectModal-{{ $leave->id }}').classList.add('hidden')" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:text-slate-800">Cancel</button>
                                    <button type="submit" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold shadow-xs">Confirm Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-slate-400">
                        <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto text-emerald-400 mb-1"></i>
                        <p class="text-xs font-bold text-slate-600">All caught up! No pending leave requests to review.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Reviewed Leaves History -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Past Reviewed Leave Requests</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($reviewedLeaves as $rev)
                    @php $badge = $rev->status_badge; @endphp
                    <div class="p-4 hover:bg-slate-50/70 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-slate-900">{{ $rev->user->name }}</span>
                                <span class="text-slate-400">&bull;</span>
                                <span class="font-semibold text-slate-600">{{ $rev->leave_type_label }}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $badge['bg'] }} inline-flex items-center gap-1">
                                    <i data-lucide="{{ $badge['icon'] }}" class="w-3 h-3"></i>
                                    <span>{{ $badge['label'] }}</span>
                                </span>
                            </div>
                            <div class="text-slate-500 text-[11px]">
                                {{ $rev->start_date->format('d M Y') }} &rarr; {{ $rev->end_date->format('d M Y') }} ({{ $rev->total_days }} day(s)) &bull; Reason: "{{ $rev->reason }}"
                            </div>
                            @if($rev->review_notes)
                                <div class="text-[10px] text-slate-600 italic">
                                    TL Note: {{ $rev->review_notes }}
                                </div>
                            @endif
                        </div>
                        <div class="text-slate-400 text-[10px] font-mono shrink-0">
                            Reviewed {{ $rev->reviewed_at ? $rev->reviewed_at->format('d M Y') : '—' }}
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        No reviewed leaves recorded yet.
                    </div>
                @endforelse
            </div>
        </div>

    @else
        <!-- MEMBER VIEW: MY LEAVES -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">My Leave History</span>
                <span class="text-xs text-slate-400 font-semibold">{{ $myLeaves->count() }} Applications</span>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($myLeaves as $leave)
                    @php $badge = $leave->status_badge; @endphp
                    <div class="p-4 hover:bg-slate-50/70 transition flex flex-col sm:flex-row sm:items-start justify-between gap-3 text-xs">
                        <div class="space-y-1.5 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-slate-900 text-sm">{{ $leave->leave_type_label }}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $badge['bg'] }} inline-flex items-center gap-1">
                                    <i data-lucide="{{ $badge['icon'] }}" class="w-3 h-3"></i>
                                    <span>{{ $badge['label'] }}</span>
                                </span>
                                <span class="text-slate-500 font-semibold text-xs">
                                    {{ $leave->start_date->format('d M Y') }} &rarr; {{ $leave->end_date->format('d M Y') }} ({{ $leave->total_days }} days)
                                </span>
                            </div>
                            <div class="text-slate-700 text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                <strong>My Reason:</strong> {{ $leave->reason }}
                            </div>
                            @if($leave->review_notes)
                                <div class="text-[11px] text-slate-600 bg-amber-50/50 p-2 rounded border border-amber-100">
                                    <strong>TL Feedback:</strong> {{ $leave->review_notes }}
                                </div>
                            @endif
                        </div>

                        <div class="text-right text-[10px] text-slate-400 shrink-0">
                            Applied {{ $leave->created_at->format('d M Y') }}
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-400">
                        <i data-lucide="calendar" class="w-10 h-10 mx-auto text-slate-300 mb-2"></i>
                        <p class="text-xs font-bold text-slate-600">You haven't submitted any leave requests yet.</p>
                        <p class="text-[11px] text-slate-400 mt-1">Need time off? Click "Apply for Leave" above.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Apply Leave Modal for Members -->
        <div id="applyLeaveModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-4 sm:p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="font-bold text-slate-900 text-base">Apply for Leave</h3>
                    <button type="button" onclick="document.getElementById('applyLeaveModal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form method="POST" action="{{ route('leaves.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Leave Type</label>
                        <select name="leave_type" class="w-full px-3 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                            <option value="casual">Casual Leave</option>
                            <option value="sick">Sick Leave</option>
                            <option value="emergency">Emergency Leave</option>
                            <option value="privilege">Privilege Leave</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">From Date</label>
                            <input type="date" name="start_date" min="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">To Date</label>
                            <input type="date" name="end_date" min="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Reason for Leave</label>
                        <textarea name="reason" rows="3" required minlength="10" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Provide a detailed explanation for your time-off request..."></textarea>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button" onclick="document.getElementById('applyLeaveModal').classList.add('hidden')" class="px-3 py-2 text-xs font-bold text-slate-500 hover:text-slate-800">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-xs">Submit Application</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>

@endsection