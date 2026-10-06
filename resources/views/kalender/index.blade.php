@extends('layouts.app', ['title' => 'Kalender & Agenda Warga — Warga Digital', 'pageTitle' => 'Kalender & Agenda'])

@section('content')
@php
    $todayStr = now()->format('Y-m-d');
@endphp

<div class="space-y-6 w-full" x-data="kalenderApp()">

    <!-- Top Header & Scope Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 font-bold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </span>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Kalender & Agenda Warga</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Jadwal kegiatan lingkungan, rapat warga, dan agenda penting RT & RW.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 self-start sm:self-auto">
            <!-- Scope Switcher (RT Saya vs RW) -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100/80 rounded-xl border border-slate-200/60">
                @if(auth()->user()->rt_id)
                <a href="{{ route('kalender.index', ['scope' => 'rt', 'year' => $year, 'month' => $month]) }}" 
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $requestedScope === 'rt' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                    RT Saya ({{ auth()->user()->rt?->kode_rt ?? 'RT' }})
                </a>
                @endif
                <a href="{{ route('kalender.index', ['scope' => 'rw', 'year' => $year, 'month' => $month]) }}" 
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $requestedScope === 'rw' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                    Kawasan RW ({{ auth()->user()->rw?->kode_rw ?? auth()->user()->rt?->rw?->kode_rw ?? 'RW' }})
                </a>
            </div>

            <!-- Tombol Tambah Agenda (Khusus Pengurus Berwenang) -->
            @if($canManage)
            <button 
                type="button" 
                @click="openCreateModal()"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Agenda</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-lg leading-none cursor-pointer">&times;</button>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm shadow-2xs">
        <div class="flex items-center gap-2 font-bold mb-1">
            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Terdapat kesalahan pengisian:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 ml-6">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Main Content: Dua Kolom (Month View & Agenda Terdekat) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start w-full">

        <!-- KOLOM KIRI: KALENDER BULANAN (Col 7) -->
        <div class="lg:col-span-7 bg-white rounded-3xl p-6 border border-slate-200/90 shadow-2xs space-y-5 w-full">
            
            <!-- Month Navigation Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">
                        {{ $currentMonth->translatedFormat('F Y') }}
                    </h2>
                    @if($year != now()->year || $month != now()->month)
                    <a href="{{ route('kalender.index', ['scope' => $requestedScope, 'year' => now()->year, 'month' => now()->month]) }}" 
                       aria-label="Kembali ke Bulan Ini"
                       class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-md transition-all">
                        Bulan Ini
                    </a>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('kalender.index', ['scope' => $requestedScope, 'year' => $prevMonthDate->year, 'month' => $prevMonthDate->month]) }}" 
                       aria-label="Bulan Sebelumnya"
                       class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-sm transition-all cursor-pointer">
                        &larr;
                    </a>
                    <a href="{{ route('kalender.index', ['scope' => $requestedScope, 'year' => $nextMonthDate->year, 'month' => $nextMonthDate->month]) }}" 
                       aria-label="Bulan Berikutnya"
                       class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-sm transition-all cursor-pointer">
                        &rarr;
                    </a>
                </div>
            </div>

            <!-- 7-Days Calendar Grid -->
            <div>
                <!-- Day Names Header -->
                <div class="grid grid-cols-7 gap-1 text-center text-xs font-bold text-slate-400 mb-2">
                    <span class="py-1">Min</span>
                    <span class="py-1">Sen</span>
                    <span class="py-1">Sel</span>
                    <span class="py-1">Rab</span>
                    <span class="py-1">Kam</span>
                    <span class="py-1">Jum</span>
                    <span class="py-1">Sab</span>
                </div>

                <!-- Day Cells Grid -->
                <div class="grid grid-cols-7 gap-1 sm:gap-1.5 text-center">
                    {{-- Kotak Kosong Sebelum Hari Pertama --}}
                    @for($i = 0; $i < $firstDayOfWeek; $i++)
                        <div class="h-10 sm:h-12 rounded-xl bg-slate-50/50 border border-transparent"></div>
                    @endfor

                    {{-- Tanggal dalam Bulan Berjalan --}}
                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $isToday = ($dateStr === $todayStr);
                            $dayEvents = $eventsByDate->get($dateStr, collect());
                            $hasEvents = $dayEvents->isNotEmpty();
                        @endphp

                        <button 
                            type="button"
                            @click="selectDate('{{ $dateStr }}')"
                            aria-label="{{ $day }} {{ $currentMonth->translatedFormat('F Y') }}{{ $hasEvents ? ' (' . $dayEvents->count() . ' agenda)' : '' }}"
                            :class="{
                                'ring-2 ring-emerald-500 shadow-md ring-offset-1 font-extrabold': {{ $isToday ? 'true' : 'false' }} && selectedDate === '{{ $dateStr }}',
                                'bg-[#131919] text-white font-bold shadow-xs': {{ $isToday ? 'true' : 'false' }},
                                'ring-2 ring-emerald-500 font-extrabold bg-emerald-50 text-emerald-900 shadow-2xs': !{{ $isToday ? 'true' : 'false' }} && selectedDate === '{{ $dateStr }}',
                                'text-slate-700 hover:bg-slate-100/80 font-medium': !{{ $isToday ? 'true' : 'false' }} && selectedDate !== '{{ $dateStr }}',
                            }"
                            class="min-h-[42px] h-10 sm:h-12 rounded-xl flex flex-col items-center justify-between p-1 sm:p-1.5 transition-all text-xs relative cursor-pointer group border border-slate-100">
                            
                            <span class="leading-none {{ $isToday ? 'text-white' : '' }}"
                                  :class="{
                                      'text-emerald-900 font-extrabold': !{{ $isToday ? 'true' : 'false' }} && selectedDate === '{{ $dateStr }}',
                                      'text-slate-800': !{{ $isToday ? 'true' : 'false' }} && selectedDate !== '{{ $dateStr }}'
                                  }">{{ $day }}</span>

                            <!-- Dot Indikator Agenda -->
                            <div class="flex items-center justify-center gap-1 w-full h-2">
                                @if($hasEvents)
                                    @foreach($dayEvents->take(3) as $ev)
                                        <span class="w-1.5 h-1.5 rounded-full {{ $ev->kategori_dot_class }}"></span>
                                    @endforeach
                                    @if($dayEvents->count() > 3)
                                        <span class="text-[8px] leading-none text-slate-400 font-bold">+</span>
                                    @endif
                                @endif
                            </div>
                        </button>
                    @endfor
                </div>
            </div>

            <!-- Event Legend (Sesuai Konvensi 4 Kategori Terstandarisasi) -->
            <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-slate-100 text-xs text-slate-600">
                <span class="font-bold text-slate-400 text-[11px] uppercase tracking-wider">Kategori:</span>
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
        </div>

        <!-- KOLOM KANAN: DAFTAR AGENDA TERDEKAT (Col 5) -->
        <div class="lg:col-span-5 space-y-4 w-full">
            
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 tracking-tight" x-text="selectedDateLabel">
                            Agenda Mendatang
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $requestedScope === 'rt' ? 'Lingkup RT ' . (auth()->user()->rt?->nomor_rt ?? '05') : 'Kawasan RW ' . (auth()->user()->rw?->nomor_rw ?? '03') }}
                        </p>
                    </div>

                    <template x-if="selectedDate">
                        <button 
                            type="button" 
                            @click="clearDateFilter()"
                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                            Semua Agenda
                        </button>
                    </template>
                </div>

                <!-- List Agenda Cards -->
                <div class="space-y-3">
                    @forelse($displayedEvents as $event)
                        @php
                            $isEventUpcoming = $event->tanggal->isFuture() || $event->tanggal->isToday();
                        @endphp
                        <div 
                            x-show="!selectedDate ? {{ $isEventUpcoming ? 'true' : 'false' }} : selectedDate === '{{ $event->tanggal->format('Y-m-d') }}'"
                            @if(!$isEventUpcoming) style="display: none;" @endif
                            class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 hover:border-slate-300 transition-all space-y-2.5">
                            
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <!-- Tanggal Box -->
                                    <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex flex-col items-center justify-center shrink-0 shadow-2xs">
                                        <span class="text-base font-extrabold text-slate-900 leading-none">{{ $event->tanggal->format('d') }}</span>
                                        <span class="text-[9px] font-bold text-slate-500 uppercase mt-0.5">{{ $event->tanggal->translatedFormat('M') }}</span>
                                    </div>

                                    <div>
                                        <h3 class="font-bold text-slate-900 text-sm leading-snug">
                                            {{ $event->judul }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px] text-slate-500">
                                            <span>{{ $event->formatted_waktu }}</span>
                                            @if($event->lokasi)
                                                <span>•</span>
                                                <span class="truncate max-w-[150px]">📍 {{ $event->lokasi }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Kategori Badge -->
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md shrink-0 {{ $event->kategori_badge_class }}">
                                    {{ $event->kategori_label }}
                                </span>
                            </div>

                            @if($event->deskripsi)
                                <p class="text-xs text-slate-600 line-clamp-2 pl-15">
                                    {{ $event->deskripsi }}
                                </p>
                            @endif

                            <div class="flex items-center justify-between pt-2 border-t border-slate-200/50 text-[11px]">
                                <!-- Source Badge -->
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200/70 text-slate-700">
                                        {{ $event->source_badge_label }}
                                    </span>
                                    @if($event->is_cancelled)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                            DIBATALKAN
                                        </span>
                                    @endif
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2">
                                    @if($event->is_linked_to_announcement && $event->announcement_id)
                                        <a href="{{ route('komunitas.pengumuman.show', $event->announcement_id) }}" 
                                           class="text-emerald-700 hover:text-emerald-800 font-bold hover:underline flex items-center gap-1">
                                            <span>Lihat Pengumuman</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    @elseif($canManage && $event->sumber === \App\Models\KalenderEvent::SUMBER_MANUAL)
                                        <button 
                                            type="button" 
                                            @click="openEditModal({{ json_encode($event) }})"
                                            class="text-slate-500 hover:text-emerald-700 font-medium cursor-pointer">
                                            Edit
                                        </button>
                                        <span class="text-slate-300">•</span>
                                        <button 
                                            type="button" 
                                            @click="openDeleteModal({{ json_encode($event) }})"
                                            class="text-slate-500 hover:text-rose-600 font-medium cursor-pointer">
                                            Hapus
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div x-show="!selectedDate" class="py-8 text-center space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-slate-700">Belum ada agenda mendatang</p>
                            <p class="text-[11px] text-slate-400 max-w-[220px] mx-auto">Semua agenda kegiatan lingkungan RT & RW akan tercatat secara rapi di sini.</p>
                        </div>
                    @endforelse

                    <!-- Empty State Khusus saat Tanggal Terpilih Tidak Memiliki Agenda -->
                    <div x-show="selectedDate && !activeDates.includes(selectedDate)" x-cloak style="display: none;" class="py-8 text-center space-y-2">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-700">Tidak ada agenda pada tanggal ini</p>
                        <p class="text-[11px] text-slate-400 max-w-[240px] mx-auto">
                            Tidak ada kegiatan yang dijadwalkan pada <span class="font-bold text-slate-600" x-text="selectedDateLabel.replace('Agenda Tanggal ', '')"></span>.
                        </p>
                        <div class="pt-2">
                            <button 
                                type="button" 
                                @click="clearDateFilter()" 
                                class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                                Tampilkan Semua Agenda
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL 1: TAMBAH AGENDA STANDALONE (CREATE) -->
    <div 
        x-show="showCreateModal" 
        x-cloak 
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        
        <div 
            @click.away="showCreateModal = false"
            class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5 transform transition-all max-h-[90vh] overflow-y-auto"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                        +
                    </span>
                    <h2 class="text-base font-bold text-slate-900">Tambah Agenda Kalender</h2>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
            </div>

            <form action="{{ route('kalender.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="scope_type" value="{{ $requestedScope }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Judul Agenda <span class="text-rose-500">*</span></label>
                    <input type="text" name="judul" required placeholder="Contoh: Kerja Bakti Lingkungan RT" value="{{ old('judul') }}"
                           class="w-full px-3.5 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                        <select name="kategori" required class="w-full px-3 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden bg-white">
                            <option value="KEGIATAN" {{ old('kategori') === 'KEGIATAN' ? 'selected' : '' }}>Kegiatan Warga</option>
                            <option value="RAPAT" {{ old('kategori') === 'RAPAT' ? 'selected' : '' }}>Rapat Warga</option>
                            <option value="POSYANDU" {{ old('kategori') === 'POSYANDU' ? 'selected' : '' }}>Posyandu</option>
                            <option value="LAINNYA" {{ old('kategori') === 'LAINNYA' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Kegiatan <span class="text-rose-500">*</span></label>
                        <input type="date" name="tanggal" required value="{{ old('tanggal', now()->toDateString()) }}"
                               class="w-full px-3 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Waktu Mulai (HH:MM)</label>
                        <input type="text" name="waktu_mulai" placeholder="08:00" value="{{ old('waktu_mulai') }}"
                               class="w-full px-3 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Waktu Selesai (HH:MM)</label>
                        <input type="text" name="waktu_selesai" placeholder="11:00" value="{{ old('waktu_selesai') }}"
                               class="w-full px-3 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lokasi Kegiatan</label>
                    <input type="text" name="lokasi" placeholder="Contoh: Balai Warga RT 05" value="{{ old('lokasi') }}"
                           class="w-full px-3.5 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Tambahan</label>
                    <textarea name="deskripsi" rows="2" placeholder="Keterangan singkat perlengkapan atau info penting..."
                              class="w-full px-3.5 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">{{ old('deskripsi') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all shadow-xs cursor-pointer">
                        Simpan Agenda
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDIT AGENDA STANDALONE (UPDATE) -->
    <div 
        x-show="showEditModal" 
        x-cloak 
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        
        <div 
            @click.away="showEditModal = false"
            class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5 transform transition-all max-h-[90vh] overflow-y-auto"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                        ✎
                    </span>
                    <h2 class="text-base font-bold text-slate-900">Edit Agenda Kalender</h2>
                </div>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
            </div>

            <form :action="editActionUrl" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Judul Agenda <span class="text-rose-500">*</span></label>
                    <input type="text" name="judul" required x-model="editEvent.judul"
                           class="w-full px-3.5 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                        <select name="kategori" required x-model="editEvent.kategori" class="w-full px-3 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden bg-white">
                            <option value="KEGIATAN">Kegiatan Warga</option>
                            <option value="RAPAT">Rapat Warga</option>
                            <option value="POSYANDU">Posyandu</option>
                            <option value="LAINNYA">Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Kegiatan <span class="text-rose-500">*</span></label>
                        <input type="date" name="tanggal" required x-model="editEvent.tanggal"
                               class="w-full px-3 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Waktu Mulai (HH:MM)</label>
                        <input type="text" name="waktu_mulai" placeholder="08:00" x-model="editEvent.waktu_mulai"
                               class="w-full px-3 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Waktu Selesai (HH:MM)</label>
                        <input type="text" name="waktu_selesai" placeholder="11:00" x-model="editEvent.waktu_selesai"
                               class="w-full px-3 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lokasi Kegiatan</label>
                    <input type="text" name="lokasi" x-model="editEvent.lokasi"
                           class="w-full px-3.5 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Tambahan</label>
                    <textarea name="deskripsi" rows="2" x-model="editEvent.deskripsi"
                              class="w-full px-3.5 py-2 rounded-xl text-xs sm:text-sm border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden"></textarea>
                </div>

                <!-- Pembatalan Eksplisit -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-800 cursor-pointer">
                        <input type="checkbox" name="is_cancelled" value="1" x-model="editEvent.is_cancelled"
                               class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                        <span>Tandai Agenda Ini Sebagai DIBATALKAN</span>
                    </label>
                    <div x-show="editEvent.is_cancelled" x-cloak>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Alasan Pembatalan</label>
                        <input type="text" name="pembatalan_alasan" x-model="editEvent.pembatalan_alasan" placeholder="Contoh: Ditunda karena cuaca buruk"
                               class="w-full px-3 py-1.5 rounded-lg text-xs border border-slate-200 focus:border-rose-500 outline-hidden">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all shadow-xs cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: HAPUS AGENDA (DELETE CONFIRMATION) -->
    <div 
        x-show="showDeleteModal" 
        x-cloak 
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        
        <div 
            @click.away="showDeleteModal = false"
            class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 text-center space-y-4 transform transition-all max-h-[90vh] overflow-y-auto"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100">
            
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 mx-auto flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <div>
                <h3 class="text-base font-bold text-slate-900">Hapus Agenda Kalender?</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Agenda "<span class="font-bold text-slate-800" x-text="deleteEvent.judul"></span>" akan dihapus secara permanen dari Kalender.
                </p>
            </div>

            <form :action="deleteActionUrl" method="POST" @submit="isSubmittingDelete = true" class="flex items-center justify-center gap-2 pt-2">
                @csrf
                @method('DELETE')

                <button type="button" @click="showDeleteModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-all cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        :disabled="isSubmittingDelete"
                        class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 transition-all shadow-xs cursor-pointer disabled:opacity-50">
                    <span x-text="isSubmittingDelete ? 'Menghapus...' : 'Ya, Hapus'"></span>
                </button>
            </form>
        </div>
    </div>

</div>

<script>
function kalenderApp() {
    return {
        selectedDate: null,
        selectedDateLabel: 'Agenda Mendatang',
        showCreateModal: false,
        showEditModal: false,
        showDeleteModal: false,
        isSubmittingDelete: false,
        activeDates: @json($eventsByDate->keys()),
        
        editEvent: {
            id: null,
            judul: '',
            kategori: 'KEGIATAN',
            tanggal: '',
            waktu_mulai: '',
            waktu_selesai: '',
            lokasi: '',
            deskripsi: '',
            is_cancelled: false,
            pembatalan_alasan: '',
        },
        editActionUrl: '',

        deleteEvent: {
            id: null,
            judul: '',
        },
        deleteActionUrl: '',

        selectDate(dateStr) {
            if (this.selectedDate === dateStr) {
                this.clearDateFilter();
                return;
            }
            this.selectedDate = dateStr;
            const parts = dateStr.split('-');
            this.selectedDateLabel = `Agenda Tanggal ${parts[2]}/${parts[1]}/${parts[0]}`;
        },

        clearDateFilter() {
            this.selectedDate = null;
            this.selectedDateLabel = 'Agenda Mendatang';
        },

        openCreateModal() {
            this.showCreateModal = true;
        },

        openEditModal(ev) {
            this.editEvent = {
                id: ev.id,
                judul: ev.judul,
                kategori: ev.kategori,
                tanggal: ev.tanggal ? (ev.tanggal.substring ? ev.tanggal.substring(0, 10) : ev.tanggal) : '',
                waktu_mulai: ev.waktu_mulai || '',
                waktu_selesai: ev.waktu_selesai || '',
                lokasi: ev.lokasi || '',
                deskripsi: ev.deskripsi || '',
                is_cancelled: Boolean(ev.is_cancelled),
                pembatalan_alasan: ev.pembatalan_alasan || '',
            };
            this.editActionUrl = '{{ url('/kalender') }}/' + ev.id;
            this.showEditModal = true;
        },

        openDeleteModal(ev) {
            this.deleteEvent = {
                id: ev.id,
                judul: ev.judul,
            };
            this.deleteActionUrl = '{{ url('/kalender') }}/' + ev.id;
            this.isSubmittingDelete = false;
            this.showDeleteModal = true;
        }
    };
}
</script>
@endsection
