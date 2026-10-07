<!-- MODAL 4: KONFIRMASI HAPUS LISTING -->
<div x-show="showDeleteModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10231e]/60 backdrop-blur-xs"
     role="dialog"
     aria-modal="true"
     style="display: none;">
    <div @click.away="showDeleteModal = false"
         x-show="showDeleteModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 border border-[#e4ded1] text-left relative z-10">

        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3 border border-rose-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>

        <div class="text-center space-y-2">
            <h3 class="text-base font-bold text-stone-900">Hapus Listing Usaha?</h3>
            <p class="text-xs text-stone-500 leading-relaxed">
                Apakah Anda yakin ingin menghapus listing <strong class="text-stone-800" x-text="'&ldquo;' + deleteData.nama + '&rdquo;'"></strong>? Tindakan ini permanen dan berkas foto produk akan ikut dibersihkan.
            </p>
        </div>

        <form :action="deleteData.actionUrl" method="POST" @submit="isSubmittingDelete = true" class="mt-5 flex items-center justify-center gap-2">
            @csrf
            @method('DELETE')
            <button type="button"
                    @click="showDeleteModal = false"
                    class="w-1/2 px-4 py-2 rounded-xl text-stone-700 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer text-center">
                Batal
            </button>
            <button type="submit"
                    :disabled="isSubmittingDelete"
                    class="w-1/2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer text-center disabled:opacity-50">
                <span x-text="isSubmittingDelete ? 'Menghapus...' : 'Ya, Hapus'"></span>
            </button>
        </form>
    </div>
</div>
