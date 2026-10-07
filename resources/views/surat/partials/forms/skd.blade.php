<!-- Form Khusus: Surat Keterangan Domisili (SKD) -->
<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div class="space-y-1 sm:col-span-2">
            <label class="font-bold text-stone-800 block">
                Alamat Domisili Tinggal Sekarang <span class="text-rose-600">*</span>
            </label>
            <input 
                type="text" 
                name="alamat_domisili" 
                value="{{ old('alamat_domisili', $user->alamat) }}" 
                required 
                maxlength="300"
                placeholder="Contoh: Jl. Sekeloa No. 15 RT 05 RW 03, Kel. Sekeloa" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
            <p class="text-[11px] text-stone-500">Tuliskan alamat domisili nyata di mana Anda tinggal saat ini di lingkungan RT 0{{ $user->rt->nomor_rt ?? 5 }}.</p>
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Lama Tinggal di Lingkungan Ini
            </label>
            <input 
                type="text" 
                name="lama_tinggal" 
                value="{{ old('lama_tinggal') }}" 
                maxlength="50"
                placeholder="Contoh: 3 Tahun / 6 Bulan" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>
    </div>

    <!-- Slot Upload Wajib SKD: dokumen_ktp_kk -->
    <div class="pt-3 border-t border-stone-100">
        <div class="p-4 rounded-2xl bg-[#f6f1e4]/60 border border-[#e4ded1] space-y-2">
            <div class="flex items-center justify-between">
                <label class="font-bold text-stone-900 text-xs flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Unggah KTP Asli / Kartu Keluarga <span class="text-rose-600">*</span></span>
                </label>
                <span class="text-[10px] font-semibold text-stone-500 uppercase">Wajib (Maks 5 MB)</span>
            </div>
            <p class="text-[11px] text-stone-600 leading-relaxed">
                Lampirkan foto atau scan KTP Asli atau Kartu Keluarga untuk verifikasi domisili resmi oleh Ketua RT.
            </p>
            <input 
                type="file" 
                name="dokumen_ktp_kk[]" 
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                class="block w-full text-xs text-stone-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#10231e] file:text-white hover:file:bg-[#18362e] cursor-pointer"
            >
        </div>
    </div>
</div>
