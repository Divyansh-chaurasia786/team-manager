@extends('layouts.app')
@section('title', $groupTitle ?? 'Team Chat')
@section('page-title', $groupTitle ?? 'Team Chat')

@section('content')

@php
    $isManagement = in_array(auth()->user()->role, ['hr', 'ceo']);
@endphp

<div class="h-full flex flex-col flex-1 min-h-0">

    <!-- Desktop Top Breadcrumb Header Bar (Hidden on Mobile for Native WhatsApp Feel) -->
    <div class="hidden md:flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold mb-1">
                <a href="{{ auth()->user()->isTL() ? route('tl.dashboard') : (auth()->user()->role === 'ceo' ? route('ceo.dashboard') : (auth()->user()->role === 'hr' ? route('hr.dashboard') : route('member.dashboard'))) }}" class="hover:text-[#008069] transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Team Chat</span>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <span>Team Collaboration Hub</span>
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold flex items-center gap-1 {{ $groupType === 'team' ? 'bg-[#e7fce3] border border-[#a3e899] text-[#008069]' : 'bg-emerald-50 border border-emerald-200 text-emerald-800' }}">
                    <i data-lucide="{{ $groupType === 'team' ? 'lock' : 'building-2' }}" class="w-3.5 h-3.5"></i>
                    <span>{{ $groupType === 'team' ? 'Private Team Channel' : 'EcoFone Company Hub' }}</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-bold flex items-center gap-1.5 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-[#00a884] animate-pulse"></span>
                <span>{{ count($teamUsers) }} Active Members</span>
            </span>
        </div>
    </div>

    <!-- Main Integrated Chat Workspace (Full Height on Mobile, WhatsApp Web Style Frame on Desktop) -->
    <div class="flex-1 flex flex-col md:flex-row bg-white md:rounded-2xl md:border md:border-[#d1d7db] md:shadow-md overflow-hidden h-[100dvh] md:h-[calc(100vh-11.5rem)] md:min-h-[620px] relative">

        <!-- 🟢 LEFT SIDEBAR (Desktop WhatsApp Web Channel List & Roster / Mobile Slide-Over Sheet) -->
        <div id="chatSidebar" class="hidden md:flex w-full md:w-80 lg:w-92 bg-white md:border-r md:border-[#e9edef] flex-col shrink-0 absolute md:relative inset-0 z-40 md:z-auto transition-transform duration-200">
            
            <!-- Sidebar Header: Green Theme on Mobile, WhatsApp Web #f0f2f5 on Desktop -->
            <div class="h-14 sm:h-16 px-3.5 sm:px-4 bg-[#008069] md:bg-[#f0f2f5] text-white md:text-[#111b21] border-b border-slate-200 md:border-[#e9edef] flex items-center justify-between shrink-0 select-none">
                <div class="flex items-center gap-2.5 min-w-0">
                    <button type="button" onclick="toggleMobileSidebar(false)" class="md:hidden p-1.5 -ml-1 text-white hover:bg-black/10 rounded-full transition cursor-pointer" title="Back to Chat">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </button>
                    <div class="relative shrink-0">
                        @if(auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full object-cover ring-2 ring-white/30 md:ring-[#00a884]/30">
                        @else
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-[#128c7e] md:bg-[#00a884] text-white font-black text-xs sm:text-sm flex items-center justify-center ring-2 ring-white/30 md:ring-[#00a884]/30">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                        @endif
                        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-[#00a884] rounded-full ring-2 ring-white"></span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-xs sm:text-sm font-bold truncate leading-tight">{{ auth()->user()->name }}</h3>
                        <span class="text-[10px] sm:text-[11px] text-emerald-100 md:text-[#667781] font-semibold">{{ auth()->user()->isTL() ? 'Team Lead' : (auth()->user()->isAdmin() ? strtoupper(auth()->user()->role) : 'Staff Member') }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    @if(auth()->user()->isTL() && $groupType === 'team')
                        <button type="button" onclick="openManageMembersModal()" class="px-2.5 py-1 rounded-lg bg-white/15 hover:bg-white/25 md:bg-white md:hover:bg-slate-100 text-white md:text-[#008069] md:border md:border-[#e9edef] text-[11px] font-bold flex items-center gap-1 transition cursor-pointer shadow-2xs" title="Manage Members">
                            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                            <span>Admin</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- In-Chat Search Input (WhatsApp Web Style Search Capsule) -->
            <div class="p-2.5 md:px-3 md:py-2 bg-white md:bg-[#f0f2f5]/80 border-b border-slate-100 md:border-[#e9edef]">
                <div class="relative flex items-center">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 md:text-[#54656f] absolute left-3"></i>
                    <input 
                        type="text" 
                        id="searchChatInput" 
                        placeholder="Search or start new chat" 
                        class="w-full pl-9 pr-3 py-1.5 bg-slate-50 md:bg-white focus:bg-white text-xs md:text-[13px] rounded-xl md:rounded-lg focus:outline-none focus:ring-1 focus:ring-[#00a884] transition border border-slate-200 md:border-[#e9edef] placeholder:text-slate-400 md:placeholder:text-[#8696a0] text-[#111b21]"
                    >
                </div>
            </div>

            <!-- Group Switcher: Mobile Tabs & Desktop WhatsApp Web Conversation Cards -->
            <div class="p-2.5 md:p-0 bg-slate-50/70 md:bg-white border-b border-slate-100 md:border-[#e9edef] select-none">
                <!-- Mobile 2-Tab Grid (Exact untouched mobile experience) -->
                <div class="grid grid-cols-2 gap-1.5 md:hidden">
                    @if(!$isManagement)
                        <a href="{{ route('thoughts.index', ['group' => 'team']) }}" 
                           class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-bold transition {{ $groupType === 'team' ? 'bg-[#008069] text-white' : 'text-slate-600 hover:bg-white/60' }}">
                            <i data-lucide="lock" class="w-3.5 h-3.5 {{ $groupType === 'team' ? 'text-white' : 'text-slate-400' }}"></i>
                            <span class="truncate">Private Team</span>
                        </a>
                    @else
                        <div class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-semibold text-slate-400 bg-slate-200/50 cursor-not-allowed" title="Private team chat (Lead & members only)">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                            <span class="truncate">Private Team</span>
                        </div>
                    @endif

                    <a href="{{ route('thoughts.index', ['group' => 'company']) }}" 
                       class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-bold transition {{ $groupType === 'company' ? 'bg-[#008069] text-white' : 'text-slate-600 hover:bg-white/60' }}">
                        <i data-lucide="building-2" class="w-3.5 h-3.5 {{ $groupType === 'company' ? 'text-white' : 'text-slate-400' }}"></i>
                        <span class="truncate">Company Hub</span>
                    </a>
                </div>

                <!-- Desktop WhatsApp Web Conversation Cards -->
                <div class="hidden md:flex flex-col divide-y divide-[#f0f2f5]">
                    <!-- Channel 1: Private Team Chat -->
                    @if(!$isManagement)
                        <a href="{{ route('thoughts.index', ['group' => 'team']) }}" 
                           class="flex items-center gap-3 px-3.5 py-3 transition relative group cursor-pointer {{ $groupType === 'team' ? 'bg-[#f0f2f5] border-l-4 border-l-[#00a884]' : 'hover:bg-[#f5f6f6] border-l-4 border-l-transparent' }}">
                            <div class="w-11 h-11 rounded-full bg-[#00a884] text-white flex items-center justify-center shrink-0 shadow-2xs font-bold ring-1 ring-[#00a884]/20">
                                <i data-lucide="users" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1 mb-0.5">
                                    <h4 class="text-[13px] font-semibold text-[#111b21] truncate">
                                        {{ auth()->user()->isTL() ? auth()->user()->name . "'s Team" : ($teamUsers->firstWhere('role', 'tl')?->name ? $teamUsers->firstWhere('role', 'tl')->name . "'s Team" : 'Private Team') }}
                                    </h4>
                                    <span class="text-[10px] font-medium text-[#667781] shrink-0">Team</span>
                                </div>
                                <div class="flex items-center justify-between text-xs text-[#667781]">
                                    <span class="truncate flex items-center gap-1 text-[11px]">
                                        <i data-lucide="lock" class="w-3 h-3 text-[#00a884] shrink-0"></i>
                                        <span>Private squad channel</span>
                                    </span>
                                    @if($groupType === 'team')
                                        <span class="w-2 h-2 rounded-full bg-[#00a884] shrink-0"></span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @else
                        <div class="flex items-center gap-3 px-3.5 py-3 opacity-60 bg-slate-50 cursor-not-allowed border-l-4 border-l-transparent">
                            <div class="w-11 h-11 rounded-full bg-slate-300 text-slate-600 flex items-center justify-center shrink-0">
                                <i data-lucide="lock" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-[13px] font-semibold text-slate-500 truncate">Private Team</h4>
                                <span class="text-[11px] text-slate-400">Lead & members only</span>
                            </div>
                        </div>
                    @endif

                    <!-- Channel 2: Company Hub -->
                    <a href="{{ route('thoughts.index', ['group' => 'company']) }}" 
                       class="flex items-center gap-3 px-3.5 py-3 transition relative group cursor-pointer {{ $groupType === 'company' ? 'bg-[#f0f2f5] border-l-4 border-l-[#00a884]' : 'hover:bg-[#f5f6f6] border-l-4 border-l-transparent' }}">
                        <div class="w-11 h-11 rounded-full bg-[#128c7e] text-white flex items-center justify-center shrink-0 shadow-2xs font-bold ring-1 ring-[#128c7e]/20">
                            <i data-lucide="building-2" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                <h4 class="text-[13px] font-semibold text-[#111b21] truncate">EcoFone Company Hub</h4>
                                <span class="text-[10px] font-medium text-[#667781] shrink-0">All-Hands</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-[#667781]">
                                <span class="truncate flex items-center gap-1 text-[11px]">
                                    <i data-lucide="globe" class="w-3 h-3 text-[#128c7e] shrink-0"></i>
                                    <span>All-company discussions</span>
                                </span>
                                @if($groupType === 'company')
                                    <span class="w-2 h-2 rounded-full bg-[#00a884] shrink-0"></span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Team Members Roster (WhatsApp Web Contact List) -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 md:divide-[#f5f6f6]">
                <div class="px-3.5 py-2 bg-slate-50/80 md:bg-[#f0f2f5]/60 text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 md:text-[#54656f] flex items-center justify-between border-b border-slate-100 md:border-[#e9edef] select-none">
                    <span>Channel Contacts ({{ count($teamUsers) }})</span>
                    @if(auth()->user()->isTL() && $groupType === 'team')
                        <button type="button" onclick="openManageMembersModal()" class="text-[#008069] hover:underline font-bold cursor-pointer">
                            + Edit
                        </button>
                    @endif
                </div>

                @foreach($teamUsers as $member)
                    <div class="p-2.5 px-3.5 hover:bg-slate-50 md:hover:bg-[#f5f6f6] transition flex items-center justify-between gap-2 group cursor-pointer" onclick="mentionMember('{{ addslashes($member->name) }}')">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="relative shrink-0">
                                @if($member->avatar_url)
                                    <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-slate-150 md:bg-[#e9edef] text-slate-700 md:text-[#54656f] font-bold text-xs flex items-center justify-center ring-1 ring-slate-200 md:ring-[#d1d7db]">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="absolute bottom-0 right-0 w-2 h-2 bg-[#00a884] rounded-full ring-1 ring-white"></span>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs md:text-[13px] font-semibold text-slate-800 md:text-[#111b21] truncate flex items-center gap-1">
                                    <span>{{ $member->name }}</span>
                                    @if($member->isTL())
                                        <span class="px-1 py-0.2 rounded text-[8px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">TL</span>
                                    @endif
                                </div>
                                <div class="text-[10px] md:text-[11px] text-slate-400 md:text-[#667781] truncate">
                                    {{ $member->designation ?: ($member->isTL() ? 'Team Lead' : 'Staff Member') }}
                                </div>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-[#008069] opacity-0 group-hover:opacity-100 transition px-1.5 py-0.5 rounded bg-emerald-50 md:bg-emerald-100/60">@mention</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 💬 RIGHT MAIN CHAT AREA (Mobile WhatsApp App on Mobile, WhatsApp Web on Desktop) -->
        <div class="flex-1 flex flex-col h-full relative min-w-0" style="background-color: #efeae2; background-image: radial-gradient(#d5cdc4 0.75px, transparent 0.75px); background-size: 16px 16px;">

            <!-- WhatsApp Header Bar (Mobile Green #008069, Desktop WhatsApp Web #f0f2f5) -->
            <div class="h-14 sm:h-16 px-2.5 sm:px-4 bg-[#008069] md:bg-[#f0f2f5] text-white md:text-[#111b21] md:border-b md:border-[#e9edef] flex items-center justify-between shrink-0 shadow-xs md:shadow-none z-30 select-none">
                
                <!-- Left: Back Button, Avatar, Title & Subtitle -->
                <div class="flex items-center gap-2 sm:gap-3 min-w-0 cursor-pointer" onclick="toggleMobileSidebar(true)" title="Group Info">
                    <!-- Mobile WhatsApp Back Arrow (Returns to Dashboard) -->
                    <a href="{{ auth()->user()->isTL() ? route('tl.dashboard') : (auth()->user()->role === 'ceo' ? route('ceo.dashboard') : (auth()->user()->role === 'hr' ? route('hr.dashboard') : route('member.dashboard'))) }}" 
                       onclick="event.stopPropagation()"
                       class="md:hidden p-1 -ml-1 text-white hover:bg-black/10 rounded-full transition flex items-center" 
                       title="Back to Dashboard">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>

                    <!-- Channel Avatar (Round WhatsApp Style) -->
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-[#128c7e] md:bg-[#00a884] text-white flex items-center justify-center font-bold text-xs sm:text-sm shadow-2xs shrink-0 ring-2 ring-white/20 md:ring-[#00a884]/20">
                        <i data-lucide="{{ $groupType === 'team' ? 'users' : 'building-2' }}" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>

                    <!-- Channel Info -->
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <h2 class="text-xs sm:text-sm md:text-[15px] font-bold text-white md:text-[#111b21] truncate leading-tight">
                                {{ $groupType === 'team' ? (auth()->user()->isTL() ? auth()->user()->name . "'s Team" : ($teamUsers->firstWhere('role', 'tl')?->name ? $teamUsers->firstWhere('role', 'tl')->name . "'s Team" : 'Team Chat')) : 'EcoFone Company Hub' }}
                            </h2>
                            <span class="md:hidden px-1.5 py-0.2 rounded text-[8px] font-black uppercase bg-white/20 text-white">
                                {{ $groupType === 'team' ? 'Private' : 'Hub' }}
                            </span>
                        </div>
                        <!-- Mobile Subtitle -->
                        <p class="md:hidden text-[10px] sm:text-[11px] text-emerald-100 font-normal truncate mt-0.5">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-300 mr-1"></span>
                            <span>{{ count($teamUsers) }} members • tap for info</span>
                        </p>
                        <!-- Desktop Subtitle (WhatsApp Web Participant List) -->
                        <p class="hidden md:flex items-center gap-1.5 text-xs text-[#667781] font-normal truncate mt-0.5">
                            <span>{{ count($teamUsers) }} participants: {{ $teamUsers->pluck('name')->take(3)->implode(', ') }}@if(count($teamUsers) > 3), and others...@endif</span>
                        </p>
                    </div>
                </div>

                <!-- Right: Quick Channel Switch, Search & Actions -->
                <div class="flex items-center gap-1 sm:gap-2">
                    <!-- Desktop Live Sync Badge -->
                    <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-[#e9edef] text-[11px] font-semibold text-[#54656f] shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-[#00a884] animate-pulse"></span>
                        <span>Synced</span>
                    </div>

                    <!-- Desktop Quick Channel Switch Button -->
                    <a href="{{ route('thoughts.index', ['group' => $groupType === 'team' ? 'company' : 'team']) }}" 
                       class="hidden md:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white hover:bg-slate-100 border border-[#e9edef] text-xs font-bold text-[#111b21] transition shadow-2xs"
                       title="Switch to {{ $groupType === 'team' ? 'Company Hub' : 'Private Team' }}">
                        <i data-lucide="{{ $groupType === 'team' ? 'building-2' : 'lock' }}" class="w-3.5 h-3.5 text-[#00a884]"></i>
                        <span>{{ $groupType === 'team' ? 'Switch to Hub' : 'Switch to Team' }}</span>
                    </a>

                    <!-- Mobile Channel Toggle Pill (Switch Private vs Company with 1 tap) -->
                    <a href="{{ route('thoughts.index', ['group' => $groupType === 'team' ? 'company' : 'team']) }}" 
                       class="md:hidden px-2 py-1 rounded-lg bg-black/15 hover:bg-black/25 text-white text-[10px] font-bold flex items-center gap-1 transition"
                       title="Switch to {{ $groupType === 'team' ? 'Company Hub' : 'Private Team' }}">
                        <i data-lucide="{{ $groupType === 'team' ? 'building-2' : 'lock' }}" class="w-3 h-3"></i>
                        <span>{{ $groupType === 'team' ? 'Hub' : 'Private' }}</span>
                    </a>

                    <!-- Search Toggle -->
                    <button type="button" onclick="toggleMobileSearch()" class="p-1.5 md:p-2 text-white md:text-[#54656f] hover:bg-black/10 md:hover:bg-black/5 rounded-full transition cursor-pointer" title="Search Chat">
                        <i data-lucide="search" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </button>

                    <!-- Group Info / Members Button -->
                    <button type="button" onclick="toggleMobileSidebar(true)" class="p-1.5 md:p-2 text-white md:text-[#54656f] hover:bg-black/10 md:hover:bg-black/5 rounded-full transition cursor-pointer" title="Group Info">
                        <i data-lucide="more-vertical" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Slide-down Mobile Search Bar -->
            <div id="mobileSearchTray" class="hidden px-3 py-2 bg-white border-b border-slate-200 z-20 shadow-xs animate-in slide-in-from-top-2 duration-150">
                <div class="relative flex items-center">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3"></i>
                    <input 
                        type="text" 
                        id="mobileSearchInput" 
                        placeholder="Search in this chat..." 
                        class="w-full pl-9 pr-8 py-1.5 bg-slate-100 text-xs rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#008069] transition"
                        oninput="handleMobileSearch(this.value)"
                    >
                    <button type="button" onclick="toggleMobileSearch(false)" class="absolute right-2.5 text-slate-400 hover:text-slate-700 text-xs font-bold p-1">✕</button>
                </div>
            </div>

            <!-- Scrollable Messages Feed (WhatsApp Bubbles) -->
            <div id="chatMessagesScrollArea" class="flex-1 overflow-y-auto p-2.5 sm:p-4 md:p-5 pt-3 sm:pt-5 space-y-2.5 scroll-smooth">
                
                <div id="chatMessagesList" class="space-y-2 sm:space-y-2.5">
                    @php 
                        $lastDate = null; 
                        $userColors = ['#075e54', '#128c7e', '#0284c7', '#059669', '#d97706', '#7c3aed', '#db2777'];
                    @endphp

                    @foreach($thoughts as $thought)
                        @php
                            $msgDate = $thought->created_at->isToday() ? 'Today' : ($thought->created_at->isYesterday() ? 'Yesterday' : $thought->created_at->format('M d, Y'));
                            $isMe = $thought->user_id === auth()->id();
                            $colorIndex = abs(crc32($thought->user?->name ?? 'User')) % count($userColors);
                            $senderColor = $userColors[$colorIndex];
                            $seenBy = $thought->seen_by ?: [];
                            $reactions = $thought->reactions ?: [];
                            $groupedReactions = [];
                            foreach($reactions as $r) {
                                $groupedReactions[$r['emoji']][] = $r['user_name'];
                            }
                        @endphp

                        @if($lastDate !== $msgDate)
                            <div class="flex justify-center my-2 select-none">
                                <span class="px-3 py-1 rounded-lg bg-white/90 text-[#54656f] text-[10px] sm:text-[11px] font-bold shadow-2xs uppercase tracking-wider border border-slate-200/50">
                                    {{ $msgDate }}
                                </span>
                            </div>
                            @php $lastDate = $msgDate; @endphp
                        @endif

                        @if($isMe)
                            <!-- 🟢 Outgoing Message (Self: WhatsApp Light Green Bubble) -->
                            <div class="flex justify-end message-item group relative" data-message-id="{{ $thought->id }}" data-text="{{ strtolower($thought->content ?? '') }}">
                                <div class="flex flex-col items-end max-w-[88%] sm:max-w-[72%] md:max-w-[65%]">
                                    @if($thought->is_deleted)
                                        <div class="px-3 py-1.5 rounded-xl rounded-tr-none bg-[#e9edef] text-slate-500 text-xs italic border border-slate-200/60 flex items-center gap-1.5 select-none shadow-2xs">
                                            <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span>You unsent this message</span>
                                        </div>
                                    @else
                                        <div class="relative group/bubble w-fit rounded-xl rounded-tr-none px-3 py-1.5 bg-[#d9fdd3] text-[#111b21] shadow-2xs border border-[#c1f3b8]">
                                            @if($thought->media_path || $thought->drive_url)
                                                @php 
                                                    $mediaSrc = $thought->media_path ? asset($thought->media_path) : $thought->drive_url; 
                                                    $fileName = $thought->original_name ?? basename($thought->media_path ?? 'file');
                                                    $ext = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION) ?: 'FILE');
                                                @endphp
                                                <div class="mb-1 rounded-lg overflow-hidden">
                                                    @if($thought->media_type === 'image')
                                                        <div class="bg-black/5 rounded-lg overflow-hidden">
                                                            <img src="{{ $mediaSrc }}" alt="Media" onclick="openImageLightbox('{{ $mediaSrc }}')" class="max-h-64 rounded-lg object-cover cursor-pointer hover:opacity-95 transition">
                                                        </div>
                                                    @elseif($thought->media_type === 'video')
                                                        <div class="bg-black rounded-lg overflow-hidden">
                                                            <video controls class="max-h-64 rounded-lg bg-black w-full">
                                                                <source src="{{ $mediaSrc }}">
                                                            </video>
                                                        </div>
                                                    @elseif($thought->media_type === 'audio')
                                                        <div class="p-1.5 rounded-lg bg-[#b6eeb0]/50">
                                                            <audio controls class="w-full max-w-[240px] sm:max-w-[280px] h-8">
                                                                <source src="{{ $mediaSrc }}">
                                                            </audio>
                                                        </div>
                                                    @else
                                                        <div class="p-2 rounded-lg bg-[#c8f5c0] text-[#111b21] flex items-center justify-between gap-2.5 max-w-xs">
                                                            <div class="flex items-center gap-2 min-w-0">
                                                                <div class="w-8 h-8 rounded-lg bg-white/70 text-[#008069] font-black text-[9px] flex items-center justify-center shrink-0 shadow-2xs">
                                                                    {{ $ext }}
                                                                </div>
                                                                <div class="min-w-0">
                                                                    <p class="text-xs font-bold truncate">{{ $fileName }}</p>
                                                                    @if($thought->media_size)
                                                                        <span class="text-[10px] text-[#667781]">
                                                                            {{ $thought->media_size >= 1048576 ? round($thought->media_size / 1048576, 1) . ' MB' : round($thought->media_size / 1024, 1) . ' KB' }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <a href="{{ $mediaSrc }}" download="{{ $fileName }}" class="p-1.5 rounded-lg hover:bg-black/10 text-[#008069] transition shrink-0" title="Download">
                                                                <i data-lucide="download" class="w-4 h-4"></i>
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if(!empty($thought->content))
                                                <div class="text-[13px] sm:text-[14px] leading-snug break-words whitespace-pre-line select-text font-normal">{{ trim($thought->content) }}</div>
                                            @endif

                                            @if(!empty($thought->link_url))
                                                <div class="mt-1 p-1.5 rounded-lg bg-[#c8f5c0]/70">
                                                    <a href="{{ $thought->link_url }}" target="_blank" class="text-xs text-[#008069] hover:underline font-semibold flex items-center gap-1 truncate">
                                                        <i data-lucide="link" class="w-3 h-3 shrink-0"></i>
                                                        <span class="truncate">{{ $thought->link_url }}</span>
                                                    </a>
                                                </div>
                                            @endif

                                            <!-- Timestamp & Read Status Double-Checkmarks (WhatsApp Style) -->
                                            <div class="flex items-center justify-end gap-1 mt-0.5 text-[10px] text-[#667781] select-none">
                                                <span>{{ $thought->created_at->format('h:i A') }}</span>
                                                <button type="button" onclick="openReactionPickerModal({{ $thought->id }})" class="hover:text-slate-900 transition p-0.5 text-[#667781] opacity-70 hover:opacity-100 cursor-pointer" title="Add reaction">
                                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                                                </button>
                                                @if(count($seenBy) > 0)
                                                    <button type="button" onclick="openMessageInfoModal({{ $thought->id }})" class="hover:opacity-80 cursor-pointer text-[#53bdeb]" title="Seen by {{ count($seenBy) }} members">
                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 15" fill="none"><path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/></svg>
                                                    </button>
                                                @else
                                                    <svg class="w-3.5 h-3.5 text-[#8696a0]" viewBox="0 0 16 15" fill="none"><path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/></svg>
                                                @endif
                                            </div>

                                            <!-- WhatsApp Instant Floating Reaction Bar (Desktop Hover / Mobile Tap) -->
                                            <div class="hidden group-hover/bubble:flex items-center gap-0.5 sm:gap-1 absolute -top-7 right-0 sm:right-1 bg-white/95 backdrop-blur-xs border border-slate-200 shadow-lg rounded-full px-2 py-0.5 z-30 text-slate-700 select-none animate-in zoom-in-90 duration-100">
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '👍')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">👍</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '❤️')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">❤️</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😂')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😂</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😮')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😮</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😢')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😢</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '🙏')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">🙏</button>
                                                <button type="button" onclick="openReactionPickerModal({{ $thought->id }})" class="w-5 h-5 rounded-full bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-500 flex items-center justify-center transition cursor-pointer text-xs font-black ml-0.5" title="More">+</button>
                                                @if($thought->isUnsendableBy(auth()->user()))
                                                    <span class="w-px h-3 bg-slate-200 mx-0.5"></span>
                                                    <button type="button" onclick="unsendMessage({{ $thought->id }})" class="text-rose-500 hover:text-rose-700 font-bold text-[10px] px-1 cursor-pointer" title="Unsend">✕</button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Attached Reactions Dock -->
                                        <div class="reactions-dock flex flex-wrap gap-1 mt-1 justify-end" data-reactions-container="{{ $thought->id }}">
                                            @foreach($groupedReactions as $emoji => $names)
                                                @php $iReacted = in_array(auth()->user()->name, $names); @endphp
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '{{ $emoji }}')" 
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] cursor-pointer transition select-none {{ $iReacted ? 'bg-[#d9fdd3] border-[#008069] text-[#008069] font-black ring-1 ring-[#008069]/30 shadow-2xs' : 'bg-white hover:bg-slate-50 border-slate-200 text-slate-700 shadow-2xs' }}" 
                                                        title="{{ implode(', ', $names) }}">
                                                    <span class="leading-none text-sm">{{ $emoji }}</span>
                                                    <span class="text-[10px] font-bold">{{ count($names) }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <!-- ⚪ Incoming Teammate Message (WhatsApp Crisp White Bubble) -->
                            <div class="flex items-start gap-1.5 message-item group relative" data-message-id="{{ $thought->id }}" data-text="{{ strtolower($thought->content ?? '') }}">
                                <div class="shrink-0 pt-0.5">
                                    @if($thought->user?->avatar_url)
                                        <img src="{{ $thought->user->avatar_url }}" alt="{{ $thought->user->name }}" class="w-7 h-7 rounded-full object-cover ring-1 ring-slate-200 shadow-2xs">
                                    @else
                                        <div class="w-7 h-7 rounded-full text-white font-bold text-xs flex items-center justify-center shadow-2xs" style="background-color: {{ $senderColor }};">
                                            {{ strtoupper(substr($thought->user?->name ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-col items-start max-w-[88%] sm:max-w-[72%] md:max-w-[65%]">
                                    @if($thought->is_deleted)
                                        <div class="px-3 py-1.5 rounded-xl rounded-tl-none bg-[#e9edef] text-slate-500 text-xs italic border border-slate-200/60 flex items-center gap-1.5 select-none shadow-2xs">
                                            <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span>This message was deleted</span>
                                        </div>
                                    @else
                                        <div class="relative group/bubble w-fit rounded-xl rounded-tl-none px-3 py-1.5 bg-white text-[#111b21] shadow-2xs border border-slate-200/70">
                                            <!-- Sender Name (Colored WhatsApp Group Style) -->
                                            <div class="text-[11px] font-bold leading-tight mb-0.5 flex items-center gap-1" style="color: {{ $senderColor }};">
                                                <span>{{ $thought->user?->name ?? 'Member' }}</span>
                                                @if($thought->user?->isTL())
                                                    <span class="px-1 py-0.2 rounded text-[8px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">TL</span>
                                                @endif
                                            </div>

                                            @if($thought->media_path || $thought->drive_url)
                                                @php 
                                                    $mediaSrc = $thought->media_path ? asset($thought->media_path) : $thought->drive_url; 
                                                    $fileName = $thought->original_name ?? basename($thought->media_path ?? 'file');
                                                    $ext = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION) ?: 'FILE');
                                                @endphp
                                                <div class="mb-1 rounded-lg overflow-hidden">
                                                    @if($thought->media_type === 'image')
                                                        <div class="bg-black/5 rounded-lg overflow-hidden">
                                                            <img src="{{ $mediaSrc }}" alt="Media" onclick="openImageLightbox('{{ $mediaSrc }}')" class="max-h-64 rounded-lg object-cover cursor-pointer hover:opacity-95 transition">
                                                        </div>
                                                    @elseif($thought->media_type === 'video')
                                                        <div class="bg-black rounded-lg overflow-hidden">
                                                            <video controls class="max-h-64 rounded-lg bg-black w-full">
                                                                <source src="{{ $mediaSrc }}">
                                                            </video>
                                                        </div>
                                                    @elseif($thought->media_type === 'audio')
                                                        <div class="p-1.5 rounded-lg bg-slate-100">
                                                            <audio controls class="w-full max-w-[240px] sm:max-w-[280px] h-8">
                                                                <source src="{{ $mediaSrc }}">
                                                            </audio>
                                                        </div>
                                                    @else
                                                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 flex items-center justify-between gap-2.5 max-w-xs">
                                                            <div class="flex items-center gap-2 min-w-0">
                                                                <div class="w-8 h-8 rounded-lg bg-[#008069]/10 text-[#008069] font-black text-[9px] flex items-center justify-center shrink-0">
                                                                    {{ $ext }}
                                                                </div>
                                                                <div class="min-w-0">
                                                                    <p class="text-xs font-bold truncate">{{ $fileName }}</p>
                                                                    @if($thought->media_size)
                                                                        <span class="text-[10px] text-slate-400">
                                                                            {{ $thought->media_size >= 1048576 ? round($thought->media_size / 1048576, 1) . ' MB' : round($thought->media_size / 1024, 1) . ' KB' }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <a href="{{ $mediaSrc }}" download="{{ $fileName }}" class="p-1.5 rounded-lg hover:bg-slate-200 text-[#008069] transition shrink-0" title="Download">
                                                                <i data-lucide="download" class="w-4 h-4"></i>
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if(!empty($thought->content))
                                                <div class="text-[13px] sm:text-[14px] leading-snug break-words whitespace-pre-line select-text text-[#111b21]">{{ trim($thought->content) }}</div>
                                            @endif

                                            @if(!empty($thought->link_url))
                                                <div class="mt-1 p-1.5 rounded-lg bg-slate-50 border border-slate-150">
                                                    <a href="{{ $thought->link_url }}" target="_blank" class="text-xs text-[#008069] hover:underline font-semibold flex items-center gap-1 truncate">
                                                        <i data-lucide="link" class="w-3 h-3 shrink-0"></i>
                                                        <span class="truncate">{{ $thought->link_url }}</span>
                                                    </a>
                                                </div>
                                            @endif

                                            <!-- Timestamp Dock -->
                                            <div class="flex items-center justify-end gap-1 mt-0.5 text-[10px] text-[#667781] select-none">
                                                <span>{{ $thought->created_at->format('h:i A') }}</span>
                                                <button type="button" onclick="openReactionPickerModal({{ $thought->id }})" class="hover:text-slate-900 transition p-0.5 text-[#667781] opacity-70 hover:opacity-100 cursor-pointer" title="Add reaction">
                                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                                                </button>
                                            </div>

                                            <!-- WhatsApp Instant Floating Reaction Bar (Desktop Hover / Mobile Tap) -->
                                            <div class="hidden group-hover/bubble:flex items-center gap-0.5 sm:gap-1 absolute -top-7 left-0 sm:left-1 bg-white/95 backdrop-blur-xs border border-slate-200 shadow-lg rounded-full px-2 py-0.5 z-30 text-slate-700 select-none animate-in zoom-in-90 duration-100">
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '👍')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">👍</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '❤️')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">❤️</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😂')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😂</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😮')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😮</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😢')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😢</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '🙏')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">🙏</button>
                                                <button type="button" onclick="openReactionPickerModal({{ $thought->id }})" class="w-5 h-5 rounded-full bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-500 flex items-center justify-center transition cursor-pointer text-xs font-black ml-0.5" title="More">+</button>
                                                @if(auth()->user()->isTL())
                                                    <span class="w-px h-3 bg-slate-200 mx-0.5"></span>
                                                    <button type="button" onclick="deleteMessage({{ $thought->id }})" class="text-rose-500 hover:text-rose-700 font-bold text-[10px] px-1 cursor-pointer" title="Delete">✕</button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Attached Reactions Dock -->
                                        <div class="reactions-dock flex flex-wrap gap-1 mt-1 justify-start" data-reactions-container="{{ $thought->id }}">
                                            @foreach($groupedReactions as $emoji => $names)
                                                @php $iReacted = in_array(auth()->user()->name, $names); @endphp
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '{{ $emoji }}')" 
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] cursor-pointer transition select-none {{ $iReacted ? 'bg-[#d9fdd3] border-[#008069] text-[#008069] font-black ring-1 ring-[#008069]/30 shadow-2xs' : 'bg-white hover:bg-slate-50 border-slate-200 text-slate-700 shadow-2xs' }}" 
                                                        title="{{ implode(', ', $names) }}">
                                                    <span class="leading-none text-sm">{{ $emoji }}</span>
                                                    <span class="text-[10px] font-bold">{{ count($names) }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

            </div>

            <!-- Attachment Preview Tray -->
            <div id="attachmentPreviewTray" class="hidden px-4 py-2 bg-white/95 backdrop-blur-md border-t border-slate-200 flex items-center justify-between gap-2 shadow-xs">
                <div class="flex items-center gap-2 min-w-0">
                    <i data-lucide="paperclip" class="w-4 h-4 text-[#008069] shrink-0"></i>
                    <span id="attachedFileName" class="text-xs font-bold text-slate-800 truncate"></span>
                    <span id="attachedFileSize" class="text-[11px] text-slate-400 shrink-0"></span>
                </div>
                <button type="button" onclick="clearSelectedAttachment()" class="text-slate-400 hover:text-slate-700 text-xs font-bold p-1">✕</button>
            </div>

            <!-- Link Attachment Input Tray -->
            <div id="linkInputTray" class="hidden px-3 sm:px-4 py-2 bg-white/95 backdrop-blur-md border-t border-slate-200 shadow-xs">
                <div class="relative flex items-center">
                    <i data-lucide="link" class="w-3.5 h-3.5 text-slate-400 absolute left-3"></i>
                    <input type="url" id="linkUrlInput" placeholder="Paste link (https://...)" class="w-full pl-8 pr-7 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-[#008069]">
                    <button type="button" onclick="toggleLinkInput(false)" class="absolute right-2.5 text-slate-400 hover:text-slate-700 text-xs font-bold p-1">✕</button>
                </div>
            </div>

            <!-- ⌨️ Mobile WhatsApp Floating Input Bar & Desktop WhatsApp Web Bottom Toolbar -->
            <div class="p-2 sm:p-3 md:px-4 md:py-3 bg-transparent md:bg-[#f0f2f5] md:border-t md:border-[#e9edef] shrink-0 relative z-20">
                <form id="chatMessageForm" onsubmit="sendChatMessage(event)" class="flex items-end md:items-center gap-1.5 sm:gap-2">
                    @csrf
                    <input type="hidden" name="group_type" value="{{ $groupType }}">

                    <!-- WhatsApp Pill Input Capsule -->
                    <div class="flex-1 bg-white rounded-3xl md:rounded-lg shadow-sm md:shadow-none border border-slate-200/80 md:border-[#e9edef] flex items-center px-2 sm:px-3 md:px-3.5 py-1.5 md:py-2 gap-1 sm:gap-1.5 md:gap-2 min-w-0 focus-within:ring-1 focus-within:ring-[#00a884]">
                        <!-- Emoji Picker Button -->
                        <button type="button" onclick="toggleEmojiPicker()" class="p-1.5 text-slate-500 md:text-[#54656f] hover:text-[#008069] md:hover:text-[#111b21] active:scale-95 transition shrink-0 cursor-pointer" title="Insert Emoji">
                            <i data-lucide="smile" class="w-5 h-5"></i>
                        </button>

                        <!-- Real WhatsApp / Instagram Style Emoji Drawer -->
                        <div id="emojiPickerTray" class="hidden absolute bottom-14 left-2 sm:left-4 w-[calc(100vw-1rem)] sm:w-96 max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col z-50 overflow-hidden animate-in zoom-in-95 duration-100">
                            <!-- Search & Close Header -->
                            <div class="p-2.5 bg-slate-50 border-b border-slate-200 flex items-center gap-2">
                                <div class="relative flex items-center flex-1">
                                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3"></i>
                                    <input 
                                        type="text" 
                                        id="chatEmojiSearchInput" 
                                        placeholder="Search emojis (smile, heart, fire)..." 
                                        class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-[#008069] text-slate-800"
                                        oninput="filterChatEmojis(this.value)"
                                    >
                                </div>
                                <button type="button" onclick="toggleEmojiPicker(false)" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-200 transition text-xs font-bold" title="Close">✕</button>
                            </div>

                            <!-- WhatsApp Style Category Tabs -->
                            <div class="px-2 py-1.5 bg-slate-50/70 border-b border-slate-100 flex items-center gap-1 overflow-x-auto text-[11px] font-bold select-none scrollbar-none">
                                <button type="button" onclick="switchChatEmojiCategory('all')" class="chat-cat-tab px-2.5 py-1 rounded-lg bg-white text-[#008069] shadow-2xs border border-slate-200 shrink-0">✨ All</button>
                                <button type="button" onclick="switchChatEmojiCategory('smileys')" class="chat-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">😀 Smileys</button>
                                <button type="button" onclick="switchChatEmojiCategory('gestures')" class="chat-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">👍 Hands</button>
                                <button type="button" onclick="switchChatEmojiCategory('hearts')" class="chat-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">❤️ Hearts</button>
                                <button type="button" onclick="switchChatEmojiCategory('desi')" class="chat-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">🇮🇳 Desi</button>
                                <button type="button" onclick="switchChatEmojiCategory('work')" class="chat-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">🚀 Work</button>
                                <button type="button" onclick="switchChatEmojiCategory('animals')" class="chat-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">🍕 Food/Pets</button>
                            </div>

                            <!-- Emoji Grid -->
                            <div id="emojiGridContainer" class="p-2.5 overflow-y-auto max-h-60 grid grid-cols-7 sm:grid-cols-8 gap-1.5 text-xl select-none min-h-[180px]"></div>
                        </div>

                        <!-- Text Input Field (WhatsApp Placeholder "Message") -->
                        <input 
                            type="text" 
                            id="chatMessageInput" 
                            name="content" 
                            autocomplete="off" 
                            placeholder="Message" 
                            class="flex-1 bg-transparent text-[14px] sm:text-sm text-[#111b21] placeholder:text-slate-400 md:placeholder:text-[#8696a0] outline-none min-w-0 py-1"
                        >

                        <!-- Media Attachment Trigger -->
                        <label class="p-1.5 text-slate-500 md:text-[#54656f] hover:text-[#008069] md:hover:text-[#111b21] active:scale-95 transition shrink-0 cursor-pointer" title="Attach Photos, Audio or Docs">
                            <i data-lucide="paperclip" class="w-5 h-5"></i>
                            <input type="file" id="chatMediaInput" name="media" class="hidden" onchange="handleChatFileSelect(this)">
                        </label>

                        <!-- Link Trigger -->
                        <button type="button" onclick="toggleLinkInput()" class="p-1.5 text-slate-500 md:text-[#54656f] hover:text-[#008069] md:hover:text-[#111b21] active:scale-95 transition shrink-0 cursor-pointer" title="Attach Web Link">
                            <i data-lucide="link" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- WhatsApp Circular Green Send Button -->
                    <button 
                        type="submit" 
                        id="sendBtn" 
                        class="w-11 h-11 md:w-10 md:h-10 rounded-full bg-[#00a884] hover:bg-[#008f6f] text-white flex items-center justify-center shadow-md md:shadow-2xs active:scale-95 transition shrink-0 cursor-pointer"
                        title="Send Message"
                    >
                        <i data-lucide="send" class="w-5 h-5 md:w-4 md:h-4 ml-0.5"></i>
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>

<!-- Message Info Modal (Read Receipts) -->
<div id="messageInfoModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xs w-full p-5 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-black text-slate-900">Message Info</h3>
            <button type="button" onclick="closeMessageInfoModal()" class="text-slate-400 hover:text-slate-700 text-sm font-bold">✕</button>
        </div>
        <div class="py-3">
            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-2">Read by</div>
            <div id="seenByList" class="space-y-1.5 max-h-52 overflow-y-auto"></div>
        </div>
        <button type="button" onclick="closeMessageInfoModal()" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">Close</button>
    </div>
</div>

<!-- TL Manage Group Members Modal -->
@if(auth()->user()->isTL())
<div id="manageMembersModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-5 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-black text-slate-900">Manage Team Channel</h3>
                <p class="text-[10px] text-slate-400">Add or remove members from this private group</p>
            </div>
            <button type="button" onclick="closeManageMembersModal()" class="text-slate-400 hover:text-slate-700 text-sm font-bold">✕</button>
        </div>

        <div class="py-3 border-b border-slate-100">
            <div class="flex items-center gap-1.5">
                <select id="newMemberSelect" class="flex-1 px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-1 focus:ring-[#008069]">
                    <option value="">Select team member...</option>
                    @foreach($candidateUsers as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                    @endforeach
                </select>
                <button type="button" onclick="addMemberToGroup()" class="px-3.5 py-2 rounded-xl bg-[#008069] hover:bg-[#006e5a] text-white text-xs font-bold cursor-pointer transition">Add</button>
            </div>
        </div>

        <div class="py-2.5">
            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-2">Current Members ({{ count($teamUsers) }})</div>
            <div class="space-y-1.5 max-h-52 overflow-y-auto">
                @foreach($teamUsers as $u)
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 text-xs">
                        <span class="font-bold text-slate-800 truncate">{{ $u->name }}</span>
                        @if($u->id !== auth()->id())
                            <button type="button" onclick="removeMemberFromGroup({{ $u->id }}, '{{ addslashes($u->name) }}')" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 text-[10px] font-bold cursor-pointer transition">Remove</button>
                        @else
                            <span class="text-[10px] font-bold text-[#008069]">Lead Admin</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <button type="button" onclick="closeManageMembersModal()" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl mt-2 cursor-pointer transition">Done</button>
    </div>
</div>
@endif

<!-- Image Lightbox Modal -->
<div id="imageLightbox" class="hidden fixed inset-0 z-50 bg-black/85 backdrop-blur-xs flex items-center justify-center p-3 cursor-pointer" onclick="closeImageLightbox()">
    <div class="relative max-w-4xl max-h-[90vh] flex items-center justify-center" onclick="event.stopPropagation()">
        <img id="lightboxImage" src="" alt="Enlarged Photo" class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain">
        <button type="button" onclick="closeImageLightbox()" class="absolute -top-10 right-0 text-white font-bold text-sm bg-white/20 hover:bg-white/30 px-3 py-1 rounded-full transition">✕ Close</button>
    </div>
</div>

<!-- WhatsApp Custom Reaction Picker Modal (+ button) -->
<div id="messageReactionPickerModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-3 animate-in fade-in duration-100" onclick="closeReactionPickerModal()">
    <div class="bg-white rounded-3xl max-w-sm sm:max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[80vh]" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="p-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-[#008069] flex items-center justify-center text-xs font-black">+</span>
                <h3 class="text-xs sm:text-sm font-black text-slate-800">Add Reaction</h3>
            </div>
            <button type="button" onclick="closeReactionPickerModal()" class="w-7 h-7 rounded-xl hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition text-sm font-bold cursor-pointer">✕</button>
        </div>

        <!-- Search Bar -->
        <div class="p-2.5 bg-white border-b border-slate-100">
            <div class="relative flex items-center">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3"></i>
                <input 
                    type="text" 
                    id="reactionModalSearchInput" 
                    placeholder="Search emoji (fire, clap, cake)..." 
                    class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-[#008069] text-slate-800 transition" 
                    oninput="filterReactionModalEmojis(this.value)"
                >
            </div>
        </div>

        <!-- Category Selector -->
        <div class="px-2 py-1.5 bg-slate-50/70 border-b border-slate-100 flex items-center gap-1 overflow-x-auto text-[11px] font-bold select-none scrollbar-none">
            <button type="button" onclick="switchReactionCategory('all')" class="reaction-cat-tab px-2.5 py-1 rounded-lg bg-white text-[#008069] shadow-2xs border border-slate-200 shrink-0">✨ All</button>
            <button type="button" onclick="switchReactionCategory('smileys')" class="reaction-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">😀 Smileys</button>
            <button type="button" onclick="switchReactionCategory('gestures')" class="reaction-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">👍 Hands</button>
            <button type="button" onclick="switchReactionCategory('hearts')" class="reaction-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">❤️ Hearts</button>
            <button type="button" onclick="switchReactionCategory('desi')" class="reaction-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">🇮🇳 Desi</button>
            <button type="button" onclick="switchReactionCategory('work')" class="reaction-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">🚀 Work</button>
            <button type="button" onclick="switchReactionCategory('animals')" class="reaction-cat-tab px-2 py-1 rounded-lg text-slate-600 hover:bg-white shrink-0">🍕 Food/Pets</button>
        </div>

        <!-- Emoji Grid -->
        <div id="reactionModalGrid" class="p-3 overflow-y-auto flex-1 grid grid-cols-7 sm:grid-cols-8 gap-2 text-2xl select-none min-h-[220px]"></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let latestMessageId = {{ $thoughts->max('id') ?: 0 }};
const currentUserId = {{ auth()->id() }};
const isUserTL = {{ auth()->user()->isTL() ? 'true' : 'false' }};
const activeGroupType = '{{ $groupType }}';
let pollingInterval = null;

let activeReactionMessageId = null;
let currentChatEmojiCat = 'all';
let currentReactionModalCat = 'all';

const emojiCatalog = [
    // Popular Smileys
    { emoji: '😀', name: 'grinning face smile happy', cat: 'smileys' },
    { emoji: '😃', name: 'smiling face big eyes happy joyful', cat: 'smileys' },
    { emoji: '😄', name: 'smiling face smiling eyes joy glad', cat: 'smileys' },
    { emoji: '😁', name: 'beaming face grin excited cheerful', cat: 'smileys' },
    { emoji: '😆', name: 'grinning squinting face laugh haha hilarious', cat: 'smileys' },
    { emoji: '😅', name: 'grinning face sweat relief phew nervous', cat: 'smileys' },
    { emoji: '😂', name: 'face with tears of joy lol haha crying laughing dead', cat: 'smileys' },
    { emoji: '🤣', name: 'rolling on the floor laughing rofl dying', cat: 'smileys' },
    { emoji: '🥲', name: 'smiling face with tear proud grateful bittersweet', cat: 'smileys' },
    { emoji: '🥹', name: 'face holding back tears emotional cute please overwhelmed', cat: 'smileys' },
    { emoji: '☺️', name: 'smiling face warm blush modest relaxed', cat: 'smileys' },
    { emoji: '😊', name: 'smiling face blush pleased content', cat: 'smileys' },
    { emoji: '😇', name: 'smiling face halo angel innocent holy', cat: 'smileys' },
    { emoji: '🙂', name: 'slightly smiling face ok fine calm', cat: 'smileys' },
    { emoji: '😉', name: 'winking face wink flirt secret cheeky', cat: 'smileys' },
    { emoji: '😌', name: 'relieved face calm peaceful zen satisfied', cat: 'smileys' },
    { emoji: '😍', name: 'heart eyes love crush romantic enamored adoration', cat: 'smileys' },
    { emoji: '🥰', name: 'smiling face hearts adore love affection cute sweet', cat: 'smileys' },
    { emoji: '😘', name: 'blowing kiss kiss love mwah romance sweetheart', cat: 'smileys' },
    { emoji: '😋', name: 'savoring food delicious yum tasty tongue hungry', cat: 'smileys' },
    { emoji: '😛', name: 'face tongue playful goofy silly joke', cat: 'smileys' },
    { emoji: '😜', name: 'winking face tongue crazy wild fun party', cat: 'smileys' },
    { emoji: '🤪', name: 'zany face goofy weird silly wacky wild', cat: 'smileys' },
    { emoji: '😝', name: 'squinting face tongue playful goofy teasing', cat: 'smileys' },
    { emoji: '🤑', name: 'money-mouth face rich cash dollar profit wealth', cat: 'smileys' },
    { emoji: '🤗', name: 'open hands hug warmth embrace welcome cheer', cat: 'smileys' },
    { emoji: '🤭', name: 'hand over mouth giggle oops teehee secret chuckle', cat: 'smileys' },
    { emoji: '🤫', name: 'shushing face quiet silence secret shh hush mute', cat: 'smileys' },
    { emoji: '🤔', name: 'thinking face hmm ponder wonder curious evaluate consider', cat: 'smileys' },
    { emoji: '🤐', name: 'zipper mouth silent secret zip confidential sealed', cat: 'smileys' },
    { emoji: '🤨', name: 'raised eyebrow skeptic doubt suspicious really hmm', cat: 'smileys' },
    { emoji: '😐', name: 'neutral face straight poker whatever ok', cat: 'smileys' },
    { emoji: '😑', name: 'expressionless face blank unamused deadpan done', cat: 'smileys' },
    { emoji: '😶', name: 'face without mouth speechless mute quiet blank', cat: 'smileys' },
    { emoji: '😏', name: 'smirking face smug flirt slick clever sneaky', cat: 'smileys' },
    { emoji: '😒', name: 'unamused face annoyed bored irritated unimpressed', cat: 'smileys' },
    { emoji: '🙄', name: 'rolling eyes eye roll duh whatever', cat: 'smileys' },
    { emoji: '😬', name: 'grimacing face awkward yikes oops nervous cringe', cat: 'smileys' },
    { emoji: '🤥', name: 'lying face pinocchio lie cap fake dishonest long nose', cat: 'smileys' },
    { emoji: '😔', name: 'pensive face sad sorrow regret depressed mourn', cat: 'smileys' },
    { emoji: '😪', name: 'sleepy face tired snooze exhausted', cat: 'smileys' },
    { emoji: '🤤', name: 'drooling face hungry craving delicious appetizing want', cat: 'smileys' },
    { emoji: '😴', name: 'sleeping face zzz goodnight tired rest sleep', cat: 'smileys' },
    { emoji: '😷', name: 'medical mask sick quarantine mask doctor flu hospital', cat: 'smileys' },
    { emoji: '🤒', name: 'thermometer fever sick ill unwell temperature disease', cat: 'smileys' },
    { emoji: '🤕', name: 'head bandage hurt injury ouch wound accident recovery', cat: 'smileys' },
    { emoji: '🤢', name: 'nauseated face disgusted sick gross ew barf green', cat: 'smileys' },
    { emoji: '🤮', name: 'face vomiting puke puking gross spew sick', cat: 'smileys' },
    { emoji: '🤧', name: 'sneezing face sneeze allergy tissue cold sick flu', cat: 'smileys' },
    { emoji: '🥵', name: 'hot face sweat summer heat spicy fever warm', cat: 'smileys' },
    { emoji: '🥶', name: 'cold face freezing winter ice frozen blue frost', cat: 'smileys' },
    { emoji: '🥴', name: 'woozy face tipsy drunk dizzy weird groggy intoxicated', cat: 'smileys' },
    { emoji: '😵', name: 'crossed out eyes dead knocked out dizzy shock stunned', cat: 'smileys' },
    { emoji: '🤯', name: 'exploding head mind blown shock wow impossible insane eureka', cat: 'smileys' },
    { emoji: '🤠', name: 'cowboy hat face yeehaw western sheriff texas cool', cat: 'smileys' },
    { emoji: '🥳', name: 'partying face party celebration birthday congrats hooray', cat: 'smileys' },
    { emoji: '😎', name: 'sunglasses cool confident slick boss swag awesome', cat: 'smileys' },
    { emoji: '🤓', name: 'nerd face glasses geek smart genius tech programmer', cat: 'smileys' },
    { emoji: '🧐', name: 'monocle detective inspect analyze classy curious examine', cat: 'smileys' },
    { emoji: '😕', name: 'confused face puzzled doubt lost what huh', cat: 'smileys' },
    { emoji: '😟', name: 'worried face anxious nervous stressed concerned fear', cat: 'smileys' },
    { emoji: '😮', name: 'open mouth surprised shocked gasp wow amazed what', cat: 'smileys' },
    { emoji: '😯', name: 'hushed face stunned quiet surprised bewildered', cat: 'smileys' },
    { emoji: '😲', name: 'astonished face amazed shocked disbelief overwhelmed', cat: 'smileys' },
    { emoji: '😳', name: 'flushed face blushed embarrassed wide eyes shock shy', cat: 'smileys' },
    { emoji: '🥺', name: 'pleading face begging puppy eyes cute please mercy cry', cat: 'smileys' },
    { emoji: '😦', name: 'frowning face open mouth dismay surprise dismayed', cat: 'smileys' },
    { emoji: '😨', name: 'fearful face scared panic fear dread horror terrified', cat: 'smileys' },
    { emoji: '😰', name: 'anxious face sweat nervous pressure stress tension', cat: 'smileys' },
    { emoji: '😥', name: 'sad relieved face phew close call whew relief', cat: 'smileys' },
    { emoji: '😢', name: 'crying face tear sad emotional upset sorrow weep', cat: 'smileys' },
    { emoji: '😭', name: 'loudly crying face bawling heartbroken devastated sob scream', cat: 'smileys' },
    { emoji: '😱', name: 'screaming fear scream shock horrified terror', cat: 'smileys' },
    { emoji: '😤', name: 'steam nose proud determined victory win angry focus', cat: 'smileys' },
    { emoji: '😡', name: 'enraged face angry mad furious pissed red wrath', cat: 'smileys' },
    { emoji: '😠', name: 'angry face mad grumpy irritated cross annoyed', cat: 'smileys' },
    { emoji: '🤬', name: 'symbols mouth cursing swearing bleep angry rage', cat: 'smileys' },
    { emoji: '😈', name: 'smiling face horns devil naughty mischief evil evil smile', cat: 'smileys' },
    { emoji: '💀', name: 'skull dead dying laugh funny skeleton rip bones', cat: 'smileys' },
    { emoji: '☠️', name: 'skull crossbones danger poison pirate lethal warning hazard', cat: 'smileys' },
    { emoji: '💩', name: 'pile of poo poop funny crap turd', cat: 'smileys' },
    { emoji: '👻', name: 'ghost spooky halloween spirit phantom boo', cat: 'smileys' },
    { emoji: '🤖', name: 'robot bot AI tech automation artificial intelligence', cat: 'smileys' },

    // Gestures & Hands
    { emoji: '👍', name: 'thumbs up ok like agree approve good yes correct nice done', cat: 'gestures' },
    { emoji: '👎', name: 'thumbs down dislike bad disapprove reject no fail wrong', cat: 'gestures' },
    { emoji: '👊', name: 'oncoming fist fist bump power punch attack bro hit', cat: 'gestures' },
    { emoji: '✊', name: 'raised fist solidarity power protest strength resist', cat: 'gestures' },
    { emoji: '🤛', name: 'left-facing fist bump fistbump bro handshake respect', cat: 'gestures' },
    { emoji: '🤜', name: 'right-facing fist bump fistbump bro partner respect', cat: 'gestures' },
    { emoji: '🤞', name: 'crossed fingers hope wish good luck fortune pray', cat: 'gestures' },
    { emoji: '✌️', name: 'victory hand peace two v sign chill win', cat: 'gestures' },
    { emoji: '🫰', name: 'finger heart k-pop love money snap korean cute', cat: 'gestures' },
    { emoji: '🤟', name: 'love-you gesture rock love metal ily sign language', cat: 'gestures' },
    { emoji: '🤘', name: 'sign of the horns rock metal concert music party awesome', cat: 'gestures' },
    { emoji: '👌', name: 'ok hand perfect okay excellent zero fine spot on', cat: 'gestures' },
    { emoji: '🤌', name: 'pinched fingers italian what do you want chef kiss why mama mia', cat: 'gestures' },
    { emoji: '🤏', name: 'pinching hand little small tiny bit slight fraction', cat: 'gestures' },
    { emoji: '👈', name: 'pointing left direction look check there left', cat: 'gestures' },
    { emoji: '👉', name: 'pointing right direction look check there right see', cat: 'gestures' },
    { emoji: '👆', name: 'pointing up above look agree top this', cat: 'gestures' },
    { emoji: '👇', name: 'pointing down below read bottom inspect down', cat: 'gestures' },
    { emoji: '☝️', name: 'index pointing up first one important note idea attention', cat: 'gestures' },
    { emoji: '✋', name: 'raised hand stop high five palm halt wait hold on', cat: 'gestures' },
    { emoji: '🤚', name: 'raised back of hand stop wait backhand halt', cat: 'gestures' },
    { emoji: '🖐️', name: 'hand with fingers splayed five high five open palm reach', cat: 'gestures' },
    { emoji: '👋', name: 'waving hand wave hello hi goodbye bye greeting ciao', cat: 'gestures' },
    { emoji: '🤙', name: 'call me hand phone shaka hang loose surf cool aloha', cat: 'gestures' },
    { emoji: '🫵', name: 'index pointing viewer you targeting attention chosen', cat: 'gestures' },
    { emoji: '👏', name: 'clapping hands applause bravo congrats good job praise salute', cat: 'gestures' },
    { emoji: '🙌', name: 'raising hands praise celebration hooray hallelujah hype success', cat: 'gestures' },
    { emoji: '🫶', name: 'heart hands love care affection cute heart together', cat: 'gestures' },
    { emoji: '👐', name: 'open hands openness hug jazz hands welcome honesty', cat: 'gestures' },
    { emoji: '🤲', name: 'palms up together prayer dua bless offer hold', cat: 'gestures' },
    { emoji: '🤝', name: 'handshake deal agreement partnership shake hello agreed contract meetup', cat: 'gestures' },
    { emoji: '🙏', name: 'folded hands pray please namaste thanks thank you hope respect grateful bowing', cat: 'gestures' },
    { emoji: '✍️', name: 'writing hand note signing write author signature compose', cat: 'gestures' },
    { emoji: '💅', name: 'nail polish sassy fab flawless careless manicure salon', cat: 'gestures' },
    { emoji: '🤳', name: 'selfie camera photo pose picture phone snapshot', cat: 'gestures' },
    { emoji: '💪', name: 'flexed biceps strong muscle strength gym workout flex fitness power', cat: 'gestures' },
    { emoji: '🧠', name: 'brain smart think intelligence mind genius mental intellect', cat: 'gestures' },
    { emoji: '👀', name: 'eyes look watching see peek suspicious observe witness glance spy', cat: 'gestures' },

    // Hearts & Affection
    { emoji: '❤️', name: 'red heart love passion romantic affection favourite sweetheart', cat: 'hearts' },
    { emoji: '🩷', name: 'pink heart sweet cute love affection gentle', cat: 'hearts' },
    { emoji: '🧡', name: 'orange heart warmth care friendship orange sunset', cat: 'hearts' },
    { emoji: '💛', name: 'yellow heart friendship joy sunny gold happy', cat: 'hearts' },
    { emoji: '💚', name: 'green heart nature eco envy health green vitality', cat: 'hearts' },
    { emoji: '💙', name: 'blue heart loyalty trust peace blue ocean cool', cat: 'hearts' },
    { emoji: '🩵', name: 'light blue heart calm gentle fresh sky pastel', cat: 'hearts' },
    { emoji: '💜', name: 'purple heart royalty magic luxury purple amethyst', cat: 'hearts' },
    { emoji: '🖤', name: 'black heart dark sorrow grief gothic edge', cat: 'hearts' },
    { emoji: '🩶', name: 'grey heart neutral silver minimal modern stone', cat: 'hearts' },
    { emoji: '🤍', name: 'white heart pure peace angel clean crystal sincere', cat: 'hearts' },
    { emoji: '🤎', name: 'brown heart earth chocolate cozy warm autumn', cat: 'hearts' },
    { emoji: '💔', name: 'broken heart heartbreak break up sad pain hurt dump grief', cat: 'hearts' },
    { emoji: '❤️‍🔥', name: 'heart on fire burning passion flame desire intense lit love', cat: 'hearts' },
    { emoji: '❤️‍🩹', name: 'mending heart healing recovery better bandage repair cure', cat: 'hearts' },
    { emoji: '❣️', name: 'heart exclamation emphasis excited love exclamation mark', cat: 'hearts' },
    { emoji: '💕', name: 'two hearts love floating affection romantic sweet', cat: 'hearts' },
    { emoji: '💞', name: 'revolving hearts revolving dizzy in love romance swirl', cat: 'hearts' },
    { emoji: '💓', name: 'beating heart pulse heartbeat nervous excited flutter', cat: 'hearts' },
    { emoji: '💗', name: 'growing heart expanding blush warm love bigger', cat: 'hearts' },
    { emoji: '💖', name: 'sparkling heart shiny love sparkle glam magical glitter', cat: 'hearts' },
    { emoji: '💘', name: 'heart arrow cupid love shot valentine romance', cat: 'hearts' },
    { emoji: '💝', name: 'heart ribbon gift present valentine surprise wrap package', cat: 'hearts' },
    { emoji: '✨', name: 'sparkles magic shiny clean star sparkle awesome special glitter shine', cat: 'hearts' },
    { emoji: '🌟', name: 'glowing star shine bright champion outstanding stellar prime', cat: 'hearts' },
    { emoji: '⭐', name: 'star rating favorite gold review ranking brilliance', cat: 'hearts' },
    { emoji: '💥', name: 'collision boom bang explosion impact wow blast smash', cat: 'hearts' },
    { emoji: '🔥', name: 'fire lit hot flame burn trending viral popular dope fierce', cat: 'hearts' },
    { emoji: '💯', name: 'hundred points 100 perfect score keep it real factual full grade', cat: 'hearts' },

    // Desi, India & Culture
    { emoji: '🇮🇳', name: 'flag India bharat indian tricolor tiranga proud nation', cat: 'desi' },
    { emoji: '🪔', name: 'diya lamp diwali light deepam festival puja flame sacred', cat: 'desi' },
    { emoji: '🕉️', name: 'om omkara hindu sacred symbol spiritual shanti peace mantra', cat: 'desi' },
    { emoji: '🪷', name: 'lotus flower national flower sacred bloom petal flora water lily', cat: 'desi' },
    { emoji: '🏏', name: 'cricket bat ball match ipl sport batsman trophy boundary', cat: 'desi' },
    { emoji: '🍛', name: 'curry rice food desi spicy dal biryani bowl dinner lunch', cat: 'desi' },
    { emoji: '☕', name: 'hot beverage chai tea coffee morning refreshment masala chai cup', cat: 'desi' },
    { emoji: '🛺', name: 'auto rickshaw tuk tuk tempo travel transport ride drive', cat: 'desi' },
    { emoji: '🐅', name: 'tiger royal bengal tiger wild majestic predator striped', cat: 'desi' },
    { emoji: '🐘', name: 'elephant ganesh wisdom huge wild trunk animal', cat: 'desi' },
    { emoji: '🦚', name: 'peacock national bird beautiful colorful feathers dance royal', cat: 'desi' },
    { emoji: '🥭', name: 'mango aam king of fruits sweet tropical alphonso dessert yellow', cat: 'desi' },
    { emoji: '💰', name: 'money bag cash rupee wealth rich profit bonus fund finance', cat: 'desi' },
    { emoji: '🎇', name: 'sparkler diwali festival celebration cracker night fun', cat: 'desi' },
    { emoji: '🎆', name: 'fireworks celebration diwali new year party sky spectacular night', cat: 'desi' },
    { emoji: '💐', name: 'bouquet flowers congratulation greetings welcome celebration floral', cat: 'desi' },
    { emoji: '🌺', name: 'hibiscus flower puja nature bloom red flower garden', cat: 'desi' },
    { emoji: '🚩', name: 'triangular flag bhagwa win milestone banner victory flag saffron', cat: 'desi' },

    // Work, Tech & Activities
    { emoji: '🚀', name: 'rocket launch deploy startup fast speed boost scale fly space blast', cat: 'work' },
    { emoji: '🎉', name: 'party popper tada congrats celebrate success win holiday yay', cat: 'work' },
    { emoji: '🎊', name: 'confetti ball celebration party festivity success', cat: 'work' },
    { emoji: '🎈', name: 'balloon birthday party celebration float festive', cat: 'work' },
    { emoji: '🎁', name: 'wrapped gift present surprise bonus reward package birthday', cat: 'work' },
    { emoji: '🏆', name: 'trophy champion winner award first place prize champion gold', cat: 'work' },
    { emoji: '🥇', name: '1st place medal gold winner champion top number one', cat: 'work' },
    { emoji: '🥈', name: '2nd place medal silver runner up second rank', cat: 'work' },
    { emoji: '🥉', name: '3rd place medal bronze third', cat: 'work' },
    { emoji: '🎯', name: 'bullseye direct hit target goal focus aim kpi accurate precision', cat: 'work' },
    { emoji: '💡', name: 'light bulb idea inspiration solution think innovation smart genius invent', cat: 'work' },
    { emoji: '📢', name: 'loudspeaker announcement megaphone notice broadcast shout alert news', cat: 'work' },
    { emoji: '🔔', name: 'bell notification reminder chime ring alert update notice', cat: 'work' },
    { emoji: '📌', name: 'pushpin pin pinned important remember save bookmark highlight', cat: 'work' },
    { emoji: '📍', name: 'round pushpin location map spot here place destination', cat: 'work' },
    { emoji: '📎', name: 'paperclip attach attachment file link connect document clip', cat: 'work' },
    { emoji: '💼', name: 'briefcase work job office business portfolio suit professional', cat: 'work' },
    { emoji: '📊', name: 'bar chart analytics stats metrics growth report trends dashboard presentation', cat: 'work' },
    { emoji: '📈', name: 'chart increasing upward trend profit growth success stocks bull', cat: 'work' },
    { emoji: '📉', name: 'chart decreasing downward trend drop loss fall bear', cat: 'work' },
    { emoji: '💻', name: 'laptop computer tech code software dev work macbook pc developer', cat: 'work' },
    { emoji: '🖥️', name: 'desktop computer monitor display pc workspace station screen', cat: 'work' },
    { emoji: '📱', name: 'mobile phone smartphone iphone android device call cellular screen', cat: 'work' },
    { emoji: '⌨️', name: 'keyboard typing code tech hardware input keys', cat: 'work' },
    { emoji: '⚙️', name: 'gear settings system config machinery engine build options', cat: 'work' },
    { emoji: '🔧', name: 'wrench repair fix tools maintenance mechanic configure assemble', cat: 'work' },
    { emoji: '🛠️', name: 'hammer and wrench build construction debug developer tools toolkit', cat: 'work' },
    { emoji: '🔒', name: 'locked padlock security safe protect private password encrypted auth', cat: 'work' },
    { emoji: '🔓', name: 'unlocked padlock open access freedom public decipher key', cat: 'work' },
    { emoji: '🔑', name: 'key access login secret solution unlock api credential', cat: 'work' },
    { emoji: '📦', name: 'package box delivery shipping parcel deploy release bundle product', cat: 'work' },
    { emoji: '📅', name: 'calendar date schedule appointment deadline meeting event month', cat: 'work' },
    { emoji: '⏰', name: 'alarm clock time timer alert countdown hurry wakeup clock schedule', cat: 'work' },
    { emoji: '⏳', name: 'hourglass not done waiting loading pending time remaining timer process', cat: 'work' },
    { emoji: '✅', name: 'check mark button verified done complete approved pass yes confirmed success', cat: 'work' },
    { emoji: '❌', name: 'cross mark cancel error reject fail wrong no ban decline delete', cat: 'work' },
    { emoji: '⚠️', name: 'warning danger caution attention alert issue notice hazard', cat: 'work' },
    { emoji: '🚫', name: 'prohibited forbidden stop ban no entry restricted stop', cat: 'work' },

    // Animals & Food
    { emoji: '🐶', name: 'dog puppy pet cute canine loyal bark animal', cat: 'animals' },
    { emoji: '🐱', name: 'cat kitty pet cute feline meow whiskers animal', cat: 'animals' },
    { emoji: '🦁', name: 'lion king predator brave strength roar safari animal', cat: 'animals' },
    { emoji: '🐼', name: 'panda bear cute chill lazy bamboo zoo animal', cat: 'animals' },
    { emoji: '🦊', name: 'fox clever sly smart animal red wildlife', cat: 'animals' },
    { emoji: '🐰', name: 'rabbit bunny fast cute carrot leap easter animal', cat: 'animals' },
    { emoji: '🐻', name: 'bear grizzly honey forest strong animal wild', cat: 'animals' },
    { emoji: '🐨', name: 'koala cute sleepy australia eucalyptus wildlife', cat: 'animals' },
    { emoji: '🐵', name: 'monkey playful mischievous chimp jungle animal cheeky', cat: 'animals' },
    { emoji: '🦅', name: 'eagle freedom sharp high predator fly bird wildlife sky', cat: 'animals' },
    { emoji: '🦉', name: 'owl wise night wisdom bird nocturnal eyes smart', cat: 'animals' },
    { emoji: '🦋', name: 'butterfly beauty change gentle colorful insect wings spring', cat: 'animals' },
    { emoji: '🐝', name: 'honeybee bee busy work hard honey pollen sting insect', cat: 'animals' },
    { emoji: '🍕', name: 'pizza slice cheese food delicious party dinner snack italian', cat: 'animals' },
    { emoji: '🍔', name: 'hamburger burger fast food patty bite lunch diner sandwich', cat: 'animals' },
    { emoji: '🍟', name: 'french fries potato chips snack crispy fast food salty', cat: 'animals' },
    { emoji: '🥪', name: 'sandwich lunch snack bread meal breakfast sandwich', cat: 'animals' },
    { emoji: '🌮', name: 'taco mexican food crispy spicy fiesta snack dinner', cat: 'animals' },
    { emoji: '🍣', name: 'sushi japanese fish rice gourmet raw roll wasabi', cat: 'animals' },
    { emoji: '🍩', name: 'doughnut donut sweet dessert glazed snack pastry sugar bake', cat: 'animals' },
    { emoji: '🍦', name: 'soft ice cream dessert cold sweet summer treat dairy cone', cat: 'animals' },
    { emoji: '🎂', name: 'birthday cake sweet celebration party dessert candle festive', cat: 'animals' },
    { emoji: '🍫', name: 'chocolate bar sweet snack dark milk cocoa candy dessert', cat: 'animals' },
    { emoji: '🍿', name: 'popcorn movie snack cinema entertainment butter theater', cat: 'animals' },
    { emoji: '🍻', name: 'clinking beer mugs drink party cheers celebrate alcohol bar pub', cat: 'animals' },
    { emoji: '🥂', name: 'clinking glasses champagne toast cheers celebration wine anniversary', cat: 'animals' },
    { emoji: '🥤', name: 'cup straw drink soda juice beverage cold refreshment sip', cat: 'animals' }
];

document.addEventListener("DOMContentLoaded", function() {
    scrollToBottom();
    startLivePolling();
    switchChatEmojiCategory('all');
    parseTwemoji();

    // Event delegation for Chat Input Emoji Picker buttons
    const emojiContainer = document.getElementById('emojiGridContainer');
    if (emojiContainer) {
        emojiContainer.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-emoji]');
            if (!btn) return;
            const emojiChar = btn.getAttribute('data-emoji');
            insertEmoji(emojiChar);
        });
    }

    // Event delegation for WhatsApp Custom Reaction Modal picker (+ button)
    const reactionGrid = document.getElementById('reactionModalGrid');
    if (reactionGrid) {
        reactionGrid.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-emoji]');
            if (!btn) return;
            const emojiChar = btn.getAttribute('data-emoji');
            selectCustomReaction(emojiChar);
        });
    }

    // Global click listener to close emoji picker when clicking outside
    document.addEventListener('click', function(e) {
        const tray = document.getElementById('emojiPickerTray');
        const trigger = e.target.closest('[onclick*="toggleEmojiPicker"]');
        if (tray && !tray.classList.contains('hidden')) {
            if (!tray.contains(e.target) && !trigger) {
                tray.classList.add('hidden');
            }
        }
    });

    // Escape key to dismiss modals and emoji picker
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            toggleEmojiPicker(false);
            closeReactionPickerModal();
            closeMessageInfoModal();
            closeImageLightbox();
            toggleMobileSidebar(false);
            toggleMobileSearch(false);
        }
    });

    const searchInput = document.getElementById('searchChatInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            filterMessagesInDom(e.target.value);
        });
    }
});

// 🟢 Mobile Navigation & Search Helpers
function toggleMobileSidebar(show) {
    if (window.innerWidth >= 768) return;
    const sidebar = document.getElementById('chatSidebar');
    if (!sidebar) return;
    if (show) {
        sidebar.classList.remove('hidden');
        sidebar.classList.add('flex');
    } else {
        sidebar.classList.add('hidden');
        sidebar.classList.remove('flex');
    }
}

function toggleMobileSearch(force = null) {
    const tray = document.getElementById('mobileSearchTray');
    if (!tray) return;
    const isHidden = tray.classList.contains('hidden');
    const shouldShow = force !== null ? force : isHidden;
    if (shouldShow) {
        tray.classList.remove('hidden');
        const inp = document.getElementById('mobileSearchInput');
        if (inp) inp.focus();
    } else {
        tray.classList.add('hidden');
        filterMessagesInDom('');
        const inp = document.getElementById('mobileSearchInput');
        if (inp) inp.value = '';
    }
}

function handleMobileSearch(val) {
    filterMessagesInDom(val);
}

function filterMessagesInDom(query) {
    const q = (query || '').toLowerCase().trim();
    const items = document.querySelectorAll('.message-item');
    items.forEach(item => {
        const text = item.getAttribute('data-text') || '';
        if (!q || text.includes(q)) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
}

function mentionMember(name) {
    toggleMobileSidebar(false);
    const input = document.getElementById('chatMessageInput');
    if (input) {
        input.value = (input.value ? input.value + ' ' : '') + '@' + name + ' ';
        input.focus();
    }
}

function scrollToBottom() {
    const area = document.getElementById('chatMessagesScrollArea');
    if (area) {
        area.scrollTop = area.scrollHeight;
    }
}

function parseTwemoji(el = document.body) {
    if (typeof twemoji !== 'undefined') {
        twemoji.parse(el, { folder: 'svg', ext: '.svg' });
    }
}

// 🟢 Chat Input Emoji Picker Logic (WhatsApp Style)
function renderChatEmojis(list) {
    const container = document.getElementById('emojiGridContainer');
    if (!container) return;

    if (!list || list.length === 0) {
        container.innerHTML = `<div class="col-span-7 sm:col-span-8 py-6 text-center text-xs text-slate-400 font-semibold">No emojis found</div>`;
        return;
    }

    container.innerHTML = list.map(item => `
        <button type="button" data-emoji="${item.emoji}" class="p-1 hover:bg-slate-100 rounded-xl transition-all hover:scale-130 active:scale-95 cursor-pointer flex items-center justify-center leading-none text-xl select-none" title="${item.name}">
            ${item.emoji}
        </button>
    `).join('');
    parseTwemoji(container);
}

function switchChatEmojiCategory(catKey) {
    currentChatEmojiCat = catKey;
    const searchInput = document.getElementById('chatEmojiSearchInput');
    if (searchInput) searchInput.value = '';

    // Update active tab styles
    document.querySelectorAll('.chat-cat-tab').forEach(tab => {
        tab.classList.remove('bg-white', 'text-[#008069]', 'shadow-2xs', 'border', 'border-slate-200');
        tab.classList.add('text-slate-600');
    });
    const activeTab = event?.currentTarget || document.querySelector(`.chat-cat-tab[onclick*="'${catKey}'"]`);
    if (activeTab) {
        activeTab.classList.remove('text-slate-600');
        activeTab.classList.add('bg-white', 'text-[#008069]', 'shadow-2xs', 'border', 'border-slate-200');
    }

    const filtered = (catKey === 'all') 
        ? emojiCatalog 
        : emojiCatalog.filter(e => e.cat === catKey);

    renderChatEmojis(filtered);
}

function filterChatEmojis(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) {
        switchChatEmojiCategory(currentChatEmojiCat);
        return;
    }

    const matched = emojiCatalog.filter(e => {
        const inCat = (currentChatEmojiCat === 'all' || e.cat === currentChatEmojiCat);
        const inName = e.name.toLowerCase().includes(q) || e.emoji.includes(q);
        return inCat && inName;
    });

    renderChatEmojis(matched);
}

function toggleEmojiPicker(force = null) {
    const tray = document.getElementById('emojiPickerTray');
    if (!tray) return;
    if (force !== null) {
        if (force) {
            tray.classList.remove('hidden');
            switchChatEmojiCategory(currentChatEmojiCat);
            const search = document.getElementById('chatEmojiSearchInput');
            if (search) search.focus();
        } else {
            tray.classList.add('hidden');
        }
    } else {
        const isClosed = tray.classList.contains('hidden');
        tray.classList.toggle('hidden');
        if (isClosed) {
            switchChatEmojiCategory(currentChatEmojiCat);
            const search = document.getElementById('chatEmojiSearchInput');
            if (search) search.focus();
        }
    }
}

function insertEmoji(emoji) {
    const input = document.getElementById('chatMessageInput');
    if (!input) return;
    
    const start = input.selectionStart || input.value.length;
    const end = input.selectionEnd || input.value.length;
    const val = input.value;
    input.value = val.substring(0, start) + emoji + val.substring(end);
    input.selectionStart = input.selectionEnd = start + emoji.length;
    input.focus();
}

function toggleLinkInput(show = null) {
    const tray = document.getElementById('linkInputTray');
    if (!tray) return;
    if (show === null) {
        tray.classList.toggle('hidden');
    } else if (show) {
        tray.classList.remove('hidden');
    } else {
        tray.classList.add('hidden');
        document.getElementById('linkUrlInput').value = '';
    }
    if (!tray.classList.contains('hidden')) {
        document.getElementById('linkUrlInput').focus();
    }
}

function handleChatFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        // 50MB client-side limit
        if (file.size > 52428800) {
            alert('File size exceeds 50MB limit. Please select a smaller file.');
            input.value = '';
            clearSelectedAttachment();
            return;
        }
        document.getElementById('attachedFileName').textContent = file.name;
        const sizeStr = file.size >= 1048576 
            ? (file.size / 1048576).toFixed(1) + ' MB' 
            : (file.size / 1024).toFixed(1) + ' KB';
        const sizeEl = document.getElementById('attachedFileSize');
        if (sizeEl) sizeEl.textContent = `(${sizeStr})`;
        document.getElementById('attachmentPreviewTray').classList.remove('hidden');
    }
}

function clearSelectedAttachment() {
    const input = document.getElementById('chatMediaInput');
    if (input) input.value = '';
    const sizeEl = document.getElementById('attachedFileSize');
    if (sizeEl) sizeEl.textContent = '';
    document.getElementById('attachmentPreviewTray').classList.add('hidden');
}

function openImageLightbox(src) {
    document.getElementById('lightboxImage').src = src;
    document.getElementById('imageLightbox').classList.remove('hidden');
}

function closeImageLightbox() {
    document.getElementById('imageLightbox').classList.add('hidden');
    document.getElementById('lightboxImage').src = '';
}

// 🟢 Send Message (WhatsApp Universal Flow)
async function sendChatMessage(event) {
    event.preventDefault();
    const input = document.getElementById('chatMessageInput');
    const content = input.value.trim();
    const mediaInput = document.getElementById('chatMediaInput');
    const linkInput = document.getElementById('linkUrlInput');
    const linkUrl = linkInput ? linkInput.value.trim() : '';
    const hasMedia = mediaInput.files && mediaInput.files[0];
    const sendBtn = document.getElementById('sendBtn');

    if (!content && !hasMedia && !linkUrl) return;

    const origBtnHtml = sendBtn ? sendBtn.innerHTML : '';
    if (sendBtn) {
        sendBtn.disabled = true;
        sendBtn.innerHTML = `
            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        `;
    }

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('group_type', activeGroupType);
    if (content) formData.append('content', content);
    if (linkUrl) formData.append('link_url', linkUrl);
    if (hasMedia) formData.append('media', mediaInput.files[0]);

    input.value = '';
    clearSelectedAttachment();
    toggleLinkInput(false);

    try {
        const response = await fetch('{{ route('thoughts.store') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (response.ok && data.success && data.thought) {
            renderMessageBubble(data.thought);
            latestMessageId = Math.max(latestMessageId, data.thought.id);
            scrollToBottom();
        } else {
            alert(data.message || 'Unable to send message or file. Please try again.');
        }
    } catch (err) {
        console.error('Failed to send message:', err);
        alert('An error occurred. Please check your network and try again.');
    } finally {
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = origBtnHtml;
            if (window.lucide) lucide.createIcons();
        }
    }
}

