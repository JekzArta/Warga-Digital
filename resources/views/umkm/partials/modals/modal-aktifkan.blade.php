<!-- MODAL 4B: KONFIRMASI AKTIFKAN KEMBALI LISTING (Pemilik Usaha) -->
<div x-show="showAktifkanModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10231e]/60 backdrop-blur-xs"
     role="dialog"
     aria-modal="true"
     style="display: none;">
    <div @click.away="showAktifkanModal = false"
         x-show="showAktifkanModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 border border-[#e4ded1] text-left relative z-10">

        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3 border border-emerald-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <div class="text-center space-y-2">
            <h3 class="text-base font-bold text-stone-900">Aktifkan Kembali Usaha?</h3>
            <p class="text-xs text-stone-500 leading-relaxed">
                Pengajuan usaha <strong class="text-stone-800" x-text="'&ldquo;' + aktifkanData.nama + '&rdquo;'"></strong> akan dikirimkan kembali ke antrean <strong>Kurasi Pengurus RT</strong> untuk diverifikasi sebelum tayang kembali di etalase warga.
            </p>
        </div>

        <form :action="aktifkanData.actionUrl" method="POST" @submit="isSubmittingAktifkan = true" class="mt-5 flex items-center justify-center gap-2">
            @csrf
            <button type="button"
                    @click="showAktifkanModal = false"
                    class="w-1/2 px-4 py-2 rounded-xl text-stone-700 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer text-center">
                Batal
            </button>
            <button type="submit"
                    :disabled="isSubmittingAktifkan"
                    class="w-1/2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer text-center disabled:opacity-50">
                <span x-text="isSubmittingAktifkan ? 'Mengajukan...' : 'Ya, Ajukan Aktif'"></span>
            </button>
        </form>
    </div>
</div>
