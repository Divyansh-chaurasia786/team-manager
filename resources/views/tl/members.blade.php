@extends('layouts.app')
@section('title', 'Team Members')
@section('content')
@php
    $routePrefix = auth()->user()->isHR() ? 'hr.members.' : 'tl.members.';
@endphp

<div class="space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Team Members Directory</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Manage employee onboarding, designations, contact details & credentials</p>
        </div>

        <button type="button" onclick="document.getElementById('addMemberModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer border border-indigo-400/30">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Register Employee</span>
        </button>
    </div>

    @if(session('new_member'))
    <!-- New Registered Member Confirmation Banner (Zero Credentials On Screen) -->
    <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-950 via-slate-900 to-indigo-950 text-white border border-emerald-500/30 shadow-xl relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-36 h-36 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex items-center justify-between gap-4 relative z-10">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0 mt-0.5">
                    <i data-lucide="mail-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-black text-white">Employee Successfully Registered & Credentials Emailed</h3>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold">Email Dispatched</span>
                    </div>
                    <p class="text-xs text-slate-300 mt-1">
                        <strong>{{ session('new_member')['name'] }}</strong> ({{ session('new_member')['designation'] }}) has been registered. An automated welcome email containing their login details and temporary one-time password has been sent directly to <strong>{{ session('new_member')['email'] }}</strong>.
                    </p>
                    <p class="text-[11px] text-emerald-300/90 mt-1.5 flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 shrink-0"></i>
                        <span>Privacy Protocol: Credentials are never displayed on screen to team leads and are delivered exclusively to the employee's inbox.</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Team Members Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($members as $member)
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition group flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3.5 min-w-0">
                            @if($member->avatar_url)
                                <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-12 h-12 rounded-2xl object-cover shadow-md shadow-indigo-600/20 shrink-0 border border-slate-100">
                            @else
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-extrabold text-base flex items-center justify-center shadow-md shadow-indigo-600/20 shrink-0">
                                    {{ strtoupper(substr($member->name, 0, 2)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition truncate" title="{{ $member->name }}">
                                    {{ $member->name }}
                                </h3>
                                <div class="mt-1">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ $member->designation ?? 'Staff Member' }}
                                    </span>
                                </div>
                            </div>
                        </div>

@php
    $routePrefix = auth()->user()->isHR() ? 'hr.members.' : 'tl.members.';
@endphp

                        <div class="flex items-center gap-1 shrink-0">
                            <a href="{{ route($routePrefix . 'show', $member) }}" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="View & Edit Details">
                                <i data-lucide="external-link" class="w-4 h-4"></i>
                            </a>
                            <form method="POST" action="{{ route($routePrefix . 'destroy', $member) }}" onsubmit="return confirm('Remove {{ $member->name }} from team?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Delete Member">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span class="text-slate-400 text-[11px]">Tasks: <strong class="text-slate-700 font-bold">{{ $member->tasks->count() }}</strong></span>
                    <a href="{{ route($routePrefix . 'show', $member) }}" class="inline-flex items-center gap-1.5 font-bold text-indigo-600 hover:text-indigo-500 text-xs px-2.5 py-1 rounded-lg hover:bg-indigo-50 transition">
                        <span>View Details</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-12 rounded-2xl border border-slate-200 text-center text-slate-400">
                <i data-lucide="users" class="w-10 h-10 mx-auto text-slate-300 mb-2"></i>
                <p class="font-semibold text-slate-700">No team members registered yet</p>
                <p class="text-xs text-slate-400 mt-1">Click "Register Employee" above to create an employee account.</p>
            </div>
        @endforelse
    </div>

</div>

<!-- Modal: Register Employee -->
<div id="addMemberModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-4 sm:p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                    <i data-lucide="user-plus" class="w-4 h-4 text-indigo-600"></i>
                    Register New Employee
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Account credentials and OTP will be auto-generated and emailed.</p>
            </div>
            <button type="button" onclick="document.getElementById('addMemberModal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer">✕</button>
        </div>

        <form method="POST" action="{{ route($routePrefix . 'store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Full Name</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="name" class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required placeholder="e.g. Rahul Sharma" value="{{ old('name') }}">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Mobile Number</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                    </span>
                    <input type="tel" name="mobile_number" class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required placeholder="e.g. +91 98765 43210" value="{{ old('mobile_number') }}">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Designation</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="briefcase" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="designation" class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required placeholder="e.g. Senior Frontend Developer" value="{{ old('designation') }}">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </span>
                    <input type="email" name="email" class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required placeholder="rahul@ecofone.com" value="{{ old('email') }}">
                </div>
            </div>

            <!-- Security Info Note -->
            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2.5">
                <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                <div>
                    <span class="font-bold">Automated Credentials Workflow:</span>
                    <p class="text-[11px] text-amber-700 mt-0.5">
                        A <strong>unique username</strong> and a secure <strong>One-Time Password (OTP)</strong> will be automatically generated and emailed to the employee. The employee can sign in using their unique username (or email) and will be prompted to set a permanent password.
                    </p>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('addMemberModal').classList.add('hidden')" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Register & Dispatch Credentials</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection