<!-- ========================================================================= -->
<!-- TAB 1: ETALASE WARGA (Katalog Publik se-RW)                               -->
<!-- ========================================================================= -->
<div x-show="activeTab === 'etalase'" class="space-y-6">

    <!-- FILTER KATEGORI, WILAYAH RT & PENCARIAN -->
    <div class="bg-white rounded-3xl border border-[#e4ded1] p-4 sm:p-5 shadow-2xs space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3.5">
            <!-- Filter Kategori Tabs (Semua / Barang / Jasa) -->
            <div class="flex items-center gap-1.5 p-1 bg-stone-100/80 rounded-2xl border border-[#e4ded1] w-fit">
                <a href="{{ route('umkm.index', array_filter(['rt_id' => request('rt_id'), 'q' => request('q')])) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ !request('kategori') || request('kategori') === 'semua' ? 'bg-[#10231e] text-white shadow-2xs' : 'text-stone-600 hover:text-stone-900' }}">
                    Semua ({{ $etalase->total() }})
                </a>
                <a href="{{ route('umkm.index', array_filter(['kategori' => 'barang', 'rt_id' => request('rt_id'), 'q' => request('q')])) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ request('kategori') === 'barang' ? 'bg-[#10231e] text-white shadow-2xs' : 'text-stone-600 hover:text-stone-900' }}">
                    Barang
                </a>
                <a href="{{ route('umkm.index', array_filter(['kategori' => 'jasa', 'rt_id' => request('rt_id'), 'q' => request('q')])) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ request('kategori') === 'jasa' ? 'bg-[#10231e] text-white shadow-2xs' : 'text-stone-600 hover:text-stone-900' }}">
                    Jasa
                </a>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 lg:justify-end">
                <!-- Filter RT di dalam RW (Anti-spoofing) -->
                @if(isset($daftarRt) && $daftarRt->count() > 0)
                    <form action="{{ route('umkm.index') }}" method="GET" class="flex items-center gap-2 shrink-0">
                        @if(request('kategori') && in_array(request('kategori'), ['barang', 'jasa']))
                            <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                        @endif
                        @if(request('q'))
                            <input type="hidden" name="q" value="{{ request('q') }}">
                        @endif
                        <label for="filter_rt_select" class="text-xs text-stone-500 font-semibold shrink-0">Wilayah:</label>
                        <select id="filter_rt_select"
                                name="rt_id"
                                onchange="this.form.submit()"
                                class="text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] py-2 px-3 text-stone-800 font-semibold focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] cursor-pointer">
                            <option value="semua" {{ request('rt_id', 'semua') === 'semua' ? 'selected' : '' }}>
                                Semua RT (Se-RW)
                            </option>
                            @foreach($daftarRt as $rtItem)
                                <option value="{{ $rtItem->id }}" {{ (string) request('rt_id') === (string) $rtItem->id ? 'selected' : '' }}>
                                    RT 0{{ $rtItem->nomor_rt }} ({{ $rtItem->nama }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <!-- Search Bar (Nama & Deskripsi) -->
                <form action="{{ route('umkm.index') }}" method="GET" class="relative sm:max-w-xs flex-1">
                    @if(request('kategori') && in_array(request('kategori'), ['barang', 'jasa']))
                        <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                    @endif
                    @if(request('rt_id') && request('rt_id') !== 'semua')
                        <input type="hidden" name="rt_id" value="{{ request('rt_id') }}">
                    @endif
                    <div class="relative">
                        <input type="text"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="Cari produk atau jasa warga..."
                               class="w-full pl-9 pr-8 py-2 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] transition-all text-stone-900 placeholder:text-stone-400">
                        <svg class="w-4 h-4 text-stone-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        @if(request('q'))
                            <a href="{{ route('umkm.index', array_filter(['kategori' => request('kategori'), 'rt_id' => request('rt_id')])) }}" class="absolute right-2.5 top-2.5 text-stone-400 hover:text-stone-700" title="Hapus pencarian">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Result Summary & Active Filters Indicator -->
        <div class="pt-3 border-t border-[#e4ded1]/60 flex flex-wrap items-center justify-between text-xs text-stone-500 gap-2">
            <div>
                @if($etalase->total() > 0)
                    <span>Menampilkan <strong>{{ $etalase->firstItem() }}–{{ $etalase->lastItem() }}</strong> dari <strong>{{ $etalase->total() }}</strong> usaha warga se-RW</span>
                @else
                    <span>Tidak ada usaha yang ditampilkan</span>
                @endif
            </div>

            @if(request('q') || request('kategori') || (request('rt_id') && request('rt_id') !== 'semua'))
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-stone-400">Filter aktif:</span>
                    @if(request('rt_id') && request('rt_id') !== 'semua')
                        <span class="px-2 py-0.5 rounded-lg bg-stone-100 text-stone-700 font-semibold text-[11px] border border-[#e4ded1]">
                            RT: {{ $daftarRt->firstWhere('id', request('rt_id'))?->nomor_rt ? 'RT 0' . $daftarRt->firstWhere('id', request('rt_id'))->nomor_rt : request('rt_id') }}
                        </span>
                    @endif
                    @if(request('kategori'))
                        <span class="px-2 py-0.5 rounded-lg bg-stone-100 text-stone-700 font-semibold text-[11px] capitalize border border-[#e4ded1]">
                            {{ request('kategori') }}
                        </span>
                    @endif
                    @if(request('q'))
                        <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 font-semibold text-[11px] border border-emerald-200">
                            "{{ request('q') }}"
                        </span>
                    @endif
                    <a href="{{ route('umkm.index') }}" class="text-[11px] font-semibold text-rose-600 hover:underline ml-1">
                        Reset filter
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- PRODUCT & SERVICE GRID -->
    @if($etalase->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($etalase as $listing)
                <div class="bg-white rounded-3xl border border-[#e4ded1] overflow-hidden shadow-2xs hover:shadow-md hover:border-[#10231e]/30 transition-all duration-200 group flex flex-col justify-between">
                    <!-- Foto Produk / Layanan dengan Aspect Ratio 4:3 -->
                    <div class="h-48 bg-stone-100/80 relative overflow-hidden flex items-center justify-center">
                        @if($listing->foto_url)
                            <img src="{{ Storage::url($listing->foto_url) }}"
                                 alt="{{ $listing->nama }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="flex flex-col items-center justify-center text-stone-400 p-4 text-center">
                                @if($listing->kategori === 'barang')
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

                        <!-- Badge Kategori (Top Left) -->
                        <div class="absolute top-3 left-3">
                            @if($listing->kategori === 'barang')
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-[#e5a53f] text-white shadow-xs">
                                    Barang
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-[#10231e] text-white shadow-xs">
                                    Jasa
                                </span>
                            @endif
                        </div>

                        <!-- Badge RT Wilayah (Bottom Left) -->
                        <span class="absolute bottom-3 left-3 bg-[#10231e]/75 backdrop-blur-xs text-white text-[10px] font-bold px-2.5 py-0.5 rounded-lg border border-white/10">
                            RT 0{{ $listing->rt?->nomor_rt ?? 5 }}
                        </span>
                    </div>

                    <!-- Detail Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-bold text-stone-900 text-sm line-clamp-1 group-hover:text-[#10231e] transition-colors" title="{{ $listing->nama }}">
                                    {{ $listing->nama }}
                                </h3>
                                @if(auth()->check() && (int) auth()->id() === (int) $listing->user_id)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shrink-0">
                                        Usaha Anda
                                    </span>
                                @endif
                            </div>

                            <p class="text-xs text-stone-500 mt-1 flex items-center gap-1.5 truncate">
                                <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>oleh <strong class="text-stone-700 font-semibold">{{ $listing->user?->nama ?? 'Warga RT' }}</strong></span>
                            </p>

                            <p class="text-xs text-stone-600 mt-2.5 line-clamp-2 leading-relaxed">
                                {{ $listing->deskripsi }}
                            </p>
                        </div>

                        <!-- Card Footer: Harga & CTA WhatsApp / Takedown -->
                        <div class="mt-4 pt-3.5 border-t border-[#e4ded1]/70 space-y-2.5">
                            <!-- Baris Atas: Harga & Tombol Moderasi Pengurus (Takedown) -->
                            <div class="flex items-center justify-between gap-2 min-h-[30px]">
                                <div class="min-w-0">
                                    @if($listing->kategori === 'barang' && $listing->harga !== null)
                                        <span class="text-base font-extrabold text-[#10231e] tracking-tight whitespace-nowrap">
                                            {{ $listing->formatted_harga }}
                                        </span>
                                    @else
                                        <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-lg whitespace-nowrap inline-block">
                                            {{ $listing->formatted_harga }}
                                        </span>
                                    @endif
                                </div>

                                @if(auth()->check() && \App\Services\ScopeAuthorizer::canTakedownUmkm(auth()->user(), $listing))
                                    <button type="button"
                                            @click="openTakedownModal({{ json_encode($listing) }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-rose-200 bg-rose-50/70 text-rose-600 hover:bg-rose-100 active:scale-95 text-xs font-semibold transition-all cursor-pointer shrink-0"
                                            title="Takedown listing usaha ini dari etalase warga">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        <span>Takedown</span>
                                    </button>
                                @endif
                            </div>

                            <!-- Baris Bawah: CTA Utama WhatsApp (Full Width) -->
                            @if($listing->whatsapp_link)
                                <a href="{{ $listing->whatsapp_link }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-semibold text-xs transition-all shadow-2xs cursor-pointer"
                                   title="Hubungi {{ $listing->user?->nama ?? 'Penjual' }} via WhatsApp">
                                    <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                    </svg>
                                    <span>Hubungi Penjual</span>
                                </a>
                            @else
                                <div class="w-full text-center py-1.5 text-[11px] text-stone-400 italic">
                                    Kontak belum tersedia
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center justify-center">
            {{ $etalase->links() }}
        </div>
    @else
        <!-- EMPTY STATE ETALASE -->
        <div class="bg-white rounded-3xl border border-[#e4ded1] p-12 text-center shadow-2xs space-y-3">
            @if(request('q') || request('kategori'))
                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-[#e5a53f] flex items-center justify-center mx-auto mb-2 border border-amber-200">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-stone-900">Tidak menemukan produk atau jasa yang sesuai</h3>
                <p class="text-xs text-stone-500 max-w-sm mx-auto">
                    Tidak ada usaha warga yang cocok dengan kata kunci <strong>"{{ request('q') }}"</strong> @if(request('kategori')) pada kategori <strong>{{ request('kategori') }}</strong> @endif.
                </p>
                <div class="pt-2">
                    <a href="{{ route('umkm.index') }}" class="inline-flex items-center gap-1.5 px-4.5 py-2 rounded-xl bg-[#10231e] hover:bg-[#1a3830] text-white text-xs font-semibold shadow-xs transition-colors">
                        <span>Reset Pencarian & Filter</span>
                    </a>
                </div>
            @else
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 border border-emerald-100">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-stone-900">Belum ada usaha warga yang tayang</h3>
                <p class="text-xs text-stone-500 max-w-md mx-auto leading-relaxed">
                    Saat ada produk kuliner, kerajinan, atau layanan jasa warga sekitar yang disetujui pengurus RT, etalasenya akan otomatis tampil di sini.
                </p>
            @endif
        </div>
    @endif

</div>
