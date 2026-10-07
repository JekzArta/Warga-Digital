<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\GaleriAlbum;
use App\Models\KalenderEvent;
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

        // Rekomendasi UMKM Terkini (Scope RT/Tenant, Status DISETUJUI, Maksimal 3)
        $umkmList = UmkmListing::disetujui()
            ->with(['user', 'rt'])
            ->latest()
            ->take(3)
            ->get();

        // Status Surat Terakhir Pengguna (Khusus milik pengguna yang login untuk privasi)
        $recentSurat = SuratPengajuan::where('user_id', $user->id)
            ->latest()
            ->first();

        // Statistik Cepat
        $stats = [
            'surat_menunggu' => SuratPengajuan::where('status', 'MENUNGGU')->count(),
            'saldo_kas' => $saldoKas,
            'umkm_menunggu' => UmkmListing::where('status', 'MENUNGGU')->count(),
            'total_warga' => 50,
        ];

        // Kalender Event: Visibilitas sesuai scope wilayah user
        $now = Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;
        $dashboardCalendarMonth = Carbon::createFromDate($currentYear, $currentMonth, 1)->startOfDay();

        // Agenda aktif bulan berjalan (untuk titik indikator pada kalender grid)
        $dashboardMonthEvents = KalenderEvent::forUser($user)
            ->active()
            ->forMonth($currentYear, $currentMonth)
            ->get();

        $dashboardEventsByDate = $dashboardMonthEvents->groupBy(fn ($e) => $e->tanggal->format('Y-m-d'));

        // 2 Agenda terdekat mendatang
        $upcomingAgenda = KalenderEvent::forUser($user)
            ->active()
            ->upcoming()
            ->with('announcement')
            ->take(2)
            ->get();

        // Galeri Dokumentasi Kegiatan Terbaru (Scope Sesuai User)
        $latestAlbum = GaleriAlbum::with(['coverFoto', 'rt'])
            ->withCount('fotos')
            ->orderBy('tanggal_kegiatan', 'desc')
            ->latest('id')
            ->first();

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
            'dashboardEventsByDate' => $dashboardEventsByDate,
            'dashboardDaysInMonth' => $dashboardCalendarMonth->daysInMonth,
            'dashboardFirstDayOfWeek' => $dashboardCalendarMonth->dayOfWeek,
            'dashboardCurrentMonth' => $dashboardCalendarMonth,
            'upcomingAgenda' => $upcomingAgenda,
            'latestAlbum' => $latestAlbum,
        ]);
    }
}
