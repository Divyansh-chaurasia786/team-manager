@extends('layouts.app')
@section('title', 'Team Attendance Roster')
@section('content')

<div class="space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Team Attendance Roster</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Mark daily presence to gate task delegation & ensure tasks are only assigned to active members</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Date Picker Form -->
            <form method="GET" action="{{ route('attendance.index') }}" class="flex items-center gap-2 m-0">
                <input 
                    type="date" 
                    name="date" 
                    value="{{ $selectedDate }}" 
                    onchange="this.form.submit()" 
                    class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold bg-white text-slate-700 shadow-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
            </form>

            <!-- Bulk Mark All Present Button -->
            <form method="POST" action="{{ route('tl.attendance.bulk') }}" class="m-0" onsubmit="return confirm('Mark all active team members as Present for {{ $selectedDate }}?')">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate }}">
                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="check-check" class="w-4 h-4"></i>
                    <span>Mark All Present</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Attendance Vitals Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shadow-xs">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Eligible</span>
            </div>
            <div class="mt-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Present Today</div>
                <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $presentCount }} <span class="text-xs font-medium text-slate-400">/ {{ $members->count() }}</span></div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold shadow-xs">
                    <i data-lucide="user-x" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-700">Locked</span>
            </div>
            <div class="mt-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Absent</div>
                <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $absentCount }} <span class="text-xs font-medium text-slate-400">members</span></div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold shadow-xs">
                    <i data-lucide="calendar-off" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700">On Leave</span>
            </div>
            <div class="mt-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Approved Leave</div>
                <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $onLeaveCount }} <span class="text-xs font-medium text-slate-400">members</span></div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold shadow-xs">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Pending</span>
            </div>
            <div class="mt-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Unmarked</div>
                <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $unmarkedCount }} <span class="text-xs font-medium text-slate-400">to mark</span></div>
            </div>
        </div>
    </div>

    <!-- Attendance Roster Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar" class="w-4 h-4 text-indigo-600"></i>
                <span class="text-xs font-bold text-slate-700">Daily Roster for {{ \Carbon\Carbon::parse($selectedDate)->format('l, d F Y') }}</span>
            </div>
            <span class="text-xs text-slate-400 font-semibold">{{ $members->count() }} Team Staff</span>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-extrabold tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4" style="width: 25%;">Team Member</th>
                        <th class="py-3.5 px-4" style="width: 20%;">Current Status</th>
                        <th class="py-3.5 px-4" style="width: 20%;">Task Assignment Status</th>
                        <th class="py-3.5 px-4 text-right" style="width: 35%;">Mark Attendance (1-Click)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($members as $m)
                        @php
                            $att = $attendances->get($m->id);
                            $status = $att ? $att->status : 'unmarked';
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-extrabold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                        {{ strtoupper(substr($m->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $m->name }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $m->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-4 px-4">
                                @if($att)
                                    @php $badge = $att->status_badge; @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold border {{ $badge['bg'] }} inline-flex items-center gap-1">
                                        <i data-lucide="{{ $badge['icon'] }}" class="w-3.5 h-3.5"></i>
                                        <span>{{ $badge['label'] }}</span>
                                    </span>
                                    @if($att->notes)
                                        <div class="text-[10px] text-slate-400 mt-1 italic max-w-xs truncate">"{{ $att->notes }}"</div>
                                    @endif
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-500 border border-slate-200">
                                        Not Marked Yet
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-4">
                                @if(in_array($status, ['present', 'wfh']))
                                    <span class="text-emerald-700 font-bold text-xs flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>Eligible for Tasks</span>
                                    </span>
                                @elseif($status === 'on_leave')
                                    <span class="text-purple-700 font-bold text-xs flex items-center gap-1">
                                        <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                        <span>Locked (On Leave)</span>
                                    </span>
                                @elseif($status === 'absent')
                                    <span class="text-rose-600 font-bold text-xs flex items-center gap-1">
                                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                        <span>Locked (Absent)</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 font-semibold text-xs">
                                        Defaulting to Present
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5 flex-wrap justify-end">
                                    <!-- Present -->
                                    <form method="POST" action="{{ route('attendance.mark') }}" class="m-0">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $m->id }}">
                                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                                        <input type="hidden" name="status" value="present">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $status === 'present' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }}" title="Mark Present">
                                            Present
                                        </button>
                                    </form>

                                    <!-- WFH -->
                                    <form method="POST" action="{{ route('attendance.mark') }}" class="m-0">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $m->id }}">
                                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                                        <input type="hidden" name="status" value="wfh">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $status === 'wfh' ? 'bg-blue-600 text-white shadow-xs' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200' }}" title="Mark Work From Home">
                                            WFH
                                        </button>
                                    </form>

                                    <!-- Half Day -->
                                    <form method="POST" action="{{ route('attendance.mark') }}" class="m-0">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $m->id }}">
                                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                                        <input type="hidden" name="status" value="half_day">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $status === 'half_day' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' }}" title="Mark Half Day">
                                            Half-Day
                                        </button>
                                    </form>

                                    <!-- Absent -->
                                    <form method="POST" action="{{ route('attendance.mark') }}" class="m-0">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $m->id }}">
                                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                                        <input type="hidden" name="status" value="absent">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $status === 'absent' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' }}" title="Mark Absent (Locks task delegation)">
                                            Absent
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-400">
                                No team members found. Go to <a href="{{ route('tl.members') }}" class="text-indigo-600 font-bold underline">Team Members</a> to add staff.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Stacked Card View -->
        <div class="md:hidden divide-y divide-slate-100">
            @forelse($members as $m)
                @php
                    $att = $attendances->get($m->id);
                    $status = $att ? $att->status : 'unmarked';
                @endphp
                <div class="p-3.5 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-extrabold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                {{ strtoupper(substr($m->name, 0, 2)) }}
                            </div>
                            <div class="truncate">
                                <div class="font-bold text-slate-900 text-xs sm:text-sm truncate">{{ $m->name }}</div>
                                <div class="text-[10px] text-slate-400 truncate">{{ $m->email }}</div>
                            </div>
                        </div>

                        <div>
                            @if($att)
                                @php $badge = $att->status_badge; @endphp
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold border {{ $badge['bg'] }} inline-flex items-center gap-1 shrink-0">
                                    <i data-lucide="{{ $badge['icon'] }}" class="w-3 h-3"></i>
                                    <span>{{ $badge['label'] }}</span>
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-slate-100 text-slate-500 border border-slate-200 shrink-0">
                                    Unmarked
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] bg-slate-50 rounded-xl px-2.5 py-1.5 border border-slate-100">
                        <span class="text-slate-500 font-medium">Task Eligibility:</span>
                        @if(in_array($status, ['present', 'wfh']))
                            <span class="text-emerald-700 font-bold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Eligible</span>
                            </span>
                        @elseif($status === 'on_leave')
                            <span class="text-purple-700 font-bold flex items-center gap-1">
                                <i data-lucide="shield-alert" class="w-3 h-3"></i>
                                <span>On Leave</span>
                            </span>
                        @elseif($status === 'absent')
                            <span class="text-rose-600 font-bold flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                <span>Absent (Locked)</span>
                            </span>
                        @else
                            <span class="text-slate-400 font-medium">Eligible (Default)</span>
                        @endif
                    </div>

                    <!-- 1-Click Action Buttons -->
                    <div class="grid grid-cols-4 gap-1.5 pt-1">
                        <form method="POST" action="{{ route('attendance.mark') }}" class="m-0">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $m->id }}">
                            <input type="hidden" name="date" value="{{ $selectedDate }}">
                            <input type="hidden" name="status" value="present">
                            <button type="submit" class="w-full py-1.5 rounded-lg text-[10px] font-bold transition text-center {{ $status === 'present' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }}">
                                Present
                            </button>
                        </form>

                        <form method="POST" action="{{ route('attendance.mark') }}" class="m-0">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $m->id }}">
                            <input type="hidden" name="date" value="{{ $selectedDate }}">
                            <input type="hidden" name="status" value="wfh">
                            <button type="submit" class="w-full py-1.5 rounded-lg text-[10px] font-bold transition text-center {{ $status === 'wfh' ? 'bg-blue-600 text-white shadow-xs' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200' }}">
                                WFH
                            </button>
                        </form>

                        <form method="POST" action="{{ route('attendance.mark') }}" class="m-0">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $m->id }}">
                            <input type="hidden" name="date" value="{{ $selectedDate }}">
                            <input type="hidden" name="status" value="half_day">
                            <button type="submit" class="w-full py-1.5 rounded-lg text-[10px] font-bold transition text-center {{ $status === 'half_day' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' }}">
                                Half-Day
                            </button>
                        </form>

                        <form method="POST" action="{{ route('attendance.mark') }}" class="m-0">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $m->id }}">
                            <input type="hidden" name="date" value="{{ $selectedDate }}">
                            <input type="hidden" name="status" value="absent">
                            <button type="submit" class="w-full py-1.5 rounded-lg text-[10px] font-bold transition text-center {{ $status === 'absent' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' }}">
                                Absent
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">
                    No team members found.
                </div>
            @endforelse
        </div>
    </div>

</div>

@endsection