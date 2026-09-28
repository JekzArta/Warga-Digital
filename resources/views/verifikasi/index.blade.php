@extends('layouts.app')

@php
    $pageTitle = 'Verifikasi Dokumen Resmi';
@endphp

@section('content')
<div class="max-w-3xl mx-auto my-4 sm:my-8 space-y-6">

    <!-- Header Section -->
    <div class="text-center space-y-2">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-950/20 mb-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight">Pusat Verifikasi Dokumen</h1>
        <p class="text-sm text-stone-600 max-w-lg mx-auto">
            Layanan pemeriksaan validitas pencatatan surat keterangan dan pengantar resmi yang diterbitkan oleh Pengurus RT/RW melalui sistem <strong>Warga Digital</strong>.
        </p>
    </div>

    <!-- Search / Verification Form Card -->
    <div class="bg-white rounded-3xl border border-stone-200/90 shadow-sm p-6 sm:p-8">
        <form method="GET" action="{{ route('verifikasi.index') }}" class="space-y-4">
            <div>
                <label for="kode" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-2">
                    Masukkan 16 Karakter Kode Validasi Dokumen
                </label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            id="kode" 
                            name="kode" 
                            value="{{ old('kode', $kodeInput) }}" 
                            placeholder="Contoh: 8F2A1C0E4B7D9E3F" 
                            maxlength="32"
                            required 
                            autofocus
                            class="w-full pl-11 pr-4 py-3 rounded-2xl border border-stone-300 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 text-base font-mono uppercase tracking-wider text-stone-900 placeholder:normal-case placeholder:tracking-normal placeholder:font-sans placeholder:text-stone-400 transition-all bg-stone-50/50 focus:bg-white"
                        >
                    </div>
                    <button 
                        type="submit" 
                        class="px-6 py-3 rounded-2xl bg-emerald-700 hover:bg-emerald-800 active:scale-[0.99] text-white font-bold text-sm shadow-sm transition-all flex items-center justify-center gap-2 shrink-0 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Periksa Dokumen</span>
                    </button>
                </div>
                <p class="text-[11px] text-stone-500 mt-2 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Kode validasi 16 karakter tercetak di bagian footer kotak verifikasi pada lembar dokumen fisik atau PDF.</span>
                </p>
            </div>
        </form>
    </div>

    <!-- Hasil Verifikasi: Gagal / Tidak Ditemukan (Generic Response) -->
    @if(!empty($error))
    <div class="bg-rose-50/80 border border-rose-200/90 rounded-3xl p-6 sm:p-7 text-stone-800 shadow-sm animate-fade-in">
        <div class="flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-rose-900">{{ $error }}</h3>
                <p class="text-xs text-rose-700 leading-relaxed">
                    Sistem tidak menemukan catatan penerbitan aktif yang cocok dengan kode yang Anda masukkan. Pastikan kombinasi karakter tidak tertukar, atau hubungi pemilik dokumen/pengurus lingkungan terkait untuk konfirmasi lebih lanjut.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Hasil Verifikasi: Sukses / Valid -->
    @if(!empty($hasil))
    <div class="bg-white rounded-3xl border border-emerald-200 shadow-sm overflow-hidden animate-fade-in">
        <!-- Banner Status -->
        <div class="bg-[#EAF5EC] border-b border-[#BFDFCA] p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 mb-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                        DATA TERCATAT RESMI
                    </div>
                    <p class="text-sm font-semibold text-emerald-950">
                        {{ $hasil['status_konfirmasi'] }}
                    </p>
                </div>
            </div>
            <div class="text-left sm:text-right shrink-0">
                <span class="text-[11px] text-emerald-800/80 block uppercase font-medium">Tanggal Pengesahan</span>
                <span class="text-xs font-bold text-emerald-950 font-mono">{{ $hasil['tanggal_terbit'] }}</span>
            </div>
        </div>

        <!-- Tabel Perbandingan Metadata Dokumen -->
        <div class="p-6 sm:p-8 space-y-6">
            <div>
                <h3 class="text-xs font-bold text-stone-500 uppercase tracking-wider mb-3">
                    Rincian Metadata Dokumen untuk Pencocokan Manual:
                </h3>
                <div class="border border-stone-200/80 rounded-2xl divide-y divide-stone-100 overflow-hidden bg-stone-50/30">
                    
                    <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-sm">
                        <span class="text-xs font-semibold text-stone-500 w-44">Jenis Surat</span>
                        <span class="font-bold text-stone-900 sm:text-right">{{ $hasil['jenis_surat'] }}</span>
                    </div>

                    <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-sm">
                        <span class="text-xs font-semibold text-stone-500 w-44">Nomor Surat</span>
                        <span class="font-mono font-bold text-emerald-800 sm:text-right">{{ $hasil['nomor_surat'] }}</span>
                    </div>

                    @if(!in_array($hasil['jenis_surat_kode'], ['SKL', 'SKKm']))
                    <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-sm">
                        <span class="text-xs font-semibold text-stone-500 w-44">Nama Lengkap Pemohon</span>
                        <span class="font-bold text-stone-900 sm:text-right">{{ $hasil['nama_pemohon'] }}</span>
                    </div>
                    @endif

                    <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-sm">
                        <span class="text-xs font-semibold text-stone-500 w-44">Wilayah Penerbit</span>
                        <span class="font-semibold text-stone-800 sm:text-right">
                            {{ $hasil['rt_rw'] }}, Kelurahan {{ $hasil['kelurahan'] }}, Kecamatan {{ $hasil['kecamatan'] }}, Kota {{ $hasil['kota'] }}
                        </span>
                    </div>

                    <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-sm">
                        <span class="text-xs font-semibold text-stone-500 w-44">Nama Penandatangan</span>
                        <span class="font-bold text-stone-900 sm:text-right">{{ $hasil['penandatangan_nama'] }}</span>
                    </div>

                    <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-sm">
                        <span class="text-xs font-semibold text-stone-500 w-44">Jabatan Penandatangan</span>
                        <span class="font-semibold text-stone-800 sm:text-right">{{ $hasil['penandatangan_jabatan'] }}</span>
                    </div>

                    <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-sm">
                        <span class="text-xs font-semibold text-stone-500 w-44">Kode Validasi Dokumen</span>
                        <span class="font-mono font-bold text-stone-700 tracking-wider sm:text-right">{{ $hasil['kode_verifikasi'] }}</span>
                    </div>

                </div>
            </div>

            <!-- Blok Rincian Objek Keterangan (Kondisional per Jenis Surat) -->
            @if(!empty($hasil['rincian_objek']))
            <div>
                <h3 class="text-xs font-bold text-stone-500 uppercase tracking-wider mb-3">
                    Rincian Objek Keterangan:
                </h3>
                <div class="border border-stone-200/80 rounded-2xl divide-y divide-stone-100 overflow-hidden bg-stone-50/30">
                    @foreach($hasil['rincian_objek'] as $objek)
                    <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-sm">
                        <span class="text-xs font-semibold text-stone-500 w-44">{{ $objek['label'] }}</span>
                        <span class="font-bold text-stone-900 sm:text-right">{{ $objek['value'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Petunjuk Pemeriksa Pihak Ketiga & UU PDP Notice -->
            <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 text-xs text-amber-900 space-y-1.5">
                <div class="font-bold flex items-center gap-1.5 text-amber-950">
                    <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Panduan Pemeriksa (Pihak Ketiga / Instansi / Lembaga):</span>
                </div>
                <p class="leading-relaxed text-amber-800">
                    Layanan ini menyajikan verifikasi pencatatan penerbitan surat pada sistem Warga Digital. Pihak pemeriksa dapat mencocokkan identitas pihak yang berkepentingan, rincian objek keterangan, nomor surat, penandatangan, serta metadata penerbitan terhadap dokumen fisik atau salinan resmi yang dipegang. Demi mematuhi prinsip pelindungan data pribadi (UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi / UU PDP), data privat seperti NIK, alamat lengkap pemohon, data ekonomi/penghasilan, tanggal lahir bayi, penyebab/tempat/tanggal kematian, serta berkas lampiran pendukung sengaja tidak diekspos ke kanal publik.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Back to Login / Home Link -->
    <div class="text-center pt-2">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-xs font-bold text-stone-600 hover:text-emerald-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Portal Masuk Warga Digital</span>
        </a>
    </div>

</div>
@endsection
