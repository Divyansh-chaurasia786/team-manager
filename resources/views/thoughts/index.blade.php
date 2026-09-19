@extends('layouts.app')
@section('title', $groupTitle ?? 'Team Chat')
@section('page-title', $groupTitle ?? 'Team Chat')

@section('content')

@php
    $isManagement = in_array(auth()->user()->role, ['hr', 'ceo']);
@endphp

<div class="space-y-4">

    <!-- Top Page Header Bar (Cohesive with EcoFone Portal) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold mb-1">
                <a href="{{ auth()->user()->isTL() ? route('tl.dashboard') : (auth()->user()->role === 'ceo' ? route('ceo.dashboard') : (auth()->user()->role === 'hr' ? route('hr.dashboard') : route('member.dashboard'))) }}" class="hover:text-indigo-600 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-700">Team Chat</span>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Team Collaboration & Chat Hub</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold flex items-center gap-1 {{ $groupType === 'team' ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-indigo-50 border border-indigo-200 text-indigo-700' }}">
                    <i data-lucide="{{ $groupType === 'team' ? 'lock' : 'building-2' }}" class="w-3.5 h-3.5"></i>
                    <span>{{ $groupType === 'team' ? 'Private' : 'Company Hub' }}</span>
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Real-time team messaging, project announcements, and media coordination</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>{{ count($teamUsers) }} Members</span>
            </span>
        </div>
    </div>

    <!-- Main Integrated Chat Workspace Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden h-[calc(100vh-14.5rem)] min-h-[580px] flex flex-col md:flex-row relative">

        <!-- 🟢 LEFT SIDEBAR: Channels & Team Members -->
        <div id="chatSidebar" class="hidden md:flex w-full md:w-80 lg:w-88 bg-white border-r border-slate-200 flex-col shrink-0 absolute md:relative inset-0 z-30 md:z-auto">
            
            <!-- Sidebar Top: User Info & Close (Mobile) -->
            <div class="h-16 px-4 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <button type="button" onclick="toggleMobileSidebar(false)" class="md:hidden p-1.5 -ml-1 text-slate-500 hover:text-slate-900 hover:bg-slate-200 rounded-xl transition" title="Back to Chat">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    </button>
                    <div class="relative shrink-0">
                        @if(auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 rounded-full object-cover ring-2 ring-indigo-500/20">
                        @else
                            <div class="w-9 h-9 rounded-full bg-indigo-600 text-white font-black text-xs flex items-center justify-center ring-2 ring-indigo-400/20">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                        @endif
                        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white"></span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-xs font-bold text-slate-900 truncate leading-tight">{{ auth()->user()->name }}</h3>
                        <span class="text-[10px] text-indigo-600 font-semibold">{{ auth()->user()->isTL() ? 'Team Lead' : (auth()->user()->isAdmin() ? strtoupper(auth()->user()->role) : 'Staff') }}</span>
                    </div>
                </div>

                @if(auth()->user()->isTL() && $groupType === 'team')
                    <button type="button" onclick="openManageMembersModal()" class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-bold flex items-center gap-1 transition cursor-pointer" title="Manage Members">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                        <span>Admin</span>
                    </button>
                @endif
            </div>

            <!-- Group Switcher Tabs -->
            <div class="p-2.5 bg-slate-50/50 border-b border-slate-100 grid grid-cols-2 gap-1.5">
                @if(!$isManagement)
                    <a href="{{ route('thoughts.index', ['group' => 'team']) }}" 
                       class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-bold transition {{ $groupType === 'team' ? 'bg-white text-indigo-700 shadow-2xs border border-slate-200' : 'text-slate-600 hover:bg-white/60' }}">
                        <i data-lucide="lock" class="w-3.5 h-3.5 {{ $groupType === 'team' ? 'text-indigo-600' : 'text-slate-400' }}"></i>
                        <span class="truncate">Private</span>
                    </a>
                @else
                    <div class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-semibold text-slate-400 bg-slate-200/50 cursor-not-allowed" title="Private team chat">
                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                        <span class="truncate">Private</span>
                    </div>
                @endif

                <a href="{{ route('thoughts.index', ['group' => 'company']) }}" 
                   class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-bold transition {{ $groupType === 'company' ? 'bg-white text-indigo-700 shadow-2xs border border-slate-200' : 'text-slate-600 hover:bg-white/60' }}">
                    <i data-lucide="building-2" class="w-3.5 h-3.5 {{ $groupType === 'company' ? 'text-indigo-600' : 'text-slate-400' }}"></i>
                    <span class="truncate">Company Hub</span>
                </a>
            </div>

            <!-- Member Search -->
            <div class="p-2.5 bg-white border-b border-slate-100">
                <div class="relative flex items-center">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3"></i>
                    <input 
                        type="text" 
                        id="searchChatInput" 
                        placeholder="Search messages..." 
                        class="w-full pl-8 pr-3 py-1.5 bg-slate-50 focus:bg-white text-xs rounded-xl focus:outline-none focus:ring-1 focus:ring-indigo-500 transition border border-slate-200 placeholder:text-slate-400"
                    >
                </div>
            </div>

            <!-- Team Members Roster -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                <div class="px-3.5 py-2 bg-slate-50/70 text-[10px] font-black uppercase tracking-wider text-slate-400 flex items-center justify-between">
                    <span>Active Channel Members ({{ count($teamUsers) }})</span>
                    @if(auth()->user()->isTL() && $groupType === 'team')
                        <button type="button" onclick="openManageMembersModal()" class="text-indigo-600 hover:text-indigo-800 font-bold hover:underline cursor-pointer">
                            + Edit
                        </button>
                    @endif
                </div>

                @foreach($teamUsers as $member)
                    <div class="p-2.5 px-3.5 hover:bg-slate-50 transition flex items-center justify-between gap-2 group cursor-pointer" onclick="mentionMember('{{ addslashes($member->name) }}')">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="relative shrink-0">
                                @if($member->avatar_url)
                                    <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center ring-1 ring-slate-200">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="absolute bottom-0 right-0 w-2 h-2 bg-emerald-500 rounded-full ring-1 ring-white"></span>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-800 truncate flex items-center gap-1">
                                    <span>{{ $member->name }}</span>
                                    @if($member->isTL())
                                        <span class="px-1 py-0.2 rounded text-[8px] font-extrabold bg-indigo-100 text-indigo-700">TL</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-400 truncate">
                                    {{ $member->designation ?: ($member->isTL() ? 'Team Lead' : 'Staff Member') }}
                                </div>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-indigo-600 opacity-0 group-hover:opacity-100 transition">@mention</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 💬 RIGHT MAIN CHAT AREA (Bespoke EcoFone Portal Style) -->
        <div class="flex-1 flex flex-col h-full bg-slate-50/60 relative min-w-0">

            <!-- Chat Channel Header Bar -->
            <div class="h-16 px-4 bg-white border-b border-slate-200/80 flex items-center justify-between shrink-0 shadow-2xs z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" onclick="toggleMobileSidebar(true)" class="md:hidden p-1.5 -ml-1 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition shrink-0" title="Channels & Members">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </button>
                    <div class="w-9 h-9 rounded-xl {{ $groupType === 'team' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-violet-50 text-violet-600 border border-violet-100' }} flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                        <i data-lucide="{{ $groupType === 'team' ? 'lock' : 'building-2' }}" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-black text-slate-900 truncate leading-tight">
                                {{ $groupType === 'team' ? (auth()->user()->isTL() ? auth()->user()->name . "'s Team" : ($teamUsers->firstWhere('role', 'tl')?->name ? $teamUsers->firstWhere('role', 'tl')->name . "'s Team" : 'Private Team')) : 'EcoFone Company Hub' }}
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase {{ $groupType === 'team' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                                {{ $groupType === 'team' ? 'Private' : 'Company' }}
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5 truncate">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>{{ count($teamUsers) }} participants</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Live Sync</span>
                    </span>
                </div>
            </div>

            <!-- Scrollable Messages Feed -->
            <div id="chatMessagesScrollArea" class="flex-1 overflow-y-auto p-3 sm:p-5 space-y-3 scroll-smooth">
                
                <!-- Messages List -->
                <div id="chatMessagesList" class="space-y-3">
                    @php 
                        $lastDate = null; 
                        $userColors = ['#4f46e5', '#0284c7', '#059669', '#d97706', '#7c3aed', '#db2777'];
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
                            <div class="flex justify-center my-3">
                                <span class="px-3 py-0.5 rounded-full bg-white border border-slate-200/80 shadow-2xs text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                    {{ $msgDate }}
                                </span>
                            </div>
                            @php $lastDate = $msgDate; @endphp
                        @endif

                        @if($isMe)
                            <!-- Outgoing Message (Self) -->
                            <div class="flex justify-end message-item group relative" data-message-id="{{ $thought->id }}" data-text="{{ strtolower($thought->content ?? '') }}">
                                <div class="flex flex-col items-end max-w-[85%] sm:max-w-[70%]">
                                    @if($thought->is_deleted)
                                        <div class="px-3.5 py-1.5 rounded-2xl rounded-tr-xs bg-slate-100 text-slate-500 text-xs italic border border-slate-200/80 flex items-center gap-1.5">
                                            <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span>You unsent this message</span>
                                        </div>
                                    @else
                                        <div class="relative group/bubble w-fit rounded-2xl rounded-tr-xs px-3.5 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-xs">
                                            @if($thought->media_path || $thought->drive_url)
                                                @php $mediaSrc = $thought->media_path ? asset($thought->media_path) : $thought->drive_url; @endphp
                                                <div class="mb-1.5 rounded-xl overflow-hidden bg-black/10">
                                                    @if($thought->media_type === 'image')
                                                        <img src="{{ $mediaSrc }}" alt="Media" onclick="openImageLightbox('{{ $mediaSrc }}')" class="max-h-60 rounded-xl object-cover cursor-pointer hover:opacity-95 transition">
                                                    @elseif($thought->media_type === 'video')
                                                        <video controls class="max-h-60 rounded-xl bg-black">
                                                            <source src="{{ $mediaSrc }}">
                                                        </video>
                                                    @endif
                                                </div>
                                            @endif

                                            @if(!empty($thought->content))
                                                <div class="text-[13px] sm:text-sm leading-snug break-words whitespace-pre-line select-text">{{ trim($thought->content) }}</div>
                                            @endif

                                            @if(!empty($thought->link_url))
                                                <div class="mt-1 p-1.5 rounded-xl bg-indigo-800/60">
                                                    <a href="{{ $thought->link_url }}" target="_blank" class="text-xs text-indigo-100 hover:text-white font-semibold flex items-center gap-1 truncate">
                                                        <i data-lucide="link" class="w-3 h-3 shrink-0"></i>
                                                        <span class="truncate">{{ $thought->link_url }}</span>
                                                    </a>
                                                </div>
                                            @endif

                                            <!-- Timestamp & Read Status Dock -->
                                            <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-indigo-200 select-none">
                                                <span>{{ $thought->created_at->format('h:i A') }}</span>
                                                @if(count($seenBy) > 0)
                                                    <button type="button" onclick="openMessageInfoModal({{ $thought->id }})" class="hover:opacity-80 cursor-pointer text-sky-300" title="Seen by {{ count($seenBy) }} members">
                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 15" fill="none"><path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/></svg>
                                                    </button>
                                                @else
                                                    <svg class="w-3.5 h-3.5 text-indigo-300" viewBox="0 0 16 15" fill="none"><path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/></svg>
                                                @endif
                                            </div>

                                            <!-- Hover Actions Bar -->
                                            <div class="hidden group-hover/bubble:flex items-center gap-1 absolute -top-3 left-0 bg-white border border-slate-200 shadow-md rounded-full px-1.5 py-0.5 z-10 text-slate-700">
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '👍')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">👍</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '❤️')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">❤️</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😂')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">😂</button>
                                                @if($thought->isUnsendableBy(auth()->user()))
                                                    <button type="button" onclick="unsendMessage({{ $thought->id }})" class="text-rose-500 hover:text-rose-700 font-bold text-[10px] px-1 cursor-pointer" title="Unsend">✕</button>
                                                @endif
                                            </div>
                                        </div>

                                        @if(count($reactions) > 0)
                                            <div class="flex flex-wrap gap-1 mt-1 justify-end">
                                                @foreach($groupedReactions as $emoji => $names)
                                                    <button type="button" onclick="reactToMessage({{ $thought->id }}, '{{ $emoji }}')" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[11px] cursor-pointer" title="{{ implode(', ', $names) }}">
                                                        <span>{{ $emoji }}</span>
                                                        <span class="font-bold text-[10px]">{{ count($names) }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @else
                            <!-- Incoming Teammate Message (Left aligned with avatar) -->
                            <div class="flex items-start gap-2 message-item group relative" data-message-id="{{ $thought->id }}" data-text="{{ strtolower($thought->content ?? '') }}">
                                <div class="shrink-0 pt-0.5">
                                    @if($thought->user?->avatar_url)
                                        <img src="{{ $thought->user->avatar_url }}" alt="{{ $thought->user->name }}" class="w-7 h-7 rounded-full object-cover ring-1 ring-slate-200">
                                    @else
                                        <div class="w-7 h-7 rounded-full text-white font-bold text-xs flex items-center justify-center shadow-2xs" style="background-color: {{ $senderColor }};">
                                            {{ strtoupper(substr($thought->user?->name ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-col items-start max-w-[85%] sm:max-w-[70%]">
                                    @if($thought->is_deleted)
                                        <div class="px-3.5 py-1.5 rounded-2xl rounded-tl-xs bg-slate-100 text-slate-500 text-xs italic border border-slate-200/80 flex items-center gap-1.5">
                                            <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span>This message was deleted</span>
                                        </div>
                                    @else
                                        <div class="relative group/bubble w-fit rounded-2xl rounded-tl-xs px-3.5 py-2 bg-white text-slate-800 border border-slate-200/90 shadow-2xs">
                                            <!-- Sender Name in Bubble -->
                                            <div class="text-[11px] font-bold leading-tight mb-1 flex items-center gap-1" style="color: {{ $senderColor }};">
                                                <span>{{ $thought->user?->name ?? 'Member' }}</span>
                                                @if($thought->user?->isTL())
                                                    <span class="px-1 py-0.2 rounded text-[8px] font-extrabold bg-indigo-50 border border-indigo-100 text-indigo-600">TL</span>
                                                @endif
                                            </div>

                                            @if($thought->media_path || $thought->drive_url)
                                                @php $mediaSrc = $thought->media_path ? asset($thought->media_path) : $thought->drive_url; @endphp
                                                <div class="mb-1.5 rounded-xl overflow-hidden bg-black/5">
                                                    @if($thought->media_type === 'image')
                                                        <img src="{{ $mediaSrc }}" alt="Media" onclick="openImageLightbox('{{ $mediaSrc }}')" class="max-h-60 rounded-xl object-cover cursor-pointer hover:opacity-95 transition">
                                                    @elseif($thought->media_type === 'video')
                                                        <video controls class="max-h-60 rounded-xl bg-black">
                                                            <source src="{{ $mediaSrc }}">
                                                        </video>
                                                    @endif
                                                </div>
                                            @endif

                                            @if(!empty($thought->content))
                                                <div class="text-[13px] sm:text-sm leading-snug break-words whitespace-pre-line select-text text-slate-800">{{ trim($thought->content) }}</div>
                                            @endif

                                            @if(!empty($thought->link_url))
                                                <div class="mt-1 p-1.5 rounded-xl bg-slate-50 border border-slate-100">
                                                    <a href="{{ $thought->link_url }}" target="_blank" class="text-xs text-indigo-600 hover:underline font-semibold flex items-center gap-1 truncate">
                                                        <i data-lucide="link" class="w-3 h-3 shrink-0"></i>
                                                        <span class="truncate">{{ $thought->link_url }}</span>
                                                    </a>
                                                </div>
                                            @endif

                                            <!-- Timestamp Dock -->
                                            <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-slate-400 select-none">
                                                <span>{{ $thought->created_at->format('h:i A') }}</span>
                                            </div>

                                            <!-- Hover Actions Bar -->
                                            <div class="hidden group-hover/bubble:flex items-center gap-1 absolute -top-3 right-0 bg-white border border-slate-200 shadow-md rounded-full px-1.5 py-0.5 z-10 text-slate-700">
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '👍')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">👍</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '❤️')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">❤️</button>
                                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😂')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">😂</button>
                                                @if(auth()->user()->isTL())
                                                    <button type="button" onclick="deleteMessage({{ $thought->id }})" class="text-rose-500 hover:text-rose-700 font-bold text-[10px] px-1 cursor-pointer" title="Delete">✕</button>
                                                @endif
                                            </div>
                                        </div>

                                        @if(count($reactions) > 0)
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach($groupedReactions as $emoji => $names)
                                                    <button type="button" onclick="reactToMessage({{ $thought->id }}, '{{ $emoji }}')" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-[11px] cursor-pointer" title="{{ implode(', ', $names) }}">
                                                        <span>{{ $emoji }}</span>
                                                        <span class="font-bold text-[10px]">{{ count($names) }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

            </div>

            <!-- Attachment Preview Bar -->
            <div id="attachmentPreviewTray" class="hidden px-4 py-2 bg-white border-t border-slate-200 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <i data-lucide="paperclip" class="w-4 h-4 text-indigo-600 shrink-0"></i>
                    <span id="attachedFileName" class="text-xs font-bold text-slate-800 truncate"></span>
                </div>
                <button type="button" onclick="clearSelectedAttachment()" class="text-slate-400 hover:text-slate-600 text-xs p-1">✕</button>
            </div>

            <!-- Link Attachment Input -->
            <div id="linkInputTray" class="hidden px-4 py-2 bg-white border-t border-slate-200">
                <div class="relative flex items-center">
                    <i data-lucide="link" class="w-3.5 h-3.5 text-slate-400 absolute left-3"></i>
                    <input type="url" id="linkUrlInput" placeholder="Paste URL link (https://...)" class="w-full pl-8 pr-7 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <button type="button" onclick="toggleLinkInput(false)" class="absolute right-2.5 text-slate-400 hover:text-slate-600 text-xs">✕</button>
                </div>
            </div>

            <!-- ⌨️ Chat Input Bar (Clean EcoFone Portal Theme) -->
            <div class="p-3 bg-white border-t border-slate-200 shrink-0">
                <form id="chatMessageForm" onsubmit="sendChatMessage(event)" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="group_type" value="{{ $groupType }}">

                    <!-- Emoji Picker Trigger -->
                    <div class="relative">
                        <button type="button" onclick="toggleEmojiPicker()" class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-xl transition cursor-pointer" title="Insert Emojis">
                            <i data-lucide="smile" class="w-5 h-5"></i>
                        </button>

                        <!-- Real 3D Glossy Emoji Picker Drawer -->
                        <div id="emojiPickerTray" class="hidden absolute bottom-12 left-0 w-72 sm:w-84 bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col z-40 overflow-hidden animate-in zoom-in-95 duration-100">
                            <!-- Emoji Header with Category Tabs -->
                            <div class="p-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-1">
                                <div class="flex items-center gap-1 text-xs">
                                    <button type="button" onclick="switchEmojiCategory('indian')" class="px-2 py-1 hover:bg-slate-200 rounded-lg font-bold text-xs transition">🇮🇳 Desi</button>
                                    <button type="button" onclick="switchEmojiCategory('smileys')" class="px-2 py-1 hover:bg-slate-200 rounded-lg font-bold text-xs transition">😀 Faces</button>
                                    <button type="button" onclick="switchEmojiCategory('gestures')" class="px-2 py-1 hover:bg-slate-200 rounded-lg font-bold text-xs transition">👍 Hands</button>
                                    <button type="button" onclick="switchEmojiCategory('hearts')" class="px-2 py-1 hover:bg-slate-200 rounded-lg font-bold text-xs transition">❤️ Hearts</button>
                                    <button type="button" onclick="switchEmojiCategory('celebration')" class="px-2 py-1 hover:bg-slate-200 rounded-lg font-bold text-xs transition">🚀 Work</button>
                                </div>
                                <button type="button" onclick="toggleEmojiPicker(false)" class="text-slate-400 hover:text-slate-600 p-1 text-xs font-bold">✕</button>
                            </div>

                            <!-- Emoji Grid -->
                            <div id="emojiGridContainer" class="p-2.5 overflow-y-auto max-h-56 grid grid-cols-7 gap-1.5 text-xl select-none"></div>
                        </div>
                    </div>

                    <!-- Media Attachment Trigger -->
                    <label class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-xl transition cursor-pointer" title="Attach Media">
                        <i data-lucide="paperclip" class="w-5 h-5"></i>
                        <input type="file" id="chatMediaInput" name="media" accept="image/*,video/*" class="hidden" onchange="handleChatFileSelect(this)">
                    </label>

                    <!-- Link Trigger -->
                    <button type="button" onclick="toggleLinkInput()" class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-xl transition cursor-pointer" title="Attach Link">
                        <i data-lucide="link" class="w-5 h-5"></i>
                    </button>

                    <!-- Text Input Field -->
                    <div class="flex-1 relative">
                        <input 
                            type="text" 
                            id="chatMessageInput" 
                            name="content" 
                            autocomplete="off" 
                            placeholder="Type a message..." 
                            class="w-full px-4 py-2.5 bg-slate-50 hover:bg-slate-100/60 focus:bg-white text-slate-800 placeholder:text-slate-400 text-xs sm:text-sm rounded-xl focus:outline-none focus:ring-1 focus:ring-indigo-500 border border-slate-200 transition"
                        >
                    </div>

                    <!-- EcoFone Indigo Send Button -->
                    <button 
                        type="submit" 
                        id="sendBtn" 
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm flex items-center justify-center gap-1.5 shadow-md shadow-indigo-600/20 transition shrink-0 cursor-pointer active:scale-95"
                    >
                        <span>Send</span>
                        <i data-lucide="send" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>

<!-- Message Info Modal -->
<div id="messageInfoModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-xs w-full p-4 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <h3 class="text-xs font-bold text-slate-800">Message Info</h3>
            <button type="button" onclick="closeMessageInfoModal()" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
        </div>
        <div class="py-2.5">
            <div class="text-[10px] text-slate-400 font-bold uppercase mb-1.5">Read by</div>
            <div id="seenByList" class="space-y-1.5 max-h-48 overflow-y-auto"></div>
        </div>
        <button type="button" onclick="closeMessageInfoModal()" class="w-full py-1.5 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">Close</button>
    </div>
</div>

<!-- TL Manage Group Members Modal -->
@if(auth()->user()->isTL())
<div id="manageMembersModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-4 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <div>
                <h3 class="text-xs font-bold text-slate-800">Manage Team Members</h3>
                <p class="text-[10px] text-slate-400">Add or remove from private team channel</p>
            </div>
            <button type="button" onclick="closeManageMembersModal()" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
        </div>

        <div class="py-2.5 border-b border-slate-100">
            <div class="flex items-center gap-1.5">
                <select id="newMemberSelect" class="flex-1 px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl">
                    <option value="">Select employee...</option>
                    @foreach($candidateUsers as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                    @endforeach
                </select>
                <button type="button" onclick="addMemberToGroup()" class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-bold cursor-pointer">Add</button>
            </div>
        </div>

        <div class="py-2">
            <div class="text-[10px] text-slate-400 font-bold uppercase mb-1.5">Current Members ({{ count($teamUsers) }})</div>
            <div class="space-y-1.5 max-h-48 overflow-y-auto">
                @foreach($teamUsers as $u)
                    <div class="flex items-center justify-between p-1.5 rounded-xl bg-slate-50 text-xs">
                        <span class="font-semibold text-slate-800 truncate">{{ $u->name }}</span>
                        @if($u->id !== auth()->id())
                            <button type="button" onclick="removeMemberFromGroup({{ $u->id }}, '{{ addslashes($u->name) }}')" class="px-2 py-0.5 rounded bg-rose-50 text-rose-600 text-[10px] font-bold cursor-pointer">Remove</button>
                        @else
                            <span class="text-[10px] font-bold text-indigo-600">Admin</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <button type="button" onclick="closeManageMembersModal()" class="w-full py-1.5 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl mt-1 cursor-pointer">Done</button>
    </div>
</div>
@endif

<!-- Image Lightbox Modal -->
<div id="imageLightbox" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 cursor-pointer" onclick="closeImageLightbox()">
    <div class="relative max-w-4xl max-h-[90vh] flex items-center justify-center" onclick="event.stopPropagation()">
        <img id="lightboxImage" src="" alt="Enlarged Photo" class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain">
        <button type="button" onclick="closeImageLightbox()" class="absolute -top-10 right-0 text-white font-bold text-lg hover:text-slate-300">✕ Close</button>
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

const emojiCategories = {
    indian: ['🇮🇳', '🙏', '🪔', '🕉️', '🪷', '🏏', '🍛', '☕', '🛺', '🐅', '🐘', '💰', '🎇', '🎆', '🎉', '🤝', '👏', '💐', '🌺', '🥭', '🦚', '✨'],
    smileys: ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '🥲', '🥹', '☺️', '😊', '😇', '🙂', '😉', '😌', '😍', '🥰', '😘', '😋', '😎', '🤩', '🥳', '🥺', '😢', '😭', '🤯', '😱', '🤗', '🤔', '🤫', '😴'],
    gestures: ['👍', '👎', '👊', '✊', '🤛', '🤜', '🤞', '✌️', '🫰', '🤟', '🤘', '👌', '🤌', '🤏', '👈', '👉', '👆', '👇', '✋', '👋', '👏', '🙌', '🫶', '🙏', '💪', '🤝'],
    hearts: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❤️‍🔥', '💕', '💞', '💓', '💗', '💖', '✨', '🌟', '💥', '🔥', '💯', '✅', '❌', '⚠️', '🎯'],
    celebration: ['🚀', '🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🥈', '🥉', '💡', '📢', '🔔', '📌', '📎', '🔒', '💼', '📊', '💻', '📱', '☕', '🎂']
};

document.addEventListener("DOMContentLoaded", function() {
    scrollToBottom();
    startLivePolling();
    switchEmojiCategory('indian');
    parseTwemoji();

    // Attach event delegation for Emoji Picker buttons (Prevents broken inline quote issues)
    const emojiContainer = document.getElementById('emojiGridContainer');
    if (emojiContainer) {
        emojiContainer.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-emoji]');
            if (!btn) return;
            const emojiChar = btn.getAttribute('data-emoji');
            insertEmoji(emojiChar);
        });
    }

    const searchInput = document.getElementById('searchChatInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            const items = document.querySelectorAll('.message-item');
            items.forEach(item => {
                const text = item.getAttribute('data-text') || '';
                if (!query || text.includes(query)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });
    }
});

function parseTwemoji(element = null) {
    if (window.twemoji) {
        twemoji.parse(element || document.getElementById('chatMessagesList') || document.body, {
            folder: 'svg',
            ext: '.svg'
        });
    }
}

function scrollToBottom() {
    const area = document.getElementById('chatMessagesScrollArea');
    if (area) {
        area.scrollTop = area.scrollHeight;
    }
}

function toggleMobileSidebar(forceState = null) {
    const sidebar = document.getElementById('chatSidebar');
    if (!sidebar) return;
    if (forceState !== null) {
        if (forceState) sidebar.classList.remove('hidden');
        else sidebar.classList.add('hidden');
    } else {
        sidebar.classList.toggle('hidden');
    }
    if (window.lucide) lucide.createIcons();
}

function mentionMember(name) {
    const input = document.getElementById('chatMessageInput');
    if (input) {
        input.value = `@${name} ` + input.value;
        input.focus();
    }
    if (window.innerWidth < 768) {
        toggleMobileSidebar(false);
    }
}

function toggleEmojiPicker(force = null) {
    const tray = document.getElementById('emojiPickerTray');
    if (!tray) return;
    if (force !== null) {
        if (force) tray.classList.remove('hidden');
        else tray.classList.add('hidden');
    } else {
        tray.classList.toggle('hidden');
    }
}

function switchEmojiCategory(catKey) {
    const container = document.getElementById('emojiGridContainer');
    if (!container) return;
    const list = emojiCategories[catKey] || emojiCategories.indian;
    container.innerHTML = list.map(e => `
        <button type="button" data-emoji="${e}" class="p-1 hover:bg-slate-100 rounded-lg transition hover:scale-125 cursor-pointer flex items-center justify-center">
            ${e}
        </button>
    `).join('');
    parseTwemoji(container);
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
        document.getElementById('attachedFileName').textContent = file.name;
        document.getElementById('attachmentPreviewTray').classList.remove('hidden');
    }
}

function clearSelectedAttachment() {
    const input = document.getElementById('chatMediaInput');
    if (input) input.value = '';
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

// 🟢 Send Message
async function sendChatMessage(event) {
    event.preventDefault();
    const input = document.getElementById('chatMessageInput');
    const content = input.value.trim();
    const mediaInput = document.getElementById('chatMediaInput');
    const linkInput = document.getElementById('linkUrlInput');
    const linkUrl = linkInput ? linkInput.value.trim() : '';
    const hasMedia = mediaInput.files && mediaInput.files[0];

    if (!content && !hasMedia && !linkUrl) return;

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
        }
    } catch (err) {
        console.error('Failed to send message:', err);
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
        const metaRow = innerCol.querySelector('.flex.items-center');
        const metaHtml = metaRow ? metaRow.outerHTML : '';
        innerCol.innerHTML = `
            ${metaHtml}
            <div class="px-3.5 py-1.5 rounded-2xl ${isMe ? 'rounded-tr-xs' : 'rounded-tl-xs'} bg-slate-100 text-slate-500 text-xs italic border border-slate-200/80 flex items-center gap-1.5">
                <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>${isMe ? 'You unsent this message' : 'This message was deleted'}</span>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    }
}

// 🟢 React to Message
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
        if (res.ok && data.success) {
            pollNewMessages();
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

// 🟢 Real-time Automatic Live Synchronization
function startLivePolling() {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(() => {
        pollNewMessages();
    }, 2500);
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
    try {
        const res = await fetch(`{{ route('thoughts.messages') }}?group=${activeGroupType}&after_id=${latestMessageId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (res.ok && data.success && data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => {
                if (!document.querySelector(`[data-message-id="${msg.id}"]`)) {
                    renderMessageBubble(msg);
                }
            });

            latestMessageId = data.latest_id;
            scrollToBottom();
        }
    } catch (err) {
        console.warn('Live sync notice:', err);
    }
}

// 🟢 Render Message HTML (Compact, Snug WhatsApp/Instagram-style Bubble)
function renderMessageBubble(msg) {
    const container = document.getElementById('chatMessagesList');
    if (!container) return;

    const isMe = msg.user_id === currentUserId;
    const item = document.createElement('div');
    item.className = `${isMe ? 'flex justify-end' : 'flex items-start gap-2'} message-item animate-in fade-in duration-100 group relative`;
    item.setAttribute('data-message-id', msg.id);
    item.setAttribute('data-text', (msg.content || '').toLowerCase());

    let mediaHtml = '';
    if (msg.media_url && !msg.is_deleted) {
        if (msg.media_type === 'image') {
            mediaHtml = `
                <div class="mb-1.5 rounded-xl overflow-hidden bg-black/5">
                    <img src="${msg.media_url}" onclick="openImageLightbox('${msg.media_url}')" class="max-h-60 rounded-xl object-cover cursor-pointer hover:opacity-95 transition">
                </div>`;
        } else if (msg.media_type === 'video') {
            mediaHtml = `
                <div class="mb-1.5 rounded-xl overflow-hidden bg-black">
                    <video controls class="max-h-60 rounded-xl">
                        <source src="${msg.media_url}">
                    </video>
                </div>`;
        }
    }

    let linkHtml = '';
    if (msg.link_url && !msg.is_deleted) {
        linkHtml = `
            <div class="mt-1 p-1.5 rounded-xl ${isMe ? 'bg-indigo-800/60' : 'bg-slate-50 border border-slate-100'}">
                <a href="${msg.link_url}" target="_blank" class="text-xs ${isMe ? 'text-indigo-100 hover:text-white' : 'text-indigo-600 hover:underline'} font-semibold flex items-center gap-1 truncate">
                    <span class="truncate">${msg.link_url}</span>
                </a>
            </div>`;
    }

    let checkmarks = '';
    if (isMe && !msg.is_deleted) {
        if (msg.is_seen) {
            checkmarks = `
                <button type="button" onclick="openMessageInfoModal(${msg.id})" class="hover:opacity-80 cursor-pointer text-sky-300 ml-0.5" title="Seen by ${msg.seen_count} members">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 16 15" fill="none"><path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/></svg>
                </button>`;
        } else {
            checkmarks = `
                <svg class="w-3.5 h-3.5 text-indigo-300 ml-0.5" viewBox="0 0 16 15" fill="none"><path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/></svg>`;
        }
    }

    const unsendBtn = (msg.can_unsend && !msg.is_deleted) ? `
        <button type="button" onclick="unsendMessage(${msg.id})" class="text-rose-500 hover:text-rose-700 font-bold text-[10px] px-1 cursor-pointer" title="Unsend">✕</button>` : '';

    const deleteBtn = (isUserTL && !isMe && !msg.is_deleted) ? `
        <button type="button" onclick="deleteMessage(${msg.id})" class="text-rose-500 hover:text-rose-700 font-bold text-[10px] px-1 cursor-pointer" title="Delete">✕</button>` : '';

    if (isMe) {
        item.innerHTML = `
            <div class="flex flex-col items-end max-w-[85%] sm:max-w-[70%]">
                ${msg.is_deleted ? `
                    <div class="px-3.5 py-1.5 rounded-2xl rounded-tr-xs bg-slate-100 text-slate-500 text-xs italic border border-slate-200/80 flex items-center gap-1.5">
                        <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>You unsent this message</span>
                    </div>
                ` : `
                    <div class="relative group/bubble w-fit rounded-2xl rounded-tr-xs px-3.5 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-xs">
                        ${mediaHtml}
                        ${msg.content ? `<div class="text-[13px] sm:text-sm leading-snug break-words whitespace-pre-line select-text">${msg.content}</div>` : ''}
                        ${linkHtml}
                        <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-indigo-200 select-none">
                            <span>${msg.time}</span>
                            ${checkmarks}
                        </div>
                        <div class="hidden group-hover/bubble:flex items-center gap-1 absolute -top-3 left-0 bg-white border border-slate-200 shadow-md rounded-full px-1.5 py-0.5 z-10 text-slate-700">
                            <button type="button" onclick="reactToMessage(${msg.id}, '👍')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">👍</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '❤️')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">❤️</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '😂')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">😂</button>
                            ${unsendBtn}
                        </div>
                    </div>
                `}
            </div>
        `;
    } else {
        const initial = (msg.user_name || 'U').charAt(0).toUpperCase();
        item.innerHTML = `
            <div class="shrink-0 pt-0.5">
                <div class="w-7 h-7 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shadow-2xs">
                    ${initial}
                </div>
            </div>
            <div class="flex flex-col items-start max-w-[85%] sm:max-w-[70%]">
                ${msg.is_deleted ? `
                    <div class="px-3.5 py-1.5 rounded-2xl rounded-tl-xs bg-slate-100 text-slate-500 text-xs italic border border-slate-200/80 flex items-center gap-1.5">
                        <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>This message was deleted</span>
                    </div>
                ` : `
                    <div class="relative group/bubble w-fit rounded-2xl rounded-tl-xs px-3.5 py-2 bg-white text-slate-800 border border-slate-200/90 shadow-2xs">
                        <div class="text-[11px] font-bold leading-tight mb-1 text-indigo-600 flex items-center gap-1">
                            <span>${msg.user_name}</span>
                            ${msg.is_tl ? '<span class="px-1 py-0.2 rounded text-[8px] font-extrabold bg-indigo-50 border border-indigo-100 text-indigo-600">TL</span>' : ''}
                        </div>
                        ${mediaHtml}
                        ${msg.content ? `<div class="text-[13px] sm:text-sm leading-snug break-words whitespace-pre-line select-text text-slate-800">${msg.content}</div>` : ''}
                        ${linkHtml}
                        <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-slate-400 select-none">
                            <span>${msg.time}</span>
                        </div>
                        <div class="hidden group-hover/bubble:flex items-center gap-1 absolute -top-3 right-0 bg-white border border-slate-200 shadow-md rounded-full px-1.5 py-0.5 z-10 text-slate-700">
                            <button type="button" onclick="reactToMessage(${msg.id}, '👍')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">👍</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '❤️')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">❤️</button>
                            <button type="button" onclick="reactToMessage(${msg.id}, '😂')" class="hover:scale-125 transition text-xs p-0.5 cursor-pointer">😂</button>
                            ${deleteBtn}
                        </div>
                    </div>
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
