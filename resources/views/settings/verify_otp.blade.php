@extends('layouts.app')
@section('title', 'Verify Security OTP')
@section('page-title', 'Verify Security OTP')

@section('content')
<div class="max-w-md mx-auto py-6">
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-200/80 text-center">
        <!-- Shield Icon -->
        <div class="w-16 h-16 rounded-3xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-4 shadow-sm border border-indigo-100">
            <i data-lucide="shield-check" class="w-8 h-8"></i>
        </div>

        <h2 class="text-xl font-black text-slate-900 tracking-tight">Enter Verification Code</h2>
        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            We sent a 6-digit One-Time Password (OTP) to your registered email:
            <strong class="text-slate-800 block mt-0.5">{{ $user->email }}</strong>
        </p>

        <!-- Validation error -->
        @if ($errors->any())
            <div class="mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('settings.verify_otp') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">
                    6-Digit Security OTP
                </label>
                <input 
                    type="text" 
                    name="otp" 
                    maxlength="6" 
                    pattern="[0-9]{6}" 
                    inputmode="numeric" 
                    required 
                    autofocus 
                    placeholder="••••••" 
                    class="w-full text-center text-3xl font-mono font-black tracking-widest py-3 px-4 rounded-2xl border-2 border-indigo-200 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-500/15 transition text-slate-900 placeholder:text-slate-300">
            </div>

            <!-- Staged Updates Summary -->
            @if(!empty($user->pending_profile_update))
                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 text-left text-xs space-y-1">
                    <span class="text-[10px] font-black uppercase text-slate-400 block tracking-wider">Pending Changes</span>
                    <div class="text-slate-700 font-semibold">
                        Name: <span class="font-normal">{{ $user->pending_profile_update['name'] }}</span>
                    </div>
                    <div class="text-slate-700 font-semibold">
                        Email: <span class="font-normal">{{ $user->pending_profile_update['email'] }}</span>
                    </div>
                    @if(!empty($user->pending_profile_update['mobile_number']))
                        <div class="text-slate-700 font-semibold">
                            Mobile: <span class="font-normal">{{ $user->pending_profile_update['mobile_number'] }}</span>
                        </div>
                    @endif
                    @if(isset($user->pending_profile_update['new_password_hashed']))
                        <div class="text-indigo-600 font-semibold">
                            Password: <span class="font-normal">Will be updated</span>
                        </div>
                    @endif
                </div>
            @endif

            <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-wider rounded-xl transition shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2 cursor-pointer">
                <span>Confirm & Update Profile</span>
                <i data-lucide="check" class="w-4 h-4"></i>
            </button>
        </form>

        <!-- Resend OTP form -->
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-400">Didn't receive code?</span>
            <form method="POST" action="{{ route('settings.resend_otp') }}" class="m-0">
                @csrf
                <button type="submit" class="text-indigo-600 hover:text-indigo-800 font-bold transition flex items-center gap-1 cursor-pointer">
                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                    <span>Resend OTP</span>
                </button>
            </form>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('settings.index') }}" class="text-[11px] text-slate-400 hover:text-slate-600 font-semibold">
                &larr; Cancel and return to settings
            </a>
        </div>
    </div>
</div>
@endsection
