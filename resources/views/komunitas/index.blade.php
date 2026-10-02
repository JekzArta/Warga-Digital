@extends('layouts.app', ['title' => ($pageTitle ?? 'Ruang Komunitas') . ' — Warga Digital', 'pageTitle' => $pageTitle ?? 'Ruang Komunitas'])

@section('content')
<div class="space-y-6" x-data="{ 
    activeTab: '{{ $activeTab }}',
    showPengumumanModal: false,
    showThreadModal: false,
    showPembaruanModal: false,
    showDeactivateModal: false,
    showLinkForumModal: false,
    selectedAnnouncement: null
}">
    <!-- Top Header & Scope Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 font-bold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Ruang Komunitas</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Platform terstruktur untuk pengumuman resmi, obrolan santai, dan musyawarah warga.</p>
        </div>

        <!-- Scope Switcher (RT vs RW) -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-100/80 rounded-xl border border-slate-200/60 self-start sm:self-auto">
            @if(auth()->user()->rt_id)
            <a href="{{ route('komunitas.index', ['scope' => 'rt', 'tab' => $activeTab]) }}" 
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $requestedScope === 'rt' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                Lingkup RT ({{ auth()->user()->rt?->kode_rt ?? 'RT 05' }})
            </a>
            @endif
            <a href="{{ route('komunitas.index', ['scope' => 'rw', 'tab' => $activeTab]) }}" 
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $requestedScope === 'rw' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                Kawasan RW ({{ auth()->user()->rw?->kode_rw ?? auth()->user()->rt?->rw?->kode_rw ?? 'RW 03' }})
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200/80 text-rose-800 text-sm shadow-xs">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Layer Navigation Tabs -->
    <div class="border-b border-slate-200/80 flex items-center gap-2">
        <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'pengumuman']) }}"
           class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'pengumuman' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            <span>Pengumuman Resmi</span>
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">{{ $announcements->count() }}</span>
        </a>

        <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'chat']) }}"
           class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'chat' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <span>Chat Bebas</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 animate-pulse">Live</span>
        </a>

        <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'forum']) }}"
           class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'forum' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
            </svg>
            <span>Forum Warga</span>
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">{{ $threads->total() }}</span>
        </a>
    </div>

    <!-- ==================== TAB 1: PENGUMUMAN RESMI ==================== -->
    @if($activeTab === 'pengumuman')
    <div class="space-y-4">
        <!-- Action Header -->
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Daftar Pengumuman {{ strtoupper($requestedScope) }}</h2>
            @if($canPublishAnnouncement)
            <button @click="showPengumumanModal = true" type="button" class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Terbitkan Pengumuman</span>
            </button>
            @endif
        </div>

        @if($announcements->isEmpty())
        <div class="p-8 text-center bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <p class="text-sm text-slate-500">Belum ada pengumuman resmi yang aktif untuk lingkup ini.</p>
        </div>
        @else
        <div class="space-y-4">
            @foreach($announcements as $anc)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden transition-all hover:border-slate-300 {{ $anc->tipe === 'MENDESAK' ? 'border-l-4 !border-l-rose-500' : ($anc->tipe === 'PENTING' ? 'border-l-4 !border-l-amber-500' : 'border-l-4 !border-l-emerald-600') }}">
                <div class="p-5">
                    <!-- Top metadata & Badges -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 mb-2.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Urgency badge -->
                            @if($anc->tipe === 'MENDESAK')
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200/80 uppercase">Mendesak</span>
                            @elseif($anc->tipe === 'PENTING')
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-50 text-amber-700 border border-amber-200/80 uppercase">Penting</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 uppercase">Info</span>
                            @endif

                            @if($anc->is_pinned)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-500/10 text-amber-700 border border-amber-300/40">
                                    <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                                    </svg>
                                    Disematkan
                                </span>
                            @endif

                            @if($anc->expired_at)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-md bg-slate-100 text-slate-600 border border-slate-200/70" title="Masa berlaku pengumuman">
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Berlaku s/d {{ \Carbon\Carbon::parse($anc->expired_at)->translatedFormat('d F Y') }}</span>
                                </span>
                            @endif
                        </div>

                        <!-- Right Actions Header -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs text-slate-400 font-medium">{{ $anc->created_at?->diffForHumans() }}</span>

                            @if($canPublishAnnouncement)
                            <div class="flex items-center gap-1.5">
                                <!-- Sematkan (Toggle Pin) -->
                                <form action="{{ route('komunitas.pengumuman.toggle-pin', $anc->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            title="{{ $anc->is_pinned ? 'Lepas sematan pengumuman' : 'Sematkan pengumuman di posisi teratas' }}"
                                            class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold rounded-lg transition-all {{ $anc->is_pinned ? 'bg-amber-100 text-amber-800 hover:bg-amber-200 border border-amber-300/60' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200/60' }}">
                                        <svg class="w-3 h-3 {{ $anc->is_pinned ? 'text-amber-700' : 'text-slate-400' }}" fill="{{ $anc->is_pinned ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                        </svg>
                                        <span>{{ $anc->is_pinned ? 'Lepas' : 'Sematkan' }}</span>
                                    </button>
                                </form>

                                <!-- Buat Pembaruan (Hanya jika belum digantikan / dinonaktifkan) -->
                                @if($anc->canBeUpdated())
                                <button type="button" 
                                        @click="selectedAnnouncement = { id: {{ $anc->id }}, judul: '{{ addslashes($anc->judul) }}', tipe: '{{ $anc->tipe }}', konten: {{ json_encode($anc->konten) }}, expired_at: '{{ $anc->expired_at?->toDateString() }}', forum_thread_id: '{{ $anc->forum_thread_id }}' }; showPembaruanModal = true"
                                        title="Terbitkan pembaruan untuk pengumuman ini"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-all">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <span>Pembaruan</span>
                                </button>
                                @endif

                                <!-- Hubungkan ke Forum (Hanya jika belum punya Forum) -->
                                @if($anc->canLinkForum())
                                <button type="button"
                                        @click="selectedAnnouncement = { id: {{ $anc->id }}, judul: '{{ addslashes($anc->judul) }}' }; showLinkForumModal = true"
                                        title="Hubungkan pengumuman ini ke Forum Warga"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 transition-all">
                                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                    <span>Hubungkan Forum</span>
                                </button>
                                @endif

                                <!-- Nonaktifkan Pengumuman -->
                                <button type="button"
                                        @click="selectedAnnouncement = { id: {{ $anc->id }}, judul: '{{ addslashes($anc->judul) }}' }; showDeactivateModal = true"
                                        title="Nonaktifkan pengumuman resmi ini"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition-all">
                                    <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    <span>Nonaktifkan</span>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Title & Content -->
                    <h3 class="text-base font-bold text-slate-800 leading-snug mb-2">{{ $anc->judul }}</h3>
                    <p class="text-sm text-slate-600 whitespace-pre-line leading-relaxed">{{ $anc->konten }}</p>

                    <!-- Author footer -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-700">{{ $anc->author?->nama }}</span>
                            <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-medium text-[10px]">{{ $anc->author?->getHighestRoleBadge() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Relationship Strip: Pembaruan & Forum Link (Sesuai Aturan Bagian Q: Diletakkan sejajar/di atas Tanggapan) -->
                @if($anc->replaces_announcement_id || $anc->forum_thread_id)
                <div class="px-5 py-2.5 bg-stone-50/90 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 text-xs" x-data="{ showPrevPreview: false }">
                    <div class="flex items-center gap-2 flex-wrap">
                        @if($anc->previous)
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-100/70 text-emerald-800 font-semibold text-[11px]">
                                <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                                Pembaruan dari Pengumuman Sebelumnya
                            </span>
                            <button @click="showPrevPreview = !showPrevPreview" type="button" class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-900 underline underline-offset-2">
                                <span x-text="showPrevPreview ? '[ Tutup Versi Lama ]' : '[ Lihat Versi Sebelumnya ]'"></span>
                            </button>
                        </div>
                        @endif
                    </div>

                    @if($anc->forumThread)
                    <a href="{{ route('komunitas.forum.thread.show', $anc->forum_thread_id) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-1 bg-white hover:bg-slate-50 text-emerald-700 border border-emerald-200/80 rounded-lg text-xs font-semibold shadow-2xs transition-all self-start sm:self-auto">
                        <span>💬 Diskusikan di Forum</span>
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    @endif

                    <!-- Accordion Preview Versi Lama (Expandable History Section) -->
                    @if($anc->previous)
                    <div x-show="showPrevPreview" x-collapse class="w-full mt-2 p-3.5 bg-white rounded-xl border border-stone-200/80 text-xs shadow-2xs space-y-2.5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-800">Riwayat Pembaruan</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">Sudah digantikan oleh pembaruan terbaru</span>
                            </div>
                            @if($anc->previous->previous)
                            <span class="text-[11px] text-slate-500 font-medium">Versi sebelumnya tersedia</span>
                            @endif
                        </div>

                        <!-- Detail Ringkas Versi Sebelumnya [V2] -->
                        <div class="p-3 bg-stone-50/60 rounded-lg border border-slate-100 space-y-1.5">
                            <div class="flex items-center justify-between text-[11px] text-slate-500">
                                <span class="font-bold text-slate-800 text-xs">{{ $anc->previous->judul }}</span>
                                <span>{{ $anc->previous->created_at?->translatedFormat('d F Y, H:i') }} WIB</span>
                            </div>
                            <p class="text-slate-600 text-[11px] line-clamp-3 leading-relaxed">{{ $anc->previous->konten }}</p>
                            <div class="flex items-center justify-between pt-1 text-[11px] text-slate-400">
                                <span>💬 {{ $anc->previous->comments->count() }} Tanggapan warga pada versi ini</span>
                            </div>
                        </div>

                        <!-- Navigation Bar (Previous & Current/Latest indicator) -->
                        <div class="flex items-center justify-between pt-1">
                            <a href="{{ route('komunitas.pengumuman.show', $anc->previous->id) }}" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-all">
                                <span>← Buka Versi Sebelumnya</span>
                            </a>

                            <div class="text-[11px] text-emerald-700 font-semibold flex items-center gap-1">
                                <span>Versi terbaru</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Collapsible Comments Section (Alpine Accordion) -->
                <div x-data="{ openComments: false }" class="bg-slate-50/70 border-t border-slate-100 p-4">
                    <button @click="openComments = !openComments" type="button" class="flex items-center gap-2 text-xs font-semibold text-emerald-700 hover:text-emerald-800 transition-all">
                        <svg class="w-3.5 h-3.5 transition-transform" :class="openComments ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span>Tanggapan Warga ({{ $anc->comments->count() }})</span>
                    </button>

                    <div x-show="openComments" x-collapse class="mt-3 space-y-3 pt-2">
                        <!-- Comment list -->
                        @foreach($anc->comments as $cmt)
                        <div class="p-3 bg-white rounded-xl border border-slate-200/60 text-xs shadow-2xs">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-slate-800">{{ $cmt->author?->nama }}</span>
                                    <span class="text-[9px] px-1 rounded bg-slate-100 text-slate-600">{{ $cmt->author?->getHighestRoleBadge() }}</span>
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $cmt->created_at?->diffForHumans() }}</span>
                            </div>
                            <p class="text-slate-600">{{ $cmt->konten }}</p>
                        </div>
                        @endforeach

                        @if($anc->canReceiveComments())
                        <!-- Add Comment Form -->
                        <form action="{{ route('komunitas.pengumuman.komentar', $anc->id) }}" method="POST" class="pt-2">
                            @csrf
                            <div class="flex gap-2">
                                <input type="text" name="konten" required placeholder="Tuliskan tanggapan Anda..." class="flex-1 text-xs px-3 py-2 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all">Kirim</button>
                            </div>
                        </form>
                        @else
                        <div class="p-3 bg-amber-50/80 rounded-xl border border-amber-200/80 text-xs text-amber-800 flex items-center gap-2 mt-2">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span>Tanggapan ditutup karena pengumuman ini sudah digantikan atau dinonaktifkan.</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- ==================== MODAL 1: BUAT PENGUMUMAN BARU ==================== -->
        @if($canPublishAnnouncement)
        <div x-show="showPengumumanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
            <div @click.away="showPengumumanModal = false" class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-slate-200 max-h-[90vh] overflow-y-auto">
                <h3 class="text-base font-bold text-slate-800 mb-4">Terbitkan Pengumuman Resmi ({{ strtoupper($requestedScope) }})</h3>
                <form action="{{ route('komunitas.pengumuman.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="scope_type" value="{{ $requestedScope }}">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Pengumuman</label>
                        <input type="text" name="judul" required placeholder="Contoh: Kerja Bakti Hari Minggu" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Urgensi</label>
                        <select name="tipe" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                            <option value="INFO">INFO (Netral / Informasi Biasa)</option>
                            <option value="PENTING">PENTING (Harap Menjadi Perhatian)</option>
                            <option value="MENDESAK">MENDESAK (Darurat / Tindakan Cepat)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Berlaku sampai (Opsional)</label>
                        <input type="date" name="expired_at" min="{{ now()->toDateString() }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                        <p class="text-[10px] text-slate-400 mt-1">Hari terakhir pengumuman tetap aktif di beranda warga (otomatis kadaluarsa keesokan harinya).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Hubungkan ke Forum Warga (Opsional)</label>
                        <select name="forum_thread_id" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Tanpa Forum Terkait --</option>
                            @foreach($availableThreads as $thr)
                                <option value="{{ $thr->id }}">{{ $thr->judul }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Pengumuman</label>
                        <textarea name="konten" rows="4" required placeholder="Tuliskan isi rincian pengumuman..." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_pinned" id="is_pinned" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="is_pinned" class="text-xs text-slate-600 font-medium">Sematkan di posisi teratas (Pin)</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showPengumumanModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs">Terbitkan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL 2: BUAT PEMBARUAN PENGUMUMAN ==================== -->
        <div x-show="showPembaruanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
            <div @click.away="showPembaruanModal = false" class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-slate-200 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center gap-2.5 mb-3">
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Buat Pembaruan Pengumuman</h3>
                        <p class="text-[11px] text-slate-400">Versi baru akan menggantikan pengumuman sebelumnya secara resmi.</p>
                    </div>
                </div>

                <!-- Konteks Source Versi Lama -->
                <div class="p-3 bg-stone-50 border border-stone-200/80 rounded-xl text-xs mb-4">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pembaruan dari:</span>
                    <p class="font-bold text-slate-700 mt-0.5" x-text="selectedAnnouncement?.judul"></p>
                </div>

                <form :action="`{{ url('/komunitas/pengumuman') }}/${selectedAnnouncement?.id}/pembaruan`" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Pembaruan</label>
                        <input type="text" name="judul" required :value="selectedAnnouncement?.judul" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Urgensi</label>
                        <select name="tipe" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500" :value="selectedAnnouncement?.tipe">
                            <option value="INFO">INFO (Netral / Informasi Biasa)</option>
                            <option value="PENTING">PENTING (Harap Menjadi Perhatian)</option>
                            <option value="MENDESAK">MENDESAK (Darurat / Tindakan Cepat)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Berlaku sampai (Opsional)</label>
                        <input type="date" name="expired_at" :value="selectedAnnouncement?.expired_at" min="{{ now()->toDateString() }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Hubungkan ke Forum Warga (Opsional)</label>
                        <select name="forum_thread_id" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500" :value="selectedAnnouncement?.forum_thread_id">
                            <option value="">-- Tanpa Forum Terkait --</option>
                            @foreach($availableThreads as $thr)
                                <option value="{{ $thr->id }}">{{ $thr->judul }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Pembaruan Pengumuman</label>
                        <textarea name="konten" rows="5" required :value="selectedAnnouncement?.konten" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_pinned" id="is_pinned_pembaruan" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="is_pinned_pembaruan" class="text-xs text-slate-600 font-medium">Sematkan di posisi teratas (Pin)</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showPembaruanModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs">Terbitkan Pembaruan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL 3: NONAKTIFKAN PENGUMUMAN ==================== -->
        <div x-show="showDeactivateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
            <div @click.away="showDeactivateModal = false" class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Nonaktifkan Pengumuman?</h3>
                        <p class="text-xs text-slate-500 font-medium" x-text="selectedAnnouncement?.judul"></p>
                    </div>
                </div>

                <p class="text-xs text-slate-500 mb-4 leading-relaxed bg-stone-50 p-3 rounded-xl border border-stone-200/80">
                    Pengumuman ini tidak akan lagi tampil pada active feed warga. Seluruh riwayat data dan tanggapan tetap disimpan utuh untuk akuntabilitas dan audit.
                </p>

                <form :action="`{{ url('/komunitas/pengumuman') }}/${selectedAnnouncement?.id}/deactivate`" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Penonaktifan <span class="text-rose-500">*</span></label>
                        <textarea name="alasan" rows="3" required placeholder="Contoh: Jadwal kegiatan dibatalkan karena hujan lebat..." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-rose-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="showDeactivateModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-xs">Nonaktifkan Pengumuman</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL 4: HUBUNGKAN KE FORUM WARGA ==================== -->
        <div x-show="showLinkForumModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
            <div @click.away="showLinkForumModal = false" class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Hubungkan ke Forum Warga</h3>
                        <p class="text-xs text-slate-500 font-medium" x-text="selectedAnnouncement?.judul"></p>
                    </div>
                </div>

                <form :action="`{{ url('/komunitas/pengumuman') }}/${selectedAnnouncement?.id}/link-forum`" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Thread Forum yang Sesuai</label>
                        <select name="forum_thread_id" required class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Pilih Thread Forum Terkait --</option>
                            @foreach($availableThreads as $thr)
                                <option value="{{ $thr->id }}">{{ $thr->judul }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Warga akan melihat tombol pintasan menuju ruang diskusi forum pada pengumuman ini.</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="showLinkForumModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs">Hubungkan</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- ==================== TAB 2: CHAT BEBAS REAL-TIME ==================== -->
    @if($activeTab === 'chat')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-[600px]"
         x-data="{
             messages: [],
             newMessage: '',
             loading: true,
             connected: false,
             sending: false,
             scopeType: '{{ $requestedScope }}',
             scopeId: {{ $scopeId }},
             pollInterval: null,
             async init() {
                 await this.loadMessages();
                 this.setupEcho();
                 this.startPollingFallback();
             },
             async loadMessages() {
                 try {
                     const res = await fetch(`{{ route('komunitas.chat.messages') }}?scope_type=${this.scopeType}`);
                     const data = await res.json();
                     if (data.status === 'success' && Array.isArray(data.data)) {
                         this.messages = data.data;
                     }
                 } catch (e) {
                     console.error('Error fetching chat messages', e);
                 } finally {
                     this.loading = false;
                     this.scrollToBottom();
                 }
             },
             setupEcho() {
                 if (window.Echo) {
                     const channelName = `chat.${this.scopeType}.${this.scopeId}`;
                     window.Echo.private(channelName)
                         .listen('.ChatMessageSent', (e) => this.handleIncomingMessage(e))
                         .listen('ChatMessageSent', (e) => this.handleIncomingMessage(e));

                     // Pantau status koneksi socket Reverb
                     if (window.Echo.connector && window.Echo.connector.pusher) {
                         const pusher = window.Echo.connector.pusher;
                         if (pusher.connection.state === 'connected') {
                             this.connected = true;
                         }
                         pusher.connection.bind('connected', () => { this.connected = true; });
                         pusher.connection.bind('unavailable', () => { this.connected = false; });
                         pusher.connection.bind('failed', () => { this.connected = false; });
                         pusher.connection.bind('disconnected', () => { this.connected = false; });
                     }
                 }
             },
             handleIncomingMessage(incoming) {
                 if (!incoming || !incoming.id) return;
                 if (!this.messages.some(m => m.id === incoming.id)) {
                     this.messages.push(incoming);
                     this.scrollToBottom();
                 }
             },
             startPollingFallback() {
                 // Polling fallback jika koneksi websocket terhalang firewall/offline
                 this.pollInterval = setInterval(async () => {
                     if (!this.connected) {
                         await this.syncLatestMessages();
                     }
                 }, 8000);
             },
             async syncLatestMessages() {
                 try {
                     const res = await fetch(`{{ route('komunitas.chat.messages') }}?scope_type=${this.scopeType}`);
                     const data = await res.json();
                     if (data.status === 'success' && Array.isArray(data.data)) {
                         let hasNew = false;
                         data.data.forEach(incoming => {
                             if (!this.messages.some(m => m.id === incoming.id)) {
                                 this.messages.push(incoming);
                                 hasNew = true;
                             }
                         });
                         if (hasNew) this.scrollToBottom();
                     }
                 } catch (e) {
                     // Polling fallback diam tanpa interupsi UI
                 }
             },
             async sendMessage() {
                 const text = this.newMessage.trim();
                 if (!text || this.sending) return;
                 this.sending = true;

                 const headers = {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                 };

                 if (window.Echo && typeof window.Echo.socketId === 'function') {
                     const sockId = window.Echo.socketId();
                     if (sockId) headers['X-Socket-ID'] = sockId;
                 }

                 try {
                     const res = await fetch(`{{ route('komunitas.chat.send') }}`, {
                         method: 'POST',
                         headers: headers,
                         body: JSON.stringify({
                             scope_type: this.scopeType,
                             konten: text
                         })
                     });
                     const data = await res.json();
                     if (res.ok && data.status === 'success' && data.data) {
                         this.handleIncomingMessage(data.data);
                         this.newMessage = '';
                     }
                 } catch (e) {
                     console.error('Failed to send message', e);
                 } finally {
                     this.sending = false;
                 }
             },
             scrollToBottom() {
                 this.$nextTick(() => {
                     const el = this.$refs.chatBox;
                     if (el) el.scrollTop = el.scrollHeight;
                 });
             }
         }">
        <!-- Chat Header -->
        <div class="p-4 border-b border-stone-200/80 flex items-center justify-between bg-white">
            <div class="flex items-center gap-3">
                <div class="w-2.5 h-2.5 rounded-full" :class="connected ? 'bg-emerald-500 animate-pulse' : 'bg-amber-400'"></div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800">Obrolan Santai {{ strtoupper($requestedScope) }}</h3>
                    <p class="text-[10px] text-slate-400" x-text="connected ? 'Terkoneksi ke Laravel Reverb (Real-Time)' : 'Menghubungkan ke jaringan obrolan...'"></p>
                </div>
            </div>
            <span class="text-[11px] font-bold text-emerald-800 bg-[#EAF5EC] border border-[#BFDFCA] px-3 py-1 rounded-full shadow-2xs">
                Scope: {{ strtoupper($requestedScope) }}
            </span>
        </div>

        <!-- Chat Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-[#FAF7F4]" x-ref="chatBox">
            <template x-if="loading">
                <div class="h-full flex items-center justify-center py-10 text-xs text-slate-400">Memuat percakapan...</div>
            </template>

            <!-- Chat Empty State -->
            <template x-if="!loading && messages.length === 0">
                <div class="h-full flex flex-col items-center justify-center py-16 px-4 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 shadow-2xs border border-emerald-100/60">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-800 mb-1">Belum Ada Percakapan</h4>
                    <p class="text-xs text-slate-500 max-w-xs leading-relaxed">Jadilah warga pertama yang memulai obrolan santai di ruang silaturahmi ini.</p>
                </div>
            </template>

            <!-- Chat Messages List -->
            <template x-for="msg in messages" :key="msg.id">
                <div class="flex flex-col" :class="msg.author_id === {{ auth()->id() }} ? 'items-end' : 'items-start'">
                    <div class="flex items-center gap-1.5 mb-1 px-1 text-[11px] text-slate-500">
                        <span class="font-bold text-slate-700" x-text="msg.author?.nama || 'Warga'"></span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded bg-slate-200/70 text-slate-700 font-semibold" x-text="msg.author?.role_badge || 'Warga'"></span>
                        <span class="text-[10px] text-slate-400" x-text="msg.created_at_human || 'baru saja'"></span>
                    </div>
                    <div class="max-w-[78%] px-4 py-2.5 rounded-2xl text-xs leading-relaxed shadow-xs transition-all"
                         :class="msg.author_id === {{ auth()->id() }} ? 'bg-emerald-600 text-white rounded-tr-xs' : 'bg-white text-slate-800 rounded-tl-xs border border-stone-200/80'"
                         :style="msg.author_id === {{ auth()->id() }} ? 'background-color: #059669 !important; color: #ffffff !important;' : 'background-color: #ffffff !important; color: #1e293b !important; border-color: #e7e5e4 !important;'">
                        <span x-text="msg.konten" class="whitespace-pre-line break-words font-medium"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Chat Input Form -->
        <form @submit.prevent="sendMessage" class="p-3 bg-white border-t border-stone-200/80 flex gap-2">
            <input type="text" x-model="newMessage" placeholder="Ketik pesan santai..." 
                   class="flex-1 text-xs px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
            <button type="submit" :disabled="sending || !newMessage.trim()"
                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                <span>Kirim</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>
    </div>
    @endif

    <!-- ==================== TAB 3: FORUM WARGA ==================== -->
    @if($activeTab === 'forum')
    <div class="space-y-4">
        <!-- Action Header & Category Filter -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <!-- Category Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'forum']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all {{ is_null($selectedCategoryId) ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                    Semua Kategori
                </a>
                @foreach($forumCategories as $cat)
                <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'forum', 'category_id' => $cat->id]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all {{ $selectedCategoryId === $cat->id ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                    {{ $cat->nama }}
                </a>
                @endforeach
            </div>

            <button @click="showThreadModal = true" type="button" class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buat Usulan Baru</span>
            </button>
        </div>

        <!-- Thread List -->
        @if($threads->isEmpty())
        <div class="p-8 text-center bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <p class="text-sm text-slate-500">Belum ada diskusi topik pada kategori atau lingkup wilayah ini.</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($threads as $th)
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1.5 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                            {{ $th->category?->nama }}
                        </span>

                        @if($th->is_pinned)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-700 border border-amber-300/40">
                            Sematkan
                        </span>
                        @endif

                        @if($th->status === 'closed')
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                            Ditutup
                        </span>
                        @else
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Aktif
                        </span>
                        @endif
                    </div>

                    <a href="{{ route('komunitas.forum.thread.show', $th->id) }}" class="text-base font-bold text-slate-800 hover:text-emerald-700 transition-all block leading-snug">
                        {{ $th->judul }}
                    </a>

                    <!-- Thread Content Excerpt / Snippet (Task 5) -->
                    <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                        {{ \Illuminate\Support\Str::limit($th->konten, 180) }}
                    </p>

                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <span class="font-medium text-slate-600">{{ $th->author?->nama }}</span>
                        @if(!empty($th->author_role_snapshot))
                            <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 text-[10px]">{{ implode(', ', $th->author_role_snapshot) }}</span>
                        @endif
                        <span>•</span>
                        <span>{{ $th->created_at?->diffForHumans() }}</span>
                    </div>
                </div>

                <!-- Reply count badge -->
                <div class="flex items-center gap-2 shrink-0">
                    <div class="flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200/60 text-xs font-semibold text-slate-600">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span>{{ $th->posts_count }} Tanggapan</span>
                    </div>
                    <a href="{{ route('komunitas.forum.thread.show', $th->id) }}" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach

            <div class="pt-2">
                {{ $threads->links() }}
            </div>
        </div>
        @endif

        <!-- Modal Buat Usulan Thread Baru -->
        <div x-show="showThreadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
            <div @click.away="showThreadModal = false" class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-slate-200">
                <h3 class="text-base font-bold text-slate-800 mb-4">Buat Usulan Diskusi Baru ({{ strtoupper($requestedScope) }})</h3>
                <form action="{{ route('komunitas.forum.thread.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Topik</label>
                        <select name="category_id" required class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($forumCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Usulan</label>
                        <input type="text" name="judul" required placeholder="Contoh: Pengadaan Tempat Sampah Terpilah" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Uraian Usulan / Pertanyaan</label>
                        <textarea name="konten" rows="4" required placeholder="Jelaskan gagasan atau usulan Anda..." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 text-[11px] text-slate-500">
                        <span class="font-bold text-slate-700">Snapshot Jabatan:</span> Postingan Anda akan dikunci dengan status identitas: <span class="font-bold text-emerald-700">{{ auth()->user()->getHighestRoleBadge() }}</span>.
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showThreadModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs">Publikasikan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
