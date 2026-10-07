@if($canManage)
<!-- Modal Koreksi Transaksi Lama (Khusus Pengurus Berwenang) -->
<div x-show="showKoreksiModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
     role="dialog"
     aria-modal="true"
     style="display: none;">
    <div @click.away="showKoreksiModal = false"
         x-show="showKoreksiModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-stone-200/90 max-h-[90vh] overflow-y-auto text-left relative z-10">

        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-stone-100">
            <div>
                <h3 class="text-base font-bold text-stone-900">Koreksi Transaksi Kas</h3>
                <p class="text-xs text-stone-400 mt-0.5">Perbaiki kekeliruan data dengan catatan audit resmi.</p>
            </div>
            <button type="button" @click="showKoreksiModal = false" class="text-stone-400 hover:text-stone-600 transition-colors p-1 rounded-lg hover:bg-stone-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Alert Error Validasi Server -->
        @if($errors->any() && old('is_koreksi_form'))
        <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
            <span class="font-bold block mb-1">Gagal menyimpan koreksi:</span>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Kartu Informasi Transaksi yang Sedang Dikoreksi -->
        <div class="mt-4 p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-xs space-y-1.5">
            <div class="flex items-center justify-between">
                <span class="font-bold text-amber-950 uppercase tracking-wider text-[10px]">Data Transaksi Sebelumnya</span>
                <span class="font-mono font-bold text-stone-700" x-text="koreksiTarget.tanggal"></span>
            </div>
            <div class="flex items-baseline justify-between pt-1">
                <span class="font-bold text-stone-800 text-sm" x-text="koreksiTarget.kategori"></span>
                <span class="font-mono font-bold text-stone-600 line-through" x-text="'Rp ' + (koreksiTarget.nominalLamaFormatted || koreksiTarget.nominal)"></span>
            </div>
            <p class="text-[11px] text-stone-500 italic" x-text="koreksiTarget.keterangan || 'Tanpa keterangan sebelumnya.'"></p>
            <span class="text-[10px] text-amber-900 block pt-1 border-t border-amber-200/60">
                * Transaksi di atas tidak akan dihapus, tetapi digantikan dengan versi koreksi baru di bawah ini.
            </span>
        </div>

        <!-- Form Koreksi Baru -->
        <form :action="koreksiTarget.actionUrl" method="POST" @submit="isSubmittingKoreksi = true" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="is_koreksi_form" value="1">
            <input type="hidden" name="koreksi_target_id" :value="koreksiTarget.id">
            <input type="hidden" name="nominal_lama_formatted" :value="koreksiTarget.nominalLamaFormatted">
            <input type="hidden" name="jenis" :value="koreksiTarget.jenis">

            <!-- Kategori Baru -->
            <div>
                <label class="block text-xs font-bold text-stone-700 mb-1">Kategori Transaksi <span class="text-rose-500">*</span></label>
                <select name="kategori" x-model="koreksiTarget.kategori" required class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                    <template x-for="kat in (koreksiTarget.jenis === 'masuk' ? kategoriPresetsMasuk : kategoriPresetsKeluar)" :key="kat">
                        <option :value="kat" x-text="kat" :selected="kat === koreksiTarget.kategori"></option>
                    </template>
                </select>
            </div>

            <!-- Nominal Baru -->
            <div>
                <label class="block text-xs font-bold text-stone-700 mb-1">Nominal yang Benar (Rp) <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-xs font-bold text-stone-400">Rp</span>
                    <input type="number"
                           name="nominal"
                           x-model="koreksiTarget.nominal"
                           required
                           min="1"
                           step="1"
                           class="w-full pl-10 pr-3 py-2 text-xs font-mono font-bold bg-stone-50 border border-stone-200 rounded-xl text-stone-900 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                </div>
            </div>

            <!-- Tanggal Transaksi -->
            <div>
                <label class="block text-xs font-bold text-stone-700 mb-1">Tanggal Transaksi <span class="text-rose-500">*</span></label>
                <input type="date"
                       name="tanggal"
                       x-model="koreksiTarget.tanggal"
                       required
                       class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
            </div>

            <!-- Keterangan Baru -->
            <div>
                <label class="block text-xs font-bold text-stone-700 mb-1">Keterangan Baru (Opsional)</label>
                <textarea name="keterangan"
                          x-model="koreksiTarget.keterangan"
                          rows="2"
                          class="w-full text-xs bg-stone-50 border border-stone-200 rounded-xl p-3 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600"></textarea>
            </div>

            <!-- Alasan Koreksi (Wajib) -->
            <div>
                <label class="block text-xs font-bold text-amber-950 mb-1">
                    Alasan Koreksi Resmi <span class="text-rose-500">*</span>
                </label>
                <textarea name="alasan_koreksi"
                          x-model="koreksiTarget.alasan_koreksi"
                          required
                          minlength="5"
                          maxlength="500"
                          rows="2"
                          placeholder="Jelaskan alasan pembetulan (misal: salah ketik nominal kuitansi kelebihan satu digit nol)..."
                          class="w-full text-xs bg-amber-50/50 border border-amber-300 rounded-xl p-3 text-stone-900 focus:bg-white focus:ring-1 focus:ring-amber-500"></textarea>
                <span class="text-[10px] text-stone-400 mt-1 block">* Alasan koreksi wajib diisi dan akan dicatat permanen pada riwayat publik warga & audit trail.</span>
            </div>

            <!-- Tombol Aksi Simpan & Batal (Anti Double-Submit) -->
            <div class="pt-3 border-t border-stone-100 flex items-center justify-end gap-2">
                <button type="button"
                        @click="showKoreksiModal = false"
                        :disabled="isSubmittingKoreksi"
                        class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 text-xs font-semibold rounded-xl transition-all cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        :disabled="isSubmittingKoreksi"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all cursor-pointer disabled:opacity-50">
                    <svg x-show="isSubmittingKoreksi" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isSubmittingKoreksi ? 'Menyimpan Koreksi...' : 'Simpan Koreksi Transaksi'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif
