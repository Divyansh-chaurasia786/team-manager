@extends('layouts.app')
@section('title', 'Team Discussion & Thoughts')
@section('page-title', 'Team Discussion & Thoughts')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header Banner -->
    <div class="p-4 sm:p-6 bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 rounded-3xl text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative overflow-hidden">
        <div class="absolute -right-6 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-2 border border-indigo-500/30">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                <span>Open Team Collaboration Board</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">Share Thoughts, Links & Media</h2>
            <p class="text-xs sm:text-sm text-indigo-200 mt-1 max-w-xl">
                Collaborate freely with the team. Photos and videos stay locally for 7 days unless approved & uploaded to Google Drive by the Team Lead.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
            <div class="px-3.5 py-2 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/10 text-center">
                <span class="block text-[10px] uppercase font-bold text-indigo-200">Posts Today</span>
                <span class="text-base font-black text-white">{{ $thoughts->where('created_at', '>=', now()->startOfDay())->count() }}</span>
            </div>
            <div class="px-3.5 py-2 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/10 text-center">
                <span class="block text-[10px] uppercase font-bold text-indigo-200">Total Thoughts</span>
                <span class="text-base font-black text-white">{{ $thoughts->total() }}</span>
            </div>
        </div>
    </div>

    <!-- Thought Composer Card -->
    <div class="bg-white rounded-3xl p-4 sm:p-6 shadow-sm border border-slate-200/80" x-data="thoughtComposer()">
        <form method="POST" action="{{ route('thoughts.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <!-- Composer Top Bar -->
            <div class="flex items-center gap-3">
                @if(auth()->user()->avatar_url)
                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-2xl object-cover shadow-sm shrink-0">
                @else
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-black text-sm flex items-center justify-center shadow-sm shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Post an update or thought</h3>
                    <p class="text-[11px] text-slate-500">
                        Posting as <span class="font-bold text-slate-800">{{ auth()->user()->name }}</span> 
                        &bull; <span class="font-semibold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-100">{{ auth()->user()->designation ?: (auth()->user()->isTL() ? 'Team Lead' : 'Staff Member') }}</span>
                        &bull; <span class="text-slate-400">{{ now()->format('d M Y, h:i A') }}</span>
                    </p>
                </div>
            </div>

            <!-- Content Area -->
            <div>
                <textarea 
                    name="content" 
                    rows="3" 
                    class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition resize-none placeholder:text-slate-400"
                    placeholder="What's on your mind? Share an idea, project feedback, design inspiration, or a question..."></textarea>
            </div>

            <!-- Optional Link Input (Toggleable or Visible) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <button type="button" @click="showLink = !showLink" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1.5 transition">
                        <i data-lucide="link" class="w-3.5 h-3.5"></i>
                        <span x-text="showLink ? 'Remove Link Input' : '+ Attach a Reference Link / URL'"></span>
                    </button>
                    <span class="text-[11px] text-slate-400 font-medium">Optional</span>
                </div>

                <div x-show="showLink" x-cloak class="relative animate-in fade-in duration-150">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="globe" class="w-4 h-4"></i>
                    </span>
                    <input 
                        type="url" 
                        name="link_url" 
                        x-model="linkUrl"
                        class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition placeholder:text-slate-400"
                        placeholder="https://example.com/figma-preview, https://github.com/... or any reference link">
                </div>
            </div>

            <!-- File Upload Bar & Previews -->
            <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <label class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                            <i data-lucide="image" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Attach Photo or Video</span>
                            <input type="file" name="media" accept="image/*,video/*" class="hidden" @change="handleFileSelect($event)">
                        </label>
                        <span class="text-[11px] text-slate-400 font-medium">Images or Videos &bull; Max 50MB</span>
                    </div>

                    <div class="flex items-center gap-1 text-[11px] text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200/60 font-semibold self-start sm:self-auto">
                        <i data-lucide="clock" class="w-3 h-3 text-amber-600"></i>
                        <span>7-day local retention policy</span>
                    </div>
                </div>

                <!-- Client-side Selected Media Preview -->
                <div x-show="previewUrl" x-cloak class="p-3 bg-white rounded-xl border border-slate-200 relative flex items-center gap-3">
                    <template x-if="fileType === 'image'">
                        <img :src="previewUrl" class="w-16 h-16 object-cover rounded-lg border border-slate-200">
                    </template>
                    <template x-if="fileType === 'video'">
                        <video :src="previewUrl" class="w-24 h-16 object-cover rounded-lg border border-slate-200" controls></video>
                    </template>
                    <div class="min-w-0 flex-grow">
                        <span class="text-xs font-bold text-slate-900 block truncate" x-text="fileName"></span>
                        <span class="text-[10px] text-slate-400" x-text="fileSize"></span>
                    </div>
                    <button type="button" @click="clearFile()" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg transition" title="Remove file">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Footer Action Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pt-1">
                <div class="text-[11px] text-slate-400 flex items-center gap-1">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>Only TL can upload shared media to Google Drive</span>
                </div>
                <button type="submit" class="w-full sm:w-auto justify-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition shadow-md shadow-indigo-600/20 flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Post Thought</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Feed Stream Header -->
    <div class="flex items-center justify-between pt-2">
        <div class="flex items-center gap-2">
            <h3 class="font-black text-slate-900 text-base">Recent Thoughts & Team Stream</h3>
            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-bold">{{ $thoughts->total() }}</span>
        </div>
        <button type="button" onclick="window.location.reload()" class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 rounded-xl transition flex items-center gap-1 shadow-2xs cursor-pointer">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
            <span>Refresh</span>
        </button>
    </div>

    <!-- Thoughts Feed List -->
    <div class="space-y-4">
        @forelse($thoughts as $thought)
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 transition hover:border-slate-300 relative group">
                <!-- Top Row: Member info, timestamp, and actions -->
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3">
                        @if($thought->user->avatar_url)
                            <img src="{{ $thought->user->avatar_url }}" alt="{{ $thought->user->name }}" class="w-10 h-10 rounded-2xl object-cover shadow-xs shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-2xl {{ $thought->user->isTL() ? 'bg-indigo-600' : 'bg-slate-700' }} text-white font-black text-sm flex items-center justify-center shadow-xs shrink-0">
                                {{ strtoupper(substr($thought->user->name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-slate-900 text-sm">{{ $thought->user->name }}</span>
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200/80 flex items-center gap-1">
                                    <i data-lucide="briefcase" class="w-3 h-3 text-indigo-500"></i>
                                    <span>{{ $thought->user->designation ?: ($thought->user->isTL() ? 'Team Lead' : 'Staff Member') }}</span>
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold text-slate-500 bg-slate-100">
                                    {{ $thought->user->isTL() ? 'Team Lead' : 'Member' }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-1 flex-wrap font-medium">
                                <span class="inline-flex items-center gap-1 text-slate-600 font-semibold">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $thought->created_at->format('d M Y') }}</span>
                                </span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="inline-flex items-center gap-1 text-slate-600 font-semibold">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $thought->created_at->format('h:i A') }}</span>
                                </span>
                                <span class="text-slate-400 text-[10px]">({{ $thought->created_at->diffForHumans() }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Corner: Thought Delete (Strictly TL Only) -->
                    @if(auth()->user()->isTL())
                        <form method="POST" action="{{ route('thoughts.destroy', $thought) }}" onsubmit="return confirm('As Team Lead, do you want to permanently remove this thought & link from the team board?')" class="m-0">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer" title="Delete thought (TL Only)">
                                <i data-lucide="trash" class="w-4 h-4"></i>
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Text Content -->
                @if($thought->content)
                    <div class="text-xs sm:text-sm text-slate-800 leading-relaxed whitespace-pre-line mb-3 font-normal">
                        {{ $thought->content }}
                    </div>
                @endif

                <!-- Shared Link Card (Never expires until TL deletes) -->
                @if($thought->link_url)
                    <div class="mb-3.5 p-3 sm:p-3.5 rounded-2xl bg-indigo-50/60 border border-indigo-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                <i data-lucide="link-2" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Shared Reference Link</span>
                                    <span class="inline-flex items-center gap-1 text-[9px] font-extrabold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md">
                                        <i data-lucide="infinity" class="w-3 h-3"></i> Never Expires
                                    </span>
                                </div>
                                <a href="{{ $thought->link_url }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-slate-900 hover:text-indigo-600 hover:underline truncate block mt-0.5 max-w-xs sm:max-w-md">
                                    {{ $thought->link_url }}
                                </a>
                            </div>
                        </div>
                        <a href="{{ $thought->link_url }}" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto justify-center px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-xs shrink-0 cursor-pointer">
                            <span>Open Link</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                @endif

                <!-- Media Content: Image / Video / Drive Sync Status -->
                @if($thought->hasDriveSync() || $thought->hasLocalMedia() || $thought->isExpired())
                    <div class="mt-3 p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <!-- Media Status Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    @if($thought->media_type === 'video')
                                        <i data-lucide="video" class="w-4 h-4 text-indigo-600"></i>
                                        <span>Attached Video</span>
                                    @else
                                        <i data-lucide="image" class="w-4 h-4 text-indigo-600"></i>
                                        <span>Attached Photo</span>
                                    @endif
                                </span>
                                @if($thought->media_original_name)
                                    <span class="text-[11px] text-slate-400 truncate max-w-xs font-medium">({{ $thought->media_original_name }})</span>
                                @endif
                            </div>

                            <!-- Badges: Drive Synced vs Local Storage vs Expired -->
                            <div>
                                @if($thought->hasDriveSync())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                                        <i data-lucide="cloud" class="w-3 h-3 text-emerald-600"></i>
                                        <span>Saved to Google Drive</span>
                                    </span>
                                @elseif($thought->isExpired())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300">
                                        <i data-lucide="alert-circle" class="w-3 h-3 text-rose-600"></i>
                                        <span>Expired from Local Storage (7 Days)</span>
                                    </span>
                                @else
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                            <i data-lucide="hard-drive" class="w-3 h-3 text-amber-700"></i>
                                            <span>Local Server Storage</span>
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200/60">
                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                            <span>Expires in {{ $thought->daysRemaining() }} day(s)</span>
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Media Display / Viewer -->
                        @if($thought->hasDriveSync())
                            <!-- Synced to Google Drive View -->
                            <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-sm shrink-0">
                                        <i data-lucide="cloud-check" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs font-black text-emerald-950 block">Google Drive Cloud Storage</span>
                                        <span class="text-[11px] text-emerald-800">
                                            Approved and uploaded by Team Lead. Permanently stored in company Drive.
                                        </span>
                                    </div>
                                </div>

                                <a href="{{ $thought->drive_url }}" target="_blank" rel="noopener noreferrer" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-emerald-600/20 self-start sm:self-auto shrink-0">
                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    <span>Open on Google Drive</span>
                                </a>
                            </div>

                        @elseif($thought->hasLocalMedia())
                            <!-- Local Media View (Image or Video) -->
                            <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-950 flex items-center justify-center max-h-96">
                                @if($thought->media_type === 'image')
                                    <a href="/{{ $thought->media_path }}" target="_blank" class="block w-full text-center">
                                        <img src="/{{ $thought->media_path }}" alt="Shared thought media" class="max-h-96 w-full object-contain mx-auto transition hover:opacity-95">
                                    </a>
                                @elseif($thought->media_type === 'video')
                                    <video src="/{{ $thought->media_path }}" controls class="max-h-96 w-full object-contain"></video>
                                @endif
                            </div>

                            <!-- TL Upload to Drive Action Bar -->
                            <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-amber-50/60 p-3.5 rounded-2xl border border-amber-200">
                                <div class="flex items-start sm:items-center gap-2">
                                    <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5 sm:mt-0"></i>
                                    <p class="text-[11px] text-amber-900 leading-tight">
                                        <strong>Local File:</strong> Will be permanently deleted in 7 days unless uploaded to Google Drive.
                                    </p>
                                </div>

                                @if(auth()->user()->isTL())
                                    <form method="POST" action="{{ route('thoughts.drive.upload', $thought) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold transition shadow-md shadow-emerald-600/20 flex items-center gap-1.5 cursor-pointer shrink-0">
                                            <i data-lucide="cloud-upload" class="w-4 h-4"></i>
                                            <span>Upload to Google Drive</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-500 italic">
                                        (Awaiting Team Lead upload to Drive)
                                    </span>
                                @endif
                            </div>

                        @elseif($thought->isExpired())
                            <!-- Expired State Notice -->
                            <div class="p-3.5 bg-slate-100 rounded-2xl border border-dashed border-slate-300 text-center text-xs text-slate-500">
                                <i data-lucide="clock-alert" class="w-5 h-5 mx-auto mb-1 text-slate-400"></i>
                                <span>This file was stored locally and has automatically expired and been cleaned up after 7 days without Team Lead cloud upload.</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-3xl p-12 text-center border border-slate-200">
                <div class="w-14 h-14 rounded-3xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3 shadow-xs">
                    <i data-lucide="messages-square" class="w-7 h-7"></i>
                </div>
                <h4 class="font-bold text-slate-900 text-base">No Thoughts Shared Yet</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                    Be the first team member to share an update, reference link, idea, or media with the team above!
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($thoughts->hasPages())
        <div class="pt-4 flex justify-center">
            {{ $thoughts->links() }}
        </div>
    @endif
</div>

<script>
function thoughtComposer() {
    return {
        showLink: false,
        linkUrl: '',
        fileName: '',
        fileSize: '',
        fileType: '',
        previewUrl: null,

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.fileName = file.name;
            this.fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';

            if (file.type.startsWith('image/')) {
                this.fileType = 'image';
            } else if (file.type.startsWith('video/')) {
                this.fileType = 'video';
            } else {
                this.fileType = 'unknown';
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewUrl = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        clearFile() {
            this.previewUrl = null;
            this.fileName = '';
            this.fileSize = '';
            this.fileType = '';
            const fileInput = document.querySelector('input[type="file"][name="media"]');
            if (fileInput) fileInput.value = '';
        }
    };
}
</script>
@endsection
