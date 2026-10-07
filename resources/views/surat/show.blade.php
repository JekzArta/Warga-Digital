@extends('layouts.app')

@section('title', 'Detail Surat ' . $surat->jenis_surat . ' — Warga Digital')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Breadcrumb & Back Link -->
    <div class="flex items-center justify-between text-xs text-stone-500">
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="hover:text-stone-800 transition-colors">Beranda</a>
            <span>/</span>
            <a href="{{ route('surat.index') }}" class="hover:text-stone-800 transition-colors">Pengajuan Surat</a>
            <span>/</span>
            <span class="text-stone-900 font-bold font-mono">WD-SRT-{{ str_pad($surat->id, 4, '0', STR_PAD_LEFT) }}</span>
        </div>

        @if($isPengurusRt)
        <a href="{{ route('admin.surat.index') }}" class="font-bold text-emerald-800 hover:text-emerald-950 flex items-center gap-1 transition-colors">
            <span>&larr;</span>
            <span>Kembali ke Meja Verifikasi</span>
        </a>
        @else
        <a href="{{ route('surat.index') }}" class="font-bold text-stone-600 hover:text-stone-900 flex items-center gap-1 transition-colors">
            <span>&larr;</span>
            <span>Kembali ke Status Saya</span>
        </a>
        @endif
    </div>

    <!-- 1. HEADER RINGKASAN SURAT & STATUS BADGE -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-stone-200/90 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-5">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-full bg-[#10231e] text-emerald-300 font-mono text-xs font-bold">
                    {{ $surat->jenis_surat }}
                </span>
                <span class="text-xs text-stone-400">Diajukan pada {{ $surat->created_at->translatedFormat('d F Y, H:i') }} WIB</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">{{ $namaJenis }}</h1>
            <p class="text-xs text-stone-500">
                Pemohon: <span class="font-bold text-stone-800">{{ $surat->user->nama }}</span> 
                (RT 0{{ $surat->rt->nomor_rt ?? 5 }} / RW 0{{ $surat->rt->rw->nomor_rw ?? 3 }})
                • <span class="font-mono text-stone-400">{{ $surat->user->kode_warga ?? ('WRG-RT0' . ($surat->rt->nomor_rt ?? 5) . '-' . str_pad($surat->user->id, 3, '0', STR_PAD_LEFT)) }}</span>
            </p>
        </div>

        <div class="shrink-0">
            @if($surat->status === 'DISETUJUI')
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold text-xs shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                <span>Disetujui RT</span>
            </div>
            @elseif($surat->status === 'PERLU_KELENGKAPAN')
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-50 text-amber-800 border border-amber-300 font-bold text-xs shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Perlu Kelengkapan Dokumen</span>
            </div>
            @elseif($surat->status === 'DITOLAK')
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-rose-50 text-rose-800 border border-rose-300 font-bold text-xs shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span>
                <span>Permohonan Ditolak</span>
            </div>
            @elseif($surat->status === 'DIREVIEW')
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-sky-50 text-sky-800 border border-sky-300 font-bold text-xs shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-sky-600 animate-pulse"></span>
                <span>Sedang Ditinjau Pengurus RT</span>
            </div>
            @else
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-stone-100 text-stone-800 border border-stone-300 font-bold text-xs shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-stone-400"></span>
                <span>Menunggu Peninjauan RT</span>
            </div>
            @endif
        </div>
    </div>

    <!-- 2. WORKFLOW STEPPER REALISTIS -->
    <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs">
        <div class="flex items-center justify-between text-xs font-bold text-stone-500 mb-5 uppercase tracking-wider">
            <span>Alur Perkembangan Permohonan</span>
            <span class="font-mono text-stone-400">
                @if($surat->status === 'DISETUJUI' || $surat->status === 'DITOLAK')
                    Tahap 3/3
                @elseif($surat->status === 'DIREVIEW')
                    Tahap 2/3
                @else
                    Tahap 1/3
                @endif
            </span>
        </div>

        <div class="relative flex items-center justify-between">
            <!-- Line connector -->
            <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-1 bg-stone-200 z-0"></div>
            <div 
                class="absolute left-0 top-1/2 -translate-y-1/2 h-1 {{ $surat->status === 'DITOLAK' ? 'bg-rose-500' : 'bg-emerald-600' }} z-0 transition-all duration-500" 
                style="width: {{ $surat->status === 'DISETUJUI' || $surat->status === 'DITOLAK' ? '100%' : ($surat->status === 'DIREVIEW' ? '50%' : '10%') }};"
            ></div>

            <!-- Step 1: Diajukan -->
            <div class="relative z-10 flex flex-col items-center">
                <div class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-md">
                    ✓
                </div>
                <span class="text-xs font-bold text-stone-900 mt-2">1. Diajukan</span>
                <span class="text-[10px] text-stone-400">{{ $surat->created_at->format('d/m H:i') }}</span>
            </div>

            <!-- Step 2: Verifikasi RT -->
            <div class="relative z-10 flex flex-col items-center">
                @php
                    $isStep2Done = in_array($surat->status, ['DIREVIEW', 'DISETUJUI', 'DITOLAK', 'PERLU_KELENGKAPAN']);
                @endphp
                <div class="w-9 h-9 rounded-full {{ $isStep2Done ? 'bg-emerald-600 text-white' : 'bg-stone-200 text-stone-500' }} flex items-center justify-center font-bold text-xs shadow-md">
                    {{ $isStep2Done ? '✓' : '2' }}
                </div>
                <span class="text-xs font-bold {{ $isStep2Done ? 'text-stone-900' : 'text-stone-400' }} mt-2">2. Verifikasi RT</span>
                <span class="text-[10px] text-stone-400">
                    {{ $surat->reviewer ? $surat->reviewer->nama : 'Pengurus RT' }}
                </span>
            </div>

            <!-- Step 3: Keputusan Akhir -->
            <div class="relative z-10 flex flex-col items-center">
                @if($surat->status === 'DISETUJUI')
                <div class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-md">
                    ✓
                </div>
                <span class="text-xs font-bold text-emerald-800 mt-2">3. Disetujui</span>
                <span class="text-[10px] text-emerald-600">Surat Terbit</span>
                @elseif($surat->status === 'DITOLAK')
                <div class="w-9 h-9 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-xs shadow-md">
                    ✕
                </div>
                <span class="text-xs font-bold text-rose-800 mt-2">3. Ditolak</span>
                <span class="text-[10px] text-rose-600">Periksa Alasan</span>
                @elseif($surat->status === 'PERLU_KELENGKAPAN')
                <div class="w-9 h-9 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-xs shadow-md">
                    !
                </div>
                <span class="text-xs font-bold text-amber-800 mt-2">Perlu Revisi</span>
                <span class="text-[10px] text-amber-600">Upload Ulang</span>
                @else
                <div class="w-9 h-9 rounded-full bg-stone-200 text-stone-500 flex items-center justify-center font-bold text-xs shadow-md">
                    3
                </div>
                <span class="text-xs font-bold text-stone-400 mt-2">3. Penerbitan</span>
                <span class="text-[10px] text-stone-400">Menunggu</span>
                @endif
            </div>
        </div>
    </div>

    <!-- 3. DECISION CALLOUT CARDS -->

    <!-- JIKA SUDAH DISETUJUI: BANNER UNDUH PDF RESMI -->
    @if($surat->status === 'DISETUJUI')
    <div class="bg-[#10231e] text-white rounded-3xl p-6 sm:p-7 shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-6 border border-emerald-500/30 relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="space-y-2 relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-semibold">
                <span>✓ DOKUMEN RESMI DISAHKAN</span>
            </div>
            <h2 class="text-lg font-bold tracking-tight text-white">Nomor Surat: <span class="font-mono text-emerald-300">{{ $surat->nomor_surat }}</span></h2>
            <p class="text-xs text-stone-300 max-w-xl">
                Surat telah ditandatangani secara elektronik oleh Pengurus RT dan dilengkapi kode verifikasi keabsahan. Siap diunduh dan dicetak untuk keperluan dinas.
            </p>
        </div>

        <a 
            href="{{ route('surat.download-pdf', $surat->id) }}" 
            class="inline-flex items-center justify-center gap-2.5 px-6 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-full shadow-md hover:shadow-lg transition-all shrink-0 relative z-10"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>Unduh Berkas PDF Resmi (A4)</span>
        </a>
    </div>
    @endif

    <!-- JIKA DITOLAK: TAMPILKAN ALASAN PENOLAKAN -->
    @if($surat->status === 'DITOLAK')
    <div class="bg-rose-50 border border-rose-300 rounded-3xl p-6 shadow-2xs space-y-3 text-xs">
        <div class="flex items-center gap-2 text-rose-900 font-bold text-sm">
            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>Alasan Penolakan dari Pengurus RT:</span>
        </div>
        <p class="p-4 bg-white rounded-2xl border border-rose-200 text-stone-800 text-xs font-medium leading-relaxed">
            "{{ $surat->alasan_tolak ?: 'Persyaratan berkas atau data tidak memenuhi ketentuan RT setempat.' }}"
        </p>
        <p class="text-[11px] text-stone-500">Anda dapat mengajukan permohonan surat baru setelah memperbaiki persyaratan atau data yang diperlukan.</p>
    </div>
    @endif

    <!-- JIKA PERLU KELENGKAPAN: INSTRUKSI RT & FORM BALASAN WARGA -->
    @if($surat->status === 'PERLU_KELENGKAPAN')
    <div class="bg-amber-50 border border-amber-300 rounded-3xl p-6 shadow-2xs space-y-4">
        <div class="flex items-center gap-2 text-amber-900 font-bold text-sm">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>Instruksi Kelengkapan dari Ketua RT:</span>
        </div>

        @php
            $lastRtMsg = $surat->kelengkapan->where('dari_role', 'rt')->last();
        @endphp
        <div class="p-4 bg-white rounded-2xl border border-amber-200 text-stone-800 text-xs font-medium leading-relaxed">
            {{ $lastRtMsg ? $lastRtMsg->pesan : 'Harap melengkapi dokumen persyaratan yang diminta.' }}
        </div>

        <!-- Formulir Tanggapan & Upload Perbaikan untuk Warga -->
        @if($isOwner)
        <form action="{{ route('surat.kelengkapan', $surat->id) }}" method="POST" enctype="multipart/form-data" class="pt-3 border-t border-amber-200 space-y-3">
            @csrf
            <label class="font-bold text-stone-900 text-xs block">Unggah Berkas Perbaikan / Kirim Pesan:</label>
            <textarea 
                name="pesan" 
                rows="2" 
                required 
                placeholder="Tuliskan catatan perbaikan (contoh: 'Berikut terlampir foto KTP asli yang lebih jelas.')"
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-xs text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700"
            ></textarea>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <input 
                    type="file" 
                    name="file" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full sm:w-auto text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-amber-100 file:text-amber-900 hover:file:bg-amber-200 cursor-pointer"
                >

                <button 
                    type="submit" 
                    class="px-5 py-2.5 bg-[#10231e] hover:bg-[#18362e] text-white font-bold text-xs rounded-full shadow-xs transition-all flex items-center gap-2 self-end sm:self-auto"
                >
                    <span>Kirim Berkas Perbaikan</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </button>
            </div>
            <p class="text-[11px] text-stone-500">* Setelah berkas dikirimkan, status permohonan akan otomatis kembali ke antrean <strong>Menunggu</strong> untuk diverifikasi ulang oleh Ketua RT.</p>
        </form>
        @endif
    </div>
    @endif

    <!-- 4. PANEL AKSI VERIFIKASI PENGURUS RT (Ketua RT, Wakil RT, Sekretaris) -->
    @if($isPengurusRt && in_array($surat->status, ['MENUNGGU', 'DIREVIEW', 'PERLU_KELENGKAPAN']))
    <div class="bg-white rounded-3xl p-6 sm:p-7 border-2 border-emerald-700/40 shadow-md space-y-5" x-data="{ actionModal: null }">
        <div class="flex items-center justify-between border-b border-stone-100 pb-3">
            <div>
                <h3 class="text-sm font-bold text-stone-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                    <span>Meja Verifikasi Pengurus RT 0{{ $surat->rt->nomor_rt ?? 5 }}</span>
                </h3>
                <p class="text-xs text-stone-500 mt-0.5">Tentukan keputusan resmi permohonan surat ini. Seluruh aksi akan tercatat otomatis di Audit Trail.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold">Akses Pengurus</span>
        </div>

        <!-- 3 Tombol Utama Pengurus: [SETUJUI] [MINTA KELENGKAPAN] [TOLAK] -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Tombol 1: Setujui -->
            <form action="{{ route('admin.surat.approve', $surat->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui permohonan surat ini? Nomor surat resmi akan diterbitkan otomatis.');">
                @csrf
                <button type="submit" class="w-full py-3 px-4 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Setujui Permohonan</span>
                </button>
            </form>

            <!-- Tombol 2: Minta Kelengkapan -->
            <button 
                type="button" 
                @click="actionModal = 'minta'" 
                class="w-full py-3 px-4 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Minta Kelengkapan</span>
            </button>

            <!-- Tombol 3: Tolak -->
            <button 
                type="button" 
                @click="actionModal = 'tolak'" 
                class="w-full py-3 px-4 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>Tolak Permohonan</span>
            </button>
        </div>

        <!-- Form Modal: Minta Kelengkapan -->
        <div x-show="actionModal === 'minta'" class="p-4 bg-amber-50 border border-amber-300 rounded-2xl space-y-3" style="display: none;">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-amber-900">Tuliskan instruksi dokumen yang harus dilengkapi warga:</span>
                <button type="button" @click="actionModal = null" class="text-xs font-semibold text-stone-500 hover:text-stone-800">Tutup</button>
            </div>
            <form action="{{ route('admin.surat.request-completion', $surat->id) }}" method="POST" class="space-y-3">
                @csrf
                <textarea name="pesan" rows="2" required placeholder="Contoh: Mohon unggah ulang foto KTP asli karena foto sebelumnya buram dan nomor tidak terbaca." class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-xs text-stone-900 focus:ring-2 focus:ring-amber-500/30"></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="actionModal = null" class="px-4 py-1.5 rounded-full border border-stone-300 bg-white text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-1.5 rounded-full bg-amber-600 text-white font-bold text-xs">Kirim ke Warga</button>
                </div>
            </form>
        </div>

        <!-- Form Modal: Tolak Permohonan -->
        <div x-show="actionModal === 'tolak'" class="p-4 bg-rose-50 border border-rose-300 rounded-2xl space-y-3" style="display: none;">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-900">Alasan Penolakan Permohonan (Wajib Diisi):</span>
                <button type="button" @click="actionModal = null" class="text-xs font-semibold text-stone-500 hover:text-stone-800">Tutup</button>
            </div>
            <form action="{{ route('admin.surat.reject', $surat->id) }}" method="POST" class="space-y-3">
                @csrf
                <textarea name="alasan_tolak" rows="2" required placeholder="Contoh: Pemohon belum tercatat aktif sebagai warga domisili RT 05 lebih dari 6 bulan berturut-turut." class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-xs text-stone-900 focus:ring-2 focus:ring-rose-500/30"></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="actionModal = null" class="px-4 py-1.5 rounded-full border border-stone-300 bg-white text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-1.5 rounded-full bg-rose-700 text-white font-bold text-xs">Tolak Permohonan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- 5. RINCIAN DATA PERMOHONAN -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-stone-200/90 shadow-2xs space-y-4">
        <h3 class="text-sm font-bold text-stone-900 border-b border-stone-100 pb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Rincian Isian Formulir</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 bg-stone-50 rounded-2xl border border-stone-200/60 sm:col-span-2">
                <span class="text-stone-400 block text-[11px] font-semibold uppercase">Keperluan Surat:</span>
                <span class="font-bold text-stone-900 text-sm mt-0.5 block">{{ $surat->form_data['keperluan'] ?? '—' }}</span>
            </div>

            @foreach($surat->form_data as $key => $val)
                @if(!in_array($key, ['keperluan', 'dokumen_url', 'dokumen_nama', 'lampiran', 'catatan_pemohon']) && !empty($val))
                <div class="p-3.5 bg-stone-50 rounded-2xl border border-stone-200/60">
                    <span class="text-stone-400 block text-[10px] font-semibold uppercase tracking-wider">{{ str_replace('_', ' ', $key) }}:</span>
                    <span class="font-bold text-stone-800 mt-0.5 block">{{ is_array($val) ? json_encode($val) : $val }}</span>
                </div>
                @endif
            @endforeach
        </div>

        <!-- Catatan Tambahan Pemohon jika ada -->
        @if(!empty($surat->form_data['catatan_pemohon']))
        <div class="mt-4 p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-xs space-y-1">
            <span class="font-bold text-amber-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
                Catatan Tambahan dari Pemohon:
            </span>
            <p class="text-stone-700 italic pl-5.5">"{{ $surat->form_data['catatan_pemohon'] }}"</p>
        </div>
        @endif

        <!-- Berkas Lampiran Pendukung Terstruktur -->
        @if(!empty($surat->form_data['lampiran']))
        <div class="mt-4 pt-4 border-t border-stone-100 space-y-3">
            <span class="text-xs font-bold text-stone-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                Dokumen Lampiran Persyaratan:
            </span>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                @foreach($surat->form_data['lampiran'] as $slotKey => $files)
                    @foreach($files as $idx => $file)
                    <div class="p-3 bg-stone-50 border border-stone-200/80 rounded-2xl flex items-center justify-between gap-3">
                        <div class="space-y-0.5 truncate">
                            <span class="inline-block px-2 py-0.5 rounded-md bg-stone-200 text-stone-700 font-bold text-[10px] uppercase">
                                {{ $file['label'] ?? 'Dokumen' }}
                            </span>
                            <p class="font-bold text-stone-800 text-xs truncate mt-1" title="{{ $file['nama'] }}">
                                {{ $file['nama'] }}
                            </p>
                            @if(!empty($file['size']))
                            <span class="text-[10px] text-stone-400 font-mono">{{ round($file['size'] / 1024) }} KB</span>
                            @endif
                        </div>
                        <a 
                            href="{{ asset('storage/' . $file['path']) }}" 
                            target="_blank" 
                            class="px-3.5 py-1.5 bg-white border border-stone-200 hover:bg-emerald-50 hover:border-emerald-300 text-emerald-800 font-bold rounded-xl text-xs transition-all shrink-0 flex items-center gap-1 shadow-2xs"
                        >
                            <span>Buka</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                    @endforeach
                @endforeach
            </div>
        </div>
        @elseif(!empty($surat->form_data['dokumen_url']))
        <!-- Fallback Legacy Dokumen Tunggal (Harus cocok dengan assertSee test suite) -->
        <div class="mt-4 pt-4 border-t border-stone-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                <span class="font-medium text-stone-700">Berkas Lampiran: {{ $surat->form_data['dokumen_nama'] ?? 'Dokumen Pendukung' }}</span>
            </div>
            <a 
                href="{{ asset('storage/' . $surat->form_data['dokumen_url']) }}" 
                target="_blank" 
                class="px-3 py-1.5 bg-stone-100 hover:bg-stone-200 text-stone-800 font-semibold rounded-lg text-xs transition-colors"
            >
                Buka Berkas &rarr;
            </a>
        </div>
        @endif
    </div>

    <!-- 6. RIWAYAT KELENGKAPAN / REVISI DOKUMEN -->
    @if($surat->kelengkapan->count() > 0)
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-stone-200/90 shadow-2xs space-y-4">
        <h3 class="text-sm font-bold text-stone-900 border-b border-stone-100 pb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <span>Histori Komunikasi Kelengkapan Berkas</span>
        </h3>

        <div class="space-y-3 text-xs">
            @foreach($surat->kelengkapan as $item)
            <div class="p-4 rounded-2xl {{ $item->dari_role === 'rt' ? 'bg-amber-50/80 border border-amber-200' : 'bg-stone-50 border border-stone-200' }} space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="font-bold {{ $item->dari_role === 'rt' ? 'text-amber-900' : 'text-stone-800' }}">
                        {{ $item->dari_role === 'rt' ? 'Pengurus RT 05' : 'Pemohon (Warga)' }}
                    </span>
                    <span class="text-[10px] text-stone-400">{{ $item->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>
                <p class="text-stone-700 leading-relaxed">{{ $item->pesan }}</p>

                @if($item->file_url)
                <div class="pt-1.5">
                    <a href="{{ asset('storage/' . $item->file_url) }}" target="_blank" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 hover:text-emerald-900 underline">
                        <span>Lihat Berkas Perbaikan Terlampir</span>
                        <span>&rarr;</span>
                    </a>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
