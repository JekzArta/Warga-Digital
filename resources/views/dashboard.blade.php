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
        <a href="{{ route('surat.index') }}" class="bg-white rounded-2xl p-5 border border-stone-200/90 hover:border-emerald-600/40 hover:shadow-md transition-all duration-200 group flex flex-col justify-between">
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
        <a href="{{ route('kas.index') }}" class="bg-gradient-to-br from-[#B55239] to-[#963F28] text-white rounded-2xl p-5 hover:shadow-md transition-all duration-200 group flex flex-col justify-between shadow-xs">
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
        <a href="{{ route('umkm.index') }}" class="bg-white rounded-2xl p-5 border border-stone-200/90 hover:border-emerald-600/40 hover:shadow-md transition-all duration-200 group flex flex-col justify-between">
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

    <!-- 3. REKOMENDASI UMKM DI SEKITAR ANDA (Real Database Integration) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-extrabold text-stone-900 tracking-tight">Rekomendasi UMKM di Sekitar Anda</h2>
            <a href="{{ route('umkm.index') }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                <span>Lihat Semua</span>
                <span>&rarr;</span>
            </a>
        </div>

        @if($umkmList->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($umkmList as $item)
                    <div class="bg-white rounded-2xl border border-stone-200/90 overflow-hidden shadow-2xs hover:shadow-md hover:border-emerald-600/30 transition-all duration-200 group flex flex-col justify-between">
                        <!-- Foto atau Fallback Placeholder -->
                        <div class="h-44 bg-stone-100 relative overflow-hidden flex items-center justify-center">
                            @if($item->foto_url)
                                <img 
                                    src="{{ Storage::url($item->foto_url) }}" 
                                    alt="{{ $item->nama }}" 
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                >
                            @else
                                <div class="flex flex-col items-center justify-center text-stone-400 p-4 text-center">
                                    @if($item->kategori === 'barang')
                                        <svg class="w-10 h-10 mb-1 text-amber-500/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    @else
                                        <svg class="w-10 h-10 mb-1 text-emerald-600/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                    <span class="text-[11px] font-medium text-stone-400">Belum ada foto</span>
                                </div>
                            @endif

                            <!-- Badge Kategori -->
                            <div class="absolute top-2.5 left-2.5">
                                @if($item->kategori === 'barang')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500 text-white shadow-xs">
                                        Barang
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-700 text-white shadow-xs">
                                        Jasa
                                    </span>
                                @endif
                            </div>

                            <!-- Badge RT Wilayah -->
                            <span class="absolute bottom-2.5 left-2.5 bg-black/60 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md">
                                RT 0{{ $item->rt?->nomor_rt ?? ($user->rt?->nomor_rt ?? '5') }}
                            </span>
                        </div>

                        <!-- Info Card -->
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-stone-900 text-sm truncate group-hover:text-emerald-700 transition-colors" title="{{ $item->nama }}">
                                    {{ $item->nama }}
                                </h4>
                                <p class="text-xs text-stone-500 mt-1 flex items-center gap-1.5 truncate">
                                    <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>oleh <strong class="text-stone-700 font-semibold">{{ $item->user?->nama ?? 'Warga RT' }}</strong></span>
                                </p>
                                <p class="text-xs text-stone-500 mt-1 line-clamp-2 leading-relaxed">
                                    {{ $item->deskripsi }}
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
                                <div class="flex items-baseline">
                                    <span class="text-sm font-extrabold text-stone-900">
                                        {{ $item->formatted_harga }}
                                    </span>
                                </div>
                                @if($item->whatsapp_link)
                                    <a href="{{ $item->whatsapp_link }}" target="_blank" rel="noopener noreferrer" class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs transition-colors flex items-center gap-1 shadow-2xs shrink-0">
                                        <span>Pesan WA</span>
                                    </a>
                                @else
                                    <a href="{{ route('umkm.index') }}" class="px-3 py-1.5 rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 font-semibold text-xs transition-colors flex items-center gap-1 shadow-2xs shrink-0">
                                        <span>Lihat Detail</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State Rekomendasi UMKM -->
            <div class="bg-white rounded-2xl border border-stone-200/90 p-8 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-stone-800">Belum ada usaha warga yang tayang</h3>
                <p class="text-xs text-stone-500 max-w-md mx-auto mt-1 leading-relaxed">
                    Dukung ekonomi tetangga atau jadilah yang pertama mempromosikan produk dan jasa Anda di lingkungan RT.
                </p>
                <div class="mt-4">
                    <a href="{{ route('umkm.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs transition-colors shadow-xs">
                        <span>Lihat Katalog UMKM</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- 4. 2-COLUMN MAIN CONTENT (Left 60% : Right 40%) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN (Kalender, Pengumuman, Aktivitas) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- WIDGET 1: KALENDER EVENT (Real Data Integration) -->
            <div class="bg-white rounded-3xl p-6 border border-stone-200/90 shadow-2xs">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-stone-900 leading-tight">Kalender Event</h3>
                            <p class="text-[11px] text-stone-400 font-medium">{{ $dashboardCurrentMonth->translatedFormat('F Y') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('kalender.index') }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 flex items-center gap-1 transition-colors">
                        <span>Buka Kalender</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <!-- 7-Days Calendar Grid -->
                <div class="grid grid-cols-7 gap-1 text-center text-xs mb-4">
                    <span class="text-stone-400 font-semibold py-1">Min</span>
                    <span class="text-stone-400 font-semibold py-1">Sen</span>
                    <span class="text-stone-400 font-semibold py-1">Sel</span>
                    <span class="text-stone-400 font-semibold py-1">Rab</span>
                    <span class="text-stone-400 font-semibold py-1">Kam</span>
                    <span class="text-stone-400 font-semibold py-1">Jum</span>
                    <span class="text-stone-400 font-semibold py-1">Sab</span>

                    {{-- Empty offset cells --}}
                    @for($i = 0; $i < $dashboardFirstDayOfWeek; $i++)
                        <span class="py-1.5 text-stone-200"></span>
                    @endfor

                    {{-- Days of current month --}}
                    @for($day = 1; $day <= $dashboardDaysInMonth; $day++)
                        @php
                            $dateStr = sprintf('%04d-%02d-%02d', $dashboardCurrentMonth->year, $dashboardCurrentMonth->month, $day);
                            $isToday = ($dateStr === now()->format('Y-m-d'));
                            $dayEvents = $dashboardEventsByDate->get($dateStr, collect());
                            $hasEvents = $dayEvents->isNotEmpty();
                        @endphp

                        <a href="{{ route('kalender.index', ['year' => $dashboardCurrentMonth->year, 'month' => $dashboardCurrentMonth->month]) }}" 
                           title="{{ $day }} {{ $dashboardCurrentMonth->translatedFormat('F Y') }}{{ $hasEvents ? ' (' . $dayEvents->count() . ' agenda)' : '' }}"
                           class="py-1.5 relative rounded-lg transition-all text-xs block {{ $isToday ? 'bg-[#131919] text-white font-bold shadow-xs' : ($hasEvents ? 'text-emerald-800 font-bold hover:bg-stone-100' : 'text-stone-700 font-medium hover:bg-stone-100') }}">
                            <span>{{ $day }}</span>
                            @if($hasEvents)
                                <span class="w-1 h-1 rounded-full {{ $isToday ? 'bg-emerald-400' : 'bg-emerald-500' }} absolute bottom-0.5 left-1/2 -translate-x-1/2"></span>
                            @endif
                        </a>
                    @endfor
                </div>

                <!-- Event Legend (Standardized 4 Categories) -->
                <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-stone-100 text-[11px] text-stone-500">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Kegiatan Warga</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Rapat</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>Posyandu</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                        <span>Lainnya</span>
                    </span>
                </div>

                <!-- Event Schedule List Items -->
                <div class="mt-4 space-y-2.5">
                    @forelse($upcomingAgenda as $event)
                        <a href="{{ route('kalender.index') }}" class="p-3 rounded-2xl bg-stone-50 border border-stone-200/80 hover:border-emerald-600/40 hover:bg-stone-50/80 transition-all flex items-center justify-between gap-3 group">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 rounded-xl bg-white border border-stone-200 flex flex-col items-center justify-center shrink-0 shadow-2xs group-hover:border-emerald-500/40 transition-colors">
                                    <span class="text-base font-extrabold text-stone-900 leading-none">{{ $event->tanggal->format('d') }}</span>
                                    <span class="text-[9px] font-bold text-stone-500 uppercase mt-0.5">{{ $event->tanggal->translatedFormat('M') }}</span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-stone-900 text-xs sm:text-sm truncate group-hover:text-emerald-700 transition-colors">{{ $event->judul }}</h4>
                                    <p class="text-[11px] text-stone-500 mt-0.5 truncate flex items-center gap-1.5">
                                        <span>{{ $event->formatted_waktu }}</span>
                                        @if($event->lokasi)
                                            <span>•</span>
                                            <span class="truncate">📍 {{ $event->lokasi }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $event->kategori_badge_class }}">
                                    {{ $event->kategori_label }}
                                </span>
                                <span class="text-[9px] font-semibold text-stone-400">
                                    {{ $event->source_badge_label }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="p-6 rounded-2xl bg-stone-50/80 border border-stone-200/60 text-center space-y-1.5">
                            <div class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 flex items-center justify-center mx-auto mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-bold text-stone-700">Belum ada agenda mendatang</p>
                            <p class="text-[11px] text-stone-400">Jadwal kegiatan RT dan RW akan ditampilkan di sini.</p>
                        </div>
                    @endforelse
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

            <!-- WIDGET 2: GALERI KEGIATAN HIGHLIGHT (Dinamis dari Database) -->
            @if($latestAlbum)
                <div class="rounded-3xl overflow-hidden border border-stone-200/90 shadow-2xs relative group cursor-pointer h-56 bg-slate-900 flex flex-col justify-end">
                    @if($latestAlbum->coverFoto && $latestAlbum->coverFoto->foto_url)
                        <img 
                            src="{{ Storage::url($latestAlbum->coverFoto->foto_url) }}" 
                            alt="{{ $latestAlbum->judul }}" 
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-80"
                        >
                    @else
                        <div class="absolute inset-0 bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center">
                            <svg class="w-12 h-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent p-5 flex flex-col justify-between text-white pointer-events-none">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-extrabold tracking-wider uppercase text-emerald-300 bg-black/40 backdrop-blur-xs px-2.5 py-1 rounded-full border border-white/10">
                                Dokumentasi Terbaru
                            </span>
                            <span class="text-[11px] font-bold text-white/90 bg-black/40 backdrop-blur-xs px-2.5 py-1 rounded-full border border-white/10">
                                {{ $latestAlbum->fotos_count }} Foto
                            </span>
                        </div>

                        <div>
                            <h4 class="font-bold text-base leading-tight text-white group-hover:text-emerald-300 transition-colors line-clamp-2">
                                {{ $latestAlbum->judul }}
                            </h4>
                            <p class="text-[11px] text-slate-300 mt-1 flex items-center gap-1.5">
                                <span>{{ $latestAlbum->tanggal_kegiatan ? $latestAlbum->tanggal_kegiatan->translatedFormat('d F Y') : '-' }}</span>
                                @if($latestAlbum->rt)
                                    <span>• RT 0{{ $latestAlbum->rt->nomor_rt }}</span>
                                @endif
                            </p>
                            <div class="mt-2.5 flex items-center text-xs font-bold text-emerald-300 group-hover:translate-x-1 transition-transform">
                                <span>Lihat Galeri &rarr;</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('galeri.show', $latestAlbum->id) }}" class="absolute inset-0 z-10" aria-label="Buka album {{ $latestAlbum->judul }}"></a>
                </div>
            @else
                <div class="rounded-3xl border border-stone-200/90 bg-white p-5 shadow-2xs text-center flex flex-col items-center justify-center h-56">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-stone-800">Dokumentasi Warga</h4>
                    <p class="text-xs text-stone-500 mt-1 max-w-xs leading-relaxed">
                        Belum ada album kegiatan yang dipublikasikan.
                    </p>
                    <a href="{{ route('galeri.index') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                        <span>Buka Galeri</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            @endif

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
