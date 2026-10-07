@if($canManage)
<!-- Modal Catat Pemasukan / Pengeluaran Baru (Khusus Pengurus Berwenang) -->
<div x-show="showInputModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
     role="dialog"
     aria-modal="true"
     style="display: none;">
    <div @click.away="showInputModal = false"
         x-show="showInputModal"
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
                <h3 class="text-base font-bold text-stone-900" x-text="modalJenis === 'masuk' ? 'Catat Uang Masuk (Pemasukan)' : 'Catat Uang Keluar (Pengeluaran)'"></h3>
                <p class="text-xs text-stone-400 mt-0.5">Entri transaksi baru kas RT 0{{ $rtTarget->nomor_rt ?? 5 }}.</p>
            </div>
            <button type="button" @click="showInputModal = false" class="text-stone-400 hover:text-stone-600 transition-colors p-1 rounded-lg hover:bg-stone-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Alert Error Validasi Server -->
        @if($errors->any() && !old('is_koreksi_form'))
        <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
            <span class="font-bold block mb-1">Periksa kembali isian formulir:</span>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Form Input Transaksi -->
        <form action="{{ route('kas.store') }}" method="POST" @submit="isSubmitting = true" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="jenis" :value="modalJenis">

            <!-- Pilihan Kategori Preset -->
            <div>
                <label class="block text-xs font-bold text-stone-700 mb-1">Kategori Transaksi <span class="text-rose-500">*</span></label>
                <select name="kategori" x-model="selectedKategori" required class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                    <template x-for="kat in (modalJenis === 'masuk' ? kategoriPresetsMasuk : kategoriPresetsKeluar)" :key="kat">
                        <option :value="kat" x-text="kat"></option>
                    </template>
                </select>
            </div>

            <!-- Nominal Rupiah -->
            <div>
                <label class="block text-xs font-bold text-stone-700 mb-1">Nominal (Rp) <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-xs font-bold text-stone-400">Rp</span>
                    <input type="number"
                           name="nominal"
                           x-model="inputNominal"
                           required
                           min="1"
                           step="1"
                           placeholder="50000"
                           class="w-full pl-10 pr-3 py-2 text-xs font-mono font-bold bg-stone-50 border border-stone-200 rounded-xl text-stone-900 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                </div>
            </div>

            <!-- Tanggal Transaksi (Bisa Backdate) -->
            <div>
                <label class="block text-xs font-bold text-stone-700 mb-1">Tanggal Transaksi / Kuitansi <span class="text-rose-500">*</span></label>
                <input type="date"
                       name="tanggal"
                       required
                       value="{{ old('tanggal', date('Y-m-d')) }}"
                       class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                <span class="text-[10px] text-stone-400 mt-1 block">* Tanggal faktual kuitansi atau serah terima uang.</span>
            </div>

            <!-- Keterangan Tambahan -->
            <div>
                <label class="block text-xs font-bold text-stone-700 mb-1">Keterangan / Rincian (Opsional)</label>
                <textarea name="keterangan"
                          rows="2"
                          placeholder="Catatan belanja, nama donatur, atau nomor nota kuitansi..."
                          class="w-full text-xs bg-stone-50 border border-stone-200 rounded-xl p-3 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">{{ old('keterangan') }}</textarea>
            </div>

            <!-- Tombol Aksi Simpan & Batal (Anti Double-Submit) -->
            <div class="pt-3 border-t border-stone-100 flex items-center justify-end gap-2">
                <button type="button"
                        @click="showInputModal = false"
                        :disabled="isSubmitting"
                        class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 text-xs font-semibold rounded-xl transition-all cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        :disabled="isSubmitting"
                        :class="modalJenis === 'masuk' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-white text-xs font-semibold rounded-xl shadow-xs transition-all cursor-pointer disabled:opacity-50">
                    <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Transaksi'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif
