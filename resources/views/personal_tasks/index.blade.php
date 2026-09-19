@extends('layouts.app')

@section('title', 'My Tasks')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .task-interactive-card {
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .task-interactive-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px -6px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
    }
</style>
@endpush

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full pb-16"
     x-data="{
         showCreate: {{ $errors->any() ? 'true' : 'false' }},
         showEdit: false,
         editId: null,
         editTitle: '',
         editDescription: '',
         editDueDate: '',
         editPriority: 'medium',
         editStatus: 'pending',
         activeTab: 'all',
         searchQuery: '',
         openCreate() {
             this.showCreate = true;
             this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
         },
         openEdit(id, title, description, dueDate, priority, status) {
             this.editId          = id;
             this.editTitle       = title;
             this.editDescription = description;
             this.editDueDate     = dueDate;
             this.editPriority    = priority;
             this.editStatus      = status;
             this.showEdit        = true;
             this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
         },
         matchesFilter(status, isShared, title, desc, ownerName) {
             // Tab match
             if (this.activeTab === 'pending' && status !== 'pending') return false;
             if (this.activeTab === 'in-progress' && status !== 'in-progress') return false;
             if (this.activeTab === 'completed' && status !== 'completed') return false;
             if (this.activeTab === 'shared_by_me' && !isShared) return false;

             // Search match
             if (this.searchQuery.trim() !== '') {
                 const q = this.searchQuery.toLowerCase().trim();
                 const haystack = ((title || '') + ' ' + (desc || '') + ' ' + (ownerName || '')).toLowerCase();
                 return haystack.includes(q);
             }
             return true;
         }
     }">

    <!-- Executive Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white flex items-center justify-center shadow-lg shadow-indigo-600/25 shrink-0">
                <i data-lucide="notebook-pen" class="w-5 h-5"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <span>My Tasks</span>
                    <span class="text-xs font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200/80">Personal</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Private productivity tracker — assign tasks to yourself, track deadlines, and share when needed</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="{{ route('history.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-2xs border border-slate-200/70">
                <i data-lucide="history" class="w-4 h-4 text-slate-500"></i>
                <span>Activity Log</span>
            </a>
            <button type="button" @click="openCreate()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer border border-indigo-400/30">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Create New Task</span>
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold px-4 py-3 rounded-2xl flex items-center gap-2.5 shadow-2xs">
        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-semibold px-4 py-3 rounded-2xl flex items-center gap-2.5 shadow-2xs">
        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif
    @if($errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-medium px-4 py-3 rounded-2xl shadow-2xs">
        <ul class="list-disc list-inside space-y-1 font-semibold">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @php
        $total       = $myTasks->count();
        $pending     = $myTasks->where('status','pending')->count();
        $inprog      = $myTasks->where('status','in-progress')->count();
        $done        = $myTasks->where('status','completed')->count();
        $sharedCount = $myTasks->where('is_shared', true)->count();
        $sharedWithMeCount = $sharedWithMe->count();
        $completionRate = $total > 0 ? round(($done / $total) * 100) : 0;
    @endphp

    <!-- Executive KPI & Vitals Summary Grid (Style from tasks.blade.php & dashboard) -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
        <!-- Total Tasks -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-indigo-200 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Total Tasks</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="list-checks" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $total }}</div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] font-bold text-indigo-600">
                    <i data-lucide="activity" class="w-3 h-3"></i>
                    <span>{{ $completionRate }}% completed</span>
                </div>
            </div>
        </div>

        <!-- Pending / Action Needed -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-amber-200 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Pending Start</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="hourglass" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl sm:text-3xl font-black text-amber-600 tracking-tight">{{ $pending }}</div>
                <span class="text-[11px] font-semibold text-slate-400 mt-1 block">Awaiting start</span>
            </div>
        </div>

        <!-- In Progress -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-blue-200 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">In Progress</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="play-circle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl sm:text-3xl font-black text-blue-600 tracking-tight">{{ $inprog }}</div>
                <span class="text-[11px] font-semibold text-slate-400 mt-1 block">Active tasks</span>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-emerald-200 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Completed</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl sm:text-3xl font-black text-emerald-600 tracking-tight">{{ $done }}</div>
                <span class="text-[11px] font-semibold text-slate-400 mt-1 block">Finished to-dos</span>
            </div>
        </div>

        <!-- Shared / Collab -->
        <div class="col-span-2 lg:col-span-1 bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-violet-200 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Shared Tasks</span>
                <div class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                    <i data-lucide="share-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-violet-600 tracking-tight">{{ $sharedCount }}</span>
                    @if($sharedWithMeCount > 0)
                        <span class="text-xs font-bold text-violet-500">({{ $sharedWithMeCount }} in)</span>
                    @endif
                </div>
                <span class="text-[11px] font-semibold text-slate-400 mt-1 block">Shared with TL/CEO</span>
            </div>
        </div>
    </div>

    <!-- Interactive Filter Toolbar & Live Search (Zero Overflow Layout) -->
    <div class="bg-white p-3 rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <!-- Segmented Filter Pills -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100/90 rounded-2xl overflow-x-auto scrollbar-none w-full md:w-auto">
                <button type="button" @click="activeTab = 'all'"
                    class="py-2 px-3.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 shrink-0 cursor-pointer"
                    :class="activeTab === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70 hover:text-slate-900'">
                    <span>All Tasks</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold"
                          :class="activeTab === 'all' ? 'bg-slate-700 text-slate-200' : 'bg-slate-200 text-slate-600'">{{ $total }}</span>
                </button>

                <button type="button" @click="activeTab = 'pending'"
                    class="py-2 px-3.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 shrink-0 cursor-pointer"
                    :class="activeTab === 'pending' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70 hover:text-slate-900'">
                    <span>Pending</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800">{{ $pending }}</span>
                </button>

                <button type="button" @click="activeTab = 'in-progress'"
                    class="py-2 px-3.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 shrink-0 cursor-pointer"
                    :class="activeTab === 'in-progress' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70 hover:text-slate-900'">
                    <span>In Progress</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-800">{{ $inprog }}</span>
                </button>

                <button type="button" @click="activeTab = 'completed'"
                    class="py-2 px-3.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 shrink-0 cursor-pointer"
                    :class="activeTab === 'completed' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70 hover:text-slate-900'">
                    <span>Completed</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800">{{ $done }}</span>
                </button>

                @if($sharedWithMeCount > 0)
                <button type="button" @click="activeTab = 'shared_with_me'"
                    class="py-2 px-3.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 shrink-0 cursor-pointer"
                    :class="activeTab === 'shared_with_me' ? 'bg-violet-700 text-white shadow-xs' : 'text-violet-700 hover:bg-violet-100/70'">
                    <i data-lucide="inbox" class="w-3.5 h-3.5"></i>
                    <span>Shared With Me</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-violet-200 text-violet-900">{{ $sharedWithMeCount }}</span>
                </button>
                @endif
            </div>

            <!-- Instant Search Input -->
            <div class="relative w-full md:w-72">
                <input type="text" x-model="searchQuery" placeholder="Search tasks by title or notes..."
                    class="w-full pl-9 pr-8 py-2 text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                <button type="button" x-show="searchQuery.length > 0" @click="searchQuery = ''" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Personal Tasks Section -->
    <div x-show="activeTab !== 'shared_with_me'" class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="text-sm sm:text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="list-todo" class="w-4 h-4 text-indigo-600"></i>
                    <span>Personal Task Feed</span>
                </h2>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200/70">
                    {{ $total }} Task{{ $total !== 1 ? 's' : '' }}
                </span>
            </div>
            <button type="button" @click="openCreate()" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 transition cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Add Task</span>
            </button>
        </div>

        @if($myTasks->isEmpty())
            <!-- Clean Empty State Matching Tasks Page -->
            <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-black text-slate-800">No Personal Tasks Yet</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-5">Start tracking your personal deliverables, milestones, and daily action items in one place.</p>
                <button type="button" @click="openCreate()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition inline-flex items-center gap-2 cursor-pointer border border-indigo-400/30">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Create Your First Task</span>
                </button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($myTasks as $task)
                @php
                    $isOverdue = $task->due_date && $task->due_date->isPast() && $task->status !== 'completed';
                    $isDueToday = $task->due_date && $task->due_date->isToday();
                @endphp
                <div class="task-interactive-card bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 p-4 sm:p-5 shadow-xs hover:border-slate-300 transition"
                     x-show="matchesFilter('{{ $task->status }}', {{ $task->is_shared ? 'true' : 'false' }}, '{{ addslashes($task->title) }}', '{{ addslashes($task->description ?? '') }}', '')"
                     x-transition>

                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <!-- Left: Status Quick-Toggle Button & Task Content -->
                        <div class="flex items-start gap-3.5 flex-1 min-w-0">
                            <!-- Status Interactive Toggle Button -->
                            <form method="POST" action="{{ route('my-tasks.status', $task) }}" class="mt-0.5 shrink-0">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $task->status === 'completed' ? 'pending' : ($task->status === 'pending' ? 'in-progress' : 'completed') }}">
                                <button type="submit" title="Click to cycle status: Pending → In Progress → Completed"
                                    class="w-8 h-8 rounded-2xl border-2 flex items-center justify-center transition-all duration-150 cursor-pointer shadow-2xs group
                                    {{ $task->status === 'completed' ? 'bg-emerald-500 border-emerald-500 text-white' : ($task->status === 'in-progress' ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-slate-50 border-slate-300 hover:border-indigo-500 text-slate-400') }}">
                                    @if($task->status === 'completed')
                                        <i data-lucide="check" class="w-4 h-4 stroke-[3]"></i>
                                    @elseif($task->status === 'in-progress')
                                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-blue-600"></i>
                                    @else
                                        <i data-lucide="circle" class="w-3.5 h-3.5 opacity-40 group-hover:opacity-100"></i>
                                    @endif
                                </button>
                            </form>

                            <div class="space-y-1.5 flex-1 min-w-0">
                                <!-- Badges Row: Priority, Status, Overdue, Shared -->
                                <div class="flex items-center gap-2 flex-wrap">
                                    <!-- Priority Badge -->
                                    @if($task->priority === 'high')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                            <span>High Priority</span>
                                        </span>
                                    @elseif($task->priority === 'medium')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>Medium Priority</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Low Priority</span>
                                        </span>
                                    @endif

                                    <!-- Status Pill -->
                                    @if($task->status === 'completed')
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            ✓ Completed
                                        </span>
                                    @elseif($task->status === 'in-progress')
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-300 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                            <span>⚙️ In Progress</span>
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200">
                                            ⏳ Pending Start
                                        </span>
                                    @endif

                                    <!-- Overdue Warning Tag -->
                                    @if($isOverdue)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300 flex items-center gap-1">
                                            <i data-lucide="alert-triangle" class="w-3 h-3 text-rose-600"></i>
                                            <span>Overdue</span>
                                        </span>
                                    @elseif($isDueToday)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1">
                                            <i data-lucide="flame" class="w-3 h-3 text-amber-600"></i>
                                            <span>Due Today</span>
                                        </span>
                                    @endif

                                    <!-- Shared Status Pill -->
                                    @if($task->is_shared && $task->sharedToUser)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-violet-100 text-violet-800 border border-violet-200 flex items-center gap-1">
                                            <i data-lucide="share-2" class="w-2.5 h-2.5 text-violet-600"></i>
                                            <span>Shared with {{ $task->sharedToUser->name }}</span>
                                        </span>
                                    @endif
                                </div>

                                <!-- Task Title -->
                                <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight leading-snug {{ $task->status === 'completed' ? 'line-through text-slate-400' : '' }}">
                                    {{ $task->title }}
                                </h3>

                                <!-- Description / Notes -->
                                @if($task->description)
                                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-3xl whitespace-pre-line">{{ $task->description }}</p>
                                @endif

                                <!-- Meta Footer: Due Date & Timestamps -->
                                <div class="flex items-center gap-3 text-xs text-slate-500 pt-1 flex-wrap">
                                    @if($task->due_date)
                                        <div class="flex items-center gap-1.5 font-semibold {{ $isOverdue ? 'text-rose-600 font-bold' : ($isDueToday ? 'text-amber-700 font-bold' : 'text-slate-600') }}">
                                            <i data-lucide="calendar" class="w-3.5 h-3.5 {{ $isOverdue ? 'text-rose-500' : 'text-slate-400' }}"></i>
                                            <span>Due: {{ $task->due_date->format('d M Y') }}</span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1.5 text-slate-400 text-xs">
                                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                            <span>No due date</span>
                                        </div>
                                    @endif

                                    <span class="text-slate-300">•</span>
                                    <span class="text-[11px] text-slate-400">Created {{ $task->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Unified Action Buttons (Matching tasks.blade.php) -->
                        <div class="flex items-center gap-2 shrink-0 sm:self-center w-full sm:w-auto justify-end pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 flex-wrap">
                            {{-- Share / Unshare Action --}}
                            @if(!auth()->user()->isCEO())
                                @if($task->is_shared)
                                    <form method="POST" action="{{ route('my-tasks.unshare', $task) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="px-3 py-2 bg-violet-50 hover:bg-violet-100 text-violet-700 rounded-xl font-bold text-xs transition shadow-2xs border border-violet-200/80 flex items-center gap-1.5 cursor-pointer" title="Stop sharing this task">
                                            <i data-lucide="eye-off" class="w-3.5 h-3.5 text-violet-600"></i>
                                            <span>Unshare</span>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('my-tasks.share', $task) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="px-3 py-2 bg-slate-50 hover:bg-violet-50 hover:text-violet-700 text-slate-600 rounded-xl font-bold text-xs transition shadow-2xs border border-slate-200 hover:border-violet-200 flex items-center gap-1.5 cursor-pointer" title="Share with supervisor">
                                            <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                                            <span>Share</span>
                                        </button>
                                    </form>
                                @endif
                            @endif

                            {{-- Edit Button --}}
                            <button type="button"
                                @click="openEdit({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ addslashes($task->description ?? '') }}', '{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}', '{{ $task->priority }}', '{{ $task->status }}')"
                                class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition shadow-2xs border border-slate-200 flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="pencil" class="w-3.5 h-3.5 text-slate-600"></i>
                                <span>Edit</span>
                            </button>

                            {{-- Delete Button --}}
                            <form method="POST" action="{{ route('my-tasks.destroy', $task) }}"
                                onsubmit="return confirm('Permanently delete &quot;{{ addslashes($task->title) }}&quot;? This action cannot be undone.')" class="m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 rounded-xl font-bold text-xs transition shadow-2xs border border-rose-200/70 flex items-center gap-1.5 cursor-pointer" title="Delete Task">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    <span class="hidden sm:inline">Delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Tasks Shared With Me Section (Styled Like Tasks Delegated Review) -->
    @if($sharedWithMe->isNotEmpty())
    <div x-show="activeTab === 'all' || activeTab === 'shared_with_me'" class="space-y-4 pt-2">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-violet-100 text-violet-700 flex items-center justify-center">
                    <i data-lucide="inbox" class="w-4 h-4"></i>
                </div>
                <h2 class="text-sm sm:text-base font-black text-slate-900 tracking-tight">Tasks Shared With Me</h2>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-violet-100 text-violet-800 border border-violet-200">
                    {{ $sharedWithMe->count() }} Shared
                </span>
            </div>
            <span class="text-xs text-slate-400 font-medium hidden sm:inline">Shared by your direct reports & team members</span>
        </div>

        <div class="space-y-3">
            @foreach($sharedWithMe as $task)
            <div class="task-interactive-card bg-gradient-to-r from-violet-50/40 via-white to-indigo-50/20 rounded-2xl sm:rounded-3xl border border-violet-200/90 p-4 sm:p-5 shadow-xs"
                 x-show="matchesFilter('{{ $task->status }}', false, '{{ addslashes($task->title) }}', '{{ addslashes($task->description ?? '') }}', '{{ addslashes($task->owner->name ?? '') }}')"
                 x-transition>

                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="space-y-2 flex-1 min-w-0">
                        <!-- Badges Row -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Subordinate / Owner Badge -->
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-violet-100 text-violet-900 border border-violet-200 text-xs font-bold">
                                <div class="w-4 h-4 rounded-full bg-violet-600 text-white text-[9px] font-black flex items-center justify-center">
                                    {{ strtoupper(substr($task->owner->name ?? 'U', 0, 1)) }}
                                </div>
                                <span>Shared by {{ $task->owner->name }}</span>
                            </div>

                            <!-- Priority Badge -->
                            @if($task->priority === 'high')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-50 text-rose-700 border border-rose-200">High Priority</span>
                            @elseif($task->priority === 'medium')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 border border-amber-200">Medium Priority</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">Low Priority</span>
                            @endif

                            <!-- Status Badge -->
                            @if($task->status === 'completed')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">✓ Completed</span>
                            @elseif($task->status === 'in-progress')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-800 border border-blue-300">⚙️ In Progress</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-100 text-slate-600 border border-slate-200">⏳ Pending</span>
                            @endif
                        </div>

                        <!-- Title -->
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight {{ $task->status === 'completed' ? 'line-through text-slate-400' : '' }}">
                            {{ $task->title }}
                        </h3>

                        <!-- Description -->
                        @if($task->description)
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $task->description }}</p>
                        @endif

                        <!-- Due Date -->
                        <div class="flex items-center gap-3 text-xs text-slate-500 pt-1 flex-wrap">
                            @if($task->due_date)
                                <div class="flex items-center gap-1.5 font-semibold {{ $task->due_date->isPast() && $task->status !== 'completed' ? 'text-rose-600 font-bold' : 'text-slate-600' }}">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Due: {{ $task->due_date->format('d M Y') }}</span>
                                </div>
                            @endif
                            <span class="text-slate-300">•</span>
                            <span class="text-[11px] text-slate-400">Created {{ $task->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    <div class="shrink-0 flex items-center self-start sm:self-center">
                        <span class="px-3 py-1.5 bg-violet-100 text-violet-800 border border-violet-200 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-2xs">
                            <i data-lucide="lock" class="w-3.5 h-3.5 text-violet-600"></i>
                            <span>Read Only</span>
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ==========================================
         MODAL: CREATE TASK (Styled Like Assign Task)
         ========================================== --}}
    <div x-show="showCreate" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3.5 sm:p-4"
        @click.self="showCreate = false">
        <div class="bg-white rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200" @click.stop>
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-xs">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Create Personal Task</h3>
                        <p class="text-xs text-slate-500">Plan a personal to-do or deliverable item</p>
                    </div>
                </div>
                <button type="button" @click="showCreate = false" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('my-tasks.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Task Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}" required autofocus
                        placeholder="e.g. Finalize weekly client presentation deck"
                        class="w-full border border-slate-200 bg-slate-50/50 rounded-xl px-3.5 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Description / Checklist Notes</label>
                    <textarea name="description" rows="3" placeholder="Add any details, instructions, or sub-tasks..."
                        class="w-full border border-slate-200 bg-slate-50/50 rounded-xl px-3.5 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition resize-none">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Due Date</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                            class="w-full border border-slate-200 bg-slate-50/50 rounded-xl px-3.5 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Priority Level</label>
                        <select name="priority" class="w-full border border-slate-200 bg-slate-50/50 rounded-xl px-3.5 py-2.5 text-sm font-bold focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition">
                            <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>🟢 Low Priority</option>
                            <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>🟡 Medium Priority</option>
                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>🔴 High Priority</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showCreate = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold py-2.5 rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-lg shadow-indigo-600/30 cursor-pointer border border-indigo-400/30">
                        Save Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==========================================
         MODAL: EDIT TASK (Styled Like Edit Task)
         ========================================== --}}
    <div x-show="showEdit" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3.5 sm:p-4"
        @click.self="showEdit = false">
        <div class="bg-white rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200" @click.stop>
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-xs">
                        <i data-lucide="pencil" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Edit Personal Task</h3>
                        <p class="text-xs text-slate-500">Update task details, deadline, and status</p>
                    </div>
                </div>
                <button type="button" @click="showEdit = false" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="'{{ url('/my-tasks') }}/' + editId" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Task Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" x-model="editTitle" required
                        class="w-full border border-slate-200 bg-slate-50/50 rounded-xl px-3.5 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Description / Notes</label>
                    <textarea name="description" rows="3" x-model="editDescription"
                        class="w-full border border-slate-200 bg-slate-50/50 rounded-xl px-3.5 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition resize-none"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Due Date</label>
                        <input type="date" name="due_date" x-model="editDueDate"
                            class="w-full border border-slate-200 bg-slate-50/50 rounded-xl px-3.5 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Priority</label>
                        <select name="priority" x-model="editPriority"
                            class="w-full border border-slate-200 bg-slate-50/50 rounded-xl px-3.5 py-2.5 text-sm font-bold focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition">
                            <option value="low">🟢 Low Priority</option>
                            <option value="medium">🟡 Medium Priority</option>
                            <option value="high">🔴 High Priority</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" x-model="editStatus"
                        class="w-full border border-slate-200 bg-slate-50/50 rounded-xl px-3.5 py-2.5 text-sm font-bold focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition">
                        <option value="pending">⏳ Pending Start</option>
                        <option value="in-progress">⚙️ In Progress</option>
                        <option value="completed">✓ Completed</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showEdit = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold py-2.5 rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-lg shadow-indigo-600/30 cursor-pointer border border-indigo-400/30">
                        Update Task
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>
@endpush
@endsection
