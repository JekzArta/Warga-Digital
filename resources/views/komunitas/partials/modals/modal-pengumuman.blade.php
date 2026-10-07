@if($canPublishAnnouncement)
<div x-show="showPengumumanModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs" 
     style="display: none;">
    <div @click.away="showPengumumanModal = false" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-stone-200/90 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </span>
                <div>
                    <h3 class="text-base font-bold text-stone-900">Terbitkan Pengumuman Resmi</h3>
                    <p class="text-xs text-stone-500">Lingkup Wilayah: <span class="font-semibold text-emerald-800">{{ strtoupper($requestedScope) }}</span></p>
                </div>
            </div>
            <button type="button" @click="showPengumumanModal = false" class="text-stone-400 hover:text-stone-600 p-1 rounded-lg hover:bg-stone-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form action="{{ route('komunitas.pengumuman.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="scope_type" value="{{ $requestedScope }}">

            <div>
                <label class="block text-xs font-semibold text-stone-700 mb-1">Judul Pengumuman <span class="text-rose-500">*</span></label>
                <input type="text" name="judul" required placeholder="Contoh: Kerja Bakti Lingkungan Hari Minggu" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-stone-700 mb-1">Tingkat Urgensi <span class="text-rose-500">*</span></label>
                    <select name="tipe" class="w-full text-xs px-3 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
                        <option value="INFO">INFO (Netral / Informasi Biasa)</option>
                        <option value="PENTING">PENTING (Perhatian Warga)</option>
                        <option value="MENDESAK">MENDESAK (Darurat / Tindakan Cepat)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-700 mb-1">Berlaku Sampai (Opsional)</label>
                    <input type="date" name="expired_at" min="{{ now()->toDateString() }}" 
                           class="w-full text-xs px-3 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-stone-700 mb-1">Hubungkan ke Forum Warga (Opsional)</label>
                <select name="forum_thread_id" class="w-full text-xs px-3 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
                    <option value="">-- Tanpa Forum Terkait --</option>
                    @foreach($availableThreads as $thr)
                        <option value="{{ $thr->id }}">{{ $thr->judul }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-stone-700 mb-1">Isi Pengumuman <span class="text-rose-500">*</span></label>
                <textarea name="konten" rows="4" required placeholder="Tuliskan rincian lengkap pengumuman resmi..." 
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600"></textarea>
            </div>

            <!-- Seksi Tambahan: Jadwalkan sebagai Agenda Kalender -->
            <div class="p-3.5 bg-stone-50/80 border border-stone-200/80 rounded-xl space-y-3" x-data="{ isAgenda: false }">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_agenda" id="is_agenda_store" value="1" x-model="isAgenda" class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="is_agenda_store" class="text-xs font-semibold text-stone-800 cursor-pointer">
                        Jadwalkan sebagai Agenda Kalender Warga
                    </label>
                </div>

                <div x-show="isAgenda" x-cloak class="space-y-3 pt-2.5 border-t border-stone-200/70">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-stone-700 mb-1">Tanggal Kegiatan <span class="text-rose-500">*</span></label>
                            <input type="date" name="agenda_tanggal" :required="isAgenda" class="w-full text-xs px-3 py-2 rounded-xl border border-stone-200 focus:ring-1 focus:ring-emerald-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-stone-700 mb-1">Kategori Agenda <span class="text-rose-500">*</span></label>
                            <select name="agenda_kategori" :required="isAgenda" class="w-full text-xs px-3 py-2 rounded-xl border border-stone-200 focus:ring-1 focus:ring-emerald-500 bg-white">
                                <option value="KEGIATAN">Kegiatan Warga</option>
                                <option value="RAPAT">Rapat Warga</option>
                                <option value="POSYANDU">Posyandu</option>
                                <option value="LAINNYA">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-stone-700 mb-1">Waktu Mulai (HH:MM)</label>
                            <input type="text" name="agenda_waktu_mulai" placeholder="08:00" class="w-full text-xs px-3 py-2 rounded-xl border border-stone-200 focus:ring-1 focus:ring-emerald-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-stone-700 mb-1">Waktu Selesai (HH:MM)</label>
                            <input type="text" name="agenda_waktu_selesai" placeholder="11:00" class="w-full text-xs px-3 py-2 rounded-xl border border-stone-200 focus:ring-1 focus:ring-emerald-500 bg-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-stone-700 mb-1">Lokasi Kegiatan (Opsional)</label>
                        <input type="text" name="agenda_lokasi" placeholder="Contoh: Balai Warga / Lapangan Serbaguna" class="w-full text-xs px-3 py-2 rounded-xl border border-stone-200 focus:ring-1 focus:ring-emerald-500 bg-white">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_pinned" id="is_pinned_store" value="1" class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                <label for="is_pinned_store" class="text-xs text-stone-600 font-medium cursor-pointer">Sematkan di posisi teratas (Pin)</label>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-stone-100">
                <button type="button" @click="showPengumumanModal = false" class="px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-100 rounded-xl transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">Terbitkan Pengumuman</button>
            </div>
        </form>
    </div>
</div>
@endif
