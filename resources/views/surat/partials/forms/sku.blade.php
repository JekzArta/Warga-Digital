<!-- Form Khusus: Surat Keterangan Usaha (SKU) -->
<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div class="space-y-1 sm:col-span-2">
            <label class="font-bold text-stone-800 block">
                Nama Usaha / Toko / Brand <span class="text-rose-600">*</span>
            </label>
            <input 
                type="text" 
                name="nama_usaha" 
                value="{{ old('nama_usaha') }}" 
                required 
                maxlength="200"
                placeholder="Contoh: Warung Kelontong Berkah / Barbershop Ganteng" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Bidang Usaha <span class="text-rose-600">*</span>
            </label>
            <input 
                type="text" 
                name="bidang_usaha" 
                value="{{ old('bidang_usaha') }}" 
                required 
                maxlength="150"
                placeholder="Contoh: Kuliner & Minuman / Jasa Servis / Perdagangan" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>

        <div class="space-y-1">
            <label class="font-bold text-stone-800 block">
                Lama Usaha Berjalan
            </label>
            <input 
                type="text" 
                name="lama_usaha" 
                value="{{ old('lama_usaha') }}" 
                maxlength="50"
                placeholder="Contoh: 1 Tahun 3 Bulan" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>

        <div class="space-y-1 sm:col-span-2">
            <label class="font-bold text-stone-800 block">
                Alamat Lokasi Usaha <span class="text-rose-600">*</span>
            </label>
            <input 
                type="text" 
                name="alamat_usaha" 
                value="{{ old('alamat_usaha') }}" 
                required 
                maxlength="300"
                placeholder="Contoh: Jl. Sekeloa No. 14 RT 05 RW 03" 
                class="w-full px-3.5 py-2.5 bg-white border border-stone-300 rounded-xl text-stone-900 focus:ring-2 focus:ring-emerald-700/30 focus:border-emerald-700 transition-all text-xs"
            >
        </div>
    </div>

    <!-- 2 Slot Upload Wajib SKU: dokumen_usaha & dokumen_ktp -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-stone-100">
        <div class="p-4 rounded-2xl bg-[#f6f1e4]/60 border border-[#e4ded1] space-y-2">
            <div class="flex items-center justify-between">
                <label class="font-bold text-stone-900 text-xs flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Foto Tempat Usaha <span class="text-rose-600">*</span></span>
                </label>
                <span class="text-[10px] font-semibold text-stone-500 uppercase">Wajib</span>
            </div>
            <p class="text-[11px] text-stone-600 leading-relaxed">
                Foto tampak depan warung/tempat operasional usaha Anda di RT setempat.
            </p>
            <input 
                type="file" 
                name="dokumen_usaha[]" 
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                class="block w-full text-xs text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#10231e] file:text-white hover:file:bg-[#18362e] cursor-pointer"
            >
        </div>

        <div class="p-4 rounded-2xl bg-[#f6f1e4]/60 border border-[#e4ded1] space-y-2">
            <div class="flex items-center justify-between">
                <label class="font-bold text-stone-900 text-xs flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>KTP Pemilik Usaha <span class="text-rose-600">*</span></span>
                </label>
                <span class="text-[10px] font-semibold text-stone-500 uppercase">Wajib</span>
            </div>
            <p class="text-[11px] text-stone-600 leading-relaxed">
                Foto / scan KTP Asli pemilik untuk dicocokkan dengan data registrasi.
            </p>
            <input 
                type="file" 
                name="dokumen_ktp[]" 
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                class="block w-full text-xs text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#10231e] file:text-white hover:file:bg-[#18362e] cursor-pointer"
            >
        </div>
    </div>
</div>
