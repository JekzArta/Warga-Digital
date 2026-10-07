<!-- 3. BAR FILTER MUTASI KAS -->
<div class="bg-white p-4 sm:p-5 rounded-2xl border border-stone-200/90 shadow-xs">
    <form method="GET" action="{{ route('kas.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        @if(request()->has('rt_id'))
            <input type="hidden" name="rt_id" value="{{ request('rt_id') }}">
        @endif

        <!-- Filter Bulan -->
        <div>
            <label class="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">Bulan</label>
            <select name="bulan" class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                <option value="">Semua Bulan</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
        </div>

        <!-- Filter Tahun -->
        <div>
            <label class="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">Tahun</label>
            <select name="tahun" class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                <option value="">Semua Tahun</option>
                @foreach($tahunList as $th)
                    <option value="{{ $th }}" {{ request('tahun') == $th ? 'selected' : '' }}>{{ $th }}</option>
                @endforeach
            </select>
        </div>

        <!-- Filter Jenis -->
        <div>
            <label class="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">Jenis Transaksi</label>
            <select name="jenis" class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                <option value="">Semua Jenis</option>
                <option value="masuk" {{ request('jenis') === 'masuk' ? 'selected' : '' }}>Uang Masuk</option>
                <option value="keluar" {{ request('jenis') === 'keluar' ? 'selected' : '' }}>Uang Keluar</option>
            </select>
        </div>

        <!-- Filter Kategori -->
        <div>
            <label class="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">Kategori</label>
            <select name="kategori" class="w-full text-xs font-medium bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-800 focus:bg-white focus:ring-1 focus:ring-emerald-600">
                <option value="">Semua Kategori</option>
                @foreach($kategoriList as $kat)
                    <option value="{{ $kat }}" {{ request('kategori') === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                @endforeach
            </select>
        </div>

        <!-- Tombol Terapkan & Reset -->
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-stone-900 hover:bg-stone-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">
                Filter
            </button>
            <a href="{{ route('kas.index', request()->only('rt_id')) }}" class="px-3 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 text-xs font-semibold rounded-xl transition-colors">
                Reset
            </a>
        </div>
    </form>
</div>
