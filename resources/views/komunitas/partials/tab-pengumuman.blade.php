<div class="space-y-4">
    <!-- Action Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold text-stone-800 uppercase tracking-wider">Daftar Pengumuman {{ strtoupper($requestedScope) }}</h2>
            <p class="text-xs text-stone-500 mt-0.5">Pemberitahuan resmi dari jajaran pengurus wilayah.</p>
        </div>
        @if($canPublishAnnouncement)
        <button @click="showPengumumanModal = true" type="button" 
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Terbitkan Pengumuman</span>
        </button>
        @endif
    </div>

    @if($announcements->isEmpty())
    <div class="p-12 text-center bg-white rounded-2xl border border-stone-200/90 shadow-xs">
        <div class="w-12 h-12 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
        </div>
        <h4 class="text-sm font-bold text-stone-800 mb-1">Belum Ada Pengumuman Aktif</h4>
        <p class="text-xs text-stone-500 max-w-sm mx-auto leading-relaxed">Saat ini belum ada pengumuman resmi yang dipublikasikan untuk lingkup {{ strtoupper($requestedScope) }}.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($announcements as $anc)
        <div class="bg-white rounded-2xl border border-stone-200/90 shadow-xs overflow-hidden transition-all hover:border-stone-300 {{ $anc->tipe === 'MENDESAK' ? 'border-l-4 !border-l-rose-500' : ($anc->tipe === 'PENTING' ? 'border-l-4 !border-l-amber-500' : 'border-l-4 !border-l-emerald-700') }}">
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
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/80 uppercase">Info</span>
                        @endif

                        @if($anc->is_pinned)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-500/10 text-amber-800 border border-amber-300/40">
                                <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                                </svg>
                                Disematkan
                            </span>
                        @endif

                        @if($anc->expired_at)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-md bg-stone-100 text-stone-600 border border-stone-200/70" title="Masa berlaku pengumuman">
                                <svg class="w-3 h-3 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Berlaku s/d {{ \Carbon\Carbon::parse($anc->expired_at)->translatedFormat('d F Y') }}</span>
                            </span>
                        @endif

                        @if($anc->kalenderEvent)
                            <a href="{{ route('kalender.index', ['scope' => $anc->scope_type, 'year' => $anc->kalenderEvent->tanggal->year, 'month' => $anc->kalenderEvent->tanggal->month]) }}" 
                               class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-semibold rounded-md bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors" title="Lihat di Kalender Warga">
                                <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>📅 {{ $anc->kalenderEvent->tanggal->format('d/m/Y') }} ({{ $anc->kalenderEvent->kategori_label }})</span>
                            </a>
                        @endif
                    </div>

                    <!-- Right Actions Header -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-stone-400 font-medium">{{ $anc->created_at?->diffForHumans() }}</span>

                        @if($canPublishAnnouncement)
                        <div class="flex items-center gap-1.5">
                            <!-- Sematkan (Toggle Pin) -->
                            <form action="{{ route('komunitas.pengumuman.toggle-pin', $anc->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        title="{{ $anc->is_pinned ? 'Lepas sematan pengumuman' : 'Sematkan pengumuman di posisi teratas' }}"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold rounded-lg transition-colors {{ $anc->is_pinned ? 'bg-amber-100 text-amber-900 hover:bg-amber-200 border border-amber-300/60' : 'bg-stone-100 text-stone-600 hover:bg-stone-200 border border-stone-200/60' }}">
                                    <svg class="w-3 h-3 {{ $anc->is_pinned ? 'text-amber-700' : 'text-stone-400' }}" fill="{{ $anc->is_pinned ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                    <span>{{ $anc->is_pinned ? 'Lepas' : 'Sematkan' }}</span>
                                </button>
                            </form>

                            <!-- Buat Pembaruan (Hanya jika belum digantikan / dinonaktifkan) -->
                            @if($anc->canBeUpdated())
                            <button type="button" 
                                    @click="selectedAnnouncement = { 
                                        id: {{ $anc->id }}, 
                                        judul: '{{ addslashes($anc->judul) }}', 
                                        tipe: '{{ $anc->tipe }}', 
                                        konten: {{ json_encode($anc->konten) }}, 
                                        expired_at: '{{ $anc->expired_at?->toDateString() }}', 
                                        forum_thread_id: '{{ $anc->forum_thread_id }}',
                                        has_agenda: {{ $anc->kalenderEvent ? 'true' : 'false' }},
                                        agenda_tanggal: '{{ $anc->kalenderEvent?->tanggal?->format('Y-m-d') }}',
                                        agenda_waktu_mulai: '{{ $anc->kalenderEvent?->waktu_mulai }}',
                                        agenda_waktu_selesai: '{{ $anc->kalenderEvent?->waktu_selesai }}',
                                        agenda_lokasi: '{{ addslashes($anc->kalenderEvent?->lokasi ?? '') }}',
                                        agenda_kategori: '{{ $anc->kalenderEvent?->kategori ?? 'KEGIATAN' }}'
                                    }; showPembaruanModal = true"
                                    title="Terbitkan pembaruan untuk pengumuman ini"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold rounded-lg bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors">
                                <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold rounded-lg bg-stone-100 text-stone-700 hover:bg-stone-200 border border-stone-200 transition-colors">
                                <svg class="w-3 h-3 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                <span>Hubungkan Forum</span>
                            </button>
                            @endif

                            <!-- Nonaktifkan Pengumuman -->
                            <button type="button"
                                    @click="selectedAnnouncement = { id: {{ $anc->id }}, judul: '{{ addslashes($anc->judul) }}' }; showDeactivateModal = true"
                                    title="Nonaktifkan pengumuman resmi ini"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition-colors">
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
                <h3 class="text-base font-bold text-stone-900 leading-snug mb-2">{{ $anc->judul }}</h3>
                <p class="text-sm text-stone-700 whitespace-pre-line leading-relaxed">{{ $anc->konten }}</p>

                <!-- Author footer (No NIK) -->
                <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-xs text-stone-400">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-stone-700">{{ $anc->author?->nama }}</span>
                        <span class="px-1.5 py-0.5 rounded bg-stone-100 text-stone-600 font-medium text-[10px]">{{ $anc->author?->getHighestRoleBadge() }}</span>
                    </div>
                </div>
            </div>

            <!-- Relationship Strip: Pembaruan & Forum Link & Aktivitas -->
            @if($anc->replaces_announcement_id || $anc->forum_thread_id || $anc->publicActivities->isNotEmpty())
            <div class="px-5 py-2.5 bg-stone-50/90 border-t border-stone-100 flex flex-col gap-2.5 text-xs" x-data="{ showPrevPreview: false }">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
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
                       class="inline-flex items-center gap-1.5 px-3 py-1 bg-white hover:bg-stone-50 text-emerald-700 border border-emerald-200/80 rounded-lg text-xs font-semibold shadow-2xs transition-colors self-start sm:self-auto">
                        <span>💬 Diskusikan di Forum</span>
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    @endif
                </div>

                <!-- Accordion Preview Versi Lama -->
                @if($anc->previous)
                <div x-show="showPrevPreview" x-collapse class="w-full mt-2 p-3.5 bg-white rounded-xl border border-stone-200/80 text-xs shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between border-b border-stone-100 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-stone-800">Riwayat Pembaruan</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-stone-100 text-stone-600 border border-stone-200">Sudah digantikan oleh pembaruan terbaru</span>
                        </div>
                        @if($anc->previous->previous)
                        <span class="text-[11px] text-stone-500 font-medium">Versi sebelumnya tersedia</span>
                        @endif
                    </div>

                    <div class="p-3 bg-stone-50/60 rounded-lg border border-stone-100 space-y-1.5">
                        <div class="flex items-center justify-between text-[11px] text-stone-500">
                            <span class="font-bold text-stone-800 text-xs">{{ $anc->previous->judul }}</span>
                            <span>{{ $anc->previous->created_at?->translatedFormat('d F Y, H:i') }} WIB</span>
                        </div>
                        <p class="text-stone-600 text-[11px] line-clamp-3 leading-relaxed">{{ $anc->previous->konten }}</p>
                        <div class="flex items-center justify-between pt-1 text-[11px] text-stone-400">
                            <span>💬 {{ $anc->previous->comments->count() }} Tanggapan warga pada versi ini</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <a href="{{ route('komunitas.pengumuman.show', $anc->previous->id) }}" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 font-semibold text-xs transition-colors">
                            <span>← Buka Versi Sebelumnya</span>
                        </a>

                        <div class="text-[11px] text-emerald-800 font-semibold flex items-center gap-1">
                            <span>Versi terbaru</span>
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Aktivitas Pengumuman Ringkas -->
                @if($anc->publicActivities->isNotEmpty())
                <div class="w-full pt-2 border-t border-stone-200/60 space-y-1.5">
                    <div class="flex items-center gap-1.5 text-stone-500 font-bold text-[11px]">
                        <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Aktivitas Pengumuman</span>
                    </div>
                    @foreach($anc->publicActivities as $act)
                    <div class="p-2.5 bg-white rounded-lg border border-stone-200/80 text-[11px] space-y-0.5">
                        <div class="flex items-center justify-between text-stone-500">
                            <span class="font-bold text-stone-800">{{ $act->public_actor_label }}</span>
                            <span class="text-[10px] text-stone-400">{{ $act->created_at?->translatedFormat('d M Y · H:i') }} WIB</span>
                        </div>
                        <div class="text-stone-700 font-medium">{{ rtrim($act->public_action_text, '.') }}.</div>
                        @if(!empty($act->alasan))
                        <div class="text-stone-600 bg-stone-50 border-l-2 border-stone-300 px-2 py-0.5 text-[10px] rounded-r italic">
                            Alasan: {{ $act->alasan }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            <!-- Collapsible Comments Section (Alpine Accordion) -->
            <div x-data="{ openComments: false }" class="bg-stone-50/70 border-t border-stone-100 p-4">
                <button @click="openComments = !openComments" type="button" class="flex items-center gap-2 text-xs font-semibold text-emerald-800 hover:text-emerald-950 transition-colors">
                    <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="openComments ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span>Tanggapan Warga ({{ $anc->comments->count() }})</span>
                </button>

                <div x-show="openComments" x-collapse class="mt-3 space-y-3 pt-2">
                    <!-- Comment list -->
                    @foreach($anc->comments as $cmt)
                    <div class="p-3 bg-white rounded-xl border border-stone-200/60 text-xs shadow-2xs">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-stone-800">{{ $cmt->author?->nama }}</span>
                                <span class="text-[9px] px-1 rounded bg-stone-100 text-stone-600">{{ $cmt->author?->getHighestRoleBadge() }}</span>
                            </div>
                            <span class="text-[10px] text-stone-400">{{ $cmt->created_at?->diffForHumans() }}</span>
                        </div>
                        <p class="text-stone-700 leading-relaxed">{{ $cmt->konten }}</p>
                    </div>
                    @endforeach

                    @if($anc->canReceiveComments())
                    <!-- Add Comment Form -->
                    <form action="{{ route('komunitas.pengumuman.komentar', $anc->id) }}" method="POST" class="pt-2">
                        @csrf
                        <div class="flex gap-2">
                            <input type="text" name="konten" required placeholder="Tuliskan tanggapan Anda..." 
                                   class="flex-1 text-xs px-3.5 py-2.5 rounded-xl bg-white border border-stone-200 focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
                            <button type="submit" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">Kirim</button>
                        </div>
                    </form>
                    @else
                    <div class="p-3 bg-amber-50/80 rounded-xl border border-amber-200/80 text-xs text-amber-900 flex items-center gap-2 mt-2">
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
</div>
