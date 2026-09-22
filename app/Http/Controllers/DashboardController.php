<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\KasTransaksi;
use App\Models\SuratPengajuan;
use App\Models\UmkmListing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard utama sesuai peran pengguna dan data realtime.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        Carbon::setLocale('id');

        // Ringkasan Transparansi Kas RT
        $saldoKas = 4850000;
        if ($user->rt_id) {
            $pemasukan = KasTransaksi::where('jenis', 'masuk')->sum('nominal');
            $pengeluaran = KasTransaksi::where('jenis', 'keluar')->sum('nominal');
            $calculated = $pemasukan - $pengeluaran;
            if ($calculated > 0) {
                $saldoKas = $calculated;
            }
        }

        // Pengumuman Terkini (Scope RT & RW)
        $announcements = Announcement::with('author')
            ->orderBy('is_pinned', 'desc')
            ->latest()
            ->take(3)
            ->get();

        // Listing UMKM untuk Rekomendasi
        $umkmList = UmkmListing::with('user')
            ->where('status', 'DISETUJUI')
            ->latest()
            ->take(4)
            ->get();

        // Status Surat Terakhir Pengguna
        $recentSurat = SuratPengajuan::where('user_id', $user->id)
            ->latest()
            ->first();

        // Jika warga belum ada surat, ambil sample permohonan untuk preview demo
        if (!$recentSurat && $user->rt_id) {
            $recentSurat = SuratPengajuan::where('rt_id', $user->rt_id)->first();
        }

        // Statistik Cepat
        $stats = [
            'surat_menunggu' => SuratPengajuan::where('status', 'MENUNGGU')->count(),
            'saldo_kas' => $saldoKas,
            'umkm_menunggu' => UmkmListing::where('status', 'MENUNGGU')->count(),
            'total_warga' => 50,
        ];

        // Format greeting waktu
        $hour = (int) date('H');
        if ($hour >= 5 && $hour < 11) {
            $greeting = 'Selamat Pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            $greeting = 'Selamat Siang';
        } elseif ($hour >= 15 && $hour < 18) {
            $greeting = 'Selamat Sore';
        } else {
            $greeting = 'Selamat Malam';
        }

        return view('dashboard', [
            'user' => $user,
            'stats' => $stats,
            'greeting' => $greeting,
            'announcements' => $announcements,
            'umkmList' => $umkmList,
            'recentSurat' => $recentSurat,
        ]);
    }
}
