@extends('layouts.app')

@section('title', 'Transparansi Kas RT — Warga Digital')

@section('content')
<script>
function kasManager() {
    return {
        // Modal Riwayat Koreksi (Publik Warga)
        showRiwayatModal: false,
        activeRiwayat: [],
        activeTitle: '',
        openRiwayat(riwayat, title) {
            this.activeRiwayat = riwayat || [];
            this.activeTitle = title || '';
            this.showRiwayatModal = true;
        },
        openRiwayatFromButton(el) {
            try {
                const riwayat = JSON.parse(el.getAttribute('data-riwayat') || '[]');
                const title = el.getAttribute('data-kategori') || '';
                this.openRiwayat(riwayat, title);
            } catch (e) {
                console.error('Gagal membuka riwayat:', e);
            }
        },

        // Modal Input Transaksi (Pengurus)
        showInputModal: {{ $errors->any() && !old('is_koreksi_form') ? 'true' : 'false' }},
        modalJenis: @json(old('jenis', 'masuk')),
        selectedKategori: @json(old('kategori', '')),
        inputNominal: @json(old('nominal', '')),
        isSubmitting: false,
        kategoriPresetsMasuk: @json(\App\Models\KasTransaksi::KATEGORI_MASUK_PRESETS),
        kategoriPresetsKeluar: @json(\App\Models\KasTransaksi::KATEGORI_KELUAR_PRESETS),

        openInputModal(jenis) {
            this.modalJenis = jenis;
            this.selectedKategori = jenis === 'masuk' ? this.kategoriPresetsMasuk[1] : this.kategoriPresetsKeluar[0];
            this.inputNominal = '';
            this.isSubmitting = false;
            this.showInputModal = true;
        },

        // Modal Koreksi Transaksi (Pengurus)
        showKoreksiModal: {{ $errors->any() && old('is_koreksi_form') ? 'true' : 'false' }},
        koreksiTarget: {
            id: @json(old('koreksi_target_id', '')),
            nominalLamaFormatted: @json(old('nominal_lama_formatted', '')),
            nominal: @json(old('nominal', '')),
            jenis: @json(old('jenis', 'keluar')),
            kategori: @json(old('kategori', '')),
            tanggal: @json(old('tanggal', date('Y-m-d'))),
            keterangan: @json(old('keterangan', '')),
            alasan_koreksi: @json(old('alasan_koreksi', '')),
            actionUrl: @json(old('koreksi_target_id') ? route('kas.koreksi', old('koreksi_target_id')) : '')
        },
        isSubmittingKoreksi: false,

        openKoreksiModal(data) {
            this.koreksiTarget = {
                id: data.id,
                nominalLamaFormatted: new Intl.NumberFormat('id-ID').format(data.nominal),
                nominal: data.nominal,
                jenis: data.jenis,
                kategori: data.kategori,
                tanggal: data.tanggal,
                keterangan: data.keterangan || '',
                alasan_koreksi: '',
                actionUrl: '{{ url('/kas') }}/' + data.id + '/koreksi'
            };
            this.isSubmittingKoreksi = false;
            this.showKoreksiModal = true;
        },
        openKoreksiFromButton(el) {
            try {
                const data = JSON.parse(el.getAttribute('data-transaksi') || '{}');
                this.openKoreksiModal(data);
            } catch (e) {
                console.error('Gagal membuka modal koreksi:', e);
            }
        }
    };
}
</script>

<div x-data="kasManager()"
     @open-kas-modal.window="openInputModal($event.detail.jenis)"
     @open-koreksi-modal.window="openKoreksiModal($event.detail)"
     class="space-y-6">

    <!-- TOP HEADER: Judul & Kontrol Pengurus / Pemilih RT RW -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-stone-200/90">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-50 text-emerald-800 font-bold text-sm border border-emerald-100/80 shadow-2xs">
                    <svg class="w-4.5 h-4.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <h1 class="text-xl font-bold text-stone-900 tracking-tight">Transparansi Kas RT</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-stone-100 text-stone-700 border border-stone-200/60">
                    RT 0{{ $rtTarget->nomor_rt ?? 5 }}
                </span>
            </div>
            <p class="text-xs text-stone-500 mt-1 leading-relaxed">Laporan arus kas masuk dan keluar secara terbuka, mandiri, dan akuntabel.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Jika User adalah Ketua RW atau Super Admin: Pemilih RT Binaannya -->
            @if((auth()->user()?->hasRole('ketua_rw') || auth()->user()?->is_super_admin || auth()->user()?->hasRole('super_admin')) && $availableRts->isNotEmpty())
            <div class="flex items-center gap-2 bg-stone-50 px-3 py-1.5 rounded-xl border border-stone-200/80 shadow-2xs">
                <span class="text-xs font-medium text-stone-500">Pilih RT:</span>
                <select onchange="window.location.href = '?rt_id=' + this.value" class="text-xs font-bold text-stone-900 bg-transparent border-none focus:ring-0 cursor-pointer">
                    @foreach($availableRts as $rt)
                        <option value="{{ $rt->id }}" {{ $targetRtId === $rt->id ? 'selected' : '' }}>
                            RT 0{{ $rt->nomor_rt }} ({{ $rt->nama }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Tombol Aksi Pengurus (Bendahara, Ketua RT, Wakil RT) -->
            @if($canManage)
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="openInputModal('masuk')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Catat Pemasukan</span>
                </button>
                <button type="button"
                        @click="openInputModal('keluar')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                    <span>Catat Pengeluaran</span>
                </button>
            </div>
            @endif
        </div>
    </div>

    <!-- 1. Ringkasan Kartu Saldo Berjalan, Masuk, Keluar -->
    @include('kas.partials.ringkasan-kartu')

    <!-- 2. Grafik Arus Kas 6 Bulan Terakhir -->
    @include('kas.partials.grafik-arus-kas')

    <!-- 3. Bar Filter Mutasi Kas -->
    @include('kas.partials.bar-filter')

    <!-- 4. Tabel Mutasi Kas Utama -->
    @include('kas.partials.tabel-transaksi')

    <!-- 5. Modal Timeline Riwayat Koreksi -->
    @include('kas.partials.modals.modal-riwayat')

    <!-- 6. Modal Catat Transaksi Baru (Pengurus) -->
    @include('kas.partials.modals.modal-input')

    <!-- 7. Modal Koreksi Transaksi Lama (Pengurus) -->
    @include('kas.partials.modals.modal-koreksi')
</div>
@endsection
