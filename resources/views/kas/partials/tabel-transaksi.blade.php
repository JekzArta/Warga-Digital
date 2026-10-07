<!-- 4. TABEL MUTASI KAS UTAMA -->
<div class="bg-white rounded-3xl border border-stone-200/90 shadow-xs overflow-hidden">
    <div class="p-5 border-b border-stone-100 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-stone-900">Riwayat Mutasi Kas RT</h3>
            <p class="text-xs text-stone-400 mt-0.5">Seluruh catatan pemasukan dan pengeluaran terverifikasi.</p>
        </div>
        <span class="text-xs text-stone-500 font-medium">
            Menampilkan {{ $transaksiList->count() }} dari {{ $transaksiList->total() }} catatan
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-stone-50 text-[11px] font-bold text-stone-500 uppercase tracking-wider border-b border-stone-100">
                    <th class="py-3 px-4 sm:px-6">Tanggal & Waktu</th>
                    <th class="py-3 px-4">Jenis</th>
                    <th class="py-3 px-4">Kategori & Keterangan</th>
                    <th class="py-3 px-4 text-right">Nominal</th>
                    <th class="py-3 px-4">Dicatat Oleh</th>
                    <th class="py-3 px-4 sm:px-6 text-center">Status / Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 text-xs text-stone-700">
                @forelse($transaksiList as $transaksi)
                    @php
                        $isMasuk = $transaksi->jenis === 'masuk';
                        // Riwayat koreksi aman untuk publik (tanpa IP/user_id/NIK)
                        $riwayatCollection = $transaksi->is_koreksi ? $transaksi->getRiwayatKoreksi()->map(function($r) {
                            return [
                                'id' => $r->id,
                                'jenis' => $r->jenis,
                                'kategori' => $r->kategori,
                                'nominal' => $r->nominal,
                                'nominal_formatted' => 'Rp ' . number_format($r->nominal, 0, ',', '.'),
                                'tanggal' => $r->tanggal->translatedFormat('d F Y'),
                                'created_at' => $r->created_at ? $r->created_at->translatedFormat('d M Y, H:i') : null,
                                'keterangan' => $r->keterangan,
                                'catatan_koreksi' => $r->catatan_koreksi,
                                'input_by_nama' => $r->inputBy?->nama ?? 'Pengurus RT',
                                'input_by_role' => ucwords(str_replace('_', ' ', $r->inputBy?->getHighestRoleCanonical() ?? 'Pengurus')),
                                'is_aktif' => ! $r->koreksiBerikutnya()->exists(),
                            ];
                        }) : collect();
                    @endphp
                    <tr class="hover:bg-stone-50/70 transition-colors">
                        <!-- Tanggal & Waktu -->
                        <td class="py-3.5 px-4 sm:px-6 whitespace-nowrap">
                            <span class="font-bold text-stone-900 block">
                                {{ $transaksi->tanggal->translatedFormat('d M Y') }}
                            </span>
                            @if($transaksi->created_at && $transaksi->created_at->toDateString() !== $transaksi->tanggal->toDateString())
                                <span class="text-[10px] text-stone-400 block font-normal">
                                    Dicatat: {{ $transaksi->created_at->translatedFormat('d/m/Y H:i') }}
                                </span>
                            @endif
                        </td>

                        <!-- Jenis -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($isMasuk)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Uang Masuk
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Uang Keluar
                                </span>
                            @endif
                        </td>

                        <!-- Kategori & Keterangan -->
                        <td class="py-3.5 px-4 max-w-xs sm:max-w-md">
                            <span class="font-bold text-stone-900 block text-xs">
                                {{ $transaksi->kategori }}
                            </span>
                            <span class="text-[11px] text-stone-500 line-clamp-2 mt-0.5">
                                {{ $transaksi->keterangan ?: '—' }}
                            </span>
                        </td>

                        <!-- Nominal -->
                        <td class="py-3.5 px-4 text-right whitespace-nowrap font-mono font-bold text-sm {{ $isMasuk ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $isMasuk ? '+' : '-' }} Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}
                        </td>

                        <!-- Dicatat Oleh (No NIK) -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="font-medium text-stone-800 block text-xs">
                                {{ $transaksi->inputBy?->nama ?? 'Pengurus RT' }}
                            </span>
                            <span class="text-[10px] text-stone-400 capitalize">
                                {{ str_replace('_', ' ', $transaksi->inputBy?->getHighestRoleCanonical() ?? 'Pengurus') }}
                            </span>
                        </td>

                        <!-- Status / Aksi -->
                        <td class="py-3.5 px-4 sm:px-6 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Jika merupakan hasil koreksi -->
                                @if($transaksi->is_koreksi)
                                    <button type="button"
                                            data-riwayat="{{ json_encode($riwayatCollection) }}"
                                            data-kategori="{{ $transaksi->kategori }}"
                                            @click="openRiwayatFromButton($el)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition-colors cursor-pointer">
                                        <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Terkoreksi ({{ $transaksi->jumlah_koreksi }}x)</span>
                                    </button>
                                @endif

                                <!-- Tombol Koreksi bagi Pengurus Berwenang -->
                                @if($canManage)
                                    <button type="button"
                                            data-transaksi="{{ json_encode([
                                                'id' => $transaksi->id,
                                                'nominal' => $transaksi->nominal,
                                                'jenis' => $transaksi->jenis,
                                                'kategori' => $transaksi->kategori,
                                                'tanggal' => $transaksi->tanggal->toDateString(),
                                                'keterangan' => $transaksi->keterangan ?? '',
                                            ]) }}"
                                            @click="openKoreksiFromButton($el)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-semibold bg-stone-100 hover:bg-stone-200 text-stone-700 transition-colors cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <span>Koreksi</span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-stone-400">
                            <svg class="w-10 h-10 mx-auto text-stone-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-xs font-semibold text-stone-600">Belum ada catatan transaksi kas</p>
                            <p class="text-[11px] text-stone-400 mt-0.5">Data mutasi kas RT akan ditampilkan di sini.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($transaksiList->hasPages())
        <div class="p-4 border-t border-stone-100 bg-stone-50/50">
            {{ $transaksiList->links() }}
        </div>
    @endif
</div>
