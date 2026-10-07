@extends('layouts.app')

@section('title', 'Pengajuan Surat — Warga Digital')

@section('content')
<div class="space-y-6">

    <!-- 1. HEADER HALAMAN EDITORIAL -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-[11px] font-bold text-emerald-800 tracking-wide uppercase mb-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                <span>Layanan Mandiri RT 0{{ auth()->user()?->rt?->nomor_rt ?? 5 }} / RW 0{{ auth()->user()?->rw?->nomor_rw ?? 3 }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight">Pengajuan surat</h1>
            <p class="text-xs sm:text-sm text-stone-600 mt-1 max-w-2xl">
                Kelola dan ajukan surat keterangan resmi secara mandiri tanpa antre di rumah RT. Dokumen diterbitkan dengan nomor resmi dan verifikasi digital.
            </p>
        </div>

        @if(auth()->user()?->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris']))
        <div class="flex items-center gap-2 self-start md:self-auto shrink-0">
            <a 
                href="{{ route('admin.surat.index') }}" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#10231e] hover:bg-[#18362e] text-white rounded-full text-xs font-bold shadow-xs hover:shadow transition-all group"
            >
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span>Meja Verifikasi RT</span>
                @if($antreanRtCount > 0)
                <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-[#e5a53f] text-[#10231e]">
                    {{ $antreanRtCount }} antrean
                </span>
                @endif
            </a>
        </div>
        @endif
    </div>

    <!-- 2. SERVICE BANNER: WARM EVERGREEN (ZERO NIK LEAKAGE) -->
    <div class="bg-[#10231e] text-white rounded-3xl p-6 sm:p-7 shadow-xs border border-white/10 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-52 h-52 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 relative z-10">
            <div class="space-y-1.5 max-w-xl">
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] font-semibold border border-emerald-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Pelayanan Persuratan Online Aktif</span>
                </div>
                <h2 class="text-lg sm:text-xl font-bold tracking-tight text-white">
                    Format Dokumen Resmi Standar Kelurahan & RT
                </h2>
                <p class="text-xs text-stone-300 leading-relaxed">
                    Pengajuan diproses langsung oleh Ketua / Wakil RT setempat. Surat resmi berformat PDF A4 lengkap dengan stempel digital dan tautan kode verifikasi keabsahan.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-white/10 border border-white/15 text-xs text-stone-200">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <div>
                        <span class="block text-[10px] text-stone-400 leading-tight">Pemohon Terverifikasi</span>
                        <span class="font-bold text-white">{{ auth()->user()?->nama }}</span>
                    </div>
                </div>

                <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-white/10 border border-white/15 text-xs font-mono text-emerald-300">
                    <span class="text-[10px] text-stone-400 font-sans block">Identitas:</span>
                    <span class="font-bold">{{ auth()->user()?->kode_warga ?? ('WRG-RT0' . (auth()->user()?->rt?->nomor_rt ?? 5) . '-' . str_pad(auth()->id(), 3, '0', STR_PAD_LEFT)) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. PENCARIAN & STATUS FILTER TABS -->
    <div class="space-y-3">
        <!-- Input Search: Full width rounded pill -->
        <form method="GET" action="{{ route('surat.index') }}" class="relative w-full">
            <input type="hidden" name="status" value="{{ $tab }}">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-stone-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input 
                type="text" 
                name="search" 
                value="{{ $search }}" 
                placeholder="Cari jenis surat atau riwayat permohonan Anda di sini..." 
                class="w-full pl-11 pr-16 py-3 bg-white rounded-full border border-stone-200/90 text-xs sm:text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all shadow-2xs"
            >
            @if(!empty($search))
            <a href="{{ route('surat.index', ['status' => $tab]) }}" class="absolute inset-y-0 right-0 pr-4 flex items-center text-xs font-semibold text-stone-400 hover:text-stone-700">
                Reset
            </a>
            @endif
        </form>

        <!-- Status Filter Tabs: [Semua, Menunggu, Diproses, Selesai, Ditolak, Perlu Kelengkapan] -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none text-xs">
            @php
                $filterTabs = [
                    'Semua' => 'Semua Status',
                    'MENUNGGU' => 'Menunggu',
                    'DIREVIEW' => 'Diproses',
                    'DISETUJUI' => 'Selesai',
                    'DITOLAK' => 'Ditolak',
                    'PERLU_KELENGKAPAN' => 'Perlu Kelengkapan',
                ];
            @endphp

            @foreach($filterTabs as $key => $label)
            <a 
                href="{{ route('surat.index', ['status' => $key, 'search' => $search]) }}" 
                class="px-4 py-2 rounded-full font-medium transition-all shrink-0 {{ $tab === $key ? 'bg-[#10231e] text-white font-semibold shadow-xs' : 'bg-white hover:bg-stone-100 text-stone-700 border border-stone-200/80 shadow-2xs' }}"
            >
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    <!-- 4. MAIN SPLIT SECTION: KATALOG 6 SURAT (Kiri) & TRACKING AKTIF (Kanan) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- SISI KIRI: KATALOG 6 JENIS SURAT (7 Kolom) -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-4">
            <div class="flex items-center justify-between border-b border-stone-200/80 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-stone-700">Pilihan Jenis Surat</h2>
                </div>
                <span class="text-xs text-stone-500">6 Format Resmi RT 0{{ auth()->user()?->rt?->nomor_rt ?? 5 }}</span>
            </div>

            @if(count($katalogSurat) > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($katalogSurat as $item)
                <div class="bg-white rounded-3xl p-5 border border-stone-200/90 hover:border-emerald-700/40 hover:shadow-md transition-all duration-200 flex flex-col justify-between group shadow-2xs">
                    <div>
                        <!-- Header Card & Kode Tag -->
                        <div class="flex items-start justify-between gap-2 mb-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-[#10231e] text-white flex items-center justify-center group-hover:scale-105 transition-transform shadow-2xs">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-stone-100 text-stone-700 font-mono text-[11px] font-bold">
                                {{ $item['kode'] }}
                            </span>
                        </div>

                        <!-- Judul Surat (Harus mencocokkan assertSee di test suite) -->
                        <h3 class="text-sm font-bold text-stone-900 leading-snug group-hover:text-emerald-900 transition-colors">
                            {{ $item['nama'] }}
                        </h3>

                        <p class="text-xs text-stone-500 mt-1.5 line-clamp-2 leading-relaxed">
                            {{ $item['deskripsi'] }}
                        </p>

                        <!-- Persyaratan Singkat & Estimasi -->
                        <div class="mt-4 pt-3 border-t border-stone-100 space-y-1.5 text-[11px]">
                            <div class="flex items-center justify-between text-stone-500">
                                <span class="font-medium text-stone-400">Syarat:</span>
                                <span class="font-semibold text-stone-700 text-right truncate max-w-[140px]">{{ $item['persyaratan'] }}</span>
                            </div>
                            <div class="flex items-center justify-between text-stone-500">
                                <span class="font-medium text-stone-400">Estimasi:</span>
                                <span class="font-semibold text-emerald-800">{{ $item['estimasi'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Pilih Surat -->
                    <div class="mt-5">
                        <a 
                            href="{{ route('surat.create', $item['kode']) }}" 
                            class="w-full flex items-center justify-center gap-1.5 py-2.5 px-4 rounded-full bg-[#f6f1e4] hover:bg-[#eae3d2] text-stone-900 font-bold text-xs transition-colors group-hover:bg-[#10231e] group-hover:text-white"
                        >
                            <span>Pilih Surat</span>
                            <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="bg-white rounded-3xl p-8 text-center border border-stone-200/90 shadow-2xs space-y-2">
                <p class="text-xs font-semibold text-stone-700">Tidak ada jenis surat yang cocok dengan pencarian "{{ $search }}".</p>
                <a href="{{ route('surat.index') }}" class="inline-block text-xs text-emerald-800 font-bold hover:underline">Tampilkan semua jenis surat &rarr;</a>
            </div>
            @endif
        </div>

        <!-- SISI KANAN: STATUS PERMOHONAN SAYA (5 Kolom) -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-4">
            <div class="flex items-center justify-between border-b border-stone-200/80 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#e5a53f]"></span>
                    <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-stone-700">Status Permohonan Saya</h2>
                </div>
                <span class="text-xs font-mono text-stone-400 font-semibold">{{ $riwayatSurat->count() }} Berkas</span>
            </div>

            @forelse($riwayatSurat as $surat)
                @if($surat->status === 'DISETUJUI')
                <!-- KARTU STATUS: DISETUJUI -->
                <div class="bg-white border-2 border-emerald-500/40 rounded-3xl p-4 sm:p-5 shadow-2xs space-y-3.5 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-bl-full pointer-events-none"></div>

                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                <span>Disetujui RT</span>
                            </span>
                            <h4 class="text-xs sm:text-sm font-bold text-stone-900 leading-snug">
                                {{ $surat->jenis_surat }} — {{ \App\Services\SuratPdfGenerator::getNamaJenisSurat($surat->jenis_surat) }}
                            </h4>
                        </div>
                        <span class="text-[10px] text-stone-400 shrink-0 font-medium">{{ $surat->updated_at->format('d/m/Y') }}</span>
                    </div>

                    <div class="bg-stone-50 rounded-2xl p-3 text-[11px] space-y-1 border border-stone-200/70">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-stone-400 text-[10px]">No. Surat:</span>
                            <span class="font-mono font-bold text-emerald-900 truncate">{{ $surat->nomor_surat }}</span>
                        </div>
                        <p class="text-stone-600 truncate text-[11px]">
                            <span class="text-stone-400 text-[10px]">Keperluan:</span> {{ $surat->form_data['keperluan'] ?? '—' }}
                        </p>
                    </div>

                    <!-- Tombol Unduh PDF Surat Resmi -->
                    <a 
                        href="{{ route('surat.download-pdf', $surat->id) }}" 
                        class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-[#10231e] hover:bg-[#18362e] text-white rounded-full text-xs font-bold shadow-xs transition-all"
                    >
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Unduh PDF Surat</span>
                    </a>
                </div>

                @elseif($surat->status === 'PERLU_KELENGKAPAN')
                <!-- KARTU STATUS: PERLU KELENGKAPAN -->
                <div class="bg-amber-50/70 border-2 border-amber-400/60 rounded-3xl p-4 sm:p-5 shadow-2xs space-y-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300 font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                <span>Perlu Dilengkapi</span>
                            </span>
                            <h4 class="text-xs sm:text-sm font-bold text-stone-900 leading-snug">
                                {{ $surat->jenis_surat }} — Perlu Perbaikan Berkas
                            </h4>
                        </div>
                        <span class="text-[10px] text-stone-400 shrink-0 font-medium">{{ $surat->updated_at->format('d/m/Y') }}</span>
                    </div>

                    @php
                        $lastKelengkapan = $surat->kelengkapan->where('dari_role', 'rt')->last();
                    @endphp
                    <div class="bg-white border border-amber-200 rounded-2xl p-3 text-[11px] text-stone-800 space-y-1">
                        <span class="font-bold text-amber-900 text-[10px] uppercase block tracking-wider">Catatan Pengurus RT:</span>
                        <p class="italic text-stone-700 leading-relaxed">
                            "{{ $lastKelengkapan ? $lastKelengkapan->pesan : 'Harap melengkapi dokumen persyaratan yang diminta.' }}"
                        </p>
                    </div>

                    <!-- Tombol Unggah Ulang File -->
                    <a 
                        href="{{ route('surat.show', $surat->id) }}" 
                        class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-full text-xs font-bold shadow-xs transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span>Unggah Ulang File</span>
                    </a>
                </div>

                @elseif($surat->status === 'DITOLAK')
                <!-- KARTU STATUS: DITOLAK -->
                <div class="bg-rose-50/70 border-2 border-rose-300 rounded-3xl p-4 sm:p-5 shadow-2xs space-y-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-900 border border-rose-300 font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                <span>Permohonan Ditolak</span>
                            </span>
                            <h4 class="text-xs sm:text-sm font-bold text-stone-900 leading-snug">
                                {{ $surat->jenis_surat }} — Tidak Disetujui
                            </h4>
                        </div>
                        <span class="text-[10px] text-stone-400 shrink-0 font-medium">{{ $surat->updated_at->format('d/m/Y') }}</span>
                    </div>

                    <div class="bg-white border border-rose-200 rounded-2xl p-3 text-[11px] text-stone-800 space-y-1">
                        <span class="font-bold text-rose-900 text-[10px] uppercase block tracking-wider">Alasan Penolakan RT:</span>
                        <p class="text-stone-700 leading-relaxed">
                            {{ $surat->alasan_tolak ?: 'Data tidak memenuhi kriteria penerbitan surat pengantar.' }}
                        </p>
                    </div>

                    <a 
                        href="{{ route('surat.show', $surat->id) }}" 
                        class="w-full flex items-center justify-center gap-1.5 py-2 px-4 bg-white hover:bg-stone-100 text-stone-800 border border-stone-300 rounded-full text-xs font-semibold transition-all"
                    >
                        <span>Lihat Rincian</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                @else
                <!-- KARTU STATUS: MENUNGGU / DIREVIEW -->
                <div class="bg-white border border-stone-200/90 rounded-3xl p-4 sm:p-5 shadow-2xs space-y-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full {{ $surat->status === 'DIREVIEW' ? 'bg-sky-50 text-sky-800 border border-sky-200' : 'bg-stone-100 text-stone-700 border border-stone-200' }} font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full {{ $surat->status === 'DIREVIEW' ? 'bg-sky-600 animate-pulse' : 'bg-stone-400' }}"></span>
                                <span>{{ $surat->status === 'DIREVIEW' ? 'Sedang Ditinjau' : 'Menunggu Antrean RT' }}</span>
                            </span>
                            <h4 class="text-xs sm:text-sm font-bold text-stone-900 leading-snug">
                                {{ $surat->jenis_surat }} — {{ \App\Services\SuratPdfGenerator::getNamaJenisSurat($surat->jenis_surat) }}
                            </h4>
                        </div>
                        <span class="text-[10px] text-stone-400 shrink-0 font-medium">{{ $surat->created_at->format('d/m/Y') }}</span>
                    </div>

                    <div class="bg-stone-50 rounded-2xl p-3 text-[11px] space-y-1 border border-stone-200/70">
                        <p class="font-mono text-stone-500 text-[10px]">ID: WD-SRT-{{ str_pad($surat->id, 4, '0', STR_PAD_LEFT) }}</p>
                        <p class="text-stone-700 truncate">Keperluan: {{ $surat->form_data['keperluan'] ?? '—' }}</p>
                    </div>

                    <!-- Alur Status Progres -->
                    <div class="pt-2 border-t border-stone-100 flex items-center justify-between text-[10px] text-stone-400">
                        <span class="font-bold text-emerald-800">1. Terkirim</span>
                        <span>&rarr;</span>
                        <span class="{{ $surat->status === 'DIREVIEW' ? 'font-bold text-sky-800' : '' }}">2. Verifikasi RT</span>
                        <span>&rarr;</span>
                        <span>3. Penerbitan</span>
                    </div>

                    <a 
                        href="{{ route('surat.show', $surat->id) }}" 
                        class="w-full flex items-center justify-center gap-1.5 py-2 px-4 bg-[#f6f1e4] hover:bg-[#eae3d2] text-stone-900 rounded-full text-xs font-bold transition-all"
                    >
                        <span>Pantau Progres</span>
                        <span>&rarr;</span>
                    </a>
                </div>
                @endif
            @empty
                <div class="bg-white rounded-3xl p-8 text-center border border-stone-200/90 shadow-2xs space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-stone-800">Belum Ada Permohonan Aktif</p>
                        <p class="text-[11px] text-stone-500 max-w-xs mx-auto">
                            Pilih salah satu kartu jenis surat di sebelah kiri untuk mengajukan surat keterangan resmi secara mandiri.
                        </p>
                    </div>
                </div>
            @endforelse

        </div>

    </div>

</div>
@endsection
