<!-- 1. RINGKASAN KAS: 3 Kartu Metrik Utama -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Card 1: Saldo Kas Berjalan Saat Ini (Evergreen Civic Card) -->
    <div class="bg-[#10231e] text-white p-5 sm:p-6 rounded-3xl shadow-sm relative overflow-hidden flex flex-col justify-between border border-[#1b352e]">
        <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-emerald-500/10 rounded-full blur-xl pointer-events-none"></div>
        <div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-stone-300">Saldo Kas RT Saat Ini</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $saldoBerjalan >= 0 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $saldoBerjalan >= 0 ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                    {{ $saldoBerjalan >= 0 ? 'Surplus Kas' : 'Defisit Kas' }}
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold font-mono tracking-tight mt-3 text-white">
                Rp {{ number_format($saldoBerjalan, 0, ',', '.') }}
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-stone-300">
            <span>Total Kumulatif Masuk:</span>
            <span class="font-mono text-[#e5a53f] font-bold">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Card 2: Pemasukan Bulan Berjalan -->
    <div class="bg-white p-5 sm:p-6 rounded-3xl border border-stone-200/90 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-stone-500">Uang Masuk Bulan Ini</span>
                <div class="w-7 h-7 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold font-mono tracking-tight mt-3 text-emerald-700">
                + Rp {{ number_format($masukBulanIni, 0, ',', '.') }}
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-stone-100 text-[11px] text-stone-400">
            Periode {{ now()->translatedFormat('F Y') }}
        </div>
    </div>

    <!-- Card 3: Pengeluaran Bulan Berjalan -->
    <div class="bg-white p-5 sm:p-6 rounded-3xl border border-stone-200/90 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-stone-500">Uang Keluar Bulan Ini</span>
                <div class="w-7 h-7 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold font-mono tracking-tight mt-3 text-rose-700">
                - Rp {{ number_format($keluarBulanIni, 0, ',', '.') }}
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-stone-100 text-[11px] text-stone-400">
            Periode {{ now()->translatedFormat('F Y') }}
        </div>
    </div>
</div>
