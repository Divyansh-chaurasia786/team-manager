@extends('layouts.app')
@section('title', $groupTitle ?? 'Team Chat')
@section('page-title', $groupTitle ?? 'Team Chat')

@section('content')
<div class="h-[calc(100vh-12rem)] sm:h-[calc(100vh-7.5rem)] mb-14 lg:mb-0 flex flex-col md:flex-row bg-[#efeae2] rounded-2xl md:rounded-3xl overflow-hidden border border-slate-300/80 shadow-2xl relative" style="background-image: radial-gradient(#cbd5e1 0.75px, transparent 0.75px); background-size: 16px 16px;">

    <!-- 🟢 LEFT SIDEBAR: CHANNELS & MEMBERS -->
    <div id="chatSidebar" class="hidden md:flex w-full md:w-80 lg:w-96 bg-white border-r border-slate-200 flex-col shrink-0 absolute md:relative inset-0 z-30 md:z-auto">
        
        <!-- Sidebar Top Header -->
        <div class="h-16 px-4 bg-[#f0f2f5] border-b border-slate-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <button type="button" onclick="toggleMobileSidebar(false)" class="md:hidden p-1.5 -ml-1 text-slate-600 hover:bg-slate-200 rounded-full transition" title="Back to Chat">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </button>
                <div class="relative">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full object-cover shadow-xs ring-2 ring-emerald-500/30">
                    @else
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-600 text-white font-black text-sm flex items-center justify-center shadow-xs ring-2 ring-emerald-500/30">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white"></span>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs font-bold text-slate-900 truncate leading-tight">{{ auth()->user()->name }}</h3>
                    <span class="text-[10px] font-semibold text-emerald-600 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ auth()->user()->designation ?: (auth()->user()->isTL() ? 'Team Lead' : (auth()->user()->isAdmin() ? strtoupper(auth()->user()->role) : 'Staff')) }}</span>
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-1">
                @if(auth()->user()->isTL() && $groupType === 'team')
                    <button type="button" onclick="openManageMembersModal()" class="px-2.5 py-1 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-[11px] font-extrabold flex items-center gap-1 transition" title="Manage Group Members">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                        <span>Admin</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- 📂 GROUP SWITCHER TABS (Team vs Company) -->
        <div class="p-2 bg-slate-100/80 border-b border-slate-200 grid grid-cols-2 gap-1.5">
            @if(!$isManagement)
                <!-- Team Group Tab (Hidden for HR/CEO) -->
                <a href="{{ route('thoughts.index', ['group' => 'team']) }}" 
                   class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-bold transition {{ $groupType === 'team' ? 'bg-white text-emerald-700 shadow-xs border border-slate-200' : 'text-slate-600 hover:bg-white/60' }}">
                    <i data-lucide="lock" class="w-3.5 h-3.5 {{ $groupType === 'team' ? 'text-emerald-600' : 'text-slate-400' }}"></i>
                    <span class="truncate">Team Chat</span>
                </a>
            @else
                <div class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-semibold text-slate-400 bg-slate-200/50 cursor-not-allowed" title="HR & CEO cannot access private team chats">
                    <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                    <span class="truncate">Private Teams 🔒</span>
                </div>
            @endif

            <!-- Company Hub Tab (Everyone) -->
            <a href="{{ route('thoughts.index', ['group' => 'company']) }}" 
               class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-bold transition {{ $groupType === 'company' ? 'bg-white text-indigo-700 shadow-xs border border-slate-200' : 'text-slate-600 hover:bg-white/60' }}">
                <i data-lucide="building" class="w-3.5 h-3.5 {{ $groupType === 'company' ? 'text-indigo-600' : 'text-slate-400' }}"></i>
                <span class="truncate">Company Hub</span>
            </a>
        </div>

        <!-- Search Bar -->
        <div class="p-2.5 bg-white border-b border-slate-100">
            <div class="relative flex items-center">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3"></i>
                <input 
                    type="text" 
                    id="searchChatInput" 
                    placeholder="Search in this chat..." 
                    class="w-full pl-9 pr-3.5 py-1.5 bg-[#f0f2f5] hover:bg-slate-100 focus:bg-white text-xs rounded-xl focus:outline-none focus:ring-1 focus:ring-emerald-500 transition placeholder:text-slate-400 border border-transparent focus:border-emerald-400">
            </div>
        </div>

        <!-- Group List & Members Section -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100/80">
            
            <!-- Current Active Channel Card -->
            <div class="p-3.5 {{ $groupType === 'team' ? 'bg-emerald-50/50 border-l-4 border-emerald-500' : 'bg-indigo-50/50 border-l-4 border-indigo-500' }} transition flex items-center gap-3">
                <div class="relative shrink-0">
                    <div class="w-11 h-11 rounded-2xl {{ $groupType === 'team' ? 'bg-gradient-to-tr from-emerald-600 to-teal-500' : 'bg-gradient-to-tr from-indigo-600 to-violet-600' }} text-white flex items-center justify-center shadow-xs">
                        <i data-lucide="{{ $groupType === 'team' ? 'users' : 'building-2' }}" class="w-5 h-5"></i>
                    </div>
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-slate-900 truncate">{{ $groupTitle }}</h4>
                        <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded {{ $groupType === 'team' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                            {{ $groupType === 'team' ? 'Team 🔒' : 'Company' }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 truncate mt-0.5" id="sidebarLastMessage">
                        {{ $thoughts->last()?->content ? Str::limit($thoughts->last()->content, 35) : 'Real-time discussion active' }}
                    </p>
                </div>
            </div>

            <!-- Group Members Header -->
            <div class="px-4 py-2 bg-slate-50/80 text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                <span>{{ $groupType === 'team' ? 'Team Members' : 'Company Staff' }} ({{ count($teamUsers) }})</span>
                @if(auth()->user()->isTL() && $groupType === 'team')
                    <button type="button" onclick="openManageMembersModal()" class="text-emerald-700 hover:text-emerald-900 text-[10px] font-bold hover:underline cursor-pointer">
                        + Add / Remove
                    </button>
                @endif
            </div>

            <!-- Member List -->
            @foreach($teamUsers as $member)
                <div class="p-2.5 px-3.5 hover:bg-slate-50 transition flex items-center justify-between gap-2.5 group cursor-pointer" onclick="mentionMember('{{ addslashes($member->name) }}')">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="relative shrink-0">
                            @if($member->avatar_url)
                                <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200">
                            @else
                                <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center">
                                    {{ strtoupper(substr($member->name, 0, 2)) }}
                                </div>
                            @endif
                            <span class="absolute bottom-0 right-0 w-2 h-2 bg-emerald-500 rounded-full ring-1 ring-white"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-800 truncate group-hover:text-emerald-600 transition flex items-center gap-1.5">
                                <span>{{ $member->name }}</span>
                                @if($member->isTL())
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-indigo-100 text-indigo-700">TL / Admin</span>
                                @elseif($member->isCEO())
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-amber-100 text-amber-800">CEO</span>
                                @elseif($member->isHR())
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-violet-100 text-violet-800">HR</span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-400 truncate">
                                {{ $member->designation ?: ($member->isTL() ? 'Team Lead' : 'Staff Member') }}
                            </div>
                        </div>
                    </div>
                    <button type="button" class="opacity-0 group-hover:opacity-100 text-[10px] font-bold text-emerald-600 hover:text-emerald-800 transition">
                        @
                    </button>
                </div>
            @endforeach

            <!-- For HR / CEO: All Teams Overview -->
            @if($isManagement && $allTeams->count() > 0)
                <div class="px-4 py-2 bg-slate-50/80 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                    Company Teams Overview ({{ $allTeams->count() }})
                </div>
                @foreach($allTeams as $teamTl)
                    <div class="p-2.5 px-3.5 bg-white flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shield" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <span class="font-bold text-slate-800">{{ $teamTl->name }}'s Team</span>
                        </div>
                        <span class="text-[10px] text-slate-400">{{ $teamTl->team_members_count }} members</span>
                    </div>
                @endforeach
            @endif

        </div>
    </div>

    <!-- 💬 RIGHT MAIN CONVERSATION: WHATSAPP / INSTAGRAM CHAT VIEW -->
    <div class="flex-1 flex flex-col h-full bg-[#efeae2]/90 relative min-w-0">

        <!-- Chat Top Header -->
        <div class="h-16 px-3.5 sm:px-6 bg-[#f0f2f5] border-b border-slate-200 flex items-center justify-between shrink-0 shadow-xs z-20">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <button type="button" onclick="toggleMobileSidebar(true)" class="md:hidden p-1.5 -ml-1 text-slate-600 hover:bg-slate-200 rounded-full transition shrink-0" title="View Channels / Members">
                    <i data-lucide="menu" class="w-5 h-5 text-emerald-700"></i>
                </button>
                <div class="w-10 h-10 rounded-2xl {{ $groupType === 'team' ? 'bg-gradient-to-tr from-emerald-600 to-teal-600' : 'bg-gradient-to-tr from-indigo-600 to-violet-600' }} text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                    <i data-lucide="{{ $groupType === 'team' ? 'users' : 'building' }}" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 truncate flex items-center gap-1.5">
                        <span>{{ $groupTitle }}</span>
                        @if($groupType === 'team')
                            <span class="hidden sm:inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-emerald-100 text-emerald-800">
                                🔒 Team Private
                            </span>
                        @else
                            <span class="hidden sm:inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-indigo-100 text-indigo-800">
                                🏢 Company
                            </span>
                        @endif
                    </h3>
                    <p class="text-[10px] sm:text-[11px] text-slate-500 truncate flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                        <span id="typingIndicatorText">{{ $groupSubtitle }}</span>
                    </p>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                @if(auth()->user()->isTL() && $groupType === 'team')
                    <button type="button" onclick="openManageMembersModal()" class="hidden sm:inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold shadow-2xs transition" title="Add or remove members from team group">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Manage Members</span>
                    </button>
                @endif
                <button type="button" onclick="pollNewMessages(true)" class="p-2 rounded-full hover:bg-slate-200 text-slate-600 transition cursor-pointer" title="Refresh Messages">
                    <i data-lucide="refresh-cw" class="w-4 h-4" id="refreshIcon"></i>
                </button>
            </div>
        </div>

        <!-- WhatsApp Messages Scroll Area -->
        <div id="chatMessagesScrollArea" class="flex-1 overflow-y-auto p-3 sm:p-6 space-y-3.5 scroll-smooth">
            
            <!-- Privacy Disclaimer Pill -->
            <div class="flex justify-center my-2">
                @if($groupType === 'team')
                    <div class="px-3.5 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-[11px] font-medium text-center shadow-2xs max-w-md flex items-center gap-1.5">
                        <i data-lucide="lock" class="w-4 h-4 text-emerald-700 shrink-0"></i>
                        <span><strong>Private Team Chat:</strong> Messages here are strictly between team members and TL. HR and CEO cannot view this chat.</span>
                    </div>
                @else
                    <div class="px-3.5 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-900 text-[11px] font-medium text-center shadow-2xs max-w-md flex items-center gap-1.5">
                        <i data-lucide="building" class="w-4 h-4 text-indigo-700 shrink-0"></i>
                        <span><strong>Company Hub:</strong> Shared coordination channel with HR, CEO, Team Leads, and all staff.</span>
                    </div>
                @endif
            </div>

            <!-- Dynamic Messages List Container -->
            <div id="chatMessagesList" class="space-y-3">
                @php 
                    $lastDate = null; 
                    $userColors = ['#0284c7', '#059669', '#d97706', '#7c3aed', '#db2777', '#2563eb'];
                @endphp

                @foreach($thoughts as $thought)
                    @php
                        $msgDate = $thought->created_at->isToday() ? 'Today' : ($thought->created_at->isYesterday() ? 'Yesterday' : $thought->created_at->format('l, M d, Y'));
                        $isMe = $thought->user_id === auth()->id();
                        $colorIndex = abs(crc32($thought->user?->name ?? 'User')) % count($userColors);
                        $senderColor = $userColors[$colorIndex];
                        $seenBy = $thought->seen_by ?: [];
                        $reactions = $thought->reactions ?: [];
                    @endphp

                    @if($lastDate !== $msgDate)
                        <!-- WhatsApp Date Divider Pill -->
                        <div class="flex justify-center my-3">
                            <span class="px-3 py-1 rounded-lg bg-white/90 shadow-2xs border border-slate-200/80 text-[10px] font-extrabold uppercase tracking-wider text-slate-600">
                                {{ $msgDate }}
                            </span>
                        </div>
                        @php $lastDate = $msgDate; @endphp
                    @endif

                    <!-- WhatsApp Message Bubble -->
                    <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }} message-item group relative" data-message-id="{{ $thought->id }}" data-text="{{ strtolower($thought->content ?? '') }}">
                        
                        <!-- Quick Reaction Bar (Hover on bubble) -->
                        @unless($thought->is_deleted)
                            <div class="hidden group-hover:flex absolute -top-4 {{ $isMe ? 'right-2' : 'left-2' }} bg-white rounded-full shadow-lg border border-slate-200 px-2 py-0.5 items-center gap-1 z-20 animate-in zoom-in-90 duration-100">
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '👍')" class="hover:scale-125 transition text-xs p-1" title="Thumbs Up">👍</button>
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '❤️')" class="hover:scale-125 transition text-xs p-1" title="Love">❤️</button>
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😂')" class="hover:scale-125 transition text-xs p-1" title="Laugh">😂</button>
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😮')" class="hover:scale-125 transition text-xs p-1" title="Wow">😮</button>
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😢')" class="hover:scale-125 transition text-xs p-1" title="Sad">😢</button>
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '🙏')" class="hover:scale-125 transition text-xs p-1" title="Namaste">🙏</button>
                            </div>
                        @endunless

                        <div class="relative max-w-[88%] sm:max-w-[72%] rounded-2xl p-3 shadow-2xs {{ $thought->is_deleted ? 'bg-slate-100/90 text-slate-500 italic border border-slate-200' : ($isMe ? 'bg-[#d9fdd3] text-[#111b21] rounded-tr-xs' : 'bg-white text-[#111b21] rounded-tl-xs') }}">
                            
                            <!-- Tombstone if deleted -->
                            @if($thought->is_deleted)
                                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                    <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $isMe ? 'You unsent this message' : 'This message was deleted' }}</span>
                                </div>
                                <div class="text-[9px] text-slate-400 text-right mt-1">
                                    {{ $thought->created_at->format('h:i A') }}
                                </div>
                            @else
                                <!-- Sender Header (For Group Chat) -->
                                @if(!$isMe)
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <span class="text-xs font-black" style="color: {{ $senderColor }};">
                                            {{ $thought->user?->name ?? 'Team Member' }}
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-400">
                                            {{ $thought->user?->designation ?: ($thought->user?->isTL() ? 'TL' : ($thought->user?->isAdmin() ? strtoupper($thought->user->role) : 'Member')) }}
                                        </span>
                                    </div>
                                @endif

                                <!-- Attached Photo/Video Media Preview -->
                                @if($thought->media_path || $thought->drive_url)
                                    @php
                                        $mediaSrc = $thought->media_path ? asset($thought->media_path) : $thought->drive_url;
                                    @endphp

                                    <div class="mb-2 rounded-xl overflow-hidden bg-slate-900/5 border border-slate-200/80">
                                        @if($thought->media_type === 'image')
                                            <img 
                                                src="{{ $mediaSrc }}" 
                                                alt="Media" 
                                                onclick="openImageLightbox('{{ $mediaSrc }}')" 
                                                class="w-full max-h-72 object-cover rounded-xl cursor-pointer hover:opacity-95 transition">
                                        @elseif($thought->media_type === 'video')
                                            <video controls class="w-full max-h-72 rounded-xl bg-black">
                                                <source src="{{ $mediaSrc }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @endif

                                        <!-- Media Meta Bar (Drive sync status & TL action) -->
                                        <div class="p-1.5 bg-black/5 flex items-center justify-between text-[10px] text-slate-600">
                                            @if($thought->hasDriveSync())
                                                <span class="text-emerald-700 font-bold flex items-center gap-1">
                                                    <i data-lucide="check-check" class="w-3 h-3"></i> Saved to Drive
                                                </span>
                                            @else
                                                <span class="text-slate-400">Local (7-day cache)</span>
                                            @endif

                                            @if(auth()->user()->isTL() && !$thought->hasDriveSync() && $thought->media_path)
                                                <form method="POST" action="{{ route('thoughts.drive.upload', $thought) }}" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 bg-white px-2 py-0.5 rounded shadow-2xs cursor-pointer">
                                                        Sync to Drive
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- Text Message Content -->
                                @if(!empty($thought->content))
                                    <div class="text-[13px] leading-relaxed break-words whitespace-pre-wrap select-text">
                                        {{ $thought->content }}
                                    </div>
                                @endif

                                <!-- Attached Link Card -->
                                @if(!empty($thought->link_url))
                                    <div class="mt-1.5 p-2 rounded-xl bg-black/5 hover:bg-black/10 transition">
                                        <a href="{{ $thought->link_url }}" target="_blank" class="flex items-center gap-1.5 text-indigo-700 hover:underline text-xs font-bold truncate">
                                            <i data-lucide="external-link" class="w-3.5 h-3.5 shrink-0"></i>
                                            <span class="truncate">{{ $thought->link_url }}</span>
                                        </a>
                                    </div>
                                @endif

                                <!-- Reactions Badges (Instagram style) -->
                                @if(count($reactions) > 0)
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @php
                                            $groupedReactions = [];
                                            foreach($reactions as $r) {
                                                $groupedReactions[$r['emoji']][] = $r['user_name'];
                                            }
                                        @endphp
                                        @foreach($groupedReactions as $emoji => $names)
                                            <button type="button" onclick="reactToMessage({{ $thought->id }}, '{{ $emoji }}')" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-white/90 border border-slate-200 text-[11px] shadow-2xs hover:scale-105 transition" title="{{ implode(', ', $names) }}">
                                                <span>{{ $emoji }}</span>
                                                <span class="text-[10px] font-bold text-slate-600">{{ count($names) }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Bottom Metadata: Timestamp + Status Ticks + Info + Unsend / Delete -->
                                <div class="flex items-center justify-end gap-1.5 mt-1.5 text-[10px] text-slate-400 select-none">
                                    <span>{{ $thought->created_at->format('h:i A') }}</span>

                                    <!-- Seen or Not Status -->
                                    @if($isMe)
                                        @if(count($seenBy) > 0)
                                            <!-- Double Blue Checks (Seen) -->
                                            <button type="button" onclick="openMessageInfoModal({{ $thought->id }})" class="inline-flex items-center hover:opacity-80 transition cursor-pointer" title="Seen by {{ count($seenBy) }} members (Click for details)">
                                                <svg class="w-4 h-4 text-[#53bdeb]" viewBox="0 0 16 15" fill="none">
                                                    <path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/>
                                                </svg>
                                            </button>
                                        @else
                                            <!-- Double Gray Checks (Sent / Delivered) -->
                                            <svg class="w-4 h-4 text-slate-400" viewBox="0 0 16 15" fill="none" title="Delivered">
                                                <path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/>
                                            </svg>
                                        @endif
                                    @endif

                                    <!-- Unsend (Sender within 24hr) -->
                                    @if($thought->isUnsendableBy(auth()->user()))
                                        <button type="button" onclick="unsendMessage({{ $thought->id }})" class="text-[10px] text-slate-400 hover:text-rose-600 transition ml-1" title="Unsend message (available within 24h)">
                                            Unsend
                                        </button>
                                    @endif

                                    <!-- Delete (TL can delete anyone's message) -->
                                    @if(auth()->user()->isTL() && !$isMe)
                                        <button type="button" onclick="deleteMessage({{ $thought->id }})" class="text-[10px] text-slate-400 hover:text-rose-600 transition ml-1" title="Delete message as Team Lead">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>

        </div>

        <!-- 📎 ATTACHMENT PREVIEW TRAY -->
        <div id="attachmentPreviewTray" class="hidden px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between gap-3 animate-in slide-in-from-bottom-2 duration-150">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i data-lucide="paperclip" class="w-4 h-4"></i>
                </div>
                <div class="min-w-0">
                    <div id="attachedFileName" class="text-xs font-bold text-slate-800 truncate">No file</div>
                    <div id="attachedFileSize" class="text-[10px] text-slate-400">Ready to send</div>
                </div>
            </div>
            <button type="button" onclick="clearSelectedAttachment()" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 cursor-pointer">
                ✕
            </button>
        </div>

        <!-- 🔗 OPTIONAL LINK INPUT TRAY -->
        <div id="linkInputTray" class="hidden px-4 py-2 bg-white border-t border-slate-200 animate-in slide-in-from-bottom-2 duration-150">
            <div class="relative flex items-center">
                <i data-lucide="link" class="w-4 h-4 text-slate-400 absolute left-3"></i>
                <input 
                    type="url" 
                    id="linkUrlInput" 
                    placeholder="Attach reference link (https://...)" 
                    class="w-full pl-9 pr-8 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-emerald-500">
                <button type="button" onclick="toggleLinkInput(false)" class="absolute right-2.5 text-slate-400 hover:text-slate-600 text-xs">✕</button>
            </div>
        </div>

        <!-- ⌨️ WHATSAPP BOTTOM INPUT BAR -->
        <div class="p-2.5 sm:p-3 bg-[#f0f2f5] border-t border-slate-200 shrink-0">
            <form id="whatsappChatForm" onsubmit="sendWhatsAppMessage(event)" class="flex items-center gap-1.5 sm:gap-2">
                @csrf
                <input type="hidden" name="group_type" value="{{ $groupType }}">

                <!-- Full Emoji Picker Trigger -->
                <div class="relative">
                    <button type="button" onclick="toggleEmojiPicker()" class="p-2 rounded-full text-slate-500 hover:text-slate-700 hover:bg-slate-200/80 transition cursor-pointer" title="Emojis (Indian & Global)">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                        </svg>
                    </button>

                    <!-- WhatsApp & Indian Categorized Emoji Picker Tray -->
                    <div id="emojiPickerTray" class="hidden absolute bottom-14 left-0 w-80 sm:w-96 max-h-80 bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col z-30 animate-in zoom-in-95 duration-100 overflow-hidden">
                        
                        <!-- Category Navigation Header -->
                        <div class="p-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-base">
                            <span class="text-xs font-bold text-slate-700 px-1">Emojis</span>
                            <div class="flex items-center gap-2 text-sm">
                                <button type="button" onclick="switchEmojiCategory('indian')" class="p-1 hover:bg-slate-200 rounded text-xs font-bold" title="India Specials">🇮🇳</button>
                                <button type="button" onclick="switchEmojiCategory('smileys')" class="p-1 hover:bg-slate-200 rounded" title="Smileys">😀</button>
                                <button type="button" onclick="switchEmojiCategory('gestures')" class="p-1 hover:bg-slate-200 rounded" title="Gestures">👍</button>
                                <button type="button" onclick="switchEmojiCategory('hearts')" class="p-1 hover:bg-slate-200 rounded" title="Hearts & Symbols">❤️</button>
                                <button type="button" onclick="switchEmojiCategory('celebration')" class="p-1 hover:bg-slate-200 rounded" title="Celebration & Work">🚀</button>
                            </div>
                            <button type="button" onclick="toggleEmojiPicker(false)" class="text-xs text-slate-400 hover:text-slate-600 px-1 font-bold">✕</button>
                        </div>

                        <!-- Emoji Grid Container -->
                        <div id="emojiGridContainer" class="p-2.5 overflow-y-auto max-h-64 grid grid-cols-8 gap-1.5 text-xl select-none">
                            <!-- Populated via JavaScript dynamically for silky fast rendering -->
                        </div>
                    </div>
                </div>

                <!-- Attachment Button (Paperclip) -->
                <label class="p-2 rounded-full text-slate-500 hover:text-slate-700 hover:bg-slate-200/80 transition cursor-pointer" title="Attach Photo or Video">
                    <svg class="w-5 h-5 transform -rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                    </svg>
                    <input type="file" id="chatMediaInput" name="media" accept="image/*,video/*" class="hidden" onchange="handleChatFileSelect(this)">
                </label>

                <!-- Link Attachment Toggle -->
                <button type="button" onclick="toggleLinkInput()" class="p-2 rounded-full text-slate-500 hover:text-slate-700 hover:bg-slate-200/80 transition cursor-pointer" title="Attach URL">
                    <i data-lucide="link" class="w-4 h-4"></i>
                </button>

                <!-- Message Input Field -->
                <div class="flex-1 relative">
                    <input 
                        type="text" 
                        id="chatMessageInput" 
                        name="content" 
                        autocomplete="off" 
                        placeholder="Type a message..." 
                        class="w-full px-3.5 sm:px-4 py-2 sm:py-2.5 bg-white text-slate-800 placeholder:text-slate-400 text-xs sm:text-sm rounded-2xl focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-2xs border border-slate-200/60 transition">
                </div>

                <!-- WhatsApp Green Send Button -->
                <button 
                    type="submit" 
                    id="sendBtn" 
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-[#00a884] hover:bg-[#008f6f] text-white flex items-center justify-center shadow-md transition-all shrink-0 cursor-pointer group active:scale-95">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 transform rotate-45 -translate-x-0.5 group-hover:translate-x-0 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </form>
        </div>

    </div>

</div>

<!-- 👁️ MESSAGE INFO / SEEN BY MODAL (WhatsApp Style) -->
<div id="messageInfoModal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-2xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-5 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <i data-lucide="check-check" class="w-5 h-5 text-[#53bdeb]"></i>
                <h3 class="text-sm font-bold text-slate-900">Message Info</h3>
            </div>
            <button type="button" onclick="closeMessageInfoModal()" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="py-3">
            <div class="text-[11px] text-slate-500 font-bold uppercase tracking-wider mb-2">Read by</div>
            <div id="seenByList" class="space-y-2 max-h-56 overflow-y-auto">
                <!-- Dynamically populated -->
            </div>
        </div>

        <button type="button" onclick="closeMessageInfoModal()" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
            Close
        </button>
    </div>
</div>

<!-- 👥 TL MANAGE GROUP MEMBERS MODAL (WhatsApp Admin style) -->
@if(auth()->user()->isTL())
<div id="manageMembersModal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-2xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-5 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600"></i>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Group Admin • Members</h3>
                    <p class="text-[10px] text-slate-400">Add or remove members from your private team chat</p>
                </div>
            </div>
            <button type="button" onclick="closeManageMembersModal()" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <!-- Add Member Input -->
        <div class="py-3 border-b border-slate-100">
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Add Employee to Team</label>
            <div class="flex items-center gap-2">
                <select id="newMemberSelect" class="flex-1 px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-emerald-500">
                    <option value="">Select an employee...</option>
                    @foreach($candidateUsers as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }} ({{ $candidate->designation ?: 'Staff' }})</option>
                    @endforeach
                </select>
                <button type="button" onclick="addMemberToGroup()" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shrink-0 cursor-pointer">
                    Add
                </button>
            </div>
        </div>

        <!-- Current Group Members with Remove Action -->
        <div class="py-3">
            <div class="text-[11px] text-slate-500 font-bold uppercase tracking-wider mb-2">Current Members ({{ count($teamUsers) }})</div>
            <div class="space-y-2 max-h-60 overflow-y-auto">
                @foreach($teamUsers as $u)
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($u->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-800 truncate">{{ $u->name }}</div>
                                <div class="text-[10px] text-slate-400 truncate">{{ $u->designation ?: ($u->isTL() ? 'Team Lead (Admin)' : 'Staff') }}</div>
                            </div>
                        </div>
                        
                        @if($u->id !== auth()->id())
                            <button type="button" onclick="removeMemberFromGroup({{ $u->id }}, '{{ addslashes($u->name) }}')" class="px-2 py-1 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 text-[10px] font-bold transition cursor-pointer">
                                Remove
                            </button>
                        @else
                            <span class="px-2 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-[10px] font-extrabold">
                                Admin
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <button type="button" onclick="closeManageMembersModal()" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
            Done
        </button>
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

@push('scripts')
<script>
let latestMessageId = {{ $thoughts->max('id') ?: 0 }};
const currentUserId = {{ auth()->id() }};
const isUserTL = {{ auth()->user()->isTL() ? 'true' : 'false' }};
const activeGroupType = '{{ $groupType }}';
let pollingInterval = null;

// Categorized Emoji Collections (Popular in India & globally)
const emojiCategories = {
    indian: ['🇮🇳', '🙏', '🪔', '🕉️', '🪷', '🏏', '🍛', '☕', '🛺', '🐅', '🐘', '💰', '🎇', '🎆', '🎉', '🤝', '👏', '💐', '🌺', '🕉️', '🥭', '🦚'],
    smileys: ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '🥲', '🥹', '☺️', '😊', '😇', '🙂', '😉', '😌', '😍', '🥰', '😘', '😋', '😛', '😜', '🤪', '😎', '🤩', '🥳', '😏', '🥺', '😢', '😭', '😤', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '🤗', '🤔', '🫣', '🫡', '🤫', '😴', '🤤', '😵'],
    gestures: ['👍', '👎', '👊', '✊', '🤛', '🤜', '🤞', '✌️', '🫰', '🤟', '🤘', '👌', '🤌', '🤏', '👈', '👉', '👆', '👇', '✋', '🤚', '🖐️', '🖖', '👋', '🤙', '👏', '🙌', '🫶', '👐', '🤲', '🙏', '💪', '🤝'],
    hearts: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❤️‍🔥', '❤️‍🩹', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '✨', '🌟', '💥', '💫', '🔥', '💯', '✅', '❌', '⚠️', '🎯'],
    celebration: ['🚀', '🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🥈', '🥉', '💡', '📢', '🔔', '📣', '📌', '📍', '📎', '🔒', '🔑', '💼', '📊', '📈', '📉', '💻', '📱', '⌚', '☕', '🍕', '🎂']
};

document.addEventListener("DOMContentLoaded", function() {
    scrollToBottom();
    startLivePolling();
    switchEmojiCategory('indian');

    // Client search in chat
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
        if (forceState) {
            sidebar.classList.remove('hidden');
        } else {
            sidebar.classList.add('hidden');
        }
    } else {
        sidebar.classList.toggle('hidden');
    }
    if (window.lucide) {
        lucide.createIcons();
    }
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
        <button type="button" onclick="insertEmoji('${e}')" class="p-1.5 hover:bg-slate-100 rounded-xl transition hover:scale-125 cursor-pointer">
            ${e}
        </button>
    `).join('');
}

function insertEmoji(emoji) {
    const input = document.getElementById('chatMessageInput');
    input.value += emoji;
    input.focus();
    toggleEmojiPicker(false);
}

function toggleLinkInput(show = null) {
    const tray = document.getElementById('linkInputTray');
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
        document.getElementById('attachedFileSize').textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB • ' + file.type;
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

// 🟢 Send WhatsApp Message (Zero page reload)
async function sendWhatsAppMessage(event) {
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

    // Reset input fields immediately for instant feel
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
            
            const lastMsgEl = document.getElementById('sidebarLastMessage');
            if (lastMsgEl) lastMsgEl.textContent = data.thought.content || 'Media message';
        }
    } catch (err) {
        console.error('Failed to send message:', err);
    }
}

// 🟢 Unsend Message (Allowed under 24 hours)
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

// 🟢 TL Delete Message (Admin override)
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

// Update deleted message visual tombstone
function updateDeletedMessageBubble(id, isMe) {
    const el = document.querySelector(`[data-message-id="${id}"]`);
    if (!el) return;

    const innerBubble = el.querySelector('.rounded-2xl');
    if (innerBubble) {
        innerBubble.className = 'relative max-w-[88%] sm:max-w-[72%] rounded-2xl p-3 shadow-2xs bg-slate-100/90 text-slate-500 italic border border-slate-200';
        innerBubble.innerHTML = `
            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>${isMe ? 'You unsent this message' : 'This message was deleted'}</span>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    }
}

// 🟢 React to Message (Instagram / WhatsApp reactions)
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

// 🟢 Message Info (Seen by list modal)
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
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-[10px]">
                                ${s.user_name.substring(0, 2).toUpperCase()}
                            </div>
                            <span class="font-bold text-slate-800">${s.user_name}</span>
                        </div>
                        <span class="text-[10px] text-slate-400">${s.seen_at}</span>
                    </div>
                `).join('');
            } else {
                listEl.innerHTML = `<div class="text-xs text-slate-400 text-center py-3">Delivered to everyone. Not opened yet.</div>`;
            }
            document.getElementById('messageInfoModal').classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        }
    } catch (err) {
        console.error('Info modal error:', err);
    }
}

function closeMessageInfoModal() {
    document.getElementById('messageInfoModal').classList.add('hidden');
}

// 🟢 TL Group Admin: Add Member
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

// 🟢 TL Group Admin: Remove Member
async function removeMemberFromGroup(userId, userName) {
    if (!confirm(`Remove ${userName} from this team chat group?`)) return;

    try {
        const res = await fetch(`/thoughts/members/${userId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
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
    if (window.lucide) lucide.createIcons();
}

function closeManageMembersModal() {
    const modal = document.getElementById('manageMembersModal');
    if (modal) modal.classList.add('hidden');
}

// 🟢 Background Live Polling (Every 3.5 Seconds)
function startLivePolling() {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(() => {
        pollNewMessages();
    }, 3500);
}

async function pollNewMessages(manual = false) {
    const refreshIcon = document.getElementById('refreshIcon');
    if (manual && refreshIcon) refreshIcon.classList.add('animate-spin');

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

            const lastOne = data.messages[data.messages.length - 1];
            const lastMsgEl = document.getElementById('sidebarLastMessage');
            if (lastMsgEl && lastOne) {
                lastMsgEl.textContent = (lastOne.is_me ? 'You: ' : `${lastOne.user_name}: `) + (lastOne.content || 'Media message');
            }
        }
    } catch (err) {
        console.warn('Poll notice:', err);
    } finally {
        if (manual && refreshIcon) refreshIcon.classList.remove('animate-spin');
    }
}

