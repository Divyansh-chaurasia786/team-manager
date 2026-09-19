@extends('layouts.app')
@section('title', 'Access Restricted - EcoFone')

@section('content')
<div class="max-w-md mx-auto py-16 px-4 text-center">
    <div class="w-16 h-16 rounded-3xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center mx-auto mb-4 shadow-sm">
        <i data-lucide="shield-alert" class="w-8 h-8"></i>
    </div>
    
    <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-800 text-xs font-black uppercase tracking-wider border border-rose-300">
        Access Restricted (403)
    </span>

    <h2 class="text-xl font-black text-slate-900 mt-3">Action Not Authorized</h2>
    
    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
        {{ $exception->getMessage() ?: 'You do not have administrative permission to view or modify this resource.' }}
    </p>

    <div class="mt-6 flex items-center justify-center gap-3">
        <a href="javascript:history.back()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Go Back</span>
        </a>
        <a href="{{ auth()->check() ? (auth()->user()->isTL() ? route('tl.dashboard') : (auth()->user()->role === 'ceo' ? route('ceo.dashboard') : (auth()->user()->role === 'hr' ? route('hr.dashboard') : route('member.dashboard')))) : url('/') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
            <i data-lucide="home" class="w-3.5 h-3.5"></i>
            <span>Dashboard</span>
        </a>
    </div>
</div>
@endsection
