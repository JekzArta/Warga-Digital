@if($canPublishAnnouncement)
<div x-show="showDeactivateModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs" 
     style="display: none;">
    <div @click.away="showDeactivateModal = false" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-stone-200/90">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </span>
            <div>
                <h3 class="text-base font-bold text-stone-900">Nonaktifkan Pengumuman?</h3>
                <p class="text-xs text-stone-500 font-medium" x-text="selectedAnnouncement?.judul"></p>
            </div>
        </div>

        <p class="text-xs text-stone-600 mb-4 leading-relaxed bg-stone-50 p-3.5 rounded-xl border border-stone-200/80">
            Pengumuman ini tidak akan lagi tampil di feed aktif warga. Seluruh data historis dan tanggapan tetap dipertahankan demi akuntabilitas dan audit.
        </p>

        <form :action="`{{ url('/komunitas/pengumuman') }}/${selectedAnnouncement?.id}/deactivate`" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-stone-700 mb-1">Alasan Penonaktifan <span class="text-rose-500">*</span></label>
                <textarea name="alasan" rows="3" required placeholder="Contoh: Kegiatan dibatalkan atau jadwal diundur..." 
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500 focus:border-rose-500"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-stone-100">
                <button type="button" @click="showDeactivateModal = false" class="px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-100 rounded-xl transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">Nonaktifkan Pengumuman</button>
            </div>
        </form>
    </div>
</div>
@endif
