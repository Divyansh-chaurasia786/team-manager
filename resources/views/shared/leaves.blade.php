@extends('layouts.app')
@section('title', 'Leave Management - EcoFone')
@section('page-title', 'Leave & Time-Off')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-28 lg:pb-16" x-data="{ 
    showApplyModal: false,
    selectedFilter: 'all',
    activeLeaveType: 'casual',
    startDate: '{{ now()->format('Y-m-d') }}',
    endDate: '{{ now()->format('Y-m-d') }}',
    get totalDays() {
        if (!this.startDate || !this.endDate) return 1;
        const s = new Date(this.startDate);
        const e = new Date(this.endDate);
        if (e < s) return 0;
        const diffTime = Math.abs(e - s);
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    }
}">

    <!-- =================================================================== -->
    <!-- HEADER BAR: TITLE, CONTEXT & ACTION BUTTON                          -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-5 sm:p-7 border border-slate-200/90 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shrink-0 shadow-2xs">
                <i data-lucide="calendar-days" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-lg border border-indigo-200/80">
                        {{ auth()->user()->role === 'member' ? 'Staff Portal' : 'Leadership Desk' }}
                    </span>
                    <span class="text-xs text-slate-400">&bull;</span>
                    <span class="text-xs font-bold text-slate-500">Attendance Sync Enabled</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-0.5">
                    {{ auth()->user()->role === 'member' ? 'My Leave Applications' : 'Team Leave Applications & Reviews' }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ auth()->user()->role === 'member' ? 'Request time-off, track manager approval status, and view your remaining balance.' : 'Review team time-off requests with reasons and automatically sync attendance.' }}
                </p>
            </div>
        </div>

        <div>
            <button type="button" 
                    @click="showApplyModal = true; $nextTick(() => lucide.createIcons())" 
                    class="w-full sm:w-auto px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs font-black rounded-xl shadow-md shadow-indigo-600/25 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer border border-indigo-400/30">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Apply for Leave</span>
            </button>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MEMBER LEAVE BALANCE QUOTAS                                         -->
    <!-- =================================================================== -->
    @php
        $casualUsed = $myLeaves->where('leave_type', 'casual')->where('status', 'approved')->sum('total_days');
        $sickUsed = $myLeaves->where('leave_type', 'sick')->where('status', 'approved')->sum('total_days');
        $emergencyUsed = $myLeaves->where('leave_type', 'emergency')->where('status', 'approved')->sum('total_days');
        $privilegeUsed = $myLeaves->where('leave_type', 'privilege')->where('status', 'approved')->sum('total_days');

        $quotas = [
            [
                'type' => 'casual',
                'label' => 'Casual Leave',
                'icon' => 'sun',
                'used' => $casualUsed,
                'total' => 12,
                'color' => 'amber',
                'desc' => 'Personal & planned time off'
            ],
            [
                'type' => 'sick',
                'label' => 'Sick Leave',
                'icon' => 'heart-pulse',
                'used' => $sickUsed,
                'total' => 8,
                'color' => 'rose',
                'desc' => 'Medical recovery & care'
            ],
            [
                'type' => 'emergency',
                'label' => 'Emergency Leave',
                'icon' => 'alert-triangle',
                'used' => $emergencyUsed,
                'total' => 5,
                'color' => 'orange',
                'desc' => 'Urgent family / unexpected events'
            ],
            [
                'type' => 'privilege',
                'label' => 'Privilege Leave',
                'icon' => 'plane',
                'used' => $privilegeUsed,
                'total' => 15,
                'color' => 'indigo',
                'desc' => 'Annual earned vacation days'
            ],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        @foreach($quotas as $q)
            @php
                $remaining = max(0, $q['total'] - $q['used']);
            @endphp
            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/90 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block truncate">
                        {{ $q['label'] }}
                    </span>
                    <div class="w-7 h-7 rounded-xl bg-{{ $q['color'] }}-50 text-{{ $q['color'] }}-600 flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $q['icon'] }}" class="w-3.5 h-3.5"></i>
                    </div>
                </div>

                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-black text-slate-900 leading-none">{{ $remaining }}</span>
                    <span class="text-xs font-bold text-slate-400">/ {{ $q['total'] }} left</span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-{{ $q['color'] }}-500 h-full rounded-full transition-all duration-300" style="width: {{ min(100, round(($q['used'] / $q['total']) * 100)) }}%"></div>
                </div>

                <div class="text-[10px] text-slate-400 font-medium truncate">
                    {{ $q['used'] }} used &bull; {{ $q['desc'] }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- =================================================================== -->
    <!-- REVIEWER SECTION (TL, HR, CEO): PENDING REVIEWS                      -->
    <!-- =================================================================== -->
    @if(auth()->user()->role !== 'member')
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-sm">Awaiting Your Review ({{ $pendingLeaves->count() }})</h3>
                        <p class="text-[11px] text-slate-400">Team requests requiring your approval or feedback</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($pendingLeaves as $leave)
                    <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/60 hover:bg-slate-50 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-2 min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-black text-slate-900 text-sm">{{ $leave->user->name }}</span>
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ $leave->leave_type_label }}
                                </span>
                                <span class="text-xs font-bold text-slate-500 bg-white px-2 py-0.5 rounded-lg border border-slate-200/70">
                                    📅 {{ $leave->start_date->format('d M Y') }} &rarr; {{ $leave->end_date->format('d M Y') }} ({{ $leave->total_days }} day(s))
                                </span>
                            </div>
                            <div class="text-xs text-slate-700 bg-white p-3 rounded-xl border border-slate-200/80 leading-relaxed shadow-2xs">
                                <strong class="text-slate-900 block text-[10px] uppercase tracking-wider mb-0.5 text-slate-400">Reason for Request:</strong>
                                "{{ $leave->reason }}"
                            </div>
                            <div class="text-[10px] text-slate-400 flex items-center gap-1">
                                <i data-lucide="clock" class="w-3 h-3"></i>
                                <span>Submitted {{ $leave->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <!-- Action Form: Approve or Reject -->
                        <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end">
                            <form method="POST" action="{{ route('leaves.approve', $leave) }}" onsubmit="return confirm('Approve leave for {{ $leave->user->name }}? This will mark attendance as On Leave for the dates.')" class="m-0">
                                @csrf
                                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Approve</span>
                                </button>
                            </form>

                            <button type="button" onclick="document.getElementById('rejectModal-{{ $leave->id }}').classList.remove('hidden')" class="px-4 py-2 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 font-bold text-xs transition flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                <span>Reject</span>
                            </button>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div id="rejectModal-{{ $leave->id }}" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                        <div class="bg-white rounded-3xl max-w-sm w-full p-5 sm:p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto space-y-3">
                            <h4 class="text-sm font-black text-slate-900">Reject Leave Request</h4>
                            <p class="text-xs text-slate-500">Provide feedback or reason for {{ $leave->user->name }}.</p>
                            <form method="POST" action="{{ route('leaves.reject', $leave) }}" class="space-y-3">
                                @csrf
                                <textarea name="review_notes" rows="3" required class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 focus:outline-none font-medium" placeholder="Reason for rejecting this request..."></textarea>
                                <div class="flex items-center justify-end gap-2 pt-2">
                                    <button type="button" onclick="document.getElementById('rejectModal-{{ $leave->id }}').classList.add('hidden')" class="px-3.5 py-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 cursor-pointer">Cancel</button>
                                    <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer">Confirm Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-slate-400 space-y-1">
                        <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto text-emerald-500 mb-1"></i>
                        <p class="text-xs font-black text-slate-700">All caught up!</p>
                        <p class="text-[11px] text-slate-400">No pending leave requests to review right now.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Past Reviewed Leaves -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-xs font-black text-slate-700 uppercase tracking-wider">Past Reviewed Requests</h3>
                <span class="text-xs text-slate-400 font-semibold">{{ $reviewedLeaves->count() }} Total</span>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($reviewedLeaves as $rev)
                    @php $badge = $rev->status_badge; @endphp
                    <div class="p-4 sm:p-5 hover:bg-slate-50/70 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-black text-slate-900">{{ $rev->user->name }}</span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="font-bold text-slate-600">{{ $rev->leave_type_label }}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $badge['bg'] }} inline-flex items-center gap-1">
                                    <i data-lucide="{{ $badge['icon'] }}" class="w-3 h-3"></i>
                                    <span>{{ $badge['label'] }}</span>
                                </span>
                            </div>
                            <div class="text-slate-500 text-[11px]">
                                {{ $rev->start_date->format('d M Y') }} &rarr; {{ $rev->end_date->format('d M Y') }} ({{ $rev->total_days }} day(s)) &bull; Reason: "{{ $rev->reason }}"
                            </div>
                            @if($rev->review_notes)
                                <div class="text-[10px] text-slate-600 bg-amber-50/60 p-2 rounded-xl border border-amber-100">
                                    <strong>Note:</strong> {{ $rev->review_notes }}
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
    @endif

    <!-- =================================================================== -->
    <!-- LEAVE APPLICATIONS HISTORY LIST (MEMBER & PERSONAL VIEW)             -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden space-y-0">
        
        <!-- Tab Filter Header -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                    <i data-lucide="history" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Leave Application History</h3>
                    <p class="text-[10px] text-slate-400">Detailed records and manager review remarks</p>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                <button type="button" 
                        @click="selectedFilter = 'all'" 
                        :class="selectedFilter === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3 py-1 rounded-xl text-xs font-bold transition cursor-pointer">
                    All ({{ $myLeaves->count() }})
                </button>
                <button type="button" 
                        @click="selectedFilter = 'pending'" 
                        :class="selectedFilter === 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3 py-1 rounded-xl text-xs font-bold transition cursor-pointer">
                    Pending ({{ $myLeaves->where('status', 'pending')->count() }})
                </button>
                <button type="button" 
                        @click="selectedFilter = 'approved'" 
                        :class="selectedFilter === 'approved' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3 py-1 rounded-xl text-xs font-bold transition cursor-pointer">
                    Approved ({{ $myLeaves->where('status', 'approved')->count() }})
                </button>
                <button type="button" 
                        @click="selectedFilter = 'rejected'" 
                        :class="selectedFilter === 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3 py-1 rounded-xl text-xs font-bold transition cursor-pointer">
                    Rejected ({{ $myLeaves->where('status', 'rejected')->count() }})
                </button>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($myLeaves as $leave)
                @php $badge = $leave->status_badge; @endphp
                <div x-show="selectedFilter === 'all' || selectedFilter === '{{ $leave->status }}'" 
                     class="p-4 sm:p-5 hover:bg-slate-50/70 transition flex flex-col sm:flex-row sm:items-start justify-between gap-4 text-xs">
                    
                    <div class="space-y-2 min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-black text-slate-900 text-sm">{{ $leave->leave_type_label }}</span>
                            
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $badge['bg'] }} inline-flex items-center gap-1 shadow-2xs">
                                <i data-lucide="{{ $badge['icon'] }}" class="w-3 h-3"></i>
                                <span>{{ $badge['label'] }}</span>
                            </span>

                            <span class="text-slate-600 font-bold text-xs bg-slate-100 px-2.5 py-0.5 rounded-lg border border-slate-200/70 flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                                <span>{{ $leave->start_date->format('d M Y') }} &rarr; {{ $leave->end_date->format('d M Y') }} ({{ $leave->total_days }} day{{ $leave->total_days > 1 ? 's' : '' }})</span>
                            </span>
                        </div>

                        <!-- Reason -->
                        <div class="text-slate-700 text-xs bg-slate-50/90 p-3 rounded-2xl border border-slate-200/80 leading-relaxed shadow-2xs">
                            <strong class="text-slate-400 uppercase text-[10px] block mb-0.5">My Explanation:</strong>
                            "{{ $leave->reason }}"
                        </div>

                        @if($leave->review_notes)
                            <div class="text-[11px] text-slate-700 bg-amber-50/70 p-3 rounded-2xl border border-amber-200/70 space-y-0.5">
                                <strong class="text-amber-800 font-black block text-[10px] uppercase">Manager Feedback:</strong>
                                <p>{{ $leave->review_notes }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="text-left sm:text-right text-[10px] text-slate-400 shrink-0 space-y-0.5">
                        <div>Applied on {{ $leave->created_at->format('d M Y, h:i A') }}</div>
                        @if($leave->reviewed_at)
                            <div class="text-slate-500 font-semibold">Reviewed {{ $leave->reviewed_at->diffForHumans() }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-16 text-center text-slate-400 space-y-3">
                    <div class="w-14 h-14 mx-auto rounded-3xl bg-indigo-50 text-indigo-400 flex items-center justify-center">
                        <i data-lucide="calendar-check" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-800">You haven't submitted any leave requests yet.</p>
                        <p class="text-xs text-slate-400 mt-0.5 max-w-sm mx-auto">
                            Need time away for medical care, vacation, or personal matters? Click below to request time off.
                        </p>
                    </div>
                    <div>
                        <button type="button" 
                                @click="showApplyModal = true; $nextTick(() => lucide.createIcons())" 
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer inline-flex items-center gap-1.5">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Apply for Leave</span>
                        </button>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL: APPLY FOR LEAVE (INTERACTIVE & BEAUTIFUL)                     -->
    <!-- =================================================================== -->
    <div x-show="showApplyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
        <div @click.outside="showApplyModal = false" class="bg-white rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200 space-y-5">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                        <i data-lucide="calendar-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Apply for Time-Off</h3>
                        <p class="text-xs text-slate-400">Select your leave category and target dates</p>
                    </div>
                </div>
                <button @click="showApplyModal = false" type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('leaves.store') }}" class="space-y-4">
                @csrf

                <!-- Leave Type Selector Tiles -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">
                        1. Select Leave Type <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="p-3 rounded-2xl border transition cursor-pointer flex flex-col justify-between"
                               :class="activeLeaveType === 'casual' ? 'bg-amber-50 border-amber-300 ring-2 ring-amber-400/20' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                            <input type="radio" name="leave_type" value="casual" x-model="activeLeaveType" class="sr-only">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-black text-slate-900">Casual Leave</span>
                                <i data-lucide="sun" class="w-4 h-4 text-amber-500"></i>
                            </div>
                            <span class="text-[10px] text-slate-400 leading-tight">Personal &amp; planned</span>
                        </label>

                        <label class="p-3 rounded-2xl border transition cursor-pointer flex flex-col justify-between"
                               :class="activeLeaveType === 'sick' ? 'bg-rose-50 border-rose-300 ring-2 ring-rose-400/20' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                            <input type="radio" name="leave_type" value="sick" x-model="activeLeaveType" class="sr-only">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-black text-slate-900">Sick Leave</span>
                                <i data-lucide="heart-pulse" class="w-4 h-4 text-rose-500"></i>
                            </div>
                            <span class="text-[10px] text-slate-400 leading-tight">Medical recovery</span>
                        </label>

                        <label class="p-3 rounded-2xl border transition cursor-pointer flex flex-col justify-between"
                               :class="activeLeaveType === 'emergency' ? 'bg-orange-50 border-orange-300 ring-2 ring-orange-400/20' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                            <input type="radio" name="leave_type" value="emergency" x-model="activeLeaveType" class="sr-only">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-black text-slate-900">Emergency</span>
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-orange-500"></i>
                            </div>
                            <span class="text-[10px] text-slate-400 leading-tight">Urgent situations</span>
                        </label>

                        <label class="p-3 rounded-2xl border transition cursor-pointer flex flex-col justify-between"
                               :class="activeLeaveType === 'privilege' ? 'bg-indigo-50 border-indigo-300 ring-2 ring-indigo-400/20' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                            <input type="radio" name="leave_type" value="privilege" x-model="activeLeaveType" class="sr-only">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-black text-slate-900">Privilege</span>
                                <i data-lucide="plane" class="w-4 h-4 text-indigo-600"></i>
                            </div>
                            <span class="text-[10px] text-slate-400 leading-tight">Earned vacation</span>
                        </label>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-wider">
                            2. Date Duration <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[11px] font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-200/80">
                            Total: <span x-text="totalDays">1</span> Day(s)
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">From Date</label>
                            <input type="date" 
                                   name="start_date" 
                                   x-model="startDate"
                                   min="{{ now()->format('Y-m-d') }}" 
                                   class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-semibold bg-slate-50" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">To Date</label>
                            <input type="date" 
                                   name="end_date" 
                                   x-model="endDate"
                                   :min="startDate" 
                                   class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-semibold bg-slate-50" 
                                   required>
                        </div>
                    </div>
                </div>

                <!-- Reason for Leave -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                        3. Detailed Reason <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="reason" 
                              rows="3" 
                              required 
                              minlength="10" 
                              class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-medium leading-relaxed bg-slate-50" 
                              placeholder="Please share detailed context regarding your time-off request for management approval..."></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Minimum 10 characters required.</p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" 
                            @click="showApplyModal = false" 
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white rounded-xl text-xs font-black transition shadow-md shadow-indigo-600/25 flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        <span>Submit Application</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection