@extends('layouts.app', ['title' => $album->judul . ' — Galeri Warga', 'pageTitle' => 'Galeri Kegiatan'])

@section('content')
@php
    $photoData = $fotos->map(function ($f) {
        return [
            'id' => $f->id,
            'url' => asset('storage/' . $f->foto_url),
            'uploader' => $f->uploader?->nama ?? 'Pengurus RT',
            'time' => $f->created_at ? $f->created_at->translatedFormat('d M Y, H:i') : '',
        ];
    })->values()->all();
@endphp

<div class="space-y-6 w-full" x-data="galeriShowApp(@js($photoData))">

    <!-- Top Navigation & Album Hero Header -->
    <div class="bg-white p-6 rounded-3xl shadow-xs border border-slate-200/90 space-y-4">
        
        <!-- Breadcrumb / Back Link -->
        <div class="flex items-center justify-between">
            <a href="{{ route('galeri.index') }}" 
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-emerald-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Daftar Album</span>
            </a>

            @if(auth()->user()->hasRole('ketua_rw') || auth()->user()->is_super_admin)
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                RT {{ $album->rt?->nomor_rt ?? $album->rt_id }} ({{ $album->rt?->nama ?? 'Sekeloa' }})
            </span>
            @endif
        </div>

        <!-- Album Title & Action Buttons -->
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 pt-1">
            <div class="space-y-2 max-w-2xl">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">
                    {{ $album->judul }}
                </h1>
                
                <!-- Metadata Badges -->
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 font-medium">
                    <span class="flex items-center gap-1.5 text-slate-700 font-semibold">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ \Carbon\Carbon::parse($album->tanggal_kegiatan)->translatedFormat('d F Y') }}</span>
                    </span>
                    <span>&bull;</span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Oleh {{ $album->creator?->nama ?? 'Pengurus RT' }}</span>
                    </span>
                    <span>&bull;</span>
                    <span class="flex items-center gap-1.5 text-emerald-700 font-bold">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ $fotos->total() }} Foto Tersimpan</span>
                    </span>
                </div>

                @if($album->deskripsi)
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed pt-1">
                    {{ $album->deskripsi }}
                </p>
                @endif
            </div>

            <!-- Action Controls (Hanya untuk Pengurus yang Sah) -->
            @if($canManage)
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto pt-2 md:pt-0">
                <button type="button" 
                        @click="openUploadModal()"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <span>Unggah Foto</span>
                </button>

                <button type="button" 
                        @click="openEditAlbumModal()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Info</span>
                </button>

                <button type="button" 
                        @click="openDeleteAlbumModal()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Hapus Album</span>
                </button>
            </div>
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
            <span>Terdapat kesalahan:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 ml-6">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Foto Grid / Galeri Dokumentasi -->
    @if($fotos->isNotEmpty())
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
        @foreach($fotos as $index => $foto)
        <div class="relative group aspect-square rounded-2xl overflow-hidden border border-slate-200/90 shadow-2xs bg-slate-100 cursor-pointer"
             @click="openLightbox({{ $index }})">
            
            <img src="{{ asset('storage/' . $foto->foto_url) }}" 
                 alt="Dokumentasi {{ $album->judul }}" 
                 loading="lazy" 
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

            <!-- Hover/Focus Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex flex-col justify-between p-3 text-white pointer-events-none">
                
                <!-- Top: Tombol Hapus Foto (Khusus Pengurus) -->
                <div class="flex justify-end pointer-events-auto">
                    @if($canManage)
                    <button type="button" 
                            @click.stop="openDeleteFotoModal({{ $foto->id }})"
                            class="w-8 h-8 rounded-xl bg-rose-600/90 hover:bg-rose-700 text-white flex items-center justify-center transition-all cursor-pointer shadow-xs"
                            title="Hapus foto ini"
                            aria-label="Hapus foto ini">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    @endif
                </div>

                <!-- Bottom: Keterangan Pengunggah & Perbesar -->
                <div class="flex items-center justify-between text-[11px] font-medium text-slate-200">
                    <span class="truncate">{{ $foto->uploader?->nama ?? 'Pengurus' }}</span>
                    <span class="inline-flex items-center gap-1 text-emerald-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                        </svg>
                        <span>Lihat</span>
                    </span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Paginasi Foto -->
    @if($fotos->hasPages())
    <div class="mt-6">
        {{ $fotos->links() }}
    </div>
    @endif

    @else
    <!-- Empty State (Ketika Album Masih Kosong / 0 Foto) -->
    <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/90 shadow-2xs">
        <div class="w-16 h-16 rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4 border border-slate-200/60">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 class="text-base font-bold text-slate-800">Belum Ada Foto Dokumentasi</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Album ini telah dibuat namun belum memiliki foto kegiatan. Pengurus dapat mengunggah foto dokumentasi.</p>
        
        @if($canManage)
        <button 
            type="button" 
            @click="openUploadModal()"
            class="mt-5 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition-all cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <span>Unggah Foto Pertama</span>
        </button>
        @endif
    </div>
    @endif

    <!-- ============================================== -->
    <!-- ALPINE.JS LIGHTBOX VIEWER                      -->
    <!-- ============================================== -->
    <div x-show="lightboxOpen" 
         x-cloak 
         @keydown.window.escape="closeLightbox()"
         @keydown.window.arrow-left="prevPhoto()"
         @keydown.window.arrow-right="nextPhoto()"
         class="fixed inset-0 z-50 bg-black/95 backdrop-blur-md flex flex-col justify-between p-4 sm:p-6 text-white select-none">
        
        <!-- Lightbox Top Bar -->
        <div class="flex items-center justify-between text-xs sm:text-sm font-semibold text-slate-300">
            <div>
                <span class="text-white font-bold" x-text="'Foto ' + (activeIndex + 1)"></span>
                <span>dari</span>
                <span x-text="photos.length"></span>
            </div>

            <button type="button" 
                    @click="closeLightbox()" 
                    class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center gap-1.5 transition-colors cursor-pointer text-xs"
                    aria-label="Tutup Lightbox (Escape)">
                <span>Tutup</span>
                <kbd class="text-[10px] bg-white/20 px-1.5 py-0.5 rounded">Esc</kbd>
            </button>
        </div>

        <!-- Lightbox Center Image Area -->
        <div class="relative flex-1 flex items-center justify-center my-4 overflow-hidden" @click.self="closeLightbox()">
            
            <!-- Tombol Navigasi Kiri (Prev) -->
            <button type="button" 
                    @click.stop="prevPhoto()"
                    x-show="photos.length > 1"
                    class="absolute left-2 sm:left-4 z-10 w-11 h-11 rounded-full bg-white/10 hover:bg-white/25 backdrop-blur-md text-white flex items-center justify-center transition-all cursor-pointer"
                    aria-label="Foto Sebelumnya (Panah Kiri)">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <!-- Gambar Utama Lightbox -->
            <template x-if="photos[activeIndex]">
                <img :src="photos[activeIndex].url" 
                     alt="Foto Dokumentasi Kegiatan" 
                     class="max-h-[75vh] max-w-[85vw] object-contain rounded-xl shadow-2xl transition-all duration-200">
            </template>

            <!-- Tombol Navigasi Kanan (Next) -->
            <button type="button" 
                    @click.stop="nextPhoto()"
                    x-show="photos.length > 1"
                    class="absolute right-2 sm:right-4 z-10 w-11 h-11 rounded-full bg-white/10 hover:bg-white/25 backdrop-blur-md text-white flex items-center justify-center transition-all cursor-pointer"
                    aria-label="Foto Selanjutnya (Panah Kanan)">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        <!-- Lightbox Bottom Caption Bar -->
        <div class="flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 border-t border-white/10 pt-3 gap-2">
            <div class="truncate font-medium text-slate-200">
                <span>{{ $album->judul }}</span>
            </div>
            <div class="flex items-center gap-3">
                <template x-if="photos[activeIndex]">
                    <div>
                        <span>Diunggah oleh: <strong class="text-white" x-text="photos[activeIndex].uploader"></strong></span>
                        <span class="mx-1">&bull;</span>
                        <span x-text="photos[activeIndex].time"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL 1: UPLOAD FOTO (MULTI-UPLOAD)            -->
    <!-- ============================================== -->
    @if($canManage)
    <div x-show="showUploadModal" 
         x-cloak 
         @keydown.escape.window="showUploadModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200" 
             @click.outside="showUploadModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Unggah Foto Dokumentasi</h3>
                <button type="button" @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>

            <form action="{{ route('galeri.foto.store', $album->id) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Pilih Berkas Foto <span class="text-rose-500">*</span>
                    </label>
                    
                    <div class="relative border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-6 text-center bg-slate-50 hover:bg-emerald-50/30 transition-all cursor-pointer">
                        <input type="file" 
                               id="foto_input"
                               name="fotos[]" 
                               multiple 
                               required
                               accept="image/jpeg,image/png,image/webp,image/jpg"
                               @change="handleFilesSelected($event)"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        
                        <div class="space-y-2 pointer-events-none">
                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-emerald-600 flex items-center justify-center mx-auto shadow-2xs">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-slate-700">Klik atau geser foto ke sini untuk memilih</p>
                            <p class="text-[11px] text-slate-500">Maksimal 10 foto per upload, batas ukuran 3 MB/file (JPG, PNG, WEBP)</p>
                        </div>
                    </div>
                </div>

                <!-- Preview Jumlah & Daftar File Terpilih -->
                <div x-show="selectedFiles.length > 0" class="p-3 bg-slate-100 rounded-xl text-xs space-y-1.5">
                    <div class="flex items-center justify-between font-bold text-slate-700">
                        <span>Foto Terpilih:</span>
                        <span :class="selectedFiles.length > 10 ? 'text-rose-600' : 'text-emerald-700'" 
                              x-text="selectedFiles.length + ' / 10 foto'"></span>
                    </div>

                    <div x-show="selectedFiles.length > 10" class="text-[11px] font-semibold text-rose-600">
                        Peringatan: Jumlah melebihi batas maksimal 10 foto! Mohon kurangi pilihan berkas.
                    </div>

                    <ul class="text-[11px] text-slate-600 space-y-0.5 max-h-24 overflow-y-auto list-disc list-inside">
                        <template x-for="(fName, i) in selectedFiles" :key="i">
                            <li class="truncate" x-text="fName"></li>
                        </template>
                    </ul>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="showUploadModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            :disabled="selectedFiles.length === 0 || selectedFiles.length > 10"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-semibold shadow-xs cursor-pointer">
                        Unggah Foto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL 2: HAPUS FOTO INDIVIDUAL                 -->
    <!-- ============================================== -->
    <div x-show="showDeleteFotoModal" 
         x-cloak 
         @keydown.escape.window="showDeleteFotoModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200" 
             @click.outside="showDeleteFotoModal = false">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 border border-rose-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <h3 class="text-base font-bold text-slate-900 text-center">Hapus Foto Ini?</h3>
            <p class="text-xs text-slate-500 text-center mt-1">
                Foto akan dihapus dari album dan berkas fisik akan dibersihkan dari penyimpanan.
            </p>

            <form :action="'{{ url('galeri/' . $album->id . '/fotos') }}/' + deleteFotoId" method="POST" class="mt-5 flex items-center justify-center gap-2">
                @csrf
                @method('DELETE')
                <button type="button" @click="showDeleteFotoModal = false" class="w-full px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="w-full px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs cursor-pointer">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL 3: EDIT INFO ALBUM DARI SHOW PAGE        -->
    <!-- ============================================== -->
    <div x-show="showEditAlbumModal" 
         x-cloak 
         @keydown.escape.window="showEditAlbumModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" 
             @click.outside="showEditAlbumModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Edit Info Album Kegiatan</h3>
                <button type="button" @click="showEditAlbumModal = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>

            <form action="{{ route('galeri.album.update', $album->id) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="edit_judul_show" class="block text-xs font-bold text-slate-700 mb-1">Judul Album Kegiatan <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           id="edit_judul_show" 
                           name="judul" 
                           required 
                           maxlength="150"
                           value="{{ $album->judul }}"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label for="edit_tanggal_show" class="block text-xs font-bold text-slate-700 mb-1">Tanggal Pelaksanaan Kegiatan <span class="text-rose-500">*</span></label>
                    <input type="date" 
                           id="edit_tanggal_show" 
                           name="tanggal_kegiatan" 
                           required 
                           value="{{ $album->tanggal_kegiatan ? $album->tanggal_kegiatan->format('Y-m-d') : date('Y-m-d') }}"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label for="edit_deskripsi_show" class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Kegiatan</label>
                    <textarea id="edit_deskripsi_show" 
                              name="deskripsi" 
                              rows="3" 
                              maxlength="1000"
                              class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20">{{ $album->deskripsi }}</textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="showEditAlbumModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs cursor-pointer">
                        Perbarui Info
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL 4: HAPUS ALBUM DARI SHOW PAGE            -->
    <!-- ============================================== -->
    <div x-show="showDeleteAlbumModal" 
         x-cloak 
         @keydown.escape.window="showDeleteAlbumModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200" 
             @click.outside="showDeleteAlbumModal = false">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 border border-rose-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <h3 class="text-base font-bold text-slate-900 text-center">Hapus Album Kegiatan?</h3>
            <p class="text-xs text-slate-500 text-center mt-1">
                Album <strong>{{ $album->judul }}</strong> beserta seluruh foto di dalamnya ({{ $fotos->total() }} foto) akan dihapus secara permanen.
            </p>

            <form action="{{ route('galeri.album.destroy', $album->id) }}" method="POST" class="mt-5 flex items-center justify-center gap-2">
                @csrf
                @method('DELETE')
                <button type="button" @click="showDeleteAlbumModal = false" class="w-full px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="w-full px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs cursor-pointer">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>
    @endif

