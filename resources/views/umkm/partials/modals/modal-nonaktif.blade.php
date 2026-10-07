<!-- MODAL 4A: KONFIRMASI NONAKTIFKAN LISTING (Pemilik Usaha) -->
<div x-show="showNonaktifModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10231e]/60 backdrop-blur-xs"
     role="dialog"
     aria-modal="true"
     style="display: none;">
    <div @click.away="showNonaktifModal = false"
         x-show="showNonaktifModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 border border-[#e4ded1] text-left relative z-10">

        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-3 border border-amber-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <div class="text-center space-y-2">
            <h3 class="text-base font-bold text-stone-900">Tarik Usaha dari Etalase?</h3>
            <p class="text-xs text-stone-500 leading-relaxed">
                Usaha <strong class="text-stone-800" x-text="'&ldquo;' + nonaktifData.nama + '&rdquo;'"></strong> akan dinonaktifkan sementara dan tidak dapat dilihat oleh tetangga di etalase warga. Anda dapat mengaktifkannya kembali sewaktu-waktu (wajib kurasi ulang RT).
            </p>
        </div>

        <form :action="nonaktifData.actionUrl" method="POST" @submit="isSubmittingNonaktif = true" class="mt-5 flex items-center justify-center gap-2">
            @csrf
            <button type="button"
                    @click="showNonaktifModal = false"
                    class="w-1/2 px-4 py-2 rounded-xl text-stone-700 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer text-center">
                Batal
            </button>
            <button type="submit"
                    :disabled="isSubmittingNonaktif"
                    class="w-1/2 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer text-center disabled:opacity-50">
                <span x-text="isSubmittingNonaktif ? 'Memproses...' : 'Ya, Nonaktifkan'"></span>
            </button>
        </form>
    </div>
</div>
