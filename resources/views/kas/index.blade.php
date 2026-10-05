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
                actionUrl: '/kas/' + data.id + '/koreksi'
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
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-bold text-stone-900 tracking-tight">Transparansi Kas RT</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-stone-200 text-stone-800">
                    RT 0{{ $rtTarget->nomor_rt ?? 5 }}
                </span>
            </div>
            <p class="text-xs text-stone-500 mt-1">Laporan arus kas masuk dan keluar secara terbuka, mandiri, dan akuntabel.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Jika User adalah Ketua RW atau Super Admin: Pemilih RT Binaannya -->
            @if((auth()->user()?->hasRole('ketua_rw') || auth()->user()?->is_super_admin || auth()->user()?->hasRole('super_admin')) && $availableRts->isNotEmpty())
            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-stone-200 shadow-xs">
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
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Catat Pemasukan</span>
                </button>
                <button type="button"
                        @click="openInputModal('keluar')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                    <span>Catat Pengeluaran</span>
                </button>
            </div>
            @endif
        </div>
    </div>



    <!-- 1. RINGKASAN KAS: 3 Kartu Metrik Utama -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Card 1: Saldo Kas Berjalan Saat Ini -->
        <div class="bg-gradient-to-br from-[#182222] to-[#0f1717] text-white p-5 sm:p-6 rounded-3xl shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-emerald-500/10 rounded-full blur-xl pointer-events-none"></div>
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-stone-400">Saldo Kas RT Saat Ini</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $saldoBerjalan >= 0 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $saldoBerjalan >= 0 ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                        {{ $saldoBerjalan >= 0 ? 'Surplus Kas' : 'Defisit Kas' }}
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold font-mono tracking-tight mt-3 text-white">
                    Rp {{ number_format($saldoBerjalan, 0, ',', '.') }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-stone-400">
                <span>Total Kumulatif Masuk:</span>
                <span class="font-mono text-emerald-400 font-bold">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Card 2: Pemasukan Bulan Berjalan -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-stone-200/90 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-stone-500">Uang Masuk Bulan Ini</span>
                    <div class="w-7 h-7 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold font-mono tracking-tight mt-3 text-emerald-700">
                    + Rp {{ number_format($masukBulanIni, 0, ',', '.') }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-100 text-[11px] text-stone-400">
                Periode {{ now()->translatedFormat('F Y') }}
            </div>
        </div>

        <!-- Card 3: Pengeluaran Bulan Berjalan -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-stone-200/90 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-stone-500">Uang Keluar Bulan Ini</span>
                    <div class="w-7 h-7 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold font-mono tracking-tight mt-3 text-rose-700">
                    - Rp {{ number_format($keluarBulanIni, 0, ',', '.') }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-100 text-[11px] text-stone-400">
                Periode {{ now()->translatedFormat('F Y') }}
            </div>
        </div>
    </div>

    <!-- 2. GRAFIK ARUS KAS 6 BULAN TERAKHIR (Visualisasi Awam Tanpa Library Berat) -->
    <div class="bg-white p-5 sm:p-6 rounded-3xl border border-stone-200/90 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h3 class="text-sm font-bold text-stone-900">Perbandingan Arus Kas 6 Bulan Terakhir</h3>
                <p class="text-xs text-stone-400 mt-0.5">Tren perbandingan uang masuk (iuran/sumbangan) dan uang keluar (biaya operasional/lingkungan).</p>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-md bg-emerald-500"></span>
                    <span class="text-stone-600 font-medium">Uang Masuk</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-md bg-rose-500"></span>
                    <span class="text-stone-600 font-medium">Uang Keluar</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-6 gap-2 sm:gap-4 items-end h-44 pt-6 border-b border-stone-100 pb-2">
            @foreach($grafik6Bulan as $bulanData)
                @php
                    $persenMasuk = $maxNominalGrafik > 0 ? max(6, round(($bulanData['masuk'] / $maxNominalGrafik) * 100)) : 6;
                    $persenKeluar = $maxNominalGrafik > 0 ? max(6, round(($bulanData['keluar'] / $maxNominalGrafik) * 100)) : 6;
                @endphp
                <div class="flex flex-col items-center h-full justify-end group">
                    <div class="w-full flex items-end justify-center gap-1 sm:gap-1.5 h-32">
                        <!-- Batang Masuk -->
                        <div class="w-3.5 sm:w-6 bg-emerald-500 hover:bg-emerald-600 rounded-t-md transition-all relative cursor-pointer"
                             style="height: {{ $bulanData['masuk'] > 0 ? $persenMasuk : 4 }}%;"
                             title="Masuk: Rp {{ number_format($bulanData['masuk'], 0, ',', '.') }}">
                        </div>
                        <!-- Batang Keluar -->
                        <div class="w-3.5 sm:w-6 bg-rose-500 hover:bg-rose-600 rounded-t-md transition-all relative cursor-pointer"
                             style="height: {{ $bulanData['keluar'] > 0 ? $persenKeluar : 4 }}%;"
                             title="Keluar: Rp {{ number_format($bulanData['keluar'], 0, ',', '.') }}">
                        </div>
                    </div>
                    <span class="text-[10px] sm:text-xs font-semibold text-stone-500 mt-2 truncate w-full text-center">
                        {{ $bulanData['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 3. BAR FILTER MUTASI KAS -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-stone-200 shadow-xs">
        <form method="GET" action="{{ route('kas.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            @if(request()->has('rt_id'))
                <input type="hidden" name="rt_id" value="{{ request('rt_id') }}">
            @endif

            <!-- Filter Bulan -->
            <div>
                <label class="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">Bulan</label>
                <select name="bulan" class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                    <option value="">Semua Bulan</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <!-- Filter Tahun -->
            <div>
                <label class="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">Tahun</label>
                <select name="tahun" class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                    <option value="">Semua Tahun</option>
                    @foreach($tahunList as $th)
                        <option value="{{ $th }}" {{ request('tahun') == $th ? 'selected' : '' }}>{{ $th }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Jenis -->
            <div>
                <label class="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">Jenis Transaksi</label>
                <select name="jenis" class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                    <option value="">Semua Jenis</option>
                    <option value="masuk" {{ request('jenis') === 'masuk' ? 'selected' : '' }}>Uang Masuk</option>
                    <option value="keluar" {{ request('jenis') === 'keluar' ? 'selected' : '' }}>Uang Keluar</option>
                </select>
            </div>

            <!-- Filter Kategori -->
            <div>
                <label class="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">Kategori</label>
                <select name="kategori" class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $kat)
                        <option value="{{ $kat }}" {{ request('kategori') === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tombol Terapkan & Reset -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-stone-900 hover:bg-stone-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-all">
                    Filter
                </button>
                <a href="{{ route('kas.index', request()->only('rt_id')) }}" class="px-3 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 text-xs font-semibold rounded-xl transition-all">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- 4. TABEL MUTASI KAS UTAMA -->
    <div class="bg-white rounded-3xl border border-stone-200/90 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-stone-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-stone-900">Riwayat Mutasi Kas RT</h3>
                <p class="text-xs text-stone-400 mt-0.5">Seluruh catatan pemasukan dan pengeluaran terverifikasi.</p>
            </div>
            <span class="text-xs text-stone-500 font-medium">
                Menampilkan {{ $transaksiList->count() }} dari {{ $transaksiList->total() }} catatan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-stone-50 text-[11px] font-bold text-stone-500 uppercase tracking-wider border-b border-stone-100">
                        <th class="py-3 px-4 sm:px-6">Tanggal & Waktu</th>
                        <th class="py-3 px-4">Jenis</th>
                        <th class="py-3 px-4">Kategori & Keterangan</th>
                        <th class="py-3 px-4 text-right">Nominal</th>
                        <th class="py-3 px-4">Dicatat Oleh</th>
                        <th class="py-3 px-4 sm:px-6 text-center">Status / Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 text-xs text-stone-700">
                    @forelse($transaksiList as $transaksi)
                        @php
                            $isMasuk = $transaksi->jenis === 'masuk';
                            // Riwayat koreksi aman untuk publik (tanpa IP/user_id)
                            $riwayatCollection = $transaksi->is_koreksi ? $transaksi->getRiwayatKoreksi()->map(function($r) {
                                return [
                                    'id' => $r->id,
                                    'jenis' => $r->jenis,
                                    'kategori' => $r->kategori,
                                    'nominal' => $r->nominal,
                                    'nominal_formatted' => 'Rp ' . number_format($r->nominal, 0, ',', '.'),
                                    'tanggal' => $r->tanggal->translatedFormat('d F Y'),
                                    'created_at' => $r->created_at ? $r->created_at->translatedFormat('d M Y, H:i') : null,
                                    'keterangan' => $r->keterangan,
                                    'catatan_koreksi' => $r->catatan_koreksi,
                                    'input_by_nama' => $r->inputBy?->nama ?? 'Pengurus RT',
                                    'input_by_role' => ucwords(str_replace('_', ' ', $r->inputBy?->getHighestRoleCanonical() ?? 'Pengurus')),
                                    'is_aktif' => ! $r->koreksiBerikutnya()->exists(),
                                ];
                            }) : collect();
                        @endphp
                        <tr class="hover:bg-stone-50/70 transition-colors">
                            <!-- Tanggal & Waktu -->
                            <td class="py-3.5 px-4 sm:px-6 whitespace-nowrap">
                                <span class="font-bold text-stone-900 block">
                                    {{ $transaksi->tanggal->translatedFormat('d M Y') }}
                                </span>
                                @if($transaksi->created_at && $transaksi->created_at->toDateString() !== $transaksi->tanggal->toDateString())
                                    <span class="text-[10px] text-stone-400 block font-normal">
                                        Dicatat: {{ $transaksi->created_at->translatedFormat('d/m/Y H:i') }}
                                    </span>
                                @endif
                            </td>

                            <!-- Jenis -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($isMasuk)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Uang Masuk
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Uang Keluar
                                    </span>
                                @endif
                            </td>

                            <!-- Kategori & Keterangan -->
                            <td class="py-3.5 px-4 max-w-xs sm:max-w-md">
                                <span class="font-bold text-stone-900 block text-xs">
                                    {{ $transaksi->kategori }}
                                </span>
                                <span class="text-[11px] text-stone-500 line-clamp-2 mt-0.5">
                                    {{ $transaksi->keterangan ?: '—' }}
                                </span>
                            </td>

                            <!-- Nominal -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap font-mono font-bold text-sm {{ $isMasuk ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $isMasuk ? '+' : '-' }} Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}
                            </td>

                            <!-- Dicatat Oleh -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-medium text-stone-800 block text-xs">
                                    {{ $transaksi->inputBy?->nama ?? 'Pengurus RT' }}
                                </span>
                                <span class="text-[10px] text-stone-400 capitalize">
                                    {{ str_replace('_', ' ', $transaksi->inputBy?->getHighestRoleCanonical() ?? 'Pengurus') }}
                                </span>
                            </td>

                            <!-- Status / Aksi -->
                            <td class="py-3.5 px-4 sm:px-6 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Jika merupakan hasil koreksi -->
                                    @if($transaksi->is_koreksi)
                                        <button type="button"
                                                data-riwayat="{{ json_encode($riwayatCollection) }}"
                                                data-kategori="{{ $transaksi->kategori }}"
                                                @click="openRiwayatFromButton($el)"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition-all cursor-pointer">
                                            <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>Terkoreksi ({{ $transaksi->jumlah_koreksi }}x)</span>
                                        </button>
                                    @endif

                                    <!-- Tombol Koreksi bagi Pengurus Berwenang -->
                                    @if($canManage)
                                        <button type="button"
                                                data-transaksi="{{ json_encode([
                                                    'id' => $transaksi->id,
                                                    'nominal' => $transaksi->nominal,
                                                    'jenis' => $transaksi->jenis,
                                                    'kategori' => $transaksi->kategori,
                                                    'tanggal' => $transaksi->tanggal->toDateString(),
                                                    'keterangan' => $transaksi->keterangan ?? '',
                                                ]) }}"
                                                @click="openKoreksiFromButton($el)"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-semibold bg-stone-100 hover:bg-stone-200 text-stone-700 transition-all cursor-pointer">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span>Koreksi</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-stone-400">
                                <svg class="w-10 h-10 mx-auto text-stone-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-xs font-semibold text-stone-600">Belum ada catatan transaksi kas</p>
                                <p class="text-[11px] text-stone-400 mt-0.5">Data mutasi kas RT akan ditampilkan di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transaksiList->hasPages())
            <div class="p-4 border-t border-stone-100 bg-stone-50/50">
                {{ $transaksiList->links() }}
            </div>
        @endif
    </div>

    <!-- 5. MODAL TIMELINE RIWAYAT KOREKSI (Konsumsi Publik Warga Awam) -->
    <div x-show="showRiwayatModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div @click.away="showRiwayatModal = false"
             x-show="showRiwayatModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-stone-100 max-h-[90vh] overflow-y-auto text-left relative z-10">

                <div class="flex items-start justify-between pb-4 border-b border-stone-100">
                    <div>
                        <h3 class="text-base font-bold text-stone-900" id="modal-title">Riwayat Perjalanan Koreksi Kas</h3>
                        <p class="text-xs text-stone-400 mt-0.5" x-text="'Transaksi: ' + activeTitle"></p>
                    </div>
                    <button type="button" @click="showRiwayatModal = false" class="text-stone-400 hover:text-stone-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Timeline Vertikal Sederhana -->
                <div class="mt-6 space-y-6">
                    <template x-for="(item, index) in activeRiwayat" :key="item.id">
                        <div class="relative pl-6 pb-2">
                            <!-- Garis Vertikal Timeline -->
                            <div x-show="index < activeRiwayat.length - 1" class="absolute left-2.5 top-3.5 -bottom-6 w-0.5 bg-stone-200"></div>

                            <!-- Bullet Titik Indikator -->
                            <div class="absolute left-0 top-1 w-5 h-5 rounded-full flex items-center justify-center"
                                 :class="item.is_aktif ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">
                                <span class="w-2 h-2 rounded-full" :class="item.is_aktif ? 'bg-emerald-600' : 'bg-amber-500'"></span>
                            </div>

                            <!-- Konten Tiap Versi -->
                            <div class="p-3.5 rounded-2xl border"
                                 :class="item.is_aktif ? 'bg-emerald-50/40 border-emerald-200' : 'bg-stone-50 border-stone-200/80'">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold"
                                          :class="item.is_aktif ? 'text-emerald-800' : 'text-stone-700'"
                                          x-text="index === 0 ? 'Versi Awal' : (item.is_aktif ? 'Versi Aktif Saat Ini' : 'Koreksi #' + index)">
                                    </span>
                                    <span class="text-[10px] text-stone-400" x-text="item.tanggal"></span>
                                </div>

                                <div class="mt-2 flex items-baseline justify-between">
                                    <div class="text-sm font-bold font-mono"
                                         :class="item.is_aktif ? 'text-emerald-700' : 'line-through text-stone-400'"
                                         x-text="item.nominal_formatted">
                                    </div>
                                    <div class="text-[10px] text-stone-400">
                                        Oleh: <span class="font-medium text-stone-700" x-text="item.input_by_nama"></span> (<span x-text="item.input_by_role"></span>)
                                    </div>
                                </div>

                                <p class="text-xs text-stone-600 mt-1" x-text="item.keterangan || 'Tanpa keterangan tambahan.'"></p>

                                <!-- Alasan Koreksi Resmi -->
                                <div x-show="item.catatan_koreksi" class="mt-2.5 p-2.5 rounded-xl bg-amber-50 border border-amber-200/80 text-[11px] text-amber-900 font-medium">
                                    <span class="font-bold block text-amber-950 mb-0.5">Alasan Koreksi:</span>
                                    <span x-text="item.catatan_koreksi"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-6 pt-4 border-t border-stone-100 flex justify-end">
                    <button type="button" @click="showRiwayatModal = false" class="px-4 py-2 bg-stone-900 hover:bg-stone-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-all">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    <!-- 6. MODAL CATAT PEMASUKAN / PENGELUARAN BARU (Khusus Pengurus Berwenang) -->
    @if($canManage)
    <div x-show="showInputModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div @click.away="showInputModal = false"
             x-show="showInputModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-stone-100 max-h-[90vh] overflow-y-auto text-left relative z-10">

                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-4 border-b border-stone-100">
                    <div>
                        <h3 class="text-base font-bold text-stone-900" x-text="modalJenis === 'masuk' ? 'Catat Uang Masuk (Pemasukan)' : 'Catat Uang Keluar (Pengeluaran)'"></h3>
                        <p class="text-xs text-stone-400 mt-0.5">Entri transaksi baru kas RT 0{{ $rtTarget->nomor_rt ?? 5 }}.</p>
                    </div>
                    <button type="button" @click="showInputModal = false" class="text-stone-400 hover:text-stone-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Alert Error Validasi Server -->
                @if($errors->any() && !old('is_koreksi_form'))
                <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <span class="font-bold block mb-1">Periksa kembali isian formulir:</span>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Form Input Transaksi -->
                <form action="{{ route('kas.store') }}" method="POST" @submit="isSubmitting = true" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="jenis" :value="modalJenis">

                    <!-- Pilihan Kategori Preset -->
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Kategori Transaksi <span class="text-rose-500">*</span></label>
                        <select name="kategori" x-model="selectedKategori" required class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                            <template x-for="kat in (modalJenis === 'masuk' ? kategoriPresetsMasuk : kategoriPresetsKeluar)" :key="kat">
                                <option :value="kat" x-text="kat"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Nominal Rupiah -->
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Nominal (Rp) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-xs font-bold text-stone-400">Rp</span>
                            <input type="number"
                                   name="nominal"
                                   x-model="inputNominal"
                                   required
                                   min="1"
                                   step="1"
                                   placeholder="50000"
                                   class="w-full pl-10 pr-3 py-2 text-xs font-mono font-bold bg-stone-50 border border-stone-200 rounded-xl text-stone-900 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                        </div>
                    </div>

                    <!-- Tanggal Transaksi (Bisa Backdate) -->
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Tanggal Transaksi / Kuitansi <span class="text-rose-500">*</span></label>
                        <input type="date"
                               name="tanggal"
                               required
                               value="{{ old('tanggal', date('Y-m-d')) }}"
                               class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                        <span class="text-[10px] text-stone-400 mt-1 block">* Tanggal faktual kuitansi atau serah terima uang.</span>
                    </div>

                    <!-- Keterangan Tambahan -->
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Keterangan / Rincian (Opsional)</label>
                        <textarea name="keterangan"
                                  rows="2"
                                  placeholder="Catatan belanja, nama donatur, atau nomor nota kuitansi..."
                                  class="w-full text-xs bg-stone-50 border border-stone-200 rounded-xl p-3 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">{{ old('keterangan') }}</textarea>
                    </div>

                    <!-- Tombol Aksi Simpan & Batal (Anti Double-Submit) -->
                    <div class="pt-3 border-t border-stone-100 flex items-center justify-end gap-2">
                        <button type="button"
                                @click="showInputModal = false"
                                :disabled="isSubmitting"
                                class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 text-xs font-semibold rounded-xl transition-all cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                :disabled="isSubmitting"
                                :class="modalJenis === 'masuk' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-white text-xs font-semibold rounded-xl shadow-xs transition-all cursor-pointer disabled:opacity-50">
                            <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Transaksi'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <!-- 7. MODAL KOREKSI TRANSAKSI LAMA (Khusus Pengurus Berwenang) -->
    <div x-show="showKoreksiModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div @click.away="showKoreksiModal = false"
             x-show="showKoreksiModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-stone-100 max-h-[90vh] overflow-y-auto text-left relative z-10">

                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-4 border-b border-stone-100">
                    <div>
                        <h3 class="text-base font-bold text-stone-900">Koreksi Transaksi Kas</h3>
                        <p class="text-xs text-stone-400 mt-0.5">Perbaiki kekeliruan data dengan catatan audit resmi.</p>
                    </div>
                    <button type="button" @click="showKoreksiModal = false" class="text-stone-400 hover:text-stone-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Alert Error Validasi Server -->
                @if($errors->any() && old('is_koreksi_form'))
                <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <span class="font-bold block mb-1">Gagal menyimpan koreksi:</span>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Kartu Informasi Transaksi yang Sedang Dikoreksi -->
                <div class="mt-4 p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-xs space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-amber-950 uppercase tracking-wider text-[10px]">Data Transaksi Sebelumnya</span>
                        <span class="font-mono font-bold text-stone-700" x-text="koreksiTarget.tanggal"></span>
                    </div>
                    <div class="flex items-baseline justify-between pt-1">
                        <span class="font-bold text-stone-800 text-sm" x-text="koreksiTarget.kategori"></span>
                        <span class="font-mono font-bold text-stone-600 line-through" x-text="'Rp ' + (koreksiTarget.nominalLamaFormatted || koreksiTarget.nominal)"></span>
                    </div>
                    <p class="text-[11px] text-stone-500 italic" x-text="koreksiTarget.keterangan || 'Tanpa keterangan sebelumnya.'"></p>
                    <span class="text-[10px] text-amber-900 block pt-1 border-t border-amber-200/60">
                        * Transaksi di atas tidak akan dihapus, tetapi digantikan dengan versi koreksi baru di bawah ini.
                    </span>
                </div>

                <!-- Form Koreksi Baru -->
                <form :action="koreksiTarget.actionUrl" method="POST" @submit="isSubmittingKoreksi = true" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="is_koreksi_form" value="1">
                    <input type="hidden" name="koreksi_target_id" :value="koreksiTarget.id">
                    <input type="hidden" name="nominal_lama_formatted" :value="koreksiTarget.nominalLamaFormatted">
                    <input type="hidden" name="jenis" :value="koreksiTarget.jenis">

                    <!-- Kategori Baru -->
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Kategori Transaksi <span class="text-rose-500">*</span></label>
                        <select name="kategori" x-model="koreksiTarget.kategori" required class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                            <template x-for="kat in (koreksiTarget.jenis === 'masuk' ? kategoriPresetsMasuk : kategoriPresetsKeluar)" :key="kat">
                                <option :value="kat" x-text="kat" :selected="kat === koreksiTarget.kategori"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Nominal Baru -->
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Nominal yang Benar (Rp) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-xs font-bold text-stone-400">Rp</span>
                            <input type="number"
                                   name="nominal"
                                   x-model="koreksiTarget.nominal"
                                   required
                                   min="1"
                                   step="1"
                                   class="w-full pl-10 pr-3 py-2 text-xs font-mono font-bold bg-stone-50 border border-stone-200 rounded-xl text-stone-900 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                        </div>
                    </div>

                    <!-- Tanggal Transaksi -->
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Tanggal Transaksi <span class="text-rose-500">*</span></label>
                        <input type="date"
                               name="tanggal"
                               x-model="koreksiTarget.tanggal"
                               required
                               class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                    </div>

                    <!-- Keterangan Baru -->
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Keterangan Baru (Opsional)</label>
                        <textarea name="keterangan"
                                  x-model="koreksiTarget.keterangan"
                                  rows="2"
                                  class="w-full text-xs bg-stone-50 border border-stone-200 rounded-xl p-3 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600"></textarea>
                    </div>

                    <!-- Alasan Koreksi (Wajib) -->
                    <div>
                        <label class="block text-xs font-bold text-amber-950 mb-1">
                            Alasan Koreksi Resmi <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="alasan_koreksi"
                                  x-model="koreksiTarget.alasan_koreksi"
                                  required
                                  minlength="5"
                                  maxlength="500"
                                  rows="2"
                                  placeholder="Jelaskan alasan pembetulan (misal: salah ketik nominal kuitansi kelebihan satu digit nol)..."
                                  class="w-full text-xs bg-amber-50/50 border border-amber-300 rounded-xl p-3 text-stone-900 focus:bg-white focus:ring-1 focus:ring-amber-500"></textarea>
                        <span class="text-[10px] text-stone-400 mt-1 block">* Alasan koreksi wajib diisi dan akan dicatat permanen pada riwayat publik warga & audit trail.</span>
                    </div>

                    <!-- Tombol Aksi Simpan & Batal (Anti Double-Submit) -->
                    <div class="pt-3 border-t border-stone-100 flex items-center justify-end gap-2">
                        <button type="button"
                                @click="showKoreksiModal = false"
                                :disabled="isSubmittingKoreksi"
                                class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 text-xs font-semibold rounded-xl transition-all cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                :disabled="isSubmittingKoreksi"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all cursor-pointer disabled:opacity-50">
                            <svg x-show="isSubmittingKoreksi" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isSubmittingKoreksi ? 'Menyimpan Koreksi...' : 'Simpan Koreksi Transaksi'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
