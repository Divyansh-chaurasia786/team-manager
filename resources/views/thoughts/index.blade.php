@extends('layouts.app')
@section('title', 'WhatsApp Team Chat')
@section('page-title', 'EcoFone Team Chat')

@section('content')
<div class="h-[calc(100vh-13rem)] sm:h-[calc(100vh-8rem)] md:h-[calc(100vh-7rem)] mb-16 lg:mb-0 flex flex-col md:flex-row bg-[#efeae2] rounded-2xl md:rounded-3xl overflow-hidden border border-slate-300/80 shadow-2xl relative" style="background-image: radial-gradient(#cbd5e1 0.75px, transparent 0.75px); background-size: 16px 16px;">

    <!-- 🟢 LEFT SIDEBAR: TEAM CHANNELS & ACTIVE MEMBERS -->
    <div id="chatSidebar" class="hidden md:flex w-full md:w-80 lg:w-96 bg-white border-r border-slate-200 flex-col shrink-0 absolute md:relative inset-0 z-30 md:z-auto">
        
        <!-- Sidebar Top Header (WhatsApp Style) -->
        <div class="h-16 px-4 bg-[#f0f2f5] border-b border-slate-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <button type="button" onclick="toggleMobileSidebar(false)" class="md:hidden p-1.5 -ml-1 text-slate-600 hover:bg-slate-200 rounded-full transition" title="Back to Chat">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </button>
                <div class="relative">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full object-cover shadow-xs border border-white">
                    @else
                        <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-bold text-sm flex items-center justify-center shadow-xs">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white"></span>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 leading-tight">{{ auth()->user()->name }}</h3>
                    <span class="text-[11px] font-semibold text-emerald-600">Online &bull; {{ auth()->user()->isTL() ? 'Team Lead' : 'Staff' }}</span>
                </div>
            </div>

            <div class="flex items-center gap-1 text-slate-500">
                <span class="p-2 rounded-full hover:bg-slate-200/80 transition cursor-pointer text-slate-600" title="EcoFone Operations">
                    <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600"></i>
                </span>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="p-2.5 bg-white border-b border-slate-100">
            <div class="relative flex items-center">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3"></i>
                <input 
                    type="text" 
                    id="searchChatInput" 
                    placeholder="Search in chat..." 
                    class="w-full pl-9 pr-3.5 py-1.5 bg-[#f0f2f5] hover:bg-slate-100 focus:bg-white text-xs rounded-xl focus:outline-none focus:ring-1 focus:ring-emerald-500 transition placeholder:text-slate-400 border border-transparent focus:border-emerald-400">
            </div>
        </div>

        <!-- Chat List & Active Team Members -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100/80">
            
            <!-- Main Group Chat (Selected) -->
            <div class="p-3.5 bg-[#f0f2f5]/90 hover:bg-[#f0f2f5] transition flex items-center gap-3 cursor-pointer border-l-4 border-emerald-500">
                <div class="relative shrink-0">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shadow-xs">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                    <span class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-slate-900 truncate">EcoFone Team Discussion</h4>
                        <span class="text-[10px] font-bold text-emerald-600">Live</span>
                    </div>
                    <p class="text-xs text-slate-500 truncate mt-0.5" id="sidebarLastMessage">
                        {{ $thoughts->last()?->content ? Str::limit($thoughts->last()->content, 35) : 'Active discussion thread' }}
                    </p>
                </div>
            </div>

            <!-- Active Team Members Section -->
            <div class="px-4 py-2 bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                <span>Team Members ({{ count($teamUsers) }})</span>
                <span class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Connected
                </span>
            </div>

            @foreach($teamUsers as $member)
                <div class="p-3 hover:bg-slate-50 transition flex items-center justify-between gap-3 group cursor-pointer" onclick="mentionMember('{{ addslashes($member->name) }}')">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="relative shrink-0">
                            @if($member->avatar_url)
                                <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-9 h-9 rounded-full object-cover">
                            @else
                                <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center">
                                    {{ strtoupper(substr($member->name, 0, 2)) }}
                                </div>
                            @endif
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full border border-white"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-800 truncate group-hover:text-emerald-600 transition flex items-center gap-1.5">
                                <span>{{ $member->name }}</span>
                                @if($member->isTL())
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-indigo-100 text-indigo-700">TL</span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-400 truncate">
                                {{ $member->designation ?: ($member->isTL() ? 'Team Lead' : 'Staff Member') }}
                            </div>
                        </div>
                    </div>
                    <button type="button" class="opacity-0 group-hover:opacity-100 text-[10px] font-bold text-emerald-600 hover:text-emerald-800 transition">
                        Mention
                    </button>
                </div>
            @endforeach

        </div>
    </div>

    <!-- 💬 RIGHT MAIN CONVERSATION: WHATSAPP CHAT VIEW -->
    <div class="flex-1 flex flex-col h-full bg-[#efeae2]/90 relative min-w-0">

        <!-- WhatsApp Chat Top Header -->
        <div class="h-16 px-4 sm:px-6 bg-[#f0f2f5] border-b border-slate-200 flex items-center justify-between shrink-0 shadow-xs z-20">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <button type="button" onclick="toggleMobileSidebar(true)" class="md:hidden p-1.5 -ml-1 text-slate-600 hover:bg-slate-200 rounded-full transition shrink-0" title="View Members">
                    <i data-lucide="users" class="w-5 h-5 text-emerald-600"></i>
                </button>
                <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                    <i data-lucide="message-square" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 truncate flex items-center gap-2">
                        <span>EcoFone Team Discussion</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 truncate flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span id="typingIndicatorText">Online &bull; {{ count($teamUsers) }} members &bull; End-to-end sync</span>
                    </p>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center gap-2 shrink-0">
                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-800 text-[11px] font-bold">
                    <i data-lucide="cloud" class="w-3.5 h-3.5"></i>
                    <span>Drive Connected</span>
                </span>
                <button type="button" onclick="pollNewMessages(true)" class="p-2 rounded-full hover:bg-slate-200 text-slate-600 transition cursor-pointer" title="Refresh Messages">
                    <i data-lucide="refresh-cw" class="w-4 h-4" id="refreshIcon"></i>
                </button>
            </div>
        </div>

        <!-- WhatsApp Messages Scroll Area -->
        <div id="chatMessagesScrollArea" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3.5 scroll-smooth">
            
            <!-- WhatsApp Welcome Encryption Pill -->
            <div class="flex justify-center my-2">
                <div class="px-3.5 py-1.5 rounded-xl bg-[#ffeecd] border border-[#fae2a6] text-[#6b5832] text-[11px] font-medium text-center shadow-2xs max-w-md flex items-center gap-1.5">
                    <i data-lucide="lock" class="w-3.5 h-3.5 text-[#b08b3c] shrink-0"></i>
                    <span>Messages are synchronized in real-time across the EcoFone workspace with Google Drive cloud archive.</span>
                </div>
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
                    <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }} message-item" data-message-id="{{ $thought->id }}" data-text="{{ strtolower($thought->content ?? '') }}">
                        <div class="relative max-w-[85%] sm:max-w-[70%] rounded-2xl p-3 shadow-2xs {{ $isMe ? 'bg-[#d9fdd3] text-[#111b21] rounded-tr-xs' : 'bg-white text-[#111b21] rounded-tl-xs' }}">
                            
                            <!-- Sender Header (For Group Chat) -->
                            @if(!$isMe)
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="text-xs font-black" style="color: {{ $senderColor }};">
                                        {{ $thought->user?->name ?? 'Team Member' }}
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400">
                                        {{ $thought->user?->designation ?: ($thought->user?->isTL() ? 'TL' : 'Member') }}
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

                            <!-- Bottom Metadata: Timestamp + WhatsApp Double Check -->
                            <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-slate-400 select-none">
                                <span>{{ $thought->created_at->format('h:i A') }}</span>
                                @if($isMe)
                                    <!-- Double Blue Checkmarks (Read) -->
                                    <svg class="w-3.5 h-3.5 text-[#53bdeb]" viewBox="0 0 16 15" fill="none">
                                        <path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/>
                                    </svg>
                                @endif

                                @if(auth()->user()->isTL())
                                    <form method="POST" action="{{ route('thoughts.destroy', $thought) }}" class="inline ml-1" onsubmit="return confirm('Remove message?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="hover:text-rose-600 transition" title="Delete">✕</button>
                                    </form>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

        </div>

        <!-- 📎 ATTACHMENT PREVIEW TRAY (Shown when user selects a file or toggles link) -->
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
        <div class="p-3 bg-[#f0f2f5] border-t border-slate-200 shrink-0">
            <form id="whatsappChatForm" onsubmit="sendWhatsAppMessage(event)" class="flex items-center gap-2">
                @csrf

                <!-- Emoji Picker / Reactions Drawer Toggle -->
                <div class="relative">
                    <button type="button" onclick="toggleEmojiPicker()" class="p-2 rounded-full text-slate-500 hover:text-slate-700 hover:bg-slate-200/80 transition cursor-pointer" title="Quick Emojis">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                        </svg>
                    </button>
                    <!-- Quick Emoji Bar -->
                    <div id="emojiPickerTray" class="hidden absolute bottom-12 left-0 bg-white rounded-2xl p-2 shadow-xl border border-slate-200 flex items-center gap-1.5 z-30 animate-in zoom-in-95 duration-100">
                        <button type="button" onclick="insertEmoji('👍')" class="p-1.5 hover:bg-slate-100 rounded-lg text-lg">👍</button>
                        <button type="button" onclick="insertEmoji('❤️')" class="p-1.5 hover:bg-slate-100 rounded-lg text-lg">❤️</button>
                        <button type="button" onclick="insertEmoji('😂')" class="p-1.5 hover:bg-slate-100 rounded-lg text-lg">😂</button>
                        <button type="button" onclick="insertEmoji('🚀')" class="p-1.5 hover:bg-slate-100 rounded-lg text-lg">🚀</button>
                        <button type="button" onclick="insertEmoji('🔥')" class="p-1.5 hover:bg-slate-100 rounded-lg text-lg">🔥</button>
                        <button type="button" onclick="insertEmoji('👏')" class="p-1.5 hover:bg-slate-100 rounded-lg text-lg">👏</button>
                        <button type="button" onclick="insertEmoji('✅')" class="p-1.5 hover:bg-slate-100 rounded-lg text-lg">✅</button>
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
                        class="w-full px-4 py-2.5 bg-white text-slate-800 placeholder:text-slate-400 text-sm rounded-2xl focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-2xs border border-slate-200/60 transition">
                </div>

                <!-- WhatsApp Green Send Button -->
                <button 
                    type="submit" 
                    id="sendBtn" 
                    class="w-10 h-10 rounded-full bg-[#00a884] hover:bg-[#008f6f] text-white flex items-center justify-center shadow-md transition-all shrink-0 cursor-pointer group active:scale-95">
                    <svg class="w-5 h-5 transform rotate-45 -translate-x-0.5 group-hover:translate-x-0 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </form>
        </div>

    </div>

