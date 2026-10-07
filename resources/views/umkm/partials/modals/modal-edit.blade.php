<!-- MODAL 3: EDIT / REVISI LISTING -->
<div x-show="showEditModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10231e]/60 backdrop-blur-xs"
     role="dialog"
     aria-modal="true"
     style="display: none;">
    <div @click.away="showEditModal = false"
         x-show="showEditModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-[#e4ded1] max-h-[90vh] overflow-y-auto text-left relative z-10">

        <div class="flex items-center justify-between pb-3 border-b border-[#e4ded1]/70">
            <div>
                <h3 class="text-base font-bold text-stone-900"
                    x-text="editData.status === 'DITOLAK' ? 'Revisi & Ajukan Ulang Usaha' : 'Edit Data Usaha'"></h3>
                <p class="text-xs text-stone-500 mt-0.5">Perbarui informasi listing usaha Anda.</p>
            </div>
            <button type="button" @click="showEditModal = false" class="text-stone-400 hover:text-stone-700 transition-colors p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Konteks Banner Status Khusus -->
        <template x-if="editData.status === 'DITOLAK'">
            <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1">
                <span class="font-bold text-rose-800 block">Alasan Penolakan Pengurus:</span>
                <p class="font-medium text-rose-950" x-text="'&ldquo;' + editData.alasan_tolak + '&rdquo;'"></p>
                <span class="text-[10px] text-rose-600 block pt-1">
                    Perbaiki informasi yang diminta di bawah ini. Setelah Anda menyimpan perbaikan, status listing akan kembali ke <strong>Menunggu Verifikasi</strong>.
                </span>
            </div>
        </template>

        <template x-if="editData.status === 'DISETUJUI'">
            <div class="mt-4 p-3.5 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs">
                <span class="font-bold block mb-0.5">Perhatian Perubahan Data:</span>
                <span class="text-amber-800 leading-relaxed block text-[11px]">
                    Mengubah informasi utama (nama, kategori, deskripsi, harga, foto, atau template WhatsApp) akan mengembalikan status usaha ini ke <strong>Menunggu Verifikasi</strong> untuk dikurasi ulang oleh pengurus RT.
                </span>
            </div>
        </template>

        <template x-if="editData.status === 'DITAKEDOWN'">
            <div class="mt-4 p-3.5 rounded-2xl bg-purple-50 border border-purple-200 text-purple-900 text-xs space-y-1">
                <span class="font-bold text-purple-800 block">Alasan Takedown Pengurus:</span>
                <p class="font-medium text-purple-950" x-text="'&ldquo;' + (editData.alasan_takedown || 'Listing dinonaktifkan oleh pengurus') + '&rdquo;'"></p>
                <span class="text-[10px] text-purple-700 block pt-1">
                    Perbaiki informasi atau foto produk di bawah ini. Setelah Anda menyimpan perbaikan, status listing akan kembali ke <strong>Menunggu Verifikasi</strong> untuk dikurasi ulang oleh pengurus RT.
                </span>
            </div>
        </template>

        <!-- Error Validasi Server Edit -->
        @if($errors->any() && old('is_edit_form'))
            <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                <span class="font-bold block mb-1">Gagal memperbarui listing:</span>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form :action="editData.actionUrl" method="POST" enctype="multipart/form-data" @submit="isSubmittingEdit = true" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_edit_form" value="1">
            <input type="hidden" name="edit_id" :value="editData.id">
            <input type="hidden" name="kategori" :value="editData.kategori">

            <!-- 1. Kategori Toggle -->
            <div>
                <label class="block font-bold text-stone-800 text-xs mb-1.5">
                    Jenis Usaha <span class="text-rose-600">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2 p-1 bg-stone-100/80 rounded-2xl border border-[#e4ded1]">
                    <button type="button"
                            @click="editData.kategori = 'barang'"
                            :class="editData.kategori === 'barang' ? 'bg-white text-stone-900 shadow-2xs font-bold border border-[#e4ded1]/80' : 'text-stone-500 hover:text-stone-900 font-medium'"
                            class="py-2.5 text-xs rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4 text-[#e5a53f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span>Barang / Produk</span>
                    </button>
                    <button type="button"
                            @click="editData.kategori = 'jasa'"
                            :class="editData.kategori === 'jasa' ? 'bg-white text-stone-900 shadow-2xs font-bold border border-[#e4ded1]/80' : 'text-stone-500 hover:text-stone-900 font-medium'"
                            class="py-2.5 text-xs rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4 text-[#10231e]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Layanan Jasa</span>
                    </button>
                </div>
            </div>

            <!-- 2. Nama Listing -->
            <div>
                <label for="edit_nama" class="block font-bold text-stone-800 text-xs mb-1">
                    Nama Produk atau Layanan Jasa <span class="text-rose-600">*</span>
                </label>
                <input type="text"
                       id="edit_nama"
                       name="nama"
                       x-model="editData.nama"
                       required
                       class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] transition-all text-stone-900">
            </div>

            <!-- 3. Deskripsi -->
            <div>
                <label for="edit_deskripsi" class="block font-bold text-stone-800 text-xs mb-1">
                    Deskripsi Usaha <span class="text-rose-600">*</span>
                </label>
                <textarea id="edit_deskripsi"
                          name="deskripsi"
                          rows="3"
                          x-model="editData.deskripsi"
                          required
                          class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] transition-all text-stone-900 leading-relaxed"></textarea>
            </div>

            <!-- 4. Harga -->
            <div>
                <label for="edit_harga" class="block font-bold text-stone-800 text-xs mb-1">
                    <span x-text="editData.kategori === 'barang' ? 'Harga Jual (Rp)' : 'Tarif / Patokan Harga (Rp) — Opsional'"></span>
                    <span x-show="editData.kategori === 'barang'" class="text-rose-600">*</span>
                </label>
                <input type="number"
                       id="edit_harga"
                       name="harga"
                       x-model="editData.harga"
                       min="0"
                       step="500"
                       class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] transition-all text-stone-900 font-mono">
                <span class="text-[11px] text-stone-400 block mt-1"
                      x-text="editData.kategori === 'barang' ? '* Masukkan harga jual dalam nominal angka.' : '* Untuk jasa, boleh dikosongkan jika tarif disesuaikan dengan negosiasi.'"></span>
            </div>

            <!-- 5. Foto Upload -->
            <div>
                <label class="block font-bold text-stone-800 text-xs mb-1">
                    Foto Produk atau Layanan
                </label>

                <!-- Preview Foto Lama & Baru -->
                <div class="flex items-center gap-3 mb-2">
                    <template x-if="editData.foto_url && !editFotoPreview">
                        <div class="flex items-center gap-2">
                            <div class="w-16 h-14 rounded-2xl overflow-hidden border border-[#e4ded1] bg-stone-50 shadow-2xs">
                                <img :src="'/storage/' + editData.foto_url" class="w-full h-full object-cover" alt="Foto saat ini">
                            </div>
                            <span class="text-[11px] text-stone-500">Foto saat ini</span>
                        </div>
                    </template>

                    <template x-if="editFotoPreview">
                        <div class="flex items-center gap-2">
                            <div class="w-16 h-14 rounded-2xl overflow-hidden border border-emerald-300 bg-emerald-50 relative shadow-2xs">
                                <img :src="editFotoPreview" class="w-full h-full object-cover" alt="Foto baru">
                            </div>
                            <div>
                                <span class="text-[11px] text-emerald-800 font-semibold block">Pratinjau foto baru</span>
                                <button type="button" @click="clearFoto('edit')" class="text-[10px] text-rose-600 hover:underline cursor-pointer">Batal ganti</button>
                            </div>
                        </div>
                    </template>
                </div>

                <input type="file"
                       id="edit_foto_input"
                       name="foto"
                       accept="image/jpeg,image/png,image/webp"
                       @change="previewImage($event, 'edit')"
                       class="w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 file:text-stone-700 hover:file:bg-stone-200 cursor-pointer">
                <span class="text-[11px] text-stone-400 block mt-1">Biarkan kosong jika tidak ingin mengganti foto saat ini. Format JPG, PNG, WebP (maks. 2MB).</span>
            </div>

            <!-- 6. Template Pesan WhatsApp Kustom -->
            <div>
                <label for="edit_template_pesan_wa" class="block font-bold text-stone-800 text-xs mb-1">
                    Template Pesan WhatsApp Pembeli (Opsional)
                </label>
                <textarea id="edit_template_pesan_wa"
                          name="template_pesan_wa"
                          rows="2"
                          x-model="editData.template_pesan_wa"
                          placeholder="Contoh: Halo Kak, saya tertarik dengan produk ini di Warga Digital..."
                          class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] transition-all text-stone-900 leading-relaxed"></textarea>
            </div>

            <div class="pt-3 flex items-center justify-end gap-2 border-t border-[#e4ded1]/70">
                <button type="button"
                        @click="showEditModal = false"
                        class="px-4 py-2 rounded-xl text-stone-600 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        :disabled="isSubmittingEdit"
                        class="inline-flex items-center gap-1.5 px-4.5 py-2 rounded-xl bg-[#10231e] hover:bg-[#1a3830] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50">
                    <span x-text="isSubmittingEdit ? 'Menyimpan...' : (editData.status === 'DITOLAK' ? 'Kirim Ulang Revisi' : (editData.status === 'DITAKEDOWN' ? 'Ajukan Ulang Kurasi' : 'Simpan Perubahan'))"></span>
                </button>
            </div>
        </form>
    </div>
</div>
