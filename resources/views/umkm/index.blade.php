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
        showCreateModal: {{ ($errors->any() && !old('is_edit_form') && !$errors->has('no_hp') && !$errors->has('alasan_tolak') && !$errors->has('alasan_takedown')) || session('open_create_modal') ? 'true' : 'false' }},
        showEditModal: {{ $errors->any() && old('is_edit_form') ? 'true' : 'false' }},
        showDeleteModal: false,
        showNonaktifModal: false,
        showAktifkanModal: false,
        showTakedownModal: {{ $errors->has('alasan_takedown') ? 'true' : 'false' }},
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
            alasan_takedown: @json(old('alasan_takedown', '')),
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

        // Form Nonaktifkan State (Pemilik)
        nonaktifData: {
            id: '',
            nama: '',
            actionUrl: ''
        },
        isSubmittingNonaktif: false,

        // Form Aktifkan Kembali State (Pemilik)
        aktifkanData: {
            id: '',
            nama: '',
            actionUrl: ''
        },
        isSubmittingAktifkan: false,

        // Form Takedown State (Pengurus)
        takedownData: {
            id: @json(old('takedown_id', '')),
            nama: @json(old('takedown_nama', '')),
            actionUrl: @json(old('takedown_id') ? route('umkm.takedown', old('takedown_id')) : '')
        },
        isSubmittingTakedown: false,

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
                alasan_takedown: item.alasan_takedown || '',
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

        // Buka Modal Konfirmasi Nonaktifkan (Pemilik)
        openNonaktifModal(item) {
            this.nonaktifData = {
                id: item.id,
                nama: item.nama,
                actionUrl: '{{ url('/umkm') }}/' + item.id + '/nonaktifkan'
            };
            this.showNonaktifModal = true;
        },

        // Buka Modal Konfirmasi Aktifkan Kembali (Pemilik)
        openAktifkanModal(item) {
            this.aktifkanData = {
                id: item.id,
                nama: item.nama,
                actionUrl: '{{ url('/umkm') }}/' + item.id + '/aktifkan'
            };
            this.showAktifkanModal = true;
        },

        // Buka Modal Konfirmasi Takedown (Pengurus)
        openTakedownModal(item) {
            this.takedownData = {
                id: item.id,
                nama: item.nama,
                actionUrl: '{{ url('/umkm') }}/' + item.id + '/takedown'
            };
            this.showTakedownModal = true;
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
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#10231e]/10 text-[#10231e] border border-[#10231e]/20">
                    RT 0{{ auth()->user()->rt?->nomor_rt ?? 5 }}
                </span>
            </div>
            <p class="text-xs text-stone-500 mt-1">Dukung usaha tetangga sekitar RT tanpa potongan komisi. Pesan langsung ke penjual melalui WhatsApp.</p>
        </div>

        <!-- Action CTA: Buka Usaha Warga -->
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="handleBukaUsaha()"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#10231e] hover:bg-[#1a3830] active:scale-95 text-white text-xs font-semibold shadow-xs transition-all cursor-pointer"
                    title="Buka usaha baru atau daftarkan produk/jasa Anda">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buka Usaha Warga</span>
            </button>
        </div>
    </div>

    {{-- 2. NAVIGASI TAB (Etalase Warga vs Usaha Saya vs Kurasi) --}}
    <div class="flex items-center gap-2 border-b border-[#e4ded1]">
        <button type="button"
                @click="activeTab = 'etalase'"
                :class="activeTab === 'etalase' ? 'border-[#10231e] text-stone-900 font-bold' : 'border-transparent text-stone-500 hover:text-stone-700 font-medium'"
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
                :class="activeTab === 'usaha-saya' ? 'border-[#10231e] text-stone-900 font-bold' : 'border-transparent text-stone-500 hover:text-stone-700 font-medium'"
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
                    :class="activeTab === 'kurasi' ? 'border-[#10231e] text-stone-900 font-bold' : 'border-transparent text-stone-500 hover:text-stone-700 font-medium'"
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

    <!-- 3. KONTEN TAB -->
    @include('umkm.partials.tab-etalase')

    @include('umkm.partials.tab-usaha-saya')

    @if($canReview)
        @include('umkm.partials.tab-kurasi')
    @endif

    <!-- 4. MODAL-MODAL INTERAKTIF -->
    @include('umkm.partials.modals.modal-no-hp')
    @include('umkm.partials.modals.modal-create')
    @include('umkm.partials.modals.modal-edit')
    @include('umkm.partials.modals.modal-delete')
    @include('umkm.partials.modals.modal-nonaktif')
    @include('umkm.partials.modals.modal-aktifkan')
    @include('umkm.partials.modals.modal-takedown')

    @if($canReview)
        @include('umkm.partials.modals.modal-approve')
        @include('umkm.partials.modals.modal-reject')
        @include('umkm.partials.modals.modal-lightbox')
    @endif

</div>
@endsection
