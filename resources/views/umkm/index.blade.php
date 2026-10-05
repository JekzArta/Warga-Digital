@extends('layouts.app')

@section('title', 'Katalog UMKM & Jasa Warga — Warga Digital')

@section('content')
@php
    $defaultTab = 'etalase';
    if ($canReview && (old('reject_id') || $errors->has('alasan_tolak'))) {
        $defaultTab = 'kurasi';
    } else {
        $candidateTab = request('tab', session('tab', 'etalase'));
        if ($canReview && in_array($candidateTab, ['etalase', 'usaha-saya', 'kurasi'], true)) {
            $defaultTab = $candidateTab;
        } elseif (in_array($candidateTab, ['etalase', 'usaha-saya'], true)) {
            $defaultTab = $candidateTab;
        }
    }
@endphp
<script>
function umkmApp() {
    return {
        // Tab Navigasi Aktif (etalase | usaha-saya | kurasi)
        canReview: {{ $canReview ? 'true' : 'false' }},
        activeTab: '{{ $defaultTab }}',

        // Cek Prasyarat Nomor WhatsApp Profil User
        hasNoHp: {{ auth()->check() && !empty(trim((string) auth()->user()->no_hp)) ? 'true' : 'false' }},
        userNoHp: @json(auth()->user()?->no_hp ?? ''),

        // Status Modal
        showNoHpModal: {{ $errors->has('no_hp') ? 'true' : 'false' }},
        showCreateModal: {{ ($errors->any() && !old('is_edit_form') && !$errors->has('no_hp') && !$errors->has('alasan_tolak')) || session('open_create_modal') ? 'true' : 'false' }},
        showEditModal: {{ $errors->any() && old('is_edit_form') ? 'true' : 'false' }},
        showDeleteModal: false,
        showApproveModal: false,
        showRejectModal: {{ $errors->has('alasan_tolak') ? 'true' : 'false' }},
        previewImageUrl: null,

        // Form Create State
        createKategori: @json(old('kategori', 'barang')),
        createFotoPreview: null,
        isSubmittingCreate: false,

        // Form Edit State
        editData: {
            id: @json(old('edit_id', '')),
            kategori: @json(old('kategori', 'barang')),
            nama: @json(old('nama', '')),
            deskripsi: @json(old('deskripsi', '')),
            harga: @json(old('harga', '')),
            template_pesan_wa: @json(old('template_pesan_wa', '')),
            status: @json(old('status', '')),
            alasan_tolak: @json(old('alasan_tolak', '')),
            foto_url: @json(old('foto_url', '')),
            actionUrl: @json(old('edit_id') ? route('umkm.update', old('edit_id')) : '')
        },
        editFotoPreview: null,
        isSubmittingEdit: false,

        // Form Delete State
        deleteData: {
            id: '',
            nama: '',
            actionUrl: ''
        },
        isSubmittingDelete: false,

        // Form Approve State
        approveData: {
            id: '',
            nama: '',
            actionUrl: ''
        },
        isSubmittingApprove: false,

        // Form Reject State
        rejectData: {
            id: @json(old('reject_id', '')),
            nama: @json(old('reject_nama', '')),
            actionUrl: @json(old('reject_id') ? route('umkm.tolak', old('reject_id')) : '')
        },
        isSubmittingReject: false,

        // Handler Pembukaan Form Create (No HP Gate)
        handleBukaUsaha() {
            if (!this.hasNoHp) {
                this.showNoHpModal = true;
            } else {
                this.showCreateModal = true;
            }
        },

        // Buka Modal Edit dengan Pre-filling Data Listing
        openEditModal(item) {
            this.editData = {
                id: item.id,
                kategori: item.kategori,
                nama: item.nama,
                deskripsi: item.deskripsi,
                harga: item.harga !== null ? item.harga : '',
                template_pesan_wa: item.template_pesan_wa || '',
                status: item.status,
                alasan_tolak: item.alasan_tolak || '',
                foto_url: item.foto_url || '',
                actionUrl: '{{ url('/umkm') }}/' + item.id
            };
            this.editFotoPreview = null;
            this.showEditModal = true;
        },

        // Buka Modal Konfirmasi Hapus
        openDeleteModal(item) {
            this.deleteData = {
                id: item.id,
                nama: item.nama,
                actionUrl: '{{ url('/umkm') }}/' + item.id
            };
            this.showDeleteModal = true;
        },

        // Buka Modal Konfirmasi Setujui
        openApproveModal(item) {
            this.approveData = {
                id: item.id,
                nama: item.nama,
                actionUrl: '{{ url('/umkm') }}/' + item.id + '/approve'
            };
            this.showApproveModal = true;
        },

        // Buka Modal Tolak dengan Alasan Wajib
        openRejectModal(item) {
            this.rejectData = {
                id: item.id,
                nama: item.nama,
                actionUrl: '{{ url('/umkm') }}/' + item.id + '/tolak'
            };
            this.showRejectModal = true;
        },

        // Live Preview Foto Upload
        previewImage(event, target) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (target === 'create') {
                        this.createFotoPreview = e.target.result;
                    } else if (target === 'edit') {
                        this.editFotoPreview = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        },

        // Reset Foto Preview
        clearFoto(target) {
            if (target === 'create') {
                this.createFotoPreview = null;
                const input = document.getElementById('create_foto_input');
                if (input) input.value = '';
            } else if (target === 'edit') {
                this.editFotoPreview = null;
                const input = document.getElementById('edit_foto_input');
                if (input) input.value = '';
            }
        }
    };
}
</script>