</div>

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
let pollingInterval = null;

document.addEventListener("DOMContentLoaded", function() {
    scrollToBottom();
    startLivePolling();

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

function toggleEmojiPicker() {
    const tray = document.getElementById('emojiPickerTray');
    tray.classList.toggle('hidden');
}

function insertEmoji(emoji) {
    const input = document.getElementById('chatMessageInput');
    input.value += emoji;
    input.focus();
    document.getElementById('emojiPickerTray').classList.add('hidden');
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

// 🟢 Real-time Instant WhatsApp Message Sending (Zero Page Reload)
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
    if (content) formData.append('content', content);
    if (linkUrl) formData.append('link_url', linkUrl);
    if (hasMedia) formData.append('media', mediaInput.files[0]);

    // Reset input fields immediately for instant chat feel
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
            
            // Update sidebar last message
            const lastMsgEl = document.getElementById('sidebarLastMessage');
            if (lastMsgEl) lastMsgEl.textContent = data.thought.content || 'Media message';
        }
    } catch (err) {
        console.error('Failed to send message:', err);
    }
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
        const res = await fetch(`{{ route('thoughts.messages') }}?after_id=${latestMessageId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (res.ok && data.success && data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => {
                // Avoid duplicating messages already rendered
                if (!document.querySelector(`[data-message-id="${msg.id}"]`)) {
                    renderMessageBubble(msg);
                }
            });

            latestMessageId = data.latest_id;
            scrollToBottom();

            // Update sidebar preview
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

// Render WhatsApp Message HTML dynamically
function renderMessageBubble(msg) {
    const container = document.getElementById('chatMessagesList');
    if (!container) return;

    const isMe = msg.user_id === currentUserId;
    const item = document.createElement('div');
    item.className = `flex ${isMe ? 'justify-end' : 'justify-start'} message-item animate-in fade-in duration-200`;
    item.setAttribute('data-message-id', msg.id);
    item.setAttribute('data-text', (msg.content || '').toLowerCase());

    let mediaHtml = '';
    if (msg.media_url) {
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
    if (msg.link_url) {
        linkHtml = `
            <div class="mt-1.5 p-2 rounded-xl bg-black/5 hover:bg-black/10 transition">
                <a href="${msg.link_url}" target="_blank" class="flex items-center gap-1.5 text-indigo-700 hover:underline text-xs font-bold truncate">
                    <span class="truncate">${msg.link_url}</span>
                </a>
            </div>`;
    }

    const checkmarks = isMe ? `
        <svg class="w-3.5 h-3.5 text-[#53bdeb]" viewBox="0 0 16 15" fill="none">
            <path d="M15.01 3.316l-7.79 7.79-3.21-3.21.71-.71 2.5 2.5 7.08-7.08.71.71zm-4.79 7.79l-.71.71-3.21-3.21.71-.71 2.5 2.5.71-.7zM1.79 7.896l2.5 2.5-.71.71-2.5-2.5.71-.71z" fill="currentColor"/>
        </svg>` : '';

    const senderHeader = !isMe ? `
        <div class="flex items-center justify-between gap-2 mb-1">
            <span class="text-xs font-black text-emerald-700">${msg.user_name}</span>
            <span class="text-[9px] font-bold text-slate-400">${msg.user_role}</span>
        </div>` : '';

    item.innerHTML = `
        <div class="relative max-w-[85%] sm:max-w-[70%] rounded-2xl p-3 shadow-2xs ${isMe ? 'bg-[#d9fdd3] text-[#111b21] rounded-tr-xs' : 'bg-white text-[#111b21] rounded-tl-xs'}">
            ${senderHeader}
            ${mediaHtml}
            ${msg.content ? `<div class="text-[13px] leading-relaxed break-words whitespace-pre-wrap select-text">${msg.content}</div>` : ''}
            ${linkHtml}
            <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-slate-400 select-none">
                <span>${msg.time}</span>
                ${checkmarks}
            </div>
        </div>
    `;

    container.appendChild(item);
}
</script>
@endpush
@endsection
