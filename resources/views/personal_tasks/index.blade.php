@extends('layouts.app')

@section('title', 'My Tasks')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .task-card { transition: box-shadow 0.15s ease, transform 0.15s ease; }
    .task-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); transform: translateY(-1px); }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 space-y-8" x-data="myTasks()">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="notebook-pen" class="w-5 h-5"></i>
                </div>
                My Tasks
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">Your private task list — only you can see this</p>
        </div>
        <button @click="showCreate = true"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl shadow-sm transition">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>New Task</span>
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium px-4 py-3 rounded-xl flex items-center gap-2">
        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium px-4 py-3 rounded-xl flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
        {{ session('error') }}
    </div>
    @endif

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @php
            $total     = $myTasks->count();
            $pending   = $myTasks->where('status','pending')->count();
            $inprog    = $myTasks->where('status','in-progress')->count();
            $done      = $myTasks->where('status','completed')->count();
        @endphp
        <div class="bg-white border border-slate-200 rounded-2xl p-4 text-center">
            <div class="text-2xl font-black text-slate-900">{{ $total }}</div>
            <div class="text-xs text-slate-500 font-semibold mt-0.5">Total</div>
        </div>
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-center">
            <div class="text-2xl font-black text-slate-600">{{ $pending }}</div>
            <div class="text-xs text-slate-500 font-semibold mt-0.5">Pending</div>
        </div>
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 text-center">
            <div class="text-2xl font-black text-blue-600">{{ $inprog }}</div>
            <div class="text-xs text-blue-500 font-semibold mt-0.5">In Progress</div>
        </div>
        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-center">
            <div class="text-2xl font-black text-emerald-600">{{ $done }}</div>
            <div class="text-xs text-emerald-500 font-semibold mt-0.5">Completed</div>
        </div>
    </div>

    {{-- My Tasks List --}}
    <div>
        <h2 class="text-base font-extrabold text-slate-800 mb-3 flex items-center gap-2">
            <i data-lucide="list-todo" class="w-4 h-4 text-indigo-500"></i>
            My Personal Tasks
            <span class="ml-auto text-xs font-semibold text-slate-400">{{ $total }} task{{ $total !== 1 ? 's' : '' }}</span>
        </h2>

        @if($myTasks->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-2xl py-14 text-center">
            <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                <i data-lucide="clipboard-list" class="w-7 h-7 text-indigo-400"></i>
            </div>
            <p class="text-slate-600 font-semibold">No tasks yet</p>
            <p class="text-slate-400 text-sm mt-1">Click "New Task" to create your first private task</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($myTasks as $task)
            <div class="task-card bg-white border border-slate-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-start gap-4">

                {{-- Status Quick Toggle --}}
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <form method="POST" action="{{ route('my-tasks.status', $task) }}" class="mt-0.5 shrink-0">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $task->status === 'completed' ? 'pending' : ($task->status === 'pending' ? 'in-progress' : 'completed') }}">
                        <button type="submit" title="Toggle status"
                            class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition
                            {{ $task->status === 'completed' ? 'bg-emerald-500 border-emerald-500 text-white' : ($task->status === 'in-progress' ? 'bg-blue-400 border-blue-400 text-white' : 'border-slate-300 hover:border-indigo-400') }}">
                            @if($task->status === 'completed')
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            @elseif($task->status === 'in-progress')
                                <i data-lucide="loader-2" class="w-3.5 h-3.5"></i>
                            @endif
                        </button>
                    </form>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-sm font-bold text-slate-900 {{ $task->status === 'completed' ? 'line-through text-slate-400' : '' }}">
                                {{ $task->title }}
                            </span>
                            {{-- Priority Badge --}}
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full
                                {{ $task->priority === 'high' ? 'bg-rose-100 text-rose-700' : ($task->priority === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                {{ $task->priority }}
                            </span>
                            {{-- Status Badge --}}
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full
                                {{ $task->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($task->status === 'in-progress' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600') }}">
                                {{ str_replace('-', ' ', $task->status) }}
                            </span>
                            {{-- Shared Badge --}}
                            @if($task->is_shared && $task->sharedToUser)
                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 flex items-center gap-1">
                                <i data-lucide="share-2" class="w-2.5 h-2.5"></i>
                                Shared with {{ $task->sharedToUser->name }}
                            </span>
                            @endif
                        </div>
                        @if($task->description)
                        <p class="text-xs text-slate-500 leading-relaxed mb-1">{{ Str::limit($task->description, 120) }}</p>
                        @endif
                        @if($task->due_date)
                        <div class="flex items-center gap-1 text-xs {{ $task->due_date->isPast() && $task->status !== 'completed' ? 'text-rose-500 font-semibold' : 'text-slate-400' }}">
                            <i data-lucide="calendar" class="w-3 h-3"></i>
                            Due: {{ $task->due_date->format('d M Y') }}
                            @if($task->due_date->isPast() && $task->status !== 'completed')
                            <span class="text-rose-500 font-bold">(Overdue)</span>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 shrink-0 flex-wrap">
                    {{-- Share / Unshare --}}
                    @if(!auth()->user()->isCEO())
                        @if($task->is_shared)
                        <form method="POST" action="{{ route('my-tasks.unshare', $task) }}">
                            @csrf
                            <button type="submit" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-violet-50 text-violet-700 hover:bg-violet-100 border border-violet-200 transition flex items-center gap-1">
                                <i data-lucide="eye-off" class="w-3 h-3"></i> Unshare
                            </button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('my-tasks.share', $task) }}">
                            @csrf
                            <button type="submit" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition flex items-center gap-1">
                                <i data-lucide="share-2" class="w-3 h-3"></i> Share
                            </button>
                        </form>
                        @endif
                    @endif

                    {{-- Edit --}}
                    <button @click="openEdit({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ addslashes($task->description ?? '') }}', '{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}', '{{ $task->priority }}', '{{ $task->status }}')"
                        class="text-xs font-bold px-3 py-1.5 rounded-lg bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200 transition flex items-center gap-1">
                        <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                    </button>

                    {{-- Delete --}}
                    <form method="POST" action="{{ route('my-tasks.destroy', $task) }}"
                        onsubmit="return confirm('Delete this task? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 transition flex items-center gap-1">
                            <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Tasks Shared With Me --}}
    @if($sharedWithMe->isNotEmpty())
    <div>
        <h2 class="text-base font-extrabold text-slate-800 mb-3 flex items-center gap-2">
            <i data-lucide="inbox" class="w-4 h-4 text-violet-500"></i>
            Shared With Me
            <span class="ml-auto text-xs font-semibold text-slate-400">{{ $sharedWithMe->count() }} task{{ $sharedWithMe->count() !== 1 ? 's' : '' }}</span>
        </h2>
        <div class="space-y-3">
            @foreach($sharedWithMe as $task)
            <div class="task-card bg-violet-50 border border-violet-200 rounded-2xl p-4">
                <div class="flex flex-wrap items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-sm font-bold text-slate-900 {{ $task->status === 'completed' ? 'line-through text-slate-400' : '' }}">
                                {{ $task->title }}
                            </span>
                            {{-- Priority Badge --}}
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full
                                {{ $task->priority === 'high' ? 'bg-rose-100 text-rose-700' : ($task->priority === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                {{ $task->priority }}
                            </span>
                            {{-- Status Badge --}}
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full
                                {{ $task->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($task->status === 'in-progress' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600') }}">
                                {{ str_replace('-', ' ', $task->status) }}
                            </span>
                        </div>
                        @if($task->description)
                        <p class="text-xs text-slate-600 leading-relaxed mb-1">{{ $task->description }}</p>
                        @endif
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1">
                            <span class="flex items-center gap-1">
                                <i data-lucide="user" class="w-3 h-3 text-violet-500"></i>
                                Shared by <strong>{{ $task->owner->name }}</strong>
                            </span>
                            @if($task->due_date)
                            <span class="flex items-center gap-1 {{ $task->due_date->isPast() && $task->status !== 'completed' ? 'text-rose-500 font-semibold' : '' }}">
                                <i data-lucide="calendar" class="w-3 h-3"></i>
                                Due: {{ $task->due_date->format('d M Y') }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="shrink-0 flex items-center">
                        <span class="text-xs text-violet-600 font-semibold bg-violet-100 px-2 py-1 rounded-lg flex items-center gap-1">
                            <i data-lucide="eye" class="w-3 h-3"></i> View Only
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

{{-- ============================
     CREATE TASK MODAL
     ============================ --}}
<div x-show="showCreate" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    @click.self="showCreate = false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" @click.stop>
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-5 h-5 text-indigo-500"></i>
                New Personal Task
            </h3>
            <button @click="showCreate = false" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('my-tasks.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Title <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title') }}" required
                    placeholder="What do you need to do?"
                    class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" placeholder="Add details (optional)..."
                    class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Priority</label>
                    <select name="priority" class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>🟢 Low</option>
                        <option value="medium" {{ old('priority','medium') === 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>🔴 High</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2.5 rounded-xl transition">
                    Create Task
                </button>
                <button type="button" @click="showCreate = false"
                    class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold py-2.5 rounded-xl transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================
     EDIT TASK MODAL
     ============================ --}}
<div x-show="showEdit" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    @click.self="showEdit = false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" @click.stop>
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i data-lucide="pencil" class="w-5 h-5 text-indigo-500"></i>
                Edit Task
            </h3>
            <button @click="showEdit = false" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form :action="'/my-tasks/' + editId" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_method" value="PATCH">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" x-model="editTitle" required
                    class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" x-model="editDescription"
                    class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" x-model="editDueDate"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Priority</label>
                    <select name="priority" x-model="editPriority"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        <option value="low">🟢 Low</option>
                        <option value="medium">🟡 Medium</option>
                        <option value="high">🔴 High</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                <select name="status" x-model="editStatus"
                    class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <option value="pending">Pending</option>
                    <option value="in-progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2.5 rounded-xl transition">
                    Save Changes
                </button>
                <button type="button" @click="showEdit = false"
                    class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold py-2.5 rounded-xl transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function myTasks() {
    return {
        showCreate: {{ $errors->any() ? 'true' : 'false' }},
        showEdit: false,
        editId: null,
        editTitle: '',
        editDescription: '',
        editDueDate: '',
        editPriority: 'medium',
        editStatus: 'pending',

        openEdit(id, title, description, dueDate, priority, status) {
            this.editId          = id;
            this.editTitle       = title;
            this.editDescription = description;
            this.editDueDate     = dueDate;
            this.editPriority    = priority;
            this.editStatus      = status;
            this.showEdit        = true;
        }
    };
}
</script>
@endpush
