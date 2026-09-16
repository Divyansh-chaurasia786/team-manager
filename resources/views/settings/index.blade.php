@extends('layouts.app')
@section('title', 'Account Settings & Security')
@section('page-title', 'Account Settings & Security')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Top Header Breadcrumb & Status Bar -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[11px] font-extrabold border border-indigo-100">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-indigo-600"></i>
                    <span>Security Console</span>
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Email OTP Protection Active</span>
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Account Settings & Profile</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Manage your credentials, contact phone, and avatar. Sensitive updates require verifying a 6-digit OTP on your registered email.
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <span class="px-3 py-1.5 rounded-2xl bg-slate-100 text-slate-700 font-extrabold text-xs uppercase tracking-wide border border-slate-200">
                {{ $user->isTL() ? 'Team Lead' : 'Staff Member' }}
            </span>
        </div>
    </div>

    <!-- Main Content 2-Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Profile Card & Photo Uploader (4 cols on lg) -->
        <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs flex flex-col items-center text-center">
                
                <!-- Avatar with Camera Overlay -->
                <div class="relative group mb-4">
                    <div class="w-28 h-28 rounded-3xl overflow-hidden border-4 border-white shadow-xl shadow-indigo-600/10 bg-gradient-to-tr from-indigo-600 via-indigo-700 to-violet-600 flex items-center justify-center text-white font-black text-3xl">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                    </div>

                    <!-- Hover Camera Overlay to trigger file chooser -->
                    <label for="profile_photo_file" class="absolute inset-0 bg-slate-900/60 rounded-3xl flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition duration-200 cursor-pointer backdrop-blur-2xs">
                        <i data-lucide="camera" class="w-6 h-6 mb-1 text-white"></i>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Change Photo</span>
                    </label>
                </div>

                <!-- Member Name & Role -->
                <h2 class="text-base sm:text-lg font-black text-slate-900 leading-snug">{{ $user->name }}</h2>
                <div class="mt-1 flex items-center gap-1.5 flex-wrap justify-center">
                    <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-100">
                        {{ $user->designation ?: ($user->isTL() ? 'Team Lead' : 'Staff Member') }}
                    </span>
                </div>

                <!-- Username Pill with Copy -->
                <div class="mt-2.5 inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-slate-50 border border-slate-200 text-xs font-mono font-bold text-slate-600">
                    <span>@<span>{{ $user->username }}</span></span>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $user->username }}'); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy', 1500)" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-sans font-bold cursor-pointer">
                        Copy
                    </button>
                </div>

                <!-- Direct Upload Form -->
                <form method="POST" action="{{ route('settings.avatar') }}" enctype="multipart/form-data" class="w-full mt-5">
                    @csrf
                    <input type="file" id="profile_photo_file" name="profile_photo" accept="image/*" class="hidden" onchange="this.form.submit()">
                    <button type="button" onclick="document.getElementById('profile_photo_file').click()" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-2 shadow-sm shadow-indigo-600/30 cursor-pointer">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        <span>Upload Profile Image</span>
                    </button>
                </form>

                <!-- Profile Info & Specs -->
                <div class="w-full mt-5 pt-4 border-t border-slate-100 text-left space-y-2.5 text-xs">
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-medium">Work Email:</span>
                        <span class="font-bold text-slate-800 select-all truncate max-w-[180px]" title="{{ $user->email }}">{{ $user->email }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-medium">Mobile Contact:</span>
                        <span class="font-bold text-slate-800">{{ $user->mobile_number ?: 'Not Set' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-medium">Joined On:</span>
                        <span class="font-bold text-slate-800">{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-medium">File Formats:</span>
                        <span class="font-semibold text-slate-700">JPG, PNG, WEBP (Max 5MB)</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="text-slate-400 font-medium">Team Sync:</span>
                        <span class="font-bold text-emerald-600 flex items-center gap-1">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Instant
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right Column: Settings & In-Place OTP Verification (8 cols on lg) -->
        <div class="lg:col-span-8 space-y-6" 
             x-data="settingsConsole({
                 hasPendingOtp: {{ ($user->security_otp && $user->pending_profile_update && ($user->security_otp_expires_at && $user->security_otp_expires_at->isFuture())) ? 'true' : 'false' }},
                 userEmail: '{{ $user->email }}',
                 pendingEmail: '{{ $user->pending_profile_update['email'] ?? $user->email }}',
                 pendingUpdates: {{ json_encode($user->pending_profile_update ?? []) }},
                 csrfToken: '{{ csrf_token() }}',
                 requestUpdateUrl: '{{ route('settings.request_update') }}',
                 verifyOtpUrl: '{{ route('settings.verify_otp') }}',
                 resendOtpUrl: '{{ route('settings.resend_otp') }}',
                 cancelPendingUrl: '{{ route('settings.cancel_pending') }}'
             })">

            <!-- Dynamic Alert Box -->
            <div x-show="alertMessage" x-cloak class="p-4 rounded-2xl border text-xs font-semibold flex items-start gap-3 transition-all"
                 :class="alertType === 'error' ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800'">
                <i :data-lucide="alertType === 'error' ? 'alert-circle' : 'check-circle-2'" class="w-4 h-4 shrink-0 mt-0.5"></i>
                <div class="flex-1" x-text="alertMessage"></div>
                <button type="button" @click="alertMessage = ''" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>

            @if ($errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <div class="font-bold mb-1 flex items-center gap-1.5">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600"></i>
                        <span>Please correct the errors:</span>
                    </div>
                    <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- =================================================================== -->
            <!-- STEP 1: CREDENTIALS & PROFILE EDIT FORM                             -->
            <!-- =================================================================== -->
            <div x-show="step === 'form'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-xs space-y-6">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Personal Information & Credentials</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Updates are verified with an OTP sent to your registered email.</p>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                    </div>
                </div>

                <form @submit.prevent="submitRequestUpdate" method="POST" action="{{ route('settings.request_update') }}" class="space-y-5">
                    @csrf

                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Full Name
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </span>
                            <input type="text" name="name" x-model="form.name" required 
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition font-semibold text-slate-800 bg-slate-50/50 focus:bg-white"
                                   placeholder="Your full name">
                        </div>
                    </div>

                    <!-- Email Address -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Registered Email Address
                            </label>
                            <span class="text-[10px] text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md font-bold border border-indigo-100">
                                OTP will be sent to your current email
                            </span>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </span>
                            <input type="email" name="email" x-model="form.email" required 
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition font-semibold text-slate-800 bg-slate-50/50 focus:bg-white"
                                   placeholder="your.email@ecofone.com">
                        </div>
                    </div>

                    <!-- Mobile Number -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Mobile Phone Number
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="phone" class="w-4 h-4"></i>
                            </span>
                            <input type="text" name="mobile_number" x-model="form.mobile_number" 
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition font-semibold text-slate-800 bg-slate-50/50 focus:bg-white"
                                   placeholder="+91 98765 43210">
                        </div>
                    </div>

                    <!-- Toggle: Change Password Section -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="key" class="w-4 h-4 text-indigo-600"></i>
                                <span class="text-xs font-black text-slate-900 uppercase tracking-wider">Account Password</span>
                            </div>
                            <button type="button" @click="showPasswordFields = !showPasswordFields" 
                                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition cursor-pointer">
                                <span x-text="showPasswordFields ? 'Hide Password Fields' : '+ Change Password'"></span>
                            </button>
                        </div>

                        <div x-show="showPasswordFields" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-[11px] font-bold text-slate-700 uppercase">New Password</label>
                                    <button type="button" @click="showNewPass = !showNewPass; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" class="text-[10px] text-slate-400 hover:text-indigo-600 flex items-center gap-1 cursor-pointer select-none">
                                        <span x-text="showNewPass ? 'Hide' : 'Show'">Show</span>
                                        <i data-lucide="eye" x-show="!showNewPass" class="w-3 h-3"></i>
                                        <i data-lucide="eye-off" x-show="showNewPass" x-cloak class="w-3 h-3"></i>
                                    </button>
                                </div>
                                <input :type="showNewPass ? 'text' : 'password'" name="new_password" x-model="form.new_password" 
                                       placeholder="Minimum 8 characters" 
                                       class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <span class="text-[10px] text-slate-400 mt-1 block">Leave empty to keep your existing password</span>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-[11px] font-bold text-slate-700 uppercase">Confirm New Password</label>
                                    <button type="button" @click="showConfirmPass = !showConfirmPass; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" class="text-[10px] text-slate-400 hover:text-indigo-600 flex items-center gap-1 cursor-pointer select-none">
                                        <span x-text="showConfirmPass ? 'Hide' : 'Show'">Show</span>
                                        <i data-lucide="eye" x-show="!showConfirmPass" class="w-3 h-3"></i>
                                        <i data-lucide="eye-off" x-show="showConfirmPass" x-cloak class="w-3 h-3"></i>
                                    </button>
                                </div>
                                <input :type="showConfirmPass ? 'text' : 'password'" name="new_password_confirmation" x-model="form.new_password_confirmation" 
                                       placeholder="Re-type new password" 
                                       class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            </div>
                        </div>
                    </div>

                    <!-- Security Authorization Box (Current Password) -->
                    <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-50/80 via-indigo-50/40 to-slate-50 border border-amber-200/80 space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-700 border border-amber-300 flex items-center justify-center shrink-0 mt-0.5">
                                <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">
                                    Current Password (Required for Security Verification)
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                    To protect your account, enter your current password. We will immediately dispatch a 6-digit OTP to <strong>{{ $user->email }}</strong>, and you will enter the OTP right here on this page to finalize the update.
                                </p>
                            </div>
                        </div>

                        <div class="relative">
                            <input :type="showCurrentPass ? 'text' : 'password'" name="current_password" x-model="form.current_password" required 
                                   placeholder="Enter your current account password" 
                                   class="w-full pl-4 pr-11 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white font-medium">
                            <button type="button" @click="showCurrentPass = !showCurrentPass; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" class="absolute right-3.5 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer select-none">
                                <i data-lucide="eye" x-show="!showCurrentPass" class="w-4 h-4"></i>
                                <i data-lucide="eye-off" x-show="showCurrentPass" x-cloak class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Action Button -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                        <span class="text-[11px] text-slate-400 flex items-center gap-1.5 font-medium">
                            <i data-lucide="lock" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <span>Protected by email OTP confirmation</span>
                        </span>

                        <button type="submit" :disabled="isLoading" 
                                class="w-full sm:w-auto justify-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-black transition shadow-md shadow-indigo-600/25 flex items-center gap-2 cursor-pointer">
                            <span x-show="!isLoading" class="flex items-center gap-2">
                                <span>Send Verification OTP</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </span>
                            <span x-show="isLoading" x-cloak class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Sending OTP...</span>
                            </span>
                        </button>
                    </div>

                </form>
            </div>

            <!-- =================================================================== -->
            <!-- STEP 2: IN-PAGE OTP VERIFICATION CONSOLE                             -->
            <!-- (Entered right here on this page after sending the OTP)             -->
            <!-- =================================================================== -->
            <div x-show="step === 'otp'" x-cloak class="bg-white rounded-3xl p-6 sm:p-8 border-2 border-indigo-500/40 shadow-xl space-y-6 animate-in fade-in zoom-in-95 duration-200">
                
                <!-- Security Header Banner -->
                <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100">
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/30 shrink-0">
                            <i data-lucide="key-round" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-black text-slate-900">Enter Verification OTP</h2>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-extrabold border border-emerald-100 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Code Dispatched</span>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                A 6-digit One-Time Password has been dispatched to <strong class="text-indigo-600" x-text="userEmail"></strong>.
                                Enter the code below to finalize and apply your updates.
                            </p>
                        </div>
                    </div>

                    <button type="button" @click="cancelUpdate" class="text-xs font-bold text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer" title="Cancel & Return">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Staged Changes Pill Summary -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-extrabold text-slate-700 uppercase tracking-wider text-[10px]">Staged Pending Updates:</span>
                        <span class="text-[11px] text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded border border-amber-200/60">
                            Valid for 10 minutes
                        </span>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap pt-1">
                        <template x-for="(value, key) in pendingUpdates" :key="key">
                            <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-700 flex items-center gap-1.5 shadow-2xs">
                                <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                <span class="capitalize" x-text="key.replace('_hashed', '').replace('_', ' ')"></span>:
                                <span class="font-mono text-indigo-600" x-text="key.includes('password') ? '••••••••' : value"></span>
                            </span>
                        </template>
                    </div>
                </div>

                <!-- OTP Input Boxes (6 Digits) -->
                <form @submit.prevent="submitVerifyOtp" method="POST" action="{{ route('settings.verify_otp') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-center text-xs font-black text-slate-800 uppercase tracking-wider mb-3">
                            Enter 6-Digit Security Code
                        </label>

                        <!-- 6 Inputs Grid with Auto-Focus and Auto-Advance -->
                        <div class="flex items-center justify-center gap-1.5 sm:gap-3 max-w-sm mx-auto" @paste="handlePaste($event)">
                            <template x-for="(digit, index) in otpDigits" :key="index">
                                <input type="text" 
                                       inputmode="numeric" 
                                       maxlength="1" 
                                       x-ref="otpInput" 
                                       x-model="otpDigits[index]" 
                                       @input="handleOtpInput(index, $event)" 
                                       @keydown="handleOtpKeydown(index, $event)" 
                                       class="w-9 h-12 sm:w-12 sm:h-14 text-center font-mono font-black text-lg sm:text-2xl rounded-xl sm:rounded-2xl border-2 border-slate-200 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-500/15 transition shadow-2xs bg-white text-slate-900 selection:bg-indigo-500 selection:text-white"
                                       required>
                            </template>
                        </div>

                        <!-- Hidden consolidated OTP input for standard form POST fallback -->
                        <input type="hidden" name="otp" :value="fullOtpCode">
                    </div>

                    <!-- Resend OTP Link with Countdown -->
                    <div class="text-center text-xs text-slate-500">
                        <span x-show="countdown > 0">
                            Resend code available in <strong class="text-indigo-600 font-mono" x-text="countdown + 's'"></strong>
                        </span>
                        <button type="button" x-show="countdown <= 0" @click="resendOtp" 
                                class="text-indigo-600 hover:text-indigo-800 font-bold underline cursor-pointer transition">
                            Didn't receive email? Resend OTP
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                        <button type="button" @click="cancelUpdate" 
                                class="w-full sm:w-auto px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                            <span>Edit Information / Cancel</span>
                        </button>

                        <button type="submit" :disabled="isLoading || fullOtpCode.length !== 6" 
                                class="w-full sm:w-auto px-7 py-2.5 bg-gradient-to-r from-emerald-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 disabled:opacity-50 text-white rounded-xl text-xs font-black transition shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2 cursor-pointer">
                            <span x-show="!isLoading" class="flex items-center gap-1.5">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                <span>Verify OTP & Save Changes</span>
                            </span>
                            <span x-show="isLoading" x-cloak class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Verifying...</span>
                            </span>
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

@push('scripts')
<script>
function settingsConsole(config) {
    return {
        step: config.hasPendingOtp ? 'otp' : 'form',
        userEmail: config.userEmail,
        pendingUpdates: config.pendingUpdates,
        showPasswordFields: false,
        showCurrentPass: false,
        showNewPass: false,
        showConfirmPass: false,
        isLoading: false,
        alertMessage: '',
        alertType: 'success',
        countdown: 0,
        timerInterval: null,
        otpDigits: ['', '', '', '', '', ''],
        form: {
            name: '{{ old('name', $user->name) }}',
            email: '{{ old('email', $user->email) }}',
            mobile_number: '{{ old('mobile_number', $user->mobile_number) }}',
            current_password: '',
            new_password: '',
            new_password_confirmation: ''
        },

        init() {
            if (this.step === 'otp') {
                this.startCountdown(60);
                this.$nextTick(() => this.focusFirstOtp());
            }
        },

        get fullOtpCode() {
            return this.otpDigits.join('');
        },

        startCountdown(seconds) {
            this.countdown = seconds;
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.timerInterval = setInterval(() => {
                if (this.countdown > 0) {
                    this.countdown--;
                } else {
                    clearInterval(this.timerInterval);
                }
            }, 1000);
        },

        focusFirstOtp() {
            const inputs = document.querySelectorAll('input[inputmode="numeric"]');
            if (inputs && inputs.length > 0) {
                inputs[0].focus();
            }
        },

        handleOtpInput(index, event) {
            const val = event.target.value;
            // Only digits
            if (!/^\d*$/.test(val)) {
                this.otpDigits[index] = '';
                return;
            }
            if (val.length > 0) {
                this.otpDigits[index] = val.slice(-1);
                // Advance focus to next input
                const inputs = document.querySelectorAll('input[inputmode="numeric"]');
                if (index < 5 && inputs[index + 1]) {
                    inputs[index + 1].focus();
                }
            }
            // If all 6 filled, auto submit
            if (this.fullOtpCode.length === 6) {
                this.submitVerifyOtp();
            }
        },

        handleOtpKeydown(index, event) {
            if (event.key === 'Backspace' && !this.otpDigits[index]) {
                const inputs = document.querySelectorAll('input[inputmode="numeric"]');
                if (index > 0 && inputs[index - 1]) {
                    inputs[index - 1].focus();
                }
            }
        },

        handlePaste(event) {
            event.preventDefault();
            const text = (event.clipboardData || window.clipboardData).getData('text').trim();
            if (/^\d{6}$/.test(text)) {
                for (let i = 0; i < 6; i++) {
                    this.otpDigits[i] = text[i];
                }
                this.$nextTick(() => this.submitVerifyOtp());
            }
        },

        async submitRequestUpdate() {
            this.isLoading = true;
            this.alertMessage = '';

            try {
                const response = await fetch(config.requestUpdateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (!response.ok) {
                    this.alertType = 'error';
                    this.alertMessage = data.message || 'Please check your inputs and try again.';
                    this.isLoading = false;
                    return;
                }

                // Transition to OTP step right here!
                this.step = 'otp';
                this.pendingUpdates = {
                    name: this.form.name,
                    email: this.form.email,
                    mobile_number: this.form.mobile_number,
                    ...(this.form.new_password ? { password: '••••••••' } : {})
                };
                this.alertType = 'success';
                this.alertMessage = data.message || 'Verification code sent to ' + this.userEmail;
                this.startCountdown(60);
                this.$nextTick(() => {
                    this.focusFirstOtp();
                    if (window.lucide) window.lucide.createIcons();
                });
            } catch (err) {
                this.alertType = 'error';
                this.alertMessage = 'Connection error. Please try again.';
            } finally {
                this.isLoading = false;
            }
        },

        async submitVerifyOtp() {
            if (this.fullOtpCode.length !== 6) return;
            this.isLoading = true;
            this.alertMessage = '';

            try {
                const response = await fetch(config.verifyOtpUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({ otp: this.fullOtpCode })
                });

                const data = await response.json();

                if (!response.ok) {
                    this.alertType = 'error';
                    this.alertMessage = data.message || 'Invalid or expired OTP code.';
                    this.isLoading = false;
                    return;
                }

                // Success! Reload or redirect
                this.alertType = 'success';
                this.alertMessage = data.message || 'Account updated successfully!';
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            } catch (err) {
                this.alertType = 'error';
                this.alertMessage = 'Connection error during verification.';
                this.isLoading = false;
            }
        },

        async resendOtp() {
            this.isLoading = true;
            try {
                const response = await fetch(config.resendOtpUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                });
                const data = await response.json();
                this.alertType = 'success';
                this.alertMessage = data.message || 'Fresh OTP code dispatched!';
                this.startCountdown(60);
            } catch (err) {
                this.alertType = 'error';
                this.alertMessage = 'Failed to resend code.';
            } finally {
                this.isLoading = false;
            }
        },

        async cancelUpdate() {
            try {
                await fetch(config.cancelPendingUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                });
            } catch (e) {}

            this.step = 'form';
            this.otpDigits = ['', '', '', '', '', ''];
            this.alertMessage = '';
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        }
    };
}
</script>
@endpush
@endsection
