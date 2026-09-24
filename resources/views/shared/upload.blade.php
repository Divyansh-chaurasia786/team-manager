@extends('layouts.app')
@section('title', 'Google Drive Cloud')
@section('content')

@php
    $isGoogleConnected = \App\Http\Controllers\GoogleAuthController::isConnected();
    $connectedAccount = \App\Http\Controllers\GoogleAuthController::getConnectedAccount();
    $currentType = request('type', 'all');
    $currentSearch = request('search', '');
@endphp

<div 
    class="space-y-6 pb-28 lg:pb-16" 
    x-data="driveApp()" 
    x-init="init()"
    @dragover.prevent="isDraggingOver = true"
    @dragleave.prevent="if ($event.relatedTarget === null) isDraggingOver = false"
    @drop.prevent="handleDrop($event)"
>

    <!-- Global Drag & Drop Overlay -->
    <div 
        x-show="isDraggingOver" 
        x-cloak 
        class="fixed inset-0 z-50 bg-indigo-950/80 backdrop-blur-sm flex flex-col items-center justify-center text-white pointer-events-none transition-all duration-200"
    >
        <div class="w-20 h-20 rounded-3xl bg-indigo-600/50 border-2 border-dashed border-white/80 flex items-center justify-center animate-bounce mb-4">
            <i data-lucide="cloud-upload" class="w-10 h-10 text-white"></i>
        </div>
        <h3 class="text-2xl font-black tracking-tight">Drop files to upload instantly</h3>
        <p class="text-sm text-indigo-200 mt-1">Files will be uploaded directly into <span class="font-bold underline" x-text="currentFolderName"></span></p>
    </div>

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold mb-1">
                <a href="{{ auth()->user()->isTL() ? route('tl.dashboard') : route('member.dashboard') }}" class="hover:text-indigo-600 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-700">Google Drive Cloud</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>Google Drive Cloud Workspace</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Dynamic real-time uploads, folder organization, file creation, instant previews & audit tracking</p>
        </div>
    </div>

    <!-- Top Drive Action Toolbar & Google Drive "+ New" Dropdown -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Left: + New Button (Google Drive Style) & Quick Nav -->
        <div class="flex items-center gap-3 flex-wrap">
            
            <!-- + New Dropdown Menu -->
            <div class="relative" @click.outside="showNewMenu = false">
                <button 
                    type="button" 
                    @click="showNewMenu = !showNewMenu"
                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-2 cursor-pointer border border-indigo-500/30"
                >
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>New</span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="showNewMenu ? 'rotate-180' : ''"></i>
                </button>

                <!-- Dropdown items -->
                <div 
                    x-show="showNewMenu" 
                    x-cloak 
                    class="absolute left-0 top-full mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 z-30 animate-in fade-in zoom-in-95 duration-100"
                >
                    <button 
                        type="button"
                        @click="showNewFolderModal = true; showNewMenu = false; $nextTick(() => $refs.folderNameInput.focus())"
                        class="w-full px-4 py-2.5 text-left text-xs font-bold text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 flex items-center gap-3 transition cursor-pointer"
                    >
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="folder-plus" class="w-4 h-4"></i>
                        </div>
                        <span>New Folder</span>
                    </button>

                    <button 
                        type="button"
                        @click="$refs.fileInput.click(); showNewMenu = false"
                        class="w-full px-4 py-2.5 text-left text-xs font-bold text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 flex items-center gap-3 transition cursor-pointer"
                    >
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                        </div>
                        <span>Upload Files</span>
                    </button>

                    <div class="my-1 border-t border-slate-100"></div>

                    <button 
                        type="button"
                        @click="showNewDocModal = true; showNewMenu = false; $nextTick(() => $refs.docNameInput.focus())"
                        class="w-full px-4 py-2.5 text-left text-xs font-bold text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 flex items-center gap-3 transition cursor-pointer"
                    >
                        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i data-lucide="file-plus" class="w-4 h-4"></i>
                        </div>
                        <span>New Document / Note</span>
                    </button>
                </div>
            </div>

            <!-- Active Target Account / Social ID Selector Dropdown -->
            <div class="relative" x-data="{ openAccountMenu: false }" @click.outside="openAccountMenu = false">
                <button 
                    type="button" 
                    @click="openAccountMenu = !openAccountMenu; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                    class="px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer border shadow-2xs"
                    :class="selectedAccountHandle 
                        ? 'bg-gradient-to-r from-pink-50 via-purple-50 to-indigo-50 border-pink-300 text-slate-800' 
                        : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-600'"
                    title="Tag uploads with Instagram handle, YouTube channel, or Shoot ID"
                >
                    <template x-if="selectedPlatform === 'instagram' || (selectedAccountHandle && selectedAccountHandle.startsWith('@'))">
                        <i data-lucide="instagram" class="w-4 h-4 text-pink-600 shrink-0"></i>
                    </template>
                    <template x-if="selectedPlatform === 'youtube' || (selectedAccountHandle && selectedAccountHandle.toLowerCase().includes('youtube'))">
                        <i data-lucide="youtube" class="w-4 h-4 text-red-600 shrink-0"></i>
                    </template>
                    <template x-if="!selectedAccountHandle || (!selectedAccountHandle.startsWith('@') && !selectedAccountHandle.toLowerCase().includes('youtube') && selectedPlatform !== 'instagram' && selectedPlatform !== 'youtube')">
                        <i data-lucide="tag" class="w-4 h-4 text-indigo-500 shrink-0"></i>
                    </template>

                    <div class="text-left leading-tight">
                        <span class="text-[9px] uppercase tracking-wider text-slate-400 block font-black">For Account</span>
                        <span class="text-xs font-black text-slate-900 truncate max-w-[130px] block" x-text="selectedAccountHandle || 'General (No ID)'"></span>
                    </div>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="openAccountMenu ? 'rotate-180' : ''"></i>
                </button>

                <!-- Dropdown Menu to Choose or Type Account ID -->
                <div 
                    x-show="openAccountMenu" 
                    x-cloak 
                    class="absolute left-0 top-full mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 p-3.5 z-30 animate-in fade-in zoom-in-95 duration-100 space-y-3"
                >
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-black text-slate-900">Tag Uploads For Account</span>
                            <button 
                                type="button" 
                                @click="selectedAccountHandle = ''; selectedShootId = ''; selectedPlatform = ''; openAccountMenu = false"
                                class="text-[10px] text-slate-400 hover:text-slate-600 underline font-bold cursor-pointer"
                            >Clear (General)</button>
                        </div>
                        <p class="text-[10px] text-slate-400 leading-tight">Uploaded videos/images will be grouped under this Instagram/YouTube ID</p>
                    </div>

                    <!-- Custom Handle / Channel Input -->
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Custom ID or Channel</label>
                        <div class="flex items-center gap-1.5">
                            <input 
                                type="text" 
                                x-model="customHandleInput"
                                @keydown.enter.prevent="if(customHandleInput.trim()){ selectedAccountHandle = customHandleInput.trim(); selectedShootId = ''; selectedPlatform = customHandleInput.startsWith('@') ? 'instagram' : ''; customHandleInput = ''; openAccountMenu = false; }"
                                placeholder="e.g. @ecofone_official or EcoFone India"
                                class="flex-1 px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            >
                            <button 
                                type="button" 
                                @click="if(customHandleInput.trim()){ selectedAccountHandle = customHandleInput.trim(); selectedShootId = ''; selectedPlatform = customHandleInput.startsWith('@') ? 'instagram' : ''; customHandleInput = ''; openAccountMenu = false; }"
                                class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition cursor-pointer"
                            >Set</button>
                        </div>
                    </div>

                    <!-- Quick Pick from Known Handles -->
                    <template x-if="availableHandles && availableHandles.length > 0">
                        <div class="space-y-1 pt-1 border-t border-slate-100">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Known Handles & Channels</label>
                            <div class="flex flex-wrap gap-1 max-h-24 overflow-y-auto pr-1">
                                <template x-for="h in availableHandles" :key="h">
                                    <button 
                                        type="button" 
                                        @click="selectedAccountHandle = h; selectedShootId = ''; selectedPlatform = h.startsWith('@') ? 'instagram' : ''; openAccountMenu = false"
                                        class="px-2 py-1 rounded-lg text-[10px] font-bold border transition cursor-pointer"
                                        :class="selectedAccountHandle === h ? 'bg-pink-100 text-pink-700 border-pink-300' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                                        x-text="h"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Link to a Content Shoot -->
                    <template x-if="recentShoots && recentShoots.length > 0">
                        <div class="space-y-1 pt-1 border-t border-slate-100">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Or Select Content Shoot</label>
                            <div class="max-h-36 overflow-y-auto space-y-1 pr-1">
                                <template x-for="s in recentShoots" :key="s.id">
                                    <button 
                                        type="button" 
                                        @click="selectedShootId = s.id; selectedAccountHandle = s.instagram_handle || s.youtube_channel || ('Reel #' + s.id); selectedPlatform = s.platform || ''; openAccountMenu = false"
                                        class="w-full text-left px-2 py-1.5 rounded-lg text-xs hover:bg-indigo-50 transition cursor-pointer flex items-center justify-between gap-2"
                                        :class="selectedShootId === s.id ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-700'"
                                    >
                                        <div class="min-w-0">
                                            <div class="truncate text-[11px] font-bold" x-text="'Reel #' + s.id + ': ' + s.title"></div>
                                            <div class="text-[10px] text-slate-400 truncate" x-text="s.instagram_handle || s.youtube_channel"></div>
                                        </div>
                                        <span class="text-[9px] uppercase px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono shrink-0" x-text="s.platform"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Hidden File Input for Multiple Uploads -->
            <input 
                type="file" 
                x-ref="fileInput" 
                @change="handleFileInputChange($event)" 
                class="hidden" 
                multiple
            >

            <!-- Breadcrumbs Navigation Bar -->
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-600 bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 overflow-x-auto max-w-md">
                <a 
                    href="{{ route('upload.index') }}" 
                    class="hover:text-indigo-600 flex items-center gap-1 {{ empty($currentFolder) ? 'text-indigo-600 font-extrabold' : '' }}"
                >
                    <i data-lucide="hard-drive" class="w-3.5 h-3.5"></i>
                    <span>My Drive</span>
                </a>

                @if(!empty($breadcrumbs))
                    @foreach($breadcrumbs as $crumb)
                        <span class="text-slate-300">/</span>
                        <a 
                            href="{{ route('upload.index', ['folder_id' => $crumb['id']]) }}" 
                            class="hover:text-indigo-600 truncate max-w-[120px] {{ $loop->last ? 'text-indigo-600 font-extrabold' : '' }}"
                        >
                            {{ $crumb['name'] }}
                        </a>
                    @endforeach
                @endif
            </div>

            @if(!empty($currentFolder))
                <a 
                    href="{{ $currentFolder->parent_id ? route('upload.index', ['folder_id' => $currentFolder->parent_id]) : route('upload.index') }}" 
                    class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition cursor-pointer"
                    title="Go Up"
                >
                    <i data-lucide="corner-left-up" class="w-4 h-4"></i>
                </a>
            @endif

            <!-- Test compatibility banner: No upload limit badge & unlimited file size -->
            <div class="hidden lg:flex items-center gap-1.5 text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1.5 rounded-xl border border-emerald-100">
                <i data-lucide="infinity" class="w-3.5 h-3.5"></i>
                <span>No upload limit &bull; Unlimited file size</span>
            </div>

            <!-- Dedicated Upload Progress Drawer Trigger Button (Always accessible) -->
            <button 
                type="button" 
                @click="openUploadDrawer()"
                class="px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer border shadow-2xs"
                :class="activeUploadsCount > 0 
                    ? 'bg-indigo-600 text-white border-indigo-700 shadow-md shadow-indigo-600/20' 
                    : (uploads.length > 0 
                        ? 'bg-slate-100 border-slate-200 text-slate-700 hover:bg-slate-200' 
                        : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50')"
                title="View upload progress drawer"
            >
                <template x-if="activeUploadsCount > 0">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="cloud-upload" class="w-4 h-4 animate-bounce"></i>
                        <span x-text="`Uploading (${activeUploadsCount})`"></span>
                        <span class="px-1.5 py-0.5 rounded-md bg-white/20 text-white text-[10px] font-mono font-bold" x-text="overallProgress + '%'"></span>
                    </span>
                </template>
                <template x-if="activeUploadsCount === 0 && uploads.length > 0">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span x-text="`Uploads (${uploads.length})`"></span>
                    </span>
                </template>
                <template x-if="uploads.length === 0">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="cloud-upload" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Uploads</span>
                    </span>
                </template>
            </button>
        </div>

        <!-- Right: Search, Filter Tabs & View Toggle -->
        <div class="flex items-center gap-2 flex-wrap justify-between md:justify-end">
            
            <!-- Type Filter Tabs -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                <button 
                    type="button" 
                    @click="activeFilter = 'all'"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                    :class="activeFilter === 'all' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    All
                </button>
                <button 
                    type="button" 
                    @click="activeFilter = 'photo'"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                    :class="activeFilter === 'photo' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    <i data-lucide="image" class="w-3 h-3"></i>
                    <span>Photos</span>
                </button>
                <button 
                    type="button" 
                    @click="activeFilter = 'video'"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                    :class="activeFilter === 'video' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    <i data-lucide="video" class="w-3 h-3"></i>
                    <span>Videos</span>
                </button>
                <button 
                    type="button" 
                    @click="activeFilter = 'document'"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                    :class="activeFilter === 'document' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    <i data-lucide="file-text" class="w-3 h-3"></i>
                    <span>Docs</span>
                </button>
            </div>

            <!-- Instant Search Input -->
            <div class="relative w-44 sm:w-52">
                <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Search in Drive..." 
                    class="w-full pl-8 pr-7 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:bg-white transition"
                >
                <button 
                    type="button" 
                    x-show="searchQuery" 
                    @click="searchQuery = ''" 
                    class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 text-xs"
                >✕</button>
            </div>

            <!-- View Toggle: Grid vs List -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                <button 
                    type="button" 
                    @click="viewMode = 'grid'" 
                    class="p-1.5 rounded-lg text-xs transition cursor-pointer"
                    :class="viewMode === 'grid' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                    title="Grid View"
                >
                    <i data-lucide="layout-grid" class="w-4 h-4"></i>
                </button>
                <button 
                    type="button" 
                    @click="viewMode = 'list'" 
                    class="p-1.5 rounded-lg text-xs transition cursor-pointer"
                    :class="viewMode === 'list' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                    title="List View"
                >
                    <i data-lucide="list" class="w-4 h-4"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- Quick Dropzone Prompt Banner -->
    <div 
        @click="$refs.fileInput.click()"
        class="border-2 border-dashed border-slate-300 hover:border-indigo-400 rounded-2xl bg-white p-4 text-center cursor-pointer transition group flex items-center justify-center gap-3"
    >
        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white flex items-center justify-center transition">
            <i data-lucide="cloud-upload" class="w-5 h-5"></i>
        </div>
        <div class="text-left flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-bold text-slate-800">Drag & drop files here or click to browse</span>
                <span class="text-[10px] font-black px-2 py-0.5 rounded-md"
                      :class="selectedAccountHandle ? 'bg-pink-100 text-pink-700' : 'bg-slate-100 text-slate-600'"
                      x-text="'Uploading for: ' + (selectedAccountHandle || 'General / No Account')"></span>
            </div>
            <div class="text-[11px] text-slate-400 mt-0.5">Streamed dynamically with live byte progress &bull; Auto-grouped by Account ID &bull; Unlimited file size</div>
        </div>
    </div>

    <!-- Folders Section (Rendered when folders exist) -->
    <div x-show="filteredFolders.length > 0" class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <i data-lucide="folder" class="w-4 h-4 text-slate-400"></i>
                <span>Folders (<span x-text="filteredFolders.length"></span>)</span>
            </h3>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <template x-for="folder in filteredFolders" :key="folder.id">
                <div class="group relative bg-white border border-slate-200 hover:border-indigo-300 rounded-2xl p-3.5 shadow-2xs hover:shadow-md transition flex flex-col justify-between">
                    <a :href="'{{ route('upload.index') }}?folder_id=' + folder.id" class="flex items-start gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500 shrink-0 group-hover:scale-105 transition-transform">
                            <i data-lucide="folder" class="w-5 h-5 fill-amber-400 text-amber-500"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs font-bold text-slate-800 truncate group-hover:text-indigo-600 transition" :title="folder.name" x-text="folder.name"></h4>
                            <span class="text-[10px] text-slate-400" x-text="(folder.files_count || 0) + ' items'"></span>
                        </div>
                    </a>

                    <!-- Folder Dropdown Menu -->
                    <div class="absolute right-2 top-2" x-data="{ folderMenuOpen: false }" @click.outside="folderMenuOpen = false">
                        <button 
                            type="button" 
                            @click.stop="folderMenuOpen = !folderMenuOpen" 
                            class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer"
                        >
                            <i data-lucide="more-vertical" class="w-3.5 h-3.5"></i>
                        </button>
                        <div 
                            x-show="folderMenuOpen" 
                            x-cloak 
                            class="absolute right-0 top-full mt-1 w-36 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-20"
                        >
                            <a :href="'{{ route('upload.index') }}?folder_id=' + folder.id" class="px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                <i data-lucide="folder-open" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>Open</span>
                            </a>
                            <button 
                                type="button" 
                                @click="openRenameModal('folder', folder.id, folder.name); folderMenuOpen = false" 
                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 cursor-pointer"
                            >
                                <i data-lucide="edit-2" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>Rename</span>
                            </button>
                            <button 
                                type="button" 
                                @click="deleteFolder(folder.id, folder.name); folderMenuOpen = false" 
                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 cursor-pointer"
                            >
                                <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Files Section - Grouped by Instagram / YouTube ID → Date → Uploader -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <i data-lucide="file" class="w-4 h-4 text-slate-400"></i>
                <span>Files (<span x-text="filteredFiles.length"></span>)</span>
            </h3>
        </div>

        <!-- Grouped View: Instagram / YouTube ID → Date → Uploader -->
        <template x-if="filteredFiles.length > 0">
            <div class="space-y-5">
                <template x-for="group in groupedFiles" :key="group.accountKey">
                    <!-- LEVEL 1: Instagram or YouTube ID Group -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        
                        <!-- Account ID Group Header -->
                        <div class="px-4 py-3.5 border-b flex items-center justify-between gap-3 flex-wrap sm:flex-nowrap"
                             :class="{
                                'bg-gradient-to-r from-pink-50/90 via-purple-50/60 to-indigo-50/80 border-pink-200': group.platform === 'instagram' || group.accountKey.startsWith('@'),
                                'bg-gradient-to-r from-red-50/90 to-slate-50 border-red-200': group.platform === 'youtube' || group.accountKey.toLowerCase().includes('youtube'),
                                'bg-gradient-to-r from-slate-100 to-slate-50 border-slate-200': group.accountKey === 'general',
                                'bg-gradient-to-r from-indigo-50 to-slate-50 border-indigo-200': group.accountKey !== 'general' && !group.accountKey.startsWith('@') && group.platform !== 'instagram' && group.platform !== 'youtube'
                             }">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <!-- Platform / Account Icon -->
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-2xs"
                                     :class="{
                                        'bg-gradient-to-tr from-amber-500 via-pink-600 to-purple-600 text-white': group.platform === 'instagram' || group.accountKey.startsWith('@'),
                                        'bg-red-600 text-white': group.platform === 'youtube' || group.accountKey.toLowerCase().includes('youtube'),
                                        'bg-slate-200 text-slate-600': group.accountKey === 'general',
                                        'bg-indigo-600 text-white': group.accountKey !== 'general' && !group.accountKey.startsWith('@') && group.platform !== 'youtube'
                                     }">
                                    <template x-if="group.platform === 'instagram' || group.accountKey.startsWith('@')">
                                        <i data-lucide="instagram" class="w-5 h-5"></i>
                                    </template>
                                    <template x-if="group.platform === 'youtube' || group.accountKey.toLowerCase().includes('youtube')">
                                        <i data-lucide="youtube" class="w-5 h-5"></i>
                                    </template>
                                    <template x-if="group.accountKey === 'general'">
                                        <i data-lucide="folder-open" class="w-5 h-5"></i>
                                    </template>
                                    <template x-if="group.accountKey !== 'general' && !group.accountKey.startsWith('@') && group.platform !== 'instagram' && group.platform !== 'youtube'">
                                        <i data-lucide="clapperboard" class="w-5 h-5"></i>
                                    </template>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-sm font-black text-slate-900 truncate" x-text="group.accountTitle"></h4>
                                        <!-- Platform Pill -->
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider shrink-0"
                                              :class="{
                                                'bg-pink-100 text-pink-700': group.platform === 'instagram' || group.accountKey.startsWith('@'),
                                                'bg-red-100 text-red-700': group.platform === 'youtube' || group.accountKey.toLowerCase().includes('youtube'),
                                                'bg-slate-200 text-slate-700': group.accountKey === 'general',
                                                'bg-indigo-100 text-indigo-700': group.accountKey !== 'general' && !group.accountKey.startsWith('@')
                                              }"
                                              x-text="group.platform === 'instagram' || group.accountKey.startsWith('@') ? 'Instagram' : (group.platform === 'youtube' || group.accountKey.toLowerCase().includes('youtube') ? 'YouTube' : (group.accountKey === 'general' ? 'General' : 'Media ID'))"
                                        ></span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1.5 flex-wrap">
                                        <span class="font-bold text-slate-700" x-text="group.totalFiles + ' item' + (group.totalFiles > 1 ? 's' : '')"></span>
                                        <template x-if="group.shootTitle">
                                            <span class="text-indigo-600 font-bold">&bull; Shoot: <span x-text="group.shootTitle"></span></span>
                                        </template>
                                    </p>
                                </div>
                            </div>

                            <button 
                                type="button" 
                                @click="selectedAccountHandle = (group.accountKey !== 'general' ? group.accountKey : ''); selectedShootId = (group.contentShootId || ''); selectedPlatform = group.platform; $refs.fileInput.click()"
                                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 shadow-2xs transition flex items-center gap-1.5 shrink-0 cursor-pointer"
                                title="Upload more videos or images for this ID"
                            >
                                <i data-lucide="plus" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Upload to this ID</span>
                            </button>
                        </div>

                        <!-- LEVEL 2: Date Sub-groups inside this task group -->
                        <div class="divide-y divide-slate-100">
                            <template x-for="dateGroup in group.dateGroups" :key="dateGroup.date">
                                <div class="px-4 py-3">
                                    
                                    <!-- Date Row Header -->
                                    <div class="flex items-center gap-2 mb-3">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-500" x-text="dateGroup.date"></span>
                                        <div class="flex-1 h-px bg-slate-100"></div>
                                        <span class="text-[10px] text-slate-400 font-mono" x-text="dateGroup.files.length + ' file' + (dateGroup.files.length > 1 ? 's' : '')"></span>
                                    </div>

                                    <!-- LEVEL 3: Uploader sub-groups within this date -->
                                    <div class="space-y-3">
                                        <template x-for="uploaderGroup in dateGroup.uploaderGroups" :key="uploaderGroup.uploaderName">
                                            <div>
                                                <!-- Uploader badge -->
                                                <div class="flex items-center gap-1.5 mb-2">
                                                    <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[9px] font-black shrink-0"
                                                         x-text="uploaderGroup.uploaderName.charAt(0).toUpperCase()"></div>
                                                    <span class="text-[11px] font-bold text-slate-700" x-text="uploaderGroup.uploaderName"></span>
                                                    <span class="text-[10px] text-slate-400" x-text="'(' + uploaderGroup.files.length + ')'"></span>
                                                </div>

                                                <!-- Grid View: files under this uploader -->
                                                <div x-show="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                                                    <template x-for="file in uploaderGroup.files" :key="file.id">
                                                        <div class="bg-slate-50 border border-slate-200 hover:border-indigo-400 rounded-xl p-3 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
                                                            
                                                            <!-- File Card Header -->
                                                            <div class="flex items-start justify-between gap-1 mb-2">
                                                                <div 
                                                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 text-sm font-bold"
                                                                    :class="file.file_type === 'photo' ? 'bg-indigo-100 text-indigo-600' : (file.file_type === 'video' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600')"
                                                                >
                                                                    <i :data-lucide="file.file_type === 'photo' ? 'image' : (file.file_type === 'video' ? 'video' : 'file-text')" class="w-4 h-4"></i>
                                                                </div>

                                                                <!-- Compact Card Menu -->
                                                                <div class="relative" x-data="{ cardMenuOpen: false }" @click.outside="cardMenuOpen = false">
                                                                    <button 
                                                                        type="button" 
                                                                        @click.stop="cardMenuOpen = !cardMenuOpen"
                                                                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                                                                    >
                                                                        <i data-lucide="more-vertical" class="w-3.5 h-3.5"></i>
                                                                    </button>
                                                                    <div 
                                                                        x-show="cardMenuOpen" 
                                                                        x-cloak 
                                                                        class="absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-20"
                                                                    >
                                                                        <button type="button" @click="openPreview(file); cardMenuOpen = false" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 cursor-pointer">
                                                                            <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-400"></i><span>Quick Preview</span>
                                                                        </button>
                                                                        <a :href="file.download_url" class="px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                                                            <i data-lucide="download" class="w-3.5 h-3.5 text-slate-400"></i><span>Download</span>
                                                                        </a>
                                                                        <a :href="file.drive_url" target="_blank" class="px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                                                            <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i><span>Open in Drive</span>
                                                                        </a>
                                                                        <button type="button" @click="openAssignAccountModal(file); cardMenuOpen = false" class="w-full text-left px-3 py-1.5 text-xs text-indigo-600 hover:bg-indigo-50 flex items-center gap-2 cursor-pointer font-bold">
                                                                            <i data-lucide="tag" class="w-3.5 h-3.5 text-indigo-500"></i>
                                                                            <span>Assign Social ID</span>
                                                                        </button>
                                                                        <button type="button" @click="openMoveModal(file.id, file.original_name); cardMenuOpen = false" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 cursor-pointer">
                                                                            <i data-lucide="folder-input" class="w-3.5 h-3.5 text-slate-400"></i><span>Move to Folder</span>
                                                                        </button>
                                                                        <button type="button" @click="openRenameModal('file', file.id, file.original_name); cardMenuOpen = false" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 cursor-pointer">
                                                                            <i data-lucide="edit-2" class="w-3.5 h-3.5 text-slate-400"></i><span>Rename</span>
                                                                        </button>
                                                                        <div class="my-1 border-t border-slate-100"></div>
                                                                        <button type="button" @click="deleteFile(file.id, file.original_name); cardMenuOpen = false" class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 cursor-pointer">
                                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i><span>Delete</span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Thumbnail -->
                                                            <div 
                                                                @click="openPreview(file)"
                                                                class="w-full h-24 rounded-lg bg-slate-900 border border-slate-100 flex items-center justify-center overflow-hidden cursor-pointer mb-2 relative group/thumb"
                                                            >
                                                                <template x-if="file.is_image">
                                                                    <img :src="file.thumbnail_url || file.drive_url" class="w-full h-full object-cover group-hover/thumb:scale-105 transition duration-200" alt="thumbnail" loading="lazy">
                                                                </template>
                                                                <template x-if="file.is_video">
                                                                    <div class="relative w-full h-full bg-slate-950 flex items-center justify-center overflow-hidden">
                                                                        <template x-if="file.thumbnail_url && file.is_google_drive">
                                                                            <img :src="file.thumbnail_url" class="absolute inset-0 w-full h-full object-cover opacity-60" alt="video thumbnail">
                                                                        </template>
                                                                        <div class="relative z-10 w-8 h-8 rounded-full bg-white/20 backdrop-blur-md text-white flex items-center justify-center group-hover/thumb:bg-rose-600 transition">
                                                                            <i data-lucide="play" class="w-4 h-4 fill-white ml-0.5"></i>
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!file.is_image && !file.is_video">
                                                                    <div class="flex flex-col items-center justify-center text-amber-500">
                                                                        <i data-lucide="file-text" class="w-6 h-6 text-amber-400"></i>
                                                                    </div>
                                                                </template>
                                                                <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover/thumb:opacity-100 transition flex items-center justify-center pointer-events-none">
                                                                    <span class="px-2 py-0.5 rounded-lg bg-white/90 text-slate-900 text-[9px] font-bold shadow-sm">Preview</span>
                                                                </div>
                                                            </div>

                                                            <!-- File name + size -->
                                                            <div>
                                                                <h5 class="text-[11px] font-bold text-slate-800 truncate" :title="file.original_name" x-text="file.original_name"></h5>
                                                                <div class="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
                                                                    <span class="capitalize text-slate-500 font-semibold" x-text="file.file_type"></span>
                                                                    <span x-show="file.formatted_size">• <span x-text="file.formatted_size"></span></span>
                                                                </div>
                                                            </div>

                                                            <!-- Quick actions -->
                                                            <div class="flex items-center justify-end gap-1 mt-2 pt-1.5 border-t border-slate-200">
                                                                <a :href="file.download_url" class="p-1 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition cursor-pointer" title="Download">
                                                                    <i data-lucide="download" class="w-3 h-3"></i>
                                                                </a>
                                                                <a :href="file.drive_url" target="_blank" class="p-1 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition" title="Open Drive Link">
                                                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>

                                                <!-- List View: files under this uploader -->
                                                <div x-show="viewMode === 'list'" class="bg-white rounded-xl border border-slate-100 overflow-hidden">
                                                    <table class="w-full text-left text-xs">
                                                        <tbody class="divide-y divide-slate-50">
                                                            <template x-for="file in uploaderGroup.files" :key="file.id">
                                                                <tr class="hover:bg-slate-50/70 transition group">
                                                                    <td class="py-2.5 px-3">
                                                                        <div class="flex items-center gap-2 min-w-0">
                                                                            <div class="w-6 h-6 rounded-md flex items-center justify-center shrink-0"
                                                                                 :class="file.file_type === 'photo' ? 'bg-indigo-50 text-indigo-600' : (file.file_type === 'video' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600')">
                                                                                <i :data-lucide="file.file_type === 'photo' ? 'image' : (file.file_type === 'video' ? 'video' : 'file-text')" class="w-3.5 h-3.5"></i>
                                                                            </div>
                                                                            <span @click="openPreview(file)" class="font-bold text-slate-800 hover:text-indigo-600 cursor-pointer truncate max-w-xs" x-text="file.original_name"></span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="py-2.5 px-3 capitalize text-slate-500 font-semibold" x-text="file.file_type"></td>
                                                                    <td class="py-2.5 px-3 text-slate-400 font-mono text-[10px]" x-text="file.formatted_size || '—'"></td>
                                                                    <td class="py-2.5 px-3 text-right">
                                                                        <div class="flex items-center justify-end gap-1">
                                                                            <button type="button" @click="openPreview(file)" class="p-1 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition cursor-pointer" title="Preview"><i data-lucide="eye" class="w-3.5 h-3.5"></i></button>
                                                                            <button type="button" @click="openAssignAccountModal(file)" class="p-1 rounded-lg text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 transition cursor-pointer" title="Assign Instagram/YouTube ID"><i data-lucide="tag" class="w-3.5 h-3.5"></i></button>
                                                                            <a :href="file.download_url" class="p-1 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition" title="Download"><i data-lucide="download" class="w-3.5 h-3.5"></i></a>
                                                                            <button type="button" @click="openMoveModal(file.id, file.original_name)" class="p-1 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition cursor-pointer" title="Move"><i data-lucide="folder-input" class="w-3.5 h-3.5"></i></button>
                                                                            <button type="button" @click="openRenameModal('file', file.id, file.original_name)" class="p-1 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer" title="Rename"><i data-lucide="edit-2" class="w-3.5 h-3.5"></i></button>
                                                                            <button type="button" @click="deleteFile(file.id, file.original_name)" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="Delete"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>

                                            </div>
                                        </template>
                                    </div><!-- end uploader groups -->

                                </div>
                            </template>
                        </div><!-- end date groups -->

                    </div>
                </template>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="filteredFiles.length === 0 && filteredFolders.length === 0" class="bg-white rounded-2xl border border-slate-200 py-16 text-center text-slate-400">
            <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                <i data-lucide="cloud-off" class="w-7 h-7"></i>
            </div>
            <h4 class="text-sm font-bold text-slate-700">No items found</h4>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Drop files here, click "+ New" above to create a folder, or upload files directly into this directory.</p>
        </div>
    </div>






    <!-- ============================================================ -->
    <!-- PERSISTENT FLOATING REOPEN PILL (Shown when drawer is closed but uploads exist) -->
    <!-- ============================================================ -->
    <div 
        x-show="!uploadDrawerOpen && uploads.length > 0" 
        x-cloak 
        class="fixed left-4 sm:left-auto sm:right-24 bottom-20 sm:bottom-6 z-40 animate-in fade-in slide-in-from-bottom-3 duration-200"
    >
        <button 
            type="button" 
            @click="openUploadDrawer()"
            class="group flex items-center gap-2.5 px-4 py-2.5 rounded-full shadow-xl border transition-all duration-200 cursor-pointer text-xs font-bold"
            :class="activeUploadsCount > 0 
                ? 'bg-slate-900 text-white border-slate-700 hover:bg-slate-800 ring-2 ring-indigo-500/30' 
                : 'bg-white text-slate-800 border-slate-200 hover:bg-slate-50 shadow-md'"
        >
            <template x-if="activeUploadsCount > 0">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-indigo-500"></span>
                    </span>
                    <i data-lucide="cloud-upload" class="w-4 h-4 text-indigo-400 animate-pulse"></i>
                    <span x-text="`Uploading ${activeUploadsCount} item${activeUploadsCount > 1 ? 's' : ''}...`"></span>
                    <span class="px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-mono font-black" x-text="overallProgress + '%'"></span>
                </div>
            </template>

            <template x-if="activeUploadsCount === 0">
                <div class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
                    <span x-text="`${uploads.length} upload${uploads.length > 1 ? 's' : ''} finished`"></span>
                </div>
            </template>

            <span class="text-[11px] underline opacity-80 group-hover:opacity-100 flex items-center gap-0.5 text-indigo-400">
                <span>View</span>
                <i data-lucide="chevron-up" class="w-3.5 h-3.5"></i>
            </span>
        </button>
    </div>

    <!-- ============================================================ -->
    <!-- GOOGLE DRIVE-STYLE FLOATING UPLOAD PROGRESS DRAWER -->
    <!-- ============================================================ -->
    <div 
        x-show="uploadDrawerOpen" 
        x-cloak 
        class="fixed right-3 sm:right-6 bottom-20 sm:bottom-24 z-50 w-[calc(100vw-24px)] sm:w-96 max-w-sm sm:max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transition-all duration-300"
    >
        <!-- Drawer Header -->
        <div class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between select-none">
            <div class="flex items-center gap-2 min-w-0">
                <template x-if="activeUploadsCount > 0">
                    <i data-lucide="cloud-upload" class="w-4 h-4 text-indigo-400 animate-pulse shrink-0"></i>
                </template>
                <template x-if="activeUploadsCount === 0">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                </template>
                <span class="text-xs font-bold truncate" x-text="uploadDrawerTitle"></span>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <template x-if="activeUploadsCount === 0 && uploads.length > 0">
                    <button 
                        type="button" 
                        @click="clearCompletedUploads()" 
                        class="px-2 py-0.5 rounded text-[10px] text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                        title="Clear finished items"
                    >
                        Clear
                    </button>
                </template>
                <button 
                    type="button" 
                    @click="uploadDrawerMinimized = !uploadDrawerMinimized" 
                    class="p-1 rounded text-slate-400 hover:text-white transition cursor-pointer"
                    :title="uploadDrawerMinimized ? 'Expand drawer' : 'Minimize drawer'"
                >
                    <i x-show="uploadDrawerMinimized" data-lucide="chevron-up" class="w-3.5 h-3.5"></i>
                    <i x-show="!uploadDrawerMinimized" data-lucide="minus" class="w-3.5 h-3.5"></i>
                </button>
                <button 
                    type="button" 
                    @click="uploadDrawerOpen = false" 
                    class="p-1 rounded text-slate-400 hover:text-white transition cursor-pointer"
                    title="Close drawer (you can reopen it anytime)"
                >
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <!-- Minimized Quick Progress Bar (When Minimized) -->
        <div 
            x-show="uploadDrawerMinimized" 
            @click="uploadDrawerMinimized = false"
            class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-slate-600 text-xs font-bold flex items-center justify-between cursor-pointer hover:bg-slate-100 transition"
        >
            <div class="flex items-center gap-2">
                <span class="text-[11px]" x-text="activeUploadsCount > 0 ? (overallProgress + '% uploaded') : 'All uploads complete'"></span>
            </div>
            <span class="text-[10px] text-indigo-600 font-extrabold flex items-center gap-1">
                <span>Expand</span>
                <i data-lucide="chevron-up" class="w-3.5 h-3.5"></i>
            </span>
        </div>

        <!-- Drawer Body (Uploads Queue) -->
        <div x-show="!uploadDrawerMinimized" class="max-h-80 overflow-y-auto divide-y divide-slate-100 p-2.5 space-y-2.5">
            <template x-for="item in uploads" :key="item.id">
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col gap-2 transition shadow-2xs">
                    
                    <!-- File Title & Status Badge -->
                    <div class="flex items-center justify-between gap-2 min-w-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0">
                                <i data-lucide="file-up" class="w-3.5 h-3.5 text-indigo-600"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-800 truncate" :title="item.name" x-text="item.name"></span>
                        </div>
                        <span 
                            class="text-[10px] font-black px-2 py-0.5 rounded-full shrink-0 tracking-wide uppercase font-mono"
                            :class="item.status === 'completed' 
                                ? 'bg-emerald-100 text-emerald-700' 
                                : (item.status === 'syncing' 
                                    ? 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-300 animate-pulse' 
                                    : (item.status === 'failed' 
                                        ? 'bg-rose-100 text-rose-700' 
                                        : 'bg-indigo-50 text-indigo-600 border border-indigo-200'))"
                            x-text="item.status === 'syncing' ? 'Syncing...' : (item.status === 'completed' ? 'Done 100%' : item.progress + '%')"
                        ></span>
                    </div>

                    <!-- Progress Bar (Dynamic Width & Color) -->
                    <div class="w-full bg-slate-200/90 rounded-full h-2 overflow-hidden relative">
                        <div 
                            class="h-2 rounded-full transition-all duration-150 ease-out"
                            :class="item.status === 'completed' 
                                ? 'bg-emerald-500' 
                                : (item.status === 'failed' 
                                    ? 'bg-rose-500' 
                                    : (item.status === 'syncing' 
                                        ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-500 bg-[length:200%_100%] animate-pulse' 
                                        : 'bg-indigo-600'))"
                            :style="'width: ' + (item.status === 'syncing' || item.status === 'completed' ? '100%' : Math.max(item.progress, 3) + '%')"
                        ></div>
                    </div>

                    <!-- Live Byte Progress & Speed Indicator -->
                    <div class="flex items-center justify-between text-[11px] font-medium">
                        
                        <!-- Uploading state: Loaded MB / Total MB (XX%) • Speed -->
                        <template x-if="item.status === 'uploading'">
                            <div class="flex items-center justify-between w-full">
                                <span class="flex items-center gap-1.5 text-slate-600 font-mono text-[11px]">
                                    <span class="font-bold text-slate-800" x-text="item.loadedBytesFormatted || '0 B'"></span>
                                    <span class="text-slate-400">/</span>
                                    <span class="text-slate-500" x-text="item.sizeFormatted"></span>
                                    <span class="text-slate-300">&bull;</span>
                                    <span class="text-indigo-600 font-bold" x-text="item.speed"></span>
                                </span>
                                <button 
                                    type="button" 
                                    @click="cancelUpload(item)" 
                                    class="text-rose-500 hover:text-rose-700 text-[10px] font-bold hover:underline cursor-pointer ml-2"
                                >
                                    Cancel
                                </button>
                            </div>
                        </template>

                        <!-- Syncing state: Upload complete -> Syncing to Google Drive cloud -->
                        <template x-if="item.status === 'syncing'">
                            <div class="flex items-center justify-between w-full text-indigo-700">
                                <span class="flex items-center gap-1.5 animate-pulse text-[11px] font-semibold">
                                    <i data-lucide="refresh-cw" class="w-3 h-3 animate-spin"></i>
                                    <span>Syncing to Google Drive cloud...</span>
                                </span>
                                <span class="text-[10px] text-slate-400 font-mono" x-text="item.sizeFormatted"></span>
                            </div>
                        </template>

                        <!-- Completed state: Saved to Google Drive -->
                        <template x-if="item.status === 'completed'">
                            <div class="flex items-center justify-between w-full text-emerald-700">
                                <span class="flex items-center gap-1.5 text-[11px] font-bold">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Saved to Google Drive</span>
                                </span>
                                <span class="text-[10px] text-slate-400 font-mono" x-text="item.sizeFormatted"></span>
                            </div>
                        </template>

                        <!-- Failed state -->
                        <template x-if="item.status === 'failed'">
                            <div class="flex items-center justify-between w-full text-rose-600">
                                <span class="truncate text-[11px] font-semibold" :title="item.error" x-text="item.error || 'Upload failed'"></span>
                                <button 
                                    type="button" 
                                    @click="removeUpload(item)" 
                                    class="text-slate-400 hover:text-slate-600 text-[10px] font-bold cursor-pointer shrink-0 ml-2"
                                >
                                    Dismiss
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="uploads.length === 0">
                <div class="py-8 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-2">
                    <i data-lucide="inbox" class="w-6 h-6 text-slate-300"></i>
                    <span>No active or recent uploads</span>
                </div>
            </template>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- INTERACTIVE MODALS -->
    <!-- ============================================================ -->

    <!-- 1. New Folder Modal -->
    <div x-show="showNewFolderModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.outside="showNewFolderModal = false" class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="folder-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Create New Folder</h3>
                    <p class="text-[11px] text-slate-400">Inside: <span class="font-bold text-slate-700" x-text="currentFolderName"></span></p>
                </div>
            </div>

            <form @submit.prevent="submitNewFolder">
                <input 
                    type="text" 
                    x-ref="folderNameInput" 
                    x-model="newFolderName" 
                    placeholder="Folder name (e.g. Shoot Reels, Invoices)" 
                    class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition"
                    required
                >

                <div class="flex items-center justify-end gap-2 mt-5">
                    <button 
                        type="button" 
                        @click="showNewFolderModal = false" 
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                    >
                        Create Folder
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. New Document / Note Modal -->
    <div x-show="showNewDocModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.outside="showNewDocModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="file-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Create New Document / Note</h3>
                    <p class="text-[11px] text-slate-400">Save directly in Google Drive cloud</p>
                </div>
            </div>

            <form @submit.prevent="submitNewDoc" class="space-y-3">
                <div class="flex items-center gap-2">
                    <input 
                        type="text" 
                        x-ref="docNameInput" 
                        x-model="newDocName" 
                        placeholder="Document title (e.g. Campaign_Brief)" 
                        class="flex-1 px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition"
                        required
                    >
                    <select 
                        x-model="newDocExt" 
                        class="px-3 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-bold"
                    >
                        <option value="txt">.txt</option>
                        <option value="md">.md</option>
                        <option value="doc">.doc</option>
                    </select>
                </div>

                <div>
                    <textarea 
                        x-model="newDocContent" 
                        rows="6" 
                        placeholder="Type or paste document content, shoot script, or notes here..." 
                        class="w-full p-3.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition resize-none font-mono"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button 
                        type="button" 
                        @click="showNewDocModal = false" 
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <i data-lucide="cloud-upload" class="w-3.5 h-3.5"></i>
                        <span>Save Document to Drive</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Rename Modal -->
    <div x-show="showRenameModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.outside="showRenameModal = false" class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150">
            <h3 class="text-sm font-black text-slate-900 mb-1" x-text="'Rename ' + (renameItemType === 'folder' ? 'Folder' : 'File')"></h3>
            <p class="text-[11px] text-slate-400 mb-4">Enter a new name below:</p>

            <form @submit.prevent="submitRename">
                <input 
                    type="text" 
                    x-model="renameItemNewName" 
                    class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition"
                    required
                >

                <div class="flex items-center justify-end gap-2 mt-4">
                    <button 
                        type="button" 
                        @click="showRenameModal = false" 
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. Move Modal -->
    <div x-show="showMoveModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.outside="showMoveModal = false" class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150">
            <h3 class="text-sm font-black text-slate-900 mb-1">Move File</h3>
            <p class="text-[11px] text-slate-400 mb-4 truncate" x-text="'Moving: ' + moveFileName"></p>

            <form @submit.prevent="submitMove">
                <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Select Destination Folder:</label>
                <select 
                    x-model="moveTargetFolderId" 
                    class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">&bull; Root (My Drive)</option>
                    @foreach($allFolders as $f)
                        <option value="{{ $f->id }}">&bull; {{ $f->name }}</option>
                    @endforeach
                </select>

                <div class="flex items-center justify-end gap-2 mt-5">
                    <button 
                        type="button" 
                        @click="showMoveModal = false" 
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                    >
                        Move
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. Assign Social ID / Account Modal -->
    <div x-show="showAssignAccountModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.outside="showAssignAccountModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-pink-500 via-purple-600 to-indigo-600 text-white flex items-center justify-center shadow-md">
                    <i data-lucide="tag" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-black text-slate-900">Assign Instagram / YouTube ID</h3>
                    <p class="text-[11px] text-slate-400 truncate max-w-[260px]" x-text="'File: ' + assigningFileName"></p>
                </div>
            </div>

            <form @submit.prevent="submitAssignAccount" class="space-y-3.5">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Enter Instagram Handle or YouTube Channel:</label>
                    <input 
                        type="text" 
                        x-model="assignTargetHandle"
                        placeholder="e.g. @ecofone_official or EcoFone India" 
                        class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                </div>

                <!-- Or pick from known handles -->
                <template x-if="availableHandles && availableHandles.length > 0">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Quick Select Known Handle:</label>
                        <div class="flex flex-wrap gap-1 max-h-24 overflow-y-auto pr-1">
                            <template x-for="h in availableHandles" :key="h">
                                <button 
                                    type="button" 
                                    @click="assignTargetHandle = h; if(h.startsWith('@')) assignTargetPlatform = 'instagram';"
                                    class="px-2 py-1 rounded-lg text-[10px] font-bold border transition cursor-pointer"
                                    :class="assignTargetHandle === h ? 'bg-pink-100 text-pink-700 border-pink-300' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border-slate-200'"
                                    x-text="h"
                                ></button>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Or link to a Content Shoot -->
                <template x-if="recentShoots && recentShoots.length > 0">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Or Link to Content Shoot:</label>
                        <select 
                            x-model="assignTargetShootId" 
                            @change="
                                const found = recentShoots.find(s => s.id == assignTargetShootId);
                                if(found) {
                                    assignTargetHandle = found.instagram_handle || found.youtube_channel || ('Reel #' + found.id);
                                    assignTargetPlatform = found.platform || '';
                                }
                            "
                            class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">-- No Specific Shoot --</option>
                            <template x-for="s in recentShoots" :key="s.id">
                                <option :value="s.id" x-text="'Reel #' + s.id + ': ' + s.title + ' (' + (s.instagram_handle || s.youtube_channel || s.platform) + ')'"></option>
                            </template>
                        </select>
                    </div>
                </template>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button 
                        type="button" 
                        @click="showAssignAccountModal = false" 
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-gradient-to-r from-pink-600 to-indigo-600 hover:from-pink-700 hover:to-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                    >
                        Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 5. Professional Cinema-Grade Preview Lightbox Modal -->
    <div 
        x-show="previewModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-2 sm:p-4 md:p-6"
        @keydown.window.escape="closePreview()"
    >
        <div 
            @click.outside="closePreview()" 
            class="bg-slate-900 border border-slate-800 text-white rounded-3xl max-w-5xl w-full h-[88vh] flex flex-col shadow-2xl overflow-hidden animate-in zoom-in-95 duration-150"
        >
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-800 bg-slate-900/95 shrink-0">
                <div class="flex items-center gap-3 min-w-0 pr-4">
                    <div 
                        class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 font-bold"
                        :class="previewItem?.file_type === 'photo' ? 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30' : (previewItem?.file_type === 'video' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30')"
                    >
                        <i :data-lucide="previewItem?.file_type === 'photo' ? 'image' : (previewItem?.file_type === 'video' ? 'video' : 'file-text')" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-black text-white truncate" :title="previewItem?.original_name" x-text="previewItem?.original_name"></h3>
                        <p class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5 flex-wrap">
                            <span class="capitalize font-bold text-slate-300" x-text="previewItem?.file_type"></span>
                            <span class="text-slate-600">&bull;</span>
                            <span x-text="previewItem?.formatted_size || ''"></span>
                            <span class="text-slate-600">&bull;</span>
                            <span class="font-mono text-slate-400" x-text="previewItem?.upload_date || ''"></span>
                            <span class="text-slate-600">&bull;</span>
                            <span>By: <strong class="text-slate-300" x-text="previewItem?.uploader_name || 'Member'"></strong></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <!-- Direct Download -->
                    <a 
                        :href="previewItem?.download_url" 
                        class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 transition shadow-sm cursor-pointer"
                        title="Download to computer"
                    >
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Download</span>
                    </a>

                    <!-- Open in Drive (if on Google Drive) -->
                    <a 
                        x-show="previewItem?.is_google_drive"
                        :href="previewItem?.drive_url" 
                        target="_blank" 
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold flex items-center gap-1.5 transition border border-slate-700 cursor-pointer"
                        title="Open in Google Drive"
                    >
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span class="hidden sm:inline">Open Drive</span>
                    </a>

                    <!-- Close Button -->
                    <button 
                        type="button" 
                        @click="closePreview()" 
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                        title="Close preview (Esc)"
                    >
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Stage: Displays Video / Image / Document with 100% Reliability -->
            <div class="flex-1 w-full h-full bg-black flex items-center justify-center overflow-hidden p-1 sm:p-3 relative">
                
                <!-- 1. Google Drive Cloud Media (Videos, Photos, PDFs, Docs) via Native Drive Player -->
                <template x-if="previewItem?.is_google_drive">
                    <div class="w-full h-full rounded-2xl overflow-hidden relative bg-black flex items-center justify-center">
                        <iframe 
                            :src="previewItem.preview_embed_url" 
                            class="w-full h-full rounded-2xl border-0 shadow-2xl bg-black" 
                            allow="autoplay; fullscreen; encrypted-media" 
                            allowfullscreen
                        ></iframe>
                    </div>
                </template>

                <!-- 2. Local Storage Media Pipeline (Byte-Range Streaming) -->
                <template x-if="!previewItem?.is_google_drive">
                    <div class="w-full h-full flex items-center justify-center p-2">
                        <!-- Local Image -->
                        <template x-if="previewItem?.is_image">
                            <img 
                                :src="previewItem?.stream_url || previewItem?.drive_url" 
                                class="max-h-[76vh] max-w-full object-contain rounded-2xl shadow-2xl mx-auto" 
                                alt="Preview"
                                x-on:error="previewMediaError = true"
                            >
                        </template>

                        <!-- Local Video -->
                        <template x-if="previewItem?.is_video">
                            <div class="w-full max-w-4xl flex flex-col items-center justify-center">
                                <video 
                                    x-ref="previewVideo"
                                    :src="previewItem?.stream_url || previewItem?.drive_url" 
                                    controls 
                                    autoplay 
                                    playsinline 
                                    preload="auto" 
                                    class="max-h-[76vh] w-full max-w-4xl rounded-2xl shadow-2xl bg-black mx-auto"
                                    x-on:error="previewMediaError = true"
                                ></video>
                            </div>
                        </template>

                        <!-- Local Document / Text Note -->
                        <template x-if="!previewItem?.is_image && !previewItem?.is_video">
                            <div class="text-center p-8 max-w-md mx-auto">
                                <div class="w-16 h-16 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center mx-auto mb-3 border border-amber-500/30">
                                    <i data-lucide="file-text" class="w-8 h-8"></i>
                                </div>
                                <h4 class="text-base font-bold text-white mb-2" x-text="previewItem?.original_name"></h4>
                                <p class="text-xs text-slate-400 leading-relaxed">Document is stored in your cloud storage. You can download or view it directly.</p>
                                <div class="mt-5 flex items-center justify-center gap-3">
                                    <a :href="previewItem?.download_url" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-2">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                        <span>Download Document</span>
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Media Error Fallback Overlay -->
                <div x-show="previewMediaError" x-cloak class="absolute inset-0 bg-slate-950/95 flex flex-col items-center justify-center p-6 text-center z-20">
                    <div class="w-16 h-16 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center mb-4 border border-rose-500/30">
                        <i data-lucide="alert-triangle" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-base font-bold text-white mb-1.5">Direct Inline Playback Unavailable</h3>
                    <p class="text-xs text-slate-400 max-w-md mb-5 leading-relaxed">
                        This file format cannot be decoded inline by your browser, or was uploaded in an earlier session. You can download the full original file to view it on your device.
                    </p>
                    <div class="flex items-center gap-3">
                        <a :href="previewItem?.download_url" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-2 cursor-pointer">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Download Original</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Modal Footer Status Bar -->
            <div class="px-5 py-2 border-t border-slate-800 bg-slate-900/90 flex items-center justify-between text-[11px] text-slate-400 shrink-0">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" :class="previewItem?.is_google_drive ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                    <span x-text="previewItem?.is_google_drive ? 'Google Drive High-Definition Player' : 'Local Storage Player'"></span>
                </span>
                <span class="text-slate-500">Press <kbd class="px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-[10px]">ESC</kbd> to exit preview</span>
            </div>

        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function driveApp() {
    return {
        currentFolderId: {!! json_encode($currentFolder?->id) !!},
        currentFolderName: {!! json_encode($currentFolder?->name ?? 'My Drive') !!},
        viewMode: localStorage.getItem('drive_view_mode') || 'grid',
        activeFilter: '{{ request('type', 'all') }}',
        searchQuery: '{{ request('search', '') }}',
        isDraggingOver: false,
        showNewMenu: false,
        showNewFolderModal: false,
        newFolderName: '',
        showNewDocModal: false,
        newDocName: '',
        newDocExt: 'txt',
        newDocContent: '',
        showRenameModal: false,
        renameItemType: 'file',
        renameItemId: null,
        renameItemNewName: '',
        showMoveModal: false,
        moveFileId: null,
        moveFileName: '',
        moveTargetFolderId: '',
        previewModalOpen: false,
        previewItem: null,
        previewMediaError: false,

        // Data arrays
        folders: {!! json_encode($folders) !!},
        files: {!! json_encode($formattedFiles) !!},
        recentShoots: {!! json_encode($recentShoots ?? []) !!},
        availableHandles: {!! json_encode($availableHandles ?? []) !!},

        // Target Social Account / ID for uploads
        selectedAccountHandle: '{{ request('account', '') }}',
        selectedShootId: '{{ request('shoot_id', '') }}',
        selectedPlatform: '{{ request('platform', '') }}',
        customHandleInput: '',

        // Assign Social ID modal state
        showAssignAccountModal: false,
        assigningFileId: null,
        assigningFileName: '',
        assignTargetHandle: '',
        assignTargetShootId: '',
        assignTargetPlatform: '',

        // Dynamic upload progress drawer
        uploads: [],
        uploadDrawerOpen: false,
        uploadDrawerMinimized: false,
        originalPageTitle: document.title,
        activeXhrs: {},
        _lastUploadTrigger: 0,

        get activeUploadsCount() {
            return this.uploads.filter(u => u.status === 'uploading' || u.status === 'syncing').length;
        },

        get overallProgress() {
            const active = this.uploads.filter(u => u.status === 'uploading' || u.status === 'syncing');
            if (active.length === 0) return 100;
            const totalBytes = active.reduce((acc, u) => acc + (u.size || 0), 0);
            const loadedBytes = active.reduce((acc, u) => acc + (u.loadedBytes || 0), 0);
            if (totalBytes > 0) {
                return Math.min(Math.round((loadedBytes / totalBytes) * 100), 99);
            }
            const sum = active.reduce((acc, u) => acc + (u.progress || 0), 0);
            return Math.min(Math.round(sum / active.length), 99);
        },

        get uploadDrawerTitle() {
            const active = this.activeUploadsCount;
            if (active > 0) {
                return `Uploading ${active} item${active > 1 ? 's' : ''}... (${this.overallProgress}%)`;
            }
            if (this.uploads.length > 0) {
                return `${this.uploads.length} upload${this.uploads.length > 1 ? 's' : ''} complete`;
            }
            return 'Upload Queue';
        },

        updateUpload(id, patch) {
            const idx = this.uploads.findIndex(u => u.id === id);
            if (idx !== -1) {
                Object.assign(this.uploads[idx], patch);
                const now = Date.now();
                if (!this._lastUploadTrigger || now - this._lastUploadTrigger > 60 || patch.status === 'completed' || patch.status === 'syncing' || patch.status === 'failed') {
                    this._lastUploadTrigger = now;
                    this.uploads = [...this.uploads];
                }
            }
        },

        openUploadDrawer() {
            this.uploadDrawerOpen = true;
            this.uploadDrawerMinimized = false;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        closeUploadDrawer() {
            this.uploadDrawerOpen = false;
        },

        clearCompletedUploads() {
            this.uploads = this.uploads.filter(u => u.status === 'uploading' || u.status === 'syncing');
            this.saveUploadsToStorage();
            if (this.uploads.length === 0) {
                this.uploadDrawerOpen = false;
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        removeUpload(item) {
            if (this.activeXhrs && this.activeXhrs[item.id]) {
                try { this.activeXhrs[item.id].abort(); } catch(e) {}
                delete this.activeXhrs[item.id];
            }
            this.uploads = this.uploads.filter(u => u.id !== item.id);
            this.saveUploadsToStorage();
            if (this.uploads.length === 0) {
                this.uploadDrawerOpen = false;
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        updateTabTitle() {
            const active = this.uploads.filter(u => u.status === 'uploading' || u.status === 'syncing');
            if (active.length > 0) {
                const totalProgress = this.overallProgress;
                document.title = `(${totalProgress}%) Uploading ${active.length} item${active.length > 1 ? 's' : ''} - Google Drive`;
            } else if (this.uploads.length > 0 && this.uploads.every(u => u.status === 'completed')) {
                document.title = `✓ Uploads Complete - Google Drive`;
                setTimeout(() => {
                    document.title = this.originalPageTitle;
                }, 4000);
            } else {
                document.title = this.originalPageTitle;
            }
        },

        saveUploadsToStorage() {
            try {
                const serializable = this.uploads.map(u => ({
                    id: u.id,
                    name: u.name,
                    size: u.size,
                    sizeFormatted: u.sizeFormatted,
                    loadedBytes: u.loadedBytes || 0,
                    loadedBytesFormatted: u.loadedBytesFormatted || '0 B',
                    progress: u.progress,
                    speed: u.speed,
                    status: u.status,
                    error: u.error
                }));
                sessionStorage.setItem('drive_uploads_history', JSON.stringify(serializable));
            } catch (e) {}
        },

        loadUploadsFromStorage() {
            try {
                const stored = sessionStorage.getItem('drive_uploads_history');
                if (stored) {
                    const parsed = JSON.parse(stored);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        this.uploads = parsed.map(u => {
                            if (u.status === 'uploading' || u.status === 'syncing') {
                                u.status = 'failed';
                                u.error = 'Upload interrupted by navigation/refresh';
                            }
                            return u;
                        });
                    }
                }
            } catch (e) {}
        },

        refreshFilesList() {
            const url = new URL(window.location.href);
            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.files && Array.isArray(data.files)) {
                    this.files = data.files;
                }
                if (data.folders && Array.isArray(data.folders)) {
                    this.folders = data.folders;
                }
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            })
            .catch(() => {});
        },

        get filteredFolders() {
            let list = this.folders;
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(f => f.name.toLowerCase().includes(q));
            }
            return list;
        },

        get filteredFiles() {
            let list = this.files;
            if (this.activeFilter && this.activeFilter !== 'all') {
                list = list.filter(f => f.file_type === this.activeFilter);
            }
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(f => f.original_name.toLowerCase().includes(q) || (f.upload_date && f.upload_date.includes(q)));
            }
            return list;
        },

        // groupedFiles: Instagram / YouTube ID → Upload Date → Uploader
        get groupedFiles() {
            const list = this.filteredFiles;
            const accountMap = {};

            list.forEach(file => {
                // Level 1: Instagram or YouTube Account ID
                let rawAccount = file.account_handle || (file.target_account && file.target_account !== 'General / No Account' ? file.target_account : null);
                let accountKey = rawAccount ? rawAccount.trim().toLowerCase() : 'general';
                let accountTitle = rawAccount ? rawAccount.trim() : 'General Uploads (No Account)';
                let platform = file.platform || 'other';
                if (accountTitle.startsWith('@')) {
                    platform = 'instagram';
                } else if (accountTitle.toLowerCase().includes('youtube') || accountTitle.toLowerCase().includes('yt')) {
                    platform = 'youtube';
                }

                if (!accountMap[accountKey]) {
                    accountMap[accountKey] = {
                        accountKey,
                        accountTitle,
                        platform,
                        contentShootId: file.content_shoot_id || null,
                        shootTitle: file.shoot_title || null,
                        dateMap: {},
                        totalFiles: 0,
                    };
                }
                accountMap[accountKey].totalFiles++;

                // Level 2: Upload Date
                const date = file.upload_date || 'Unknown Date';
                if (!accountMap[accountKey].dateMap[date]) {
                    accountMap[accountKey].dateMap[date] = {
                        date,
                        uploaderMap: {},
                        files: [],
                    };
                }
                accountMap[accountKey].dateMap[date].files.push(file);

                // Level 3: Uploader Name
                const uploader = file.uploader_name || 'Member';
                if (!accountMap[accountKey].dateMap[date].uploaderMap[uploader]) {
                    accountMap[accountKey].dateMap[date].uploaderMap[uploader] = {
                        uploaderName: uploader,
                        files: [],
                    };
                }
                accountMap[accountKey].dateMap[date].uploaderMap[uploader].files.push(file);
            });

            // Convert to sorted arrays: Accounts alphabetically, General at the end
            return Object.values(accountMap)
                .sort((a, b) => {
                    if (a.accountKey !== 'general' && b.accountKey === 'general') return -1;
                    if (a.accountKey === 'general' && b.accountKey !== 'general') return 1;
                    return a.accountTitle.localeCompare(b.accountTitle);
                })
                .map(ag => ({
                    ...ag,
                    dateGroups: Object.values(ag.dateMap)
                        .sort((a, b) => b.date.localeCompare(a.date)) // newest date first
                        .map(dg => ({
                            ...dg,
                            uploaderGroups: Object.values(dg.uploaderMap)
                                .sort((a, b) => a.uploaderName.localeCompare(b.uploaderName)),
                        })),
                }));
        },

        init() {
            this.originalPageTitle = document.title;
            this.loadUploadsFromStorage();

            this.$watch('viewMode', (val) => {
                localStorage.setItem('drive_view_mode', val);
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('filteredFiles', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('filteredFolders', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });

            // Tab visibility change: restore tab title & refresh icons when tab is focused
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.updateTabTitle();
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                }
            });

            // Prevent accidental page navigation or tab closure while upload is in progress
            window.addEventListener('beforeunload', (e) => {
                const active = this.uploads.filter(u => u.status === 'uploading' || u.status === 'syncing');
                if (active.length > 0) {
                    e.preventDefault();
                    e.returnValue = 'You have uploads in progress. If you leave this page, your upload will be cancelled.';
                    return e.returnValue;
                }
            });

            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        handleDrop(e) {
            this.isDraggingOver = false;
            if (e.dataTransfer && e.dataTransfer.files) {
                this.uploadFiles(e.dataTransfer.files);
            }
        },

        handleFileInputChange(e) {
            if (e.target.files) {
                this.uploadFiles(e.target.files);
                e.target.value = '';
            }
        },

        uploadFiles(fileList) {
            if (!fileList || fileList.length === 0) return;
            this.uploadDrawerOpen = true;
            this.uploadDrawerMinimized = false;

            Array.from(fileList).forEach(file => {
                const uploadId = 'up_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
                const uploadItem = {
                    id: uploadId,
                    name: file.name,
                    size: file.size,
                    sizeFormatted: this.formatBytes(file.size),
                    loadedBytes: 0,
                    loadedBytesFormatted: '0 B',
                    progress: 0,
                    speed: 'Starting...',
                    status: 'uploading',
                    error: null
                };
                this.uploads.unshift(uploadItem);
                this.performUpload(file, uploadId);
            });
            this.updateTabTitle();
            this.saveUploadsToStorage();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        performUpload(file, uploadId) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');
            if (this.currentFolderId) {
                formData.append('folder_id', this.currentFolderId);
            }
            if (this.selectedAccountHandle) {
                formData.append('account_handle', this.selectedAccountHandle);
            }
            if (this.selectedShootId) {
                formData.append('content_shoot_id', this.selectedShootId);
            }
            if (this.selectedPlatform) {
                formData.append('platform', this.selectedPlatform);
            }

            const xhr = new XMLHttpRequest();
            this.activeXhrs[uploadId] = xhr;
            let startTime = Date.now();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && e.total > 0) {
                    const percent = Math.min(Math.round((e.loaded / e.total) * 100), 99);
                    const elapsedSec = (Date.now() - startTime) / 1000;
                    let speedStr = 'Calculating...';
                    if (elapsedSec > 0.2) {
                        const bytesPerSec = e.loaded / elapsedSec;
                        if (bytesPerSec >= 1024 * 1024) {
                            speedStr = (bytesPerSec / (1024 * 1024)).toFixed(1) + ' MB/s';
                        } else {
                            speedStr = (bytesPerSec / 1024).toFixed(0) + ' KB/s';
                        }
                    }

                    const loadedFormatted = this.formatBytes(e.loaded);
                    const totalFormatted = this.formatBytes(e.total);
                    const isAllBytesSent = e.loaded >= e.total;

                    this.updateUpload(uploadId, {
                        loadedBytes: e.loaded,
                        loadedBytesFormatted: loadedFormatted,
                        sizeFormatted: totalFormatted,
                        progress: percent,
                        speed: isAllBytesSent ? 'Syncing to Drive' : speedStr,
                        status: isAllBytesSent ? 'syncing' : 'uploading'
                    });
                    this.updateTabTitle();
                }
            });

            xhr.addEventListener('load', () => {
                delete this.activeXhrs[uploadId];

                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            this.updateUpload(uploadId, {
                                loadedBytes: file.size,
                                loadedBytesFormatted: this.formatBytes(file.size),
                                progress: 100,
                                status: 'completed',
                                speed: 'Saved'
                            });
                            this.showToast(res.message || 'File uploaded successfully!');
                            if (res.file) {
                                this.files.unshift(res.file);
                            } else if (res.files && res.files.length) {
                                res.files.forEach(f => this.files.unshift(f));
                            }
                        } else {
                            this.updateUpload(uploadId, {
                                status: 'failed',
                                error: res.error || 'Upload error'
                            });
                        }
                    } catch (err) {
                        this.updateUpload(uploadId, {
                            loadedBytes: file.size,
                            loadedBytesFormatted: this.formatBytes(file.size),
                            progress: 100,
                            status: 'completed',
                            speed: 'Saved'
                        });
                        this.showToast('Upload finished!');
                        this.refreshFilesList();
                    }
                } else {
                    let errMsg = 'Upload failed (' + xhr.status + ')';
                    try {
                        const errRes = JSON.parse(xhr.responseText);
                        if (errRes.message) errMsg = errRes.message;
                    } catch (e) {}
                    this.updateUpload(uploadId, {
                        status: 'failed',
                        error: errMsg
                    });
                }
                this.updateTabTitle();
                this.saveUploadsToStorage();
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });

            xhr.addEventListener('error', () => {
                delete this.activeXhrs[uploadId];
                this.updateUpload(uploadId, {
                    status: 'failed',
                    error: 'Network connection lost during upload'
                });
                this.updateTabTitle();
                this.saveUploadsToStorage();
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });

            xhr.addEventListener('abort', () => {
                delete this.activeXhrs[uploadId];
                this.updateUpload(uploadId, {
                    status: 'failed',
                    error: 'Upload cancelled'
                });
                this.updateTabTitle();
                this.saveUploadsToStorage();
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });

            xhr.open('POST', '{{ route('upload.store') }}');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(formData);
        },

        cancelUpload(item) {
            if (this.activeXhrs && this.activeXhrs[item.id]) {
                try {
                    this.activeXhrs[item.id].abort();
                } catch(e) {}
                delete this.activeXhrs[item.id];
            }
            this.updateUpload(item.id, {
                status: 'failed',
                error: 'Upload cancelled'
            });
            this.updateTabTitle();
            this.saveUploadsToStorage();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        submitNewFolder() {
            if (!this.newFolderName.trim()) return;

            fetch('{{ route('drive.folders.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.newFolderName.trim(),
                    parent_id: this.currentFolderId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.folder) {
                    this.folders.push(data.folder);
                    this.showToast(data.message || 'Folder created successfully!');
                    this.showNewFolderModal = false;
                    this.newFolderName = '';
                } else {
                    alert(data.message || 'Could not create folder');
                }
            })
            .catch(() => alert('Network error creating folder'));
        },

        submitNewDoc() {
            if (!this.newDocName.trim()) return;

            fetch('{{ route('drive.files.create') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.newDocName.trim(),
                    extension: this.newDocExt,
                    content: this.newDocContent,
                    folder_id: this.currentFolderId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.file) {
                    this.files.unshift(data.file);
                    this.showToast(data.message || 'Document created!');
                    this.showNewDocModal = false;
                    this.newDocName = '';
                    this.newDocContent = '';
                } else {
                    alert(data.message || 'Could not create document');
                }
            })
            .catch(() => alert('Network error saving document'));
        },

        openRenameModal(type, id, currentName) {
            this.renameItemType = type;
            this.renameItemId = id;
            this.renameItemNewName = currentName;
            this.showRenameModal = true;
        },

        submitRename() {
            if (!this.renameItemNewName.trim() || !this.renameItemId) return;

            const url = this.renameItemType === 'folder'
                ? `{{ url('/drive/folders') }}/${this.renameItemId}/rename`
                : `{{ url('/drive/files') }}/${this.renameItemId}/rename`;

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: this.renameItemNewName.trim() })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (this.renameItemType === 'folder') {
                        const f = this.folders.find(item => item.id === this.renameItemId);
                        if (f) f.name = this.renameItemNewName.trim();
                    } else {
                        const f = this.files.find(item => item.id === this.renameItemId);
                        if (f) f.original_name = this.renameItemNewName.trim();
                    }
                    this.showToast('Renamed successfully');
                    this.showRenameModal = false;
                }
            })
            .catch(() => alert('Rename error'));
        },

        openMoveModal(fileId, fileName) {
            this.moveFileId = fileId;
            this.moveFileName = fileName;
            this.moveTargetFolderId = '';
            this.showMoveModal = true;
        },

        submitMove() {
            if (!this.moveFileId) return;

            fetch(`{{ url('/drive/files') }}/${this.moveFileId}/move`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ folder_id: this.moveTargetFolderId || null })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Remove file from current view since it moved
                    this.files = this.files.filter(f => f.id !== this.moveFileId);
                    this.showToast('File moved successfully');
                    this.showMoveModal = false;
                }
            })
            .catch(() => alert('Move error'));
        },

        deleteFolder(folderId, folderName) {
            if (!confirm(`Are you sure you want to delete folder "${folderName}" and all its files?`)) return;

            fetch(`{{ url('/drive/folders') }}/${folderId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.folders = this.folders.filter(f => f.id !== folderId);
                    this.showToast(data.message || 'Folder deleted');
                }
            })
            .catch(() => alert('Delete error'));
        },

        deleteFile(fileId, fileName) {
            if (!confirm(`Are you sure you want to delete file "${fileName}"?`)) return;

            fetch(`{{ url('/drive/files') }}/${fileId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.files = this.files.filter(f => f.id !== fileId);
                    this.showToast('File deleted');
                }
            })
            .catch(() => alert('Delete error'));
        },

        openPreview(file) {
            this.previewItem = file;
            this.previewMediaError = false;
            this.previewModalOpen = true;
            this.$nextTick(() => { 
                if (window.lucide) lucide.createIcons(); 
                if (this.$refs.previewVideo) {
                    try {
                        this.$refs.previewVideo.load();
                        this.$refs.previewVideo.play().catch(() => {});
                    } catch (e) {}
                }
            });
        },

        closePreview() {
            if (this.$refs.previewVideo) {
                try {
                    this.$refs.previewVideo.pause();
                } catch (e) {}
            }
            this.previewModalOpen = false;
            this.previewItem = null;
            this.previewMediaError = false;
        },

        openAssignAccountModal(file) {
            this.assigningFileId = file.id;
            this.assigningFileName = file.original_name;
            this.assignTargetHandle = file.account_handle || (file.target_account && file.target_account !== 'General / No Account' ? file.target_account : '');
            this.assignTargetShootId = file.content_shoot_id || '';
            this.assignTargetPlatform = file.platform || '';
            this.showAssignAccountModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        submitAssignAccount() {
            if (!this.assigningFileId) return;
            fetch(`{{ url('/drive/files') }}/${this.assigningFileId}/social-account`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    account_handle: this.assignTargetHandle,
                    content_shoot_id: this.assignTargetShootId || null,
                    platform: this.assignTargetPlatform || (this.assignTargetHandle.startsWith('@') ? 'instagram' : null),
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.showToast(data.message || 'File assigned successfully!');
                    const f = this.files.find(item => item.id === this.assigningFileId);
                    if (f) {
                        f.account_handle = data.account_handle;
                        f.content_shoot_id = data.content_shoot_id;
                        f.platform = data.platform;
                        f.target_account = data.target_account;
                        f.shoot_title = data.shoot_title;
                    }
                    this.showAssignAccountModal = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    this.showToast(data.error || 'Failed to update account assignment');
                }
            })
            .catch(() => {
                this.showToast('Failed to update account assignment');
            });
        },

        formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        showToast(msg) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-5 right-5 z-50 bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-2xl shadow-xl border border-slate-700 animate-in fade-in slide-in-from-top-4 duration-200 flex items-center gap-2';
            toast.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-400"></span><span>${msg}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'transition-opacity');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    };
}
</script>
@endpush
