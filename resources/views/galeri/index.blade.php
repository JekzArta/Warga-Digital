@extends('layouts.app', ['title' => 'Galeri Kegiatan — Warga Digital', 'pageTitle' => 'Galeri Kegiatan'])

@section('content')
<div class="space-y-6 w-full" x-data="galeriIndexApp()">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 font-bold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </span>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Galeri Kegiatan Warga</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Arsip dan dokumentasi momen kebersamaan warga lingkungan RT & RW.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 self-start sm:self-auto">
            <!-- Filter RT (Khusus Ketua RW & Super Admin) -->
            @if(auth()->user()->is_super_admin || auth()->user()->hasRole('ketua_rw'))
            <div class="flex items-center gap-2 bg-slate-100/80 p-1 rounded-xl border border-slate-200/60">
                <label for="rt_filter" class="text-xs font-semibold text-slate-600 pl-2">Wilayah:</label>
                <select id="rt_filter" 
                        onchange="window.location.href = this.value" 
                        class="text-xs rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-medium text-slate-700 shadow-2xs focus:border-emerald-500 focus:outline-hidden cursor-pointer">
                    <option value="{{ route('galeri.index', ['rt_id' => 'semua']) }}" {{ $activeRt === 'semua' ? 'selected' : '' }}>Semua RT</option>
                    @foreach($daftarRt as $rtItem)
                        <option value="{{ route('galeri.index', ['rt_id' => $rtItem->id]) }}" {{ (string)$activeRt === (string)$rtItem->id ? 'selected' : '' }}>
                            RT {{ $rtItem->nomor_rt }} ({{ $rtItem->nama }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Tombol Tambah Album (Khusus Pengurus Berwenang) -->
            @if($canManage)
            <button 
                type="button" 
                @click="openCreateModal()"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buat Album Kegiatan</span>
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
            <span>Terdapat kesalahan:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 ml-6">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Album Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($albums as $album)
        <div class="group relative flex flex-col bg-white rounded-3xl border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-emerald-300 transition-all duration-300 overflow-hidden">
            
            <!-- Visual Cover Slot -->
            <div class="relative h-48 w-full overflow-hidden bg-slate-100 flex items-center justify-center">
                @if($album->coverFoto)
                    <img src="{{ asset('storage/' . $album->coverFoto->foto_url) }}" 
                         alt="Cover {{ $album->judul }}" 
                         loading="lazy" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                @else
                    <!-- Placeholder Album Kosong -->
                    <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-slate-50 via-slate-100 to-stone-100 text-slate-400 p-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/80 border border-slate-200/60 shadow-2xs flex items-center justify-center text-slate-400 group-hover:text-emerald-600 group-hover:scale-110 transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-400 mt-2">Belum Ada Foto</span>
                    </div>
                @endif

                <!-- Pill Badge Jumlah Foto -->
                <div class="absolute top-3 left-3 z-20 pointer-events-none">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-black/60 backdrop-blur-md text-white text-[11px] font-bold shadow-xs">
                        <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ $album->fotos_count }} Foto</span>
                    </span>
                </div>

                <!-- Menu Aksi Pengurus (Edit & Hapus) -->
                @if($canManage && (auth()->user()->is_super_admin || auth()->user()->rt_id == $album->rt_id))
                <div class="absolute top-3 right-3 z-30" x-data="{ openMenu: false }">
                    <button type="button" 
                            @click.stop="openMenu = !openMenu" 
                            @click.outside="openMenu = false"
                            class="w-7 h-7 rounded-xl bg-black/50 hover:bg-black/75 backdrop-blur-md text-white flex items-center justify-center transition-all cursor-pointer shadow-xs"
                            aria-label="Menu Aksi Album">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                    <div x-show="openMenu" 
                         x-cloak
                         x-transition
                         class="absolute right-0 mt-1.5 w-36 bg-white rounded-xl shadow-lg border border-slate-200 py-1 text-xs text-slate-700 z-30">
                        <button type="button" 
                                @click.stop="openMenu = false; openEditModal({
                                    id: {{ $album->id }},
                                    judul: '{{ addslashes($album->judul) }}',
                                    tanggal_kegiatan: '{{ $album->tanggal_kegiatan ? $album->tanggal_kegiatan->format('Y-m-d') : '' }}',
                                    deskripsi: '{{ addslashes($album->deskripsi ?? '') }}'
                                })"
                                class="w-full text-left px-3 py-2 hover:bg-slate-50 flex items-center gap-2 cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>Edit Info</span>
                        </button>
                        <button type="button" 
                                @click.stop="openMenu = false; openDeleteModal({
                                    id: {{ $album->id }},
                                    judul: '{{ addslashes($album->judul) }}',
                                    fotos_count: {{ $album->fotos_count }}
                                })"
                                class="w-full text-left px-3 py-2 hover:bg-rose-50 text-rose-600 flex items-center gap-2 cursor-pointer border-t border-slate-100">
                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus Album</span>
                        </button>
                    </div>
                </div>
                @endif
            </div>

            <!-- Metadata Info Card -->
            <div class="p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 text-[11px] font-semibold text-slate-400 mb-1.5">
                        <span>{{ \Carbon\Carbon::parse($album->tanggal_kegiatan)->translatedFormat('d F Y') }}</span>
                        @if(auth()->user()->hasRole('ketua_rw') || auth()->user()->is_super_admin)
                        <span>&bull;</span>
                        <span class="text-emerald-700 font-bold">RT {{ $album->rt?->nomor_rt ?? $album->rt_id }}</span>
                        @endif
                    </div>
                    <h2 class="text-base font-bold text-slate-800 group-hover:text-emerald-700 transition-colors line-clamp-1">
                        {{ $album->judul }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                        {{ $album->deskripsi ?: 'Dokumentasi momen kegiatan kebersamaan warga.' }}
                    </p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span class="truncate">Oleh: {{ $album->creator?->nama ?? 'Pengurus RT' }}</span>
                    <span class="text-emerald-600 font-semibold group-hover:translate-x-0.5 transition-transform shrink-0 flex items-center gap-1">
                        <span>Buka</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </div>
            </div>

            <!-- Card Click Anchor (Menuju Detail Album) -->
            <a href="{{ route('galeri.show', $album->id) }}" class="absolute inset-0 z-10" aria-label="Buka Album {{ $album->judul }}"></a>
        </div>
        @empty
        <!-- Empty State (Ketika Belum Ada Album) -->
        <div class="col-span-full bg-white rounded-3xl p-12 text-center border border-slate-200/90 shadow-2xs">
            <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4 border border-emerald-100">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Belum Ada Album Kegiatan</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Dokumentasi momen kegiatan warga belum diarsipkan. Pengurus dapat membuat album pertama.</p>
            @if($canManage)
            <button 
                type="button" 
                @click="openCreateModal()"
                class="mt-5 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buat Album Pertama</span>
            </button>
            @endif
        </div>
        @endforelse
    </div>

    <!-- Paginasi -->
    @if($albums->hasPages())
    <div class="mt-6">
        {{ $albums->links() }}
    </div>
    @endif

    @if($canManage)
    <!-- ============================================== -->
    <!-- MODAL 1: BUAT ALBUM KEGIATAN BARU             -->
    <!-- ============================================== -->
    <div x-show="showCreateModal" 
         x-cloak 
         @keydown.escape.window="showCreateModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" 
             @click.outside="showCreateModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Buat Album Kegiatan Baru</h3>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>

            <form action="{{ route('galeri.album.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="create_judul" class="block text-xs font-bold text-slate-700 mb-1">Judul Album Kegiatan <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           id="create_judul" 
                           name="judul" 
                           required 
                           maxlength="150"
                           placeholder="Contoh: Kerja Bakti & Pembersihan Saluran"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label for="create_tanggal" class="block text-xs font-bold text-slate-700 mb-1">Tanggal Pelaksanaan Kegiatan <span class="text-rose-500">*</span></label>
                    <input type="date" 
                           id="create_tanggal" 
                           name="tanggal_kegiatan" 
                           required 
                           value="{{ date('Y-m-d') }}"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label for="create_deskripsi" class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Kegiatan (Opsional)</label>
                    <textarea id="create_deskripsi" 
                              name="deskripsi" 
                              rows="3" 
                              maxlength="1000"
                              placeholder="Rangkuman singkat kegiatan bersama warga..."
                              class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20"></textarea>
                </div>

                @if(auth()->user()->is_super_admin)
                <div>
                    <label for="create_rt_id" class="block text-xs font-bold text-slate-700 mb-1">Pilih RT Target (Super Admin)</label>
                    <select id="create_rt_id" name="rt_id" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-800">
                        @foreach($daftarRt as $rtItem)
                            <option value="{{ $rtItem->id }}">RT {{ $rtItem->nomor_rt }} - {{ $rtItem->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs cursor-pointer">
                        Simpan Album
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL 2: EDIT INFO ALBUM                      -->
    <!-- ============================================== -->
    <div x-show="showEditModal" 
         x-cloak 
         @keydown.escape.window="showEditModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" 
             @click.outside="showEditModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Edit Info Album Kegiatan</h3>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>

            <form :action="'{{ url('galeri') }}/' + editData.id" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="edit_judul" class="block text-xs font-bold text-slate-700 mb-1">Judul Album Kegiatan <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           id="edit_judul" 
                           name="judul" 
                           x-model="editData.judul"
                           required 
                           maxlength="150"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label for="edit_tanggal" class="block text-xs font-bold text-slate-700 mb-1">Tanggal Pelaksanaan Kegiatan <span class="text-rose-500">*</span></label>
                    <input type="date" 
                           id="edit_tanggal" 
                           name="tanggal_kegiatan" 
                           x-model="editData.tanggal_kegiatan"
                           required 
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label for="edit_deskripsi" class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Kegiatan</label>
                    <textarea id="edit_deskripsi" 
                              name="deskripsi" 
                              x-model="editData.deskripsi"
                              rows="3" 
                              maxlength="1000"
                              class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">
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
    <!-- MODAL 3: KONFIRMASI HAPUS ALBUM                -->
    <!-- ============================================== -->
    <div x-show="showDeleteModal" 
         x-cloak 
         @keydown.escape.window="showDeleteModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200" 
             @click.outside="showDeleteModal = false">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 border border-rose-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <h3 class="text-base font-bold text-slate-900 text-center">Hapus Album Kegiatan?</h3>
            <p class="text-xs text-slate-500 text-center mt-1">
                Album <strong class="text-slate-800" x-text="deleteData.judul"></strong> beserta seluruh foto di dalamnya (<span x-text="deleteData.fotos_count"></span> foto) akan dihapus secara permanen.
            </p>

            <form :action="'{{ url('galeri') }}/' + deleteData.id" method="POST" class="mt-5 flex items-center justify-center gap-2">
                @csrf
                @method('DELETE')
                <button type="button" @click="showDeleteModal = false" class="w-full px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">
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
function galeriIndexApp() {
    return {
        showCreateModal: false,
        showEditModal: false,
        showDeleteModal: false,
        editData: { id: null, judul: '', tanggal_kegiatan: '', deskripsi: '' },
        deleteData: { id: null, judul: '', fotos_count: 0 },

        openCreateModal() {
            this.showCreateModal = true;
        },
        openEditModal(data) {
            this.editData = { ...data };
            this.showEditModal = true;
        },
        openDeleteModal(data) {
            this.deleteData = { ...data };
            this.showDeleteModal = true;
        }
    };
}
</script>
@endsection
