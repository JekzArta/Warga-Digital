<!-- MODAL 1: LENGKAPI NOMOR WHATSAPP (No HP Gate) -->
<div x-show="showNoHpModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10231e]/60 backdrop-blur-xs"
     role="dialog"
     aria-modal="true"
     style="display: none;">
    <div @click.away="showNoHpModal = false"
         x-show="showNoHpModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 border border-[#e4ded1] text-left relative z-10">

        <div class="flex items-center justify-between pb-3 border-b border-[#e4ded1]/70">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-stone-900">Lengkapi Nomor WhatsApp</h3>
                    <p class="text-[11px] text-stone-500">Prasyarat sebelum membuka usaha di Warga Digital</p>
                </div>
            </div>
            <button type="button" @click="showNoHpModal = false" class="text-stone-400 hover:text-stone-700 transition-colors p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="mt-4 space-y-3 text-xs text-stone-600 leading-relaxed">
            <p>Nomor WhatsApp diperlukan agar warga lain di lingkungan RT dapat menghubungi dan memesan barang atau jasa Anda secara langsung.</p>

            @if($errors->has('no_hp'))
                <div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    {{ $errors->first('no_hp') }}
                </div>
            @endif

            <form action="{{ route('umkm.updateNoHp') }}" method="POST" class="space-y-4 pt-1">
                @csrf
                <div>
                    <label for="input_no_hp" class="block font-bold text-stone-800 text-xs mb-1">
                        Nomor WhatsApp Aktif <span class="text-rose-600">*</span>
                    </label>
                    <input type="text"
                           id="input_no_hp"
                           name="no_hp"
                           value="{{ old('no_hp', auth()->user()?->no_hp) }}"
                           placeholder="Contoh: 081234567890"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-stone-50/80 border border-[#e4ded1] focus:bg-white focus:border-[#10231e] focus:ring-1 focus:ring-[#10231e] transition-all text-stone-900 font-mono">
                    <span class="text-[11px] text-stone-400 block mt-1">Gunakan format awalan 08 atau 62 (contoh: 081234567890).</span>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-[#e4ded1]/70">
                    <button type="button"
                            @click="showNoHpModal = false"
                            class="px-4 py-2 rounded-xl text-stone-600 hover:bg-stone-100 text-xs font-semibold transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-[#10231e] hover:bg-[#1a3830] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                        Simpan Nomor WhatsApp
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
