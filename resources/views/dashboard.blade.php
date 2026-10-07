@extends('layouts.app')

@section('content')
<div class="space-y-6 sm:space-y-8">

    <!-- 1. TOP GREETING HERO BANNER (Warm Deep Evergreen with Ambient Glow) -->
    <div class="bg-gradient-to-br from-[#10231e] via-[#142b25] to-[#1a3830] rounded-3xl p-6 sm:p-8 text-white border border-white/10 shadow-md relative overflow-hidden">
        <!-- Ambient Warm Gold Glow -->
        <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-[#e5a53f]/15 blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 relative z-10">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-[#fde68a] text-[11px] font-bold tracking-wider uppercase mb-2 border border-white/10">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#e5a53f]"></span>
                    <span>{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-2">
                    <span>{{ $greeting }}, {{ explode(' ', $user->nama)[0] }}!</span>
                </h1>
                <p class="text-stone-300 text-xs sm:text-sm mt-1 font-medium">
                    Selamat datang di portal pelayanan mandiri dan ruang kebersamaan warga.
                </p>
            </div>

            <!-- Right Status Badges (Civil & Tenant Context, Zero NIK Leak) -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Tenant Scope Pill -->
                <div class="bg-white/10 text-white/90 px-3.5 py-1.5 rounded-full text-xs font-bold border border-white/15 flex items-center gap-2 shadow-inner backdrop-blur-xs">
                    <svg class="w-3.5 h-3.5 text-[#e5a53f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>
                        @if($user->is_super_admin)
                            Super Administrator
                        @elseif($user->rt)
                            RT 0{{ $user->rt->nomor_rt }} • RW 0{{ $user->rt->rw->nomor_rw }}
                        @elseif($user->rw)
                            RW 0{{ $user->rw->nomor_rw }}
                        @else
                            Warga Terdaftar
                        @endif
                    </span>
                </div>

                <!-- Status Akun Pill -->
                <div class="bg-[#1f332b] text-[#52d28a] px-3.5 py-1.5 rounded-full text-xs font-bold border border-[#2d4e3e] flex items-center gap-2 shadow-inner">
                    <span class="w-2 h-2 rounded-full bg-[#52d28a] animate-pulse"></span>
                    <span>Akun Aktif</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. QUICK ACTIONS (Harmonious Bento Grid with Clear Primary Action) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Ajukan Surat (Primary Civic Action) -->
        <a href="{{ route('surat.index') }}" class="bg-white rounded-3xl p-5 sm:p-6 border border-[#e4ded1] hover:border-[#10231e]/30 shadow-2xs hover:shadow-md transition-all duration-200 active:scale-[0.98] group flex flex-col justify-between cursor-pointer relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 bg-[#e5a53f]"></div>
            <div>
                <div class="w-10 h-10 rounded-2xl bg-[#10231e] text-[#e5a53f] flex items-center justify-center mb-4 group-hover:scale-105 transition-transform shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-extrabold text-[#111827] group-hover:text-[#10231e] transition-colors">Ajukan Surat</h3>
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-[#e5a53f]/20 text-[#b45309]">Utama</span>
                </div>
                <p class="text-xs text-stone-500 mt-1 leading-relaxed">6 jenis surat resmi, unduh PDF otomatis</p>
            </div>
            <div class="mt-4 pt-3 border-t border-[#e4ded1]/60 flex items-center justify-between text-xs font-bold text-[#10231e]">
                <span>Ajukan</span>
                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>

        <!-- Card 2: Forum Warga -->
        <a href="{{ route('komunitas.index', ['tab' => 'forum']) }}" class="bg-white rounded-3xl p-5 sm:p-6 border border-[#e4ded1] hover:border-[#10231e]/30 shadow-2xs hover:shadow-md transition-all duration-200 active:scale-[0.98] group flex flex-col justify-between cursor-pointer">
            <div>
                <div class="w-10 h-10 rounded-2xl bg-[#faf7f0] border border-[#e4ded1] text-[#10231e] flex items-center justify-center mb-4 group-hover:scale-105 transition-transform shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-base font-extrabold text-[#111827] group-hover:text-[#10231e] transition-colors">Forum Warga</h3>
                <p class="text-xs text-stone-500 mt-1 leading-relaxed">Musyawarah & diskusi lingkungan RT</p>
            </div>
            <div class="mt-4 pt-3 border-t border-[#e4ded1]/60 flex items-center justify-between text-xs font-bold text-[#10231e]">
                <span>Buka Forum</span>
                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>

        <!-- Card 3: Transparansi Kas -->
        <a href="{{ route('kas.index') }}" class="bg-white rounded-3xl p-5 sm:p-6 border border-[#e4ded1] hover:border-[#10231e]/30 shadow-2xs hover:shadow-md transition-all duration-200 active:scale-[0.98] group flex flex-col justify-between cursor-pointer">
            <div>
                <div class="w-10 h-10 rounded-2xl bg-[#ecfdf5] border border-[#a7f3d0] text-emerald-800 flex items-center justify-center mb-4 group-hover:scale-105 transition-transform shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <h3 class="text-base font-extrabold text-[#111827] group-hover:text-[#10231e] transition-colors">Transparansi Kas</h3>
                <p class="text-xs text-stone-500 mt-1 leading-relaxed">Pantau arus kas RT terbuka & akuntabel</p>
            </div>
            <div class="mt-4 pt-3 border-t border-[#e4ded1]/60 flex items-center justify-between text-xs font-bold text-emerald-800">
                <span>Lihat Kas</span>
                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>

        <!-- Card 4: Belanja UMKM -->
        <a href="{{ route('umkm.index') }}" class="bg-white rounded-3xl p-5 sm:p-6 border border-[#e4ded1] hover:border-[#10231e]/30 shadow-2xs hover:shadow-md transition-all duration-200 active:scale-[0.98] group flex flex-col justify-between cursor-pointer">
            <div>
                <div class="w-10 h-10 rounded-2xl bg-[#fefce8] border border-[#fef08a] text-amber-700 flex items-center justify-center mb-4 group-hover:scale-105 transition-transform shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="text-base font-extrabold text-[#111827] group-hover:text-[#10231e] transition-colors">Belanja UMKM</h3>
                <p class="text-xs text-stone-500 mt-1 leading-relaxed">Dukung produk & jasa warga sekitar</p>
            </div>
            <div class="mt-4 pt-3 border-t border-[#e4ded1]/60 flex items-center justify-between text-xs font-bold text-amber-800">
                <span>Belanja</span>
                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>
    </div>

    <!-- 3. REKOMENDASI UMKM DI SEKITAR ANDA (Real Database Integration) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-extrabold text-[#111827] tracking-tight">Rekomendasi UMKM di Sekitar Anda</h2>
                <p class="text-xs text-stone-500 mt-0.5">Produk dan jasa unggulan dari warga untuk warga lingkungan setempat</p>
            </div>
            <a href="{{ route('umkm.index') }}" class="text-xs font-bold text-[#10231e] hover:text-[#e5a53f] flex items-center gap-1 transition-colors">
                <span>Lihat Semua</span>
                <span>&rarr;</span>
            </a>
        </div>

        @if($umkmList->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($umkmList as $item)
                    <div class="bg-white rounded-3xl border border-[#e4ded1] overflow-hidden shadow-2xs hover:shadow-md hover:border-[#10231e]/30 transition-all duration-200 group flex flex-col justify-between">
                        <!-- Foto atau Fallback Placeholder -->
                        <div class="h-44 bg-[#faf7f0] relative overflow-hidden flex items-center justify-center">
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
                            <div class="absolute top-3 left-3">
                                @if($item->kategori === 'barang')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#e5a53f] text-[#10231e] shadow-xs">
                                        Barang
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#10231e] text-white shadow-xs">
                                        Jasa
                                    </span>
                                @endif
                            </div>

                            <!-- Badge RT Wilayah -->
                            <span class="absolute bottom-3 left-3 bg-[#10231e]/80 backdrop-blur-xs text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-white/10">
                                RT 0{{ $item->rt?->nomor_rt ?? ($user->rt?->nomor_rt ?? '5') }}
                            </span>
                        </div>

                        <!-- Info Card -->
                        <div class="p-5 flex-1 flex flex-col justify-between">
                            <div>
                                <h4 class="font-extrabold text-[#111827] text-sm truncate group-hover:text-[#10231e] transition-colors" title="{{ $item->nama }}">
                                    {{ $item->nama }}
                                </h4>
                                <p class="text-xs text-stone-500 mt-1 flex items-center gap-1.5 truncate">
                                    <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>oleh <strong class="text-stone-700 font-semibold">{{ $item->user?->nama ?? 'Warga RT' }}</strong></span>
                                </p>
                                <p class="text-xs text-stone-500 mt-2 line-clamp-2 leading-relaxed">
                                    {{ $item->deskripsi }}
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-[#e4ded1]/60 flex items-center justify-between gap-2">
                                <div class="flex items-baseline">
                                    <span class="text-sm font-extrabold text-[#111827]">
                                        {{ $item->formatted_harga }}
                                    </span>
                                </div>
                                @if($item->whatsapp_link)
                                    <a href="{{ $item->whatsapp_link }}" target="_blank" rel="noopener noreferrer" class="px-3.5 py-1.5 rounded-xl bg-[#10231e] hover:bg-[#1b342d] text-white font-bold text-xs transition-all flex items-center gap-1.5 shadow-2xs shrink-0 active:scale-95">
                                        <svg class="w-3.5 h-3.5 text-[#52d28a]" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.772.8 2.791.8 3.182 0 5.768-2.587 5.768-5.766.001-3.182-2.585-5.786-5.768-5.786zm3.385 8.169c-.14.394-.712.729-1.028.776-.316.046-.714.072-2.185-.536-1.503-.622-2.454-2.158-2.529-2.257-.074-.099-.607-.808-.607-1.543 0-.735.385-1.097.522-1.246.136-.149.298-.186.397-.186.099 0 .198 0 .284.005.093.004.218-.035.34.26.124.298.423 1.031.46 1.105.037.074.062.161.012.26-.049.099-.074.161-.148.248-.074.086-.157.193-.224.259-.074.075-.152.156-.065.305.087.149.387.638.83 1.033.57.508 1.05.666 1.199.74.148.074.235.062.322-.037.086-.099.372-.433.471-.582.099-.149.198-.124.334-.074.136.049.868.409 1.017.483.149.074.248.112.285.174.037.062.037.359-.103.753z"/>
                                        </svg>
                                        <span>Pesan WA</span>
                                    </a>
                                @else
                                    <a href="{{ route('umkm.index') }}" class="px-3.5 py-1.5 rounded-xl bg-[#faf7f0] hover:bg-[#e4ded1] text-[#111827] font-bold text-xs transition-colors flex items-center gap-1 shadow-2xs shrink-0">
                                        <span>Lihat Detail</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State Rekomendasi UMKM (Preserves Required Test Phrases) -->
            <div class="bg-white rounded-3xl border border-[#e4ded1] p-8 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-2xl bg-[#faf7f0] text-stone-400 flex items-center justify-center mx-auto mb-3 border border-[#e4ded1]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-[#111827]">Belum ada usaha warga yang tayang</h3>
                <p class="text-xs text-stone-500 max-w-md mx-auto mt-1 leading-relaxed">
                    Dukung ekonomi tetangga atau jadilah yang pertama mempromosikan produk dan jasa Anda di lingkungan RT.
                </p>
                <div class="mt-4">
                    <a href="{{ route('umkm.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#10231e] hover:bg-[#1b342d] text-white font-bold text-xs transition-all shadow-xs active:scale-95">
                        <span>Lihat Katalog UMKM</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- 4. 2-COLUMN MAIN CONTENT (Left 65% : Right 35%) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: Kalender Event & Pengumuman -->
        <div class="lg:col-span-8 space-y-6">

            <!-- WIDGET 1: KALENDER EVENT (Preserves Required Test Assertions) -->
            <div class="bg-white rounded-3xl p-6 border border-[#e4ded1] shadow-2xs">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-2xl bg-[#ecfdf5] border border-[#a7f3d0] text-emerald-800 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-[#111827] leading-tight">Kalender Event</h3>
                            <p class="text-xs text-stone-500 font-medium">{{ $dashboardCurrentMonth->translatedFormat('F Y') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('kalender.index') }}" class="text-xs font-bold text-[#10231e] hover:text-[#e5a53f] flex items-center gap-1 transition-colors">
                        <span>Buka Kalender</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <!-- 7-Days Calendar Grid -->
                <div class="grid grid-cols-7 gap-1 text-center text-xs mb-4">
                    <span class="text-stone-400 font-bold py-1">Min</span>
                    <span class="text-stone-400 font-bold py-1">Sen</span>
                    <span class="text-stone-400 font-bold py-1">Sel</span>
                    <span class="text-stone-400 font-bold py-1">Rab</span>
                    <span class="text-stone-400 font-bold py-1">Kam</span>
                    <span class="text-stone-400 font-bold py-1">Jum</span>
                    <span class="text-stone-400 font-bold py-1">Sab</span>

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
                           class="py-1.5 relative rounded-xl transition-all text-xs block {{ $isToday ? 'bg-[#10231e] text-white font-bold shadow-xs' : ($hasEvents ? 'text-emerald-800 font-bold hover:bg-[#faf7f0]' : 'text-stone-700 font-medium hover:bg-[#faf7f0]') }}">
                            <span>{{ $day }}</span>
                            @if($hasEvents)
                                <span class="w-1.5 h-1.5 rounded-full {{ $isToday ? 'bg-[#e5a53f]' : 'bg-emerald-600' }} absolute bottom-0.5 left-1/2 -translate-x-1/2"></span>
                            @endif
                        </a>
                    @endfor
                </div>

                <!-- Upcoming Agenda Cards -->
                <div class="mt-4 pt-3 border-t border-[#e4ded1]/60 space-y-2.5">
                    @forelse($upcomingAgenda as $event)
                        <a href="{{ route('kalender.index') }}" class="p-3.5 rounded-2xl bg-[#faf7f0] border border-[#e4ded1] hover:border-[#10231e]/30 hover:bg-white transition-all flex items-center justify-between gap-3 group">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 rounded-2xl bg-white border border-[#e4ded1] flex flex-col items-center justify-center shrink-0 shadow-2xs group-hover:border-[#10231e]/30 transition-colors">
                                    <span class="text-base font-extrabold text-[#111827] leading-none">{{ $event->tanggal->format('d') }}</span>
                                    <span class="text-[9px] font-bold text-stone-500 uppercase mt-0.5">{{ $event->tanggal->translatedFormat('M') }}</span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-extrabold text-[#111827] text-xs sm:text-sm truncate group-hover:text-[#10231e] transition-colors">{{ $event->judul }}</h4>
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
                                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full {{ $event->kategori_badge_class }}">
                                    {{ $event->kategori_label }}
                                </span>
                                <span class="text-[9px] font-bold text-stone-400">
                                    {{ $event->source_badge_label }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="p-6 rounded-2xl bg-[#faf7f0]/80 border border-[#e4ded1] text-center space-y-1.5">
                            <div class="w-8 h-8 rounded-full bg-white border border-[#e4ded1] text-stone-400 flex items-center justify-center mx-auto mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-bold text-[#111827]">Belum ada agenda mendatang</p>
                            <p class="text-[11px] text-stone-500">Jadwal kegiatan RT dan RW akan ditampilkan di sini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- WIDGET 2: PENGUMUMAN TERBARU -->
            <div class="bg-white rounded-3xl p-6 border border-[#e4ded1] shadow-2xs">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-extrabold text-[#111827]">Pengumuman Terbaru</h3>
                    <a href="{{ route('komunitas.index', ['tab' => 'pengumuman']) }}" class="text-xs font-bold text-[#10231e] hover:text-[#e5a53f] transition-colors">
                        Lihat Semua &rarr;
                    </a>
                </div>

                <div class="divide-y divide-[#e4ded1]/60">
                    @forelse($announcements as $ann)
                        <div class="py-3.5 first:pt-0 last:pb-0">
                            <div class="flex items-center gap-2 mb-1.5">
                                @if($ann->tipe === 'MENDESAK')
                                    <span class="text-[9px] font-extrabold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full">MENDESAK</span>
                                @elseif($ann->tipe === 'PENTING')
                                    <span class="text-[9px] font-extrabold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">PENTING</span>
                                @else
                                    <span class="text-[9px] font-extrabold text-sky-800 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded-full">INFO</span>
                                @endif
                                <span class="text-[11px] text-stone-400 font-medium">{{ $ann->created_at->diffForHumans() }}</span>
                                <span class="text-[11px] text-stone-400">• {{ $ann->scope_type === 'rw' ? 'Lingkup RW 0' . ($ann->rw?->nomor_rw ?? '3') : 'Lingkup RT 0' . ($ann->rt?->nomor_rt ?? '5') }}</span>
                            </div>
                            <a href="{{ route('komunitas.pengumuman.show', $ann->id) }}" class="font-extrabold text-[#111827] text-sm hover:text-[#10231e] transition-colors block">
                                {{ $ann->judul }}
                            </a>
                            <p class="text-xs text-stone-600 mt-1 leading-relaxed line-clamp-2">{{ $ann->konten }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-stone-400 py-4 text-center">Belum ada pengumuman terbaru.</p>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: Status Surat, Galeri Highlight, Kas RT -->
        <div class="lg:col-span-4 space-y-6">

            <!-- WIDGET 1: STATUS SURAT SAYA (Contextual Card, No Neighbor Leak) -->
            <div class="bg-white rounded-3xl p-6 border border-[#e4ded1] shadow-2xs">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-extrabold text-[#111827]">Status Surat Saya</h3>
                    <a href="{{ route('surat.index') }}" class="text-xs font-bold text-[#10231e] hover:text-[#e5a53f] transition-colors">Detail &rarr;</a>
                </div>

                @if($recentSurat)
                    <div class="bg-[#faf7f0] p-4 rounded-2xl border border-[#e4ded1]">
                        <span class="text-[10px] font-mono text-stone-500 block uppercase tracking-wider">
                            {{ $recentSurat->nomor_surat ?? 'Menunggu Penomoran' }}
                        </span>
                        <h4 class="font-extrabold text-[#111827] text-sm mt-0.5">
                            {{ \App\Services\SuratPdfGenerator::getNamaJenisSurat($recentSurat->jenis_surat) }}
                        </h4>

                        <!-- Status Badge Real Sesuai Database Enum -->
                        <div class="mt-3 flex items-center justify-between pt-3 border-t border-[#e4ded1]">
                            <span class="text-[11px] text-stone-500 font-medium">Status:</span>
                            @if($recentSurat->status === 'DISETUJUI')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#ecfdf5] text-emerald-800 border border-[#a7f3d0]">
                                    ✓ Disetujui
                                </span>
                            @elseif($recentSurat->status === 'DITOLAK')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                                    ✕ Ditolak
                                </span>
                            @elseif($recentSurat->status === 'PERLU_KELENGKAPAN')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-800 border border-amber-200">
                                    ⚠ Perlu Kelengkapan
                                </span>
                            @elseif($recentSurat->status === 'DIREVIEW')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-sky-50 text-sky-800 border border-sky-200">
                                    ● Sedang Direview
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-stone-100 text-stone-700 border border-stone-300">
                                    ⏳ Menunggu
                                </span>
                            @endif
                        </div>

                        <div class="mt-2 text-right">
                            <a href="{{ route('surat.show', $recentSurat->id) }}" class="text-[11px] font-bold text-[#10231e] hover:underline">
                                Buka Permohonan &rarr;
                            </a>
                        </div>
                    </div>
                @else
                    <!-- Empty State Aman (Tidak Bocor Surat Tetangga) -->
                    <div class="bg-[#faf7f0] p-5 rounded-2xl border border-[#e4ded1] text-center">
                        <div class="w-8 h-8 rounded-full bg-white border border-[#e4ded1] text-stone-400 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h4 class="text-xs font-bold text-[#111827]">Belum Ada Pengajuan Surat</h4>
                        <p class="text-[11px] text-stone-500 mt-1 leading-relaxed">
                            Butuh surat pengantar domisili, usaha, atau lainnya? Ajukan secara online kapan saja.
                        </p>
                        <a href="{{ route('surat.index') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-[#10231e] hover:text-[#e5a53f]">
                            <span>Ajukan Sekarang</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- WIDGET 2: GALERI KEGIATAN HIGHLIGHT (Preserves Required Test Assertions) -->
            @if($latestAlbum)
                <div class="rounded-3xl overflow-hidden border border-[#e4ded1] shadow-2xs relative group cursor-pointer h-56 bg-slate-900 flex flex-col justify-end">
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
                            <span class="text-[10px] font-extrabold tracking-wider uppercase text-[#fde68a] bg-black/40 backdrop-blur-xs px-2.5 py-1 rounded-full border border-white/10">
                                Dokumentasi Terbaru
                            </span>
                            <span class="text-[11px] font-bold text-white/90 bg-black/40 backdrop-blur-xs px-2.5 py-1 rounded-full border border-white/10">
                                {{ $latestAlbum->fotos_count }} Foto
                            </span>
                        </div>

                        <div>
                            <h4 class="font-extrabold text-base leading-tight text-white group-hover:text-[#fde68a] transition-colors line-clamp-2">
                                {{ $latestAlbum->judul }}
                            </h4>
                            <p class="text-[11px] text-slate-300 mt-1 flex items-center gap-1.5">
                                <span>{{ $latestAlbum->tanggal_kegiatan ? $latestAlbum->tanggal_kegiatan->translatedFormat('d F Y') : '-' }}</span>
                                @if($latestAlbum->rt)
                                    <span>• RT 0{{ $latestAlbum->rt->nomor_rt }}</span>
                                @endif
                            </p>
                            <div class="mt-2.5 flex items-center text-xs font-bold text-[#fde68a] group-hover:translate-x-1 transition-transform">
                                <span>Lihat Galeri &rarr;</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('galeri.show', $latestAlbum->id) }}" class="absolute inset-0 z-10" aria-label="Buka album {{ $latestAlbum->judul }}"></a>
                </div>
            @else
                <div class="rounded-3xl border border-[#e4ded1] bg-white p-5 shadow-2xs text-center flex flex-col items-center justify-center h-56">
                    <div class="w-10 h-10 rounded-2xl bg-[#faf7f0] border border-[#e4ded1] text-stone-600 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h4 class="text-sm font-extrabold text-[#111827]">Dokumentasi Warga</h4>
                    <p class="text-xs text-stone-500 mt-1 max-w-xs leading-relaxed">
                        Belum ada album kegiatan yang dipublikasikan.
                    </p>
                    <a href="{{ route('galeri.index') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-[#10231e] hover:text-[#e5a53f]">
                        <span>Buka Galeri</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            @endif

            <!-- WIDGET 3: KAS RT (Real Saldo + Akuntabilitas, Tanpa Mock Statis) -->
            <div class="bg-white rounded-3xl p-6 border border-[#e4ded1] shadow-2xs">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-stone-500 uppercase tracking-wider">Kas RT — {{ now()->translatedFormat('F Y') }}</h3>
                    <a href="{{ route('kas.index') }}" class="text-xs font-bold text-[#10231e] hover:text-[#e5a53f] transition-colors">Laporan &rarr;</a>
                </div>

                <div class="mt-2">
                    <span class="text-3xl font-extrabold text-[#111827] tracking-tight block">
                        Rp {{ number_format($stats['saldo_kas'], 0, ',', '.') }}
                    </span>
                    <span class="text-xs text-stone-500 font-medium block mt-1">Saldo kas RT aktif saat ini</span>
                </div>

                <div class="mt-4 pt-4 border-t border-[#e4ded1]/60">
                    <div class="p-3 rounded-2xl bg-[#faf7f0] border border-[#e4ded1] text-xs text-stone-600 leading-relaxed flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-emerald-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Pencatatan transparan & akuntabel, dapat dipantau oleh seluruh warga lingkungan RT.</span>
                    </div>

                    <div class="mt-3 text-right">
                        <a href="{{ route('kas.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-800 hover:underline">
                            <span>Buka Buku Kas & Laporan</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
