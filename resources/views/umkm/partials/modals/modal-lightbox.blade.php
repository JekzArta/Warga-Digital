<!-- MODAL 7: PRATINJAU FOTO PRODUK / JASA (Lightweight Image Zoom) -->
<div x-show="previewImageUrl"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10231e]/85 backdrop-blur-xs"
     @click="previewImageUrl = null"
     style="display: none;">
    <div class="relative max-w-2xl max-h-[85vh] p-2" @click.stop>
        <button type="button"
                @click="previewImageUrl = null"
                class="absolute -top-3 -right-3 bg-stone-900 text-white rounded-full p-2 hover:bg-stone-800 shadow-md border border-white/20 transition-all cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <img :src="previewImageUrl" class="max-w-full max-h-[80vh] rounded-3xl shadow-2xl object-contain mx-auto border border-white/10" alt="Foto produk">
    </div>
</div>
