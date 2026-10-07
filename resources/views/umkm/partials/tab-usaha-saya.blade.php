<!-- ========================================================================= -->
<!-- TAB 2: USAHA SAYA (Daftar Usaha Milik User Login)                          -->
<!-- ========================================================================= -->
<div x-show="activeTab === 'usaha-saya'" class="space-y-6" style="display: none;">

    <!-- Header Usaha Saya Banner -->
    <div class="bg-white rounded-3xl border border-[#e4ded1] p-5 sm:p-6 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-stone-900">Daftar Usaha & Jasa Milik Anda</h2>
            <p class="text-xs text-stone-500 mt-0.5">Kelola produk atau layanan jasa yang Anda tawarkan ke tetangga RT. Listing baru atau perubahan substantif akan diverifikasi pengurus RT terlebih dahulu.</p>
        </div>
        <button type="button"
                @click="handleBukaUsaha()"
                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#10231e] hover:bg-[#1a3830] text-white text-xs font-semibold shadow-xs transition-all cursor-pointer shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Produk / Jasa</span>
        </button>
    </div>

    @if($usahaSaya->count() > 0)
        <!-- Grid Listing Usaha Saya -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($usahaSaya as $item)
                <div class="bg-white rounded-3xl border border-[#e4ded1] overflow-hidden shadow-2xs hover:shadow-md transition-all flex flex-col justify-between">
                    <!-- Foto & Badges -->
                    <div class="h-44 bg-stone-100/80 relative overflow-hidden flex items-center justify-center">
                        @if($item->foto_url)
                            <img src="{{ Storage::url($item->foto_url) }}"
                                 alt="{{ $item->nama }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="flex flex-col items-center justify-center text-stone-400 p-4 text-center">
                                @if($item->kategori === 'barang')
                                    <svg class="w-10 h-10 mb-1 text-[#e5a53f]/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                @else
                                    <svg class="w-10 h-10 mb-1 text-[#10231e]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                @endif
                                <span class="text-[11px] font-medium text-stone-400">Belum ada foto</span>
                            </div>
                        @endif

                        <!-- Kategori Badge (Top Left) -->
                        <div class="absolute top-3 left-3">
                            @if($item->kategori === 'barang')
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-[#e5a53f] text-white shadow-xs">
                                    Barang
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-[#10231e] text-white shadow-xs">
                                    Jasa
                                </span>
                            @endif
                        </div>

                        <!-- Status Badge (Top Right) -->
                        <div class="absolute top-3 right-3">
                            @if($item->status === \App\Models\UmkmListing::STATUS_MENUNGGU)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-xs">
                                    <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Menunggu Verifikasi</span>
                                </span>
                            @elseif($item->status === \App\Models\UmkmListing::STATUS_DISETUJUI)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 shadow-xs">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Disetujui &amp; Tayang</span>
                                </span>
                            @elseif($item->status === \App\Models\UmkmListing::STATUS_NONAKTIF)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-stone-200 text-stone-800 border border-stone-300 shadow-xs">
                                    <svg class="w-3 h-3 text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Nonaktif</span>
                                </span>
                            @elseif($item->status === \App\Models\UmkmListing::STATUS_DITAKEDOWN)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-900 border border-purple-300 shadow-xs">
                                    <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    <span>Di-takedown</span>
                                </span>
                            @elseif($item->status === \App\Models\UmkmListing::STATUS_DITOLAK)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-900 border border-rose-300 shadow-xs">
                                    <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span>Ditolak</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-stone-900 text-sm line-clamp-1" title="{{ $item->nama }}">
                                {{ $item->nama }}
                            </h3>

                            <div class="mt-1 flex items-baseline">
                                @if($item->kategori === 'barang' && $item->harga !== null)
                                    <span class="text-sm font-extrabold text-[#10231e] whitespace-nowrap">
                                        {{ $item->formatted_harga }}
                                    </span>
                                @else
                                    <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2.5 py-0.5 rounded-lg whitespace-nowrap">
                                        {{ $item->formatted_harga }}
                                    </span>
                                @endif
                            </div>

                            <p class="text-xs text-stone-500 mt-2 line-clamp-2 leading-relaxed">
                                {{ $item->deskripsi }}
                            </p>

                            <!-- ALASAN PENOLAKAN / TAKEDOWN TRANSPARAN -->
                            @if($item->status === \App\Models\UmkmListing::STATUS_DITOLAK)
                                <div class="mt-3 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1">
                                    <div class="flex items-center gap-1.5 font-bold text-rose-800 text-[11px]">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        <span>Alasan Penolakan Pengurus:</span>
                                    </div>
                                    <p class="text-xs text-rose-950 font-medium pl-5 leading-relaxed">
                                        "{{ $item->alasan_tolak }}"
                                    </p>
                                    <p class="text-[10px] text-rose-600 pl-5 pt-0.5">
                                        Silakan edit dan kirimkan perbaikan agar dapat ditinjau ulang.
                                    </p>
                                </div>
                            @elseif($item->status === \App\Models\UmkmListing::STATUS_DITAKEDOWN)
                                <div class="mt-3 p-3.5 rounded-2xl bg-purple-50 border border-purple-200 text-purple-900 text-xs space-y-1">
                                    <div class="flex items-center gap-1.5 font-bold text-purple-800 text-[11px]">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        <span>Alasan Takedown Pengurus:</span>
                                    </div>
                                    <p class="text-xs text-purple-950 font-medium pl-5 leading-relaxed">
                                        "{{ $item->alasan_takedown }}"
                                    </p>
                                    <p class="text-[10px] text-purple-700 pl-5 pt-0.5">
                                        Usaha ini ditarik pengurus. Silakan perbaiki data melalui tombol di bawah untuk mengajukan kurasi ulang.
                                    </p>
                                </div>
                            @elseif($item->status === \App\Models\UmkmListing::STATUS_NONAKTIF)
                                <p class="text-[10px] text-stone-500 mt-2.5 italic">
                                    * Usaha sedang nonaktif (ditarik dari etalase). Klik "Aktifkan" untuk mengajukan kurasi ulang ke RT.
                                </p>
                            @elseif($item->status === \App\Models\UmkmListing::STATUS_DISETUJUI)
                                <p class="text-[10px] text-stone-400 mt-2.5 italic">
                                    * Mengubah data substantif akan mengirim ulang usaha ini untuk verifikasi pengurus.
                                </p>
                            @elseif($item->status === \App\Models\UmkmListing::STATUS_MENUNGGU)
                                <p class="text-[10px] text-amber-700 mt-2.5 italic">
                                    * Usaha sedang dalam antrean verifikasi pengurus RT.
                                </p>
                            @endif
                        </div>

                        <!-- Card Footer: Aksi Kelola Listing -->
                        <div class="mt-4 pt-3.5 border-t border-[#e4ded1]/70 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                @if($item->status === \App\Models\UmkmListing::STATUS_DITAKEDOWN)
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($item) }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <span>Perbaiki &amp; Ajukan Ulang</span>
                                    </button>
                                @elseif($item->status === \App\Models\UmkmListing::STATUS_NONAKTIF)
                                    <button type="button"
                                            @click="openAktifkanModal({{ json_encode($item) }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors cursor-pointer"
                                            title="Ajukan pengaktifan kembali ke pengurus RT">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Aktifkan Kembali</span>
                                    </button>
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($item) }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 font-semibold text-xs transition-colors cursor-pointer">
                                        <span>Edit</span>
                                    </button>
                                @else
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($item) }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl {{ $item->status === \App\Models\UmkmListing::STATUS_DITOLAK ? 'bg-amber-600 hover:bg-amber-700 text-white font-bold' : 'bg-stone-100 hover:bg-stone-200 text-stone-700 font-semibold' }} text-xs transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <span>{{ $item->status === \App\Models\UmkmListing::STATUS_DITOLAK ? 'Edit & Ajukan Ulang' : 'Edit' }}</span>
                                    </button>

                                    @if(in_array($item->status, [\App\Models\UmkmListing::STATUS_DISETUJUI, \App\Models\UmkmListing::STATUS_MENUNGGU], true))
                                        <button type="button"
                                                @click="openNonaktifModal({{ json_encode($item) }})"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer"
                                                title="Tarik / nonaktifkan sementara usaha ini dari etalase">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>Nonaktifkan</span>
                                        </button>
                                    @endif
                                @endif
                            </div>

                            <button type="button"
                                    @click="openDeleteModal({{ json_encode($item) }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-semibold transition-colors cursor-pointer"
                                    title="Hapus listing usaha ini">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span class="sr-only sm:not-sr-only">Hapus</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State Usaha Saya -->
        <div class="bg-white rounded-3xl border border-[#e4ded1] p-12 text-center shadow-2xs space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 border border-emerald-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-stone-900">Anda belum memiliki usaha yang didaftarkan</h3>
            <p class="text-xs text-stone-500 max-w-md mx-auto leading-relaxed">
                Mulai promosikan produk kuliner, kerajinan tangan, atau layanan keahlian Anda ke tetangga sekitar RT tanpa potongan komisi.
            </p>
            <div class="pt-2">
                <button type="button"
                        @click="handleBukaUsaha()"
                        class="inline-flex items-center gap-1.5 px-4.5 py-2.5 rounded-xl bg-[#10231e] hover:bg-[#1a3830] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>+ Buka Usaha Sekarang</span>
                </button>
            </div>
        </div>
    @endif

</div>
