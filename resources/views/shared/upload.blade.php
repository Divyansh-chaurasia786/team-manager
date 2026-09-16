@extends('layouts.app')
@section('title', 'Google Drive Cloud')
@section('content')

<div class="space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Google Drive Synchronization & Download</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Automated date-wise folder sorting (Photos, Videos, Documents) with 1-click download & audit logging</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('history.index') }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1.5 transition">
                <i data-lucide="history" class="w-4 h-4"></i>
                <span>View Download History</span>
            </a>
            <span class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold flex items-center gap-1.5">
                <i data-lucide="cloud" class="w-4 h-4"></i>
                <span>Google Drive Live Sync</span>
            </span>
        </div>
    </div>

    @php
        $isGoogleConnected = \App\Http\Controllers\GoogleAuthController::isConnected();
        $connectedAccount = \App\Http\Controllers\GoogleAuthController::getConnectedAccount();
    @endphp

    @if($isGoogleConnected)
        <!-- Google Drive Active Connected Banner -->
        <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-emerald-950 via-slate-900 to-emerald-900 text-white border border-emerald-500/40 shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM19 18H6c-2.21 0-4-1.79-4-4 0-2.05 1.53-3.76 3.56-3.97l1.07-.11.5-.95C8.08 7.14 9.94 6 12 6c2.62 0 4.88 1.86 5.39 4.43l.3 1.5 1.53.11c1.56.1 2.78 1.41 2.78 2.96 0 1.65-1.35 3-3 3z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-black text-white">Google Drive Connected & Synchronized</h3>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold">Active</span>
                    </div>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Connected as <strong class="text-white">{{ $connectedAccount['email'] ?? 'Authorized Account' }}</strong>. All files automatically sort into date folders and Photos/Videos/Documents subfolders.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <form method="POST" action="{{ route('google.disconnect') }}" onsubmit="return confirm('Disconnect Google Drive?')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-rose-600/80 text-white text-xs font-bold transition cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="unlink" class="w-3.5 h-3.5"></i>
                        <span>Disconnect</span>
                    </button>
                </form>
            </div>
        </div>
    @else
        <!-- Connect Google Drive 1-Click Banner -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Connect Your Google Drive (1-Click)</h3>
                    <p class="text-xs text-slate-500 mt-0.5 max-w-xl leading-relaxed">
                        Sign in with your Google account to automatically create and sync all folders and files directly in your Drive with zero manual setup.
                    </p>
                </div>
            </div>

            <a href="{{ route('google.connect') }}" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-xs font-black transition shadow-md flex items-center justify-center gap-2 shrink-0 cursor-pointer">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM19 18H6c-2.21 0-4-1.79-4-4 0-2.05 1.53-3.76 3.56-3.97l1.07-.11.5-.95C8.08 7.14 9.94 6 12 6c2.62 0 4.88 1.86 5.39 4.43l.3 1.5 1.53.11c1.56.1 2.78 1.41 2.78 2.96 0 1.65-1.35 3-3 3z"/>
                </svg>
                <span>Connect Google Drive</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Upload Zone Card -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Direct Upload Pipeline</h3>
                    </div>
                    <span class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">Auto Deduplication</span>
                </div>

                <form method="POST" action="{{ route('upload.store') }}" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    
                    <!-- Drag & Drop / Click Zone -->
                    <div 
                        class="p-8 border-2 border-dashed border-slate-300 hover:border-indigo-500 rounded-2xl bg-slate-50/60 hover:bg-indigo-50/20 text-center cursor-pointer transition-all duration-200 group"
                        onclick="document.getElementById('fileInput').click()"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white mx-auto flex items-center justify-center transition-all shadow-xs">
                            <i data-lucide="cloud-upload" class="w-6 h-6"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800 mt-3 mb-1">Click or drag file to upload</h4>
                        <p class="text-xs text-slate-400">Photos (JPG, PNG, WEBP), Videos (MP4, MOV), Documents (PDF, DOCX, XLSX)</p>
                        
                        <input type="file" name="file" id="fileInput" class="hidden" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" onchange="showSelectedFile(this)" required>
                    </div>

                    <!-- Selected File Info Banner -->
                    <div id="selectedFileInfo" class="hidden mt-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <i data-lucide="file-check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                            <span id="fileNameLabel" class="font-bold truncate"></span>
                        </div>
                        <span id="fileSizeLabel" class="text-[11px] text-emerald-600 shrink-0"></span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn" class="mt-4 w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2 cursor-pointer border border-indigo-400/30">
                        <i data-lucide="cloud-upload" class="w-4 h-4"></i>
                        <span>Upload Directly to Google Drive</span>
                    </button>
                </form>

                <!-- Smart Deduplication Note -->
                <div class="mt-6 p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3 text-xs text-slate-600">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
                    <div>
                        <span class="font-bold text-slate-900">100% Audited & Logged:</span> Every upload, download, and deletion is recorded with member identity, exact timestamp, and IP address for compliance.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Recent Upload History with Direct Download -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                            <i data-lucide="folder-clock" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Synchronized Files Stream</h3>
                    </div>
                    <span class="text-xs font-bold text-slate-400">{{ $recentFiles->count() }} Files</span>
                </div>

                <div class="space-y-3 max-h-[520px] overflow-y-auto pr-1">
                    @forelse($recentFiles as $file)
                        <div class="p-3.5 rounded-xl border border-slate-100 hover:border-slate-200 hover:bg-slate-50/50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-bold text-xs {{ $file->file_type === 'photo' ? 'bg-indigo-50 text-indigo-600' : ($file->file_type === 'video' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600') }}">
                                    <i data-lucide="{{ $file->file_type === 'photo' ? 'image' : ($file->file_type === 'video' ? 'video' : 'file-text') }}" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 truncate" title="{{ $file->original_name }}">{{ $file->original_name }}</div>
                                    <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5 flex-wrap">
                                        <span class="capitalize font-bold text-slate-600">{{ $file->file_type }}</span>
                                        <span>•</span>
                                        <span class="bg-slate-100 px-1.5 py-0.5 rounded font-mono text-[10px]">{{ $file->upload_date }}</span>
                                        <span>•</span>
                                        <span>Uploaded by: <strong class="text-slate-700">{{ $file->uploader->name ?? 'User' }}</strong></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions: Download, Preview & Delete -->
                            <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end">
                                <!-- Download Button (Tracks in History) -->
                                <a href="{{ route('drive.download', $file) }}" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center gap-1 shadow-xs cursor-pointer" title="Download to your computer (Audited)">
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
                                    <form method="POST" action="{{ route('drive.destroy', $file) }}" onsubmit="return confirm('Delete this file from Drive records?')" class="m-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete file record">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-14 text-center text-slate-400">
                            <i data-lucide="cloud-off" class="w-10 h-10 mx-auto mb-2 text-slate-300"></i>
                            <p class="text-xs font-semibold">No files synced to Google Drive yet</p>
                            <p class="text-[11px] text-slate-400 mt-1">Files uploaded by any team member will appear here for instant download.</p>
                        </div>
                    @endforelse
                </div>
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
</script>
@endpush