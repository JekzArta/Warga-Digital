<!-- 2. GRAFIK ARUS KAS 6 BULAN TERAKHIR (Visualisasi Data Riil DB) -->
<div class="bg-white p-5 sm:p-6 rounded-3xl border border-stone-200/90 shadow-xs">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h3 class="text-sm font-bold text-stone-900">Perbandingan Arus Kas 6 Bulan Terakhir</h3>
            <p class="text-xs text-stone-400 mt-0.5">Tren perbandingan uang masuk (iuran/sumbangan) dan uang keluar (biaya operasional/lingkungan).</p>
        </div>
        <div class="flex items-center gap-4 text-xs">
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-md bg-emerald-600"></span>
                <span class="text-stone-600 font-medium">Uang Masuk</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-md bg-rose-500"></span>
                <span class="text-stone-600 font-medium">Uang Keluar</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-6 gap-2 sm:gap-4 items-end h-44 pt-6 border-b border-stone-100 pb-2">
        @foreach($grafik6Bulan as $bulanData)
            @php
                $persenMasuk = $maxNominalGrafik > 0 ? max(6, round(($bulanData['masuk'] / $maxNominalGrafik) * 100)) : 6;
                $persenKeluar = $maxNominalGrafik > 0 ? max(6, round(($bulanData['keluar'] / $maxNominalGrafik) * 100)) : 6;
            @endphp
            <div class="flex flex-col items-center h-full justify-end group">
                <div class="w-full flex items-end justify-center gap-1 sm:gap-1.5 h-32">
                    <!-- Batang Masuk -->
                    <div class="w-3.5 sm:w-6 bg-emerald-600 hover:bg-emerald-700 rounded-t-md transition-all relative cursor-pointer"
                         style="height: {{ $bulanData['masuk'] > 0 ? $persenMasuk : 4 }}%;"
                         title="Masuk: Rp {{ number_format($bulanData['masuk'], 0, ',', '.') }}">
                    </div>
                    <!-- Batang Keluar -->
                    <div class="w-3.5 sm:w-6 bg-rose-500 hover:bg-rose-600 rounded-t-md transition-all relative cursor-pointer"
                         style="height: {{ $bulanData['keluar'] > 0 ? $persenKeluar : 4 }}%;"
                         title="Keluar: Rp {{ number_format($bulanData['keluar'], 0, ',', '.') }}">
                    </div>
                </div>
                <span class="text-[10px] sm:text-xs font-semibold text-stone-500 mt-2 truncate w-full text-center">
                    {{ $bulanData['label'] }}
                </span>
            </div>
        @endforeach
    </div>
</div>
