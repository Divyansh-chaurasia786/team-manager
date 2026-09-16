@extends('layouts.app')
@section('title', 'HR Dashboard')
@section('content')

<div class="space-y-6">

    {{-- HERO --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900 text-white p-6 sm:p-8 border border-teal-500/20 shadow-xl">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 sm:gap-6">
            <div class="flex items-start sm:items-center gap-3.5 sm:gap-4">
                <div class="relative shrink-0">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl object-cover border border-white/20 shadow-lg">
                    @else
                        <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-tr from-teal-600 to-emerald-500 text-white font-extrabold text-lg sm:text-xl flex items-center justify-center shadow-lg border border-white/20">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 sm:w-4 sm:h-4 bg-emerald-500 rounded-full border-2 border-slate-900"></div>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-lg sm:text-2xl font-black text-white tracking-tight">
                            Welcome, {{ explode(' ', auth()->user()->name)[0] }} 👩‍💼
                        </h1>
                        <span class="px-2 sm:px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-extrabold uppercase tracking-wide bg-teal-500/20 text-teal-300 border border-teal-400/30">HR Manager</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-300 mt-0.5 flex items-center gap-2">
                        <span>EcoFone — People & Operations</span>
                        <span class="text-slate-500">•</span>
                        <span class="text-emerald-400 font-semibold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span> Live Team
                        </span>
                    </p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-2.5 w-full lg:w-auto">
                <a href="{{ route('hr.members') }}" class="col-span-2 sm:col-span-1 px-4 py-2.5 bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-teal-600/30 transition flex items-center justify-center gap-2 border border-teal-400/30">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Manage Members</span>
                </a>
                <a href="{{ route('attendance.index') }}" class="px-3.5 py-2.5 bg-white/10 hover:bg-white/15 text-white text-xs font-bold rounded-xl border border-white/15 transition flex items-center justify-center gap-1.5">
                    <i data-lucide="user-check" class="w-4 h-4 text-emerald-300"></i>
                    <span>Attendance</span>
                </a>
                <a href="{{ route('leaves.index') }}" class="px-3.5 py-2.5 bg-white/10 hover:bg-white/15 text-white text-xs font-bold rounded-xl border border-white/15 transition flex items-center justify-center gap-1.5">
                    <i data-lucide="calendar-off" class="w-4 h-4 text-rose-300"></i>
                    <span>Leaves</span>
                </a>
            </div>
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {{-- Total Team Size --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-3.5 sm:p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Team Size</span>
                <div class="w-7 h-7 rounded-xl bg-teal-50 flex items-center justify-center">
                    <i data-lucide="users" class="w-4 h-4 text-teal-600"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900">{{ $allMembers->count() }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Active Employees</div>
        </div>

        {{-- Present Today --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Present Today</span>
                <div class="w-7 h-7 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-700">{{ $presentCount }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">{{ round(($presentCount / max($allMembers->count(), 1)) * 100) }}% turnout rate</div>
        </div>

        {{-- Absent / On Leave --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Absent / Leave</span>
                <div class="w-7 h-7 rounded-xl bg-rose-50 flex items-center justify-center">
                    <i data-lucide="user-x" class="w-4 h-4 text-rose-600"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-rose-700">{{ $absentCount }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">{{ $unmarkedCount }} unmarked</div>
        </div>

        {{-- Pending Leaves --}}
        <div class="bg-white rounded-2xl border border-amber-200 p-4 shadow-xs {{ $pendingLeaves->count() > 0 ? 'ring-1 ring-amber-300' : '' }}">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Pending Leaves</span>
                <div class="w-7 h-7 rounded-xl bg-amber-50 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                </div>
            </div>
            <div class="text-2xl font-black {{ $pendingLeaves->count() > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $pendingLeaves->count() }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Need approval</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- LEFT: Pending Leave Requests --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-500/20">
                            <i data-lucide="calendar-off" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900">Pending Leave Applications</h2>
                            <p class="text-[10px] text-slate-400">Review, approve or reject staff time-off requests</p>
                        </div>
                    </div>
                    @if($pendingLeaves->count() > 0)
                        <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-amber-100 text-amber-900 border border-amber-300 animate-pulse">
                            {{ $pendingLeaves->count() }} Actions Needed
                        </span>
                    @endif
                </div>

                @if($pendingLeaves->isEmpty())
                    <div class="p-10 text-center">
                        <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-400 mx-auto mb-2"></i>
                        <p class="text-xs text-slate-400 font-semibold">No pending leave applications at this moment.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($pendingLeaves as $leave)
                            <div class="p-4 sm:p-5 hover:bg-slate-50/70 transition">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="space-y-1.5 flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-bold text-slate-900 text-sm">{{ $leave->user->name }}</h3>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                {{ $leave->leave_type_label ?? ucfirst($leave->leave_type) }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700">
                                                {{ $leave->total_days }} Day(s)
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 flex items-center gap-3 flex-wrap">
                                            <span><strong>Dates:</strong> {{ $leave->start_date->format('d M Y') }} to {{ $leave->end_date->format('d M Y') }}</span>
                                            <span>•</span>
                                            <span>Applied {{ $leave->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 italic bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                            "{{ $leave->reason }}"
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0 sm:self-center">
                                        <form method="POST" action="{{ route('hr.leaves.approve', $leave) }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm shadow-emerald-600/20 cursor-pointer">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('hr.leaves.reject', $leave) }}" class="m-0" onsubmit="return confirm('Reject this leave request?')">
                                            @csrf
                                            <input type="hidden" name="review_notes" value="Declined by HR">
                                            <button type="submit" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition cursor-pointer">
                                                <span>Reject</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Team Member Directory Quick List --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900">Team Roster & Attendance Status</h2>
                            <p class="text-[10px] text-slate-400">All registered employees in operations</p>
                        </div>
                    </div>
                    <a href="{{ route('hr.members') }}" class="text-xs text-teal-600 font-bold hover:underline">Full Directory →</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($memberStats as $stat)
                        <div class="px-5 py-3.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-teal-100 text-teal-800 font-black text-xs flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($stat['member']->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-800">{{ $stat['member']->name }}</span>
                                        <span class="text-[10px] font-semibold text-slate-400">{{ $stat['member']->designation ?? ucfirst($stat['member']->role) }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400">{{ $stat['member']->email }} • {{ $stat['member']->mobile_number ?? 'No phone' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @php $attStatus = $stat['att_status']; @endphp
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full
                                    {{ in_array($attStatus, ['present','wfh','half_day']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($attStatus === 'unmarked' ? 'bg-slate-100 text-slate-500' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                                    {{ strtoupper(str_replace('_',' ',$attStatus)) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-xs text-slate-400">No members registered yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT: Quick Actions & Recently Processed Leaves --}}
        <div class="space-y-4">
            {{-- Quick HR Actions --}}
            <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs space-y-3">
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="zap" class="w-4 h-4 text-teal-600"></i>
                    <span>Quick Operations</span>
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('hr.members') }}" class="w-full px-3.5 py-2.5 bg-teal-50 hover:bg-teal-100 text-teal-800 rounded-xl text-xs font-bold transition flex items-center justify-between border border-teal-200/60">
                        <span class="flex items-center gap-2">
                            <i data-lucide="user-plus" class="w-4 h-4 text-teal-600"></i>
                            Add / Edit Employee
                        </span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                    <a href="{{ route('attendance.index') }}" class="w-full px-3.5 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-xl text-xs font-bold transition flex items-center justify-between border border-emerald-200/60">
                        <span class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4 text-emerald-600"></i>
                            Daily Attendance Register
                        </span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                    <a href="{{ route('history.index') }}" class="w-full px-3.5 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl text-xs font-bold transition flex items-center justify-between border border-slate-200/60">
                        <span class="flex items-center gap-2">
                            <i data-lucide="history" class="w-4 h-4 text-slate-600"></i>
                            System Audit Logs
                        </span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

            {{-- Recently Reviewed Leaves --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                        <i data-lucide="file-check" class="w-3.5 h-3.5"></i>
                    </div>
                    <h3 class="text-xs font-black text-slate-900">Recent Leave Decisions</h3>
                </div>
                @if($recentLeaves->isEmpty())
                    <div class="p-6 text-center text-xs text-slate-400">No recently processed requests.</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($recentLeaves as $rl)
                            <div class="p-3.5 flex items-center justify-between gap-2">
                                <div>
                                    <div class="text-xs font-bold text-slate-800">{{ $rl->user->name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $rl->start_date->format('d M') }} - {{ $rl->end_date->format('d M') }} ({{ $rl->total_days }}d)</div>
                                </div>
                                <div>
                                    @if($rl->status === 'approved')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Approved</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">Rejected</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
