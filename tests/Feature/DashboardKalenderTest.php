<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\KalenderEvent;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardKalenderTest extends TestCase
{
    use RefreshDatabase;

    protected User $wargaRt05;
    protected User $ketuaRt05;
    protected User $ketuaRw03;
    protected Rt $rt05;
    protected Rt $rt06;
    protected Rw $rw03;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        // Bersihkan data event agar deterministik
        KalenderEvent::truncate();

        $this->ketuaRt05 = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();
        $this->ketuaRw03 = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rw'))->first();
        $this->rt05 = $this->ketuaRt05->rt;
        $this->rw03 = Rw::first();

        $this->wargaRt05 = User::where('rt_id', $this->rt05->id)
            ->whereHas('roles', fn ($q) => $q->where('role', 'warga'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('role', ['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'ketua_rw']))
            ->first();

        // Buat RT 06 sebagai tenant lain
        $this->rt06 = Rt::firstOrCreate(
            ['nomor_rt' => '06', 'rw_id' => $this->rw03->id],
            ['nama_rt' => 'RT 06', 'kode_rt' => 'RT06']
        );
    }

    /**
     * Dashboard merender widget Kalender Event dengan data bulan berjalan.
     */
    public function test_dashboard_renders_calendar_widget_with_month_data(): void
    {
        // Buat satu event di bulan sekarang
        KalenderEvent::create([
            'judul' => 'Kerja Bakti Akbar RT 05',
            'deskripsi' => 'Bersih-bersih saluran air',
            'kategori' => KalenderEvent::KATEGORI_KEGIATAN,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:30',
            'waktu_selesai' => '10:00',
            'lokasi' => 'Balai RT 05',
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'sumber' => KalenderEvent::SUMBER_MANUAL,
            'created_by' => $this->ketuaRt05->id,
        ]);

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Kalender Event');
        $response->assertSee(now()->translatedFormat('F Y'));
        $response->assertSee('Buka Kalender');
        $response->assertSee(route('kalender.index'));
        $response->assertSee('Kerja Bakti Akbar RT 05');
    }

    /**
     * Upcoming agenda di dashboard bersumber dari canonical KalenderEvent.
     */
    public function test_dashboard_upcoming_agenda_uses_calendar_event(): void
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        KalenderEvent::create([
            'judul' => 'Rapat Pengurus RT 05',
            'kategori' => KalenderEvent::KATEGORI_RAPAT,
            'tanggal' => $today,
            'waktu_mulai' => '19:30',
            'lokasi' => 'Rumah Pak RT',
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'sumber' => KalenderEvent::SUMBER_MANUAL,
            'created_by' => $this->ketuaRt05->id,
        ]);

        KalenderEvent::create([
            'judul' => 'Posyandu Melati',
            'kategori' => KalenderEvent::KATEGORI_POSYANDU,
            'tanggal' => $tomorrow,
            'waktu_mulai' => '08:30',
            'lokasi' => 'Posyandu RT 05',
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'sumber' => KalenderEvent::SUMBER_MANUAL,
            'created_by' => $this->ketuaRt05->id,
        ]);

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Rapat Pengurus RT 05');
        $response->assertSee('Posyandu Melati');
        $response->assertSee('Rapat Warga');
        $response->assertSee('Posyandu');
    }

    /**
     * Dashboard menghormati isolasi tenant/scope (RT 05 tidak melihat agenda RT 06).
     */
    public function test_dashboard_respects_tenant_scope_isolation(): void
    {
        // Event RT 05 (visible to warga RT 05)
        KalenderEvent::create([
            'judul' => 'Agenda Internal RT 05',
            'kategori' => KalenderEvent::KATEGORI_KEGIATAN,
            'tanggal' => now()->addDay()->toDateString(),
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'sumber' => KalenderEvent::SUMBER_MANUAL,
            'created_by' => $this->ketuaRt05->id,
        ]);

        // Event RW 03 (visible to all RTs in RW 03)
        KalenderEvent::create([
            'judul' => 'Jalan Santai RW 03',
            'kategori' => KalenderEvent::KATEGORI_KEGIATAN,
            'tanggal' => now()->addDays(2)->toDateString(),
            'scope_type' => 'rw',
            'scope_id' => $this->rw03->id,
            'sumber' => KalenderEvent::SUMBER_MANUAL,
            'created_by' => $this->ketuaRw03->id,
        ]);

        // Event RT 06 (DIFFERENT RT: must NOT be visible to warga RT 05)
        KalenderEvent::create([
            'judul' => 'Rahasia Warga RT 06',
            'kategori' => KalenderEvent::KATEGORI_RAPAT,
            'tanggal' => now()->addDay()->toDateString(),
            'scope_type' => 'rt',
            'scope_id' => $this->rt06->id,
            'sumber' => KalenderEvent::SUMBER_MANUAL,
            'created_by' => $this->ketuaRt05->id,
        ]);

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Agenda Internal RT 05');
        $response->assertSee('Jalan Santai RW 03');
        $response->assertDontSee('Rahasia Warga RT 06');
    }

    /**
     * Dashboard mengecualikan agenda yang dibatalkan (is_cancelled = true).
     */
    public function test_dashboard_excludes_cancelled_events(): void
    {
        KalenderEvent::create([
            'judul' => 'Lomba Memancing Yang Dibatalkan',
            'kategori' => KalenderEvent::KATEGORI_KEGIATAN,
            'tanggal' => now()->addDay()->toDateString(),
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'sumber' => KalenderEvent::SUMBER_MANUAL,
            'created_by' => $this->ketuaRt05->id,
            'is_cancelled' => true,
            'pembatalan_alasan' => 'Kolam kering',
        ]);

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Lomba Memancing Yang Dibatalkan');
    }

    /**
     * Dashboard mengecualikan agenda dari pengumuman yang di-deactivate atau replaced.
     */
    public function test_dashboard_excludes_inactive_announcement_events(): void
    {
        $announcement = Announcement::create([
            'judul' => 'Pengumuman Deactivated',
            'konten' => 'Isi pengumuman yang tidak aktif',
            'author_id' => $this->ketuaRt05->id,
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'tipe' => 'INFO',
            'is_deactivated' => true,
        ]);

        KalenderEvent::create([
            'judul' => 'Agenda Dari Pengumuman Deactivated',
            'kategori' => KalenderEvent::KATEGORI_KEGIATAN,
            'tanggal' => now()->addDay()->toDateString(),
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'sumber' => KalenderEvent::SUMBER_ANNOUNCEMENT,
            'announcement_id' => $announcement->id,
            'created_by' => $this->ketuaRt05->id,
        ]);

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Agenda Dari Pengumuman Deactivated');
    }

    /**
     * Dashboard menampilkan empty state yang natural jika tidak ada agenda mendatang.
     */
    public function test_dashboard_empty_state_when_no_upcoming_agenda(): void
    {
        // Pastikan tidak ada event sama sekali
        KalenderEvent::truncate();

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Belum ada agenda mendatang');
        // Pastikan mockup hardcoded lama sudah bersih dan tidak muncul
        $response->assertDontSee('Upacara & Lomba Kemerdekaan RT 05');
        $response->assertDontSee('Pelayanan Posyandu Balita & Lansia');
    }

    /**
     * Sidebar memiliki link Kalender yang mengarah ke route('kalender.index').
     */
    public function test_sidebar_contains_kalender_link_pointing_to_route(): void
    {
        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('href="' . route('kalender.index') . '"', false);
        $response->assertDontSee('href="#kalender"', false);
    }

    /**
     * Sidebar menandai active state ketika user berada di route kalender.*.
     */
    public function test_sidebar_highlights_active_state_on_kalender_route(): void
    {
        $response = $this->actingAs($this->wargaRt05)->get(route('kalender.index'));

        $response->assertOk();
        // Active item class in sidebar app layout: bg-white/10 text-white font-semibold shadow-xs
        $response->assertSee('bg-white/10 text-white font-semibold shadow-xs');
    }
}