</div>

<script>
function galeriShowApp(photoData) {
    return {
        photos: photoData || [],
        lightboxOpen: false,
        activeIndex: 0,
        showUploadModal: false,
        showDeleteFotoModal: false,
        showEditAlbumModal: false,
        showDeleteAlbumModal: false,
        deleteFotoId: null,
        selectedFiles: [],

        openLightbox(index) {
            if (this.photos.length === 0) return;
            this.activeIndex = index >= 0 && index < this.photos.length ? index : 0;
            this.lightboxOpen = true;
        },
        closeLightbox() {
            this.lightboxOpen = false;
        },
        nextPhoto() {
            if (this.photos.length <= 1) return;
            this.activeIndex = (this.activeIndex + 1) % this.photos.length;
        },
        prevPhoto() {
            if (this.photos.length <= 1) return;
            this.activeIndex = (this.activeIndex - 1 + this.photos.length) % this.photos.length;
        },
        openUploadModal() {
            this.selectedFiles = [];
            this.showUploadModal = true;
        },
        openDeleteFotoModal(fotoId) {
            this.deleteFotoId = fotoId;
            this.showDeleteFotoModal = true;
        },
        openEditAlbumModal() {
            this.showEditAlbumModal = true;
        },
        openDeleteAlbumModal() {
            this.showDeleteAlbumModal = true;
        },
        handleFilesSelected(event) {
            const files = event.target.files;
            this.selectedFiles = [];
            if (!files) return;
            for (let i = 0; i < files.length; i++) {
                this.selectedFiles.push(files[i].name);
            }
        }
    };
}
</script>
@endsection
