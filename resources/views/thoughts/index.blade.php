@extends('layouts.app')
@section('title', $groupTitle ?? 'Team Chat')
@section('page-title', $groupTitle ?? 'Team Chat')

@section('content')
<!-- Full-Bleed Clean WhatsApp / Instagram Chat Container -->
<div class="h-[calc(100dvh-7.5rem)] lg:h-[calc(100vh-4.5rem)] w-full flex flex-col md:flex-row bg-[#efeae2] overflow-hidden relative select-none">

    <!-- 🟢 LEFT SIDEBAR: CHANNELS & MEMBERS (WhatsApp Style) -->
    <div id="chatSidebar" class="hidden md:flex w-full md:w-80 lg:w-96 bg-white border-r border-slate-200 flex-col shrink-0 absolute md:relative inset-0 z-30 md:z-auto">
        
        <!-- Sidebar Top Header -->
        <div class="h-14 px-4 bg-[#f0f2f5] border-b border-slate-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5 min-w-0">
                <button type="button" onclick="toggleMobileSidebar(false)" class="md:hidden p-1 -ml-1 text-slate-600 hover:bg-slate-200 rounded-full transition" title="Back to Chat">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </button>
                <div class="relative shrink-0">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 rounded-full object-cover">
                    @else
                        <div class="w-9 h-9 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full border-2 border-white"></span>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs font-bold text-slate-800 truncate leading-tight">{{ auth()->user()->name }}</h3>
                    <span class="text-[10px] text-emerald-600 font-semibold">{{ auth()->user()->isTL() ? 'Team Lead' : (auth()->user()->isAdmin() ? strtoupper(auth()->user()->role) : 'Staff') }}</span>
                </div>
            </div>

            @if(auth()->user()->isTL() && $groupType === 'team')
                <button type="button" onclick="openManageMembersModal()" class="px-2.5 py-1 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-[11px] font-bold flex items-center gap-1 transition cursor-pointer" title="Manage Members">
                    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                    <span>Admin</span>
                </button>
            @endif
        </div>

        <!-- 📂 GROUP SWITCHER TABS -->
        <div class="p-2 bg-slate-100/90 border-b border-slate-200 grid grid-cols-2 gap-1.5">
            @if(!$isManagement)
                <a href="{{ route('thoughts.index', ['group' => 'team']) }}" 
                   class="flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-xl text-xs font-bold transition {{ $groupType === 'team' ? 'bg-white text-emerald-700 shadow-2xs border border-slate-200' : 'text-slate-600 hover:bg-white/60' }}">
                    <i data-lucide="lock" class="w-3.5 h-3.5 {{ $groupType === 'team' ? 'text-emerald-600' : 'text-slate-400' }}"></i>
                    <span class="truncate">Team Chat</span>
                </a>
            @else
                <div class="flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-xl text-xs font-semibold text-slate-400 bg-slate-200/50 cursor-not-allowed" title="HR & CEO cannot access private team chats">
                    <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                    <span class="truncate">Private Teams 🔒</span>
                </div>
            @endif

            <a href="{{ route('thoughts.index', ['group' => 'company']) }}" 
               class="flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-xl text-xs font-bold transition {{ $groupType === 'company' ? 'bg-white text-indigo-700 shadow-2xs border border-slate-200' : 'text-slate-600 hover:bg-white/60' }}">
                <i data-lucide="building" class="w-3.5 h-3.5 {{ $groupType === 'company' ? 'text-indigo-600' : 'text-slate-400' }}"></i>
                <span class="truncate">Company Hub</span>
            </a>
        </div>

        <!-- Search Bar -->
        <div class="p-2 bg-white border-b border-slate-100">
            <div class="relative flex items-center">
                <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3"></i>
                <input 
                    type="text" 
                    id="searchChatInput" 
                    placeholder="Search in chat..." 
                    class="w-full pl-8 pr-3 py-1 bg-[#f0f2f5] hover:bg-slate-100 focus:bg-white text-xs rounded-xl focus:outline-none focus:ring-1 focus:ring-emerald-500 transition border border-transparent">
            </div>
        </div>

        <!-- Channels & Members List -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
            <!-- Active Channel Summary -->
            <div class="p-3 {{ $groupType === 'team' ? 'bg-emerald-50/60 border-l-4 border-emerald-500' : 'bg-indigo-50/60 border-l-4 border-indigo-500' }} flex items-center gap-3">
                <div class="w-10 h-10 rounded-full {{ $groupType === 'team' ? 'bg-emerald-600' : 'bg-indigo-600' }} text-white flex items-center justify-center font-bold text-sm shrink-0">
                    <i data-lucide="{{ $groupType === 'team' ? 'users' : 'building-2' }}" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-slate-900 truncate">{{ $groupTitle }}</h4>
                        <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded {{ $groupType === 'team' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                            {{ $groupType === 'team' ? 'Team 🔒' : 'Company' }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 truncate mt-0.5" id="sidebarLastMessage">
                        {{ $thoughts->last()?->content ? Str::limit($thoughts->last()->content, 32) : 'Active chat' }}
                    </p>
                </div>
            </div>

            <!-- Members Header -->
            <div class="px-3.5 py-1.5 bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
                <span>Members ({{ count($teamUsers) }})</span>
                @if(auth()->user()->isTL() && $groupType === 'team')
                    <button type="button" onclick="openManageMembersModal()" class="text-emerald-700 hover:text-emerald-900 font-bold hover:underline cursor-pointer">
                        + Edit
                    </button>
                @endif
            </div>

            <!-- Member Rows -->
            @foreach($teamUsers as $member)
                <div class="p-2 px-3 hover:bg-slate-50 transition flex items-center justify-between gap-2 group cursor-pointer" onclick="mentionMember('{{ addslashes($member->name) }}')">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="relative shrink-0">
                            @if($member->avatar_url)
                                <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-7 h-7 rounded-full object-cover">
                            @else
                                <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="absolute bottom-0 right-0 w-2 h-2 bg-emerald-500 rounded-full border border-white"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-slate-800 truncate flex items-center gap-1">
                                <span>{{ $member->name }}</span>
                                @if($member->isTL())
                                    <span class="px-1 py-0.2 rounded text-[8px] font-extrabold bg-indigo-100 text-indigo-700">TL</span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-400 truncate">
                                {{ $member->designation ?: ($member->isTL() ? 'Team Lead' : 'Staff') }}
                            </div>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-600 opacity-0 group-hover:opacity-100 transition">@</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 💬 RIGHT CHAT AREA (Decluttered & Clean) -->
    <div class="flex-1 flex flex-col h-full bg-[#efeae2] relative min-w-0">

        <!-- Top Header (Clean WhatsApp/Instagram Style) -->
        <div class="h-14 px-3 sm:px-4 bg-[#f0f2f5] border-b border-slate-200/90 flex items-center justify-between shrink-0 shadow-2xs z-20">
            <div class="flex items-center gap-2.5 min-w-0">
                <button type="button" onclick="toggleMobileSidebar(true)" class="md:hidden p-1 -ml-1 text-slate-600 hover:bg-slate-200 rounded-full transition shrink-0" title="Channels / Members">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="w-9 h-9 rounded-full {{ $groupType === 'team' ? 'bg-emerald-600' : 'bg-indigo-600' }} text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                    <i data-lucide="{{ $groupType === 'team' ? 'users' : 'building' }}" class="w-4 h-4"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 truncate leading-tight">
                        {{ $groupTitle }}
                    </h3>
                    <p class="text-[10px] sm:text-[11px] text-slate-500 truncate flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                        <span>{{ count($teamUsers) }} members &bull; {{ $groupType === 'team' ? 'Private Team 🔒' : 'Company Coordination 🏢' }}</span>
                    </p>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center gap-1 shrink-0">
                @if(auth()->user()->isTL() && $groupType === 'team')
                    <button type="button" onclick="openManageMembersModal()" class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-[11px] font-bold shadow-2xs transition cursor-pointer">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Manage</span>
                    </button>
                @endif
                <button type="button" onclick="pollNewMessages(true)" class="p-1.5 rounded-full hover:bg-slate-200 text-slate-600 transition cursor-pointer" title="Refresh">
                    <i data-lucide="refresh-cw" class="w-4 h-4" id="refreshIcon"></i>
                </button>
            </div>
        </div>

        <!-- Scrollable Messages Viewport -->
        <div id="chatMessagesScrollArea" class="flex-1 overflow-y-auto p-3 sm:p-5 space-y-2.5 scroll-smooth">
            
            <!-- Tiny Subtle Privacy Note (Zero Clutter) -->
            <div class="flex justify-center my-1">
                <span class="px-2.5 py-0.5 rounded-full bg-black/5 text-slate-500 text-[10px] font-medium flex items-center gap-1 shadow-2xs">
                    <i data-lucide="lock" class="w-3 h-3 text-slate-400"></i>
                    <span>{{ $groupType === 'team' ? 'Private Team Chat (No HR / CEO)' : 'Company Coordination Channel' }}</span>
                </span>
            </div>

            <!-- Messages List -->
            <div id="chatMessagesList" class="space-y-2">
                @php 
                    $lastDate = null; 
                    $userColors = ['#0284c7', '#059669', '#d97706', '#7c3aed', '#db2777', '#2563eb'];
                @endphp

                @foreach($thoughts as $thought)
                    @php
                        $msgDate = $thought->created_at->isToday() ? 'Today' : ($thought->created_at->isYesterday() ? 'Yesterday' : $thought->created_at->format('M d, Y'));
                        $isMe = $thought->user_id === auth()->id();
                        $colorIndex = abs(crc32($thought->user?->name ?? 'User')) % count($userColors);
                        $senderColor = $userColors[$colorIndex];
                        $seenBy = $thought->seen_by ?: [];
                        $reactions = $thought->reactions ?: [];
                    @endphp

                    @if($lastDate !== $msgDate)
                        <div class="flex justify-center my-2">
                            <span class="px-2.5 py-0.5 rounded-md bg-white/90 shadow-2xs text-[10px] font-bold text-slate-500 uppercase tracking-wide">
                                {{ $msgDate }}
                            </span>
                        </div>
                        @php $lastDate = $msgDate; @endphp
                    @endif

                    <!-- WhatsApp Message Bubble (Fit content, no blank waste) -->
                    <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }} message-item group relative" data-message-id="{{ $thought->id }}" data-text="{{ strtolower($thought->content ?? '') }}">
                        
                        <!-- Quick Reaction Bar on Hover -->
                        @unless($thought->is_deleted)
                            <div class="hidden group-hover:flex absolute -top-3.5 {{ $isMe ? 'right-2' : 'left-2' }} bg-white rounded-full shadow-md border border-slate-200 px-1.5 py-0.5 items-center gap-0.5 z-20 animate-in zoom-in-90 duration-75">
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '👍')" class="hover:scale-125 transition text-xs p-0.5">👍</button>
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '❤️')" class="hover:scale-125 transition text-xs p-0.5">❤️</button>
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😂')" class="hover:scale-125 transition text-xs p-0.5">😂</button>
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '😮')" class="hover:scale-125 transition text-xs p-0.5">😮</button>
                                <button type="button" onclick="reactToMessage({{ $thought->id }}, '🙏')" class="hover:scale-125 transition text-xs p-0.5">🙏</button>
                            </div>
                        @endunless

                        <div class="relative w-fit max-w-[85%] sm:max-w-[70%] rounded-2xl px-3 py-1.5 shadow-2xs {{ $thought->is_deleted ? 'bg-slate-100/90 text-slate-500 italic border border-slate-200 text-xs' : ($isMe ? 'bg-[#d9fdd3] text-[#111b21] rounded-tr-xs' : 'bg-white text-[#111b21] rounded-tl-xs') }}">
                            
                            @if($thought->is_deleted)
                                <div class="flex items-center gap-1.5 py-0.5">
                                    <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $isMe ? 'You unsent this message' : 'This message was deleted' }}</span>
                                </div>
                            @else
                                <!-- Sender Name (Compact, no giant whitespace) -->
                                @if(!$isMe)
                                    <div class="text-[11px] font-bold leading-tight mb-0.5" style="color: {{ $senderColor }};">
                                        {{ $thought->user?->name ?? 'Member' }}
                                        @if($thought->user?->isTL())
                                            <span class="text-[9px] font-extrabold text-indigo-600 bg-indigo-50 px-1 rounded ml-1">TL</span>
                                        @endif
                                    </div>
                                @endif

                                <!-- Media Preview -->
                                @if($thought->media_path || $thought->drive_url)
                                    @php
                                        $mediaSrc = $thought->media_path ? asset($thought->media_path) : $thought->drive_url;
                                    @endphp
                                    <div class="my-1 rounded-xl overflow-hidden bg-black/5">
                                        @if($thought->media_type === 'image')
                                            <img src="{{ $mediaSrc }}" alt="Media" onclick="openImageLightbox('{{ $mediaSrc }}')" class="max-h-60 rounded-xl object-cover cursor-pointer hover:opacity-95 transition">
                                        @elseif($thought->media_type === 'video')
                                            <video controls class="max-h-60 rounded-xl bg-black">
                                                <source src="{{ $mediaSrc }}">
                                            </video>
                                        @endif
                                        @if(auth()->user()->isTL() && !$thought->hasDriveSync() && $thought->media_path)
                                            <form method="POST" action="{{ route('thoughts.drive.upload', $thought) }}" class="m-1">
                                                @csrf
                                                <button type="submit" class="text-[10px] font-bold text-indigo-700 bg-white px-2 py-0.5 rounded shadow-2xs">
                                                    Sync to Drive
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif

                                <!-- Message Text -->
                                @if(!empty($thought->content))
                                    <div class="text-[13px] leading-snug break-words whitespace-pre-wrap select-text inline">
                                        {{ $thought->content }}
                                    </div>
                                @endif

                                <!-- Attached URL -->
                                @if(!empty($thought->link_url))
                                    <div class="mt-1 p-1.5 rounded-lg bg-black/5">
                                        <a href="{{ $thought->link_url }}" target="_blank" class="text-xs text-indigo-700 font-semibold hover:underline flex items-center gap-1 truncate">
                                            <i data-lucide="link" class="w-3 h-3 shrink-0"></i>
                                            <span class="truncate">{{ $thought->link_url }}</span>
                                        </a>
                                    </div>
                                @endif

                                <!-- Reaction Chips -->
                                @if(count($reactions) > 0)
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @php
                                            $groupedReactions = [];
                                            foreach($reactions as $r) {
                                                $groupedReactions[$r['emoji']][] = $r['user_name'];
                                            }
                                        @endphp
                                        @foreach($groupedReactions as $emoji => $names)
                                            <button type="button" onclick="reactToMessage({{ $thought->id }}, '{{ $emoji }}')" class="inline-flex items-center gap-0.5 px-1 py-0.2 rounded-full bg-white/90 border border-slate-200 text-[10px] shadow-2xs" title="{{ implode(', ', $names) }}">
                                                <span>{{ $emoji }}</span>
                                                <span class="font-bold text-slate-600">{{ count($names) }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Timestamp & Status (Tightly packed like WhatsApp) -->
                                <span class="inline-flex items-center gap-1 float-right ml-2 mt-1 text-[10px] text-slate-400 select-none leading-none">
                                    <span>{{ $thought->created_at->format('h:i A') }}</span>
                                    @if($isMe)
                                        @if(count($seenBy) > 0)
                                            <button type="button" onclick="openMessageInfoModal({{ $thought->id }})" class="hover:opacity-75 cursor-pointer" title="Seen by {{ count($seenBy) }} members">
                                                <svg class="w-3.5 h-3.5 text-[#53bdeb]" viewBox="0 0 16 15" fill="none">
                                                    <path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/>
                                                </svg>
                                            </button>
                                        @else
                                            <svg class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 16 15" fill="none">
                                                <path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/>
                                            </svg>
                                        @endif
                                    @endif

                                    @if($thought->isUnsendableBy(auth()->user()))
                                        <button type="button" onclick="unsendMessage({{ $thought->id }})" class="hover:text-rose-600 transition ml-0.5 font-medium" title="Unsend (24h)">✕</button>
                                    @endif

                                    @if(auth()->user()->isTL() && !$isMe)
                                        <button type="button" onclick="deleteMessage({{ $thought->id }})" class="hover:text-rose-600 transition ml-0.5 font-medium" title="Delete">✕</button>
                                    @endif
                                </span>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>

        </div>

        <!-- Attachment Preview Bar (If file selected) -->
        <div id="attachmentPreviewTray" class="hidden px-3 py-1.5 bg-white border-t border-slate-200 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <i data-lucide="paperclip" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span id="attachedFileName" class="text-xs font-bold text-slate-800 truncate"></span>
            </div>
            <button type="button" onclick="clearSelectedAttachment()" class="text-slate-400 hover:text-slate-600 text-xs p-1">✕</button>
        </div>

        <!-- Link Attachment Input (If link toggled) -->
        <div id="linkInputTray" class="hidden px-3 py-1.5 bg-white border-t border-slate-200">
            <div class="relative flex items-center">
                <i data-lucide="link" class="w-3.5 h-3.5 text-slate-400 absolute left-3"></i>
                <input type="url" id="linkUrlInput" placeholder="Paste link (https://...)" class="w-full pl-8 pr-7 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <button type="button" onclick="toggleLinkInput(false)" class="absolute right-2 text-slate-400 text-xs">✕</button>
            </div>
        </div>

        <!-- ⌨️ DECLUTTERED WHATSAPP INPUT BAR -->
        <div class="p-2 sm:p-2.5 bg-[#f0f2f5] border-t border-slate-200/90 shrink-0">
            <form id="whatsappChatForm" onsubmit="sendWhatsAppMessage(event)" class="flex items-center gap-1.5 sm:gap-2">
                @csrf
                <input type="hidden" name="group_type" value="{{ $groupType }}">

                <!-- Emoji Button -->
                <div class="relative">
                    <button type="button" onclick="toggleEmojiPicker()" class="p-1.5 text-slate-500 hover:text-slate-700 rounded-full hover:bg-slate-200/80 transition cursor-pointer" title="Emojis">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                        </svg>
                    </button>

                    <!-- Emoji Tray Drawer -->
                    <div id="emojiPickerTray" class="hidden absolute bottom-12 left-0 w-72 sm:w-80 max-h-72 bg-white rounded-2xl shadow-xl border border-slate-200 flex flex-col z-30 overflow-hidden">
                        <div class="p-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-600">Reactions & Emojis</span>
                            <div class="flex items-center gap-1.5 text-xs">
                                <button type="button" onclick="switchEmojiCategory('indian')" class="p-1 hover:bg-slate-200 rounded">🇮🇳</button>
                                <button type="button" onclick="switchEmojiCategory('smileys')" class="p-1 hover:bg-slate-200 rounded">😀</button>
                                <button type="button" onclick="switchEmojiCategory('gestures')" class="p-1 hover:bg-slate-200 rounded">👍</button>
                                <button type="button" onclick="switchEmojiCategory('hearts')" class="p-1 hover:bg-slate-200 rounded">❤️</button>
                                <button type="button" onclick="switchEmojiCategory('celebration')" class="p-1 hover:bg-slate-200 rounded">🚀</button>
                            </div>
                            <button type="button" onclick="toggleEmojiPicker(false)" class="text-slate-400 font-bold p-1">✕</button>
                        </div>
                        <div id="emojiGridContainer" class="p-2 overflow-y-auto max-h-56 grid grid-cols-7 gap-1 text-lg select-none"></div>
                    </div>
                </div>

                <!-- Attachment Trigger (+) -->
                <label class="p-1.5 text-slate-500 hover:text-slate-700 rounded-full hover:bg-slate-200/80 transition cursor-pointer" title="Attach Media">
                    <svg class="w-5 h-5 transform -rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                    </svg>
                    <input type="file" id="chatMediaInput" name="media" accept="image/*,video/*" class="hidden" onchange="handleChatFileSelect(this)">
                </label>

                <!-- Link Trigger -->
                <button type="button" onclick="toggleLinkInput()" class="p-1.5 text-slate-500 hover:text-slate-700 rounded-full hover:bg-slate-200/80 transition cursor-pointer" title="Attach Link">
                    <i data-lucide="link" class="w-4 h-4"></i>
                </button>

                <!-- Clean Rounded Text Input -->
                <div class="flex-1 relative">
                    <input 
                        type="text" 
                        id="chatMessageInput" 
                        name="content" 
                        autocomplete="off" 
                        placeholder="Type a message..." 
                        class="w-full px-4 py-2 bg-white text-slate-800 placeholder:text-slate-400 text-xs sm:text-sm rounded-full focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-2xs border border-slate-200 transition">
                </div>

                <!-- WhatsApp Send Button -->
                <button 
                    type="submit" 
                    id="sendBtn" 
                    class="w-9 h-9 rounded-full bg-[#00a884] hover:bg-[#008f6f] text-white flex items-center justify-center shadow-sm transition shrink-0 cursor-pointer active:scale-95">
                    <svg class="w-4 h-4 transform rotate-45 -translate-x-0.5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </form>
        </div>

    </div>

</div>

<!-- 👁️ MESSAGE INFO MODAL -->
<div id="messageInfoModal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-2xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-xs w-full p-4 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <h3 class="text-xs font-bold text-slate-800">Message Info</h3>
            <button type="button" onclick="closeMessageInfoModal()" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
        </div>
        <div class="py-2.5">
            <div class="text-[10px] text-slate-400 font-bold uppercase mb-1.5">Read by</div>
            <div id="seenByList" class="space-y-1.5 max-h-48 overflow-y-auto"></div>
        </div>
        <button type="button" onclick="closeMessageInfoModal()" class="w-full py-1.5 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl">Close</button>
    </div>
</div>

<!-- 👥 TL MANAGE GROUP MEMBERS MODAL -->
@if(auth()->user()->isTL())
<div id="manageMembersModal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-2xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-4 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <div>
                <h3 class="text-xs font-bold text-slate-800">Manage Team Members</h3>
                <p class="text-[10px] text-slate-400">Add or remove from private team chat</p>
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
                <button type="button" onclick="addMemberToGroup()" class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-bold">Add</button>
            </div>
        </div>

        <div class="py-2">
            <div class="text-[10px] text-slate-400 font-bold uppercase mb-1.5">Current Members ({{ count($teamUsers) }})</div>
            <div class="space-y-1.5 max-h-48 overflow-y-auto">
                @foreach($teamUsers as $u)
                    <div class="flex items-center justify-between p-1.5 rounded-xl bg-slate-50 text-xs">
                        <span class="font-semibold text-slate-800 truncate">{{ $u->name }}</span>
                        @if($u->id !== auth()->id())
                            <button type="button" onclick="removeMemberFromGroup({{ $u->id }}, '{{ addslashes($u->name) }}')" class="px-2 py-0.5 rounded bg-rose-50 text-rose-600 text-[10px] font-bold">Remove</button>
                        @else
                            <span class="text-[10px] font-bold text-emerald-700">Admin</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <button type="button" onclick="closeManageMembersModal()" class="w-full py-1.5 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl mt-1">Done</button>
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

const emojiCategories = {
    indian: ['🇮🇳', '🙏', '🪔', '🕉️', '🪷', '🏏', '🍛', '☕', '🛺', '🐅', '🐘', '💰', '🎇', '🎆', '🎉', '🤝', '👏', '💐', '🌺', '🥭', '🦚'],
    smileys: ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '🥲', '🥹', '☺️', '😊', '😇', '🙂', '😉', '😌', '😍', '🥰', '😘', '😋', '😎', '🤩', '🥳', '🥺', '😢', '😭', '🤯', '😱', '🤗', '🤔', '🤫', '😴'],
    gestures: ['👍', '👎', '👊', '✊', '🤛', '🤜', '🤞', '✌️', '🫰', '🤟', '🤘', '👌', '🤌', '🤏', '👈', '👉', '👆', '👇', '✋', '👋', '👏', '🙌', '🫶', '🙏', '💪', '🤝'],
    hearts: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❤️‍🔥', '💕', '💞', '💓', '💗', '💖', '✨', '🌟', '💥', '🔥', '💯', '✅', '❌', '⚠️', '🎯'],
    celebration: ['🚀', '🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🥈', '🥉', '💡', '📢', '🔔', '📌', '📎', '🔒', '💼', '📊', '💻', '📱', '☕', '🎂']
};

