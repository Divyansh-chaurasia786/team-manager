@extends('layouts.app')
@section('title', 'Content & Shoot Production Planner')
@section('page-title', 'Content & Shoot Planner')

@section('content')
<div class="space-y-6" x-data="{ 
    showScheduleModal: false,
    assignCrewModal: false,
    assignTarget: { id: null, title: '', manager_id: '', camera_id: '', model_id: '', editor_id: '', other_crew: '' }
}">

    <!-- Top Header Bar -->
    <div class="bg-white rounded-3xl p-4 sm:p-6 border border-slate-200/90 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-pink-50 text-pink-700 text-[11px] font-extrabold border border-pink-100">
                    <i data-lucide="video" class="w-3.5 h-3.5 text-pink-600"></i>
                    <span>Production Console</span>
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[11px] font-bold border border-indigo-100">
                    <i data-lucide="share-2" class="w-3 h-3 text-indigo-600"></i>
                    <span>Instagram & YouTube Pipeline</span>
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Social Media Shoots & Scripts</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Plan Instagram reels, YouTube videos/shorts, assign camera & models, write scripts, and track shoot schedules from planning to publishing.
            </p>
        </div>

        @if(auth()->user()->isTL())
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <button @click="showScheduleModal = true" type="button" class="w-full sm:w-auto justify-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition flex items-center gap-2 shadow-md shadow-indigo-600/25 cursor-pointer">
                <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                <span>Schedule New Shoot</span>
            </button>
        </div>
        @endif
    </div>

    <!-- Production Statistics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <a href="{{ route('shoots.index', ['tab' => 'all']) }}" class="bg-white p-4 rounded-2xl border transition shadow-2xs hover:border-indigo-300 {{ $tab === 'all' ? 'ring-2 ring-indigo-500 border-indigo-500' : 'border-slate-200' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] uppercase font-bold text-slate-400">Total Shoots</span>
                <i data-lucide="film" class="w-4 h-4 text-slate-400"></i>
            </div>
            <div class="text-xl font-black text-slate-900 mt-2">{{ $stats['total'] }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">All productions</div>
        </a>

        <a href="{{ route('shoots.index', ['tab' => 'scheduled']) }}" class="bg-white p-4 rounded-2xl border transition shadow-2xs hover:border-sky-300 {{ $tab === 'scheduled' ? 'ring-2 ring-sky-500 border-sky-500' : 'border-slate-200' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] uppercase font-bold text-sky-600">Scheduled</span>
                <i data-lucide="calendar-check" class="w-4 h-4 text-sky-500"></i>
            </div>
            <div class="text-xl font-black text-sky-700 mt-2">{{ $stats['scheduled'] }}</div>
            <div class="text-[10px] text-sky-600 mt-0.5">Ready on set</div>
        </a>

        <a href="{{ route('shoots.index', ['tab' => 'shooting']) }}" class="bg-white p-4 rounded-2xl border transition shadow-2xs hover:border-amber-300 {{ $tab === 'shooting' ? 'ring-2 ring-amber-500 border-amber-500' : 'border-slate-200' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] uppercase font-bold text-amber-700">Shooting</span>
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
            </div>
            <div class="text-xl font-black text-amber-800 mt-2">{{ $stats['shooting'] }}</div>
            <div class="text-[10px] text-amber-700 mt-0.5">Camera rolling</div>
        </a>

        <a href="{{ route('shoots.index', ['tab' => 'editing']) }}" class="bg-white p-4 rounded-2xl border transition shadow-2xs hover:border-purple-300 {{ $tab === 'editing' ? 'ring-2 ring-purple-500 border-purple-500' : 'border-slate-200' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] uppercase font-bold text-purple-700">In Editing</span>
                <i data-lucide="scissors" class="w-4 h-4 text-purple-500"></i>
            </div>
            <div class="text-xl font-black text-purple-800 mt-2">{{ $stats['editing'] }}</div>
            <div class="text-[10px] text-purple-600 mt-0.5">Post-production</div>
        </a>

        <a href="{{ route('shoots.index', ['tab' => 'published']) }}" class="bg-white p-4 rounded-2xl border transition shadow-2xs hover:border-emerald-300 {{ $tab === 'published' ? 'ring-2 ring-emerald-500 border-emerald-500' : 'border-slate-200' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] uppercase font-bold text-emerald-700">Published</span>
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500"></i>
            </div>
            <div class="text-xl font-black text-emerald-800 mt-2">{{ $stats['published'] }}</div>
            <div class="text-[10px] text-emerald-600 mt-0.5">Live on social</div>
        </a>

        <a href="{{ route('shoots.index', ['tab' => 'mine']) }}" class="bg-white p-4 rounded-2xl border transition shadow-2xs hover:border-indigo-300 {{ $tab === 'mine' ? 'ring-2 ring-indigo-500 border-indigo-500' : 'border-slate-200' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] uppercase font-bold text-indigo-700">My Shoots</span>
                <i data-lucide="user-check" class="w-4 h-4 text-indigo-500"></i>
            </div>
            <div class="text-xl font-black text-indigo-800 mt-2">{{ $stats['mine'] }}</div>
            <div class="text-[10px] text-indigo-600 mt-0.5">Assigned to you</div>
        </a>
    </div>

    <!-- Filter Tabs Navigation -->
    <div class="flex items-center justify-between gap-3 flex-wrap border-b border-slate-200 pb-3">
        <div class="flex items-center gap-1.5 overflow-x-auto py-1">
            <a href="{{ route('shoots.index', ['tab' => 'all']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'all' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                All Productions
            </a>
            <a href="{{ route('shoots.index', ['tab' => 'mine']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'mine' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                My Call Sheets
            </a>
            <a href="{{ route('shoots.index', ['tab' => 'scheduled']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'scheduled' ? 'bg-sky-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                Scheduled Shoots
            </a>
            <a href="{{ route('shoots.index', ['tab' => 'shooting']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'shooting' ? 'bg-amber-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                Shooting Now
            </a>
            <a href="{{ route('shoots.index', ['tab' => 'editing']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'editing' ? 'bg-purple-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                In Editing
            </a>
            <a href="{{ route('shoots.index', ['tab' => 'published']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'published' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                Published
            </a>
        </div>

        <div class="flex items-center gap-2">
            {{-- Instagram Brand Icon Button --}}
            <a href="{{ route('shoots.index', ['tab' => 'instagram']) }}"
               title="Filter: Instagram"
               class="px-3 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 {{ $tab === 'instagram' ? 'bg-pink-50 border-pink-300 text-pink-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-pink-50 hover:border-pink-200' }}">
                {{-- Instagram gradient SVG --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                    <defs>
                        <radialGradient id="ig-grad-a" cx="30%" cy="107%" r="150%">
                            <stop offset="0%" stop-color="#fdf497"/>
                            <stop offset="5%" stop-color="#fdf497"/>
                            <stop offset="45%" stop-color="#fd5949"/>
                            <stop offset="60%" stop-color="#d6249f"/>
                            <stop offset="90%" stop-color="#285AEB"/>
                        </radialGradient>
                    </defs>
                    <rect x="2" y="2" width="20" height="20" rx="5.5" ry="5.5" fill="url(#ig-grad-a)"/>
                    <circle cx="12" cy="12" r="4.5" fill="none" stroke="white" stroke-width="1.7"/>
                    <circle cx="17.3" cy="6.7" r="1" fill="white"/>
                </svg>
                <span>Instagram</span>
            </a>

            {{-- YouTube Brand Icon Button --}}
            <a href="{{ route('shoots.index', ['tab' => 'youtube']) }}"
               title="Filter: YouTube"
               class="px-3 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 {{ $tab === 'youtube' ? 'bg-red-50 border-red-300 text-red-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-red-50 hover:border-red-200' }}">
                {{-- YouTube red play button SVG --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="#FF0000">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
                <span>YouTube</span>
            </a>
        </div>

    </div>

    <!-- Content Shoots Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($shoots as $shoot)
            <div class="bg-white rounded-3xl p-5 border border-slate-200/90 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
                <div>
                    <!-- Top Row: Platform Badge, Channel ID, and Status -->
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <!-- Platform pill -->
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold border {{ $shoot->platform_info['class'] }}">
                                <i data-lucide="{{ $shoot->platform_info['icon'] }}" class="w-3 h-3"></i>
                                <span>{{ $shoot->platform_info['label'] }}</span>
                            </span>

                            <!-- Channel / Handle ID -->
                            @if($shoot->instagram_handle)
                                <span class="font-mono text-[10px] font-bold text-pink-700 bg-pink-50/60 px-2 py-0.5 rounded border border-pink-100" title="Instagram ID">
                                    {{ $shoot->instagram_handle }}
                                </span>
                            @endif
                            @if($shoot->youtube_channel)
                                <span class="font-mono text-[10px] font-bold text-red-700 bg-red-50/60 px-2 py-0.5 rounded border border-red-100" title="YouTube Channel">
                                    {{ $shoot->youtube_channel }}
                                </span>
                            @endif
                        </div>

                        <!-- Status Badge -->
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black border uppercase tracking-wider {{ $shoot->status_badge['class'] }}">
                            <i data-lucide="{{ $shoot->status_badge['icon'] }}" class="w-3 h-3"></i>
                            <span>{{ $shoot->status_badge['label'] }}</span>
                        </span>
                    </div>

                    <!-- Shoot Title -->
                    <h3 class="text-base font-black text-slate-900 group-hover:text-indigo-600 transition leading-snug line-clamp-2">
                        <a href="{{ route('shoots.show', $shoot) }}">{{ $shoot->title }}</a>
                    </h3>

                    <!-- Opening Hook Callout -->
                    @if($shoot->hook)
                        <div class="mt-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-700">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-600 block mb-0.5">Hook (Opening 3s):</span>
                            <p class="italic text-slate-600 line-clamp-2">"{{ $shoot->hook }}"</p>
                        </div>
                    @endif

                    <!-- Scheduled Date & Location -->
                    <div class="mt-3.5 space-y-1.5 text-xs text-slate-600">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                            <span class="font-bold text-slate-800">{{ $shoot->shoot_date->format('D, d M Y') }}</span>
                            <span class="text-slate-400">&bull;</span>
                            <span class="font-bold text-indigo-600">{{ $shoot->shoot_date->format('h:i A') }}</span>
                            <span class="text-[10px] text-slate-400 font-medium">({{ $shoot->shoot_date->diffForHumans() }})</span>
                        </div>

                        @if($shoot->location)
                            <div class="flex items-center gap-2 text-slate-500">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                <span class="truncate">{{ $shoot->location }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Managing Member Row -->
                    <div class="mt-3.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-6 h-6 rounded-lg {{ $shoot->managing_member_id ? 'bg-indigo-600' : 'bg-slate-700' }} text-white flex items-center justify-center shrink-0 text-xs">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[9px] uppercase font-black tracking-wider text-slate-400 block leading-none">Managing Member</span>
                                <span class="text-xs font-bold text-slate-900 truncate block leading-tight mt-0.5">
                                    {{ $shoot->managing_member_name }}
                                </span>
                            </div>
                        </div>
                        @if($shoot->managing_member_id)
                            <span class="px-2 py-0.5 rounded-md text-[9px] font-black bg-indigo-100 text-indigo-700 uppercase">Assigned</span>
                        @else
                            <span class="px-2 py-0.5 rounded-md text-[9px] font-black bg-slate-200 text-slate-700 uppercase">TL Lead</span>
                        @endif
                    </div>

                    <!-- Crew Assignment Section -->
                    <div class="mt-3 pt-3 border-t border-slate-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] uppercase font-black tracking-wider text-slate-400 flex items-center gap-1">
                                <i data-lucide="users" class="w-3 h-3 text-indigo-500"></i>
                                <span>Reel Crew (Cast & Ops)</span>
                            </span>
                            @if(auth()->user()->isTL())
                                <button type="button" 
                                        @click="assignTarget = {
                                            id: {{ $shoot->id }},
                                            title: {{ json_encode($shoot->title) }},
                                            manager_id: '{{ $shoot->managing_member_id }}',
                                            camera_id: '{{ $shoot->camera_person_id }}',
                                            camera_name: {{ json_encode($shoot->camera_person_name ?: '') }},
                                            model_id: '{{ $shoot->model_id }}',
                                            model_name: {{ json_encode($shoot->model_name ?: '') }},
                                            editor_id: '{{ $shoot->editor_id }}',
                                            editor_name: {{ json_encode($shoot->editor_name ?: '') }},
                                            other_crew: {{ json_encode($shoot->other_crew ?: '') }}
                                        }; assignCrewModal = true"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100/80 px-2 py-0.5 rounded-lg transition cursor-pointer"
                                        title="TL: Assign managing member or crew">
                                    <i data-lucide="user-plus" class="w-3 h-3"></i>
                                    <span>Assign Lead/Crew</span>
                                </button>
                            @endif
                        </div>

                        <!-- 3-Role Roster Grid -->
                        <div class="grid grid-cols-3 gap-1.5 text-xs">
                            <!-- Camera -->
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200/60 min-w-0">
                                <div class="text-[9px] uppercase font-bold text-indigo-600 flex items-center gap-1 truncate">
                                    <i data-lucide="video" class="w-2.5 h-2.5 shrink-0"></i>
                                    <span>Camera</span>
                                </div>
                                <div class="font-bold text-slate-800 truncate mt-0.5 text-[11px]" title="{{ $shoot->camera_name }}">
                                    {{ $shoot->camera_name }}
                                </div>
                            </div>

                            <!-- Model / Cast -->
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200/60 min-w-0">
                                <div class="text-[9px] uppercase font-bold text-pink-600 flex items-center gap-1 truncate">
                                    <i data-lucide="sparkles" class="w-2.5 h-2.5 shrink-0"></i>
                                    <span>Model</span>
                                </div>
                                <div class="font-bold text-slate-800 truncate mt-0.5 text-[11px]" title="{{ $shoot->model_display_name }}">
                                    {{ $shoot->model_display_name }}
                                </div>
                            </div>

                            <!-- Editor -->
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200/60 min-w-0">
                                <div class="text-[9px] uppercase font-bold text-purple-600 flex items-center gap-1 truncate">
                                    <i data-lucide="scissors" class="w-2.5 h-2.5 shrink-0"></i>
                                    <span>Editor</span>
                                </div>
                                <div class="font-bold text-slate-800 truncate mt-0.5 text-[11px]" title="{{ $shoot->editor_display_name }}">
                                    {{ $shoot->editor_display_name }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">
                        @if($shoot->status === 'scheduled')
                            <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold">
                                <i data-lucide="check" class="w-3 h-3"></i> Ready for set
                            </span>
                        @else
                            <span class="capitalize font-medium">{{ $shoot->status }} stage</span>
                        @endif
                    </span>

                    <a href="{{ route('shoots.show', $shoot) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl transition">
                        <span>View Script & Call Sheet</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-3xl p-12 text-center border border-slate-200">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="clapperboard" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-black text-slate-900">No Shoots Scheduled Yet</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    Start by scheduling an upcoming Instagram or YouTube shoot, assign your camera operator, model, and write down the script.
                </p>
                @if(auth()->user()->isTL())
                <button @click="showScheduleModal = true" type="button" class="mt-4 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition cursor-pointer">
                    Schedule Your First Shoot
                </button>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pt-2">
        {{ $shoots->links() }}
    </div>

    <!-- =================================================================== -->
    <!-- MODAL: SCHEDULE NEW SHOOT                                           -->
    <!-- =================================================================== -->
    <div x-show="showScheduleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-3 sm:p-4">
        <div @click.outside="showScheduleModal = false" class="bg-white rounded-3xl max-w-3xl w-full p-4 sm:p-6 lg:p-8 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/25 shrink-0">
                        <i data-lucide="clapperboard" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Schedule New Social Media Shoot</h3>
                        <p class="text-xs text-slate-500">Plan production, assign crew & models, and attach the shoot script.</p>
                    </div>
                </div>
                <button @click="showScheduleModal = false" type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('shoots.store') }}" class="space-y-5">
                @csrf

                <!-- Row 1: Title & Platform -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Shoot Title / Content Topic</label>
                        <input type="text" name="title" required placeholder="e.g. iPhone 16 Pro Cinematic Review Reel" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 font-semibold text-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Platform</label>
                        <select name="platform" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 font-semibold text-slate-800">
                            <option value="instagram">Instagram (Reel/Post)</option>
                            <option value="youtube">YouTube (Video/Short)</option>
                            <option value="both" selected>Both IG & YouTube</option>
                            <option value="other">Other Channel</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Channel IDs (Instagram & YouTube) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80">
                    <div>
                        <label class="block text-xs font-bold text-pink-700 uppercase mb-1 flex items-center gap-1.5">
                            <i data-lucide="instagram" class="w-3.5 h-3.5 text-pink-600"></i>
                            <span>Instagram Handle / ID</span>
                        </label>
                        <input type="text" name="instagram_handle" placeholder="@ecofone_official" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-pink-500 bg-white font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-red-700 uppercase mb-1 flex items-center gap-1.5">
                            <i data-lucide="youtube" class="w-3.5 h-3.5 text-red-600"></i>
                            <span>YouTube Channel / ID</span>
                        </label>
                        <input type="text" name="youtube_channel" placeholder="@ecofonetech or EcoFone Official" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-red-500 bg-white font-mono">
                    </div>
                </div>

                <!-- Row 3: Scheduled Date & Time & Location -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Scheduled Shoot Date & Call Time</label>
                        <input type="datetime-local" name="shoot_date" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 font-semibold text-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Shoot Location / Set</label>
                        <input type="text" name="location" placeholder="e.g. Studio Room A, Tech Park Outdoor, Office Set" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 font-semibold text-slate-800">
                    </div>
                </div>

                <!-- Row 3.5: Managing Member Assignment (CRITICAL: Who manages and updates this shoot) -->
                <div class="p-4 rounded-2xl bg-slate-900 text-white space-y-2 shadow-xs">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-black uppercase tracking-wider text-indigo-300 flex items-center gap-1.5">
                            <i data-lucide="shield-check" class="w-4 h-4 text-indigo-400"></i>
                            <span>Assigned Managing Member (Shoot Lead)</span>
                        </label>
                        <span class="text-[10px] text-slate-400">Updates shoot progress & status</span>
                    </div>
                    <select name="managing_member_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 text-xs bg-slate-800 text-white focus:ring-2 focus:ring-indigo-400 font-semibold">
                        <option value="">-- No Member Assigned (Managed directly by Team Lead) --</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->designation ?: $m->role }})</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400">
                        <strong class="text-indigo-300">Rule:</strong> If a member is assigned, this shoot will show on their dashboard and only they can mark shooting complete and update status. If left unassigned, the TL manages it. (Cameramen, models & editors cannot update status unless assigned here).
                    </p>
                </div>

                <!-- Row 4: Cast & Crew Assignments -->
                <div class="p-4 rounded-2xl bg-indigo-50/40 border border-indigo-100 space-y-3">
                    <h4 class="text-xs font-black text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="users" class="w-4 h-4 text-indigo-600"></i>
                        <span>Cast & Crew Roster (Call Sheet Credits)</span>
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Camera Person -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Camera / Videographer</label>
                            <select name="camera_person_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-indigo-500 font-medium">
                                <option value="">-- Select Team Member --</option>
                                @foreach($members as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->designation ?: $m->role }})</option>
                                @endforeach
                            </select>
                            <input type="text" name="camera_person_name" placeholder="Or custom camera person" class="w-full mt-1 px-2.5 py-1 text-[11px] rounded-lg border border-slate-200 bg-white">
                        </div>

                        <!-- Model / Actor / Host -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Model / Actor / Presenter</label>
                            <select name="model_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-indigo-500 font-medium">
                                <option value="">-- Select Team Member --</option>
                                @foreach($members as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->designation ?: $m->role }})</option>
                                @endforeach
                            </select>
                            <input type="text" name="model_name" placeholder="Or custom model / host" class="w-full mt-1 px-2.5 py-1 text-[11px] rounded-lg border border-slate-200 bg-white">
                        </div>

                        <!-- Editor -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Video Editor</label>
                            <select name="editor_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-indigo-500 font-medium">
                                <option value="">-- Select Team Member --</option>
                                @foreach($members as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->designation ?: $m->role }})</option>
                                @endforeach
                            </select>
                            <input type="text" name="editor_name" placeholder="Or custom editor" class="w-full mt-1 px-2.5 py-1 text-[11px] rounded-lg border border-slate-200 bg-white">
                        </div>
                    </div>
                </div>

                <!-- Row 5: 3-Second Hook & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Opening 3-Second Hook</label>
                        <input type="text" name="hook" placeholder="e.g. Stop making this huge mistake when buying a smartphone..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 font-semibold text-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Production Status</label>
                        <select name="status" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 font-semibold text-slate-800">
                            <option value="planning">Planning (Ideation)</option>
                            <option value="scripting">Scripting</option>
                            <option value="scheduled" selected>Scheduled (Locked)</option>
                            <option value="shooting">Shooting</option>
                            <option value="editing">In Editing</option>
                            <option value="review">Under Review</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>

                <!-- Row 6: Script & Directions -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Shoot Script & Scene Instructions</label>
                    <textarea name="script" rows="5" placeholder="Write dialogue, scene cuts, B-roll instructions, and talking points..." class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 font-sans"></textarea>
                </div>

                <!-- Row 7: Concept Notes & Reference Links -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Concept / Props / Lighting Notes</label>
                        <input type="text" name="concept_notes" placeholder="Warm lighting, tripod, phone props, RGB backlight" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Reference Audio / Reel Links</label>
                        <input type="text" name="reference_links" placeholder="https://instagram.com/reels/..., https://youtube.com/..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showScheduleModal = false" class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black rounded-xl transition shadow-md shadow-indigo-600/25 flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="calendar-check" class="w-4 h-4"></i>
                        <span>Save & Schedule Shoot</span>
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL: TL QUICK ASSIGN CREW                                          -->
    <!-- =================================================================== -->
    @if(auth()->user()->isTL())
    <div x-show="assignCrewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-3 sm:p-4">
        <div @click.outside="assignCrewModal = false" class="bg-white rounded-3xl max-w-xl w-full p-4 sm:p-6 lg:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200 space-y-4 sm:space-y-5">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-xs shrink-0">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Assign Reel Crew</h3>
                        <p class="text-xs text-slate-500 truncate max-w-sm" x-text="'Assign team members for: ' + assignTarget.title"></p>
                    </div>
                </div>
                <button @click="assignCrewModal = false" type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form :action="'{{ url('/shoots') }}/' + assignTarget.id + '/assign-crew'" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')

                <!-- 0. Managing Member (Lead & Status Controller) -->
                <div class="p-3.5 rounded-2xl bg-slate-900 text-white space-y-2">
                    <label class="block text-xs font-black text-indigo-300 uppercase flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-indigo-400"></i>
                        <span>Managing Member (Shoot Lead / Status Updater)</span>
                    </label>
                    <select name="managing_member_id" x-model="assignTarget.manager_id" class="w-full px-3 py-2 rounded-xl border border-slate-700 text-xs bg-slate-800 text-white focus:ring-2 focus:ring-indigo-400 font-semibold">
                        <option value="">-- No Member Assigned (Managed directly by TL) --</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->designation ?: $m->role }})</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 leading-tight">
                        Assigned managing member can mark shooting completed, move to editing, and review from their dashboard. If empty, the TL manages it.
                    </p>
                </div>

                <!-- 1. Camera / Videographer -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <label class="block text-xs font-bold text-indigo-700 uppercase flex items-center gap-1.5">
                        <i data-lucide="video" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Camera / Videographer</span>
                    </label>
                    <select name="camera_person_id" x-model="assignTarget.camera_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-indigo-500 font-medium">
                        <option value="">-- Select Team Member --</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->designation ?: $m->role }})</option>
                        @endforeach
                    </select>
                    <input type="text" name="camera_person_name" x-model="assignTarget.camera_name" placeholder="Or custom camera person name" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white">
                </div>

                <!-- 2. Model / Actor / Presenter -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <label class="block text-xs font-bold text-pink-700 uppercase flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-pink-600"></i>
                        <span>Model / Actor / Presenter</span>
                    </label>
                    <select name="model_id" x-model="assignTarget.model_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-pink-500 font-medium">
                        <option value="">-- Select Team Member --</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->designation ?: $m->role }})</option>
                        @endforeach
                    </select>
                    <input type="text" name="model_name" x-model="assignTarget.model_name" placeholder="Or custom model / presenter name" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white">
                </div>

                <!-- 3. Video Editor -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <label class="block text-xs font-bold text-purple-700 uppercase flex items-center gap-1.5">
                        <i data-lucide="scissors" class="w-3.5 h-3.5 text-purple-600"></i>
                        <span>Video Editor</span>
                    </label>
                    <select name="editor_id" x-model="assignTarget.editor_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-purple-500 font-medium">
                        <option value="">-- Select Team Member --</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->designation ?: $m->role }})</option>
                        @endforeach
                    </select>
                    <input type="text" name="editor_name" x-model="assignTarget.editor_name" placeholder="Or custom editor name" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white">
                </div>

                <!-- 4. Other Crew Notes -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Additional Crew Notes / Assistant</label>
                    <input type="text" name="other_crew" x-model="assignTarget.other_crew" placeholder="e.g. Lighting Tech, Assistant Director" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="assignCrewModal = false" class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black rounded-xl transition shadow-md shadow-indigo-600/25 flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Save Crew Assignment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
