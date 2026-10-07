<!-- Form Khusus: Surat Pengantar Kartu Keluarga (SPKK) -->
<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div class="space-y-1 sm:col-span-2">
            <label class="font-bold text-stone-800 block">
                Alasan / Keperluan Permohonan Kartu Keluarga <span class="text-rose-600">*</span>
            </label>
            <select 
                name="alasan_permohonan" 
                required 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
                <option value="">— Pilih Alasan Permohonan —</option>
                <option value="Membentuk Keluarga Baru" {{ old('alasan_permohonan') === 'Membentuk Keluarga Baru' ? 'selected' : '' }}>Membentuk Keluarga Baru (Pasangan Baru Menikah)</option>
                <option value="Penambahan Anggota Keluarga" {{ old('alasan_permohonan') === 'Penambahan Anggota Keluarga' ? 'selected' : '' }}>Penambahan Anggota Keluarga (Kelahiran Anak)</option>
                <option value="Pecah Kartu Keluarga" {{ old('alasan_permohonan') === 'Pecah Kartu Keluarga' ? 'selected' : '' }}>Pecah Kartu Keluarga Mandiri</option>
                <option value="Perubahan Biodata / Elemen Data" {{ old('alasan_permohonan') === 'Perubahan Biodata / Elemen Data' ? 'selected' : '' }}>Perubahan Biodata / Elemen Data Anggota</option>
                <option value="Penggantian KK Rusak / Hilang" {{ old('alasan_permohonan') === 'Penggantian KK Rusak / Hilang' ? 'selected' : '' }}>Penggantian Kartu Keluarga Rusak / Hilang</option>
            </select>
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Nama Kepala Keluarga <span class="text-rose-600">*</span>
            </label>
            <input 
                type="text" 
                name="nama_kepala_keluarga" 
                value="{{ old('nama_kepala_keluarga', $user->nama) }}" 
                required 
                maxlength="150"
                placeholder="Nama lengkap kepala keluarga" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Jumlah Anggota Keluarga (Jiwa) <span class="text-rose-600">*</span>
            </label>
            <input 
                type="number" 
                name="jumlah_anggota" 
                value="{{ old('jumlah_anggota', 2) }}" 
                min="1" 
                max="20" 
                required 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>
    </div>

    <!-- Slot Upload Wajib SPKK: dokumen_kk_nikah -->
    <div class="pt-3 border-t border-stone-100">
        <div class="p-4 rounded-2xl bg-[#f6f1e4]/60 border border-[#e4ded1] space-y-2">
            <div class="flex items-center justify-between">
                <label class="font-bold text-stone-900 text-xs flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Kartu Keluarga Lama / Buku Nikah <span class="text-rose-600">*</span></span>
                </label>
                <span class="text-[10px] font-semibold text-stone-500 uppercase">Wajib</span>
            </div>
            <p class="text-[11px] text-stone-600 leading-relaxed">
                Lampirkan scan atau foto KK sebelumnya, Buku Nikah / Akta Perkawinan, atau surat keterangan pindah.
            </p>
            <input 
                type="file" 
                name="dokumen_kk_nikah[]" 
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                class="block w-full text-xs text-stone-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#10231e] file:text-white hover:file:bg-[#18362e] cursor-pointer"
            >
        </div>
    </div>
</div>
