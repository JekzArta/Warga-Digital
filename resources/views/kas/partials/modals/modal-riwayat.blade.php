<!-- Modal Timeline Riwayat Koreksi Kas (Konsumsi Publik Warga) -->
<div x-show="showRiwayatModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
     role="dialog"
     aria-modal="true"
     style="display: none;">
    <div @click.away="showRiwayatModal = false"
         x-show="showRiwayatModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-stone-200/90 max-h-[90vh] overflow-y-auto text-left relative z-10">

        <div class="flex items-start justify-between pb-4 border-b border-stone-100">
            <div>
                <h3 class="text-base font-bold text-stone-900" id="modal-title">Riwayat Perjalanan Koreksi Kas</h3>
                <p class="text-xs text-stone-400 mt-0.5" x-text="'Transaksi: ' + activeTitle"></p>
            </div>
            <button type="button" @click="showRiwayatModal = false" class="text-stone-400 hover:text-stone-600 transition-colors p-1 rounded-lg hover:bg-stone-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Timeline Vertikal Sederhana -->
        <div class="mt-6 space-y-6">
            <template x-for="(item, index) in activeRiwayat" :key="item.id">
                <div class="relative pl-6 pb-2">
                    <!-- Garis Vertikal Timeline -->
                    <div x-show="index < activeRiwayat.length - 1" class="absolute left-2.5 top-3.5 -bottom-6 w-0.5 bg-stone-200"></div>

                    <!-- Bullet Titik Indikator -->
                    <div class="absolute left-0 top-1 w-5 h-5 rounded-full flex items-center justify-center"
                         :class="item.is_aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                        <span class="w-2 h-2 rounded-full" :class="item.is_aktif ? 'bg-emerald-600' : 'bg-amber-500'"></span>
                    </div>

                    <!-- Konten Tiap Versi -->
                    <div class="p-3.5 rounded-2xl border"
                         :class="item.is_aktif ? 'bg-emerald-50/40 border-emerald-200' : 'bg-stone-50 border-stone-200/80'">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold"
                                  :class="item.is_aktif ? 'text-emerald-900' : 'text-stone-700'"
                                  x-text="index === 0 ? 'Versi Awal' : (item.is_aktif ? 'Versi Aktif Saat Ini' : 'Koreksi #' + index)">
                            </span>
                            <span class="text-[10px] text-stone-400" x-text="item.tanggal"></span>
                        </div>

                        <div class="mt-2 flex items-baseline justify-between">
                            <div class="text-sm font-bold font-mono"
                                 :class="item.is_aktif ? 'text-emerald-700' : 'line-through text-stone-400'"
                                 x-text="item.nominal_formatted">
                            </div>
                            <div class="text-[10px] text-stone-400">
                                Oleh: <span class="font-medium text-stone-700" x-text="item.input_by_nama"></span> (<span x-text="item.input_by_role"></span>)
                            </div>
                        </div>

                        <p class="text-xs text-stone-600 mt-1" x-text="item.keterangan || 'Tanpa keterangan tambahan.'"></p>

                        <!-- Alasan Koreksi Resmi -->
                        <div x-show="item.catatan_koreksi" class="mt-2.5 p-2.5 rounded-xl bg-amber-50 border border-amber-200/80 text-[11px] text-amber-900 font-medium">
                            <span class="font-bold block text-amber-950 mb-0.5">Alasan Koreksi Resmi:</span>
                            <span x-text="item.catatan_koreksi"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-6 pt-4 border-t border-stone-100 flex justify-end">
            <button type="button" @click="showRiwayatModal = false" class="px-4 py-2 bg-stone-900 hover:bg-stone-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>
