@extends('layouts.app')

@section('title', 'Ajukan ' . $namaJenis . ' — Warga Digital')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Breadcrumb & Back Link -->
    <div class="flex items-center gap-2 text-xs text-stone-500">
        <a href="{{ route('dashboard') }}" class="hover:text-stone-800 transition-colors">Beranda</a>
        <span>/</span>
        <a href="{{ route('surat.index') }}" class="hover:text-stone-800 transition-colors">Pengajuan Surat</a>
        <span>/</span>
        <span class="text-stone-900 font-semibold">{{ $jenis }}</span>
    </div>

    <!-- Header Formulir -->
    <div class="bg-[#182222] text-white rounded-3xl p-6 sm:p-7 shadow-md relative overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-semibold mb-2">
                    <span>Kode: {{ $jenis }}</span>
                    <span>•</span>
                    <span>RT 0{{ $user->rt->nomor_rt ?? 5 }} / RW 0{{ $user->rw->nomor_rw ?? 3 }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">{{ $namaJenis }}</h1>
                <p class="text-xs text-stone-400 mt-1">Lengkapi data formulir di bawah ini dengan benar agar dapat diproses oleh Ketua RT.</p>
            </div>

            <a href="{{ route('surat.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-medium transition-all self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Katalog</span>
            </a>
        </div>
    </div>

    <!-- Alert Validasi Error jika ada -->
    @if ($errors->any())
    <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs space-y-1">
        <div class="font-bold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Mohon periksa kembali isian formulir:</span>
        </div>
        <ul class="list-disc list-inside pl-1 space-y-0.5 text-stone-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('surat.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <input type="hidden" name="jenis_surat" value="{{ $jenis }}">

        <!-- 1. DATA IDENTITAS PEMOHON (Otomatis dari Akun Warga Terverifikasi) -->
        <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <h2 class="text-sm font-bold text-stone-900 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>1. Data Kependudukan Pemohon (Otomatis)</span>
                </h2>
                <span class="text-[11px] text-stone-400">Terdaftar di RT 0{{ $user->rt->nomor_rt ?? 5 }}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="space-y-1">
                    <label class="font-medium text-stone-500">Nama Lengkap</label>
                    <input type="text" value="{{ $user->nama }}" disabled class="w-full px-3.5 py-2.5 bg-stone-100/80 border border-stone-200 rounded-xl text-stone-800 font-semibold cursor-not-allowed">
                </div>

                <div class="space-y-1">
                    <label class="font-medium text-stone-500">Nomor Induk Kependudukan (NIK)</label>
                    <input type="text" value="3273 02•• •••• {{ substr($user->nik ?? '0001', -4) }} (Tersensor Aman)" disabled class="w-full px-3.5 py-2.5 bg-stone-100/80 border border-stone-200 rounded-xl text-stone-700 font-mono cursor-not-allowed">
                </div>

                <div class="space-y-1">
                    <label class="font-medium text-stone-500">Jenis Kelamin</label>
                    <input type="text" value="{{ $user->jenis_kelamin === 'L' ? 'Laki-Laki' : 'Perempuan' }}" disabled class="w-full px-3.5 py-2.5 bg-stone-100/80 border border-stone-200 rounded-xl text-stone-700 cursor-not-allowed">
                </div>

                <div class="space-y-1">
                    <label class="font-medium text-stone-500">Alamat Terdaftar</label>
                    <input type="text" value="{{ $user->alamat ?? ('RT 0' . ($user->rt->nomor_rt ?? 5) . ' / RW 0' . ($user->rw->nomor_rw ?? 3) . ' Kel. Sekeloa') }}" disabled class="w-full px-3.5 py-2.5 bg-stone-100/80 border border-stone-200 rounded-xl text-stone-700 cursor-not-allowed truncate">
                </div>
            </div>
        </div>

        <!-- 2. ISIAN RINCIAN SPESIFIK SURAT -->
        <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <h2 class="text-sm font-bold text-stone-900 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>2. Informasi Khusus {{ $namaJenis }}</span>
                </h2>
                <span class="text-[11px] text-emerald-700 font-medium">* Wajib Diisi</span>
            </div>

            <!-- Field Khusus Berdasarkan Jenis Surat -->
            @if($jenis === 'SKU')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="space-y-1 sm:col-span-2">
                    <label class="font-bold text-stone-800">Nama Usaha / Toko <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_usaha" value="{{ old('nama_usaha') }}" required placeholder="Contoh: Warung Berkah Ibu Siti / Barbershop Ganteng" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Bidang Usaha <span class="text-red-500">*</span></label>
                    <input type="text" name="bidang_usaha" value="{{ old('bidang_usaha') }}" required placeholder="Contoh: Kuliner / Jasa Servis / Kelontong" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Lama Usaha Berjalan</label>
                    <input type="text" name="lama_usaha" value="{{ old('lama_usaha') }}" placeholder="Contoh: 1 Tahun 3 Bulan" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label class="font-bold text-stone-800">Alamat Lokasi Usaha <span class="text-red-500">*</span></label>
                    <input type="text" name="alamat_usaha" value="{{ old('alamat_usaha') }}" required placeholder="Contoh: Jl. Sekeloa No. 14 RT 05 RW 03" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
            </div>

            @elseif($jenis === 'SKTM')
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Pekerjaan Saat Ini <span class="text-red-500">*</span></label>
                    <input type="text" name="pekerjaan" value="{{ old('pekerjaan') }}" required placeholder="Contoh: Buruh Harian / Pedagang Keliling" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Penghasilan Per Bulan <span class="text-red-500">*</span></label>
                    <input type="text" name="penghasilan_per_bulan" value="{{ old('penghasilan_per_bulan') }}" required placeholder="Contoh: Kurang dari Rp 1.500.000" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Jumlah Tanggungan (Jiwa) <span class="text-red-500">*</span></label>
                    <input type="number" name="jumlah_tanggungan" value="{{ old('jumlah_tanggungan', 3) }}" min="0" max="20" required class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
            </div>

            @elseif($jenis === 'SPKK')
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Alasan Permohonan <span class="text-red-500">*</span></label>
                    <select name="alasan_permohonan" required class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                        <option value="Membentuk Keluarga Baru">Membentuk Keluarga Baru (Pernikahan)</option>
                        <option value="Penambahan Anggota Keluarga">Penambahan Anggota (Kelahiran/Adopsi)</option>
                        <option value="Pecah Kartu Keluarga">Pecah Kartu Keluarga</option>
                        <option value="Perubahan Biodata">Perubahan Biodata / Koreksi Data</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Nama Kepala Keluarga <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_kepala_keluarga" value="{{ old('nama_kepala_keluarga', $user->nama) }}" required class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Jumlah Anggota Keluarga <span class="text-red-500">*</span></label>
                    <input type="number" name="jumlah_anggota" value="{{ old('jumlah_anggota', 4) }}" min="1" max="20" required class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
            </div>

            @elseif($jenis === 'SKL')
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div class="space-y-1 sm:col-span-2">
                    <label class="font-bold text-stone-800">Nama Lengkap Bayi / Anak <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_anak" value="{{ old('nama_anak') }}" required placeholder="Nama lengkap sesuai surat lahir RS" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Jenis Kelamin Bayi <span class="text-red-500">*</span></label>
                    <select name="jenis_kelamin_anak" required class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                        <option value="L">Laki-Laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Tanggal Lahir <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_lahir_anak" value="{{ old('tanggal_lahir_anak') }}" required class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Nama Ibu Kandung <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_ibu" value="{{ old('nama_ibu') }}" required class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Nama Ayah Kandung <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_ayah" value="{{ old('nama_ayah') }}" required class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
            </div>

            @elseif($jenis === 'SKKm')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Nama Almarhum / Almarhumah <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_almarhum" value="{{ old('nama_almarhum') }}" required placeholder="Nama lengkap almarhum" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Tanggal Meninggal <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_meninggal" value="{{ old('tanggal_meninggal') }}" required class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Tempat Meninggal <span class="text-red-500">*</span></label>
                    <input type="text" name="tempat_meninggal" value="{{ old('tempat_meninggal') }}" required placeholder="Contoh: Rumah Tinggal / RS Hasan Sadikin" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Penyebab Kematian <span class="text-red-500">*</span></label>
                    <input type="text" name="penyebab" value="{{ old('penyebab') }}" required placeholder="Contoh: Sakit / Usia Lanjut" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
            </div>

            @else <!-- SKD (Surat Keterangan Domisili) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="space-y-1 sm:col-span-2">
                    <label class="font-bold text-stone-800">Alamat Tempat Tinggal Saat Ini <span class="text-red-500">*</span></label>
                    <input type="text" name="alamat_domisili" value="{{ old('alamat_domisili', $user->alamat ?? ('Jl. Sekeloa RT 0' . ($user->rt->nomor_rt ?? 5) . ' / RW 0' . ($user->rw->nomor_rw ?? 3))) }}" required class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-stone-800">Lama Menetap / Tinggal</label>
                    <input type="text" name="lama_tinggal" value="{{ old('lama_tinggal', 'Sejak Lahir / 5 Tahun') }}" placeholder="Contoh: 3 Tahun" class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all">
                </div>
            </div>
            @endif

            <!-- Field Keperluan Wajib untuk Seluruh Jenis Surat -->
            <div class="space-y-1 pt-2">
                <label class="font-bold text-stone-800 text-xs">Keperluan / Tujuan Pembuatan Surat <span class="text-red-500">*</span></label>
                <textarea 
                    name="keperluan" 
                    rows="3" 
                    required 
                    placeholder="Jelaskan secara singkat keperluan surat ini, misalnya: Melamar pekerjaan di PT ABC / Pengajuan beasiswa kuliah / Pembukaan buku tabungan bank / Persyaratan administrasi Disdukcapil."
                    class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-xs text-stone-900 placeholder-stone-400 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all"
                >{{ old('keperluan') }}</textarea>
            </div>

            <!-- Catatan Tambahan Pemohon (Opsional) -->
            <div class="space-y-1 pt-2">
                <label class="font-bold text-stone-800 text-xs flex items-center justify-between">
                    <span>Catatan Tambahan untuk Pengurus RT</span>
                    <span class="text-[11px] text-stone-400 font-normal">Opsional</span>
                </label>
                <textarea 
                    name="catatan_pemohon" 
                    rows="2" 
                    placeholder="Tuliskan catatan atau pesan tambahan jika ada (misal: 'Mohon dibantu segera untuk keperluan interview kerja besok pagi')."
                    class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-xs text-stone-900 placeholder-stone-400 focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all"
                >{{ old('catatan_pemohon') }}</textarea>
            </div>
        </div>

        <!-- 3. DOKUMEN LAMPIRAN -->
        <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs space-y-6">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <h2 class="text-sm font-bold text-stone-900 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>3. Dokumen Lampiran</span>
                </h2>
                <span class="text-[11px] text-stone-400">PDF, JPG, PNG (Maks 5 MB per file)</span>
            </div>

            <!-- Dokumen Wajib Dinamis Sesuai Jenis Surat -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-stone-900 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Dokumen Wajib ({{ $jenis }})
                    </span>
                    <span class="text-[11px] text-red-600 font-semibold">* Wajib Diunggah</span>
                </div>
                <p class="text-[11px] text-stone-500">Anda dapat memilih lebih dari satu file per slot jika diperlukan (misal foto bagian depan & belakang).</p>

                @if($jenis === 'SKU')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5 text-xs p-4 rounded-2xl bg-stone-50 border border-stone-200">
                        <label class="font-bold text-stone-800 block">
                            Foto Tempat Usaha <span class="text-red-500">*</span>
                        </label>
                        <p class="text-[11px] text-stone-500">Foto plang toko, etalase, atau tempat usaha.</p>
                        <input 
                            type="file" 
                            name="dokumen_usaha[]" 
                            multiple 
                            required 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-stone-300 rounded-xl p-1 bg-white"
                        >
                    </div>

                    <div class="space-y-1.5 text-xs p-4 rounded-2xl bg-stone-50 border border-stone-200">
                        <label class="font-bold text-stone-800 block">
                            KTP Pemilik Usaha <span class="text-red-500">*</span>
                        </label>
                        <p class="text-[11px] text-stone-500">Foto / scan KTP asli pemohon.</p>
                        <input 
                            type="file" 
                            name="dokumen_ktp[]" 
                            multiple 
                            required 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-stone-300 rounded-xl p-1 bg-white"
                        >
                    </div>
                </div>

                @elseif($jenis === 'SKTM')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5 text-xs p-4 rounded-2xl bg-stone-50 border border-stone-200">
                        <label class="font-bold text-stone-800 block">
                            Kartu Keluarga (KK) <span class="text-red-500">*</span>
                        </label>
                        <p class="text-[11px] text-stone-500">Foto / scan Kartu Keluarga asli.</p>
                        <input 
                            type="file" 
                            name="dokumen_kk[]" 
                            multiple 
                            required 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-stone-300 rounded-xl p-1 bg-white"
                        >
                    </div>

                    <div class="space-y-1.5 text-xs p-4 rounded-2xl bg-stone-50 border border-stone-200">
                        <label class="font-bold text-stone-800 block">
                            Slip Gaji / Surat Pernyataan Tidak Mampu <span class="text-red-500">*</span>
                        </label>
                        <p class="text-[11px] text-stone-500">Slip penghasilan atau surat pernyataan bermeterai.</p>
                        <input 
                            type="file" 
                            name="dokumen_slip_gaji[]" 
                            multiple 
                            required 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-stone-300 rounded-xl p-1 bg-white"
                        >
                    </div>
                </div>

                @elseif($jenis === 'SPKK')
                <div class="space-y-1.5 text-xs p-4 rounded-2xl bg-stone-50 border border-stone-200">
                    <label class="font-bold text-stone-800 block">
                        KK Lama / Buku Nikah <span class="text-red-500">*</span>
                    </label>
                    <p class="text-[11px] text-stone-500">Lampirkan foto Kartu Keluarga lama atau Buku Nikah resmi.</p>
                    <input 
                        type="file" 
                        name="dokumen_kk_nikah[]" 
                        multiple 
                        required 
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-stone-300 rounded-xl p-1 bg-white"
                    >
                </div>

                @elseif($jenis === 'SKL')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5 text-xs p-4 rounded-2xl bg-stone-50 border border-stone-200">
                        <label class="font-bold text-stone-800 block">
                            Surat Lahir dari RS/Bidan <span class="text-red-500">*</span>
                        </label>
                        <p class="text-[11px] text-stone-500">Surat keterangan lahir dari rumah sakit atau bidan.</p>
                        <input 
                            type="file" 
                            name="dokumen_surat_lahir[]" 
                            multiple 
                            required 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-stone-300 rounded-xl p-1 bg-white"
                        >
                    </div>

                    <div class="space-y-1.5 text-xs p-4 rounded-2xl bg-stone-50 border border-stone-200">
                        <label class="font-bold text-stone-800 block">
                            KTP Orang Tua <span class="text-red-500">*</span>
                        </label>
                        <p class="text-[11px] text-stone-500">Foto KTP ayah dan/atau ibu kandung bayi.</p>
                        <input 
                            type="file" 
                            name="dokumen_ktp_ortu[]" 
                            multiple 
                            required 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-stone-300 rounded-xl p-1 bg-white"
                        >
                    </div>
                </div>

                @elseif($jenis === 'SKKm')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5 text-xs p-4 rounded-2xl bg-stone-50 border border-stone-200">
                        <label class="font-bold text-stone-800 block">
                            Surat Medis / Keterangan Dokter <span class="text-red-500">*</span>
                        </label>
                        <p class="text-[11px] text-stone-500">Surat keterangan kematian dari dokter atau rumah sakit.</p>
                        <input 
                            type="file" 
                            name="dokumen_surat_medis[]" 
                            multiple 
                            required 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-stone-300 rounded-xl p-1 bg-white"
                        >
                    </div>

                    <div class="space-y-1.5 text-xs p-4 rounded-2xl bg-stone-50 border border-stone-200">
                        <label class="font-bold text-stone-800 block">
                            KTP Almarhum <span class="text-red-500">*</span>
                        </label>
                        <p class="text-[11px] text-stone-500">Foto atau fotokopi KTP almarhum/almarhumah.</p>
                        <input 
                            type="file" 
                            name="dokumen_ktp_almarhum[]" 
                            multiple 
                            required 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-stone-300 rounded-xl p-1 bg-white"
                        >
                    </div>
                </div>

                @else <!-- SKD -->
                <div class="space-y-1.5 text-xs p-4 rounded-2xl bg-stone-50 border border-stone-200">
                    <label class="font-bold text-stone-800 block">
                        KTP Asli / Kartu Keluarga <span class="text-red-500">*</span>
                    </label>
                    <p class="text-[11px] text-stone-500">Lampirkan foto KTP pemohon atau Kartu Keluarga yang berlaku.</p>
                    <input 
                        type="file" 
                        name="dokumen_ktp_kk[]" 
                        multiple 
                        required 
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-stone-300 rounded-xl p-1 bg-white"
                    >
                </div>
                @endif
            </div>

            <!-- Lampiran Pendukung Lain (Opsional) -->
            <div class="border-t border-stone-100 pt-4 space-y-2 text-xs">
                <label class="font-bold text-stone-800 flex items-center justify-between">
                    <span>Lampiran Pendukung Lain</span>
                    <span class="text-[11px] text-stone-400 font-normal">Opsional (Bisa lebih dari 1 file)</span>
                </label>
                <p class="text-[11px] text-stone-500">Dokumen pendukung tambahan di luar dokumen wajib di atas (misal: surat pengantar lama, bukti lunas PBB, dll).</p>
                <input 
                    type="file" 
                    name="dokumen_pendukung_lain[]" 
                    multiple 
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 file:text-stone-800 hover:file:bg-stone-200 cursor-pointer border border-stone-200 rounded-xl p-1 bg-white"
                >
            </div>
        </div>

        <!-- Tombol Aksi Submit -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a 
                href="{{ route('surat.index') }}" 
                class="px-5 py-2.5 rounded-full border border-stone-300 hover:bg-stone-100 text-stone-700 text-xs font-semibold transition-all"
            >
                Batal
            </a>
            <button 
                type="submit" 
                class="px-7 py-2.5 rounded-full bg-[#182222] hover:bg-stone-900 text-white text-xs font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2"
            >
                <span>Kirim Permohonan Surat</span>
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>

    </form>

</div>
@endsection
