@extends('layouts.app')
@section('title', 'My Attendance Sheet')
@section('content')

<div class="space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">My Attendance Log</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Track your recorded daily presence, WFH logs, and time-off records</p>
        </div>

        <form method="GET" action="{{ route('attendance.index') }}" class="flex items-center gap-2 m-0">
            <input 
                type="month" 
                name="month" 
                value="{{ $month }}" 
                onchange="this.form.submit()" 
                class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold bg-white text-slate-700 shadow-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            >
        </form>
    </div>

    <!-- Attendance Vitals Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0 shadow-xs">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Days Present</span>
                    <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ $presentDays }} <span class="text-xs text-slate-400 font-normal">days</span></div>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold shrink-0 shadow-xs">
                    <i data-lucide="calendar-off" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Leaves Taken</span>
                    <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ $leaveDays }} <span class="text-xs text-slate-400 font-normal">days</span></div>
                </div>
            </div>
            <a href="{{ route('leaves.index') }}" class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition">Apply</a>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold shrink-0 shadow-xs">
                    <i data-lucide="user-x" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Recorded Absences</span>
                    <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ $absentDays }} <span class="text-xs text-slate-400 font-normal">days</span></div>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">Absence</span>
        </div>
    </div>

    <!-- Personal Log Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-3.5 sm:p-4 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Attendance Entries for {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</span>
            <span class="text-xs text-slate-400 font-semibold">{{ $myAttendances->count() }} Records</span>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-extrabold tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4" style="width: 25%;">Date</th>
                        <th class="py-3.5 px-4" style="width: 20%;">Status</th>
                        <th class="py-3.5 px-4" style="width: 25%;">Marked By</th>
                        <th class="py-3.5 px-4" style="width: 30%;">Notes / Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($myAttendances as $att)
                        @php $badge = $att->status_badge; @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $att->date->format('l, d M Y') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold border {{ $badge['bg'] }} inline-flex items-center gap-1">
                                    <i data-lucide="{{ $badge['icon'] }}" class="w-3.5 h-3.5"></i>
                                    <span>{{ $badge['label'] }}</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 font-medium">
                                {{ $att->marker->name ?? 'Team Lead' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 italic">
                                {{ $att->notes ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-400">
                                No attendance records found for this month.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Stacked Card View -->
        <div class="md:hidden divide-y divide-slate-100">
            @forelse($myAttendances as $att)
                @php $badge = $att->status_badge; @endphp
                <div class="p-3.5 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-bold text-slate-900 text-xs sm:text-sm">
                            {{ $att->date->format('l, d M Y') }}
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold border {{ $badge['bg'] }} inline-flex items-center gap-1 shrink-0">
                            <i data-lucide="{{ $badge['icon'] }}" class="w-3 h-3"></i>
                            <span>{{ $badge['label'] }}</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span>Marked by: <strong class="text-slate-700">{{ $att->marker->name ?? 'Team Lead' }}</strong></span>
                    </div>
                    @if($att->notes)
                        <div class="text-[11px] text-slate-500 bg-slate-50 rounded-lg p-2 border border-slate-100 italic">
                            "{{ $att->notes }}"
                        </div>
                    @endif
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">
                    No attendance records found for this month.
                </div>
            @endforelse
        </div>
    </div>

</div>

@endsection