document.addEventListener("DOMContentLoaded", function() {
    scrollToBottom();
    startLivePolling();
    switchEmojiCategory('indian');

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
        <button type="button" onclick="insertEmoji('${e}')" class="p-1 hover:bg-slate-100 rounded-lg transition hover:scale-125 cursor-pointer">
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

// 🟢 Send WhatsApp Message
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

    const innerBubble = el.querySelector('.rounded-2xl');
    if (innerBubble) {
        innerBubble.className = 'relative w-fit max-w-[85%] sm:max-w-[70%] rounded-2xl px-3 py-1.5 shadow-2xs bg-slate-100/90 text-slate-500 italic border border-slate-200 text-xs';
        innerBubble.innerHTML = `
            <div class="flex items-center gap-1.5 py-0.5">
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
                    <div class="flex items-center justify-between p-1.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
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
    if (!confirm(`Remove ${userName} from team group?`)) return;

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
}

function closeManageMembersModal() {
    const modal = document.getElementById('manageMembersModal');
    if (modal) modal.classList.add('hidden');
}

// 🟢 Background Live Polling
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

// Render WhatsApp Message HTML (Snug, no blank waste)
function renderMessageBubble(msg) {
    const container = document.getElementById('chatMessagesList');
    if (!container) return;

    const isMe = msg.user_id === currentUserId;
    const item = document.createElement('div');
    item.className = `flex ${isMe ? 'justify-end' : 'justify-start'} message-item animate-in fade-in duration-100 group relative`;
    item.setAttribute('data-message-id', msg.id);
    item.setAttribute('data-text', (msg.content || '').toLowerCase());

    let mediaHtml = '';
    if (msg.media_url && !msg.is_deleted) {
        if (msg.media_type === 'image') {
            mediaHtml = `
                <div class="my-1 rounded-xl overflow-hidden bg-black/5">
                    <img src="${msg.media_url}" onclick="openImageLightbox('${msg.media_url}')" class="max-h-60 rounded-xl object-cover cursor-pointer hover:opacity-95 transition">
                </div>`;
        } else if (msg.media_type === 'video') {
            mediaHtml = `
                <div class="my-1 rounded-xl overflow-hidden bg-black">
                    <video controls class="max-h-60 rounded-xl">
                        <source src="${msg.media_url}">
                    </video>
                </div>`;
        }
    }

    let linkHtml = '';
    if (msg.link_url && !msg.is_deleted) {
        linkHtml = `
            <div class="mt-1 p-1.5 rounded-lg bg-black/5">
                <a href="${msg.link_url}" target="_blank" class="text-xs text-indigo-700 font-semibold hover:underline flex items-center gap-1 truncate">
                    <span class="truncate">${msg.link_url}</span>
                </a>
            </div>`;
    }

    let checkmarks = '';
    if (isMe && !msg.is_deleted) {
        if (msg.is_seen) {
            checkmarks = `
                <button type="button" onclick="openMessageInfoModal(${msg.id})" class="hover:opacity-75 cursor-pointer" title="Seen by ${msg.seen_count} members">
                    <svg class="w-3.5 h-3.5 text-[#53bdeb]" viewBox="0 0 16 15" fill="none">
                        <path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/>
                    </svg>
                </button>`;
        } else {
            checkmarks = `
                <svg class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 16 15" fill="none">
                    <path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/>
                </svg>`;
        }
    }

    const senderHeader = (!isMe && !msg.is_deleted) ? `
        <div class="text-[11px] font-bold leading-tight mb-0.5 text-emerald-700">
            ${msg.user_name}
        </div>` : '';

    const unsendBtn = (msg.can_unsend && !msg.is_deleted) ? `
        <button type="button" onclick="unsendMessage(${msg.id})" class="hover:text-rose-600 transition ml-0.5 font-medium" title="Unsend">✕</button>` : '';

    const deleteBtn = (isUserTL && !isMe && !msg.is_deleted) ? `
        <button type="button" onclick="deleteMessage(${msg.id})" class="hover:text-rose-600 transition ml-0.5 font-medium" title="Delete">✕</button>` : '';

    const reactionsBar = !msg.is_deleted ? `
        <div class="hidden group-hover:flex absolute -top-3.5 ${isMe ? 'right-2' : 'left-2'} bg-white rounded-full shadow-md border border-slate-200 px-1.5 py-0.5 items-center gap-0.5 z-20 animate-in zoom-in-90 duration-75">
            <button type="button" onclick="reactToMessage(${msg.id}, '👍')" class="hover:scale-125 transition text-xs p-0.5">👍</button>
            <button type="button" onclick="reactToMessage(${msg.id}, '❤️')" class="hover:scale-125 transition text-xs p-0.5">❤️</button>
            <button type="button" onclick="reactToMessage(${msg.id}, '😂')" class="hover:scale-125 transition text-xs p-0.5">😂</button>
            <button type="button" onclick="reactToMessage(${msg.id}, '😮')" class="hover:scale-125 transition text-xs p-0.5">😮</button>
            <button type="button" onclick="reactToMessage(${msg.id}, '🙏')" class="hover:scale-125 transition text-xs p-0.5">🙏</button>
        </div>` : '';

    item.innerHTML = `
        ${reactionsBar}
        <div class="relative w-fit max-w-[85%] sm:max-w-[70%] rounded-2xl px-3 py-1.5 shadow-2xs ${msg.is_deleted ? 'bg-slate-100/90 text-slate-500 italic border border-slate-200 text-xs' : (isMe ? 'bg-[#d9fdd3] text-[#111b21] rounded-tr-xs' : 'bg-white text-[#111b21] rounded-tl-xs')}">
            ${msg.is_deleted ? `
                <div class="flex items-center gap-1.5 py-0.5">
                    <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>${isMe ? 'You unsent this message' : 'This message was deleted'}</span>
                </div>
            ` : `
                ${senderHeader}
                ${mediaHtml}
                ${msg.content ? `<div class="text-[13px] leading-snug break-words whitespace-pre-wrap select-text inline">${msg.content}</div>` : ''}
                ${linkHtml}
                <span class="inline-flex items-center gap-1 float-right ml-2 mt-1 text-[10px] text-slate-400 select-none leading-none">
                    <span>${msg.time}</span>
                    ${checkmarks}
                    ${unsendBtn}
                    ${deleteBtn}
                </span>
            `}
        </div>
    `;

    container.appendChild(item);
    if (window.lucide) lucide.createIcons();
}
</script>
@endpush
@endsection
