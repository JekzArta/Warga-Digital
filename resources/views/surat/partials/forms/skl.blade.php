<!-- Form Khusus: Surat Keterangan Kelahiran (SKL) -->
<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div class="space-y-1 sm:col-span-2">
            <label class="font-bold text-stone-800 block">
                Nama Lengkap Bayi / Anak <span class="text-rose-600">*</span>
            </label>
            <input 
                type="text" 
                name="nama_anak" 
                value="{{ old('nama_anak') }}" 
                required 
                maxlength="150"
                placeholder="Contoh: Muhammad Rayhan Firdaus" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Jenis Kelamin Anak <span class="text-rose-600">*</span>
            </label>
            <select 
                name="jenis_kelamin_anak" 
                required 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
                <option value="">— Pilih Jenis Kelamin —</option>
                <option value="L" {{ old('jenis_kelamin_anak') === 'L' ? 'selected' : '' }}>Laki-Laki</option>
                <option value="P" {{ old('jenis_kelamin_anak') === 'P' ? 'selected' : '' }}>Perempuan</option>
            </select>
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Tanggal Kelahiran Anak <span class="text-rose-600">*</span>
            </label>
            <input 
                type="date" 
                name="tanggal_lahir_anak" 
                value="{{ old('tanggal_lahir_anak') }}" 
                required 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Nama Lengkap Ibu Kandung <span class="text-rose-600">*</span>
            </label>
            <input 
                type="text" 
                name="nama_ibu" 
                value="{{ old('nama_ibu') }}" 
                required 
                maxlength="150"
                placeholder="Nama lengkap ibu kandung" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Nama Lengkap Ayah Kandung <span class="text-rose-600">*</span>
            </label>
            <input 
                type="text" 
                name="nama_ayah" 
                value="{{ old('nama_ayah') }}" 
                required 
                maxlength="150"
                placeholder="Nama lengkap ayah kandung" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>
    </div>

    <!-- 2 Slot Upload Wajib SKL: dokumen_surat_lahir & dokumen_ktp_ortu -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-stone-100">
        <div class="p-4 rounded-2xl bg-[#f6f1e4]/60 border border-[#e4ded1] space-y-2">
            <div class="flex items-center justify-between">
                <label class="font-bold text-stone-900 text-xs flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Surat Lahir RS / Bidan <span class="text-rose-600">*</span></span>
                </label>
                <span class="text-[10px] font-semibold text-stone-500 uppercase">Wajib</span>
            </div>
            <p class="text-[11px] text-stone-600 leading-relaxed">
                Scan surat tanda kelahiran resmi dari RS, Puskesmas, atau Bidan penolong persalinan.
            </p>
            <input 
                type="file" 
                name="dokumen_surat_lahir[]" 
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                class="block w-full text-xs text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#10231e] file:text-white hover:file:bg-[#18362e] cursor-pointer"
            >
        </div>

        <div class="p-4 rounded-2xl bg-[#f6f1e4]/60 border border-[#e4ded1] space-y-2">
            <div class="flex items-center justify-between">
                <label class="font-bold text-stone-900 text-xs flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>KTP Orang Tua <span class="text-rose-600">*</span></span>
                </label>
                <span class="text-[10px] font-semibold text-stone-500 uppercase">Wajib</span>
            </div>
            <p class="text-[11px] text-stone-600 leading-relaxed">
                Scan atau foto KTP Asli dari ayah dan/atau ibu kandung bayi.
            </p>
            <input 
                type="file" 
                name="dokumen_ktp_ortu[]" 
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                class="block w-full text-xs text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#10231e] file:text-white hover:file:bg-[#18362e] cursor-pointer"
            >
        </div>
    </div>
</div>
