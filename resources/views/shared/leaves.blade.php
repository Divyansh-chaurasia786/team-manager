@extends('layouts.app')
@section('title', 'Leave Management - EcoFone')
@section('page-title', 'Leave & Time-Off')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-28 lg:pb-16" x-data="{ 
    showApplyModal: false,
    showQuotaModal: false,
    activeReviewModal: null,
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
    <!-- HEADER BAR: TITLE, CONTEXT & ACTION CONTROLS                        -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-5 sm:p-7 border border-slate-200/90 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shrink-0 shadow-2xs">
                <i data-lucide="calendar-days" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-lg border border-indigo-200/80">
                        {{ auth()->user()->role === 'member' ? 'Staff Portal' : (auth()->user()->isTL() ? 'Team Lead Desk' : (auth()->user()->isHR() ? 'HR Portal' : 'Executive CEO Desk')) }}
                    </span>
                    <span class="text-xs text-slate-400">&bull;</span>
                    <span class="text-xs font-bold text-slate-500">Attendance Auto-Sync</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-0.5">
                    {{ auth()->user()->role === 'member' ? 'Leave & Absence Portal' : 'Team Leave Management & Approvals' }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ auth()->user()->role === 'member' ? 'View your remaining balance, review company policies, and track approvals.' : 'Authorize time-off according to hierarchy, plan quotas, and sync team attendance.' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <!-- TL / Leadership Leave Application Toggle -->
            @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('leaves.toggle') }}" class="m-0">
                    @csrf
                    @if(auth()->user()->isTL())
                        <input type="hidden" name="tl_id" value="{{ auth()->id() }}">
                    @endif
                    <button type="submit" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition border flex items-center gap-2 cursor-pointer shadow-2xs {{ $isLeaveEnabled ? 'bg-emerald-50 text-emerald-800 border-emerald-300 hover:bg-emerald-100' : 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100' }}"
                            title="Toggle whether employees can apply for leaves">
                        <span class="w-2 h-2 rounded-full {{ $isLeaveEnabled ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                        <span>Employee Applications: <strong>{{ $isLeaveEnabled ? 'ON' : 'OFF' }}</strong></span>
                    </button>
                </form>
            @endif

            <!-- HR Quota Management Modal Trigger -->
            @if(auth()->user()->isHR() || auth()->user()->isCEO())
                <button type="button" 
                        @click="showQuotaModal = true; $nextTick(() => lucide.createIcons())" 
                        class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer border border-slate-200">
                    <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                    <span>Plan Quotas</span>
                </button>
            @endif

            <!-- Apply for Leave Button -->
            @if(auth()->user()->role !== 'member' || $isLeaveEnabled)
                <button type="button" 
                        @click="showApplyModal = true; $nextTick(() => lucide.createIcons())" 
                        class="w-full sm:w-auto px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs font-black rounded-xl shadow-md shadow-indigo-600/25 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer border border-indigo-400/30">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Apply for Leave</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Notice if leave applications are toggled OFF for members -->
    @if(auth()->user()->role === 'member' && !$isLeaveEnabled)
        <div class="p-4 sm:p-5 bg-amber-50/80 border border-amber-200 rounded-3xl flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
            </div>
            <div class="text-xs">
                <h4 class="font-black text-amber-900 text-sm">Leave Applications Are Currently Closed</h4>
                <p class="text-amber-800/90 mt-0.5 leading-relaxed">
                    Team Leadership has temporarily paused new leave applications. The application card is closed. If you have an unforeseen emergency or medical need, please reach out directly to your Team Lead or HR.
                </p>
            </div>
        </div>
    @endif

    <!-- =================================================================== -->
    <!-- LEAVE BALANCE QUOTAS (PERSONAL OVERVIEW WITH NEGATIVE BALANCE)      -->
    <!-- =================================================================== -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <span>My Leave Quota Overview ({{ $year }})</span>
                @if($leaveSummary['is_negative'])
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-200">
                        Negative Balance (Overdraft)
                    </span>
                @endif
            </h3>
            <div class="text-xs font-bold text-slate-500">
                Total Available: 
                <strong class="{{ $leaveSummary['is_negative'] ? 'text-rose-600' : 'text-emerald-600' }} text-sm font-black">
                    {{ $leaveSummary['total_remaining'] }} Days
                </strong>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Casual Leave -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/90 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block truncate">Casual Leave</span>
                    <div class="w-7 h-7 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <i data-lucide="sun" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-black {{ $leaveSummary['casual_remaining'] < 0 ? 'text-rose-600' : 'text-slate-900' }} leading-none">
                        {{ $leaveSummary['casual_remaining'] }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">/ {{ $leaveSummary['casual_quota'] }} left</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-amber-500 h-full rounded-full transition-all duration-300" 
                         style="width: {{ $leaveSummary['casual_quota'] > 0 ? min(100, round(($leaveSummary['casual_used'] / $leaveSummary['casual_quota']) * 100)) : 0 }}%"></div>
                </div>
                <div class="text-[10px] text-slate-400 font-medium truncate">
                    {{ $leaveSummary['casual_used'] }} used &bull; Personal &amp; planned time off
                </div>
            </div>

            <!-- Sick Leave -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/90 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block truncate">Sick Leave</span>
                    <div class="w-7 h-7 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <i data-lucide="heart-pulse" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-black {{ $leaveSummary['sick_remaining'] < 0 ? 'text-rose-600' : 'text-slate-900' }} leading-none">
                        {{ $leaveSummary['sick_remaining'] }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">/ {{ $leaveSummary['sick_quota'] }} left</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-rose-500 h-full rounded-full transition-all duration-300" 
                         style="width: {{ $leaveSummary['sick_quota'] > 0 ? min(100, round(($leaveSummary['sick_used'] / $leaveSummary['sick_quota']) * 100)) : 0 }}%"></div>
                </div>
                <div class="text-[10px] text-slate-400 font-medium truncate">
                    {{ $leaveSummary['sick_used'] }} used &bull; Medical recovery &amp; appointments
                </div>
            </div>

            <!-- Emergency Leave -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/90 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block truncate">Emergency Leave</span>
                    <div class="w-7 h-7 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-black {{ $leaveSummary['emergency_remaining'] < 0 ? 'text-rose-600' : 'text-slate-900' }} leading-none">
                        {{ $leaveSummary['emergency_remaining'] }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">/ {{ $leaveSummary['emergency_quota'] }} left</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-orange-500 h-full rounded-full transition-all duration-300" 
                         style="width: {{ $leaveSummary['emergency_quota'] > 0 ? min(100, round(($leaveSummary['emergency_used'] / $leaveSummary['emergency_quota']) * 100)) : 0 }}%"></div>
                </div>
                <div class="text-[10px] text-slate-400 font-medium truncate">
                    {{ $leaveSummary['emergency_used'] }} used &bull; Urgent family events
                </div>
            </div>

            <!-- Privilege / Vacation Leave -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/90 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block truncate">Privilege Leave</span>
                    <div class="w-7 h-7 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <i data-lucide="plane" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl sm:text-3xl font-black {{ $leaveSummary['privilege_remaining'] < 0 ? 'text-rose-600' : 'text-slate-900' }} leading-none">
                        {{ $leaveSummary['privilege_remaining'] }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">/ {{ $leaveSummary['privilege_quota'] }} left</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-indigo-500 h-full rounded-full transition-all duration-300" 
                         style="width: {{ $leaveSummary['privilege_quota'] > 0 ? min(100, round(($leaveSummary['privilege_used'] / $leaveSummary['privilege_quota']) * 100)) : 0 }}%"></div>
                </div>
                <div class="text-[10px] text-slate-400 font-medium truncate">
                    {{ $leaveSummary['privilege_used'] }} used &bull; Annual earned vacation days
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- REVIEWER SECTION (TL, HR, CEO): PENDING REVIEWS                      -->
    <!-- =================================================================== -->
    @if(auth()->user()->isAdmin())
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-sm">Pending Leave Requests ({{ $pendingLeaves->count() }})</h3>
                        <p class="text-[11px] text-slate-400">
                            @if(auth()->user()->isTL())
                                Team Lead Audit View &bull; Approvals authorized by HR &bull; TLs cannot approve
                            @elseif(auth()->user()->isHR())
                                HR Evaluation Desk &bull; Balance check enforced &bull; Insufficient balance requires CEO grant
                            @else
                                Executive CEO Desk &bull; Full approval and negative balance grant authority
                            @endif
                        </p>
                    </div>
                </div>

                @if(auth()->user()->isTL())
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        View Only (Hierarchy: HR Approves)
                    </span>
                @endif
            </div>

            <div class="space-y-3">
                @forelse($pendingLeaves as $leave)
                    @php
                        $applicantSummary = $leave->user ? $leave->user->getLeaveSummary() : null;
                        $remaining = $applicantSummary ? $applicantSummary['total_remaining'] : 0;
                        $hasSufficientBalance = $applicantSummary && ($remaining >= $leave->total_days);
                    @endphp
                    <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/60 hover:bg-slate-50 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-2 min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-black text-slate-900 text-sm">{{ $leave->user->name }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase bg-slate-100 px-1.5 py-0.2 rounded">
                                    {{ $leave->user->role }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ $leave->leave_type_label }}
                                </span>
                                <span class="text-xs font-bold text-slate-500 bg-white px-2 py-0.5 rounded-lg border border-slate-200/70">
                                    📅 {{ $leave->start_date->format('d M Y') }} &rarr; {{ $leave->end_date->format('d M Y') }} ({{ $leave->total_days }} day(s))
                                </span>

                                <!-- Balance Status Badge -->
                                @if($hasSufficientBalance)
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Balance Available ({{ $remaining }} days left)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200">
                                        Insufficient Balance (Only {{ $remaining }} day(s) left)
                                    </span>
                                @endif
                            </div>

                            <div class="text-xs text-slate-700 bg-white p-3 rounded-xl border border-slate-200/80 leading-relaxed shadow-2xs">
                                <strong class="text-slate-900 block text-[10px] uppercase tracking-wider mb-0.5 text-slate-400">Reason for Request:</strong>
                                "{{ $leave->reason }}"
                            </div>

                            <div class="text-[10px] text-slate-400 flex items-center gap-2">
                                <span>Submitted {{ $leave->created_at->diffForHumans() }}</span>
                                <span>&bull;</span>
                                <span>Casual left: {{ $applicantSummary['casual_remaining'] ?? 0 }}</span>
                                <span>&bull;</span>
                                <span>Sick left: {{ $applicantSummary['sick_remaining'] ?? 0 }}</span>
                            </div>
                        </div>

                        <!-- Review Action Buttons / Hierarchy -->
                        <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end">
                            @if(auth()->user()->isTL())
                                <!-- TL cannot approve; display status -->
                                <div class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold border border-slate-200 flex items-center gap-1.5">
                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Awaiting HR Review</span>
                                </div>
                            @elseif(auth()->user()->isHR())
                                <!-- HR Review Modal Trigger -->
                                @if($hasSufficientBalance)
                                    <button type="button" 
                                            @click="activeReviewModal = {{ $leave->id }}" 
                                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        <span>Review &amp; Approve</span>
                                    </button>
                                @else
                                    <button type="button" 
                                            disabled 
                                            class="px-3 py-2 rounded-xl bg-slate-100 text-slate-400 font-bold text-xs border border-slate-200 cursor-not-allowed flex items-center gap-1.5" 
                                            title="No leave balance available. Only the CEO has authority to grant leave without balance.">
                                        <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-rose-500"></i>
                                        <span>Needs CEO Grant</span>
                                    </button>
                                @endif

                                <button type="button" 
                                        onclick="document.getElementById('rejectModal-{{ $leave->id }}').classList.remove('hidden')" 
                                        class="px-4 py-2 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 font-bold text-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    <span>Reject</span>
                                </button>
                            @elseif(auth()->user()->isCEO())
                                <!-- CEO Approval / Negative Balance Grant -->
                                <button type="button" 
                                        @click="activeReviewModal = {{ $leave->id }}" 
                                        class="px-4 py-2 rounded-xl {{ $hasSufficientBalance ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-violet-600 hover:bg-violet-700' }} text-white font-black text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="{{ $hasSufficientBalance ? 'check' : 'shield-check' }}" class="w-3.5 h-3.5"></i>
                                    <span>{{ $hasSufficientBalance ? 'Approve' : 'CEO Grant (Negative)' }}</span>
                                </button>

                                <button type="button" 
                                        onclick="document.getElementById('rejectModal-{{ $leave->id }}').classList.remove('hidden')" 
                                        class="px-4 py-2 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 font-bold text-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    <span>Reject</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Approve / Review Modal (HR & CEO) -->
                    @if(!auth()->user()->isTL())
                        <div x-show="activeReviewModal === {{ $leave->id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
                            <div @click.outside="activeReviewModal = null" class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                    <h4 class="text-sm font-black text-slate-900">Approve Leave: {{ $leave->user->name }}</h4>
                                    <button type="button" @click="activeReviewModal = null" class="text-slate-400 hover:text-slate-600">✕</button>
                                </div>

                                <form method="POST" action="{{ route('leaves.approve', $leave) }}" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wider mb-1">
                                            Confirm / Switch Leave Category
                                        </label>
                                        <select name="approved_leave_type" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50 font-semibold focus:ring-2 focus:ring-indigo-500">
                                            <option value="casual" {{ $leave->leave_type === 'casual' ? 'selected' : '' }}>Casual Leave</option>
                                            <option value="sick" {{ $leave->leave_type === 'sick' ? 'selected' : '' }}>Sick Leave</option>
                                            <option value="emergency" {{ $leave->leave_type === 'emergency' ? 'selected' : '' }}>Emergency Leave</option>
                                            <option value="privilege" {{ $leave->leave_type === 'privilege' ? 'selected' : '' }}>Privilege Leave</option>
                                        </select>
                                    </div>

                                    @if(!$hasSufficientBalance && auth()->user()->isCEO())
                                        <div class="p-3 bg-violet-50 border border-violet-200 rounded-xl space-y-1 text-xs">
                                            <label class="flex items-start gap-2 font-bold text-violet-900 cursor-pointer">
                                                <input type="checkbox" name="grant_without_balance" value="1" checked class="mt-0.5 text-violet-600 rounded">
                                                <span>CEO Executive Exception: Grant without balance (Account will reflect negative balance)</span>
                                            </label>
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wider mb-1">
                                            Optional Remarks / Feedback
                                        </label>
                                        <textarea name="review_notes" rows="3" class="w-full text-xs p-3 rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-indigo-500 font-medium" placeholder="Add approval remarks or instructions for the employee..."></textarea>
                                    </div>

                                    <div class="pt-2 flex items-center justify-end gap-2">
                                        <button type="button" @click="activeReviewModal = null" class="px-3.5 py-1.5 text-xs font-bold text-slate-500 hover:text-slate-800">Cancel</button>
                                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-xs">Confirm Approval &amp; Sync Attendance</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

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

        <!-- Past Reviewed Leaves (Audit Trail) -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-xs font-black text-slate-700 uppercase tracking-wider">Past Reviewed Requests (Audit Trail)</h3>
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
                                    <strong>Supervisor Remarks:</strong> {{ $rev->review_notes }}
                                </div>
                            @endif
                        </div>
                        <div class="text-left sm:text-right text-slate-400 text-[10px] font-mono shrink-0 space-y-0.5">
                            <div>Reviewed by: <strong>{{ $rev->reviewer?->name ?? 'Leadership Desk' }}</strong> ({{ strtoupper($rev->reviewer?->role ?? 'HR') }})</div>
                            <div>{{ $rev->reviewed_at ? $rev->reviewed_at->format('d M Y, h:i A') : '—' }}</div>
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
    <!-- LEAVE APPLICATIONS HISTORY LIST (PERSONAL VIEW)                      -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden space-y-0">
        
        <!-- Tab Filter Header -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                    <i data-lucide="history" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">My Leave Applications</h3>
                    <p class="text-[10px] text-slate-400">Track your requests, review status, and supervisor notes</p>
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
                            <div class="text-slate-600 font-semibold">
                                Reviewed by {{ $leave->reviewer?->name ?? 'Management' }} ({{ strtoupper($leave->reviewer?->role ?? 'HR') }})
                            </div>
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
                            Need time away for medical recovery, personal matters, or vacation? Click below to apply.
                        </p>
                    </div>
                    @if(auth()->user()->role !== 'member' || $isLeaveEnabled)
                        <div>
                            <button type="button" 
                                    @click="showApplyModal = true; $nextTick(() => lucide.createIcons())" 
                                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer inline-flex items-center gap-1.5">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Apply for Leave</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL: HR / CEO QUOTA MANAGEMENT & PLANNING DESK                    -->
    <!-- =================================================================== -->
    @if(auth()->user()->isHR() || auth()->user()->isCEO())
        <div x-show="showQuotaModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            <div @click.outside="showQuotaModal = false" class="bg-white rounded-3xl max-w-3xl w-full p-5 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                            <i data-lucide="sliders" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900">Manage &amp; Plan Leave Quotas</h3>
                            <p class="text-xs text-slate-400">Set annual allowances and manage balances for staff ({{ $year }})</p>
                        </div>
                    </div>
                    <button @click="showQuotaModal = false" type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="space-y-4">
                    @forelse($allStaffSummaries as $item)
                        @php $staff = $item['user']; @endphp
                        <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-black text-slate-900 text-sm">{{ $staff->name }}</h4>
                                    <span class="text-[10px] text-slate-400">{{ strtoupper($staff->role) }} &bull; {{ $staff->email }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-slate-500">Total Remaining:</span>
                                    <strong class="{{ $item['is_negative'] ? 'text-rose-600' : 'text-emerald-600' }} text-sm font-black">
                                        {{ $item['total_remaining'] }} Days
                                    </strong>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('leaves.quotas.update') }}" class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 items-end">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $staff->id }}">
                                <input type="hidden" name="year" value="{{ $year }}">

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Casual</label>
                                    <input type="number" name="casual_quota" value="{{ $item['casual_quota'] }}" min="0" max="100" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-xl font-bold">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Sick</label>
                                    <input type="number" name="sick_quota" value="{{ $item['sick_quota'] }}" min="0" max="100" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-xl font-bold">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Emergency</label>
                                    <input type="number" name="emergency_quota" value="{{ $item['emergency_quota'] }}" min="0" max="100" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-xl font-bold">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Privilege</label>
                                    <input type="number" name="privilege_quota" value="{{ $item['privilege_quota'] }}" min="0" max="100" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-xl font-bold">
                                </div>

                                <div class="col-span-2 sm:col-span-1">
                                    <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-2xs transition">
                                        Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs">No staff accounts found.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- =================================================================== -->
    <!-- MODAL: APPLY FOR LEAVE                                              -->
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
                              minlength="6" 
                              class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-medium leading-relaxed bg-slate-50" 
                              placeholder="Please share detailed context regarding your time-off request for HR evaluation..."></textarea>
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