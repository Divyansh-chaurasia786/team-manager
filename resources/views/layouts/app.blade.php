<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>EcoFone App - @yield('title', 'Operations')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_icon.png') }}?v={{ time() }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN (Same as HRMS Portal) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons (Same as HRMS Portal) -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Twemoji for Crisp 3D Glossy Emojis (Twitter/Discord Standard) -->
    <script src="https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js" crossorigin="anonymous"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        img.emoji {
            height: 1.25em !important;
            width: 1.25em !important;
            margin: 0 .08em 0 .08em !important;
            vertical-align: -0.2em !important;
            display: inline-block !important;
            pointer-events: none !important;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 antialiased text-slate-800 flex flex-col {{ request()->routeIs('thoughts.*') ? 'p-0 overflow-hidden' : 'pb-16 lg:pb-0' }}" x-data="{ mobileMenuOpen: false }">

    <!-- TOP HEADER NAVBAR (EcoFone HRMS Portal Theme) -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs {{ request()->routeIs('thoughts.*') ? 'hidden md:block' : '' }}">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-2">

                <!-- Left: Brand Logo & Desktop Navigation Links -->
                <div class="flex items-center gap-3 sm:gap-6 lg:gap-7 min-w-0">
                    <a href="{{ auth()->user()->role === 'ceo' ? route('ceo.dashboard') : (auth()->user()->role === 'hr' ? route('hr.dashboard') : (auth()->user()->role === 'tl' ? route('tl.dashboard') : route('member.dashboard'))) }}" class="flex items-center gap-2.5 sm:gap-3 group shrink-0 min-w-0">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                            <img src="{{ asset('images/logo_icon.png') }}" alt="EcoFone App" class="w-full h-full object-contain drop-shadow-xs">
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm sm:text-base font-black text-slate-900 tracking-tight flex items-center gap-1 leading-none">
                                <span>EcoFone</span> <span class="text-orange-500 font-black">App</span>
                            </div>
                            <div class="text-[9px] sm:text-[10px] text-emerald-600 font-bold tracking-wider uppercase mt-0.5 truncate">
                                @if(auth()->user()->role === 'ceo')
                                    CEO EXECUTIVE
                                @elseif(auth()->user()->role === 'hr')
                                    HR PORTAL
                                @elseif(auth()->user()->role === 'tl')
                                    TEAM LEAD
                                @else
                                    MEMBER DESK
                                @endif
                            </div>
                        </div>
                    </a>

                    <!-- Desktop Navigation Links -->
                    <nav class="hidden lg:flex items-center gap-1">
                        @if(auth()->user()->role === 'ceo')
                            {{-- CEO Nav --}}
                            <a href="{{ route('ceo.dashboard') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('ceo.dashboard') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>Executive</span>
                            </a>
                            <a href="{{ route('tasks.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('tasks.*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="check-square" class="w-4 h-4"></i>
                                <span>Tasks</span>
                            </a>
                            <a href="{{ route('shoots.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('shoots.*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="video" class="w-4 h-4"></i>
                                <span>Shoots</span>
                            </a>
                            <a href="{{ route('attendance.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('attendance.*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                <span>Attendance</span>
                            </a>
                            <a href="{{ route('leaves.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('leaves.*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="calendar-off" class="w-4 h-4"></i>
                                <span>Leaves</span>
                            </a>
                            <a href="{{ route('upload.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('upload.*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="cloud-upload" class="w-4 h-4"></i>
                                <span>Drive</span>
                            </a>
                            <a href="{{ route('my-tasks.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('my-tasks.*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                                <span>My Tasks</span>
                            </a>
                            <a href="{{ route('history.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('history.*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="history" class="w-4 h-4"></i>
                                <span>Audit Log</span>
                            </a>

                        @elseif(auth()->user()->role === 'hr')
                            {{-- HR Nav --}}
                            <a href="{{ route('hr.dashboard') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('hr.dashboard') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>HR Desk</span>
                            </a>
                            <a href="{{ route('hr.members') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('hr.members*') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="users" class="w-4 h-4"></i>
                                <span>Members</span>
                            </a>
                            <a href="{{ route('attendance.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('attendance.*') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                <span>Attendance</span>
                            </a>
                            <a href="{{ route('leaves.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('leaves.*') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="calendar-off" class="w-4 h-4"></i>
                                <span>Leaves</span>
                            </a>
                            <a href="{{ route('my-tasks.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('my-tasks.*') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                                <span>My Tasks</span>
                            </a>
                            <a href="{{ route('history.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('history.*') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="history" class="w-4 h-4"></i>
                                <span>Logs</span>
                            </a>

                        @elseif(auth()->user()->role === 'tl')
                            <a href="{{ route('tl.dashboard') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('tl.dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>Dashboard</span>
                            </a>

                            <!-- Dropdown 1: Members & Attendance -->
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open" type="button" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer {{ (request()->routeIs('tl.members*') || request()->routeIs('attendance.*')) ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                    <i data-lucide="users" class="w-4 h-4"></i>
                                    <span>Team & Attendance</span>
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }"></i>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute left-0 mt-1.5 w-52 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 z-50">
                                    <a href="{{ route('tl.members') }}" class="px-3.5 py-2 text-xs font-bold transition flex items-center gap-2.5 {{ request()->routeIs('tl.members*') ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                            <i data-lucide="users" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div>Team Members</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Manage & view roster</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('attendance.index') }}" class="px-3.5 py-2 text-xs font-bold transition flex items-center gap-2.5 {{ request()->routeIs('attendance.*') ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                            <i data-lucide="user-check" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div>Attendance</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Daily check-in & logs</div>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Dropdown: Tasks & Planning -->
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open" type="button" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer {{ (request()->routeIs('tasks.*') || request()->routeIs('plans.*') || request()->routeIs('shoots.*')) ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                    <i data-lucide="check-square" class="w-4 h-4"></i>
                                    <span>Tasks & Planning</span>
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }"></i>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute left-0 mt-1.5 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 z-50">
                                    <a href="{{ route('tasks.index') }}" class="px-3.5 py-2 text-xs font-bold transition flex items-center gap-2.5 {{ request()->routeIs('tasks.*') ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                            <i data-lucide="check-square" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div>Tasks</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Assignment & deliverables</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('plans.index') }}" class="px-3.5 py-2 text-xs font-bold transition flex items-center gap-2.5 {{ request()->routeIs('plans.*') ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                            <i data-lucide="calendar-range" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div>Weekly Plan</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Weekly roadmap & itineraries</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('shoots.index') }}" class="px-3.5 py-2 text-xs font-bold transition flex items-center gap-2.5 {{ request()->routeIs('shoots.*') ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <div class="w-7 h-7 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center">
                                            <i data-lucide="video" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div>Content & Shoots</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Instagram & YouTube production</div>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <a href="{{ route('leaves.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('leaves.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="calendar-off" class="w-4 h-4"></i>
                                <span>Leaves</span>
                            </a>
                            <a href="{{ route('upload.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('upload.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="cloud-upload" class="w-4 h-4"></i>
                                <span>Drive</span>
                            </a>
                            <a href="{{ route('my-tasks.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('my-tasks.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                                <span>My Tasks</span>
                            </a>
                            <a href="{{ route('history.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('history.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="history" class="w-4 h-4"></i>
                                <span>History</span>
                            </a>
                        @else
                            {{-- Member Nav --}}
                            <a href="{{ route('member.dashboard') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('member.dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="{{ route('attendance.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('attendance.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                <span>My Attendance</span>
                            </a>

                            <!-- Dropdown for Members: Tasks & Planning -->
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open" type="button" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer {{ (request()->routeIs('tasks.*') || request()->routeIs('plans.*') || request()->routeIs('shoots.*')) ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                    <i data-lucide="check-square" class="w-4 h-4"></i>
                                    <span>Tasks & Planning</span>
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }"></i>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute left-0 mt-1.5 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 z-50">
                                    <a href="{{ route('tasks.index') }}" class="px-3.5 py-2 text-xs font-bold transition flex items-center gap-2.5 {{ request()->routeIs('tasks.*') ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                            <i data-lucide="check-square" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div>Deliverables</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Assigned tasks & updates</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('plans.index') }}" class="px-3.5 py-2 text-xs font-bold transition flex items-center gap-2.5 {{ request()->routeIs('plans.*') ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                            <i data-lucide="calendar-range" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div>Weekly Plan</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Weekly roadmap & goals</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('shoots.index') }}" class="px-3.5 py-2 text-xs font-bold transition flex items-center gap-2.5 {{ request()->routeIs('shoots.*') ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <div class="w-7 h-7 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center">
                                            <i data-lucide="video" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div>Content & Shoots</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Social media shoots & scripts</div>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <a href="{{ route('leaves.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('leaves.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="calendar-off" class="w-4 h-4"></i>
                                <span>Apply Leave</span>
                            </a>
                            <a href="{{ route('upload.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('upload.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="cloud-upload" class="w-4 h-4"></i>
                                <span>Drive</span>
                            </a>
                            <a href="{{ route('my-tasks.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('my-tasks.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                                <span>My Tasks</span>
                            </a>
                            <a href="{{ route('history.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('history.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <i data-lucide="history" class="w-4 h-4"></i>
                                <span>History</span>
                            </a>
                        @endif
                    </nav>
                </div>

                <!-- Right: User Avatar Dropdown & Mobile Hamburger Toggle -->
                <div class="flex items-center gap-2">
                    <!-- User Avatar & Profile Dropdown (Desktop & Mobile) -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" type="button" class="flex items-center gap-2.5 p-1 sm:pr-3 bg-white hover:bg-slate-50/80 border border-slate-200/90 rounded-full shadow-2xs hover:shadow-xs hover:border-indigo-200/80 transition-all duration-150 cursor-pointer group">
                            <div class="relative shrink-0">
                                @if(auth()->user()->avatar_url)
                                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-indigo-500/20 shadow-2xs">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-600 text-white font-black text-xs flex items-center justify-center shadow-xs ring-2 ring-indigo-400/20">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white"></span>
                            </div>
                            <div class="text-left hidden md:block">
                                <div class="text-xs font-bold text-slate-800 leading-none group-hover:text-indigo-600 transition-colors">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] text-slate-400 font-semibold leading-none mt-1">
                                    {{ auth()->user()->designation ?: (auth()->user()->role === 'ceo' ? 'Chief Executive Officer' : (auth()->user()->role === 'hr' ? 'HR Manager' : (auth()->user()->role === 'tl' ? 'Team Lead' : 'Staff Member'))) }}
                                </div>
                            </div>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform hidden sm:block group-hover:text-slate-600" :class="{ 'rotate-180': open }"></i>
                        </button>

                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email }}</p>
                            </div>

                            <a href="{{ route('settings.index') }}" class="px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 transition">
                                <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <i data-lucide="settings" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div>Account Settings</div>
                                    <div class="text-[10px] text-slate-400 font-normal">Profile & Password</div>
                                </div>
                            </a>

                            <div class="my-1 border-t border-slate-100"></div>

                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center gap-2.5 transition cursor-pointer">
                                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                                        <i data-lucide="log-out" class="w-4 h-4"></i>
                                    </div>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDE-OVER MOBILE NAVIGATION DRAWER -->
        <div x-show="mobileMenuOpen" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
            <!-- Dimmed Backdrop -->
            <div x-show="mobileMenuOpen" 
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileMenuOpen = false" 
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

            <!-- Slide-over Drawer Panel -->
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="fixed inset-y-0 right-0 z-50 w-full max-w-xs sm:max-w-sm bg-white shadow-2xl flex flex-col justify-between overflow-y-auto">
                
                <!-- Drawer Header -->
                <div>
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <div class="flex items-center gap-2.5">
                            <img src="{{ asset('images/logo_icon.png') }}" alt="EcoFone" class="w-8 h-8 object-contain">
                            <div>
                                <span class="font-black text-slate-900 text-sm tracking-tight">EcoFone <span class="text-orange-500">App</span></span>
                                <span class="block text-[9px] font-extrabold uppercase text-emerald-600 tracking-wider">
                                    {{ auth()->user()->role === 'ceo' ? 'Executive Portal' : (auth()->user()->role === 'hr' ? 'HR Portal' : (auth()->user()->role === 'tl' ? 'Team Lead Portal' : 'Member Portal')) }}
                                </span>
                            </div>
                        </div>
                        <button @click="mobileMenuOpen = false" type="button" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition cursor-pointer">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- User Information Card -->
                    <div class="p-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-indigo-50/20">
                        <div class="flex items-center gap-3">
                            @if(auth()->user()->avatar_url)
                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full object-cover border border-indigo-200 shadow-xs">
                            @else
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-black text-sm flex items-center justify-center shadow-xs">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="text-xs font-black text-slate-900 truncate">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] text-slate-400 truncate">{{ auth()->user()->email }}</div>
                                <div class="mt-1">
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase tracking-wider {{ auth()->user()->role === 'ceo' ? 'bg-violet-100 text-violet-700' : (auth()->user()->role === 'hr' ? 'bg-teal-100 text-teal-700' : 'bg-indigo-100 text-indigo-700') }}">
                                        {{ auth()->user()->designation ?: strtoupper(auth()->user()->role) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Links Section in Drawer -->
                    <div class="p-3 space-y-1">
                        <div class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Navigation Menu</div>

                        @if(auth()->user()->role === 'ceo')
                            <a href="{{ route('ceo.dashboard') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('ceo.dashboard') ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>Executive Dashboard</span>
                            </a>
                            <a href="{{ route('tasks.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('tasks.*') ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="check-square" class="w-4 h-4"></i>
                                <span>Tasks & Delegation</span>
                            </a>
                            <a href="{{ route('shoots.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('shoots.*') ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="video" class="w-4 h-4"></i>
                                <span>Content & Shoots</span>
                            </a>
                            <a href="{{ route('plans.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('plans.*') ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="calendar-range" class="w-4 h-4"></i>
                                <span>Weekly Roadmaps</span>
                            </a>
                            <a href="{{ route('attendance.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('attendance.*') ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                <span>Attendance Register</span>
                            </a>
                            <a href="{{ route('leaves.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('leaves.*') ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="calendar-off" class="w-4 h-4"></i>
                                <span>Leave Approvals</span>
                            </a>
                            <a href="{{ route('upload.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('upload.*') ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="cloud-upload" class="w-4 h-4"></i>
                                <span>Google Drive</span>
                            </a>
                            <a href="{{ route('my-tasks.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('my-tasks.*') ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                                <span>My Tasks</span>
                            </a>
                            <a href="{{ route('history.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('history.*') ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="history" class="w-4 h-4"></i>
                                <span>Activity Audit Log</span>
                            </a>

                        @elseif(auth()->user()->role === 'hr')
                            <a href="{{ route('hr.dashboard') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('hr.dashboard') ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>HR Desk</span>
                            </a>
                            <a href="{{ route('my-tasks.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('my-tasks.*') ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                                <span>My Tasks</span>
                            </a>
                            <a href="{{ route('history.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('history.*') ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="history" class="w-4 h-4"></i>
                                <span>System Audit Logs</span>
                            </a>
                            <a href="{{ route('hr.members') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('hr.members*') ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="users" class="w-4 h-4"></i>
                                <span>Staff Roster</span>
                            </a>
                            <a href="{{ route('attendance.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('attendance.*') ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                <span>Attendance Register</span>
                            </a>
                            <a href="{{ route('leaves.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('leaves.*') ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="calendar-off" class="w-4 h-4"></i>
                                <span>Leave Approvals</span>
                            </a>

                        @elseif(auth()->user()->role === 'tl')
                            <a href="{{ route('tl.dashboard') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('tl.dashboard') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>Team Dashboard</span>
                            </a>
                            <a href="{{ route('my-tasks.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('my-tasks.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                                <span>My Tasks</span>
                            </a>
                            <a href="{{ route('history.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('history.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="history" class="w-4 h-4"></i>
                                <span>Activity History</span>
                            </a>
                            <a href="{{ route('tl.members') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('tl.members*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="users" class="w-4 h-4"></i>
                                <span>Team Members</span>
                            </a>
                            <a href="{{ route('tasks.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('tasks.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="check-square" class="w-4 h-4"></i>
                                <span>Tasks & Deliverables</span>
                            </a>
                            <a href="{{ route('shoots.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('shoots.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="video" class="w-4 h-4"></i>
                                <span>Content & Shoots</span>
                            </a>
                            <a href="{{ route('plans.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('plans.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="calendar-range" class="w-4 h-4"></i>
                                <span>Weekly Plans</span>
                            </a>
                            <a href="{{ route('attendance.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('attendance.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                <span>Attendance Roster</span>
                            </a>
                            <a href="{{ route('leaves.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('leaves.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="calendar-off" class="w-4 h-4"></i>
                                <span>Leave Approvals</span>
                            </a>
                            <a href="{{ route('upload.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('upload.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="cloud-upload" class="w-4 h-4"></i>
                                <span>Google Drive</span>
                            </a>

                        @else
                            <a href="{{ route('member.dashboard') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('member.dashboard') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>My Dashboard</span>
                            </a>
                            <a href="{{ route('my-tasks.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('my-tasks.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                                <span>My Tasks</span>
                            </a>
                            <a href="{{ route('history.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('history.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="history" class="w-4 h-4"></i>
                                <span>My Activity Log</span>
                            </a>
                            <a href="{{ route('tasks.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('tasks.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="check-square" class="w-4 h-4"></i>
                                <span>My Deliverables</span>
                            </a>
                            <a href="{{ route('shoots.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('shoots.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="video" class="w-4 h-4"></i>
                                <span>Assigned Shoots</span>
                            </a>
                            <a href="{{ route('plans.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('plans.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="calendar-range" class="w-4 h-4"></i>
                                <span>My Weekly Plan</span>
                            </a>
                            <a href="{{ route('attendance.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('attendance.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                <span>My Attendance</span>
                            </a>
                            <a href="{{ route('leaves.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('leaves.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="calendar-off" class="w-4 h-4"></i>
                                <span>Apply for Leave</span>
                            </a>
                            <a href="{{ route('upload.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 {{ request()->routeIs('upload.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="cloud-upload" class="w-4 h-4"></i>
                                <span>Google Drive</span>
                            </a>
                        @endif

                        <a href="{{ route('thoughts.index') }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-3 text-slate-700 hover:bg-slate-100">
                            <i data-lucide="message-square" class="w-4 h-4 text-indigo-600"></i>
                            <span>Team Chat & Thoughts Hub</span>
                        </a>
                    </div>
                </div>

                <!-- Drawer Footer Actions -->
                <div class="p-4 border-t border-slate-100 bg-slate-50 space-y-2">
                    <a href="{{ route('settings.index') }}" class="w-full px-3 py-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 transition flex items-center justify-center gap-2">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                        <span>Account Settings</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="w-full px-3 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl text-xs font-bold text-rose-600 transition flex items-center justify-center gap-2 cursor-pointer">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </header>

    <!-- MAIN PAGE CONTAINER -->
    <main class="flex-grow w-full mx-auto {{ request()->routeIs('thoughts.*') ? 'p-0 max-w-none h-[100dvh] md:h-[calc(100vh_-_4rem)] md:max-w-7xl md:px-4 lg:px-6 md:py-3.5 overflow-hidden' : 'max-w-7xl px-3 sm:px-6 lg:px-8 py-4 sm:py-6' }} flex flex-col min-h-0">
        @unless(request()->routeIs('thoughts.*'))
            <!-- Toast Alerts -->
            @if(session('success'))
                <div class="mb-4 sm:mb-6 p-3.5 sm:p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 text-xs font-bold flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2 min-w-0">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span class="truncate">{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 p-1 shrink-0">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 sm:mb-6 p-3.5 sm:p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-800 text-xs font-bold flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                        <span class="break-words leading-relaxed">{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 p-1 shrink-0">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif
        @endunless

        @yield('content')
    </main>

    @unless(request()->routeIs('thoughts.*'))
    <!-- MOBILE BOTTOM QUICK-BAR (Fixed, thumb-friendly on all mobile phones) -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 py-1 px-2 shadow-lg flex items-center justify-around">
        @if(auth()->user()->role === 'ceo')
            <a href="{{ route('ceo.dashboard') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('ceo.dashboard') ? 'text-violet-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Desk</span>
            </a>
            <a href="{{ route('tasks.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('tasks.*') ? 'text-violet-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="check-square" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Tasks</span>
            </a>
            <a href="{{ route('shoots.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('shoots.*') ? 'text-violet-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="video" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Shoots</span>
            </a>
            <a href="{{ route('attendance.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('attendance.*') ? 'text-violet-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="user-check" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Attendance</span>
            </a>
        @elseif(auth()->user()->role === 'hr')
            <a href="{{ route('hr.dashboard') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('hr.dashboard') ? 'text-teal-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">HR Desk</span>
            </a>
            <a href="{{ route('hr.members') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('hr.members*') ? 'text-teal-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Members</span>
            </a>
            <a href="{{ route('attendance.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('attendance.*') ? 'text-teal-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="user-check" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Attendance</span>
            </a>
            <a href="{{ route('leaves.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('leaves.*') ? 'text-teal-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="calendar-off" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Leaves</span>
            </a>
        @elseif(auth()->user()->role === 'tl')
            <a href="{{ route('tl.dashboard') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('tl.dashboard') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Home</span>
            </a>
            <a href="{{ route('tasks.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('tasks.*') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="check-square" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Tasks</span>
            </a>
            <a href="{{ route('shoots.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('shoots.*') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="video" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Shoots</span>
            </a>
            <a href="{{ route('attendance.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('attendance.*') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="user-check" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Roster</span>
            </a>
        @else
            <a href="{{ route('member.dashboard') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('member.dashboard') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Home</span>
            </a>
            <a href="{{ route('tasks.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('tasks.*') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="check-square" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Tasks</span>
            </a>
            <a href="{{ route('shoots.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('shoots.*') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="video" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Shoots</span>
            </a>
            <a href="{{ route('attendance.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('attendance.*') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 font-medium' }}">
                <i data-lucide="user-check" class="w-5 h-5"></i>
                <span class="text-[10px] leading-none">Attendance</span>
            </a>
        @endif

        <a href="{{ route('my-tasks.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl transition {{ request()->routeIs('my-tasks.*') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 font-medium' }}">
            <i data-lucide="notebook-pen" class="w-5 h-5"></i>
            <span class="text-[10px] leading-none">My Tasks</span>
        </a>

        <button @click="mobileMenuOpen = true; $nextTick(() => lucide.createIcons())" type="button" class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-xl text-slate-500 hover:text-slate-900 transition cursor-pointer">
            <i data-lucide="menu" class="w-5 h-5"></i>
            <span class="text-[10px] leading-none font-semibold">More</span>
        </button>
    </nav>
    @endunless

    <!-- FLOATING CHAT & THOUGHTS HUB BUTTON (Adjusted for mobile bottom bar) -->
    @auth
        @unless(request()->routeIs('thoughts.*'))
            @php
                $unreadChatCount = auth()->user()->unreadThoughtsCount();
            @endphp
            <div class="fixed bottom-20 sm:bottom-6 right-4 sm:right-6 z-40 group">
                <a href="{{ route('thoughts.index') }}" 
                   class="relative flex items-center justify-center w-14 h-14 rounded-full bg-gradient-to-r from-indigo-600 via-indigo-700 to-violet-600 text-white shadow-xl shadow-indigo-600/35 hover:shadow-2xl hover:shadow-indigo-600/50 hover:scale-105 active:scale-95 transition-all duration-200 border-2 border-white/20 cursor-pointer"
                   title="Open Team Chat & Thoughts Hub">
                    
                    <!-- Unread Messages Badge / Active Status Container -->
                    <div id="floatingChatBadgeContainer">
                        @if($unreadChatCount > 0)
                            <span id="floatingChatUnreadBadge" class="absolute -top-1.5 -right-1.5 flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full bg-rose-600 text-white text-[10px] font-black shadow-md border-2 border-white animate-bounce">
                                {{ $unreadChatCount > 99 ? '99+' : $unreadChatCount }}
                            </span>
                        @else
                            <span id="floatingChatOnlineDot" class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white"></span>
                            </span>
                        @endif
                    </div>
                    
                    <i data-lucide="message-square" class="w-6 h-6 transition-transform group-hover:rotate-6"></i>
                </a>

                <!-- Hover tooltip pill -->
                <div class="absolute bottom-16 right-0 mb-1 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 translate-y-2 group-hover:translate-y-0">
                    <div class="bg-slate-900 text-white text-xs font-bold py-1.5 px-3 rounded-xl shadow-lg whitespace-nowrap flex items-center gap-1.5 border border-slate-700">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-indigo-400"></i>
                        <span>Team Chat & Thoughts</span>
                        <span id="floatingChatTooltipCount" class="{{ $unreadChatCount > 0 ? '' : 'hidden' }} px-1.5 py-0.2 rounded-full bg-rose-600 text-[10px] font-black">
                            {{ $unreadChatCount }} unread
                        </span>
                    </div>
                </div>
            </div>

            <!-- Dynamic Unread Messages Background Polling -->
            <script>
                (function() {
                    async function checkUnreadChatMessages() {
                        if (document.hidden) return;
                        try {
                            const res = await fetch('{{ route("thoughts.unread_count") }}', {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (!res.ok) return;
                            const data = await res.json();
                            const container = document.getElementById('floatingChatBadgeContainer');
                            const tooltipCount = document.getElementById('floatingChatTooltipCount');
                            if (!container) return;

                            if (data.unread_count > 0) {
                                const displayCount = data.unread_count > 99 ? '99+' : data.unread_count;
                                container.innerHTML = `
                                    <span id="floatingChatUnreadBadge" class="absolute -top-1.5 -right-1.5 flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full bg-rose-600 text-white text-[10px] font-black shadow-md border-2 border-white animate-bounce">
                                        ${displayCount}
                                    </span>
                                `;
                                if (tooltipCount) {
                                    tooltipCount.textContent = `${data.unread_count} unread`;
                                    tooltipCount.classList.remove('hidden');
                                }
                            } else {
                                container.innerHTML = `
                                    <span id="floatingChatOnlineDot" class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white"></span>
                                    </span>
                                `;
                                if (tooltipCount) {
                                    tooltipCount.classList.add('hidden');
                                }
                            }
                        } catch (e) {}
                    }
                    // Poll unread chat messages every 30 seconds (paused when tab is hidden)
                    setInterval(checkUnreadChatMessages, 30000);
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden) checkUnreadChatMessages();
                    });
                })();
            </script>
        @endunless
    @endauth

    <!-- ENTERPRISE FOOTER -->
    <footer class="mt-auto border-t border-slate-200 bg-white py-5 text-center text-xs text-slate-400">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-700">EcoFone App</span>
                <span>&bull;</span>
                <span>Automated Workforce & Cloud Operations</span>
            </div>
            <div class="text-[11px] text-slate-400">
                &copy; {{ date('Y') }} EcoFone Technologies &bull; Luxury within reach
            </div>
        </div>
    </footer>

    <!-- Initialize Lucide Icons -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (window.lucide) { lucide.createIcons(); }
        });
    </script>
    @stack('scripts')
</body>
</html>