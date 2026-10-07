@extends('layouts.app')

@section('title', 'Ajukan ' . $namaJenis . ' — Warga Digital')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Breadcrumb & Back Link -->
    <div class="flex items-center justify-between text-xs text-stone-500">
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="hover:text-stone-800 transition-colors">Beranda</a>
            <span>/</span>
            <a href="{{ route('surat.index') }}" class="hover:text-stone-800 transition-colors">Pengajuan Surat</a>
            <span>/</span>
            <span class="text-stone-900 font-bold font-mono">{{ $jenis }}</span>
        </div>

        <a href="{{ route('surat.index') }}" class="inline-flex items-center gap-1 font-semibold text-emerald-800 hover:text-emerald-950 transition-colors">
            <span>&larr;</span>
            <span>Kembali ke Katalog</span>
        </a>
    </div>

    <!-- Header Formulir: Deep Evergreen -->
    <div class="bg-[#10231e] text-white rounded-3xl p-6 sm:p-7 shadow-xs border border-white/10 relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] font-semibold border border-emerald-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Kode: {{ $jenis }}</span>
                    <span>•</span>
                    <span>RT 0{{ $user->rt->nomor_rt ?? 5 }} / RW 0{{ $user->rw->nomor_rw ?? 3 }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white">{{ $namaJenis }}</h1>
                <p class="text-xs text-stone-300">Lengkapi data isian formulir di bawah ini dengan benar agar dapat segera diverifikasi oleh Ketua RT.</p>
            </div>

            <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto">
                <span class="px-3 py-1.5 rounded-full bg-white/10 text-stone-200 text-xs font-semibold border border-white/15">
                    Estimasi 1x24 Jam
                </span>
            </div>
        </div>
    </div>

    <!-- Alert Validasi Error jika ada -->
    @if ($errors->any())
    <div class="p-4 rounded-3xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1.5 shadow-2xs">
        <div class="font-bold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Mohon periksa kembali isian formulir berikut:</span>
        </div>
        <ul class="list-disc list-inside pl-1 space-y-0.5 text-rose-800">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('surat.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <input type="hidden" name="jenis_surat" value="{{ $jenis }}">

        <!-- 1. DATA IDENTITAS PEMOHON (Otomatis dari Akun Warga — ZERO NIK) -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-stone-200/90 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <h2 class="text-xs sm:text-sm font-bold text-stone-900 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>1. Data Kependudukan Pemohon (Terverifikasi Otomatis)</span>
                </h2>
                <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                    Akun Sah RT 0{{ $user->rt->nomor_rt ?? 5 }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="space-y-1">
                    <label class="font-medium text-stone-500">Nama Lengkap Pemohon</label>
                    <input 
                        type="text" 
                        value="{{ $user->nama }}" 
                        disabled 
                        class="w-full px-3.5 py-2.5 bg-stone-100/80 border border-stone-200 rounded-xl text-stone-900 font-bold cursor-not-allowed text-xs"
                    >
                </div>

                <div class="space-y-1">
                    <label class="font-medium text-stone-500">Kode Registrasi Warga</label>
                    <input 
                        type="text" 
                        value="{{ $user->kode_warga ?? ('WRG-RT0' . ($user->rt->nomor_rt ?? 5) . '-' . str_pad($user->id, 3, '0', STR_PAD_LEFT)) }}" 
                        disabled 
                        class="w-full px-3.5 py-2.5 bg-stone-100/80 border border-stone-200 rounded-xl text-stone-700 font-mono font-semibold cursor-not-allowed text-xs"
                    >
                </div>

                <div class="space-y-1">
                    <label class="font-medium text-stone-500">Jenis Kelamin</label>
                    <input 
                        type="text" 
                        value="{{ $user->jenis_kelamin === 'L' ? 'Laki-Laki' : 'Perempuan' }}" 
                        disabled 
                        class="w-full px-3.5 py-2.5 bg-stone-100/80 border border-stone-200 rounded-xl text-stone-700 cursor-not-allowed text-xs"
                    >
                </div>

                <div class="space-y-1">
                    <label class="font-medium text-stone-500">Alamat Terdaftar di Wilayah</label>
                    <input 
                        type="text" 
                        value="{{ $user->alamat ?? ('RT 0' . ($user->rt->nomor_rt ?? 5) . ' / RW 0' . ($user->rw->nomor_rw ?? 3) . ' Kel. Sekeloa') }}" 
                        disabled 
                        class="w-full px-3.5 py-2.5 bg-stone-100/80 border border-stone-200 rounded-xl text-stone-700 cursor-not-allowed truncate text-xs"
                    >
                </div>
            </div>
            <p class="text-[11px] text-stone-400 italic">
                * Data identitas di atas diambil langsung dari basis data kependudukan RT untuk menjamin keaslian permohonan surat.
            </p>
        </div>

        <!-- 2. ISIAN KHUSUS JENIS SURAT (Modular Partial per Jenis) -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-stone-200/90 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <h2 class="text-xs sm:text-sm font-bold text-stone-900 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#e5a53f]"></span>
                    <span>2. Informasi Khusus {{ $namaJenis }}</span>
                </h2>
                <span class="text-[11px] text-rose-600 font-semibold">* Wajib Diisi</span>
            </div>

            @include('surat.partials.forms.' . strtolower($jenis), ['user' => $user])
        </div>

        <!-- 3. KEPERLUAN & CATATAN TAMBAHAN PEMOHON -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-stone-200/90 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <h2 class="text-xs sm:text-sm font-bold text-stone-900 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>3. Tujuan Pengajuan & Catatan Pemohon</span>
                </h2>
            </div>

            <div class="space-y-4 text-xs">
                <div class="space-y-1">
                    <label class="font-bold text-stone-800 block">
                        Keperluan Surat / Peruntukan Dokumen <span class="text-rose-600">*</span>
                    </label>
                    <textarea 
                        name="keperluan" 
                        rows="2" 
                        required 
                        maxlength="500"
                        placeholder="Contoh: Persyaratan Pembukaan Rekening Bank BCA / Pendaftaran BPJS Kesehatan / Keringanan Biaya Kuliah"
                        class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
                    >{{ old('keperluan') }}</textarea>
                    <p class="text-[11px] text-stone-500">Teks keperluan ini akan tercetak langsung pada lembar surat resmi yang diterbitkan.</p>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-stone-800 block">
                        Catatan Tambahan untuk Pengurus RT <span class="text-stone-400 font-normal">(Opsional)</span>
                    </label>
                    <textarea 
                        name="catatan_pemohon" 
                        rows="2" 
                        maxlength="1000"
                        placeholder="Tuliskan catatan khusus atau pesan yang perlu diketahui oleh Ketua RT (misal: 'Mohon dibantu segera Pak RT, berkas dibutuhkan besok siang')..."
                        class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
                    >{{ old('catatan_pemohon') }}</textarea>
                </div>

                <!-- Dokumen Pendukung Lain (Opsional) -->
                <div class="pt-3 border-t border-stone-100 space-y-2">
                    <label class="font-bold text-stone-800 block">
                        Berkas Pendukung Tambahan Lainnya <span class="text-stone-400 font-normal">(Opsional)</span>
                    </label>
                    <p class="text-[11px] text-stone-500">
                        Unggah berkas ekstra jika diminta oleh RT (contoh: surat perjanjian sewa, surat keterangan kerja, dll). Format PDF/JPG/PNG maks 5MB.
                    </p>
                    <input 
                        type="file" 
                        name="dokumen_pendukung_lain[]" 
                        multiple
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full text-xs text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 file:text-stone-700 hover:file:bg-stone-200 cursor-pointer"
                    >
                </div>
            </div>
        </div>

        <!-- 4. ACTION BAR SUBMIT & CANCEL -->
        <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-xs text-stone-500">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Permohonan akan diverifikasi resmi dan dicatat di Meja Audit RT.</span>
            </div>

            <div class="flex items-center gap-3 self-end sm:self-auto">
                <a 
                    href="{{ route('surat.index') }}" 
                    class="px-5 py-2.5 rounded-full border border-stone-300 text-stone-700 hover:bg-stone-100 font-semibold text-xs transition-all"
                >
                    Batal
                </a>

                <button 
                    type="submit" 
                    class="px-6 py-2.5 bg-[#10231e] hover:bg-[#18362e] text-white rounded-full font-bold text-xs shadow-xs hover:shadow transition-all flex items-center gap-2"
                >
                    <span>Kirim Permohonan Surat</span>
                    <span>&rarr;</span>
                </button>
            </div>
        </div>

    </form>

</div>
@endsection