// 🟢 Unsend Message
async function unsendMessage(thoughtId) {
    if (!confirm('Unsend this message for everyone?')) return;

    try {
        const res = await fetch(`/thoughts/${thoughtId}/unsend`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        });
        const data = await res.json();
        if (res.ok && data.success) {
            updateDeletedMessageBubble(thoughtId, true);
        } else {
            alert(data.message || 'Unable to unsend message.');
        }
    } catch (err) {
        console.error('Unsend error:', err);
    }
}

// 🟢 TL Delete Message
async function deleteMessage(thoughtId) {
    if (!confirm('Delete this message as Team Lead?')) return;

    try {
        const res = await fetch(`/thoughts/${thoughtId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        });
        const data = await res.json();
        if (res.ok && data.success) {
            updateDeletedMessageBubble(thoughtId, false);
        } else {
            alert(data.message || 'Unable to delete message.');
        }
    } catch (err) {
        console.error('Delete error:', err);
    }
}

function updateDeletedMessageBubble(id, isMe) {
    const el = document.querySelector(`[data-message-id="${id}"]`);
    if (!el) return;

    const innerCol = el.querySelector('.flex-col');
    if (innerCol) {
        innerCol.innerHTML = `
            <div class="px-3 py-1.5 rounded-xl ${isMe ? 'rounded-tr-none' : 'rounded-tl-none'} bg-[#e9edef] text-slate-500 text-xs italic border border-slate-200/60 flex items-center gap-1.5 select-none shadow-2xs">
                <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>${isMe ? 'You unsent this message' : 'This message was deleted'}</span>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    }
}

// 🟢 WhatsApp Message Reaction Modal (+ button) Logic
function openReactionPickerModal(thoughtId) {
    activeReactionMessageId = thoughtId;
    const modal = document.getElementById('messageReactionPickerModal');
    const search = document.getElementById('reactionModalSearchInput');
    if (search) search.value = '';
    switchReactionCategory('all');
    if (modal) modal.classList.remove('hidden');
    if (search) search.focus();
}

function closeReactionPickerModal() {
    const modal = document.getElementById('messageReactionPickerModal');
    if (modal) modal.classList.add('hidden');
    activeReactionMessageId = null;
}

function switchReactionCategory(catKey) {
    currentReactionModalCat = catKey;
    const search = document.getElementById('reactionModalSearchInput');
    const query = search ? search.value.trim() : '';

    // Update active tab styles
    document.querySelectorAll('.reaction-cat-tab').forEach(tab => {
        tab.classList.remove('bg-white', 'text-[#008069]', 'shadow-2xs', 'border', 'border-slate-200');
        tab.classList.add('text-slate-600');
    });
    const activeTab = event?.currentTarget || document.querySelector(`.reaction-cat-tab[onclick*="'${catKey}'"]`);
    if (activeTab) {
        activeTab.classList.remove('text-slate-600');
        activeTab.classList.add('bg-white', 'text-[#008069]', 'shadow-2xs', 'border', 'border-slate-200');
    }

    renderReactionModalEmojis(catKey, query);
}

function filterReactionModalEmojis(query) {
    renderReactionModalEmojis(currentReactionModalCat, query);
}

function renderReactionModalEmojis(catKey, query = '') {
    const container = document.getElementById('reactionModalGrid');
    if (!container) return;

    const q = (query || '').toLowerCase().trim();
    const list = emojiCatalog.filter(e => {
        const inCat = (catKey === 'all' || e.cat === catKey);
        const inName = !q || e.name.toLowerCase().includes(q) || e.emoji.includes(q);
        return inCat && inName;
    });

    if (list.length === 0) {
        container.innerHTML = `<div class="col-span-7 sm:col-span-8 py-8 text-center text-xs text-slate-400 font-semibold">No emojis found</div>`;
        return;
    }

    container.innerHTML = list.map(item => `
        <button type="button" data-emoji="${item.emoji}" class="p-1.5 hover:bg-slate-100 rounded-xl transition-all hover:scale-135 active:scale-90 cursor-pointer flex items-center justify-center leading-none text-2xl select-none" title="${item.name}">
            ${item.emoji}
        </button>
    `).join('');
    parseTwemoji(container);
}

async function selectCustomReaction(emoji) {
    if (!activeReactionMessageId) return;
    const msgId = activeReactionMessageId;
    closeReactionPickerModal();
    await reactToMessage(msgId, emoji);
}

// 🟢 Build WhatsApp-Style Reaction Badges HTML
function buildReactionsHtml(reactions, isMe, messageId) {
    if (!reactions || reactions.length === 0) {
        return `<div class="reactions-dock flex flex-wrap gap-1 mt-1 ${isMe ? 'justify-end' : 'justify-start'}" data-reactions-container="${messageId}"></div>`;
    }

    const grouped = {};
    reactions.forEach(r => {
        if (!grouped[r.emoji]) grouped[r.emoji] = [];
        grouped[r.emoji].push(r.user_name || 'Member');
    });

    const badges = Object.keys(grouped).map(emoji => {
        const names = grouped[emoji];
        const iReacted = reactions.some(r => r.emoji === emoji && r.user_id === currentUserId);
        const activeCls = iReacted
            ? 'bg-[#d9fdd3] border-[#008069] text-[#008069] font-black ring-1 ring-[#008069]/30 shadow-2xs'
            : 'bg-white hover:bg-slate-50 border-slate-200 text-slate-700 shadow-2xs';

        return `
            <button type="button" onclick="reactToMessage(${messageId}, '${emoji}')" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] cursor-pointer transition select-none ${activeCls}" title="${names.join(', ')}">
                <span class="leading-none text-sm">${emoji}</span>
                <span class="text-[10px] font-bold">${names.length}</span>
            </button>
        `;
    }).join('');

    return `<div class="reactions-dock flex flex-wrap gap-1 mt-1 ${isMe ? 'justify-end' : 'justify-start'}" data-reactions-container="${messageId}">${badges}</div>`;
}

function updateMessageReactionsDom(messageId, reactions, isMe) {
    const container = document.querySelector(`[data-reactions-container="${messageId}"]`);
    if (container) {
        container.outerHTML = buildReactionsHtml(reactions, isMe, messageId);
        parseTwemoji();
    }
}

// 🟢 React to Message (Instant WhatsApp-style with Authoritative Sync)
async function reactToMessage(thoughtId, emoji) {
    try {
        const res = await fetch(`/thoughts/${thoughtId}/react`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ emoji })
        });
        const data = await res.json();
        if (res.ok && data.success && data.reactions) {
            const isMe = data.thought ? data.thought.is_me : true;
            updateMessageReactionsDom(thoughtId, data.reactions, isMe);
        }
    } catch (err) {
        console.error('React error:', err);
    }
}

