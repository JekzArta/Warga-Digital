@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header Banner -->
    <div class="bg-gradient-to-r from-emerald-700 via-emerald-600 to-teal-700 rounded-3xl p-6 sm:p-8 text-white shadow-sm relative overflow-hidden">
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur-xs px-3 py-1 rounded-full text-xs font-medium text-emerald-100 border border-white/20 mb-3">
                <span>Peran Sistem:</span>
                <span class="font-bold text-white uppercase tracking-wider">{{ $user->getHighestRoleBadge() }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Selamat Datang, {{ $user->nama }}!</h1>
            <p class="text-emerald-100 text-sm sm:text-base mt-1 max-w-2xl leading-relaxed">
                Anda berada di portal terintegrasi Warga Digital untuk lingkungan 
                @if($user->rt)
                    <strong>RT 0{{ $user->rt->nomor_rt }} / RW 0{{ $user->rt->rw->nomor_rw }}</strong>, Kelurahan Sekeloa.
                @elseif($user->rw)
                    <strong>RW 0{{ $user->rw->nomor_rw }}</strong>, Kelurahan Sekeloa.
                @else
                    <strong>Platform Super Admin</strong>.
                @endif
            </p>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3 pt-4 border-t border-white/15 text-xs text-emerald-100">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-300"></span>
                <span>Kode Warga: <strong>{{ $user->kode_warga ?? 'ADM-PLATFORM' }}</strong></span>
            </span>
            <span class="opacity-40">•</span>
            <span>Status Akun: <strong class="capitalize">{{ $user->status }}</strong></span>
            <span class="opacity-40">•</span>
            <span>Sesi Terakhir: {{ $user->last_login ? $user->last_login->diffForHumans() : 'Sesi Pertama' }}</span>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Surat -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Surat Menunggu</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <div>
                <span class="text-2xl font-bold text-slate-900">{{ $stats['surat_menunggu'] }}</span>
                <span class="text-xs text-slate-500 block mt-0.5">Permohonan perlu diproses</span>
            </div>
        </div>

        <!-- Card 2: Saldo Kas RT -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Saldo Kas RT</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <span class="text-2xl font-bold text-slate-900">Rp {{ number_format($stats['saldo_kas'], 0, ',', '.') }}</span>
                <span class="text-xs text-emerald-600 font-medium block mt-0.5">Transparan & Terbuka</span>
            </div>
        </div>

        <!-- Card 3: UMKM Menunggu -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Listing UMKM</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
            <div>
                <span class="text-2xl font-bold text-slate-900">{{ $stats['umkm_menunggu'] }}</span>
                <span class="text-xs text-slate-500 block mt-0.5">Usulan produk warga</span>
            </div>
        </div>

        <!-- Card 4: Multi-Tenant Status -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Isolasi Tenant</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            <div>
                <span class="text-sm font-bold text-slate-900 block">Global Scope Aktif</span>
                <span class="text-xs text-slate-500 block mt-0.5">rt_id: {{ $user->rt_id ?? 'All' }} • rw_id: {{ $user->rw_id ?? ($user->rt->rw_id ?? 'All') }}</span>
            </div>
        </div>
    </div>

    <!-- 7 Fitur Utama Sesuai AGENTS.md (Roadmap Siap Pakai) -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-slate-900">7 Fitur Inti Warga Digital</h2>
            <span class="text-xs text-slate-500">SATU CREANOVA 2026</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- 1. Pengajuan Surat -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs font-bold">1</span>
                        <h3 class="text-sm font-bold text-slate-900">Pengajuan Surat Otomatis</h3>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Pengajuan surat keterangan (SKD, SKTM, SKU, dll) online dengan penomoran resmi & ekspor PDF otomatis.
                    </p>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-200/80 flex justify-between items-center text-xs">
                    <span class="text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded">Fase 2 (Prioritas 1)</span>
                    <span class="text-slate-400">Siap dibangun</span>
                </div>
            </div>

            <!-- 2. Ruang Komunitas -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold">2</span>
                        <h3 class="text-sm font-bold text-slate-900">Ruang Komunitas 3-Layer</h3>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Pengumuman resmi RT/RW, Forum musyawarah berjenjang, dan Chat Bebas real-time.
                    </p>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-200/80 flex justify-between items-center text-xs">
                    <span class="text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded">Fase 2 (Prioritas 2)</span>
                    <span class="text-slate-400">Siap dibangun</span>
                </div>
            </div>

            <!-- 3. Audit Trail -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-lg bg-amber-600 text-white flex items-center justify-center text-xs font-bold">3</span>
                        <h3 class="text-sm font-bold text-slate-900">Audit Trail Transparan</h3>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Pencatatan tak terhapus untuk semua keputusan sensitif pengurus guna menjamin akuntabilitas.
                    </p>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-200/80 flex justify-between items-center text-xs">
                    <span class="text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded">Fase 2 (Prioritas 3)</span>
                    <span class="text-slate-400">Siap dibangun</span>
                </div>
            </div>

            <!-- 4. Transparansi Anggaran -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs font-bold">4</span>
                        <h3 class="text-sm font-bold text-slate-900">Transparansi Anggaran</h3>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Laporan kas masuk & keluar RT yang dapat dipantau oleh seluruh warga setiap saat.
                    </p>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-200/80 flex justify-between items-center text-xs">
                    <span class="text-slate-700 font-medium bg-slate-200 px-2 py-0.5 rounded">Fase 3</span>
                    <span class="text-slate-400">Skema siap</span>
                </div>
            </div>

            <!-- 5. UMKM Lokal -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-lg bg-purple-600 text-white flex items-center justify-center text-xs font-bold">5</span>
                        <h3 class="text-sm font-bold text-slate-900">Katalog UMKM Warga</h3>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Promosi produk & jasa warga gratis, tanpa payment gateway, order via WhatsApp terstruktur.
                    </p>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-200/80 flex justify-between items-center text-xs">
                    <span class="text-slate-700 font-medium bg-slate-200 px-2 py-0.5 rounded">Fase 3</span>
                    <span class="text-slate-400">Skema siap</span>
                </div>
            </div>

            <!-- 6 & 7. Kalender & Galeri -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-lg bg-rose-600 text-white flex items-center justify-center text-xs font-bold">6 & 7</span>
                        <h3 class="text-sm font-bold text-slate-900">Kalender & Galeri Kegiatan</h3>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Agenda kegiatan warga dan dokumentasi foto kegiatan sosial kemasyarakatan.
                    </p>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-200/80 flex justify-between items-center text-xs">
                    <span class="text-slate-700 font-medium bg-slate-200 px-2 py-0.5 rounded">Fase 4</span>
                    <span class="text-slate-400">Skema siap</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
