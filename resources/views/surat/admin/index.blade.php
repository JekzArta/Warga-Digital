@extends('layouts.app')

@section('title', 'Meja Verifikasi Surat RT — Warga Digital')

@section('content')
<div class="space-y-6">

    <!-- Header Meja Verifikasi -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-[11px] font-bold text-emerald-800 tracking-wide uppercase mb-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                <span>Panel Administrasi RT 0{{ auth()->user()?->rt?->nomor_rt ?? 5 }} / RW 0{{ auth()->user()?->rw?->nomor_rw ?? 3 }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight">Meja Verifikasi Surat</h1>
            <p class="text-xs sm:text-sm text-stone-600 mt-0.5">Tinjau, setujui, tolak, atau minta kelengkapan dokumen permohonan surat masuk warga.</p>
        </div>

        <a 
            href="{{ route('surat.index') }}" 
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white hover:bg-stone-100 border border-stone-200 text-stone-700 text-xs font-bold shadow-2xs transition-all self-start sm:self-auto"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>Katalog Surat Warga</span>
        </a>
    </div>

    <!-- 4 KARTU STATISTIK ANTREAN VERIFIKASI -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-3xl p-5 border border-stone-200/90 shadow-2xs space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-400 block">Menunggu Tindakan</span>
            <div class="flex items-baseline justify-between mt-1">
                <span class="text-2xl sm:text-3xl font-extrabold text-stone-900">{{ $counts['menunggu'] }}</span>
                <span class="w-2.5 h-2.5 rounded-full bg-stone-400"></span>
            </div>
            <span class="text-[10px] text-stone-500 block">Permohonan baru masuk</span>
        </div>

        <div class="bg-white rounded-3xl p-5 border border-stone-200/90 shadow-2xs space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-sky-700 block">Sedang Ditinjau</span>
            <div class="flex items-baseline justify-between mt-1">
                <span class="text-2xl sm:text-3xl font-extrabold text-sky-950">{{ $counts['direview'] }}</span>
                <span class="w-2.5 h-2.5 rounded-full bg-sky-500 animate-pulse"></span>
            </div>
            <span class="text-[10px] text-stone-500 block">Sudah dibuka pengurus</span>
        </div>

        <div class="bg-white rounded-3xl p-5 border border-stone-200/90 shadow-2xs space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700 block">Perlu Kelengkapan</span>
            <div class="flex items-baseline justify-between mt-1">
                <span class="text-2xl sm:text-3xl font-extrabold text-amber-950">{{ $counts['perlu_kelengkapan'] }}</span>
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            </div>
            <span class="text-[10px] text-stone-500 block">Menunggu revisi warga</span>
        </div>

        <div class="bg-white rounded-3xl p-5 border border-stone-200/90 shadow-2xs space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 block">Disetujui</span>
            <div class="flex items-baseline justify-between mt-1">
                <span class="text-2xl sm:text-3xl font-extrabold text-emerald-950">{{ $counts['disetujui'] }}</span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            </div>
            <span class="text-[10px] text-stone-500 block">Nomor resmi terbit</span>
        </div>
    </div>

    <!-- PENCARIAN & FILTER TAB MEJA KERJA -->
    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-stone-200/90 shadow-2xs space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Filter Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
                @php
                    $tabs = [
                        'Semua' => 'Semua (' . $counts['total'] . ')',
                        'MENUNGGU' => 'Menunggu (' . $counts['menunggu'] . ')',
                        'DIREVIEW' => 'Ditinjau (' . $counts['direview'] . ')',
                        'PERLU_KELENGKAPAN' => 'Perlu Kelengkapan (' . $counts['perlu_kelengkapan'] . ')',
                        'DISETUJUI' => 'Disetujui (' . $counts['disetujui'] . ')',
                        'DITOLAK' => 'Ditolak (' . $counts['ditolak'] . ')',
                    ];
                @endphp

                @foreach($tabs as $k => $label)
                <a 
                    href="{{ route('admin.surat.index', ['status' => $k, 'search' => $search]) }}"
                    class="px-3.5 py-1.5 rounded-full font-semibold transition-all shrink-0 {{ $tab === $k ? 'bg-[#10231e] text-white shadow-xs' : 'bg-stone-100 hover:bg-stone-200 text-stone-700' }}"
                >
                    {{ $label }}
                </a>
                @endforeach
            </div>

            <!-- Form Cari Pemohon / Nomor Surat -->
            <form method="GET" action="{{ route('admin.surat.index') }}" class="relative w-full sm:w-72">
                <input type="hidden" name="status" value="{{ $tab }}">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="Cari pemohon / no surat..."
                    class="w-full pl-9 pr-4 py-2 bg-stone-50 rounded-xl border border-stone-200 text-xs text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </form>
        </div>

        <!-- TABEL ANTREAN PERMOHONAN SURAT MASUK -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-stone-200 text-[11px] font-bold uppercase tracking-wider text-stone-400">
                        <th class="py-3 px-3">ID / Tgl</th>
                        <th class="py-3 px-3">Nama Pemohon</th>
                        <th class="py-3 px-3">Jenis Surat</th>
                        <th class="py-3 px-3">Keperluan</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($daftarSurat as $item)
                    <tr class="hover:bg-stone-50/80 transition-colors">
                        <td class="py-3.5 px-3">
                            <span class="font-mono font-bold text-stone-900 block">WD-SRT-{{ str_pad($item->id, 4, '0', STR_PAD_LEFT) }}</span>
                            <span class="text-[10px] text-stone-400">{{ $item->created_at->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="font-bold text-stone-900 block">{{ $item->user->nama }}</span>
                            <!-- ZERO NIK: Digantikan dengan Kode Registrasi Warga yang sah -->
                            <span class="text-[10px] text-stone-400 font-mono">
                                RT 0{{ $item->rt->nomor_rt ?? 5 }} • {{ $item->user->kode_warga ?? ('WRG-RT0' . ($item->rt->nomor_rt ?? 5) . '-' . str_pad($item->user->id, 3, '0', STR_PAD_LEFT)) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="px-2 py-0.5 rounded-md bg-stone-100 text-stone-800 font-bold font-mono text-[10px]">
                                {{ $item->jenis_surat }}
                            </span>
                            @if($item->nomor_surat)
                            <span class="block text-[10px] text-emerald-800 font-mono mt-0.5">{{ $item->nomor_surat }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 max-w-[220px]">
                            <p class="truncate text-stone-700 font-medium">{{ $item->form_data['keperluan'] ?? '—' }}</p>
                            @if(!empty($item->form_data['lampiran']) || !empty($item->form_data['dokumen_url']))
                            <span class="inline-flex items-center gap-1 text-[10px] text-emerald-700 font-semibold mt-0.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                <span>Ada Lampiran</span>
                            </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3">
                            @if($item->status === 'DISETUJUI')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                <span>Disetujui</span>
                            </span>
                            @elseif($item->status === 'PERLU_KELENGKAPAN')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-300 font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <span>Perlu Berkas</span>
                            </span>
                            @elseif($item->status === 'DITOLAK')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 text-rose-800 border border-rose-300 font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                <span>Ditolak</span>
                            </span>
                            @elseif($item->status === 'DIREVIEW')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-sky-50 text-sky-800 border border-sky-300 font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-600 animate-pulse"></span>
                                <span>Ditinjau</span>
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-stone-100 text-stone-700 border border-stone-300 font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                                <span>Menunggu</span>
                            </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-right">
                            <a 
                                href="{{ route('surat.show', $item->id) }}" 
                                class="inline-flex items-center gap-1 px-3.5 py-1.5 bg-[#10231e] hover:bg-[#18362e] text-white rounded-full text-xs font-bold transition-all shadow-2xs"
                            >
                                <span>Tinjau</span>
                                <span>&rarr;</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-stone-400">
                            Tidak ada permohonan surat dalam kategori ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($daftarSurat->hasPages())
        <div class="pt-4 border-t border-stone-100">
            {{ $daftarSurat->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
