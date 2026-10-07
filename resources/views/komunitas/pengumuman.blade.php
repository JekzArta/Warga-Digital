@extends('layouts.app', ['title' => $pageTitle, 'pageTitle' => 'Pengumuman Resmi'])

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Back Button / Breadcrumbs -->
    <div class="flex items-center justify-between">
        <a href="{{ route('komunitas.index', ['scope' => $announcement->scope_type, 'tab' => 'pengumuman']) }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-stone-500 hover:text-stone-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Daftar Pengumuman {{ strtoupper($announcement->scope_type) }}</span>
        </a>

        <div class="flex items-center gap-2">
            @if($announcement->is_replaced)
                <span class="px-3 py-1 rounded-xl text-xs font-semibold bg-amber-50 text-amber-900 border border-amber-200">
                    Versi Sebelumnya
                </span>
            @elseif($announcement->is_deactivated)
                <span class="px-3 py-1 rounded-xl text-xs font-semibold bg-rose-50 text-rose-900 border border-rose-200">
                    Dinonaktifkan
                </span>
            @else
                <span class="px-3 py-1 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-900 border border-emerald-200 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Versi terbaru
                </span>
            @endif
        </div>
    </div>

    <!-- Alert Banner jika melihat Versi Lama yang sudah digantikan -->
    @if($announcement->is_replaced)
    <div class="p-4 rounded-2xl bg-amber-50/90 border border-amber-200/90 text-amber-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3">
            <span class="p-2 rounded-xl bg-amber-100 text-amber-800 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </span>
            <div>
                <div class="font-bold text-sm">Versi Sebelumnya</div>
                <div class="text-xs text-amber-800 leading-relaxed">Pengumuman ini sudah digantikan oleh pembaruan terbaru. Informasi di bawah merupakan arsip riwayat resmi.</div>
            </div>
        </div>
        @if($latestAnnouncement && $latestAnnouncement->id !== $announcement->id)
        <a href="{{ route('komunitas.pengumuman.show', $latestAnnouncement->id) }}" 
           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors shrink-0 self-start sm:self-auto">
            <span>Lihat Pembaruan Terbaru</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </a>
        @endif
    </div>
    @elseif($announcement->is_deactivated)
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center gap-3 shadow-xs">
        <span class="p-2 rounded-xl bg-rose-100 text-rose-700 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
        </span>
        <div>
            <div class="font-bold text-sm">Pengumuman Dinonaktifkan</div>
            <div class="text-xs text-rose-800">Pengumuman ini telah dinonaktifkan oleh pengurus: {{ $announcement->deactivation_reason }}</div>
        </div>
    </div>
    @endif

    <!-- Main Announcement Card -->
    <div class="bg-white rounded-2xl border border-stone-200/90 shadow-xs overflow-hidden {{ $announcement->tipe === 'MENDESAK' ? 'border-l-4 !border-l-rose-500' : ($announcement->tipe === 'PENTING' ? 'border-l-4 !border-l-amber-500' : 'border-l-4 !border-l-emerald-700') }}">
        <div class="p-6">
            <!-- Badges & Metadata -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 mb-3">
                <div class="flex items-center gap-2 flex-wrap">
                    @if($announcement->tipe === 'MENDESAK')
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200/80 uppercase">Mendesak</span>
                    @elseif($announcement->tipe === 'PENTING')
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-50 text-amber-700 border border-amber-200/80 uppercase">Penting</span>
                    @else
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/80 uppercase">Info</span>
                    @endif

                    @if($announcement->is_pinned)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-500/10 text-amber-800 border border-amber-300/40">
                            <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                            </svg>
                            Disematkan
                        </span>
                    @endif

                    @if($announcement->is_replaced)
                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md bg-stone-100 text-stone-600 border border-stone-200">
                            Sudah digantikan oleh pembaruan terbaru
                        </span>
                    @elseif($announcement->is_deactivated)
                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md bg-rose-50 text-rose-700 border border-rose-200">
                            Dinonaktifkan
                        </span>
                    @else
                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200">
                            Versi terbaru
                        </span>
                    @endif

                    @if($announcement->expired_at)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-md bg-stone-100 text-stone-600 border border-stone-200/70">
                            <svg class="w-3 h-3 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Berlaku s/d {{ \Carbon\Carbon::parse($announcement->expired_at)->translatedFormat('d F Y') }}</span>
                        </span>
                    @endif
                </div>

                <span class="text-xs text-stone-400 font-medium">{{ $announcement->created_at?->translatedFormat('d F Y, H:i') }} WIB ({{ $announcement->created_at?->diffForHumans() }})</span>
            </div>

            <!-- Title & Content -->
            <h1 class="text-xl font-bold text-stone-900 leading-snug mb-3">{{ $announcement->judul }}</h1>
            <div class="text-sm text-stone-700 whitespace-pre-line leading-relaxed">{{ $announcement->konten }}</div>

            @if($announcement->kalenderEvent)
            <!-- Agenda Kalender Linked Card -->
            <div class="mt-4 p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-white border border-emerald-200 flex flex-col items-center justify-center shrink-0 shadow-2xs">
                        <span class="text-base font-extrabold text-emerald-800 leading-none">{{ $announcement->kalenderEvent->tanggal->format('d') }}</span>
                        <span class="text-[9px] font-bold text-emerald-600 uppercase mt-0.5">{{ $announcement->kalenderEvent->tanggal->translatedFormat('M') }}</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-emerald-950">Terjadwal di Kalender Warga</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $announcement->kalenderEvent->kategori_badge_class }}">
                                {{ $announcement->kalenderEvent->kategori_label }}
                            </span>
                        </div>
                        <div class="text-[11px] text-emerald-800/80 flex flex-wrap items-center gap-1.5 mt-0.5">
                            <span>🕒 {{ $announcement->kalenderEvent->formatted_waktu }}</span>
                            @if($announcement->kalenderEvent->lokasi)
                                <span>•</span>
                                <span>📍 {{ $announcement->kalenderEvent->lokasi }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <a href="{{ route('kalender.index', ['scope' => $announcement->scope_type, 'year' => $announcement->kalenderEvent->tanggal->year, 'month' => $announcement->kalenderEvent->tanggal->month]) }}"
                   class="inline-flex items-center gap-1 text-xs font-bold text-emerald-800 hover:text-emerald-950 bg-white px-3.5 py-1.5 rounded-xl border border-emerald-200 shadow-2xs hover:bg-emerald-50 transition-colors self-start sm:self-auto">
                    <span>Buka Kalender</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
            @endif

            <!-- Author footer (No NIK) -->
            <div class="mt-6 pt-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-400">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-stone-700">{{ $announcement->author?->nama }}</span>
                    <span class="px-2 py-0.5 rounded bg-stone-100 text-stone-600 font-medium text-[10px]">{{ $announcement->author?->getHighestRoleBadge() }}</span>
                </div>
            </div>
        </div>

        <!-- Relationship & History Navigation Strip -->
        @if($announcement->previous || $announcement->successor || $announcement->forumThread || $announcement->publicActivities->isNotEmpty())
        <div class="px-6 py-3.5 bg-stone-50/90 border-t border-stone-100 space-y-3">
            @if($announcement->previous || $announcement->successor || $announcement->forumThread)
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <!-- Navigasi Riwayat: Previous / Next -->
                <div class="flex items-center gap-2 flex-wrap">
                    @if($announcement->previous)
                    <a href="{{ route('komunitas.pengumuman.show', $announcement->previous->id) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white hover:bg-stone-100 text-stone-700 border border-stone-200/80 font-semibold text-xs shadow-2xs transition-colors">
                        <svg class="w-3.5 h-3.5 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>← Versi Sebelumnya</span>
                    </a>
                    @endif

                    @if($announcement->successor)
                    <a href="{{ route('komunitas.pengumuman.show', $announcement->successor->id) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200/80 font-semibold text-xs shadow-2xs transition-colors">
                        <span>↓ Lihat Pembaruan Berikutnya</span>
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    @elseif($latestAnnouncement && $latestAnnouncement->id !== $announcement->id)
                    <a href="{{ route('komunitas.pengumuman.show', $latestAnnouncement->id) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs shadow-2xs transition-colors">
                        <span>→ Lihat Pembaruan Terbaru</span>
                    </a>
                    @endif
                </div>

                <!-- Forum Link -->
                @if($announcement->forumThread)
                <a href="{{ route('komunitas.forum.thread.show', $announcement->forum_thread_id) }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-stone-50 text-emerald-800 border border-emerald-200/80 rounded-lg text-xs font-semibold shadow-2xs transition-colors self-start sm:self-auto">
                    <span>💬 Diskusikan di Forum</span>
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
                @endif
            </div>

            <!-- Riwayat Ringkas Versi Sebelumnya jika ada -->
            @if($announcement->previous)
            <div class="p-3.5 bg-white rounded-xl border border-stone-200/80 text-xs shadow-2xs space-y-1.5">
                <div class="flex items-center justify-between text-[11px] text-stone-400">
                    <div class="flex items-center gap-1.5">
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-stone-100 text-stone-600 border border-stone-200">Sudah digantikan</span>
                        <span class="font-bold text-stone-700">{{ $announcement->previous->judul }}</span>
                    </div>
                    <span>{{ $announcement->previous->created_at?->translatedFormat('d F Y, H:i') }} WIB</span>
                </div>
                <p class="text-stone-600 text-[11px] line-clamp-2 leading-relaxed">{{ $announcement->previous->konten }}</p>
                <div class="flex items-center justify-between pt-1 text-[11px] text-stone-400">
                    <span>💬 {{ $announcement->previous->comments->count() }} Tanggapan warga pada versi sebelumnya</span>
                    <a href="{{ route('komunitas.pengumuman.show', $announcement->previous->id) }}" class="text-emerald-800 font-semibold hover:underline">
                        Buka Versi Sebelumnya →
                    </a>
                </div>
            </div>
            @endif
            @endif

            <!-- Aktivitas Pengumuman -->
            @if($announcement->publicActivities->isNotEmpty())
            <div class="{{ ($announcement->previous || $announcement->successor || $announcement->forumThread) ? 'pt-2.5 border-t border-stone-200/70' : '' }} space-y-2">
                <div class="flex items-center gap-1.5 text-stone-500 font-bold text-xs">
                    <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Aktivitas Pengumuman</span>
                </div>
                <div class="space-y-2">
                    @foreach($announcement->publicActivities as $activity)
                    <div class="p-3 bg-white rounded-xl border border-stone-200/80 text-xs shadow-2xs space-y-1">
                        <div class="flex items-center justify-between gap-2 flex-wrap text-stone-500 text-[11px]">
                            <span class="font-bold text-stone-800 text-xs">{{ $activity->public_actor_label }}</span>
                            <span>{{ $activity->created_at?->translatedFormat('d M Y · H:i') }} WIB</span>
                        </div>
                        <div class="text-stone-700 font-medium">
                            {{ rtrim($activity->public_action_text, '.') }}.
                        </div>
                        @if(!empty($activity->alasan))
                        <div class="text-stone-600 bg-stone-50 border-l-2 border-stone-300 px-2.5 py-1 text-[11px] rounded-r italic">
                            Alasan: {{ $activity->alasan }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Comments Section -->
        <div class="bg-stone-50/70 border-t border-stone-100 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <h3 class="text-sm font-bold text-stone-800">Tanggapan Warga ({{ $announcement->comments->count() }})</h3>
                </div>

                @if(! $announcement->canReceiveComments())
                <span class="text-[11px] font-semibold text-stone-500 bg-stone-200/80 px-2 py-0.5 rounded-md">
                    Read-Only (Tanggapan Dikunci)
                </span>
                @endif
            </div>

            <!-- List of existing comments -->
            <div class="space-y-3">
                @forelse($announcement->comments as $cmt)
                <div class="p-3.5 bg-white rounded-xl border border-stone-200/70 text-xs shadow-2xs">
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-stone-800">{{ $cmt->author?->nama }}</span>
                            <span class="text-[9px] px-1.5 py-0.2 rounded bg-stone-100 text-stone-600 font-medium">{{ $cmt->author?->getHighestRoleBadge() }}</span>
                        </div>
                        <span class="text-[10px] text-stone-400">{{ $cmt->created_at?->diffForHumans() }} ({{ $cmt->created_at?->translatedFormat('d M Y, H:i') }})</span>
                    </div>
                    <p class="text-stone-700 leading-relaxed">{{ $cmt->konten }}</p>
                </div>
                @empty
                <p class="text-xs text-stone-400 italic">Belum ada tanggapan pada versi pengumuman ini.</p>
                @endforelse
            </div>

            <!-- Comment Form OR Read-Only Notice -->
            @if($announcement->is_replaced)
            <div class="p-4 bg-amber-50/80 rounded-xl border border-amber-200 text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Tanggapan dikunci karena pengumuman ini sudah digantikan oleh pembaruan terbaru. Tanggapan baru hanya dapat dikirimkan pada versi terbaru.</span>
                </div>
                @if($latestAnnouncement && $latestAnnouncement->id !== $announcement->id)
                <a href="{{ route('komunitas.pengumuman.show', $latestAnnouncement->id) }}" class="inline-flex items-center gap-1 font-bold text-emerald-800 hover:text-emerald-950 shrink-0">
                    <span>Lihat Pembaruan Terbaru</span>
                    <span>→</span>
                </a>
                @endif
            </div>
            @elseif($announcement->is_deactivated)
            <div class="p-3.5 bg-rose-50/80 rounded-xl border border-rose-200 text-xs text-rose-800 flex items-center gap-2 mt-4">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                <span>Tanggapan dikunci karena pengumuman ini telah dinonaktifkan.</span>
            </div>
            @else
            <!-- Add Comment Form for ACTIVE version -->
            <form action="{{ route('komunitas.pengumuman.komentar', $announcement->id) }}" method="POST" class="pt-2">
                @csrf
                <div class="flex gap-2">
                    <input type="text" name="konten" required placeholder="Tuliskan tanggapan Anda mengenai pengumuman ini..." 
                           class="flex-1 text-xs px-3.5 py-2.5 rounded-xl bg-white border border-stone-200 focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
                    <button type="submit" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">Kirim</button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
