@extends('layouts.app')

@section('title', 'Meja Audit Akuntabilitas — Warga Digital')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" 
     x-data="{ 
         activeLog: null, 
         showModal: false, 
         showRawJson: false,
         openDetail(logData) {
             this.activeLog = logData;
             this.showModal = true;
             this.showRawJson = false;
         }
     }">

    <!-- HEADER / BANNER -->
    <div class="bg-gradient-to-r from-slate-900 via-[#131919] to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-slate-800 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5 mb-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Akuntabilitas & Integritas Sistem
                    </span>
                    @if(auth()->user()->is_super_admin)
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">Scope Global</span>
                    @elseif(auth()->user()->hasRole('ketua_rw'))
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">Scope RW {{ str_pad(auth()->user()->rw?->nomor_rw ?? auth()->user()->rt?->rw?->nomor_rw ?? 1, 2, '0', STR_PAD_LEFT) }}</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Scope RT {{ str_pad(auth()->user()->rt?->nomor_rt ?? 1, 2, '0', STR_PAD_LEFT) }}</span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Meja Audit Akuntabilitas</h1>
                <p class="text-slate-400 text-sm sm:text-base mt-1 max-w-2xl">
                    Rekam jejak tidak dapat diubah (immutable log) dari seluruh tindakan administratif, keputusan moderasi, dan transaksi penting warga dan pengurus.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-right">
                    <span class="text-xs text-slate-400 block font-medium">Total Catatan Audit</span>
                    <span class="text-2xl font-black text-emerald-400">{{ number_format($logs->total(), 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER TOOLBAR (MEJA AUDIT) -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-5">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="space-y-4">
            
            <!-- Modul Quick Filter Tabs -->
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 pb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mr-2">Modul:</span>
                @php
                    $modules = [
                        'semua' => 'Semua Modul',
                        'surat' => 'Surat',
                        'pengumuman' => 'Pengumuman',
                        'forum' => 'Forum',
                        'kependudukan' => 'Kependudukan',
                        'keuangan' => 'Keuangan',
                        'umkm' => 'UMKM',
                    ];
                @endphp
                @foreach($modules as $mKey => $mLabel)
                    <a href="{{ route('admin.audit.index', array_merge(request()->except('page'), ['modul' => $mKey])) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer {{ $modul === $mKey ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $mLabel }}
                    </a>
                @endforeach
            </div>

            <!-- Detailed Filters (Dropdowns & Search) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <input type="hidden" name="modul" value="{{ $modul }}">

                <!-- Filter Aksi -->
                <div>
                    <label for="aksi" class="block text-xs font-semibold text-slate-600 mb-1.5">Tindakan / Aksi</label>
                    <select name="aksi" id="aksi" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all">
                        <option value="semua">Semua Tindakan</option>
                        @foreach($actionOptions as $actKey => $actLabel)
                            <option value="{{ $actKey }}" {{ $aksi === $actKey ? 'selected' : '' }}>{{ $actLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Tanggal -->
                <div>
                    <label for="tanggal" class="block text-xs font-semibold text-slate-600 mb-1.5">Periode Waktu</label>
                    <select name="tanggal" id="tanggal" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all">
                        <option value="semua" {{ $tanggal === 'semua' ? 'selected' : '' }}>Semua Waktu</option>
                        <option value="hari_ini" {{ $tanggal === 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="7_hari" {{ $tanggal === '7_hari' ? 'selected' : '' }}>7 Hari Terakhir</option>
                        <option value="30_hari" {{ $tanggal === '30_hari' ? 'selected' : '' }}>30 Hari Terakhir</option>
                    </select>
                </div>

                <!-- Pencarian Teks -->
                <div class="lg:col-span-2">
                    <label for="search" class="block text-xs font-semibold text-slate-600 mb-1.5">Cari Pelaku / Alasan / Target ID</label>
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Ketik nama pelaku, alasan, atau kata kunci..."
                                   class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all">
                        </div>
                        <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs shrink-0 cursor-pointer">
                            Filter
                        </button>
                        @if($modul !== 'semua' || $aksi !== 'semua' || $tanggal !== 'semua' || !empty($search))
                            <a href="{{ route('admin.audit.index') }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition-all shrink-0 cursor-pointer" title="Reset Filter">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- FEED CATATAN AUDIT AKUNTABILITAS -->
    <div class="space-y-4">
        @forelse($logs as $log)
            @php
                $modulName = $log->modul;
                $badgeColors = match($modulName) {
                    'Surat' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'Pengumuman' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                    'Forum' => 'bg-purple-50 text-purple-700 border-purple-200',
                    'Kependudukan' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'Keuangan' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'UMKM' => 'bg-orange-50 text-orange-700 border-orange-200',
                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                };

                // JSON data untuk Alpine modal
                $logPayload = [
                    'id' => $log->id,
                    'created_at_human' => $log->created_at->format('d M Y · H:i'),
                    'created_at_diff' => $log->created_at->diffForHumans(),
                    'actor_nama' => $log->actor_nama_display,
                    'actor_role' => $log->actor_role_display,
                    'actor_role_canonical' => $log->actor_role ?? 'warga',
                    'user_id' => $log->user_id,
                    'ip_address' => $log->ip_address,
                    'aksi' => $log->aksi,
                    'aksi_label' => $log->aksi_label,
                    'modul' => $log->modul,
                    'target_type' => $log->target_type,
                    'target_id' => $log->target_id,
                    'target_description' => $log->target_description,
                    'alasan' => $log->alasan,
                    'rt_nomor' => $log->rt ? str_pad($log->rt->nomor_rt, 2, '0', STR_PAD_LEFT) : null,
                    'rw_nomor' => $log->rw ? str_pad($log->rw->nomor_rw, 2, '0', STR_PAD_LEFT) : ($log->rt?->rw ? str_pad($log->rt->rw->nomor_rw, 2, '0', STR_PAD_LEFT) : null),
                    'sebelum' => $log->sebelum,
                    'sesudah' => $log->sesudah,
                ];
            @endphp

            <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs hover:border-slate-300 hover:shadow-md transition-all">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    
                    <!-- Left: Metadata & Actor -->
                    <div class="flex items-start gap-4">
                        <!-- Icon Avatar -->
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-xs">
                            {{ strtoupper(substr($log->actor_nama_display, 0, 1)) }}
                        </div>

                        <div class="space-y-1">
                            <!-- Top row: Modul, Timestamp, Wilayah Scope -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg text-[11px] font-bold border {{ $badgeColors }}">
                                    {{ $log->modul }}
                                </span>

                                <span class="text-xs font-semibold text-slate-800">
                                    {{ $log->aksi_label }}
                                </span>

                                <span class="text-slate-300 hidden sm:inline">•</span>

                                <span class="text-xs text-slate-400">
                                    {{ $log->created_at->format('d M Y · H:i') }} ({{ $log->created_at->diffForHumans() }})
                                </span>

                                @if($log->rt_id || $log->rw_id)
                                    <span class="text-slate-300 hidden sm:inline">•</span>
                                    <span class="text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                                        @if($log->rt)
                                            RT {{ str_pad($log->rt->nomor_rt, 2, '0', STR_PAD_LEFT) }}
                                        @endif
                                        @if($log->rw)
                                            / RW {{ str_pad($log->rw->nomor_rw, 2, '0', STR_PAD_LEFT) }}
                                        @endif
                                    </span>
                                @endif
                            </div>

                            <!-- Middle row: Actor & Role -->
                            <div class="flex items-center gap-2 pt-0.5">
                                <span class="text-sm font-bold text-slate-900">{{ $log->actor_nama_display }}</span>
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $log->actor_role_display }}
                                </span>
                            </div>

                            <!-- Target description -->
                            <div class="text-xs font-medium text-slate-600 flex items-center gap-1.5 pt-0.5">
                                <span class="text-slate-400">Target:</span>
                                <span class="font-semibold text-slate-800">{{ $log->target_description }}</span>
                            </div>

                            <!-- Alasan (if present) -->
                            @if(!empty($log->alasan))
                                <div class="mt-2 text-xs text-slate-600 bg-stone-50 border-l-2 border-emerald-500 px-3 py-1.5 rounded-r-lg italic">
                                    "{{ $log->alasan }}"
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right: Action Button to Open Detail Drawer -->
                    <div class="sm:self-center shrink-0 pt-2 sm:pt-0">
                        <button type="button" 
                                @click="openDetail({{ json_encode($logPayload) }})"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-emerald-600 hover:text-white transition-all shadow-xs cursor-pointer group">
                            <span>Buka Rincian</span>
                            <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>
            </div>
        @empty
            <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm space-y-3">
                <div class="w-14 h-14 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mx-auto">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800">Tidak Ada Catatan Audit Ditemukan</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Tidak ada aktivitas atau histori audit yang cocok dengan kombinasi filter atau pencarian Anda saat ini.
                </p>
                <div class="pt-2">
                    <a href="{{ route('admin.audit.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-all">
                        Reset Semua Filter
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- PAGINATION -->
    <div class="pt-2">
        {{ $logs->links() }}
    </div>

    <!-- MODAL / DETAIL DRAWER RINCIAN AUDIT -->
    <div x-show="showModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        
        <!-- Backdrop -->
        <div x-show="showModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
             @click="showModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div x-show="showModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden text-left"
                 @keydown.escape.window="showModal = false">

                <!-- Header Modal -->
                <div class="px-6 py-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between border-b border-slate-700">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-400" x-text="activeLog?.modul"></span>
                            <span class="text-slate-400 text-xs">•</span>
                            <span class="text-xs text-slate-300 font-mono" x-text="'Audit #' + activeLog?.id"></span>
                        </div>
                        <h3 class="text-lg font-bold text-white mt-0.5" x-text="activeLog?.aksi_label"></h3>
                    </div>
                    <button type="button" 
                            @click="showModal = false" 
                            class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body Modal -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">

                    <!-- Grid Info Pelaku & Target -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-xs">
                        <div class="space-y-2">
                            <div>
                                <span class="text-slate-400 block font-medium">Pelaku Tindakan</span>
                                <span class="text-slate-900 font-bold text-sm block" x-text="activeLog?.actor_nama"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block font-medium">Role Snapshot</span>
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-bold bg-white text-slate-700 border border-slate-200 shadow-2xs mt-0.5" x-text="activeLog?.actor_role"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block font-medium">Waktu Tercatat</span>
                                <span class="text-slate-700 font-semibold" x-text="activeLog?.created_at_human + ' (' + activeLog?.created_at_diff + ')'"></span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div>
                                <span class="text-slate-400 block font-medium">Entitas Target</span>
                                <span class="text-slate-900 font-bold block" x-text="activeLog?.target_description"></span>
                                <span class="text-[11px] text-slate-400 font-mono block" x-text="activeLog?.target_type + ' [ID: ' + activeLog?.target_id + ']'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block font-medium">Lingkup Wilayah</span>
                                <span class="text-slate-700 font-semibold block" x-text="(activeLog?.rt_nomor ? 'RT ' + activeLog?.rt_nomor : '') + (activeLog?.rw_nomor ? ' / RW ' + activeLog?.rw_nomor : 'Lingkup Global')"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Alasan / Justifikasi Tindakan -->
                    <div class="space-y-1.5" x-show="activeLog?.alasan">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Alasan / Justifikasi</h4>
                        <div class="bg-stone-50 border-l-4 border-emerald-600 p-3.5 rounded-r-xl text-xs text-slate-700 font-medium italic" x-text="activeLog?.alasan"></div>
                    </div>

                    <!-- DIFF SEBELUM & SESUDAH (DIFF VIEW READABLE) -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Perubahan Nilai (Diff State)</h4>
                            <button type="button" 
                                    @click="showRawJson = !showRawJson" 
                                    class="text-[11px] font-bold text-emerald-700 hover:text-emerald-800 transition-colors cursor-pointer">
                                <span x-text="showRawJson ? 'Tutup Data Mentah JSON' : 'Lihat Data Mentah JSON'"></span>
                            </button>
                        </div>

                        <!-- Readable Diff Comparison -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Sebelum -->
                            <div class="bg-rose-50/50 rounded-2xl p-4 border border-rose-200/80 space-y-2">
                                <div class="flex items-center justify-between border-b border-rose-100 pb-2">
                                    <span class="text-xs font-bold text-rose-800 uppercase tracking-wider">Sebelum Aksi</span>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-rose-100 text-rose-700">Initial State</span>
                                </div>
                                <template x-if="!activeLog?.sebelum || Object.keys(activeLog?.sebelum).length === 0">
                                    <p class="text-xs text-slate-400 italic py-2">Tidak ada nilai sebelumnya (Pencatatan Baru / Initial Record)</p>
                                </template>
                                <template x-if="activeLog?.sebelum && Object.keys(activeLog?.sebelum).length > 0">
                                    <dl class="space-y-2 text-xs">
                                        <template x-for="(val, key) in activeLog?.sebelum" :key="'seb_' + key">
                                            <div class="bg-white/80 p-2.5 rounded-xl border border-rose-100/80">
                                                <dt class="text-[11px] font-bold text-slate-500 uppercase tracking-tight" x-text="key.replace(/_/g, ' ')"></dt>
                                                <dd class="text-xs font-semibold text-slate-800 mt-0.5 break-all" x-text="typeof val === 'object' ? JSON.stringify(val) : (val === null ? 'null' : (val === false ? 'false' : (val === true ? 'true' : val)))"></dd>
                                            </div>
                                        </template>
                                    </dl>
                                </template>
                            </div>

                            <!-- Sesudah -->
                            <div class="bg-emerald-50/50 rounded-2xl p-4 border border-emerald-200/80 space-y-2">
                                <div class="flex items-center justify-between border-b border-emerald-100 pb-2">
                                    <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Sesudah Aksi</span>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700">Current State</span>
                                </div>
                                <template x-if="!activeLog?.sesudah || Object.keys(activeLog?.sesudah).length === 0">
                                    <p class="text-xs text-slate-400 italic py-2">Tidak ada nilai setelahnya (Penghapusan / Record Purged)</p>
                                </template>
                                <template x-if="activeLog?.sesudah && Object.keys(activeLog?.sesudah).length > 0">
                                    <dl class="space-y-2 text-xs">
                                        <template x-for="(val, key) in activeLog?.sesudah" :key="'ses_' + key">
                                            <div class="bg-white/80 p-2.5 rounded-xl border border-emerald-100/80">
                                                <dt class="text-[11px] font-bold text-slate-500 uppercase tracking-tight" x-text="key.replace(/_/g, ' ')"></dt>
                                                <dd class="text-xs font-semibold text-slate-800 mt-0.5 break-all" x-text="typeof val === 'object' ? JSON.stringify(val) : (val === null ? 'null' : (val === false ? 'false' : (val === true ? 'true' : val)))"></dd>
                                            </div>
                                        </template>
                                    </dl>
                                </template>
                            </div>
                        </div>

                        <!-- Raw JSON Inspector (Toggleable) -->
                        <div x-show="showRawJson" class="bg-slate-900 rounded-2xl p-4 text-slate-200 font-mono text-[11px] space-y-3 overflow-x-auto">
                            <div>
                                <span class="text-slate-400 block mb-1 text-[10px] font-bold uppercase tracking-wider">// Payload Sebelum</span>
                                <pre x-text="JSON.stringify(activeLog?.sebelum, null, 2)"></pre>
                            </div>
                            <div class="border-t border-slate-800 pt-2">
                                <span class="text-slate-400 block mb-1 text-[10px] font-bold uppercase tracking-wider">// Payload Sesudah</span>
                                <pre x-text="JSON.stringify(activeLog?.sesudah, null, 2)"></pre>
                            </div>
                        </div>
                    </div>

                    <!-- SECURITY / TECHNICAL CONTEXT (Hanya untuk Pengurus Berwenang) -->
                    @if($canViewTechnicalDetail)
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span>IP Address Pelaku:</span>
                            <span class="font-mono font-bold text-slate-800" x-text="activeLog?.ip_address ? activeLog?.ip_address : 'Tidak tercatat'"></span>
                        </div>
                        <span class="text-[11px] text-slate-400 italic">Konteks teknis khusus pengurus</span>
                    </div>
                    @endif

                </div>

                <!-- Footer Modal -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button type="button" 
                            @click="showModal = false"
                            class="px-5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-100 border border-slate-200 transition-all shadow-2xs cursor-pointer">
                        Tutup Rincian
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
