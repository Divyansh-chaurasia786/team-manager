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
        <div class="text-left">
            <div class="text-xs font-bold text-slate-800">Drag & drop files here or click to browse</div>
            <div class="text-[11px] text-slate-400">Uploads are streamed dynamically with live byte progress &bull; Unlimited file size &bull; No page freeze</div>
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

    <!-- Files Section -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <i data-lucide="file" class="w-4 h-4 text-slate-400"></i>
                <span>Files (<span x-text="filteredFiles.length"></span>)</span>
            </h3>
        </div>

        <!-- 1. Grid View Mode -->
        <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <template x-for="file in filteredFiles" :key="file.id">
                <div class="bg-white border border-slate-200 hover:border-indigo-400 rounded-2xl p-4 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
                    
                    <!-- File Card Header -->
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div 
                                class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-sm font-bold"
                                :class="file.file_type === 'photo' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : (file.file_type === 'video' ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-amber-50 text-amber-600 border border-amber-100')"
                            >
                                <i 
                                    :data-lucide="file.file_type === 'photo' ? 'image' : (file.file_type === 'video' ? 'video' : 'file-text')" 
                                    class="w-5 h-5"
                                ></i>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 truncate" :title="file.original_name" x-text="file.original_name"></h4>
                                <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                    <span class="capitalize font-bold text-slate-600" x-text="file.file_type"></span>
                                    <span>&bull;</span>
                                    <span x-text="file.formatted_size || file.upload_date"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Dropdown Menu -->
                        <div class="relative" x-data="{ cardMenuOpen: false }" @click.outside="cardMenuOpen = false">
                            <button 
                                type="button" 
                                @click.stop="cardMenuOpen = !cardMenuOpen"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                            >
                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                            </button>
                            <div 
                                x-show="cardMenuOpen" 
                                x-cloak 
                                class="absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-20"
                            >
                                <button 
                                    type="button" 
                                    @click="openPreview(file); cardMenuOpen = false" 
                                    class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 cursor-pointer"
                                >
                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Quick Preview</span>
                                </button>
                                <a 
                                    :href="file.download_url" 
                                    class="px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2"
                                >
                                    <i data-lucide="download" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Download</span>
                                </a>
                                <a 
                                    :href="file.drive_url" 
                                    target="_blank" 
                                    class="px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2"
                                >
                                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Open in Drive</span>
                                </a>
                                <button 
                                    type="button" 
                                    @click="openMoveModal(file.id, file.original_name); cardMenuOpen = false" 
                                    class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 cursor-pointer"
                                >
                                    <i data-lucide="folder-input" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Move to Folder</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="openRenameModal('file', file.id, file.original_name); cardMenuOpen = false" 
                                    class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 cursor-pointer"
                                >
                                    <i data-lucide="edit-2" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Rename</span>
                                </button>
                                <div class="my-1 border-t border-slate-100"></div>
                                <button 
                                    type="button" 
                                    @click="deleteFile(file.id, file.original_name); cardMenuOpen = false" 
                                    class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 cursor-pointer"
                                >
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                                    <span>Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Thumbnail / Preview Area -->
                    <div 
                        @click="openPreview(file)"
                        class="w-full h-36 rounded-xl bg-slate-900 border border-slate-100 flex items-center justify-center overflow-hidden cursor-pointer mb-3 relative group/thumb"
                    >
                        <template x-if="file.is_image">
                            <img :src="file.thumbnail_url || file.drive_url" class="w-full h-full object-cover group-hover/thumb:scale-105 transition duration-200" alt="thumbnail" loading="lazy">
                        </template>

                        <template x-if="file.is_video">
                            <div class="relative w-full h-full bg-slate-950 flex items-center justify-center overflow-hidden">
                                <template x-if="file.thumbnail_url && file.is_google_drive">
                                    <img :src="file.thumbnail_url" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover/thumb:opacity-80 group-hover/thumb:scale-105 transition duration-200" alt="video thumbnail">
                                </template>
                                <div class="relative z-10 w-11 h-11 rounded-full bg-white/20 backdrop-blur-md text-white flex items-center justify-center group-hover/thumb:scale-110 group-hover/thumb:bg-rose-600 transition shadow-lg">
                                    <i data-lucide="play" class="w-5 h-5 fill-white ml-0.5"></i>
                                </div>
                                <span class="absolute bottom-2 left-2 z-10 px-2 py-0.5 rounded-md bg-black/70 backdrop-blur-xs text-[10px] font-bold text-white uppercase tracking-wider flex items-center gap-1">
                                    <i data-lucide="video" class="w-3 h-3 text-rose-400"></i> Video
                                </span>
                            </div>
                        </template>

                        <template x-if="!file.is_image && !file.is_video">
                            <div class="flex flex-col items-center justify-center text-amber-500">
                                <i data-lucide="file-text" class="w-8 h-8 text-amber-400"></i>
                                <span class="text-[10px] text-slate-400 mt-1 font-bold">Document Note</span>
                            </div>
                        </template>

                        <!-- Quick Hover Action Overlay -->
                        <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover/thumb:opacity-100 transition flex items-center justify-center gap-2 pointer-events-none">
                            <span class="px-2.5 py-1 rounded-lg bg-white/90 text-slate-900 text-[10px] font-bold shadow-sm">Click to Preview</span>
                        </div>
                    </div>

                    <!-- Footer Info & Quick Download -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-[10px] text-slate-400">
                        <span class="truncate max-w-[120px]" x-text="'By: ' + (file.uploader_name || 'Member')"></span>
                        <div class="flex items-center gap-1.5">
                            <a 
                                :href="file.download_url" 
                                class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition cursor-pointer"
                                title="Download"
                            >
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            </a>
                            <a 
                                :href="file.drive_url" 
                                target="_blank" 
                                class="p-1.5 rounded-lg bg-slate-50 text-slate-600 hover:bg-slate-200 transition"
                                title="Open Drive Link"
                            >
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </template>
        </div>

        <!-- 2. List View Mode -->
        <div x-show="viewMode === 'list'" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase">
                    <tr>
                        <th class="py-3 px-4">Name</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Size</th>
                        <th class="py-3 px-4">Uploaded By</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="file in filteredFiles" :key="file.id">
                        <tr class="hover:bg-slate-50/70 transition group">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div 
                                        class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                        :class="file.file_type === 'photo' ? 'bg-indigo-50 text-indigo-600' : (file.file_type === 'video' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600')"
                                    >
                                        <i :data-lucide="file.file_type === 'photo' ? 'image' : (file.file_type === 'video' ? 'video' : 'file-text')" class="w-4 h-4"></i>
                                    </div>
                                    <span 
                                        @click="openPreview(file)"
                                        class="font-bold text-slate-800 hover:text-indigo-600 cursor-pointer truncate max-w-xs" 
                                        x-text="file.original_name"
                                    ></span>
                                </div>
                            </td>
                            <td class="py-3 px-4 capitalize text-slate-600 font-semibold" x-text="file.file_type"></td>
                            <td class="py-3 px-4 text-slate-500" x-text="file.formatted_size || '—'"></td>
                            <td class="py-3 px-4 text-slate-600 font-semibold" x-text="file.uploader_name || 'Member'"></td>
                            <td class="py-3 px-4 text-slate-400 font-mono text-[11px]" x-text="file.upload_date"></td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button 
                                        type="button" 
                                        @click="openPreview(file)" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition cursor-pointer"
                                        title="Preview"
                                    >
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <a 
                                        :href="file.download_url" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition"
                                        title="Download"
                                    >
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </a>
                                    <button 
                                        type="button" 
                                        @click="openMoveModal(file.id, file.original_name)" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition cursor-pointer"
                                        title="Move"
                                    >
                                        <i data-lucide="folder-input" class="w-4 h-4"></i>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="openRenameModal('file', file.id, file.original_name)" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer"
                                        title="Rename"
                                    >
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="deleteFile(file.id, file.original_name)" 
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                        title="Delete"
                                    >
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

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
    <!-- GOOGLE DRIVE-STYLE FLOATING UPLOAD PROGRESS DRAWER (BOTTOM-RIGHT) -->
    <!-- ============================================================ -->
    <div 
        x-show="uploadDrawerOpen" 
        x-cloak 
        class="fixed right-4 bottom-4 z-50 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transition-all duration-300"
    >
        <!-- Drawer Header -->
        <div class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="cloud-upload" class="w-4 h-4 text-indigo-400 animate-pulse"></i>
                <span class="text-xs font-bold" x-text="uploadDrawerTitle"></span>
            </div>
            <div class="flex items-center gap-1">
                <button 
                    type="button" 
                    @click="uploadDrawerMinimized = !uploadDrawerMinimized" 
                    class="p-1 rounded text-slate-400 hover:text-white transition cursor-pointer"
                >
                    <i :data-lucide="uploadDrawerMinimized ? 'chevron-up' : 'minus'" class="w-3.5 h-3.5"></i>
                </button>
                <button 
                    type="button" 
                    @click="uploadDrawerOpen = false" 
                    class="p-1 rounded text-slate-400 hover:text-white transition cursor-pointer"
                >
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <!-- Drawer Body (Uploads Queue) -->
        <div x-show="!uploadDrawerMinimized" class="max-h-72 overflow-y-auto divide-y divide-slate-100 p-2 space-y-2">
            <template x-for="item in uploads" :key="item.id">
                <div class="p-2.5 rounded-xl bg-slate-50/70 border border-slate-100 flex flex-col gap-1.5">
                    
                    <div class="flex items-center justify-between gap-2 min-w-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <i data-lucide="file" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                            <span class="text-xs font-bold text-slate-800 truncate" :title="item.name" x-text="item.name"></span>
                        </div>
                        <span 
                            class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
                            :class="item.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : (item.status === 'syncing' ? 'bg-indigo-100 text-indigo-700 animate-pulse' : (item.status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700'))"
                            x-text="item.status === 'syncing' ? 'Syncing...' : (item.status === 'completed' ? 'Done' : item.progress + '%')"
                        ></span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                        <div 
                            class="h-1.5 rounded-full transition-all duration-200"
                            :class="item.status === 'completed' ? 'bg-emerald-500' : (item.status === 'failed' ? 'bg-rose-500' : 'bg-indigo-600')"
                            :style="'width: ' + item.progress + '%'"
                        ></div>
                    </div>

                    <!-- Subtext Info & Speed -->
                    <div class="flex items-center justify-between text-[10px] text-slate-400">
                        <span x-text="item.status === 'syncing' ? 'Connecting with Google Drive cloud...' : (item.status === 'completed' ? 'Saved to Google Drive' : (item.sizeFormatted + ' &bull; ' + item.speed))"></span>
                        <template x-if="item.status === 'uploading'">
                            <button type="button" @click="cancelUpload(item)" class="text-rose-500 hover:underline">Cancel</button>
                        </template>
                    </div>

                    <template x-if="item.error">
                        <div class="text-[10px] text-rose-600 font-bold" x-text="item.error"></div>
                    </template>
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
                                @error="previewMediaError = true"
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
                                    @error="previewMediaError = true"
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

        // Dynamic upload progress drawer
        uploads: [],
        uploadDrawerOpen: false,
        uploadDrawerMinimized: false,

        get uploadDrawerTitle() {
            const active = this.uploads.filter(u => u.status === 'uploading' || u.status === 'syncing').length;
            if (active > 0) {
                return `Uploading ${active} item${active > 1 ? 's' : ''}...`;
            }
            return `${this.uploads.length} upload${this.uploads.length > 1 ? 's' : ''} complete`;
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

        init() {
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
                const uploadItem = {
                    id: 'up_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7),
                    name: file.name,
                    size: file.size,
                    sizeFormatted: this.formatBytes(file.size),
                    progress: 0,
                    speed: 'Calculating...',
                    status: 'uploading',
                    error: null,
                    xhr: null
                };
                this.uploads.unshift(uploadItem);
                this.performUpload(file, uploadItem);
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        performUpload(file, item) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');
            if (this.currentFolderId) {
                formData.append('folder_id', this.currentFolderId);
            }

            const xhr = new XMLHttpRequest();
            item.xhr = xhr;
            let startTime = Date.now();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    item.progress = Math.min(percent, 99);

                    const elapsedSec = (Date.now() - startTime) / 1000;
                    if (elapsedSec > 0.3) {
                        const bytesPerSec = e.loaded / elapsedSec;
                        item.speed = (bytesPerSec / (1024 * 1024)).toFixed(1) + ' MB/s';
                    }

                    if (item.progress >= 99) {
                        item.status = 'syncing';
                    }
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            item.progress = 100;
                            item.status = 'completed';
                            this.showToast(res.message || 'File uploaded successfully!');
                            if (res.file) {
                                this.files.unshift(res.file);
                            } else if (res.files && res.files.length) {
                                res.files.forEach(f => this.files.unshift(f));
                            }
                        } else {
                            item.status = 'failed';
                            item.error = res.error || 'Upload error';
                        }
                    } catch (err) {
                        item.progress = 100;
                        item.status = 'completed';
                        this.showToast('Upload finished!');
                        setTimeout(() => location.reload(), 1200);
                    }
                } else {
                    item.status = 'failed';
                    try {
                        const errRes = JSON.parse(xhr.responseText);
                        item.error = errRes.message || ('Server error ' + xhr.status);
                    } catch (e) {
                        item.error = 'Upload failed (' + xhr.status + ')';
                    }
                }
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });

            xhr.addEventListener('error', () => {
                item.status = 'failed';
                item.error = 'Network connection lost during upload';
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });

            xhr.open('POST', '{{ route('upload.store') }}');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(formData);
        },

        cancelUpload(item) {
            if (item.xhr) {
                item.xhr.abort();
            }
            item.status = 'failed';
            item.error = 'Upload cancelled';
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