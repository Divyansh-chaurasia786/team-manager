@extends('layouts.app')
@section('title', $shoot->title . ' - Reel Production Studio')
@section('page-title', 'Reel Production Studio')

@section('content')
<div class="max-w-7xl mx-auto space-y-5 pb-12" x-data="{ 
    showPhaseEditModal: false, 
    showCrewAssignModal: false,
    activePhaseTab: '{{ $shoot->status }}',
    teleprompterLarge: false 
}">

    <!-- =================================================================== -->
    <!-- TOP HEADER: TITLE, LOGO, REEL SHOOT CREW & WHATSAPP ACTION          -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/90 shadow-xs space-y-4">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            
            <!-- Left: Back Navigation, Pure EcoFone Logo & Shoot Title -->
            <div class="flex items-center gap-3.5">
                <a href="{{ route('shoots.index') }}" 
                   class="p-2.5 rounded-2xl bg-slate-50 hover:bg-slate-100 border border-slate-200/80 text-slate-500 hover:text-indigo-600 transition shrink-0 shadow-2xs"
                   title="Back to All Shoots">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>

                <!-- Pure EcoFone Logo (Crisp, without extra text labels) -->
                <div class="p-2 bg-slate-50 rounded-2xl border border-slate-200/70 inline-flex items-center justify-center shrink-0">
                    <img src="{{ asset('images/logo.png') }}" alt="EcoFone" class="h-8 sm:h-9 w-auto object-contain">
                </div>

                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-xs font-bold text-orange-600 bg-orange-50 px-2.5 py-0.5 rounded-lg border border-orange-200/80">
                            REEL #{{ str_pad($shoot->id, 4, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="text-xs font-bold text-slate-300">&bull;</span>
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-600">
                            <i data-lucide="{{ $shoot->platform_info['icon'] }}" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Upload Platform: {{ $shoot->platform_info['label'] }}</span>
                        </span>
                        @if($shoot->instagram_handle)
                            <span class="text-xs font-mono font-bold text-pink-600 bg-pink-50 px-2 py-0.5 rounded-lg border border-pink-200/80">
                                {{ $shoot->instagram_handle }}
                            </span>
                        @endif
                        @if($shoot->youtube_channel)
                            <span class="text-xs font-mono font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-lg border border-red-200/80">
                                {{ $shoot->youtube_channel }}
                            </span>
                        @endif
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug mt-1">
                        {{ $shoot->title }}
                    </h1>
                </div>
            </div>

            <!-- Right: ONLY WhatsApp Share Button (Visible only when reel is scheduled) -->
            @if($shoot->status === 'scheduled')
            <div>
                <button type="button" 
                        onclick="shareOnWhatsApp()" 
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-2xl text-xs font-black transition flex items-center gap-2 shadow-md shadow-emerald-600/20 cursor-pointer"
                        title="Share reel shoot details directly to WhatsApp">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                    </svg>
                    <span>Share on WhatsApp</span>
                </button>
            </div>
            @endif
        </div>

        <!-- REEL SHOOT CREW (Placed directly at Header for instant visibility) -->
        <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-indigo-500"></i>
                    <span>Reel Shoot Crew</span>
                </span>
            </div>

            <div class="flex items-center gap-2.5 sm:gap-3 flex-wrap flex-1 justify-start sm:justify-end text-xs">
                <!-- 0. Managing Member / Shoot Supervisor -->
                <div class="flex items-center gap-2 {{ $shoot->managing_member_id ? 'bg-indigo-50 border-indigo-200' : 'bg-slate-100 border-slate-200' }} px-3 py-1.5 rounded-xl border transition shadow-2xs">
                    <div class="w-6 h-6 rounded-lg {{ $shoot->managing_member_id ? 'bg-indigo-700' : 'bg-slate-800' }} text-white flex items-center justify-center font-bold text-xs shrink-0">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <span class="text-[9px] font-extrabold uppercase {{ $shoot->managing_member_id ? 'text-indigo-700' : 'text-slate-600' }} block leading-none">Managing Member (Lead)</span>
                        <strong class="text-xs font-black text-slate-900 leading-none">{{ $shoot->managing_member_name }}</strong>
                    </div>
                </div>

                <!-- 1. Camera Operator / Videographer -->
                <div class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100/80 px-3 py-1.5 rounded-xl border border-slate-200/80 transition shadow-2xs">
                    <div class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">
                        <i data-lucide="video" class="w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <span class="text-[9px] font-extrabold uppercase text-indigo-600 block leading-none">Camera / Videographer</span>
                        <strong class="text-xs font-black text-slate-900 leading-none">{{ $shoot->camera_name }}</strong>
                    </div>
                </div>

                <!-- 2. Model / Presenter / Creator -->
                <div class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100/80 px-3 py-1.5 rounded-xl border border-slate-200/80 transition shadow-2xs">
                    <div class="w-6 h-6 rounded-lg bg-pink-600 text-white flex items-center justify-center font-bold text-xs shrink-0">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <span class="text-[9px] font-extrabold uppercase text-pink-600 block leading-none">Model / Presenter / Creator</span>
                        <strong class="text-xs font-black text-slate-900 leading-none">{{ $shoot->model_display_name }}</strong>
                    </div>
                </div>

                <!-- 3. Video Editor -->
                <div class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100/80 px-3 py-1.5 rounded-xl border border-slate-200/80 transition shadow-2xs">
                    <div class="w-6 h-6 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-xs shrink-0">
                        <i data-lucide="scissors" class="w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <span class="text-[9px] font-extrabold uppercase text-purple-600 block leading-none">Video Editor</span>
                        <strong class="text-xs font-black text-slate-900 leading-none">{{ $shoot->editor_display_name }}</strong>
                    </div>
                </div>

                @if($shoot->other_crew)
                    <div class="text-[11px] text-slate-500 font-semibold px-2.5 py-1 bg-slate-50 rounded-lg border border-slate-200/60">
                        + {{ $shoot->other_crew }}
                    </div>
                @endif

                @if(auth()->user()->isTL())
                    <button type="button" 
                            @click="showCrewAssignModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl border border-indigo-200/80 transition cursor-pointer"
                            title="TL: Reassign cast and crew for this shoot">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                        <span>Assign Crew</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2-COLUMN ARCHITECTURE: LEFT (PIPELINE) | RIGHT (PHASE CAPABILITY)    -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- ============================================================= -->
        <!-- LEFT SIDEBAR: VERTICAL PRODUCTION STAGE PIPELINE (4 cols)    -->
        <!-- ============================================================= -->
        <div class="lg:col-span-4 space-y-4">

            <!-- 1. Vitals Card (Shooting Date & Location) -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/90 shadow-xs space-y-2.5 text-xs text-slate-600">
                <div class="flex items-center gap-2.5 bg-slate-50/90 p-2.5 rounded-xl border border-slate-200/60">
                    <div class="w-7 h-7 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center shrink-0">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Shooting Date:</span>
                        <strong class="text-slate-900 font-bold text-xs truncate block">{{ $shoot->shoot_date->format('d M Y, h:i A') }}</strong>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 bg-slate-50/90 p-2.5 rounded-xl border border-slate-200/60">
                    <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Location:</span>
                        <strong class="text-slate-900 font-bold text-xs truncate block">{{ $shoot->location ?: 'Main Studio Set' }}</strong>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 bg-slate-50/90 p-2.5 rounded-xl border border-slate-200/60">
                    <div class="w-7 h-7 rounded-lg {{ $shoot->managing_member_id ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700' }} flex items-center justify-center shrink-0">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Managed By:</span>
                        <strong class="text-slate-900 font-bold text-xs truncate block">
                            {{ $shoot->managing_member_name }}
                            @if(!$shoot->managing_member_id)
                                <span class="text-[10px] text-indigo-600 font-medium">(TL Direct)</span>
                            @endif
                        </strong>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 bg-slate-50/90 p-2.5 rounded-xl border border-slate-200/60">
                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Scheduled by:</span>
                        <strong class="text-slate-900 font-bold text-xs truncate block">{{ $shoot->creator->name }}</strong>
                    </div>
                </div>
            </div>

            <!-- 2. Vertical Production Pipeline with Capabilities -->
            @php
                $stages = [
                    'planning'  => [
                        'label' => 'Planning', 
                        'capability' => 'Hook & Concept Ideation', 
                        'icon' => 'sparkles',
                        'desc' => 'Define angle, props & first 3s hook'
                    ],
                    'scripting' => [
                        'label' => 'Scripting', 
                        'capability' => 'Dialogue & Teleprompter', 
                        'icon' => 'file-text',
                        'desc' => 'Write lines & scene instructions'
                    ],
                    'scheduled' => [
                        'label' => 'Scheduled', 
                        'capability' => 'WhatsApp Call Sheet Dispatch', 
                        'icon' => 'calendar-check',
                        'desc' => 'Lock time, set & dispatch crew'
                    ],
                    'shooting'  => [
                        'label' => 'Shooting', 
                        'capability' => 'On-Set Teleprompter Filming', 
                        'icon' => 'video',
                        'desc' => 'Record vertical takes on camera'
                    ],
                    'editing'   => [
                        'label' => 'Editing', 
                        'capability' => 'Post-Production & Cuts', 
                        'icon' => 'scissors',
                        'desc' => 'Sound design, SFX & video edits'
                    ],
                    'review'    => [
                        'label' => 'Review', 
                        'capability' => 'Quality Review & Sign-Off', 
                        'icon' => 'eye',
                        'desc' => 'Inspect pacing, audio & captions'
                    ],
                    'published' => [
                        'label' => 'Published', 
                        'capability' => 'Live Social Media Launch', 
                        'icon' => 'check-circle-2',
                        'desc' => 'View live Instagram / YouTube post'
                    ],
                ];
                $stageKeys = array_keys($stages);
                $currentIndex = array_search($shoot->status, $stageKeys);
            @endphp

            <div class="bg-white rounded-3xl p-5 border border-slate-200/90 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                        </div>
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Production Stages</h3>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black border uppercase tracking-wider {{ $shoot->status_badge['class'] }}">
                        {{ $shoot->status_badge['label'] }}
                    </span>
                </div>

                <!-- Vertical Stage Navigator -->
                <div class="space-y-1.5">
                    @foreach($stages as $key => $stage)
                        @php
                            $stageIndex = array_search($key, $stageKeys);
                            $isPassed = $stageIndex < $currentIndex;
                            $isCurrent = $stageIndex === $currentIndex;
                        @endphp

                        @if($shoot->canUpdateStatus(auth()->user()))
                            <form method="POST" action="{{ route('shoots.status.update', $shoot) }}" class="m-0">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $key }}">
                                <button type="submit" 
                                        class="w-full text-left p-2.5 rounded-2xl transition flex items-center gap-3 cursor-pointer group {{ $isCurrent ? 'bg-indigo-600 text-white shadow-xs font-bold ring-2 ring-indigo-600/20' : ($isPassed ? 'bg-indigo-50/60 text-indigo-950 hover:bg-indigo-50' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-700') }}">
                                    
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs {{ $isCurrent ? 'bg-white/20 text-white' : ($isPassed ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200') }}">
                                        @if($isPassed)
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        @else
                                            <i data-lucide="{{ $stage['icon'] }}" class="w-4 h-4"></i>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs leading-tight flex items-center justify-between">
                                            <span class="font-black">{{ $loop->iteration }}. {{ $stage['label'] }}</span>
                                            @if($isCurrent)
                                                <span class="text-[9px] uppercase tracking-wider font-black bg-white/25 px-1.5 py-0.5 rounded text-white">Active</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] mt-0.5 truncate font-medium {{ $isCurrent ? 'text-indigo-100' : 'text-slate-400' }}">
                                            {{ $stage['capability'] }}
                                        </div>
                                    </div>
                                </button>
                            </form>
                        @else
                            <div class="w-full text-left p-2.5 rounded-2xl flex items-center gap-3 opacity-90 {{ $isCurrent ? 'bg-indigo-600 text-white shadow-xs font-bold' : ($isPassed ? 'bg-indigo-50/60 text-indigo-950' : 'text-slate-400') }}">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs {{ $isCurrent ? 'bg-white/20 text-white' : ($isPassed ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400') }}">
                                    @if($isPassed)
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    @else
                                        <i data-lucide="{{ $stage['icon'] }}" class="w-4 h-4"></i>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs leading-tight flex items-center justify-between">
                                        <span class="font-black">{{ $loop->iteration }}. {{ $stage['label'] }}</span>
                                        @if($isCurrent)
                                            <span class="text-[9px] uppercase tracking-wider font-black bg-white/25 px-1.5 py-0.5 rounded text-white">Active</span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] mt-0.5 truncate font-medium {{ $isCurrent ? 'text-indigo-100' : 'text-slate-400' }}">
                                        {{ $stage['capability'] }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

        </div>

        <!-- ============================================================= -->
        <!-- RIGHT WORKSPACE: ACTIVE STAGE CAPABILITY, HOOK & SCRIPT       -->
        <!-- ============================================================= -->
        <div class="lg:col-span-8 space-y-5">

            <!-- 1. Active Phase Capability Spotlight Banner -->
            @php
                $currentStageConfig = $stages[$shoot->status] ?? $stages['scheduled'];
            @endphp
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-5 text-white shadow-sm flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $currentStageConfig['icon'] }}" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-indigo-300">
                            Active Phase &bull; {{ ucfirst($shoot->status) }}
                        </div>
                        <div class="text-sm font-black text-white mt-0.5">
                            {{ $currentStageConfig['capability'] }}
                        </div>
                        <div class="text-xs text-slate-300 mt-0.5">
                            {{ $currentStageConfig['desc'] }}
                        </div>
                    </div>
                </div>

                <!-- Phase Quick Capability Actions -->
                <div class="flex items-center gap-2">
                    @if($shoot->status === 'planning')
                        <button type="button" @click="showPhaseEditModal = true; activePhaseTab = 'planning'" class="px-3.5 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            <span>Refine Hook &amp; Concept</span>
                        </button>
                    @elseif($shoot->status === 'scripting')
                        <button type="button" @click="showPhaseEditModal = true; activePhaseTab = 'scripting'" class="px-3.5 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            <span>Edit Script Dialogue</span>
                        </button>
                    @elseif($shoot->status === 'scheduled')
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-emerald-500/20 text-emerald-300 px-3 py-1.5 rounded-xl border border-emerald-500/30">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            <span>Ready to Shoot</span>
                        </span>
                    @elseif($shoot->status === 'shooting')
                        <button type="button" @click="teleprompterLarge = !teleprompterLarge" class="px-3.5 py-2 bg-white text-slate-900 hover:bg-slate-100 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                            <i data-lucide="maximize-2" class="w-3.5 h-3.5"></i>
                            <span x-text="teleprompterLarge ? 'Normal Text' : 'Teleprompter Mode'">Teleprompter Mode</span>
                        </button>
                    @elseif($shoot->status === 'editing')
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-purple-500/20 text-purple-300 px-3 py-1.5 rounded-xl border border-purple-500/30">
                            <i data-lucide="scissors" class="w-3.5 h-3.5"></i>
                            <span>In Post-Production</span>
                        </span>
                    @elseif($shoot->status === 'review')
                        @if($shoot->canUpdateStatus(auth()->user()))
                            <form method="POST" action="{{ route('shoots.status.update', $shoot) }}" class="m-0">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="published">
                                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                    <span>Approve &amp; Publish</span>
                                </button>
                            </form>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-amber-500/20 text-amber-300 px-3 py-1.5 rounded-xl border border-amber-500/30">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                <span>Awaiting Manager Sign-Off</span>
                            </span>
                        @endif
                    @elseif($shoot->status === 'published' && $shoot->published_url)
                        <a href="{{ $shoot->published_url }}" target="_blank" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            <span>Open Live Post</span>
                        </a>
                    @endif

                    <!-- Phase Edit Quick Trigger -->
                    <button type="button" @click="showPhaseEditModal = true" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition cursor-pointer" title="Edit Phase Details">
                        <i data-lucide="sliders" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- 2. Opening Hook Spotlight Card (First 3-Seconds) -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 border border-amber-200/80 bg-gradient-to-br from-amber-50/40 via-white to-orange-50/30 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-amber-800">
                        <div class="w-7 h-7 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-2xs">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-xs font-black uppercase tracking-wider">First 3-Second Hook</h2>
                    </div>
                    <span class="text-[10px] font-extrabold uppercase text-amber-700 bg-amber-100/80 px-2.5 py-0.5 rounded-full border border-amber-300/60">
                        High Retention
                    </span>
                </div>

                @if($shoot->hook)
                    <div class="p-4 rounded-2xl bg-white border border-amber-200/90 shadow-2xs">
                        <p class="font-black text-slate-900 italic text-sm sm:text-base leading-relaxed">
                            "{{ $shoot->hook }}"
                        </p>
                    </div>
                @else
                    <div class="p-4 rounded-2xl bg-white/60 border border-dashed border-amber-200 text-slate-400 text-xs text-center">
                        No opening hook specified for this reel yet. Click the sliders icon above to draft one!
                    </div>
                @endif
            </div>

            <!-- 3. Full Script & Scene Teleprompter Workspace -->
            <div class="bg-white rounded-3xl p-5 sm:p-7 border border-slate-200/90 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 flex-wrap gap-2">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900">Reel Script &amp; Scene Dialogue</h2>
                            <p class="text-[11px] text-slate-400">High-contrast teleprompter view for recording on set.</p>
                        </div>
                    </div>

                    @php
                        $wordCount = str_word_count($shoot->script ?? '');
                        $estSeconds = max(15, round($wordCount / 2.5));
                    @endphp
                    <span class="text-xs text-slate-500 font-bold bg-slate-100 px-3 py-1 rounded-xl border border-slate-200/80">
                        {{ $wordCount }} words &bull; ~{{ $estSeconds }}s reel
                    </span>
                </div>

                <!-- Teleprompter Script Content with Dynamic Sizing -->
                @if($shoot->script)
                    <div id="teleprompterScriptText" 
                         :class="teleprompterLarge ? 'text-lg sm:text-xl p-8 bg-slate-900 text-amber-300 font-semibold' : 'text-sm sm:text-base p-5 sm:p-6 bg-slate-50 text-slate-900 font-medium'"
                         class="rounded-2xl border border-slate-200/80 font-sans leading-relaxed whitespace-pre-line select-text transition-all duration-200">
{{ $shoot->script }}
                    </div>
                @else
                    <div class="p-8 text-center rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-slate-400">
                        <i data-lucide="file-edit" class="w-7 h-7 mx-auto mb-1.5 text-slate-300"></i>
                        <p class="font-bold text-slate-700 text-xs">No script has been drafted for this shoot yet.</p>
                    </div>
                @endif

                <!-- Live Publishing URL Banner (if Published) -->
                @if($shoot->status === 'published')
                    <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 text-emerald-800 font-bold">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                            <span>This reel is live on social media!</span>
                        </div>
                        @if($shoot->published_url)
                            <a href="{{ $shoot->published_url }}" target="_blank" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition flex items-center gap-1.5 shadow-2xs">
                                <span>Open Live Post</span>
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <!-- 4. Concept, Props & Reference Links Card -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/90 shadow-xs space-y-3.5 text-xs">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="lightbulb" class="w-3.5 h-3.5"></i>
                        </div>
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Concept &amp; References</h3>
                    </div>
                </div>

                @if($shoot->concept_notes)
                    <div class="space-y-1">
                        <span class="font-bold text-slate-700 block">Concept &amp; Props Notes:</span>
                        <p class="text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-2xl border border-slate-200/80">
                            {{ $shoot->concept_notes }}
                        </p>
                    </div>
                @endif

                @if($shoot->reference_links)
                    <div class="space-y-1.5 pt-1.5">
                        <span class="font-bold text-slate-700 block">Audio &amp; Reel References:</span>
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 break-all space-y-1.5">
                            @foreach(explode(',', $shoot->reference_links) as $link)
                                @php $cleanLink = trim($link); @endphp
                                @if(filter_var($cleanLink, FILTER_VALIDATE_URL))
                                    <div>
                                        <a href="{{ $cleanLink }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold inline-flex items-center gap-1.5">
                                            <i data-lucide="external-link" class="w-3 h-3"></i>
                                            <span>{{ $cleanLink }}</span>
                                        </a>
                                    </div>
                                @else
                                    <div class="text-slate-600 font-medium">{{ $cleanLink }}</div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!$shoot->concept_notes && !$shoot->reference_links)
                    <p class="text-slate-400 text-center py-2 italic">No additional concept notes or audio references added.</p>
                @endif
            </div>

        </div>

    </div>

    <!-- =================================================================== -->
    <!-- MODAL: PHASE CAPABILITY EDITOR                                      -->
    <!-- =================================================================== -->
    <div x-show="showPhaseEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-3 sm:p-4">
        <div @click.outside="showPhaseEditModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-4 sm:p-6 lg:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200 space-y-4 sm:space-y-5">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                        <i data-lucide="sliders" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Update Reel Shoot Details</h3>
                        <p class="text-xs text-slate-500">Edit stage data, hook, script or shoot logistics.</p>
                    </div>
                </div>
                <button @click="showPhaseEditModal = false" type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('shoots.update', $shoot) }}" class="space-y-4">
                @csrf @method('PUT')

                <!-- Title & Platform -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Shoot Title</label>
                        <input type="text" name="title" value="{{ old('title', $shoot->title) }}" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Status</label>
                        <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold">
                            @foreach($stages as $key => $stage)
                                <option value="{{ $key }}" {{ $shoot->status === $key ? 'selected' : '' }}>
                                    {{ $loop->iteration }}. {{ $stage['label'] }} ({{ $stage['capability'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Shooting Date</label>
                        <input type="datetime-local" name="shoot_date" value="{{ old('shoot_date', $shoot->shoot_date->format('Y-m-d\TH:i')) }}" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Location</label>
                        <input type="text" name="location" value="{{ old('location', $shoot->location) }}" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                </div>

                <!-- Hook -->
                <div>
                    <label class="block text-xs font-bold text-amber-800 uppercase mb-1">First 3-Second Hook</label>
                    <input type="text" name="hook" value="{{ old('hook', $shoot->hook) }}" class="w-full px-3.5 py-2 rounded-xl border border-amber-300 text-xs font-bold bg-amber-50/40">
                </div>

                <!-- Script -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Script &amp; Teleprompter Dialogue</label>
                    <textarea name="script" rows="5" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-sans">{{ old('script', $shoot->script) }}</textarea>
                </div>

                <!-- Concept Notes & Live URL -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Concept &amp; Props</label>
                        <input type="text" name="concept_notes" value="{{ old('concept_notes', $shoot->concept_notes) }}" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Published Live URL</label>
                        <input type="url" name="published_url" value="{{ old('published_url', $shoot->published_url) }}" placeholder="https://..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
                    </div>
                </div>

                <!-- Preserve Platform Values -->
                <input type="hidden" name="platform" value="{{ $shoot->platform }}">
                <input type="hidden" name="instagram_handle" value="{{ $shoot->instagram_handle }}">
                <input type="hidden" name="youtube_channel" value="{{ $shoot->youtube_channel }}">

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" @click="showPhaseEditModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black rounded-xl transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL: TL ASSIGN CREW                                               -->
    <!-- =================================================================== -->
    @if(auth()->user()->isTL())
    <div x-show="showCrewAssignModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-3 sm:p-4">
        <div @click.outside="showCrewAssignModal = false" class="bg-white rounded-3xl max-w-xl w-full p-4 sm:p-6 lg:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200 space-y-4 sm:space-y-5">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Assign Reel Crew</h3>
                        <p class="text-xs text-slate-500">Assign team members for "{{ $shoot->title }}"</p>
                    </div>
                </div>
                <button @click="showCrewAssignModal = false" type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form action="{{ route('shoots.assign_crew', $shoot) }}" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')

                <!-- 0. Managing Member (Lead & Status Controller) -->
                <div class="p-3.5 rounded-2xl bg-slate-900 text-white space-y-2">
                    <label class="block text-xs font-black text-indigo-300 uppercase flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-indigo-400"></i>
                        <span>Managing Member (Shoot Lead / Status Updater)</span>
                    </label>
                    <select name="managing_member_id" class="w-full px-3 py-2 rounded-xl border border-slate-700 text-xs bg-slate-800 text-white focus:ring-2 focus:ring-indigo-400 font-semibold">
                        <option value="">-- No Member Assigned (Managed directly by TL) --</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" {{ $shoot->managing_member_id === $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->designation ?: $m->role }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 leading-tight">
                        Assigned member can mark shooting completed and change status from their dashboard. If unassigned, the TL manages it directly.
                    </p>
                </div>

                <!-- 1. Camera / Videographer -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <label class="block text-xs font-bold text-indigo-700 uppercase flex items-center gap-1.5">
                        <i data-lucide="video" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Camera / Videographer</span>
                    </label>
                    <select name="camera_person_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-indigo-500 font-medium">
                        <option value="">-- Select Team Member --</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" {{ $shoot->camera_person_id === $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->designation ?: $m->role }})
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="camera_person_name" value="{{ old('camera_person_name', $shoot->camera_person_name) }}" placeholder="Or custom camera person name" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white">
                </div>

                <!-- 2. Model / Actor / Presenter -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <label class="block text-xs font-bold text-pink-700 uppercase flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-pink-600"></i>
                        <span>Model / Actor / Presenter</span>
                    </label>
                    <select name="model_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-pink-500 font-medium">
                        <option value="">-- Select Team Member --</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" {{ $shoot->model_id === $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->designation ?: $m->role }})
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="model_name" value="{{ old('model_name', $shoot->model_name) }}" placeholder="Or custom model / presenter name" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white">
                </div>

                <!-- 3. Video Editor -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <label class="block text-xs font-bold text-purple-700 uppercase flex items-center gap-1.5">
                        <i data-lucide="scissors" class="w-3.5 h-3.5 text-purple-600"></i>
                        <span>Video Editor</span>
                    </label>
                    <select name="editor_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-purple-500 font-medium">
                        <option value="">-- Select Team Member --</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" {{ $shoot->editor_id === $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->designation ?: $m->role }})
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="editor_name" value="{{ old('editor_name', $shoot->editor_name) }}" placeholder="Or custom editor name" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white">
                </div>

                <!-- 4. Other Crew Notes -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Additional Crew Notes / Assistant</label>
                    <input type="text" name="other_crew" value="{{ old('other_crew', $shoot->other_crew) }}" placeholder="e.g. Lighting Tech, Assistant Director" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showCrewAssignModal = false" class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
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

<!-- WhatsApp Share Script with Exact User Rules -->
<script>
    function generateShootSummary() {
        const title = @json($shoot->title);
        const refCode = 'REEL #' + String(@json($shoot->id)).padStart(4, '0');
        const shootingDate = @json($shoot->shoot_date->format('d M Y, h:i A'));
        
        // Target upload platform calculation
        let uploadPlatform = @json($shoot->platform_info['label']);
        const igHandle = @json($shoot->instagram_handle);
        const ytChannel = @json($shoot->youtube_channel);
        if (igHandle && ytChannel) {
            uploadPlatform = `Instagram (${igHandle}) & YouTube (${ytChannel})`;
        } else if (igHandle) {
            uploadPlatform = `Instagram (${igHandle})`;
        } else if (ytChannel) {
            uploadPlatform = `YouTube (${ytChannel})`;
        }

        const location = @json($shoot->location ?: 'Main Studio Set');
        const status = @json(ucfirst($shoot->status));

        // Managing Member & Crew: NAMES ONLY, NO mobile numbers in chat!
        const manager = @json($shoot->managing_member_name);
        const camera = @json($shoot->camera_name);
        const model = @json($shoot->model_display_name);
        const editor = @json($shoot->editor_display_name);

        const hook = @json($shoot->hook ?: '');
        const script = @json($shoot->script ?: '');
        const refLinks = @json($shoot->reference_links ?: '');

        let text = `🎬 *ECOFONE REEL SHOOT* (${refCode})\n` +
                   `━━━━━━━━━━━━━━━━━━━━\n` +
                   `📌 *Title:* ${title}\n` +
                   `📅 *Shooting Date:* ${shootingDate}\n` +
                   `📱 *Upload Platform:* ${uploadPlatform}\n` +
                   `🏢 *Location:* ${location}\n` +
                   `⚡ *Status:* ${status}\n` +
                   `👑 *Managed By:* ${manager}\n\n` +
                   `👥 *REEL CREW:*\n` +
                   `🎥 Camera: ${camera}\n` +
                   `🌟 Model/Creator: ${model}\n` +
                   `✂️ Editor: ${editor}\n\n`;

        if (hook) {
            text += `⚡ *OPENING HOOK (First 3s):*\n"${hook}"\n\n`;
        }

        if (script) {
            const shortScript = script.length > 500 ? script.substring(0, 500) + '...' : script;
            text += `📝 *SCRIPT SUMMARY:*\n${shortScript}\n\n`;
        }

        // NO website link! Only share reference link if it exists!
        if (refLinks && refLinks.trim()) {
            text += `🔗 *Reference Links:*\n${refLinks.trim()}`;
        }

        return text;
    }

    function shareOnWhatsApp() {
        const text = generateShootSummary();
        const url = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(text);
        window.open(url, '_blank');
    }
</script>
@endsection