// Render dynamic message bubble
function renderMessageBubble(msg) {
    const container = document.getElementById('chatMessagesList');
    if (!container) return;

    const isMe = msg.user_id === currentUserId;
    const item = document.createElement('div');
    item.className = `flex ${isMe ? 'justify-end' : 'justify-start'} message-item animate-in fade-in duration-200 group relative`;
    item.setAttribute('data-message-id', msg.id);
    item.setAttribute('data-text', (msg.content || '').toLowerCase());

    let mediaHtml = '';
    if (msg.media_url && !msg.is_deleted) {
        if (msg.media_type === 'image') {
            mediaHtml = `
                <div class="mb-2 rounded-xl overflow-hidden bg-slate-900/5 border border-slate-200/80">
                    <img src="${msg.media_url}" onclick="openImageLightbox('${msg.media_url}')" class="w-full max-h-72 object-cover rounded-xl cursor-pointer hover:opacity-95 transition">
                    <div class="p-1.5 bg-black/5 flex items-center justify-between text-[10px] text-slate-600">
                        <span class="${msg.has_drive_sync ? 'text-emerald-700 font-bold' : 'text-slate-400'}">
                            ${msg.has_drive_sync ? '✓ Saved to Drive' : 'Local (7-day cache)'}
                        </span>
                    </div>
                </div>`;
        } else if (msg.media_type === 'video') {
            mediaHtml = `
                <div class="mb-2 rounded-xl overflow-hidden bg-black border border-slate-200/80">
                    <video controls class="w-full max-h-72 rounded-xl">
                        <source src="${msg.media_url}">
                    </video>
                </div>`;
        }
    }

    let linkHtml = '';
    if (msg.link_url && !msg.is_deleted) {
        linkHtml = `
            <div class="mt-1.5 p-2 rounded-xl bg-black/5 hover:bg-black/10 transition">
                <a href="${msg.link_url}" target="_blank" class="flex items-center gap-1.5 text-indigo-700 hover:underline text-xs font-bold truncate">
                    <span class="truncate">${msg.link_url}</span>
                </a>
            </div>`;
    }

    let checkmarks = '';
    if (isMe && !msg.is_deleted) {
        if (msg.is_seen) {
            checkmarks = `
                <button type="button" onclick="openMessageInfoModal(${msg.id})" class="inline-flex items-center hover:opacity-80 transition cursor-pointer" title="Seen by ${msg.seen_count} members">
                    <svg class="w-4 h-4 text-[#53bdeb]" viewBox="0 0 16 15" fill="none">
                        <path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/>
                    </svg>
                </button>`;
        } else {
            checkmarks = `
                <svg class="w-4 h-4 text-slate-400" viewBox="0 0 16 15" fill="none" title="Delivered">
                    <path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/>
                </svg>`;
        }
    }

    const senderHeader = (!isMe && !msg.is_deleted) ? `
        <div class="flex items-center justify-between gap-2 mb-1">
            <span class="text-xs font-black text-emerald-700">${msg.user_name}</span>
            <span class="text-[9px] font-bold text-slate-400">${msg.user_role}</span>
        </div>` : '';

    const unsendBtn = (msg.can_unsend && !msg.is_deleted) ? `
        <button type="button" onclick="unsendMessage(${msg.id})" class="text-[10px] text-slate-400 hover:text-rose-600 transition ml-1">
            Unsend
        </button>` : '';

    const deleteBtn = (isUserTL && !isMe && !msg.is_deleted) ? `
        <button type="button" onclick="deleteMessage(${msg.id})" class="text-[10px] text-slate-400 hover:text-rose-600 transition ml-1">
            Delete
        </button>` : '';

    const reactionsBar = !msg.is_deleted ? `
        <div class="hidden group-hover:flex absolute -top-4 ${isMe ? 'right-2' : 'left-2'} bg-white rounded-full shadow-lg border border-slate-200 px-2 py-0.5 items-center gap-1 z-20 animate-in zoom-in-90 duration-100">
            <button type="button" onclick="reactToMessage(${msg.id}, '👍')" class="hover:scale-125 transition text-xs p-1">👍</button>
            <button type="button" onclick="reactToMessage(${msg.id}, '❤️')" class="hover:scale-125 transition text-xs p-1">❤️</button>
            <button type="button" onclick="reactToMessage(${msg.id}, '😂')" class="hover:scale-125 transition text-xs p-1">😂</button>
            <button type="button" onclick="reactToMessage(${msg.id}, '😮')" class="hover:scale-125 transition text-xs p-1">😮</button>
            <button type="button" onclick="reactToMessage(${msg.id}, '😢')" class="hover:scale-125 transition text-xs p-1">😢</button>
            <button type="button" onclick="reactToMessage(${msg.id}, '🙏')" class="hover:scale-125 transition text-xs p-1">🙏</button>
        </div>` : '';

    item.innerHTML = `
        ${reactionsBar}
        <div class="relative max-w-[88%] sm:max-w-[72%] rounded-2xl p-3 shadow-2xs ${msg.is_deleted ? 'bg-slate-100/90 text-slate-500 italic border border-slate-200' : (isMe ? 'bg-[#d9fdd3] text-[#111b21] rounded-tr-xs' : 'bg-white text-[#111b21] rounded-tl-xs')}">
            ${msg.is_deleted ? `
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>${isMe ? 'You unsent this message' : 'This message was deleted'}</span>
                </div>
            ` : `
                ${senderHeader}
                ${mediaHtml}
                ${msg.content ? `<div class="text-[13px] leading-relaxed break-words whitespace-pre-wrap select-text">${msg.content}</div>` : ''}
                ${linkHtml}
                <div class="flex items-center justify-end gap-1.5 mt-1 text-[10px] text-slate-400 select-none">
                    <span>${msg.time}</span>
                    ${checkmarks}
                    ${unsendBtn}
                    ${deleteBtn}
                </div>
            `}
        </div>
    `;

    container.appendChild(item);
    if (window.lucide) lucide.createIcons();
}
</script>
@endpush
@endsection
