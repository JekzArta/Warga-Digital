@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- 1. TOP GREETING HERO BANNER (Matching Figma #182222) -->
    <div class="bg-[#182222] rounded-3xl p-6 sm:p-8 text-white border border-slate-800/80 shadow-md relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-2">
                    <span>{{ $greeting }}, {{ explode(' ', $user->nama)[0] }}!</span>
                </h1>
                <p class="text-slate-400 text-xs sm:text-sm mt-1 font-medium">
                    {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}
                </p>
            </div>

            <!-- Right Status Badges -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Status Akun Pill -->
                <div class="bg-[#1F332B] text-[#52D28A] px-3.5 py-1.5 rounded-full text-xs font-semibold border border-[#2D4E3E] flex items-center gap-2 shadow-inner">
                    <span class="w-2 h-2 rounded-full bg-[#52D28A] animate-pulse"></span>
                    <span>Akun Aktif</span>
                </div>

                <!-- Masked NIK Pill (Complying with UU PDP) -->
                @php
                    $maskedNik = '3273 02•• •••• ' . substr($user->nik ?? '0000', -4);
                @endphp
                <div class="bg-[#232F2E] text-slate-300 px-3.5 py-1.5 rounded-full text-xs font-mono font-medium border border-[#314341] flex items-center gap-2 shadow-inner">
                    <span>NIK {{ $maskedNik }}</span>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. QUICK ACTION 4-CARDS (Matching Figma Action Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Ajukan Surat -->
        <a href="#surat" class="bg-white rounded-2xl p-5 border border-stone-200/90 hover:border-emerald-600/40 hover:shadow-md transition-all duration-200 group flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-[#131919] text-white flex items-center justify-center mb-4 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-stone-900 group-hover:text-emerald-700 transition-colors">Ajukan Surat</h3>
                <p class="text-xs text-stone-500 mt-1">6 Jenis surat, PDF siap unduh</p>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-xs font-bold text-stone-900 group-hover:text-emerald-700">
                <span>Ajukan</span>
                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>

        <!-- Card 2: Forum Warga -->
        <a href="#forum" class="bg-white rounded-2xl p-5 border border-stone-200/90 hover:border-emerald-600/40 hover:shadow-md transition-all duration-200 group flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-[#131919] text-white flex items-center justify-center mb-4 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-stone-900 group-hover:text-emerald-700 transition-colors">Forum Warga</h3>
                <p class="text-xs text-stone-500 mt-1">Musyawarah & balasan topik</p>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-xs font-bold text-stone-900 group-hover:text-emerald-700">
                <span>Buka Forum</span>
                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>

        <!-- Card 3: Transparansi Kas -->
        <a href="#anggaran" class="bg-gradient-to-br from-[#B55239] to-[#963F28] text-white rounded-2xl p-5 hover:shadow-md transition-all duration-200 group flex flex-col justify-between shadow-xs">
            <div>
                <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center mb-4 group-hover:scale-105 transition-transform backdrop-blur-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">Transparansi Kas</h3>
                <p class="text-xs text-white/80 mt-1">Pantau keuangan RT & RW terbuka</p>
            </div>
            <div class="mt-4 pt-3 border-t border-white/20 flex items-center justify-between text-xs font-bold text-white">
                <span>Lihat Kas</span>
                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>

        <!-- Card 4: Belanja UMKM -->
        <a href="#umkm" class="bg-white rounded-2xl p-5 border border-stone-200/90 hover:border-emerald-600/40 hover:shadow-md transition-all duration-200 group flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-[#131919] text-white flex items-center justify-center mb-4 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-stone-900 group-hover:text-emerald-700 transition-colors">Belanja UMKM</h3>
                <p class="text-xs text-stone-500 mt-1">Dukung produk & jasa warga sekitar</p>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-xs font-bold text-stone-900 group-hover:text-emerald-700">
                <span>Belanja</span>
                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>
    </div>

    <!-- 3. REKOMENDASI UMKM DI SEKITAR ANDA (Matching Figma Product Carousel) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-extrabold text-stone-900 tracking-tight">Rekomendasi UMKM di Sekitar Anda</h2>
            <a href="#umkm" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                <span>Lihat Semua</span>
                <span>&rarr;</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Sample UMKM 1: Keripik Tempe -->
            <div class="bg-white rounded-2xl border border-stone-200/90 overflow-hidden shadow-2xs hover:shadow-md transition-all group">
                <div class="h-44 bg-amber-100 relative overflow-hidden flex items-center justify-center">
                    <img 
                        src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80" 
                        alt="Keripik Tempe Bu Ani" 
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    >
                    <button class="w-8 h-8 rounded-full bg-white/90 text-rose-500 flex items-center justify-center absolute top-3 right-3 shadow-xs hover:bg-white transition-colors">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </button>
                    <span class="absolute bottom-2.5 left-2.5 bg-black/60 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md">
                        RT 05
                    </span>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-stone-900 text-sm truncate">Gado-gado & Kupat Tahu Bu Imas</h4>
                        <div class="flex items-center gap-1 text-xs font-bold text-amber-500 shrink-0">
                            <span>★</span>
                            <span class="text-stone-800">4.9</span>
                            <span class="text-stone-400 font-normal">(58)</span>
                        </div>
                    </div>
                    <p class="text-xs text-stone-500 mt-1 truncate">Kuliner olahan rumahan higienis</p>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-baseline gap-2">
                            <span class="text-base font-extrabold text-stone-900">Rp 15.000</span>
                            <span class="text-xs text-stone-400 line-through">Rp 18.000</span>
                        </div>
                        <a href="https://wa.me/6281234567801?text=Halo%20Bu%20Imas,%20saya%20tertarik%20pesan%20lewat%20Warga%20Digital" target="_blank" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors flex items-center gap-1 shadow-2xs">
                            <span>Pesan WA</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sample UMKM 2: Aneka Kue Basah -->
            <div class="bg-white rounded-2xl border border-stone-200/90 overflow-hidden shadow-2xs hover:shadow-md transition-all group">
                <div class="h-44 bg-amber-100 relative overflow-hidden flex items-center justify-center">
                    <img 
                        src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=600&q=80" 
                        alt="Aneka Kue Basah" 
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    >
                    <button class="w-8 h-8 rounded-full bg-white/90 text-rose-500 flex items-center justify-center absolute top-3 right-3 shadow-xs hover:bg-white transition-colors">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </button>
                    <span class="absolute bottom-2.5 left-2.5 bg-black/60 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md">
                        RT 05
                    </span>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-stone-900 text-sm truncate">Aneka Kue Basah & Nasi Box</h4>
                        <div class="flex items-center gap-1 text-xs font-bold text-amber-500 shrink-0">
                            <span>★</span>
                            <span class="text-stone-800">4.8</span>
                            <span class="text-stone-400 font-normal">(42)</span>
                        </div>
                    </div>
                    <p class="text-xs text-stone-500 mt-1 truncate">Menerima pesanan arisan & tasyakuran</p>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-baseline gap-2">
                            <span class="text-base font-extrabold text-stone-900">Rp 25.000</span>
                        </div>
                        <a href="https://wa.me/6281234567802?text=Halo%20saya%20ingin%20tanya%20menu%20kue%20lewat%20Warga%20Digital" target="_blank" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors flex items-center gap-1 shadow-2xs">
                            <span>Pesan WA</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sample UMKM 3: Jasa Servis & Cuci AC -->
            <div class="bg-white rounded-2xl border border-stone-200/90 overflow-hidden shadow-2xs hover:shadow-md transition-all group">
                <div class="h-44 bg-slate-100 relative overflow-hidden flex items-center justify-center">
                    <img 
                        src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&w=600&q=80" 
                        alt="Jasa Servis AC" 
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    >
                    <button class="w-8 h-8 rounded-full bg-white/90 text-rose-500 flex items-center justify-center absolute top-3 right-3 shadow-xs hover:bg-white transition-colors">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </button>
                    <span class="absolute bottom-2.5 left-2.5 bg-black/60 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md">
                        Jasa • RT 05
                    </span>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-stone-900 text-sm truncate">Servis & Cuci AC Pak Slamet</h4>
                        <div class="flex items-center gap-1 text-xs font-bold text-amber-500 shrink-0">
                            <span>★</span>
                            <span class="text-stone-800">5.0</span>
                            <span class="text-stone-400 font-normal">(19)</span>
                        </div>
                    </div>
                    <p class="text-xs text-stone-500 mt-1 truncate">Cuci AC, isi freon & perbaikan</p>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-1 rounded">Mulai Rp 65.000</span>
                        </div>
                        <a href="https://wa.me/6281234567803?text=Halo%20Pak%20Slamet,%20saya%20butuh%20servis%20AC%20lewat%20Warga%20Digital" target="_blank" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors flex items-center gap-1 shadow-2xs">
                            <span>Pesan WA</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. 2-COLUMN MAIN CONTENT (Left 60% : Right 40%) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN (Kalender, Pengumuman, Aktivitas) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- WIDGET 1: KALENDER EVENT (Matching Figma Calendar Component) -->
            <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-base font-extrabold text-stone-900">Kalender Event</h3>
                    <div class="flex items-center gap-3">
                        <button class="w-7 h-7 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 flex items-center justify-center text-xs">&larr;</button>
                        <span class="text-xs font-bold text-stone-800">{{ now()->translatedFormat('F Y') }}</span>
                        <button class="w-7 h-7 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 flex items-center justify-center text-xs">&rarr;</button>
                    </div>
                </div>

                <!-- 7-Days Calendar Grid -->
                <div class="grid grid-cols-7 gap-2 text-center text-xs mb-4">
                    <span class="text-stone-400 font-semibold py-1">Min</span>
                    <span class="text-stone-400 font-semibold py-1">Sen</span>
                    <span class="text-stone-400 font-semibold py-1">Sel</span>
                    <span class="text-stone-400 font-semibold py-1">Rab</span>
                    <span class="text-stone-400 font-semibold py-1">Kam</span>
                    <span class="text-stone-400 font-semibold py-1">Jum</span>
                    <span class="text-stone-400 font-semibold py-1">Sab</span>

                    <!-- Sample days -->
                    <span class="text-stone-300 py-1.5">28</span>
                    <span class="text-stone-300 py-1.5">29</span>
                    <span class="text-stone-300 py-1.5">30</span>
                    <span class="text-stone-300 py-1.5">31</span>
                    <span class="text-stone-700 py-1.5 font-medium">1</span>
                    <span class="text-stone-700 py-1.5 font-medium">2</span>
                    <span class="text-stone-700 py-1.5 font-medium">3</span>

                    <span class="text-stone-700 py-1.5 font-medium">4</span>
                    <span class="text-stone-700 py-1.5 font-medium">5</span>
                    <span class="text-stone-700 py-1.5 font-medium">6</span>
                    <span class="text-stone-700 py-1.5 font-medium">7</span>
                    <span class="text-stone-700 py-1.5 font-medium">8</span>
                    <span class="text-stone-700 py-1.5 font-medium">9</span>
                    <span class="text-stone-700 py-1.5 font-medium">10</span>

                    <span class="text-emerald-700 font-bold py-1.5 relative">
                        11
                        <span class="w-1 h-1 rounded-full bg-emerald-500 absolute bottom-0.5 left-1/2 -translate-x-1/2"></span>
                    </span>
                    <span class="text-stone-700 py-1.5 font-medium">12</span>
                    <!-- Active/Today -->
                    <span class="bg-[#131919] text-white font-bold rounded-xl py-1.5 shadow-xs">13</span>
                    <span class="text-stone-700 py-1.5 font-medium">14</span>
                    <span class="text-stone-700 py-1.5 font-medium">15</span>
                    <span class="text-stone-700 py-1.5 font-medium">16</span>
                    <span class="text-rose-700 font-bold py-1.5 relative">
                        17
                        <span class="w-1 h-1 rounded-full bg-rose-500 absolute bottom-0.5 left-1/2 -translate-x-1/2"></span>
                    </span>

                    <span class="text-stone-700 py-1.5 font-medium">18</span>
                    <span class="text-stone-700 py-1.5 font-medium">19</span>
                    <span class="text-amber-700 font-bold py-1.5 relative">
                        20
                        <span class="w-1 h-1 rounded-full bg-amber-500 absolute bottom-0.5 left-1/2 -translate-x-1/2"></span>
                    </span>
                    <span class="text-stone-700 py-1.5 font-medium">21</span>
                    <span class="text-stone-700 py-1.5 font-medium">22</span>
                    <span class="text-stone-700 py-1.5 font-medium">23</span>
                    <span class="text-stone-700 py-1.5 font-medium">24</span>

                    <span class="text-emerald-700 font-bold py-1.5 relative">
                        25
                        <span class="w-1 h-1 rounded-full bg-emerald-500 absolute bottom-0.5 left-1/2 -translate-x-1/2"></span>
                    </span>
                    <span class="text-stone-700 py-1.5 font-medium">26</span>
                    <span class="text-stone-700 py-1.5 font-medium">27</span>
                    <span class="text-stone-700 py-1.5 font-medium">28</span>
                    <span class="text-stone-700 py-1.5 font-medium">29</span>
                    <span class="text-stone-700 py-1.5 font-medium">30</span>
                    <span class="text-stone-700 py-1.5 font-medium">31</span>
                </div>

                <!-- Event Legend -->
                <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-stone-100 text-[11px] text-stone-500">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                        <span>Info</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Penting</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>Mendesak</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Rutinitas</span>
                    </span>
                </div>

                <!-- Event Schedule List Items -->
                <div class="mt-4 space-y-2.5">
                    <div class="p-3 rounded-2xl bg-stone-50 border border-stone-200/80 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-white border border-stone-200 flex flex-col items-center justify-center shrink-0">
                            <span class="text-base font-extrabold text-stone-900 leading-none">17</span>
                            <span class="text-[9px] font-bold text-stone-500 uppercase mt-0.5">Agt</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-stone-900 text-xs sm:text-sm truncate">Upacara & Lomba Kemerdekaan RT 05</h4>
                            <p class="text-[11px] text-stone-500 mt-0.5 truncate">08:00 WIB • Lapangan RW 03 Sekeloa</p>
                        </div>
                        <span class="text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-1 rounded-md shrink-0">
                            Mendesak
                        </span>
                    </div>

                    <div class="p-3 rounded-2xl bg-stone-50 border border-stone-200/80 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-white border border-stone-200 flex flex-col items-center justify-center shrink-0">
                            <span class="text-base font-extrabold text-stone-900 leading-none">25</span>
                            <span class="text-[9px] font-bold text-stone-500 uppercase mt-0.5">Agt</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-stone-900 text-xs sm:text-sm truncate">Pelayanan Posyandu Balita & Lansia</h4>
                            <p class="text-[11px] text-stone-500 mt-0.5 truncate">09:00 WIB • Rumah Ibu Ketua RT 05</p>
                        </div>
                        <span class="text-[10px] font-bold text-sky-700 bg-sky-50 border border-sky-200 px-2 py-1 rounded-md shrink-0">
                            Info
                        </span>
                    </div>
                </div>
            </div>

            <!-- WIDGET 2: PENGUMUMAN TERBARU (Matching Figma Announcement List) -->
            <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-extrabold text-stone-900">Pengumuman Terbaru</h3>
                    <a href="#pengumuman" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Lihat Semua...</a>
                </div>

                <div class="divide-y divide-stone-100">
                    @forelse($announcements as $ann)
                        <div class="py-3.5 first:pt-0 last:pb-0">
                            <div class="flex items-center gap-2 mb-1.5">
                                @if($ann->tipe === 'MENDESAK')
                                    <span class="text-[10px] font-extrabold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded">MENDESAK</span>
                                @elseif($ann->tipe === 'PENTING')
                                    <span class="text-[10px] font-extrabold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">PENTING</span>
                                @else
                                    <span class="text-[10px] font-extrabold text-sky-700 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded">INFO</span>
                                @endif
                                <span class="text-[11px] text-stone-400 font-medium">{{ $ann->created_at->diffForHumans() }}</span>
                                <span class="text-[11px] text-stone-400">• {{ $ann->scope_type === 'rw' ? 'Lingkup RW 03' : 'Lingkup RT 05' }}</span>
                            </div>
                            <h4 class="font-bold text-stone-900 text-sm hover:text-emerald-700 transition-colors cursor-pointer">{{ $ann->judul }}</h4>
                            <p class="text-xs text-stone-600 mt-1 leading-relaxed line-clamp-2">{{ $ann->konten }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-stone-400 py-4 text-center">Belum ada pengumuman terbaru.</p>
                    @endforelse
                </div>
            </div>

            <!-- WIDGET 3: AKTIVITAS TERBARU (Matching Figma Timeline) -->
            <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-extrabold text-stone-900">Aktivitas Terbaru</h3>
                    <a href="#aktivitas" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Lihat Semua...</a>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="p-3 rounded-2xl bg-stone-50/80 border border-stone-200/60 flex items-start gap-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0"></span>
                        <div class="flex-1">
                            <span class="font-bold text-stone-900 block">Surat Domisili Anda sedang direview oleh Ketua RT</span>
                            <span class="text-[11px] text-stone-400">10 menit yang lalu • Pengajuan Surat</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-2xl bg-stone-50/80 border border-stone-200/60 flex items-start gap-3">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>
                        <div class="flex-1">
                            <span class="font-bold text-stone-900 block">Ada balasan baru di topik forum "Kerja Bakti Akhir Pekan"</span>
                            <span class="text-[11px] text-stone-400">1 jam yang lalu • Forum Warga</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-2xl bg-stone-50/80 border border-stone-200/60 flex items-start gap-3">
                        <span class="w-2 h-2 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>
                        <div class="flex-1">
                            <span class="font-bold text-stone-900 block">Pembayaran Iuran Kas RT Bulan Ini Tercatat Lunas</span>
                            <span class="text-[11px] text-stone-400">Kemarin • Transparansi Anggaran</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN (Status Surat, Galeri Highlight, Kas RT) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- WIDGET 1: STATUS SURAT SAYA (Matching Figma Stepper Track) -->
            <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-extrabold text-stone-900">Status Surat Saya</h3>
                    <a href="#surat" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Detail</a>
                </div>

                <div class="bg-[#FAF7F2] p-4 rounded-2xl border border-stone-200">
                    <span class="text-[10px] font-mono text-stone-500 block uppercase tracking-wider">No. 001/RT05/RW03/SK/IX/2026</span>
                    <h4 class="font-bold text-stone-900 text-sm mt-0.5">Surat Keterangan Domisili</h4>

                    <!-- Stepper Track -->
                    <div class="mt-5 relative">
                        <div class="absolute top-2.5 left-2 right-2 h-0.5 bg-stone-300"></div>
                        <div class="absolute top-2.5 left-2 w-1/2 h-0.5 bg-emerald-600"></div>

                        <div class="relative flex justify-between text-center">
                            <!-- Step 1: Menunggu -->
                            <div class="flex flex-col items-center">
                                <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold shadow-xs">✓</span>
                                <span class="text-[10px] font-bold text-stone-800 mt-1.5">Menunggu</span>
                            </div>
                            <!-- Step 2: Direview -->
                            <div class="flex flex-col items-center">
                                <span class="w-5 h-5 rounded-full bg-amber-500 text-white flex items-center justify-center text-[10px] font-bold shadow-xs ring-4 ring-amber-100">●</span>
                                <span class="text-[10px] font-bold text-amber-700 mt-1.5">Direview</span>
                            </div>
                            <!-- Step 3: Disetujui -->
                            <div class="flex flex-col items-center">
                                <span class="w-5 h-5 rounded-full bg-stone-300 text-stone-600 flex items-center justify-center text-[10px] font-bold">3</span>
                                <span class="text-[10px] font-medium text-stone-400 mt-1.5">Disetujui</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WIDGET 2: GALERI KEGIATAN HIGHLIGHT (Matching Figma Photo Banner) -->
            <div class="rounded-3xl overflow-hidden border border-stone-200/90 shadow-2xs relative group cursor-pointer h-56 bg-slate-800">
                <img 
                    src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=600&q=80" 
                    alt="Lomba 17-an Masjid Nur Iqlab" 
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-80"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent p-5 flex flex-col justify-end text-white">
                    <span class="text-[10px] font-bold tracking-wider uppercase text-emerald-300">Dokumentasi Warga</span>
                    <h4 class="font-bold text-base leading-tight mt-0.5 text-white">Kerja Bakti & Lomba Warga RT 05</h4>
                    <p class="text-[11px] text-slate-300 mt-1">Foto Kegiatan Terbaru Komunitas</p>
                </div>
            </div>

            <!-- WIDGET 3: KAS RT — BULAN INI (Matching Figma Cash Summary) -->
            <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-bold text-stone-600">Kas RT — {{ now()->translatedFormat('F Y') }}</h3>
                    <a href="#anggaran" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Laporan</a>
                </div>

                <div class="mt-1">
                    <span class="text-3xl font-extrabold text-stone-900 tracking-tight block">
                        Rp {{ number_format($stats['saldo_kas'], 0, ',', '.') }}
                    </span>
                    <span class="text-xs text-stone-400 font-medium block mt-0.5">Saldo kas RT saat ini</span>
                </div>

                <!-- Mini Bar Graphic Representation -->
                <div class="mt-5 pt-4 border-t border-stone-100 flex items-end justify-between h-20 gap-2 px-1">
                    <div class="w-full flex flex-col items-center gap-1.5">
                        <div class="w-full bg-stone-200 rounded-t-md h-9"></div>
                        <span class="text-[9px] text-stone-400 font-medium">Apr</span>
                    </div>
                    <div class="w-full flex flex-col items-center gap-1.5">
                        <div class="w-full bg-emerald-700 rounded-t-md h-16"></div>
                        <span class="text-[9px] text-stone-400 font-medium">Mei</span>
                    </div>
                    <div class="w-full flex flex-col items-center gap-1.5">
                        <div class="w-full bg-stone-200 rounded-t-md h-11"></div>
                        <span class="text-[9px] text-stone-400 font-medium">Jun</span>
                    </div>
                    <div class="w-full flex flex-col items-center gap-1.5">
                        <div class="w-full bg-emerald-700 rounded-t-md h-14"></div>
                        <span class="text-[9px] text-stone-400 font-medium">Jul</span>
                    </div>
                    <div class="w-full flex flex-col items-center gap-1.5">
                        <div class="w-full bg-stone-200 rounded-t-md h-8"></div>
                        <span class="text-[9px] text-stone-400 font-medium">Agt</span>
                    </div>
                    <div class="w-full flex flex-col items-center gap-1.5">
                        <div class="w-full bg-emerald-600 rounded-t-md h-15 shadow-xs"></div>
                        <span class="text-[9px] text-emerald-800 font-bold">Sep</span>
                    </div>
                </div>

                <!-- Status Iuran Warga Pill -->
                <div class="mt-5 pt-3 border-t border-stone-100 flex items-center justify-between">
                    <div class="inline-flex items-center gap-1.5 bg-[#EAF5EC] text-[#246A3E] px-3 py-1 rounded-full text-xs font-semibold border border-[#CCE8D4]">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Iuran Anda: Lunas</span>
                    </div>
                    <span class="text-[11px] text-stone-400">Otomatis</span>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