<div x-data="umkmApp()" class="space-y-6">

    <!-- 1. TOP HEADER & HERO BANNER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-bold text-stone-900 tracking-tight">Katalog UMKM & Jasa Warga</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-stone-200 text-stone-800">
                    RT 0{{ auth()->user()->rt?->nomor_rt ?? 5 }}
                </span>
            </div>
            <p class="text-xs text-stone-500 mt-1">Dukung usaha tetangga sekitar RT tanpa potongan komisi. Pesan langsung ke penjual melalui WhatsApp.</p>
        </div>

        <!-- Action CTA: Buka Usaha Warga -->
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="handleBukaUsaha()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-semibold shadow-xs transition-all cursor-pointer"
                    title="Buka usaha baru atau daftarkan produk/jasa Anda">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buka Usaha Warga</span>
            </button>
        </div>
    </div>

    <!-- 2. NAVIGASI TAB (Etalase Warga vs Usaha Saya) -->
    <div class="flex items-center gap-2 border-b border-stone-200/90">
        <button type="button"
                @click="activeTab = 'etalase'"
                :class="activeTab === 'etalase' ? 'border-emerald-600 text-stone-900 font-bold' : 'border-transparent text-stone-500 hover:text-stone-700 font-medium'"
                class="pb-3 px-2 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition-colors cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span>Etalase Warga</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-stone-100 text-stone-700">
                {{ $etalase->total() }}
            </span>
        </button>

        <button type="button"
                @click="activeTab = 'usaha-saya'"
                :class="activeTab === 'usaha-saya' ? 'border-emerald-600 text-stone-900 font-bold' : 'border-transparent text-stone-500 hover:text-stone-700 font-medium'"
                class="pb-3 px-2 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition-colors cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <span>Usaha Saya</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $usahaSaya->count() > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-100 text-stone-600' }}">
                {{ $usahaSaya->count() }}
            </span>
        </button>

        @if($canReview)
            <button type="button"
                    @click="activeTab = 'kurasi'"
                    :class="activeTab === 'kurasi' ? 'border-emerald-600 text-stone-900 font-bold' : 'border-transparent text-stone-500 hover:text-stone-700 font-medium'"
                    class="pb-3 px-2 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition-colors cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Meja Kurasi</span>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $mejaKurasi->count() > 0 ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-stone-100 text-stone-600' }}">
                    {{ $mejaKurasi->count() }}
                </span>
            </button>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: ETALASE WARGA                                                     -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'etalase'" class="space-y-6">

        <!-- FILTER KATEGORI & PENCARIAN -->
        <div class="bg-white rounded-2xl border border-stone-200/90 p-4 shadow-2xs space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <!-- Filter Kategori Tabs (Semua / Barang / Jasa) -->
                <div class="flex items-center gap-1.5 p-1 bg-stone-100/90 rounded-xl border border-stone-200/60 w-fit">
                    <a href="{{ route('umkm.index', array_filter(['q' => request('q')])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ !request('kategori') || request('kategori') === 'semua' ? 'bg-white text-stone-900 shadow-2xs' : 'text-stone-500 hover:text-stone-900' }}">
                        Semua ({{ $etalase->total() }})
                    </a>
                    <a href="{{ route('umkm.index', array_filter(['kategori' => 'barang', 'q' => request('q')])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ request('kategori') === 'barang' ? 'bg-white text-stone-900 shadow-2xs' : 'text-stone-500 hover:text-stone-900' }}">
                        Barang
                    </a>
                    <a href="{{ route('umkm.index', array_filter(['kategori' => 'jasa', 'q' => request('q')])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ request('kategori') === 'jasa' ? 'bg-white text-stone-900 shadow-2xs' : 'text-stone-500 hover:text-stone-900' }}">
                        Jasa
                    </a>
                </div>

                <!-- Search Bar (Nama & Deskripsi) -->
                <form action="{{ route('umkm.index') }}" method="GET" class="relative flex-1 sm:max-w-xs">
                    @if(request('kategori') && in_array(request('kategori'), ['barang', 'jasa']))
                        <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                    @endif
                    <div class="relative">
                        <input type="text"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="Cari produk atau jasa warga..."
                               class="w-full pl-9 pr-8 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-stone-900 placeholder:text-stone-400">
                        <svg class="w-4 h-4 text-stone-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        @if(request('q'))
                            <a href="{{ route('umkm.index', array_filter(['kategori' => request('kategori')])) }}" class="absolute right-2.5 top-2.5 text-stone-400 hover:text-stone-700" title="Hapus pencarian">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Result Summary & Active Filters Indicator -->
            <div class="pt-2 border-t border-stone-100 flex flex-wrap items-center justify-between text-xs text-stone-500 gap-2">
                <div>
                    @if($etalase->total() > 0)
                        <span>Menampilkan <strong>{{ $etalase->firstItem() }}–{{ $etalase->lastItem() }}</strong> dari <strong>{{ $etalase->total() }}</strong> usaha warga</span>
                    @else
                        <span>Tidak ada usaha yang ditampilkan</span>
                    @endif
                </div>

                @if(request('q') || request('kategori'))
                    <div class="flex items-center gap-1.5">
                        <span class="text-stone-400">Filter aktif:</span>
                        @if(request('kategori'))
                            <span class="px-2 py-0.5 rounded-md bg-stone-100 text-stone-700 font-semibold text-[11px] capitalize">
                                {{ request('kategori') }}
                            </span>
                        @endif
                        @if(request('q'))
                            <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 font-semibold text-[11px]">
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
                    <div class="bg-white rounded-2xl border border-stone-200/90 overflow-hidden shadow-2xs hover:shadow-md hover:border-emerald-600/30 transition-all duration-200 group flex flex-col justify-between">
                        <!-- Foto Produk / Layanan dengan Aspect Ratio 4:3 -->
                        <div class="h-48 bg-stone-100 relative overflow-hidden flex items-center justify-center">
                            @if($listing->foto_url)
                                <img src="{{ Storage::url($listing->foto_url) }}"
                                     alt="{{ $listing->nama }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="flex flex-col items-center justify-center text-stone-400 p-4 text-center">
                                    @if($listing->kategori === 'barang')
                                        <svg class="w-10 h-10 mb-1 text-amber-500/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    @else
                                        <svg class="w-10 h-10 mb-1 text-emerald-600/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                    <span class="text-[11px] font-medium text-stone-400">Belum ada foto</span>
                                </div>
                            @endif

                            <!-- Badge Kategori (Top Left) -->
                            <div class="absolute top-2.5 left-2.5">
                                @if($listing->kategori === 'barang')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500 text-white shadow-xs">
                                        Barang
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-700 text-white shadow-xs">
                                        Jasa
                                    </span>
                                @endif
                            </div>

                            <!-- Badge RT Wilayah (Bottom Left) -->
                            <span class="absolute bottom-2.5 left-2.5 bg-black/60 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md">
                                RT 0{{ $listing->rt?->nomor_rt ?? 5 }}
                            </span>
                        </div>

                        <!-- Detail Card Body -->
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-stone-900 text-sm line-clamp-1 group-hover:text-emerald-700 transition-colors" title="{{ $listing->nama }}">
                                    {{ $listing->nama }}
                                </h3>

                                <p class="text-xs text-stone-500 mt-1 flex items-center gap-1.5 truncate">
                                    <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>oleh <strong class="text-stone-700 font-semibold">{{ $listing->user?->nama ?? 'Warga RT' }}</strong></span>
                                </p>

                                <p class="text-xs text-stone-600 mt-2 line-clamp-2 leading-relaxed">
                                    {{ $listing->deskripsi }}
                                </p>
                            </div>

                            <!-- Card Footer: Harga & CTA WhatsApp -->
                            <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
                                <div class="flex items-baseline">
                                    @if($listing->kategori === 'barang' && $listing->harga !== null)
                                        <span class="text-base font-extrabold text-stone-900 tracking-tight">
                                            {{ $listing->formatted_harga }}
                                        </span>
                                    @else
                                        <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200/60 px-2 py-1 rounded-lg">
                                            {{ $listing->formatted_harga }}
                                        </span>
                                    @endif
                                </div>

                                @if($listing->whatsapp_link)
                                    <a href="{{ $listing->whatsapp_link }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-semibold text-xs transition-all shadow-2xs shrink-0 cursor-pointer"
                                       title="Hubungi {{ $listing->user?->nama ?? 'Penjual' }} via WhatsApp">
                                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                        </svg>
                                        <span>Hubungi Penjual</span>
                                    </a>
                                @else
                                    <span class="text-[11px] text-stone-400 italic">Kontak belum tersedia</span>
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
            <div class="bg-white rounded-2xl border border-stone-200/90 p-12 text-center shadow-2xs space-y-3">
                @if(request('q') || request('kategori'))
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-stone-900">Tidak menemukan produk atau jasa yang sesuai</h3>
                    <p class="text-xs text-stone-500 max-w-sm mx-auto">
                        Tidak ada usaha warga yang cocok dengan kata kunci <strong>"{{ request('q') }}"</strong> @if(request('kategori')) pada kategori <strong>{{ request('kategori') }}</strong> @endif.
                    </p>
                    <div class="pt-2">
                        <a href="{{ route('umkm.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-stone-900 hover:bg-stone-800 text-white text-xs font-semibold shadow-xs transition-colors">
                            <span>Reset Pencarian & Filter</span>
                        </a>
                    </div>
                @else
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-stone-900">Belum ada usaha warga yang tayang</h3>
                    <p class="text-xs text-stone-500 max-w-md mx-auto">
                        Saat ada produk kuliner, kerajinan, atau layanan jasa warga sekitar yang disetujui pengurus RT, etalasenya akan otomatis tampil di sini.
                    </p>
                @endif
            </div>
        @endif

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: USAHA SAYA                                                        -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'usaha-saya'" class="space-y-6" style="display: none;">

        <!-- Header Usaha Saya Banner -->
        <div class="bg-white rounded-2xl border border-stone-200/90 p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-stone-900">Daftar Usaha & Jasa Milik Anda</h2>
                <p class="text-xs text-stone-500 mt-0.5">Kelola produk atau layanan jasa yang Anda tawarkan ke tetangga RT. Listing baru atau perubahan substantif akan diverifikasi pengurus RT terlebih dahulu.</p>
            </div>
            <button type="button"
                    @click="handleBukaUsaha()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-all cursor-pointer shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>+ Tambah Produk / Jasa</span>
            </button>
        </div>

        @if($usahaSaya->count() > 0)
            <!-- Grid Listing Usaha Saya -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach($usahaSaya as $item)
                    <div class="bg-white rounded-2xl border border-stone-200/90 overflow-hidden shadow-2xs hover:shadow-md transition-all flex flex-col justify-between">
                        <!-- Foto & Badges -->
                        <div class="h-44 bg-stone-100 relative overflow-hidden flex items-center justify-center">
                            @if($item->foto_url)
                                <img src="{{ Storage::url($item->foto_url) }}"
                                     alt="{{ $item->nama }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="flex flex-col items-center justify-center text-stone-400 p-4 text-center">
                                    @if($item->kategori === 'barang')
                                        <svg class="w-10 h-10 mb-1 text-amber-500/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    @else
                                        <svg class="w-10 h-10 mb-1 text-emerald-600/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                    <span class="text-[11px] font-medium text-stone-400">Belum ada foto</span>
                                </div>
                            @endif

                            <!-- Kategori Badge (Top Left) -->
                            <div class="absolute top-2.5 left-2.5">
                                @if($item->kategori === 'barang')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500 text-white shadow-xs">
                                        Barang
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-700 text-white shadow-xs">
                                        Jasa
                                    </span>
                                @endif
                            </div>

                            <!-- Status Badge (Top Right) -->
                            <div class="absolute top-2.5 right-2.5">
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
                                @elseif($item->status === \App\Models\UmkmListing::STATUS_DITOLAK)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-900 border border-rose-300 shadow-xs">
                                        <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span>Ditolak</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-stone-900 text-sm line-clamp-1" title="{{ $item->nama }}">
                                    {{ $item->nama }}
                                </h3>

                                <div class="mt-1 flex items-baseline">
                                    @if($item->kategori === 'barang' && $item->harga !== null)
                                        <span class="text-sm font-extrabold text-stone-900">
                                            {{ $item->formatted_harga }}
                                        </span>
                                    @else
                                        <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-md">
                                            {{ $item->formatted_harga }}
                                        </span>
                                    @endif
                                </div>

                                <p class="text-xs text-stone-500 mt-2 line-clamp-2 leading-relaxed">
                                    {{ $item->deskripsi }}
                                </p>

                                <!-- ALASAN PENOLAKAN TRANSPARAN (Jika Ditolak) -->
                                @if($item->status === \App\Models\UmkmListing::STATUS_DITOLAK)
                                    <div class="mt-3 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1">
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
                                @elseif($item->status === \App\Models\UmkmListing::STATUS_DISETUJUI)
                                    <p class="text-[10px] text-stone-400 mt-2 italic">
                                        * Mengubah data substantif akan mengirim ulang usaha ini untuk verifikasi pengurus.
                                    </p>
                                @elseif($item->status === \App\Models\UmkmListing::STATUS_MENUNGGU)
                                    <p class="text-[10px] text-amber-700 mt-2 italic">
                                        * Usaha sedang dalam antrean verifikasi pengurus RT.
                                    </p>
                                @endif
                            </div>

                            <!-- Card Footer: Aksi Edit & Hapus -->
                            <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
                                <button type="button"
                                        @click="openEditModal({{ json_encode($item) }})"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl {{ $item->status === \App\Models\UmkmListing::STATUS_DITOLAK ? 'bg-amber-600 hover:bg-amber-700 text-white font-bold' : 'bg-stone-100 hover:bg-stone-200 text-stone-700 font-semibold' }} text-xs transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    <span>{{ $item->status === \App\Models\UmkmListing::STATUS_DITOLAK ? 'Edit & Ajukan Ulang' : 'Edit' }}</span>
                                </button>

                                <button type="button"
                                        @click="openDeleteModal({{ json_encode($item) }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-semibold transition-colors cursor-pointer"
                                        title="Hapus listing usaha ini">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State Usaha Saya -->
            <div class="bg-white rounded-2xl border border-stone-200/90 p-12 text-center shadow-2xs space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-stone-900">Anda belum memiliki usaha yang didaftarkan</h3>
                <p class="text-xs text-stone-500 max-w-md mx-auto">
                    Mulai promosikan produk kuliner, kerajinan tangan, atau layanan keahlian Anda ke tetangga sekitar RT tanpa potongan komisi.
                </p>
                <div class="pt-2">
                    <button type="button"
                            @click="handleBukaUsaha()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>+ Buka Usaha Sekarang</span>
                    </button>
                </div>
            </div>
        @endif

    </div>

    @if($canReview)
    <!-- ========================================================================= -->
    <!-- TAB 3: MEJA KURASI RT                                                    -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'kurasi'" class="space-y-6" style="display: none;">

        <!-- Header Meja Kurasi Banner -->
        <div class="bg-white rounded-2xl border border-stone-200/90 p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <h2 class="text-base font-bold text-stone-900">Meja Kurasi RT — Antrean Usaha Warga</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $mejaKurasi->count() > 0 ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-stone-100 text-stone-700' }}">
                        {{ $mejaKurasi->count() }} Menunggu Review
                    </span>
                </div>
                <p class="text-xs text-stone-500 mt-1">Periksa kelayakan pengajuan usaha baru atau revisi warga di RT 0{{ auth()->user()->rt?->nomor_rt ?? 5 }} sebelum ditampilkan di etalase publik.</p>
            </div>
            <div class="text-xs text-stone-400 bg-stone-50 border border-stone-200/70 px-3 py-1.5 rounded-xl shrink-0">
                Wewenang: <strong class="text-stone-700 font-semibold capitalize">{{ str_replace('_', ' ', auth()->user()->peran ?? 'Reviewer RT') }}</strong>
            </div>
        </div>

        @if($mejaKurasi->count() > 0)
            <!-- Antrean Kartu Kurasi -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach($mejaKurasi as $item)
                    <div class="bg-white rounded-2xl border border-stone-200/90 overflow-hidden shadow-2xs hover:shadow-md transition-all flex flex-col justify-between">
                        <div>
                            <!-- Header Card: Foto Thumbnail, Kategori, Status & Penjual -->
                            <div class="p-4 sm:p-5 flex flex-col sm:flex-row gap-4 items-start border-b border-stone-100">
                                <!-- Thumbnail Foto (Clickable for zoom) -->
                                <div class="w-full sm:w-28 h-28 bg-stone-100 rounded-xl overflow-hidden shrink-0 border border-stone-200 relative group">
                                    @if($item->foto_url)
                                        <img src="{{ Storage::url($item->foto_url) }}"
                                             alt="{{ $item->nama }}"
                                             class="w-full h-full object-cover">
                                        <button type="button"
                                                @click="previewImageUrl = '{{ Storage::url($item->foto_url) }}'"
                                                class="absolute inset-0 bg-stone-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white cursor-pointer"
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
                                    <div class="flex items-center gap-2 mb-1">
                                        @if($item->kategori === 'barang')
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500 text-white shadow-2xs">
                                                Barang
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-700 text-white shadow-2xs">
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
                                            <span class="text-sm font-extrabold text-stone-900 tracking-tight">
                                                {{ $item->formatted_harga }}
                                            </span>
                                        @else
                                            <span class="text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-md">
                                                {{ $item->formatted_harga }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-stone-500">
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
                            <div class="p-4 sm:p-5 space-y-3">
                                <div>
                                    <h4 class="text-xs font-bold text-stone-700 mb-1">Deskripsi Usaha:</h4>
                                    <p class="text-xs text-stone-600 leading-relaxed bg-stone-50 p-3 rounded-xl border border-stone-200/70 whitespace-pre-line">
                                        {{ $item->deskripsi }}
                                    </p>
                                </div>

                                @if($item->template_pesan_wa)
                                    <div>
                                        <h4 class="text-[11px] font-bold text-stone-600 mb-1 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                            <span>Template Pesan WhatsApp Kustom:</span>
                                        </h4>
                                        <p class="text-[11px] text-stone-500 italic bg-stone-50 px-3 py-2 rounded-xl border border-stone-200/50">
                                            "{{ $item->template_pesan_wa }}"
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Card Actions: Setujui & Tolak (Khusus Reviewer) -->
                        <div class="p-4 sm:p-5 bg-stone-50/80 border-t border-stone-100 flex items-center justify-end gap-2.5">
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
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-semibold shadow-2xs transition-all cursor-pointer"
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
            <div class="bg-white rounded-2xl border border-stone-200/90 p-12 text-center shadow-2xs space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-stone-900">Semua pengajuan sudah ditinjau</h3>
                <p class="text-xs text-stone-500 max-w-md mx-auto">
                    Saat ini tidak ada antrean kurasi listing UMKM baru atau perbaikan revisi yang menunggu verifikasi di RT Anda.
                </p>
            </div>
        @endif

    </div>
    @endif

    <!-- ========================================================================= -->
    <!-- MODAL 1: LENGKAPI NOMOR WHATSAPP (No HP Gate)                             -->
    <!-- ========================================================================= -->
    <div x-show="showNoHpModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div @click.away="showNoHpModal = false"
             x-show="showNoHpModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 border border-stone-100 text-left relative z-10">

            <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-stone-900">Lengkapi Nomor WhatsApp</h3>
                        <p class="text-[11px] text-stone-400">Prasyarat sebelum membuka usaha di Warga Digital</p>
                    </div>
                </div>
                <button type="button" @click="showNoHpModal = false" class="text-stone-400 hover:text-stone-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="mt-4 space-y-3 text-xs text-stone-600 leading-relaxed">
                <p>Nomor WhatsApp diperlukan agar warga lain di lingkungan RT dapat menghubungi dan memesan barang atau jasa Anda secara langsung.</p>

                @if($errors->has('no_hp'))
                    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                        {{ $errors->first('no_hp') }}
                    </div>
                @endif

                <form action="{{ route('umkm.updateNoHp') }}" method="POST" class="space-y-4 pt-1">
                    @csrf
                    <div>
                        <label for="input_no_hp" class="block font-bold text-stone-800 text-xs mb-1">
                            Nomor WhatsApp Aktif <span class="text-rose-600">*</span>
                        </label>
                        <input type="text"
                               id="input_no_hp"
                               name="no_hp"
                               value="{{ old('no_hp', auth()->user()?->no_hp) }}"
                               placeholder="Contoh: 081234567890"
                               class="w-full px-3.5 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-stone-900 font-mono">
                        <span class="text-[11px] text-stone-400 block mt-1">Gunakan format awalan 08 atau 62 (contoh: 081234567890).</span>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-stone-100">
                        <button type="button"
                                @click="showNoHpModal = false"
                                class="px-3.5 py-2 rounded-xl text-stone-600 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                            Simpan Nomor WhatsApp
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: TAMBAH LISTING BARU                                             -->
    <!-- ========================================================================= -->
    <div x-show="showCreateModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div @click.away="showCreateModal = false"
             x-show="showCreateModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-stone-100 max-h-[90vh] overflow-y-auto text-left relative z-10">

            <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                <div>
                    <h3 class="text-base font-bold text-stone-900">Buka Usaha / Tambah Produk &amp; Jasa</h3>
                    <p class="text-xs text-stone-400 mt-0.5">Lengkapi formulir untuk mengajukan listing usaha ke pengurus RT.</p>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-stone-400 hover:text-stone-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Error Validasi Server -->
            @if($errors->any() && !old('is_edit_form') && !$errors->has('no_hp'))
                <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <span class="font-bold block mb-1">Terdapat kesalahan pengisian:</span>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('umkm.store') }}" method="POST" enctype="multipart/form-data" @submit="isSubmittingCreate = true" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="kategori" :value="createKategori">

                <!-- 1. Kategori Toggle -->
                <div>
                    <label class="block font-bold text-stone-800 text-xs mb-1.5">
                        Jenis Usaha <span class="text-rose-600">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-stone-100 rounded-xl border border-stone-200/80">
                        <button type="button"
                                @click="createKategori = 'barang'"
                                :class="createKategori === 'barang' ? 'bg-white text-stone-900 shadow-2xs font-bold' : 'text-stone-500 hover:text-stone-900 font-medium'"
                                class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <span>Barang / Produk</span>
                        </button>
                        <button type="button"
                                @click="createKategori = 'jasa'"
                                :class="createKategori === 'jasa' ? 'bg-white text-stone-900 shadow-2xs font-bold' : 'text-stone-500 hover:text-stone-900 font-medium'"
                                class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Layanan Jasa</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Nama Listing -->
                <div>
                    <label for="create_nama" class="block font-bold text-stone-800 text-xs mb-1">
                        Nama Produk atau Layanan Jasa <span class="text-rose-600">*</span>
                    </label>
                    <input type="text"
                           id="create_nama"
                           name="nama"
                           value="{{ old('nama') }}"
                           required
                           placeholder="Contoh: Keripik Tempe Bu Siti / Jasa Servis AC"
                           class="w-full px-3.5 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-stone-900">
                </div>

                <!-- 3. Deskripsi -->
                <div>
                    <label for="create_deskripsi" class="block font-bold text-stone-800 text-xs mb-1">
                        Deskripsi Usaha <span class="text-rose-600">*</span>
                    </label>
                    <textarea id="create_deskripsi"
                              name="deskripsi"
                              rows="3"
                              required
                              placeholder="Jelaskan produk, bahan, atau jenis layanan jasa Anda secara jelas agar warga tetangga mudah memahami..."
                              class="w-full px-3.5 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-stone-900 leading-relaxed">{{ old('deskripsi') }}</textarea>
                </div>

                <!-- 4. Harga -->
                <div>
                    <label for="create_harga" class="block font-bold text-stone-800 text-xs mb-1">
                        <span x-text="createKategori === 'barang' ? 'Harga Jual (Rp)' : 'Tarif / Patokan Harga (Rp) — Opsional'"></span>
                        <span x-show="createKategori === 'barang'" class="text-rose-600">*</span>
                    </label>
                    <input type="number"
                           id="create_harga"
                           name="harga"
                           value="{{ old('harga') }}"
                           min="0"
                           step="500"
                           placeholder="Contoh: 15000"
                           class="w-full px-3.5 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-stone-900 font-mono">
                    <span class="text-[11px] text-stone-400 block mt-1"
                          x-text="createKategori === 'barang' ? '* Masukkan harga jual dalam nominal angka (contoh: 25000).' : '* Untuk jasa, boleh dikosongkan jika tarif disesuaikan dengan negosiasi / jenis pekerjaan.'"></span>
                </div>

                <!-- 5. Foto Upload -->
                <div>
                    <label class="block font-bold text-stone-800 text-xs mb-1">
                        Foto Produk atau Layanan (Opsional)
                    </label>
                    <input type="file"
                           id="create_foto_input"
                           name="foto"
                           accept="image/jpeg,image/png,image/webp"
                           @change="previewImage($event, 'create')"
                           class="w-full text-xs text-stone-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 file:text-stone-700 hover:file:bg-stone-200 cursor-pointer">
                    <span class="text-[11px] text-stone-400 block mt-1">Format gambar: JPG, PNG, atau WebP (maks. 2MB).</span>

                    <!-- Live Image Preview -->
                    <template x-if="createFotoPreview">
                        <div class="mt-2 relative w-32 h-24 rounded-xl overflow-hidden border border-stone-200 bg-stone-50">
                            <img :src="createFotoPreview" class="w-full h-full object-cover" alt="Pratinjau foto baru">
                            <button type="button"
                                    @click="clearFoto('create')"
                                    class="absolute top-1 right-1 bg-stone-900/70 text-white rounded-full p-1 hover:bg-stone-900 transition-colors"
                                    title="Hapus foto">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- 6. Template Pesan WhatsApp Kustom -->
                <div>
                    <label for="create_template_pesan_wa" class="block font-bold text-stone-800 text-xs mb-1">
                        Template Pesan WhatsApp Pembeli (Opsional)
                    </label>
                    <textarea id="create_template_pesan_wa"
                              name="template_pesan_wa"
                              rows="2"
                              placeholder="Contoh: Halo Kak, saya tertarik dengan produk ini di Warga Digital. Apakah masih ada stok?"
                              class="w-full px-3.5 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-stone-900 leading-relaxed">{{ old('template_pesan_wa') }}</textarea>
                    <span class="text-[11px] text-stone-400 block mt-1">
                        Pesan awal yang otomatis terisi saat pembeli menekan tombol WhatsApp. Jika kosong, sistem menggunakan format standar.
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-stone-50 border border-stone-200 text-stone-600 text-[11px] flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Listing baru akan berstatus <strong>Menunggu Verifikasi</strong> sebelum tayang di etalase warga.</span>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-stone-100">
                    <button type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 rounded-xl text-stone-600 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="isSubmittingCreate"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50">
                        <span x-text="isSubmittingCreate ? 'Mengajukan...' : 'Ajukan Usaha Warga'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: EDIT / REVISI LISTING                                           -->
    <!-- ========================================================================= -->
    <div x-show="showEditModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div @click.away="showEditModal = false"
             x-show="showEditModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-stone-100 max-h-[90vh] overflow-y-auto text-left relative z-10">

            <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                <div>
                    <h3 class="text-base font-bold text-stone-900"
                        x-text="editData.status === 'DITOLAK' ? 'Revisi & Ajukan Ulang Usaha' : 'Edit Data Usaha'"></h3>
                    <p class="text-xs text-stone-400 mt-0.5">Perbarui informasi listing usaha Anda.</p>
                </div>
                <button type="button" @click="showEditModal = false" class="text-stone-400 hover:text-stone-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Konteks Banner Status Khusus -->
            <template x-if="editData.status === 'DITOLAK'">
                <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1">
                    <span class="font-bold text-rose-800 block">Alasan Penolakan Pengurus:</span>
                    <p class="font-medium text-rose-950" x-text="'&ldquo;' + editData.alasan_tolak + '&rdquo;'"></p>
                    <span class="text-[10px] text-rose-600 block pt-1">
                        Perbaiki informasi yang diminta di bawah ini. Setelah Anda menyimpan perbaikan, status listing akan kembali ke <strong>Menunggu Verifikasi</strong>.
                    </span>
                </div>
            </template>

            <template x-if="editData.status === 'DISETUJUI'">
                <div class="mt-4 p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                    <span class="font-bold block mb-0.5">Perhatian Perubahan Data:</span>
                    <span class="text-amber-800 leading-relaxed block text-[11px]">
                        Mengubah informasi utama (nama, kategori, deskripsi, harga, foto, atau template WhatsApp) akan mengembalikan status usaha ini ke <strong>Menunggu Verifikasi</strong> untuk dikurasi ulang oleh pengurus RT.
                    </span>
                </div>
            </template>

            <!-- Error Validasi Server Edit -->
            @if($errors->any() && old('is_edit_form'))
                <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <span class="font-bold block mb-1">Gagal memperbarui listing:</span>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form :action="editData.actionUrl" method="POST" enctype="multipart/form-data" @submit="isSubmittingEdit = true" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_edit_form" value="1">
                <input type="hidden" name="edit_id" :value="editData.id">
                <input type="hidden" name="kategori" :value="editData.kategori">

                <!-- 1. Kategori Toggle -->
                <div>
                    <label class="block font-bold text-stone-800 text-xs mb-1.5">
                        Jenis Usaha <span class="text-rose-600">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-stone-100 rounded-xl border border-stone-200/80">
                        <button type="button"
                                @click="editData.kategori = 'barang'"
                                :class="editData.kategori === 'barang' ? 'bg-white text-stone-900 shadow-2xs font-bold' : 'text-stone-500 hover:text-stone-900 font-medium'"
                                class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <span>Barang / Produk</span>
                        </button>
                        <button type="button"
                                @click="editData.kategori = 'jasa'"
                                :class="editData.kategori === 'jasa' ? 'bg-white text-stone-900 shadow-2xs font-bold' : 'text-stone-500 hover:text-stone-900 font-medium'"
                                class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Layanan Jasa</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Nama Listing -->
                <div>
                    <label for="edit_nama" class="block font-bold text-stone-800 text-xs mb-1">
                        Nama Produk atau Layanan Jasa <span class="text-rose-600">*</span>
                    </label>
                    <input type="text"
                           id="edit_nama"
                           name="nama"
                           x-model="editData.nama"
                           required
                           class="w-full px-3.5 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-stone-900">
                </div>

                <!-- 3. Deskripsi -->
                <div>
                    <label for="edit_deskripsi" class="block font-bold text-stone-800 text-xs mb-1">
                        Deskripsi Usaha <span class="text-rose-600">*</span>
                    </label>
                    <textarea id="edit_deskripsi"
                              name="deskripsi"
                              rows="3"
                              x-model="editData.deskripsi"
                              required
                              class="w-full px-3.5 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-stone-900 leading-relaxed"></textarea>
                </div>

                <!-- 4. Harga -->
                <div>
                    <label for="edit_harga" class="block font-bold text-stone-800 text-xs mb-1">
                        <span x-text="editData.kategori === 'barang' ? 'Harga Jual (Rp)' : 'Tarif / Patokan Harga (Rp) — Opsional'"></span>
                        <span x-show="editData.kategori === 'barang'" class="text-rose-600">*</span>
                    </label>
                    <input type="number"
                           id="edit_harga"
                           name="harga"
                           x-model="editData.harga"
                           min="0"
                           step="500"
                           class="w-full px-3.5 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-stone-900 font-mono">
                    <span class="text-[11px] text-stone-400 block mt-1"
                          x-text="editData.kategori === 'barang' ? '* Masukkan harga jual dalam nominal angka.' : '* Untuk jasa, boleh dikosongkan jika tarif disesuaikan dengan negosiasi.'"></span>
                </div>

                <!-- 5. Foto Upload -->
                <div>
                    <label class="block font-bold text-stone-800 text-xs mb-1">
                        Foto Produk atau Layanan
                    </label>

                    <!-- Preview Foto Lama & Baru -->
                    <div class="flex items-center gap-3 mb-2">
                        <template x-if="editData.foto_url && !editFotoPreview">
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-14 rounded-xl overflow-hidden border border-stone-200 bg-stone-50">
                                    <img :src="'/storage/' + editData.foto_url" class="w-full h-full object-cover" alt="Foto saat ini">
                                </div>
                                <span class="text-[11px] text-stone-500">Foto saat ini</span>
                            </div>
                        </template>

                        <template x-if="editFotoPreview">
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-14 rounded-xl overflow-hidden border border-emerald-300 bg-emerald-50 relative">
                                    <img :src="editFotoPreview" class="w-full h-full object-cover" alt="Foto baru">
                                </div>
                                <div>
                                    <span class="text-[11px] text-emerald-800 font-semibold block">Pratinjau foto baru</span>
                                    <button type="button" @click="clearFoto('edit')" class="text-[10px] text-rose-600 hover:underline">Batal ganti</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <input type="file"
                           id="edit_foto_input"
                           name="foto"
                           accept="image/jpeg,image/png,image/webp"
                           @change="previewImage($event, 'edit')"
                           class="w-full text-xs text-stone-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 file:text-stone-700 hover:file:bg-stone-200 cursor-pointer">
                    <span class="text-[11px] text-stone-400 block mt-1">Biarkan kosong jika tidak ingin mengganti foto saat ini. Format JPG, PNG, WebP (maks. 2MB).</span>
                </div>

                <!-- 6. Template Pesan WhatsApp Kustom -->
                <div>
                    <label for="edit_template_pesan_wa" class="block font-bold text-stone-800 text-xs mb-1">
                        Template Pesan WhatsApp Pembeli (Opsional)
                    </label>
                    <textarea id="edit_template_pesan_wa"
                              name="template_pesan_wa"
                              rows="2"
                              x-model="editData.template_pesan_wa"
                              placeholder="Contoh: Halo Kak, saya tertarik dengan produk ini di Warga Digital..."
                              class="w-full px-3.5 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-stone-900 leading-relaxed"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-stone-100">
                    <button type="button"
                            @click="showEditModal = false"
                            class="px-4 py-2 rounded-xl text-stone-600 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="isSubmittingEdit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50">
                        <span x-text="isSubmittingEdit ? 'Menyimpan...' : (editData.status === 'DITOLAK' ? 'Kirim Ulang Revisi' : 'Simpan Perubahan')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 4: KONFIRMASI HAPUS LISTING                                        -->
    <!-- ========================================================================= -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div @click.away="showDeleteModal = false"
             x-show="showDeleteModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 border border-stone-100 text-left relative z-10">

            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <div class="text-center space-y-2">
                <h3 class="text-base font-bold text-stone-900">Hapus Listing Usaha?</h3>
                <p class="text-xs text-stone-500 leading-relaxed">
                    Apakah Anda yakin ingin menghapus listing <strong class="text-stone-800" x-text="'&ldquo;' + deleteData.nama + '&rdquo;'"></strong>? Tindakan ini permanen dan berkas foto produk akan ikut dibersihkan.
                </p>
            </div>

            <form :action="deleteData.actionUrl" method="POST" @submit="isSubmittingDelete = true" class="mt-5 flex items-center justify-center gap-2">
                @csrf
                @method('DELETE')
                <button type="button"
                        @click="showDeleteModal = false"
                        class="w-1/2 px-4 py-2 rounded-xl text-stone-700 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer text-center">
                    Batal
                </button>
                <button type="submit"
                        :disabled="isSubmittingDelete"
                        class="w-1/2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer text-center disabled:opacity-50">
                    <span x-text="isSubmittingDelete ? 'Menghapus...' : 'Ya, Hapus'"></span>
                </button>
            </form>
        </div>
    </div>

    @if($canReview)
    <!-- ========================================================================= -->
    <!-- MODAL 5: KONFIRMASI SETUJUI LISTING (Meja Kurasi)                        -->
    <!-- ========================================================================= -->
    <div x-show="showApproveModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div @click.away="showApproveModal = false"
             x-show="showApproveModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 border border-stone-100 text-left relative z-10">

            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <div class="text-center space-y-2">
                <h3 class="text-base font-bold text-stone-900">Setujui Usaha Warga?</h3>
                <p class="text-xs text-stone-500 leading-relaxed">
                    Setelah disetujui, listing <strong class="text-stone-800" x-text="'&ldquo;' + approveData.nama + '&rdquo;'"></strong> akan langsung tayang di <strong>Etalase Warga</strong> dan dapat dilihat serta dihubungi oleh warga tetangga melalui WhatsApp.
                </p>
            </div>

            <form :action="approveData.actionUrl" method="POST" @submit="isSubmittingApprove = true" class="mt-5 flex items-center justify-center gap-2">
                @csrf
                <button type="button"
                        @click="showApproveModal = false"
                        class="w-1/2 px-4 py-2 rounded-xl text-stone-700 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer text-center">
                    Batal
                </button>
                <button type="submit"
                        :disabled="isSubmittingApprove"
                        class="w-1/2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer text-center disabled:opacity-50">
                    <span x-text="isSubmittingApprove ? 'Memproses...' : 'Ya, Setujui'"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 6: TOLAK LISTING DENGAN ALASAN WAJIB (Meja Kurasi)                 -->
    <!-- ========================================================================= -->
    <div x-show="showRejectModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div @click.away="showRejectModal = false"
             x-show="showRejectModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 border border-stone-100 text-left relative z-10">

            <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-stone-900">Tolak Pengajuan Usaha</h3>
                        <p class="text-[11px] text-stone-400">Listing: <strong class="text-stone-700" x-text="rejectData.nama"></strong></p>
                    </div>
                </div>
                <button type="button" @click="showRejectModal = false" class="text-stone-400 hover:text-stone-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="mt-4 space-y-3 text-xs text-stone-600 leading-relaxed">
                <p>Berikan alasan penolakan yang spesifik dan jelas agar warga dapat mengetahui hal yang perlu diperbaiki sebelum mengajukan ulang.</p>

                @if($errors->has('alasan_tolak'))
                    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                        {{ $errors->first('alasan_tolak') }}
                    </div>
                @endif

                <form :action="rejectData.actionUrl" method="POST" @submit="isSubmittingReject = true" class="space-y-4 pt-1">
                    @csrf
                    <input type="hidden" name="reject_id" :value="rejectData.id">
                    <input type="hidden" name="reject_nama" :value="rejectData.nama">

                    <div>
                        <label for="reject_alasan_tolak" class="block font-bold text-stone-800 text-xs mb-1">
                            Alasan Penolakan <span class="text-rose-600">*</span>
                        </label>
                        <textarea id="reject_alasan_tolak"
                                  name="alasan_tolak"
                                  rows="3"
                                  required
                                  minlength="5"
                                  maxlength="1000"
                                  placeholder="Contoh: Deskripsi usaha belum menjelaskan jenis layanan yang ditawarkan secara lengkap..."
                                  class="w-full px-3.5 py-2 text-xs rounded-xl bg-stone-50 border border-stone-200 focus:bg-white focus:border-rose-600 focus:ring-1 focus:ring-rose-600 transition-all text-stone-900 leading-relaxed">{{ old('alasan_tolak') }}</textarea>
                        <span class="text-[11px] text-stone-400 block mt-1">Minimal 5 karakter. Alasan ini akan ditampilkan secara transparan kepada pemilik usaha.</span>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-stone-100">
                        <button type="button"
                                @click="showRejectModal = false"
                                class="px-3.5 py-2 rounded-xl text-stone-600 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                :disabled="isSubmittingReject"
                                class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50">
                            <span x-text="isSubmittingReject ? 'Menolak...' : 'Kirim Penolakan'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 7: PRATINJAU FOTO PRODUK / JASA (Lightweight Image Zoom)           -->
    <!-- ========================================================================= -->
    <div x-show="previewImageUrl"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs"
         @click="previewImageUrl = null"
         style="display: none;">
        <div class="relative max-w-2xl max-h-[85vh] p-2" @click.stop>
            <button type="button"
                    @click="previewImageUrl = null"
                    class="absolute -top-3 -right-3 bg-stone-800 text-white rounded-full p-1.5 hover:bg-stone-700 shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <img :src="previewImageUrl" class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl object-contain mx-auto" alt="Foto produk">
        </div>
    </div>
    @endif

</div>
@endsection
