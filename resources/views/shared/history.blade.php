@extends('layouts.app')
@section('title', 'Activity & Audit History')
@section('content')

<div class="space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">System Activity & Audit History</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Comprehensive audit trail of file downloads, uploads, task progress, and member operations</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold flex items-center gap-1.5">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>{{ $totalDownloads }} Total Downloads</span>
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold flex items-center gap-1.5">
                <i data-lucide="upload" class="w-4 h-4"></i>
                <span>{{ $totalUploads }} Total Uploads</span>
            </span>
        </div>
    </div>

    <!-- Filter & Search Panel -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('history.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <!-- Search Bar -->
            <div class="relative flex-1 w-full">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search activity description, file name, member name..." 
                    class="w-full pl-10 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none"
                >
            </div>

            <!-- Action Type Dropdown -->
            <div class="w-full sm:w-56">
                <select name="action" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
                    <option value="">All Activities</option>
                    <option value="all_tasks" {{ request('action') === 'all_tasks' ? 'selected' : '' }}>All Task History</option>
                    <option value="task_assigned" {{ request('action') === 'task_assigned' ? 'selected' : '' }}>&bull; Tasks Assigned</option>
                    <option value="task_updated" {{ request('action') === 'task_updated' ? 'selected' : '' }}>&bull; Notes & Updates</option>
                    <option value="task_submitted" {{ request('action') === 'task_submitted' ? 'selected' : '' }}>&bull; Task Submissions</option>
                    <option value="task_completed" {{ request('action') === 'task_completed' ? 'selected' : '' }}>&bull; Tasks Completed</option>
                    <option value="file_downloaded" {{ request('action') === 'file_downloaded' ? 'selected' : '' }}>File Downloads</option>
                    <option value="file_uploaded" {{ request('action') === 'file_uploaded' ? 'selected' : '' }}>File Uploads</option>
                    <option value="file_deleted" {{ request('action') === 'file_deleted' ? 'selected' : '' }}>File Deletions</option>
                    <option value="member_added" {{ request('action') === 'member_added' ? 'selected' : '' }}>Members Added</option>
                </select>
            </div>

            <!-- Filter Button -->
            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-xs shrink-0 cursor-pointer">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                <span>Filter</span>
            </button>

            @if(request()->hasAny(['search', 'action']))
                <a href="{{ route('history.index') }}" class="w-full sm:w-auto px-3 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 transition text-center shrink-0">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Audit Timeline List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Historical Audit Stream</span>
            <span class="text-xs font-bold text-slate-400">{{ $logs->total() }} Log Entries</span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($logs as $log)
                @php
                    $badge = $log->action_badge;
                @endphp
                <div class="p-4 hover:bg-slate-50/70 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-3.5 min-w-0">
                        <!-- Action Icon Badge -->
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 font-bold {{ $badge['bg'] }} border shadow-2xs">
                            <i data-lucide="{{ $badge['icon'] }}" class="w-4 h-4"></i>
                        </div>

                        <!-- Description & User -->
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-900 leading-snug">
                                {{ $log->description }}
                            </div>
                            <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-1 flex-wrap">
                                <span class="font-bold text-slate-700 flex items-center gap-1">
                                    <i data-lucide="user" class="w-3 h-3"></i>
                                    {{ $log->user->name ?? 'System' }}
                                </span>
                                <span>•</span>
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 font-extrabold text-[10px] text-slate-600">
                                    {{ $badge['label'] }}
                                </span>
                                @if($log->ip_address)
                                    <span>•</span>
                                    <span class="font-mono text-[10px] text-slate-400">IP: {{ $log->ip_address }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Timestamp -->
                    <div class="text-left sm:text-right shrink-0 self-start sm:self-center pl-12 sm:pl-0">
                        <div class="text-xs font-bold text-slate-700">{{ $log->created_at->format('d M Y') }}</div>
                        <div class="text-[10px] text-slate-400 font-mono">{{ $log->created_at->format('h:i:s A') }}</div>
                    </div>
                </div>
            @empty
                <div class="py-16 text-center text-slate-400">
                    <i data-lucide="history" class="w-10 h-10 mx-auto mb-2 text-slate-300"></i>
                    <p class="text-xs font-bold">No activity history logged yet</p>
                    <p class="text-[11px] text-slate-400 mt-1">Actions such as file downloads, uploads, and task updates will be automatically recorded here.</p>
                </div>
            @endforelse
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>

@endsection