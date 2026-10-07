{{-- TAB 3: KURASI RT (Khusus Reviewer RT) --}}
<div x-show="activeTab === 'kurasi'" class="space-y-6" style="display: none;">

    <!-- Header Meja Kurasi Banner -->
    <div class="bg-white rounded-3xl border border-[#e4ded1] p-5 sm:p-6 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h2 class="text-base font-bold text-stone-900">Meja Kurasi RT — Antrean Usaha Warga</h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $mejaKurasi->count() > 0 ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-stone-100 text-stone-700' }}">
                    {{ $mejaKurasi->count() }} Menunggu Review
                </span>
            </div>
            <p class="text-xs text-stone-500 mt-1">Periksa kelayakan pengajuan usaha baru atau revisi warga di RT 0{{ auth()->user()->rt?->nomor_rt ?? 5 }} sebelum ditampilkan di etalase publik.</p>
        </div>
        <div class="text-xs text-stone-500 bg-stone-50/80 border border-[#e4ded1] px-3.5 py-2 rounded-xl shrink-0">
            Wewenang: <strong class="text-stone-800 font-semibold capitalize">{{ str_replace('_', ' ', auth()->user()->peran ?? 'Reviewer RT') }}</strong>
        </div>
    </div>

    @if($mejaKurasi->count() > 0)
        <!-- Antrean Kartu Kurasi -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($mejaKurasi as $item)
                <div class="bg-white rounded-3xl border border-[#e4ded1] overflow-hidden shadow-2xs hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <!-- Header Card: Foto Thumbnail, Kategori, Status & Penjual -->
                        <div class="p-5 flex flex-col sm:flex-row gap-4 items-start border-b border-[#e4ded1]/70">
                            <!-- Thumbnail Foto (Clickable for zoom) -->
                            <div class="w-full sm:w-28 h-28 bg-stone-100/80 rounded-2xl overflow-hidden shrink-0 border border-[#e4ded1] relative group shadow-2xs">
                                @if($item->foto_url)
                                    <img src="{{ Storage::url($item->foto_url) }}"
                                         alt="{{ $item->nama }}"
                                         class="w-full h-full object-cover">
                                    <button type="button"
                                            @click="previewImageUrl = '{{ Storage::url($item->foto_url) }}'"
                                            class="absolute inset-0 bg-[#10231e]/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white cursor-pointer"
                                            title="Lihat foto lebih besar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                    </button>
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center text-stone-400 p-2 text-center">
                                        <svg class="w-6 h-6 mb-1 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-[10px] leading-tight text-stone-400">Tidak ada foto</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Metadata Utama -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1.5">
                                    @if($item->kategori === 'barang')
                                        <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-[#e5a53f] text-white shadow-2xs">
                                            Barang
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-[#10231e] text-white shadow-2xs">
                                            Jasa
                                        </span>
                                    @endif

                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                        <svg class="w-2.5 h-2.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Menunggu Verifikasi</span>
                                    </span>
                                </div>

                                <h3 class="text-base font-bold text-stone-900 leading-snug line-clamp-1" title="{{ $item->nama }}">
                                    {{ $item->nama }}
                                </h3>

                                <div class="mt-1 flex items-baseline">
                                    @if($item->kategori === 'barang' && $item->harga !== null)
                                        <span class="text-sm font-extrabold text-[#10231e] tracking-tight whitespace-nowrap">
                                            {{ $item->formatted_harga }}
                                        </span>
                                    @else
                                        <span class="text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2.5 py-0.5 rounded-lg whitespace-nowrap">
                                            {{ $item->formatted_harga }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-stone-500">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>oleh <strong class="text-stone-800 font-semibold">{{ $item->user?->nama ?? 'Warga RT' }}</strong></span>
                                    </span>
                                    <span class="text-stone-300">•</span>
                                    <span class="text-[11px] text-stone-400">
                                        {{ $item->created_at?->diffForHumans() ?? 'Baru diajukan' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Deskripsi & Template WA -->
                        <div class="p-5 space-y-3.5">
                            <div>
                                <h4 class="text-xs font-bold text-stone-700 mb-1.5">Deskripsi Usaha:</h4>
                                <p class="text-xs text-stone-600 leading-relaxed bg-stone-50/80 p-3.5 rounded-2xl border border-[#e4ded1] whitespace-pre-line">
                                    {{ $item->deskripsi }}
                                </p>
                            </div>

                            @if($item->template_pesan_wa)
                                <div>
                                    <h4 class="text-[11px] font-bold text-stone-600 mb-1.5 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                        <span>Template Pesan WhatsApp Kustom:</span>
                                    </h4>
                                    <p class="text-[11px] text-stone-600 italic bg-stone-50/80 px-3.5 py-2.5 rounded-xl border border-[#e4ded1]">
                                        "{{ $item->template_pesan_wa }}"
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Card Actions: Setujui & Tolak (Khusus Reviewer) -->
                    <div class="p-4 sm:p-5 bg-stone-50/80 border-t border-[#e4ded1]/70 flex items-center justify-end gap-2.5">
                        <button type="button"
                                @click="openRejectModal({{ json_encode($item) }})"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-rose-300 text-rose-700 hover:bg-rose-50 hover:border-rose-400 text-xs font-semibold transition-colors cursor-pointer"
                                title="Tolak pengajuan listing ini dengan alasan resmi">
                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>Tolak</span>
                        </button>

                        <button type="button"
                                @click="openApproveModal({{ json_encode($item) }})"
                                class="inline-flex items-center gap-1.5 px-4.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-semibold shadow-2xs transition-all cursor-pointer"
                                title="Setujui pengajuan listing ini agar tayang di etalase warga">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Setujui Usaha</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State Meja Kurasi -->
        <div class="bg-white rounded-3xl border border-[#e4ded1] p-12 text-center shadow-2xs space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 border border-emerald-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-stone-900">Semua pengajuan sudah ditinjau</h3>
            <p class="text-xs text-stone-500 max-w-md mx-auto leading-relaxed">
                Saat ini tidak ada antrean kurasi listing UMKM baru atau perbaikan revisi yang menunggu verifikasi di RT Anda.
            </p>
        </div>
    @endif

</div>
