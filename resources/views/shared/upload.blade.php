@extends('layouts.app')
@section('title', 'Google Drive Cloud')
@section('content')

@php
    $isGoogleConnected = \App\Http\Controllers\GoogleAuthController::isConnected();
    $connectedAccount = \App\Http\Controllers\GoogleAuthController::getConnectedAccount();
    $currentType = request('type', 'all');
    $currentSearch = request('search', '');
    $hasOAuthConfig = !empty(config('services.google.client_id')) || \Illuminate\Support\Facades\Cache::has('google_oauth_credentials');
@endphp

<div class="space-y-6 pb-28 lg:pb-16" x-data="{ showDriveSetupHelp: false, showDriveConfigModal: {{ request('open_config') ? 'true' : 'false' }}, configTab: 'oauth' }">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold mb-1">
                <a href="{{ auth()->user()->isTL() ? route('tl.dashboard') : route('member.dashboard') }}" class="hover:text-indigo-600 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-700">Google Drive Cloud</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Google Drive Synchronization & Download</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Automated date-wise folder sorting (Photos, Videos, Documents) with 1-click download & audit logging</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" @click="showDriveConfigModal = true" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1.5 transition cursor-pointer">
                <i data-lucide="settings-2" class="w-4 h-4"></i>
                <span>Cloud Credentials</span>
            </button>
            <a href="{{ route('history.index') }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1.5 transition">
                <i data-lucide="history" class="w-4 h-4"></i>
                <span>Audit & History</span>
            </a>
            <span class="px-3 py-1.5 rounded-xl {{ $isGoogleConnected ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }} text-xs font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full {{ $isGoogleConnected ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                <span>{{ $isGoogleConnected ? 'Drive Connected' : 'Drive Ready to Connect' }}</span>
            </span>
        </div>
    </div>

    <!-- Connection Hero Status Banner -->
    @if($isGoogleConnected)
        <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-emerald-950 via-slate-900 to-emerald-900 text-white border border-emerald-500/40 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0 shadow-inner">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM19 18H6c-2.21 0-4-1.79-4-4 0-2.05 1.53-3.76 3.56-3.97l1.07-.11.5-.95C8.08 7.14 9.94 6 12 6c2.62 0 4.88 1.86 5.39 4.43l.3 1.5 1.53.11c1.56.1 2.78 1.41 2.78 2.96 0 1.65-1.35 3-3 3z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-sm font-black text-white">Google Drive Connected & Synchronized</h3>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold">Active Live Sync</span>
                        @if(!empty($connectedAccount['is_service_account']))
                            <span class="px-2 py-0.5 rounded-full bg-indigo-500/30 text-indigo-200 text-[10px] font-bold border border-indigo-400/30">Service Account</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Connected as <strong class="text-white">{{ $connectedAccount['email'] ?? 'Authorized Account' }}</strong> &bull; Target: <span class="text-emerald-300 font-semibold">EcoFone Operations Drive</span>. Files auto-organize into date and Photos/Videos/Documents subfolders.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <a href="https://drive.google.com/drive/folders/{{ config('services.google.drive_folder_id', '14ctR4tZhSEKk_yPf-quSwPmcJBTo6Gt1') }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    <span>Open in Drive</span>
                </a>
                <button type="button" @click="showDriveConfigModal = true" class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                    <span>Credentials</span>
                </button>
                @if(empty($connectedAccount['is_service_account']))
                    <form method="POST" action="{{ route('google.disconnect') }}" onsubmit="return confirm('Disconnect Google Drive?')">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-rose-600 text-white text-xs font-bold transition cursor-pointer flex items-center gap-1.5">
                            <i data-lucide="unlink" class="w-3.5 h-3.5"></i>
                            <span>Disconnect</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <!-- Connect Google Drive 1-Click Banner (Clean Full-Width Card) -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-6 h-6" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm sm:text-base font-black text-slate-900">Connect Your Google Drive (1-Click)</h3>
                        <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-[10px] font-extrabold uppercase">Fast Cloud Sync</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 max-w-2xl">
                        Authorize with your Google account to automatically store company photos, shoot reels, and documents directly in your Google Drive with full 15GB+ quota and zero storage errors.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="button" @click="showDriveSetupHelp = true" class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="help-circle" class="w-4 h-4 text-slate-500"></i>
                    <span>Setup Help</span>
                </button>
                @if($hasOAuthConfig)
                    <a href="{{ route('google.connect') }}" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-xs font-black transition shadow-md flex items-center justify-center gap-2 cursor-pointer group">
                        <svg class="w-4 h-4 text-white group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM19 18H6c-2.21 0-4-1.79-4-4 0-2.05 1.53-3.76 3.56-3.97l1.07-.11.5-.95C8.08 7.14 9.94 6 12 6c2.62 0 4.88 1.86 5.39 4.43l.3 1.5 1.53.11c1.56.1 2.78 1.41 2.78 2.96 0 1.65-1.35 3-3 3z"/>
                        </svg>
                        <span>Connect Google Drive</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                @else
                    <button type="button" @click="showDriveConfigModal = true" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-xs font-black transition shadow-md flex items-center justify-center gap-2 cursor-pointer group">
                        <svg class="w-4 h-4 text-white group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM19 18H6c-2.21 0-4-1.79-4-4 0-2.05 1.53-3.76 3.56-3.97l1.07-.11.5-.95C8.08 7.14 9.94 6 12 6c2.62 0 4.88 1.86 5.39 4.43l.3 1.5 1.53.11c1.56.1 2.78 1.41 2.78 2.96 0 1.65-1.35 3-3 3z"/>
                        </svg>
                        <span>Connect Google Drive</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                    </button>
                @endif
            </div>
        </div>
    @endif

    <!-- Main 2-Column Symmetrical Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left: Upload Zone Card (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 leading-tight">Direct Upload Pipeline</h3>
                            <span class="text-[10px] text-slate-400">Instant cloud sync & folder distribution</span>
                        </div>
                    </div>
                    <span class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">Auto Sorted</span>
                </div>

                <form method="POST" action="{{ route('upload.store') }}" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    
                    <!-- Drag & Drop Zone -->
                    <div 
                        class="p-8 border-2 border-dashed border-slate-300 hover:border-indigo-500 rounded-2xl bg-slate-50/70 hover:bg-indigo-50/20 text-center cursor-pointer transition-all duration-200 group"
                        onclick="document.getElementById('fileInput').click()"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white mx-auto flex items-center justify-center transition-all shadow-2xs">
                            <i data-lucide="cloud-upload" class="w-6 h-6"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800 mt-3 mb-1">Click or drag file to upload</h4>
                        <p class="text-xs text-slate-400">Max file size 100MB</p>
                        
                        <div class="flex items-center justify-center gap-1.5 mt-3 flex-wrap">
                            <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-bold text-slate-600">JPG, PNG, WEBP</span>
                            <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-bold text-slate-600">MP4, MOV</span>
                            <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-bold text-slate-600">PDF, DOCX, XLSX</span>
                        </div>
                        
                        <input type="file" name="file" id="fileInput" class="hidden" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" onchange="showSelectedFile(this)" required>
                    </div>

                    <!-- Selected File Info Banner -->
                    <div id="selectedFileInfo" class="hidden mt-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between animate-in fade-in">
                        <div class="flex items-center gap-2 min-w-0">
                            <i data-lucide="file-check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                            <span id="fileNameLabel" class="font-bold truncate"></span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span id="fileSizeLabel" class="text-[11px] text-emerald-600 font-semibold"></span>
                            <button type="button" onclick="clearSelectedFile()" class="text-emerald-700 hover:text-rose-600 p-0.5">✕</button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn" class="mt-4 w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-md shadow-indigo-600/20 transition flex items-center justify-center gap-2 cursor-pointer border border-indigo-400/30">
                        <i data-lucide="cloud-upload" class="w-4 h-4"></i>
                        <span>Upload Directly to Google Drive</span>
                    </button>
                </form>

                <!-- Compliance / Audit Note -->
                <div class="mt-6 p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3 text-xs text-slate-600">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
                    <div>
                        <span class="font-bold text-slate-900">100% Audited & Logged:</span> Every upload, download, and deletion is recorded with member identity, exact timestamp, and IP address.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Synchronized Files Stream (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                
                <!-- Section Header with Search & Counter -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                            <i data-lucide="folder-clock" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 leading-tight">Synchronized Files Stream</h3>
                            <span class="text-[10px] text-slate-400">Date-sorted cloud archive & audited downloads</span>
                        </div>
                    </div>

                    <span class="text-xs font-black text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full self-start sm:self-auto">
                        {{ $recentFiles->count() }} {{ Str::plural('File', $recentFiles->count()) }}
                    </span>
                </div>

                <!-- Category Filters & Search -->
                <div class="flex flex-col sm:flex-row items-center gap-2 pb-4 border-b border-slate-100">
                    <!-- Filter Tabs -->
                    <div class="flex items-center gap-1 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                        <a href="{{ route('upload.index', array_merge(request()->query(), ['type' => 'all'])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $currentType === 'all' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            All
                        </a>
                        <a href="{{ route('upload.index', array_merge(request()->query(), ['type' => 'photo'])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center gap-1 {{ $currentType === 'photo' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            <i data-lucide="image" class="w-3.5 h-3.5"></i>
                            <span>Photos</span>
                        </a>
                        <a href="{{ route('upload.index', array_merge(request()->query(), ['type' => 'video'])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center gap-1 {{ $currentType === 'video' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            <i data-lucide="video" class="w-3.5 h-3.5"></i>
                            <span>Videos</span>
                        </a>
                        <a href="{{ route('upload.index', array_merge(request()->query(), ['type' => 'document'])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center gap-1 {{ $currentType === 'document' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                            <span>Documents</span>
                        </a>
                    </div>

                    <!-- Search Input -->
                    <form method="GET" action="{{ route('upload.index') }}" class="w-full sm:w-auto sm:ml-auto flex items-center">
                        <input type="hidden" name="type" value="{{ $currentType }}">
                        <div class="relative w-full sm:w-48">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                            <input 
                                type="text" 
                                name="search" 
                                value="{{ $currentSearch }}" 
                                placeholder="Search files..." 
                                class="w-full pl-8 pr-7 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:bg-white transition"
                            >
                            @if($currentSearch)
                                <a href="{{ route('upload.index', ['type' => $currentType]) }}" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 text-xs">✕</a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Files List Stream -->
                <div class="space-y-2.5 max-h-[520px] overflow-y-auto pr-1 mt-4">
                    @forelse($recentFiles as $file)
                        <div class="p-3.5 rounded-xl border border-slate-100 hover:border-slate-200 hover:bg-slate-50/50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-bold text-xs {{ $file->file_type === 'photo' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : ($file->file_type === 'video' ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-amber-50 text-amber-600 border border-amber-100') }}">
                                    <i data-lucide="{{ $file->file_type === 'photo' ? 'image' : ($file->file_type === 'video' ? 'video' : 'file-text') }}" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 truncate" title="{{ $file->original_name }}">{{ $file->original_name }}</div>
                                    <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5 flex-wrap">
                                        <span class="capitalize font-bold text-slate-600">{{ $file->file_type }}</span>
                                        <span>•</span>
                                        <span class="bg-slate-100 px-1.5 py-0.5 rounded font-mono text-[10px] text-slate-600">{{ $file->upload_date }}</span>
                                        <span>•</span>
                                        <span>Uploaded by: <strong class="text-slate-700">{{ $file->uploader->name ?? 'User' }}</strong></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions: Download, Preview & Delete -->
                            <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end">
                                <!-- Download Button (Tracks in History) -->
                                <a href="{{ route('drive.download', $file) }}" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer" title="Download to computer (Audited)">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    <span>Download</span>
                                </a>

                                <!-- View in Drive Button -->
                                <a href="{{ $file->drive_url }}" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-600 text-xs font-bold transition flex items-center gap-1" title="Open preview in Google Drive">
                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    <span class="hidden sm:inline">Preview</span>
                                </a>

                                <!-- Delete Button (TL or File Owner) -->
                                @if(auth()->user()->role === 'tl' || $file->uploaded_by === auth()->id())
                                    <form method="POST" action="{{ route('drive.destroy', $file) }}" onsubmit="return confirm('Delete this file record from Drive?')" class="m-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Delete file record">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-14 text-center text-slate-400">
                            <i data-lucide="cloud-off" class="w-10 h-10 mx-auto mb-2 text-slate-300"></i>
                            <p class="text-xs font-semibold text-slate-600">No files found</p>
                            <p class="text-[11px] text-slate-400 mt-1">Files uploaded by any team member will appear here for instant download.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Credentials & Setup Modal -->
    <div x-show="showDriveSetupHelp" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.outside="showDriveSetupHelp = false" class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="key-round" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Google Drive Integration Guide</h3>
                        <p class="text-[11px] text-slate-400">Quick 1-Click Connect & Render environment</p>
                    </div>
                </div>
                <button type="button" @click="showDriveSetupHelp = false" class="text-slate-400 hover:text-slate-600 p-1">✕</button>
            </div>

            <div class="py-4 space-y-3.5 text-xs text-slate-600">
                <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-100">
                    <span class="font-bold text-indigo-900 block mb-1">Option 1: Recommended 1-Click OAuth (Full 15GB+ Free)</span>
                    <p class="text-[11px] text-indigo-800 leading-relaxed">
                        Simply click <strong>Connect Google Drive</strong> and log in with your Google account (<code class="bg-white px-1 rounded font-mono">divyanshecofone@gmail.com</code>). All uploads are saved straight into your Google Drive under <code class="bg-white px-1 rounded font-mono">EcoFone Operations Drive</code>.
                    </p>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="font-bold text-slate-800 block mb-1">Option 2: Render Environment Variables</span>
                    <p class="text-[11px] text-slate-500 leading-relaxed mb-2">
                        For permanent setup on Render across deploys, add these variables in Render Dashboard &rarr; Environment:
                    </p>
                    <div class="space-y-1 font-mono text-[10px] text-slate-700 bg-white p-2.5 rounded-xl border border-slate-200 select-all overflow-x-auto">
                        <div>GOOGLE_CLIENT_ID={{ config('services.google.client_id') }}</div>
                        <div>GOOGLE_DRIVE_FOLDER_ID={{ config('services.google.drive_folder_id') }}</div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" @click="showDriveSetupHelp = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">
                    Close
                </button>
                <a href="{{ route('google.connect') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                    <span>Connect Google Drive</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Interactive Cloud Credentials Modal (OAuth 2.0 & Service Account) -->
    <div x-show="showDriveConfigModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.outside="showDriveConfigModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-150 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="sliders" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Configure Google Drive Cloud</h3>
                        <p class="text-[11px] text-slate-400">Manage OAuth 2.0 credentials & Service Accounts</p>
                    </div>
                </div>
                <button type="button" @click="showDriveConfigModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">✕</button>
            </div>

            <!-- Modal Tabs -->
            <div class="flex items-center gap-2 mt-4 p-1 bg-slate-100 rounded-xl">
                <button type="button" @click="configTab = 'oauth'" :class="configTab === 'oauth' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'" class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition cursor-pointer">
                    OAuth 2.0 (User Login)
                </button>
                <button type="button" @click="configTab = 'service_account'" :class="configTab === 'service_account' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'" class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition cursor-pointer">
                    Service Account (Bot)
                </button>
                <button type="button" @click="configTab = 'status'" :class="configTab === 'status' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'" class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition cursor-pointer">
                    Current Status
                </button>
            </div>

            <!-- Tab 1: OAuth Form -->
            <div x-show="configTab === 'oauth'" class="py-4 space-y-4">
                <p class="text-xs text-slate-500">
                    Enter your Google Cloud OAuth 2.0 credentials. These will be securely stored to enable 1-click login for team members without needing server restarts.
                </p>
                <form method="POST" action="{{ route('google.configure') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="type" value="oauth">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Google Client ID</label>
                        <input type="text" name="client_id" value="{{ \Illuminate\Support\Facades\Cache::get('google_oauth_credentials')['client_id'] ?? config('services.google.client_id') }}" placeholder="e.g. 123456789-abc.apps.googleusercontent.com" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Google Client Secret</label>
                        <input type="password" name="client_secret" value="{{ \Illuminate\Support\Facades\Cache::get('google_oauth_credentials')['client_secret'] ?? config('services.google.client_secret') }}" placeholder="Enter Google Client Secret" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="pt-2 flex items-center justify-between">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">
                            Save OAuth Credentials
                        </button>
                        @if($hasOAuthConfig)
                            <a href="{{ route('google.connect') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                                <span>Authorize Now</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Tab 2: Service Account Form -->
            <div x-show="configTab === 'service_account'" class="py-4 space-y-4">
                <p class="text-xs text-slate-500">
                    Paste your Google Cloud Service Account JSON key. The app will use this bot account to autonomously create folders and upload files.
                </p>
                <form method="POST" action="{{ route('google.configure') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="type" value="service_account">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Service Account JSON</label>
                        <textarea name="service_account_json" rows="6" placeholder='{"type": "service_account", "project_id": "...", ...}' required class="w-full px-3 py-2 text-[11px] font-mono bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="pt-2 flex items-center justify-end">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">
                            Save Service Account
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab 3: Current Status -->
            <div x-show="configTab === 'status'" class="py-4 space-y-3">
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Connection Status:</span>
                        <span class="font-bold {{ $isGoogleConnected ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $isGoogleConnected ? 'Active & Ready' : 'Not Connected' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Account:</span>
                        <span class="font-bold text-slate-800">{{ $connectedAccount['email'] ?? 'ecofone-drive-bot@ecofone-team-manager.iam.gserviceaccount.com (Default Bot)' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Account Type:</span>
                        <span class="font-bold text-indigo-600">{{ $connectedAccount['type'] ?? 'Service Account' }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('google.configure') }}" onsubmit="return confirm('Reset credentials to system default?')">
                    @csrf
                    <input type="hidden" name="type" value="reset">
                    <button type="submit" class="w-full py-2 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 text-xs font-bold rounded-xl transition cursor-pointer">
                        Reset to Default EcoFone Credentials
                    </button>
                </form>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end">
                <button type="button" @click="showDriveConfigModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function showSelectedFile(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('fileNameLabel').innerText = file.name;
        document.getElementById('fileSizeLabel').innerText = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        document.getElementById('selectedFileInfo').classList.remove('hidden');
        if (window.lucide) { lucide.createIcons(); }
    }
}

function clearSelectedFile() {
    const input = document.getElementById('fileInput');
    if (input) input.value = '';
    document.getElementById('selectedFileInfo').classList.add('hidden');
}
</script>
@endpush