// 🟢 Message Info (Seen list)
async function openMessageInfoModal(thoughtId) {
    try {
        const res = await fetch(`/thoughts/${thoughtId}/info`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (res.ok && data.success) {
            const listEl = document.getElementById('seenByList');
            if (data.seen_by && data.seen_by.length > 0) {
                listEl.innerHTML = data.seen_by.map(s => `
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                        <span class="font-bold text-slate-800">${s.user_name}</span>
                        <span class="text-[10px] text-slate-400">${s.seen_at}</span>
                    </div>
                `).join('');
            } else {
                listEl.innerHTML = `<div class="text-xs text-slate-400 text-center py-2">Not opened yet</div>`;
            }
            document.getElementById('messageInfoModal').classList.remove('hidden');
        }
    } catch (err) {
        console.error('Info error:', err);
    }
}

function closeMessageInfoModal() {
    document.getElementById('messageInfoModal').classList.add('hidden');
}

// 🟢 TL Member Management
async function addMemberToGroup() {
    const select = document.getElementById('newMemberSelect');
    const userId = select.value;
    if (!userId) return;

    try {
        const res = await fetch('{{ route('thoughts.members.add') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        });
        const data = await res.json();
        if (res.ok && data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Error adding member.');
        }
    } catch (err) {
        console.error('Add member error:', err);
    }
}

async function removeMemberFromGroup(userId, userName) {
    if (!confirm(`Remove ${userName} from this group?`)) return;

    try {
        const res = await fetch(`/thoughts/members/${userId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (res.ok && data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Error removing member.');
        }
    } catch (err) {
        console.error('Remove member error:', err);
    }
}

function openManageMembersModal() {
    const modal = document.getElementById('manageMembersModal');
    if (modal) modal.classList.remove('hidden');
}

function closeManageMembersModal() {
    const modal = document.getElementById('manageMembersModal');
    if (modal) modal.classList.add('hidden');
}

// 🟢 Real-time Automatic Live Synchronization (Smart Tab-Aware Polling)
function startLivePolling() {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(() => {
        pollNewMessages();
    }, 4000);
}

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        pollNewMessages();
    }
});

window.addEventListener('focus', () => {
    pollNewMessages();
});

async function pollNewMessages() {
    if (document.hidden) return;

    try {
        const res = await fetch(`{{ route('thoughts.messages') }}?group=${activeGroupType}&after_id=${latestMessageId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (res.ok && data.success) {
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    if (!document.querySelector(`[data-message-id="${msg.id}"]`)) {
                        renderMessageBubble(msg);
                    }
                });

                latestMessageId = data.latest_id;
                scrollToBottom();
            }

            if (data.updated_messages && data.updated_messages.length > 0) {
                data.updated_messages.forEach(msg => {
                    updateMessageReactionsDom(msg.id, msg.reactions, msg.is_me);
                    if (msg.is_deleted) {
                        updateDeletedMessageBubble(msg.id, msg.is_me);
                    }
                });
            }
        }
    } catch (err) {
        console.warn('Live sync notice:', err);
    }
}

// 🟢 Render Message HTML (Authentic WhatsApp Bubble)
function renderMessageBubble(msg) {
    const container = document.getElementById('chatMessagesList');
    if (!container) return;

    const isMe = msg.user_id === currentUserId;
    const item = document.createElement('div');
    item.className = `${isMe ? 'flex justify-end' : 'flex items-start gap-1.5'} message-item animate-in fade-in duration-100 group relative`;
    item.setAttribute('data-message-id', msg.id);
    item.setAttribute('data-text', (msg.content || '').toLowerCase());

    let mediaHtml = '';
    if (msg.media_url && !msg.is_deleted) {
        if (msg.media_type === 'image') {
            mediaHtml = `
                <div class="mb-1 rounded-lg overflow-hidden bg-black/5">
                    <img src="${msg.media_url}" onclick="openImageLightbox('${msg.media_url}')" class="max-h-64 rounded-lg object-cover cursor-pointer hover:opacity-95 transition">
                </div>`;
        } else if (msg.media_type === 'video') {
            mediaHtml = `
                <div class="mb-1 rounded-lg overflow-hidden bg-black">
                    <video controls class="max-h-64 rounded-lg bg-black w-full">
                        <source src="${msg.media_url}">
                    </video>
                </div>`;
        } else if (msg.media_type === 'audio') {
            mediaHtml = `
                <div class="mb-1 rounded-lg p-1.5 ${isMe ? 'bg-[#b6eeb0]/50' : 'bg-slate-100'}">
                    <audio controls class="w-full max-w-[240px] sm:max-w-[280px] h-8">
                        <source src="${msg.media_url}">
                    </audio>
                </div>`;
        } else {
            const fileName = msg.original_name || 'Attached File';
            const ext = (msg.media_extension || 'FILE').toUpperCase();
            const size = msg.media_size_human || '';
            mediaHtml = `
                <div class="mb-1 p-2 rounded-lg ${isMe ? 'bg-[#c8f5c0]' : 'bg-slate-50 border border-slate-200'} text-[#111b21] flex items-center justify-between gap-2.5 max-w-xs">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-8 h-8 rounded-lg ${isMe ? 'bg-white/70 text-[#008069]' : 'bg-[#008069]/10 text-[#008069]'} font-black text-[9px] flex items-center justify-center shrink-0">
                            ${ext}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold truncate">${fileName}</p>
                            ${size ? `<span class="text-[10px] text-[#667781]">${size}</span>` : ''}
                        </div>
                    </div>
                    <a href="${msg.media_url}" download="${fileName}" class="p-1.5 rounded-lg hover:bg-black/10 text-[#008069] transition shrink-0" title="Download">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </a>
                </div>`;
        }
    }

    let linkHtml = '';
    if (msg.link_url && !msg.is_deleted) {
        linkHtml = `
            <div class="mt-1 p-1.5 rounded-lg ${isMe ? 'bg-[#c8f5c0]/70' : 'bg-slate-50 border border-slate-150'}">
                <a href="${msg.link_url}" target="_blank" class="text-xs text-[#008069] hover:underline font-semibold flex items-center gap-1 truncate">
                    <span class="truncate">${msg.link_url}</span>
                </a>
            </div>`;
    }

    let checkmarks = '';
    if (isMe && !msg.is_deleted) {
        if (msg.is_seen) {
            checkmarks = `
                <button type="button" onclick="openMessageInfoModal(${msg.id})" class="hover:opacity-80 cursor-pointer text-[#53bdeb] ml-0.5" title="Seen by ${msg.seen_count} members">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 16 15" fill="none"><path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/></svg>
                </button>`;
        } else {
            checkmarks = `
                <svg class="w-3.5 h-3.5 text-[#8696a0] ml-0.5" viewBox="0 0 16 15" fill="none"><path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/></svg>`;
        }
    }

    const unsendBtn = (msg.can_unsend && !msg.is_deleted) ? `
        <span class="w-px h-3 bg-slate-200 mx-0.5"></span>
        <button type="button" onclick="unsendMessage(${msg.id})" class="text-rose-500 hover:text-rose-700 font-bold text-[10px] px-1 cursor-pointer" title="Unsend">✕</button>` : '';

    const deleteBtn = (isUserTL && !isMe && !msg.is_deleted) ? `
        <span class="w-px h-3 bg-slate-200 mx-0.5"></span>
        <button type="button" onclick="deleteMessage(${msg.id})" class="text-rose-500 hover:text-rose-700 font-bold text-[10px] px-1 cursor-pointer" title="Delete">✕</button>` : '';

    const reactionsHtml = buildReactionsHtml(msg.reactions || [], isMe, msg.id);

    if (isMe) {
        item.innerHTML = `
            <div class="flex flex-col items-end max-w-[88%] sm:max-w-[72%] md:max-w-[65%]">
                ${msg.is_deleted ? `
                    <div class="px-3 py-1.5 rounded-xl rounded-tr-none bg-[#e9edef] text-slate-500 text-xs italic border border-slate-200/60 flex items-center gap-1.5 select-none shadow-2xs">
                        <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>You unsent this message</span>
                    </div>
                ` : `
                    <div class="relative group/bubble w-fit rounded-xl rounded-tr-none px-3 py-1.5 bg-[#d9fdd3] text-[#111b21] shadow-2xs border border-[#c1f3b8]">
                        ${mediaHtml}
                        ${msg.content ? `<div class="text-[13px] sm:text-[14px] leading-snug break-words whitespace-pre-line select-text font-normal">${msg.content}</div>` : ''}
                        ${linkHtml}
                        <div class="flex items-center justify-end gap-1 mt-0.5 text-[10px] text-[#667781] select-none">
                            <span>${msg.time}</span>
                            <button type="button" onclick="openReactionPickerModal(${msg.id})" class="hover:text-slate-900 transition p-0.5 text-[#667781] opacity-70 hover:opacity-100 cursor-pointer" title="Add reaction">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                            </button>
                            ${checkmarks}
                        </div>
                        <div class="hidden group-hover/bubble:flex items-center gap-0.5 sm:gap-1 absolute -top-7 right-0 sm:right-1 bg-white/95 backdrop-blur-xs border border-slate-200 shadow-lg rounded-full px-2 py-0.5 z-30 text-slate-700 select-none animate-in zoom-in-90 duration-100">
                            <button type="button" onclick="reactToMessage(${msg.id}, '👍')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">👍</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '❤️')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">❤️</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '😂')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😂</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '😮')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😮</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '😢')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😢</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '🙏')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">🙏</button>
                            <button type="button" onclick="openReactionPickerModal(${msg.id})" class="w-5 h-5 rounded-full bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-500 flex items-center justify-center transition cursor-pointer text-xs font-black ml-0.5" title="More">+</button>
                            ${unsendBtn}
                        </div>
                    </div>
                    ${reactionsHtml}
                `}
            </div>
        `;
    } else {
        const initial = (msg.user_name || 'U').charAt(0).toUpperCase();
        item.innerHTML = `
            <div class="shrink-0 pt-0.5">
                <div class="w-7 h-7 rounded-full bg-[#008069] text-white font-bold text-xs flex items-center justify-center shadow-2xs">
                    ${initial}
                </div>
            </div>
            <div class="flex flex-col items-start max-w-[88%] sm:max-w-[72%] md:max-w-[65%]">
                ${msg.is_deleted ? `
                    <div class="px-3 py-1.5 rounded-xl rounded-tl-none bg-[#e9edef] text-slate-500 text-xs italic border border-slate-200/60 flex items-center gap-1.5 select-none shadow-2xs">
                        <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>This message was deleted</span>
                    </div>
                ` : `
                    <div class="relative group/bubble w-fit rounded-xl rounded-tl-none px-3 py-1.5 bg-white text-[#111b21] shadow-2xs border border-slate-200/70">
                        <div class="text-[11px] font-bold leading-tight mb-0.5 text-[#008069] flex items-center gap-1">
                            <span>${msg.user_name}</span>
                            ${msg.is_tl ? '<span class="px-1 py-0.2 rounded text-[8px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">TL</span>' : ''}
                        </div>
                        ${mediaHtml}
                        ${msg.content ? `<div class="text-[13px] sm:text-[14px] leading-snug break-words whitespace-pre-line select-text text-[#111b21]">${msg.content}</div>` : ''}
                        ${linkHtml}
                        <div class="flex items-center justify-end gap-1 mt-0.5 text-[10px] text-[#667781] select-none">
                            <span>${msg.time}</span>
                            <button type="button" onclick="openReactionPickerModal(${msg.id})" class="hover:text-slate-900 transition p-0.5 text-[#667781] opacity-70 hover:opacity-100 cursor-pointer" title="Add reaction">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                            </button>
                        </div>
                        <div class="hidden group-hover/bubble:flex items-center gap-0.5 sm:gap-1 absolute -top-7 left-0 sm:left-1 bg-white/95 backdrop-blur-xs border border-slate-200 shadow-lg rounded-full px-2 py-0.5 z-30 text-slate-700 select-none animate-in zoom-in-90 duration-100">
                            <button type="button" onclick="reactToMessage(${msg.id}, '👍')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">👍</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '❤️')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">❤️</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '😂')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😂</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '😮')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😮</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '😢')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">😢</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '🙏')" class="hover:scale-125 active:scale-95 transition-transform text-sm sm:text-base p-0.5 cursor-pointer leading-none">🙏</button>
                            <button type="button" onclick="openReactionPickerModal(${msg.id})" class="w-5 h-5 rounded-full bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-500 flex items-center justify-center transition cursor-pointer text-xs font-black ml-0.5" title="More">+</button>
                            ${deleteBtn}
                        </div>
                    </div>
                    ${reactionsHtml}
                `}
            </div>
        `;
    }

    container.appendChild(item);
    parseTwemoji(item);
    if (window.lucide) lucide.createIcons();
}
</script>
@endpush
