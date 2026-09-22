<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\KasTransaksi;
use App\Models\SuratPengajuan;
use App\Models\UmkmListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard utama sesuai peran pengguna.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Data statistik cepat
        $stats = [
            'surat_menunggu' => 0,
            'saldo_kas' => 0,
            'pengumuman_terbaru' => Announcement::latest()->take(3)->get(),
            'umkm_menunggu' => 0,
        ];

        if ($user->rt_id) {
            $stats['surat_menunggu'] = SuratPengajuan::where('status', 'MENUNGGU')->count();
            $pemasukan = KasTransaksi::where('jenis', 'masuk')->sum('nominal');
            $pengeluaran = KasTransaksi::where('jenis', 'keluar')->sum('nominal');
            $stats['saldo_kas'] = $pemasukan - $pengeluaran;
            $stats['umkm_menunggu'] = UmkmListing::where('status', 'MENUNGGU')->count();
        }

        return view('dashboard', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }
}
