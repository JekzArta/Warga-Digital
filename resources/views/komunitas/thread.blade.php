@extends('layouts.app', ['title' => $thread->judul . ' — Forum Warga'])

@section('content')
<div class="space-y-6" x-data="{ 
    showModerateModal: false,
    moderateAction: '',
    moderateTitle: '',
    openModerate(action, title) {
        this.moderateAction = action;
        this.moderateTitle = title;
        this.showModerateModal = true;
    }
}">
    <!-- Back Button & Breadcrumbs -->
    <div class="flex items-center justify-between">
        <a href="{{ route('komunitas.index', ['scope' => $thread->category->scope_type, 'tab' => 'forum', 'category_id' => $thread->category_id]) }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Forum {{ strtoupper($thread->category->scope_type) }}</span>
        </a>

        <!-- Moderator Action Toolbar -->
        @if($canModerate)
        <div class="flex items-center gap-1.5 p-1 bg-white rounded-xl border border-slate-200 shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2">Moderasi:</span>
            
            @if($thread->is_pinned)
                <button type="button" @click="openModerate('unpin', 'Lepaskan Sematan (Unpin)')" class="px-2.5 py-1 text-xs font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition-all">Unpin</button>
            @else
                <button type="button" @click="openModerate('pin', 'Sematkan Thread (Pin)')" class="px-2.5 py-1 text-xs font-medium rounded-lg text-amber-700 bg-amber-50 hover:bg-amber-100 transition-all">Sematkan</button>
            @endif

            @if($thread->status === 'closed')
                <button type="button" @click="openModerate('reopen', 'Buka Kembali Diskusi (Reopen)')" class="px-2.5 py-1 text-xs font-medium rounded-lg text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-all">Buka Diskusi</button>
            @else
                <button type="button" @click="openModerate('close', 'Tutup Diskusi (Close)')" class="px-2.5 py-1 text-xs font-medium rounded-lg text-slate-700 hover:bg-slate-100 transition-all">Tutup Diskusi</button>
            @endif

            <button type="button" @click="openModerate('hapus', 'Hapus Thread (Soft Delete)')" class="px-2.5 py-1 text-xs font-medium rounded-lg text-rose-700 hover:bg-rose-50 transition-all">Hapus</button>
        </div>
        @endif
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-sm flex items-center gap-2 shadow-xs">
        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200/80 text-rose-800 text-sm shadow-xs">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Main Thread Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
        <!-- Badges & Status -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-slate-100 text-slate-700">
                    {{ $thread->category?->nama }}
                </span>

                @if($thread->is_pinned)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-bold bg-amber-500/10 text-amber-700 border border-amber-300/40">
                    <svg class="w-3.5 h-3.5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                    </svg>
                    Disematkan
                </span>
                @endif

                @if($thread->status === 'closed')
                <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">
                    Diskusi Ditutup
                </span>
                @else
                <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Aktif
                </span>
                @endif
            </div>

            <span class="text-xs text-slate-400 font-medium">{{ $thread->created_at?->diffForHumans() }}</span>
        </div>

        <!-- Title -->
        <h1 class="text-xl font-bold text-slate-900 leading-snug">{{ $thread->judul }}</h1>

        <!-- Author Banner with Role Snapshot -->
        <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50/80 border border-slate-200/60">
            <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white font-bold flex items-center justify-center text-sm shadow-xs">
                {{ substr($thread->author?->nama ?? 'W', 0, 1) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-800">{{ $thread->author?->nama }}</span>
                    @if(!empty($thread->author_role_snapshot))
                        <span class="px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">{{ implode(', ', $thread->author_role_snapshot) }}</span>
                    @endif
                </div>
                <span class="text-[11px] text-slate-400">Penulis Topik Usulan</span>
            </div>
        </div>

        <!-- Thread Content -->
        <div class="text-sm text-slate-700 whitespace-pre-line leading-relaxed pt-2">
            {{ $thread->konten }}
        </div>
    </div>

    <!-- Replies Header -->
    <div class="flex items-center justify-between pt-2">
        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
            <span>Tanggapan & Diskusi Warga</span>
            <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-xs font-bold">{{ $thread->posts->count() }}</span>
        </h2>
    </div>

    <!-- Replies List -->
    <div class="space-y-3">
        @forelse($thread->posts as $post)
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs">
                        {{ substr($post->author?->nama ?? 'W', 0, 1) }}
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-800">{{ $post->author?->nama }}</span>
                        @if(!empty($post->author_role_snapshot))
                            <span class="ml-1 px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 text-[9px] font-bold">{{ implode(', ', $post->author_role_snapshot) }}</span>
                        @endif
                    </div>
                </div>
                <span class="text-[11px] text-slate-400">{{ $post->created_at?->diffForHumans() }}</span>
            </div>

            <div class="text-xs text-slate-700 whitespace-pre-line leading-relaxed pl-9">
                {{ $post->konten }}
            </div>

            <!-- Nested Replies (if any) -->
            @if($post->replies && $post->replies->isNotEmpty())
            <div class="pl-9 pt-2 space-y-2">
                @foreach($post->replies as $reply)
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 text-xs space-y-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-slate-800">{{ $reply->author?->nama }}</span>
                            @if(!empty($reply->author_role_snapshot))
                                <span class="px-1 py-0.2 rounded bg-slate-200 text-slate-700 text-[9px] font-bold">{{ implode(', ', $reply->author_role_snapshot) }}</span>
                            @endif
                        </div>
                        <span class="text-[10px] text-slate-400">{{ $reply->created_at?->diffForHumans() }}</span>
                    </div>
                    <p class="text-slate-600">{{ $reply->konten }}</p>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <div class="p-8 text-center bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <p class="text-xs text-slate-500">Belum ada tanggapan pada usulan ini. Jadilah yang pertama memberikan pendapat!</p>
        </div>
        @endforelse
    </div>

    <!-- Reply Input Box -->
    @if($thread->status === 'closed')
    <div class="p-4 rounded-xl bg-slate-100 border border-slate-200 text-slate-600 text-xs text-center font-medium shadow-xs">
        🔒 Diskusi pada topik usulan ini telah resmi ditutup oleh pengurus. Tanggapan baru tidak dapat dikirimkan.
    </div>
    @else
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h3 class="text-xs font-bold text-slate-800 mb-2">Tuliskan Tanggapan Anda</h3>
        <form action="{{ route('komunitas.forum.post.store', $thread->id) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <textarea name="konten" rows="3" required placeholder="Tuliskan argumen atau masukan konstruktif Anda..." class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-[11px] text-slate-400">
                    Identitas terkunci: <strong class="text-slate-700">{{ auth()->user()->nama }} ({{ auth()->user()->getHighestRoleBadge() }})</strong>
                </span>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all">
                    Kirim Tanggapan
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Moderation Modal -->
    @if($canModerate)
    <div x-show="showModerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
        <div @click.away="showModerateModal = false" class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200">
            <h3 class="text-base font-bold text-slate-800 mb-1" x-text="moderateTitle"></h3>
            <p class="text-xs text-slate-500 mb-4">Setiap tindakan moderasi wajib mencantumkan alasan resmi yang akan dicatat di Audit Trail sistem.</p>
            
            <form action="{{ route('komunitas.forum.thread.moderate', $thread->id) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="aksi" :value="moderateAction">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Tindakan Moderasi (Wajib)</label>
                    <textarea name="alasan" rows="3" required minlength="5" placeholder="Contoh: Diskusi telah mencapai mufakat dalam rapat fisik..." class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showModerateModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-xs" x-text="'Terapkan ' + moderateAction"></button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
