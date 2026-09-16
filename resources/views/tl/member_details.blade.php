@extends('layouts.app')
@section('title', $user->name . ' - Member Details')
@section('content')

@php
    $membersIndexRoute = auth()->user()->isHR() ? 'hr.members' : 'tl.members';
    $routePrefix = auth()->user()->isHR() ? 'hr.members.' : 'tl.members.';
@endphp

<div class="space-y-6">

    <!-- Top Navigation Breadcrumb & Back -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <a href="{{ route($membersIndexRoute) }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-indigo-600 transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back to Team Members</span>
        </a>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Reset OTP Button -->
            <form method="POST" action="{{ route($routePrefix . 'reset_otp', $user) }}" onsubmit="return confirm('Generate and email a new temporary One-Time Password for {{ $user->name }}?')">
                @csrf
                <button type="submit" class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="key-round" class="w-4 h-4 text-amber-600"></i>
                    <span>Reset OTP & Email Credentials</span>
                </button>
            </form>

            <!-- Delete Member Button -->
            <form method="POST" action="{{ route($routePrefix . 'destroy', $user) }}" onsubmit="return confirm('Permanently remove {{ $user->name }} and their records?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="trash-2" class="w-4 h-4 text-rose-600"></i>
                    <span>Remove Member</span>
                </button>
            </form>
        </div>
    </div>

    @if(session('new_member'))
    <!-- New Generated Credential Banner -->
    <div class="p-5 rounded-2xl bg-gradient-to-r from-indigo-950 via-slate-900 to-indigo-900 text-white border border-indigo-500/40 shadow-xl relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center shrink-0 mt-0.5">
                    <i data-lucide="key-round" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-black text-white">Temporary Credentials Re-issued</h3>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold">Email Dispatched</span>
                    </div>
                    <p class="text-xs text-slate-300 mt-1">
                        A fresh One-Time Password has been generated and emailed to <strong>{{ $user->email }}</strong>.
                    </p>
                </div>
            </div>

            <div x-data="{ showOtp: false }" class="bg-slate-950/80 border border-slate-700/60 rounded-xl p-3 shrink-0 flex items-center gap-3">
                <div class="flex flex-col">
                    <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold">One-Time Password (Hidden)</span>
                    <span class="font-mono text-sm font-black text-amber-400" x-text="showOtp ? '{{ session('new_member')['otp'] }}' : '••••••••••••'">••••••••••••</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="showOtp = !showOtp; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" class="text-slate-400 hover:text-amber-400 p-1 transition cursor-pointer select-none" title="Toggle visibility">
                        <i data-lucide="eye" x-show="!showOtp" class="w-4 h-4"></i>
                        <i data-lucide="eye-off" x-show="showOtp" x-cloak class="w-4 h-4"></i>
                    </button>
                    <button type="button" onclick="navigator.clipboard.writeText('Username: {{ $user->username }}\nOTP: {{ session('new_member')['otp'] }}'); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy', 2000)" class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold rounded-lg cursor-pointer">
                        Copy
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Profile Overview & Edit Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1: Profile Summary Card -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs">
                <div class="text-center pb-6 border-b border-slate-100">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-20 h-20 mx-auto rounded-3xl object-cover shadow-lg shadow-indigo-600/20 mb-4 border-2 border-indigo-100">
                    @else
                        <div class="w-20 h-20 mx-auto rounded-3xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-indigo-600/20 mb-4">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @endif
                    <h2 class="text-lg font-black text-slate-900">{{ $user->name }}</h2>
                    <div class="mt-1 flex items-center justify-center gap-1.5">
                        <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100">
                            @<span>{{ $user->username }}</span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                            {{ $user->role === 'tl' ? 'Team Lead' : 'Staff Member' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2 font-medium">{{ $user->designation ?? 'Operations Specialist' }}</p>
                </div>

                <!-- Metrics Overview -->
                <div class="py-4 grid grid-cols-3 gap-2 text-center border-b border-slate-100">
                    <div class="p-2.5 rounded-2xl bg-slate-50">
                        <div class="text-lg font-black text-slate-800">{{ $tasks->count() }}</div>
                        <div class="text-[10px] uppercase font-bold text-slate-400 mt-0.5">Tasks</div>
                    </div>
                    <div class="p-2.5 rounded-2xl bg-emerald-50 text-emerald-800">
                        <div class="text-lg font-black text-emerald-600">{{ $tasks->where('status', 'completed')->count() }}</div>
                        <div class="text-[10px] uppercase font-bold text-emerald-600/80 mt-0.5">Completed</div>
                    </div>
                    <div class="p-2.5 rounded-2xl bg-amber-50 text-amber-800">
                        <div class="text-lg font-black text-amber-600">{{ $tasks->where('status', 'in-progress')->count() }}</div>
                        <div class="text-[10px] uppercase font-bold text-amber-600/80 mt-0.5">Active</div>
                    </div>
                </div>

                <!-- Account Information List -->
                <div class="pt-4 space-y-3 text-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <span class="text-slate-400 font-medium">Work Email:</span>
                        <span class="font-semibold text-slate-700 break-all select-all text-right" title="{{ $user->email }}">{{ $user->email }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-medium">Mobile Number:</span>
                        <span class="font-semibold text-slate-700">{{ $user->mobile_number ?? 'Not Provided' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-medium">Joined On:</span>
                        <span class="font-semibold text-slate-700">{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-medium">Password Status:</span>
                        @if($user->must_change_password)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                Pending OTP Change
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Permanent Password Set
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 2: Edit Form & Activity Tabs -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Edit Employee Details Form Card -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="user-cog" class="w-5 h-5 text-indigo-600"></i>
                            Update Employee Details
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Modify profile attributes, unique username, and designation</p>
                    </div>
                    <span class="text-[10px] font-mono text-slate-400 uppercase">Employee ID: #{{ $user->id }}</span>
                </div>

                <form method="POST" action="{{ route($routePrefix . 'update', $user) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Full Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Unique Username</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-mono text-sm">@</span>
                                <input type="text" name="username" value="{{ old('username', $user->username) }}" class="w-full pl-8 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Work Email Address</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Mobile Number</label>
                            <input type="tel" name="mobile_number" value="{{ old('mobile_number', $user->mobile_number) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="+91 98765 43210">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Designation / Role Title</label>
                            <input type="text" name="designation" value="{{ old('designation', $user->designation) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="e.g. Operations Specialist, Senior UI Designer">
                        </div>
                    </div>

                    <div class="pt-3 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Assigned Tasks History Card -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="check-square" class="w-4 h-4 text-indigo-600"></i>
                        Assigned Tasks & Deliverables ({{ $tasks->count() }})
                    </h3>
                    <a href="{{ route('tasks.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-500">Assign New Task &rarr;</a>
                </div>

                <div class="space-y-3">
                    @forelse($tasks->take(6) as $t)
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-slate-900 truncate">{{ $t->title }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">Deadline: {{ $t->deadline->format('d M Y, h:i A') }}</div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase shrink-0 bg-slate-100 text-slate-700">
                                {{ $t->status }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">No tasks assigned to this employee yet.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>

@endsection
