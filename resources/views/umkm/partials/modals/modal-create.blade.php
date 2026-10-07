<!-- MODAL 2: TAMBAH LISTING BARU -->
<div x-show="showCreateModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10231e]/60 backdrop-blur-xs"
     role="dialog"
     aria-modal="true"
     style="display: none;">
    <div @click.away="showCreateModal = false"
         x-show="showCreateModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-[#e4ded1] max-h-[90vh] overflow-y-auto text-left relative z-10">

        <div class="flex items-center justify-between pb-3 border-b border-[#e4ded1]/70">
            <div>
                <h3 class="text-base font-bold text-stone-900">Buka Usaha / Tambah Produk &amp; Jasa</h3>
                <p class="text-xs text-stone-500 mt-0.5">Lengkapi formulir untuk mengajukan listing usaha ke pengurus RT.</p>
            </div>
            <button type="button" @click="showCreateModal = false" class="text-stone-400 hover:text-stone-700 transition-colors p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Error Validasi Server -->
        @if($errors->any() && !old('is_edit_form') && !$errors->has('no_hp'))
            <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                <span class="font-bold block mb-1">Terdapat kesalahan pengisian:</span>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('umkm.store') }}" method="POST" enctype="multipart/form-data" @submit="isSubmittingCreate = true" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="kategori" :value="createKategori">

            <!-- 1. Kategori Toggle -->
            <div>
                <label class="block font-bold text-stone-800 text-xs mb-1.5">
                    Jenis Usaha <span class="text-rose-600">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2 p-1 bg-stone-100/80 rounded-2xl border border-[#e4ded1]">
                    <button type="button"
                            @click="createKategori = 'barang'"
                            :class="createKategori === 'barang' ? 'bg-white text-stone-900 shadow-2xs font-bold border border-[#e4ded1]/80' : 'text-stone-500 hover:text-stone-900 font-medium'"
                            class="py-2.5 text-xs rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4 text-[#e5a53f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span>Barang / Produk</span>
                    </button>
                    <button type="button"
                            @click="createKategori = 'jasa'"
                            :class="createKategori === 'jasa' ? 'bg-white text-stone-900 shadow-2xs font-bold border border-[#e4ded1]/80' : 'text-stone-500 hover:text-stone-900 font-medium'"
                            class="py-2.5 text-xs rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4 text-[#10231e]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Layanan Jasa</span>
                    </button>
                </div>
            </div>

            <!-- 2. Nama Listing -->
            <div>
                <label for="create_nama" class="block font-bold text-stone-800 text-xs mb-1">
                    Nama Produk atau Layanan Jasa <span class="text-rose-600">*</span>
                </label>
                <input type="text"
                       id="create_nama"
                       name="nama"
                       value="{{ old('nama') }}"
                       required
                       placeholder="Contoh: Keripik Tempe Bu Siti / Jasa Servis AC"
                       class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] transition-all text-stone-900">
            </div>

            <!-- 3. Deskripsi -->
            <div>
                <label for="create_deskripsi" class="block font-bold text-stone-800 text-xs mb-1">
                    Deskripsi Usaha <span class="text-rose-600">*</span>
                </label>
                <textarea id="create_deskripsi"
                          name="deskripsi"
                          rows="3"
                          required
                          placeholder="Jelaskan produk, bahan, atau jenis layanan jasa Anda secara jelas agar warga tetangga mudah memahami..."
                          class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] transition-all text-stone-900 leading-relaxed">{{ old('deskripsi') }}</textarea>
            </div>

            <!-- 4. Harga -->
            <div>
                <label for="create_harga" class="block font-bold text-stone-800 text-xs mb-1">
                    <span x-text="createKategori === 'barang' ? 'Harga Jual (Rp)' : 'Tarif / Patokan Harga (Rp) — Opsional'"></span>
                    <span x-show="createKategori === 'barang'" class="text-rose-600">*</span>
                </label>
                <input type="number"
                       id="create_harga"
                       name="harga"
                       value="{{ old('harga') }}"
                       min="0"
                       step="500"
                       placeholder="Contoh: 15000"
                       class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] transition-all text-stone-900 font-mono">
                <span class="text-[11px] text-stone-400 block mt-1"
                      x-text="createKategori === 'barang' ? '* Masukkan harga jual dalam nominal angka (contoh: 25000).' : '* Untuk jasa, boleh dikosongkan jika tarif disesuaikan dengan negosiasi / jenis pekerjaan.'"></span>
            </div>

            <!-- 5. Foto Upload -->
            <div>
                <label class="block font-bold text-stone-800 text-xs mb-1">
                    Foto Produk atau Layanan (Opsional)
                </label>
                <input type="file"
                       id="create_foto_input"
                       name="foto"
                       accept="image/jpeg,image/png,image/webp"
                       @change="previewImage($event, 'create')"
                       class="w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 file:text-stone-700 hover:file:bg-stone-200 cursor-pointer">
                <span class="text-[11px] text-stone-400 block mt-1">Format gambar: JPG, PNG, atau WebP (maks. 2MB).</span>

                <!-- Live Image Preview -->
                <template x-if="createFotoPreview">
                    <div class="mt-2.5 relative w-32 h-24 rounded-2xl overflow-hidden border border-[#e4ded1] bg-stone-50 shadow-2xs">
                        <img :src="createFotoPreview" class="w-full h-full object-cover" alt="Pratinjau foto baru">
                        <button type="button"
                                @click="clearFoto('create')"
                                class="absolute top-1.5 right-1.5 bg-stone-900/70 text-white rounded-full p-1 hover:bg-stone-900 transition-colors cursor-pointer"
                                title="Hapus foto">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <!-- 6. Template Pesan WhatsApp Kustom -->
            <div>
                <label for="create_template_pesan_wa" class="block font-bold text-stone-800 text-xs mb-1">
                    Template Pesan WhatsApp Pembeli (Opsional)
                </label>
                <textarea id="create_template_pesan_wa"
                          name="template_pesan_wa"
                          rows="2"
                          placeholder="Contoh: Halo Kak, saya tertarik dengan produk ini di Warga Digital. Apakah masih ada stok?"
                          class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] transition-all text-stone-900 leading-relaxed">{{ old('template_pesan_wa') }}</textarea>
                <span class="text-[11px] text-stone-400 block mt-1">
                    Pesan awal yang otomatis terisi saat pembeli menekan tombol WhatsApp. Jika kosong, sistem menggunakan format standar.
                </span>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-amber-900 text-[11px] flex items-center gap-2.5">
                <svg class="w-4 h-4 text-[#e5a53f] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Listing baru akan berstatus <strong>Menunggu Verifikasi</strong> sebelum tayang di etalase warga.</span>
            </div>

            <div class="pt-3 flex items-center justify-end gap-2 border-t border-[#e4ded1]/70">
                <button type="button"
                        @click="showCreateModal = false"
                        class="px-4 py-2 rounded-xl text-stone-600 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        :disabled="isSubmittingCreate"
                        class="inline-flex items-center gap-1.5 px-4.5 py-2 rounded-xl bg-[#10231e] hover:bg-[#1a3830] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50">
                    <span x-text="isSubmittingCreate ? 'Mengajukan...' : 'Ajukan Usaha Warga'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
