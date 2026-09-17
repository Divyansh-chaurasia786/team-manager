<!DOCTYPE html>
<html lang="en" class="h-full bg-[#05070f] text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    
    <title>Set New Password • EcoFone App</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_icon.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        body { 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: radial-gradient(circle at 50% 15%, #0f172a 0%, #030712 100%);
            min-height: 100vh;
        }
        @keyframes floatMesh {
            0%, 100% { transform: scale(1) translateY(0); opacity: 0.35; }
            50% { transform: scale(1.15) translateY(-20px); opacity: 0.65; }
        }
        .ambient-mesh {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .ambient-mesh-1 {
            position: absolute;
            top: -120px;
            left: 50%;
            transform: translateX(-50%);
            width: 520px;
            height: 520px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(249, 115, 22, 0.15) 50%, transparent 70%);
            border-radius: 50%;
            filter: blur(100px);
            animation: floatMesh 8s ease-in-out infinite;
        }
        .auth-glass-card {
            background: rgba(17, 24, 39, 0.75);
            backdrop-filter: blur(35px);
            -webkit-backdrop-filter: blur(35px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.95), 0 0 40px -10px rgba(99, 102, 241, 0.25);
        }
        .auth-input-field {
            width: 100%;
            background: rgba(3, 7, 18, 0.85);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            padding: 14px 16px 14px 44px;
            outline: none;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.6);
            transition: all 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .auth-input-field:focus {
            border-color: #6366f1;
            background: rgba(30, 41, 59, 0.95);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.5), inset 0 2px 4px rgba(0, 0, 0, 0.5);
            transform: translateY(-2px);
        }
        .auth-input-field::placeholder {
            color: #64748b;
            font-weight: 500;
        }
        .submit-action-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            border-radius: 18px;
            font-weight: 800;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.5);
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .submit-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px -5px rgba(16, 185, 129, 0.7);
        }
        .submit-action-btn:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body class="min-h-full flex flex-col items-center justify-center py-6 px-4 relative overflow-x-hidden antialiased">
    <div class="ambient-mesh">
        <div class="ambient-mesh-1"></div>
    </div>

    <div class="w-full max-w-[440px] relative z-10 my-auto">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-white flex items-center justify-center p-2 shadow-2xl ring-4 ring-white/10">
                <img src="{{ asset('images/logo_icon.png') }}" alt="EcoFone Logo" width="40" height="40" class="w-full h-full object-contain">
            </div>

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-[11px] font-extrabold uppercase tracking-wider mb-2">
                <i data-lucide="check-shield" class="w-3.5 h-3.5 text-emerald-400"></i>
                <span>OTP Verified Reset</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                Create New <span class="text-emerald-400">Password</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                Enter the 6-digit code sent to your email. Your account will unlock and all your existing data will be preserved.
            </p>
        </div>

        <!-- Main Card -->
        <div class="auth-glass-card p-6 sm:p-8 space-y-6">
            @if($errors->any())
                <div class="p-4 rounded-2xl text-xs font-semibold bg-rose-500/15 text-rose-300 border border-rose-500/30 flex items-start gap-3 shadow-xl backdrop-blur-xl">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 mt-0.5"></i>
                    <div class="leading-relaxed flex-1">{{ $errors->first() }}</div>
                </div>
            @endif

            @if(session('success'))
                <div class="p-4 rounded-2xl text-xs font-semibold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 flex items-start gap-3 shadow-xl backdrop-blur-xl">
                    <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 mt-0.5"></i>
                    <div class="leading-relaxed flex-1">{{ session('success') }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf

                <!-- Email Input -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-extrabold text-slate-300 uppercase tracking-wider">
                        Confirm Email Address
                    </label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-500 absolute left-4 top-4"></i>
                        <input 
                            type="email" 
                            name="email" 
                            value="{{ old('email', $prefilledEmail) }}"
                            required 
                            autocapitalize="none"
                            placeholder="employee@ecofone.com" 
                            class="auth-input-field lowercase"
                        >
                    </div>
                </div>

                <!-- 6-digit OTP Input -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-extrabold text-slate-300 uppercase tracking-wider">
                            6-Digit Verification OTP
                        </label>
                        <a href="{{ route('password.request') }}" class="text-[11px] text-indigo-400 hover:text-indigo-300">
                            Resend Code
                        </a>
                    </div>
                    <div class="relative">
                        <i data-lucide="key-round" class="w-4 h-4 text-slate-500 absolute left-4 top-4"></i>
                        <input 
                            type="text" 
                            name="otp" 
                            maxlength="6"
                            pattern="[0-9]{6}"
                            required 
                            autofocus
                            placeholder="123456" 
                            class="auth-input-field tracking-[0.4em] font-mono text-center text-lg"
                        >
                    </div>
                </div>

                <!-- New Password -->
                <div class="space-y-1.5" x-data="{ showNew: false }">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-extrabold text-slate-300 uppercase tracking-wider">
                            New Password
                        </label>
                        <button 
                            type="button" 
                            @click="showNew = !showNew; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" 
                            class="text-[11px] text-slate-400 hover:text-emerald-400 flex items-center gap-1 cursor-pointer transition select-none"
                            tabindex="-1"
                        >
                            <span x-text="showNew ? 'Hide' : 'Show'">Show</span>
                            <i data-lucide="eye" x-show="!showNew" class="w-3.5 h-3.5"></i>
                            <i data-lucide="eye-off" x-show="showNew" x-cloak class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-500 absolute left-4 top-4"></i>
                        <input 
                            :type="showNew ? 'text' : 'password'" 
                            name="password" 
                            required 
                            minlength="6"
                            placeholder="At least 6 characters" 
                            class="auth-input-field pr-12"
                        >
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-extrabold text-slate-300 uppercase tracking-wider">
                        Confirm New Password
                    </label>
                    <div class="relative">
                        <i data-lucide="lock-check" class="w-4 h-4 text-slate-500 absolute left-4 top-4"></i>
                        <input 
                            type="password" 
                            name="password_confirmation" 
                            required 
                            minlength="6"
                            placeholder="Repeat new password" 
                            class="auth-input-field"
                        >
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="submit-action-btn w-full py-3.5 px-6 text-sm text-white flex items-center justify-center gap-2 cursor-pointer active:scale-95"
                >
                    <span>Save Password & Unlock Account</span>
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </button>
            </form>

            <div class="pt-4 border-t border-white/10 flex flex-col items-center justify-center gap-2 text-center text-xs">
                <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1.5 transition">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to Sign In</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (window.lucide) { lucide.createIcons(); }
        });
    </script>
</body>
</html>
