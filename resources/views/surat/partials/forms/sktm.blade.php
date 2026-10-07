<!-- Form Khusus: Surat Keterangan Tidak Mampu (SKTM) -->
<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Pekerjaan Saat Ini <span class="text-rose-600">*</span>
            </label>
            <input 
                type="text" 
                name="pekerjaan" 
                value="{{ old('pekerjaan') }}" 
                required 
                maxlength="100"
                placeholder="Contoh: Buruh Harian Lepas / Pedagang Keliling" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Penghasilan Per Bulan <span class="text-rose-600">*</span>
            </label>
            <input 
                type="text" 
                name="penghasilan_per_bulan" 
                value="{{ old('penghasilan_per_bulan') }}" 
                required 
                maxlength="100"
                placeholder="Contoh: Kurang dari Rp 1.500.000" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Jumlah Tanggungan (Jiwa) <span class="text-rose-600">*</span>
            </label>
            <input 
                type="number" 
                name="jumlah_tanggungan" 
                value="{{ old('jumlah_tanggungan', 3) }}" 
                min="0" 
                max="20" 
                required 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>
    </div>

    <!-- 2 Slot Upload Wajib SKTM: dokumen_kk & dokumen_slip_gaji -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-stone-100">
        <div class="p-4 rounded-2xl bg-[#f6f1e4]/60 border border-[#e4ded1] space-y-2">
            <div class="flex items-center justify-between">
                <label class="font-bold text-stone-900 text-xs flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Kartu Keluarga (KK) <span class="text-rose-600">*</span></span>
                </label>
                <span class="text-[10px] font-semibold text-stone-500 uppercase">Wajib</span>
            </div>
            <p class="text-[11px] text-stone-600 leading-relaxed">
                Scan atau foto Kartu Keluarga untuk verifikasi data anggota keluarga tanggungan.
            </p>
            <input 
                type="file" 
                name="dokumen_kk[]" 
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                class="block w-full text-xs text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#10231e] file:text-white hover:file:bg-[#18362e] cursor-pointer"
            >
        </div>

        <div class="p-4 rounded-2xl bg-[#f6f1e4]/60 border border-[#e4ded1] space-y-2">
            <div class="flex items-center justify-between">
                <label class="font-bold text-stone-900 text-xs flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Slip Gaji / Surat Pernyataan <span class="text-rose-600">*</span></span>
                </label>
                <span class="text-[10px] font-semibold text-stone-500 uppercase">Wajib</span>
            </div>
            <p class="text-[11px] text-stone-600 leading-relaxed">
                Slip penghasilan atau surat pernyataan tidak mampu tertulis bermaterai.
            </p>
            <input 
                type="file" 
                name="dokumen_slip_gaji[]" 
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                class="block w-full text-xs text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#10231e] file:text-white hover:file:bg-[#18362e] cursor-pointer"
            >
        </div>
    </div>
</div>
