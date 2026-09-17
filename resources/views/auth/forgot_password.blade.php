<!DOCTYPE html>
<html lang="en" class="h-full bg-[#05070f] text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    
    <title>Forgot Password • EcoFone App</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_icon.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
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
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #f97316 100%);
            border-radius: 18px;
            font-weight: 800;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.5);
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .submit-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px -5px rgba(99, 102, 241, 0.7);
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

    <div class="w-full max-w-[420px] relative z-10 my-auto">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-white flex items-center justify-center p-2 shadow-2xl ring-4 ring-white/10">
                <img src="{{ asset('images/logo_icon.png') }}" alt="EcoFone Logo" width="40" height="40" class="w-full h-full object-contain">
            </div>

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 text-[11px] font-extrabold uppercase tracking-wider mb-2">
                <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-orange-400"></i>
                <span>Password Recovery</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                Reset <span class="text-orange-500">Password</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                Enter your email or username to receive a 6-digit verification code. All historical tasks and records remain 100% intact.
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

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-extrabold text-slate-300 uppercase tracking-wider">
                        Username or Email Address
                    </label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-500 absolute left-4 top-4"></i>
                        <input 
                            type="text" 
                            name="login" 
                            value="{{ old('login') }}"
                            required 
                            autofocus
                            autocapitalize="none"
                            autocorrect="off"
                            spellcheck="false"
                            placeholder="e.g. employee@ecofone.com or username" 
                            class="auth-input-field lowercase"
                        >
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="submit-action-btn w-full py-3.5 px-6 text-sm text-white flex items-center justify-center gap-2 cursor-pointer active:scale-95"
                >
                    <span>Send Verification Code</span>
                    <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </form>

            <div class="pt-4 border-t border-white/10 flex flex-col items-center justify-center gap-2 text-center text-xs">
                <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1.5 transition">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to Sign In</span>
                </a>
                <span class="text-[10px] text-slate-500">Zero data lapse • Full encryption guarantee</span>
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
