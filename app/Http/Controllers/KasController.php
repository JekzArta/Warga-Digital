<?php

namespace App\Http\Controllers;

use App\Constants\AuditAction;
use App\Models\KasTransaksi;
use App\Models\Rt;
use App\Services\AuditLogger;
use App\Services\ScopeAuthorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KasController extends Controller
{
    /**
     * Menampilkan transparansi kas RT bagi warga dan antarmuka manajemen kas bagi pengurus.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Tentukan RT target berdasarkan wewenang user
        $targetRtId = null;

        if ($user->hasRole('ketua_rw') && $user->rw_id) {
            // Ketua RW dapat memilih RT di wilayah binaannya, atau default ke RT pertama
            $availableRts = Rt::where('rw_id', $user->rw_id)->orderBy('nomor_rt')->get();
            $requestedRtId = $request->query('rt_id');

            if ($requestedRtId !== null && $requestedRtId !== '') {
                $targetRtId = (int) $requestedRtId;
                // Hard check: tolak bila Ketua RW memanipulasi rt_id ke luar wilayah RW-nya
                abort_unless($availableRts->contains('id', $targetRtId), 403, 'Anda tidak berhak melihat kas RT di luar wilayah binaan RW Anda.');
            } else {
                $targetRtId = $availableRts->first()?->id ?? (int) $user->rt_id;
            }
        } elseif ($user->is_super_admin || $user->hasRole('super_admin')) {
            // Super Admin memiliki akses pemantauan lintas RT
            $availableRts = Rt::orderBy('nomor_rt')->get();
            $requestedRtId = $request->query('rt_id');

            if ($requestedRtId !== null && $requestedRtId !== '') {
                $targetRtId = (int) $requestedRtId;
                abort_unless(Rt::where('id', $targetRtId)->exists(), 404, 'Data RT tidak ditemukan.');
            } else {
                $targetRtId = $availableRts->first()?->id ?? 1;
            }
        } else {
            // Warga dan Pengurus RT terkunci pada RT-nya sendiri
            $targetRtId = (int) $user->rt_id;
            $availableRts = collect();
        }

        // Pastikan user berwenang melihat RT ini via ScopeAuthorizer
        abort_unless(ScopeAuthorizer::canViewKas($user, (int) $targetRtId), 403, 'Anda tidak berhak melihat kas wilayah ini.');

        // 2. Cek apakah user berwenang mengelola kas (Bendahara / Ketua RT / Wakil RT)
        $canManage = ScopeAuthorizer::canManageKas($user, (int) $targetRtId);

        // 3. Kalkulasi Saldo Berjalan (hanya menghitung transaksi aktif / leaf node)
        $queryAktif = KasTransaksi::withoutGlobalScopes()
            ->aktif()
            ->where('rt_id', $targetRtId);

        $totalMasuk = (int) (clone $queryAktif)->masuk()->sum('nominal');
        $totalKeluar = (int) (clone $queryAktif)->keluar()->sum('nominal');
        $saldoBerjalan = $totalMasuk - $totalKeluar;

        // Pemasukan & Pengeluaran Bulan Berjalan
        $bulanIni = now()->month;
        $tahunIni = now()->year;

        $masukBulanIni = (int) (clone $queryAktif)->masuk()
            ->whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->sum('nominal');

        $keluarBulanIni = (int) (clone $queryAktif)->keluar()
            ->whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->sum('nominal');

        // 4. Query Daftar Transaksi dengan Filter Opsional
        $transactionsQuery = KasTransaksi::withoutGlobalScopes()
            ->aktif()
            ->where('rt_id', $targetRtId)
            ->with(['inputBy', 'koreksiDari']);

        if ($request->filled('bulan')) {
            $transactionsQuery->whereMonth('tanggal', (int) $request->query('bulan'));
        }
        if ($request->filled('tahun')) {
            $transactionsQuery->whereYear('tanggal', (int) $request->query('tahun'));
        }
        if ($request->filled('jenis') && in_array($request->query('jenis'), ['masuk', 'keluar'], true)) {
            $transactionsQuery->where('jenis', $request->query('jenis'));
        }
        if ($request->filled('kategori')) {
            $transactionsQuery->where('kategori', $request->query('kategori'));
        }

        $transaksiList = $transactionsQuery
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $rtTarget = Rt::find($targetRtId);

        // 5. Data Grafik Arus Kas 6 Bulan Terakhir
        $grafik6Bulan = [];
        $maxNominalGrafik = 1;

        for ($i = 5; $i >= 0; $i--) {
            $tgl = now()->subMonths($i);
            $bln = (int) $tgl->month;
            $thn = (int) $tgl->year;
            $label = $tgl->translatedFormat('M Y');

            $m = (int) (clone $queryAktif)->masuk()
                ->whereMonth('tanggal', $bln)
                ->whereYear('tanggal', $thn)
                ->sum('nominal');

            $k = (int) (clone $queryAktif)->keluar()
                ->whereMonth('tanggal', $bln)
                ->whereYear('tanggal', $thn)
                ->sum('nominal');

            if ($m > $maxNominalGrafik) {
                $maxNominalGrafik = $m;
            }
            if ($k > $maxNominalGrafik) {
                $maxNominalGrafik = $k;
            }

            $grafik6Bulan[] = [
                'label' => $label,
                'bulan' => $bln,
                'tahun' => $thn,
                'masuk' => $m,
                'keluar' => $k,
            ];
        }

        // 6. Data Master Filter (Database-agnostic untuk kompatibilitas MySQL & SQLite test)
        $tahunList = KasTransaksi::withoutGlobalScopes()
            ->where('rt_id', $targetRtId)
            ->pluck('tanggal')
            ->map(fn ($d) => $d ? \Carbon\Carbon::parse($d)->year : null)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        if (! $tahunList->contains(now()->year)) {
            $tahunList->prepend(now()->year);
        }

        $kategoriList = array_values(array_unique(array_merge(
            KasTransaksi::KATEGORI_MASUK_PRESETS,
            KasTransaksi::KATEGORI_KELUAR_PRESETS
        )));

        return view('kas.index', compact(
            'targetRtId',
            'rtTarget',
            'availableRts',
            'canManage',
            'saldoBerjalan',
            'totalMasuk',
            'totalKeluar',
            'masukBulanIni',
            'keluarBulanIni',
            'transaksiList',
            'grafik6Bulan',
            'maxNominalGrafik',
            'tahunList',
            'kategoriList'
        ));
    }

    /**
     * Mencatat transaksi kas masuk / keluar baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // 1. Authorization Guard ketat via ScopeAuthorizer
        abort_unless($user && $user->rt_id, 403, 'Data wilayah Anda tidak valid.');
        abort_unless(ScopeAuthorizer::canManageKas($user, (int) $user->rt_id), 403, 'Anda tidak memiliki wewenang untuk mencatat transaksi kas.');

        // 2. Validasi input server-side
        $validated = $request->validate([
            'jenis' => ['required', 'string', 'in:masuk,keluar'],
            'kategori' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($request) {
                    $jenis = $request->input('jenis');
                    if ($jenis === 'masuk' && in_array($value, KasTransaksi::KATEGORI_KELUAR_PRESETS, true)) {
                        $fail('Kategori yang dipilih tidak sesuai untuk jenis transaksi pemasukan.');
                    }
                    if ($jenis === 'keluar' && in_array($value, KasTransaksi::KATEGORI_MASUK_PRESETS, true)) {
                        $fail('Kategori yang dipilih tidak sesuai untuk jenis transaksi pengeluaran.');
                    }
                },
            ],
            'nominal' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        // 3. Eksekusi atomik dalam DB::transaction
        DB::transaction(function () use ($validated, $user) {
            // Simpan transaksi kas baru
            $kas = KasTransaksi::withoutGlobalScopes()->create([
                'rt_id' => (int) $user->rt_id,
                'input_by' => $user->id,
                'jenis' => $validated['jenis'],
                'kategori' => $validated['kategori'],
                'nominal' => (int) $validated['nominal'],
                'keterangan' => $validated['keterangan'] ?? null,
                'tanggal' => $validated['tanggal'],
                'is_koreksi' => false,
                'koreksi_dari_id' => null,
                'catatan_koreksi' => null,
            ]);

            // Catat ke Audit Trail
            AuditLogger::log(
                aksi: AuditAction::KAS_TRANSACTION_CREATED,
                targetType: 'kas_transaksi',
                targetId: $kas->id,
                sebelum: null,
                sesudah: [
                    'id' => $kas->id,
                    'jenis' => $kas->jenis,
                    'kategori' => $kas->kategori,
                    'nominal' => (int) $kas->nominal,
                    'tanggal' => $kas->tanggal->toDateString(),
                    'keterangan' => $kas->keterangan,
                ],
                alasan: null,
                rtId: (int) $user->rt_id,
                actor: $user
            );
        });

        return redirect()->route('kas.index')->with('success', 'Transaksi kas berhasil dicatat.');
    }

    /**
     * Mencatat pembetulan/koreksi transaksi kas lama tanpa mengubah atau menghapus record lama.
     */
    public function koreksi(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();

        // 1. Ambil transaksi yang akan dikoreksi
        $kasLama = KasTransaksi::withoutGlobalScopes()->findOrFail($id);

        // 2. Authorization Guard: pastikan user berhak mengelola kas di RT ini
        abort_unless(ScopeAuthorizer::canManageKas($user, (int) $kasLama->rt_id), 403, 'Anda tidak memiliki wewenang untuk mengoreksi transaksi kas ini.');

        // 3. HARD GUARD: Hanya transaksi leaf / aktif (yang belum pernah dikoreksi lagi) yang boleh dikoreksi
        if ($kasLama->koreksiBerikutnya()->exists()) {
            abort(422, 'Transaksi ini sudah pernah dikoreksi sebelumnya. Koreksi lanjutan hanya dapat dilakukan pada versi transaksi yang paling mutakhir.');
        }

        // 4. Validasi input server-side
        $validated = $request->validate([
            'jenis' => ['required', 'string', 'in:masuk,keluar'],
            'kategori' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($request) {
                    $jenis = $request->input('jenis');
                    if ($jenis === 'masuk' && in_array($value, KasTransaksi::KATEGORI_KELUAR_PRESETS, true)) {
                        $fail('Kategori yang dipilih tidak sesuai untuk jenis transaksi pemasukan.');
                    }
                    if ($jenis === 'keluar' && in_array($value, KasTransaksi::KATEGORI_MASUK_PRESETS, true)) {
                        $fail('Kategori yang dipilih tidak sesuai untuk jenis transaksi pengeluaran.');
                    }
                },
            ],
            'nominal' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'alasan_koreksi' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        // 5. Eksekusi atomik dalam DB::transaction (Old record UNTOUCHED, new record APPENDED)
        DB::transaction(function () use ($validated, $kasLama, $user) {
            // Buat record koreksi baru yang menunjuk ke record lama
            $kasBaru = KasTransaksi::withoutGlobalScopes()->create([
                'rt_id' => (int) $kasLama->rt_id,
                'input_by' => $user->id,
                'jenis' => $validated['jenis'],
                'kategori' => $validated['kategori'],
                'nominal' => (int) $validated['nominal'],
                'keterangan' => $validated['keterangan'] ?? null,
                'tanggal' => $validated['tanggal'],
                'is_koreksi' => true,
                'koreksi_dari_id' => $kasLama->id,
                'catatan_koreksi' => $validated['alasan_koreksi'],
            ]);

            // Catat perubahan ke Audit Trail
            AuditLogger::log(
                aksi: AuditAction::KAS_TRANSACTION_CORRECTED,
                targetType: 'kas_transaksi',
                targetId: $kasBaru->id,
                sebelum: [
                    'id' => $kasLama->id,
                    'jenis' => $kasLama->jenis,
                    'kategori' => $kasLama->kategori,
                    'nominal' => (int) $kasLama->nominal,
                    'tanggal' => $kasLama->tanggal->toDateString(),
                    'keterangan' => $kasLama->keterangan,
                ],
                sesudah: [
                    'id' => $kasBaru->id,
                    'jenis' => $kasBaru->jenis,
                    'kategori' => $kasBaru->kategori,
                    'nominal' => (int) $kasBaru->nominal,
                    'tanggal' => $kasBaru->tanggal->toDateString(),
                    'keterangan' => $kasBaru->keterangan,
                ],
                alasan: $validated['alasan_koreksi'],
                rtId: (int) $kasLama->rt_id,
                actor: $user
            );
        });

        return redirect()->route('kas.index')->with('success', 'Koreksi transaksi kas berhasil disimpan dan dicatat ke audit trail.');
    }
}
