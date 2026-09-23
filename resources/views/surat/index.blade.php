@extends('layouts.app')

@section('title', 'Pengajuan Surat — Warga Digital')

@section('content')
<div class="space-y-6">

    <!-- TOP HEADER: Sesuai Mockup Figma Pengajuan surat.png -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900 tracking-tight">Pengajuan surat</h1>
            <p class="text-xs text-stone-500 mt-0.5">Kelola dan ajukan surat keterangan resmi secara mandiri tanpa antre di rumah RT.</p>
        </div>

        @if(auth()->user()?->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris']))
        <a href="{{ route('admin.surat.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-800 hover:bg-emerald-900 text-white rounded-xl text-xs font-semibold shadow-sm transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span>Meja Verifikasi RT</span>
            @if($antreanRtCount > 0)
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-400 text-stone-950">{{ $antreanRtCount }} antrean</span>
            @endif
        </a>
        @endif
    </div>

    <!-- GREETING HERO BANNER: #182222 persis mockup Figma -->
    <div class="bg-[#182222] text-white rounded-3xl p-6 sm:p-7 shadow-md relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold tracking-tight">
                    @php
                        $jam = (int) date('H');
                        if ($jam >= 4 && $jam < 11) $salam = 'Selamat Pagi';
                        elseif ($jam >= 11 && $jam < 15) $salam = 'Selamat Siang';
                        elseif ($jam >= 15 && $jam < 18) $salam = 'Selamat Sore';
                        else $salam = 'Selamat Malam';
                    @endphp
                    {{ $salam }}, {{ explode(' ', auth()->user()?->nama ?? 'Warga')[0] }}!
                </h2>
                <p class="text-xs text-stone-400 mt-1">
                    {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Akun Aktif
                </span>

                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-mono font-medium bg-white/10 text-stone-300 border border-white/10">
                    <span>NIK 3273 02•• •••• {{ substr(auth()->user()?->nik ?? '0001', -4) }}</span>
                    <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- PENCARIAN & FILTER TABS -->
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
                placeholder="Cari surat anda di sini....." 
                class="w-full pl-11 pr-4 py-3 bg-white rounded-full border border-stone-200 text-xs sm:text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-emerald-600/30 focus:border-emerald-600 transition-all shadow-2xs"
            >
            @if(!empty($search))
            <a href="{{ route('surat.index', ['status' => $tab]) }}" class="absolute inset-y-0 right-0 pr-4 flex items-center text-xs text-stone-400 hover:text-stone-700">
                Reset
            </a>
            @endif
        </form>

        <!-- Status Filter Tabs: [Semua, Menunggu, Diproses, Selesai, Ditolak] persis Figma -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none text-xs">
            @php
                $filterTabs = [
                    'Semua' => 'Semua',
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
                class="px-5 py-2 rounded-full font-medium transition-all shrink-0 {{ $tab === $key ? 'bg-[#182222] text-white shadow-xs font-semibold' : 'bg-white hover:bg-stone-100 text-stone-700 border border-stone-200/80' }}"
            >
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    <!-- MAIN TWO-COLUMN SECTION: KATALOG SURAT (Kiri) & SIDEBAR TRACKING (Kanan) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- SISI KIRI: GRID KATALOG 6 JENIS SURAT (8 Kolom) -->
        <div class="lg:col-span-8">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Pilihan Jenis Surat</span>
                <span class="text-xs text-stone-500">Tersedia 6 format resmi RT 05 / RW 03</span>
            </div>

            @if(count($katalogSurat) > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($katalogSurat as $item)
                <div class="bg-white rounded-2xl p-5 border border-stone-200/90 hover:border-emerald-600/50 hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <!-- Icon Dokumen Kotak Gelap -->
                        <div class="w-10 h-10 rounded-xl bg-[#182222] text-white flex items-center justify-center mb-4 group-hover:scale-105 transition-transform shadow-2xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>

                        <h3 class="text-sm font-bold text-stone-900 leading-snug group-hover:text-emerald-800 transition-colors">
                            {{ $item['nama'] }}
                        </h3>
                        <p class="text-xs text-stone-500 mt-1.5 line-clamp-2 leading-relaxed">
                            {{ $item['deskripsi'] }}
                        </p>

                        <div class="mt-3.5 pt-3 border-t border-stone-100 space-y-1 text-[11px]">
                            <div class="flex items-center justify-between text-stone-500">
                                <span>Syarat:</span>
                                <span class="font-medium text-stone-700 text-right truncate max-w-[140px]">{{ $item['persyaratan'] }}</span>
                            </div>
                            <div class="flex items-center justify-between text-stone-500">
                                <span>Estimasi:</span>
                                <span class="font-medium text-emerald-700">{{ $item['estimasi'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Pilih Surat Sesuai Mockup Figma -->
                    <div class="mt-5">
                        <a 
                            href="{{ route('surat.create', $item['kode']) }}" 
                            class="w-full flex items-center justify-center gap-1.5 py-2 px-4 rounded-full bg-[#F2EAE1] hover:bg-[#E8DFD5] text-stone-800 font-semibold text-xs transition-colors"
                        >
                            <span>Pilih Surat</span>
                            <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="bg-white rounded-2xl p-8 text-center border border-stone-200">
                <p class="text-xs text-stone-500">Tidak ada jenis surat yang cocok dengan pencarian "{{ $search }}".</p>
            </div>
            @endif
        </div>

        <!-- SISI KANAN: SIDEBAR STATUS PENGAJUAN SURAT SAYA (4 Kolom dengan garis pemisah persis Figma) -->
        <div class="lg:col-span-4 lg:border-l lg:border-stone-300/80 lg:pl-6 space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Status Permohonan Saya</span>
                <span class="text-xs text-stone-400">Total: {{ $riwayatSurat->count() }}</span>
            </div>

            @forelse($riwayatSurat as $surat)
                @if($surat->status === 'DISETUJUI')
                <!-- KARTU STATUS: DISETUJUI (Hijau persis mockup Figma) -->
                <div class="bg-[#EEF7F2] border border-emerald-300/80 rounded-2xl p-4 shadow-2xs space-y-3">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            <h4 class="text-xs font-bold text-stone-900">{{ $surat->jenis_surat }} — Disetujui</h4>
                        </div>
                        <span class="text-[10px] text-stone-500">{{ $surat->updated_at->format('d/m/Y') }}</span>
                    </div>

                    <div class="text-[11px] text-stone-600 space-y-0.5">
                        <p class="font-medium text-stone-800">No: <span class="font-mono">{{ $surat->nomor_surat }}</span></p>
                        <p class="text-stone-500 text-[10px] truncate">Keperluan: {{ $surat->form_data['keperluan'] ?? '—' }}</p>
                    </div>

                    <!-- Tombol Unduh PDF Surat persis Figma: tombol hitam/gelap dengan ikon download -->
                    <a 
                        href="{{ route('surat.download-pdf', $surat->id) }}" 
                        class="w-full flex items-center justify-center gap-2 py-2 px-3 bg-[#182222] hover:bg-stone-900 text-white rounded-full text-xs font-semibold shadow-xs transition-all"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Unduh PDF Surat</span>
                    </a>
                </div>

                @elseif($surat->status === 'PERLU_KELENGKAPAN')
                <!-- KARTU STATUS: PERLU DILENGKAPI (Kuning persis mockup Figma) -->
                <div class="bg-[#FFF9E6] border border-amber-300 rounded-2xl p-4 shadow-2xs space-y-3">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <h4 class="text-xs font-bold text-stone-900">{{ $surat->jenis_surat }} — Perlu Dilengkapi</h4>
                        </div>
                        <span class="text-[10px] text-stone-500">{{ $surat->updated_at->format('d/m/Y') }}</span>
                    </div>

                    <p class="text-[11px] font-mono text-stone-600">ID: WD-SRT-{{ str_pad($surat->id, 4, '0', STR_PAD_LEFT) }}</p>

                    <!-- Kotak Catatan RT Persis Mockup Figma -->
                    @php
                        $lastKelengkapan = $surat->kelengkapan->where('dari_role', 'rt')->last();
                    @endphp
                    <div class="bg-[#FFF0CC] border border-amber-200/80 rounded-xl px-3 py-2 text-[11px] text-amber-950 font-medium">
                        Catatan RT: {{ $lastKelengkapan ? $lastKelengkapan->pesan : 'Harap melengkapi dokumen persyaratan yang diminta.' }}
                    </div>

                    <!-- Tombol Unggah Ulang File -->
                    <a 
                        href="{{ route('surat.show', $surat->id) }}" 
                        class="w-full flex items-center justify-center gap-2 py-2 px-3 bg-[#182222] hover:bg-stone-900 text-white rounded-full text-xs font-semibold shadow-xs transition-all"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span>Unggah Ulang File</span>
                    </a>
                </div>

                @elseif($surat->status === 'DITOLAK')
                <!-- KARTU STATUS: DITOLAK (Merah) -->
                <div class="bg-[#FDF2F2] border border-red-300 rounded-2xl p-4 shadow-2xs space-y-3">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span>
                            <h4 class="text-xs font-bold text-red-900">{{ $surat->jenis_surat }} — Ditolak</h4>
                        </div>
                        <span class="text-[10px] text-stone-500">{{ $surat->updated_at->format('d/m/Y') }}</span>
                    </div>

                    <div class="bg-white/80 border border-red-200 rounded-xl px-3 py-2 text-[11px] text-red-800">
                        <span class="font-bold block">Alasan RT:</span>
                        {{ $surat->alasan_tolak ?: 'Data tidak memenuhi kriteria penerbitan surat pengantar.' }}
                    </div>

                    <a 
                        href="{{ route('surat.show', $surat->id) }}" 
                        class="w-full flex items-center justify-center gap-1.5 py-1.5 px-3 bg-white hover:bg-stone-100 text-stone-800 border border-stone-300 rounded-full text-xs font-medium transition-all"
                    >
                        <span>Lihat Rincian</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                @else
                <!-- KARTU STATUS: MENUNGGU / DIREVIEW (Abu/Biru) -->
                <div class="bg-white border border-stone-200 rounded-2xl p-4 shadow-2xs space-y-3">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full {{ $surat->status === 'DIREVIEW' ? 'bg-blue-600 animate-pulse' : 'bg-stone-400' }}"></span>
                            <h4 class="text-xs font-bold text-stone-900">{{ $surat->jenis_surat }} — {{ $surat->status === 'DIREVIEW' ? 'Sedang Diproses' : 'Menunggu RT' }}</h4>
                        </div>
                        <span class="text-[10px] text-stone-500">{{ $surat->created_at->format('d/m/Y') }}</span>
                    </div>

                    <div class="text-[11px] text-stone-600 space-y-1">
                        <p class="font-mono text-stone-500 text-[10px]">ID: WD-SRT-{{ str_pad($surat->id, 4, '0', STR_PAD_LEFT) }}</p>
                        <p class="truncate text-stone-700">Keperluan: {{ $surat->form_data['keperluan'] ?? '—' }}</p>
                    </div>

                    <!-- Mini Stepper Progress -->
                    <div class="pt-2 border-t border-stone-100 flex items-center justify-between text-[10px] text-stone-400">
                        <span class="font-bold text-stone-800">1. Terkirim</span>
                        <span>&rarr;</span>
                        <span class="{{ $surat->status === 'DIREVIEW' ? 'font-bold text-blue-700' : '' }}">2. Review RT</span>
                        <span>&rarr;</span>
                        <span>3. Selesai</span>
                    </div>

                    <a 
                        href="{{ route('surat.show', $surat->id) }}" 
                        class="w-full flex items-center justify-center gap-1.5 py-1.5 px-3 bg-[#F2EAE1] hover:bg-[#E8DFD5] text-stone-800 rounded-full text-xs font-medium transition-all"
                    >
                        <span>Pantau Progres</span>
                        <span>&rarr;</span>
                    </a>
                </div>
                @endif
            @empty
                <div class="bg-white rounded-2xl p-6 text-center border border-stone-200/80 space-y-2">
                    <div class="w-10 h-10 rounded-full bg-stone-100 text-stone-400 flex items-center justify-center mx-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <p class="text-xs font-medium text-stone-700">Belum ada permohonan</p>
                    <p class="text-[11px] text-stone-400">Pilih salah satu kartu jenis surat di sebelah kiri untuk membuat permohonan baru.</p>
                </div>
            @endforelse

        </div>

    </div>

</div>
@endsection
