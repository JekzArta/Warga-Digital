<!-- MODAL 4C: TAKEDOWN LISTING OLEH PENGURUS (RT / RW / Super Admin) -->
<div x-show="showTakedownModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10231e]/60 backdrop-blur-xs"
     role="dialog"
     aria-modal="true"
     style="display: none;">
    <div @click.away="showTakedownModal = false"
         x-show="showTakedownModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 sm:p-7 border border-[#e4ded1] text-left relative z-10">

        <div class="flex items-center justify-between pb-3 border-b border-[#e4ded1]/70">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-stone-900">Takedown Listing Usaha</h3>
                    <p class="text-[11px] text-stone-500">Listing: <strong class="text-stone-700" x-text="takedownData.nama"></strong></p>
                </div>
            </div>
            <button type="button" @click="showTakedownModal = false" class="text-stone-400 hover:text-stone-700 transition-colors p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="mt-4 space-y-3 text-xs text-stone-600 leading-relaxed">
            <p>Listing ini akan langsung ditarik dari etalase se-RW dan dicatat di Audit Trail. Pemilik usaha wajib merevisi listing sebelum dapat diajukan kurasi ulang.</p>

            @if($errors->has('alasan_takedown'))
                <div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    {{ $errors->first('alasan_takedown') }}
                </div>
            @endif

            <form :action="takedownData.actionUrl" method="POST" @submit="isSubmittingTakedown = true" class="space-y-4 pt-1">
                @csrf
                <input type="hidden" name="takedown_id" :value="takedownData.id">
                <input type="hidden" name="takedown_nama" :value="takedownData.nama">

                <div>
                    <label for="takedown_alasan" class="block font-bold text-stone-800 text-xs mb-1">
                        Alasan Takedown Resmi <span class="text-rose-600">*</span>
                    </label>
                    <textarea id="takedown_alasan"
                              name="alasan_takedown"
                              rows="3"
                              required
                              minlength="5"
                              maxlength="1000"
                              placeholder="Contoh: Mengandung promosi produk terlarang atau keluhan warga sekitar..."
                              class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-rose-600 focus:ring-1 focus:ring-rose-600 transition-all text-stone-900 leading-relaxed">{{ old('alasan_takedown') }}</textarea>
                    <span class="text-[11px] text-stone-400 block mt-1">Minimal 5 karakter. Alasan ini akan tercatat di log audit dan diberitahukan transparan kepada pemilik.</span>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-[#e4ded1]/70">
                    <button type="button"
                            @click="showTakedownModal = false"
                            class="px-4 py-2 rounded-xl text-stone-600 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="isSubmittingTakedown"
                            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50">
                        <span x-text="isSubmittingTakedown ? 'Memproses...' : 'Takedown Sekarang'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
