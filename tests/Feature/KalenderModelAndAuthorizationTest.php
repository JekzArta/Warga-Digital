<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\KalenderEvent;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Services\ScopeAuthorizer;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KalenderModelAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $ketuaRw;
    protected User $ketuaRt;
    protected User $wakilRt;
    protected User $sekretaris;
    protected User $bendahara;
    protected User $warga1;
    protected User $warga2;
    protected Rt $rt05;
    protected Rt $rt06;
    protected Rw $rw03;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        // Bersihkan data event seeder agar tes terisolasi dan deterministik
        KalenderEvent::truncate();

        $this->superAdmin = User::where('is_super_admin', true)->first();
        $this->ketuaRw = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rw'))->first();
        $this->ketuaRt = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();
        $this->wakilRt = User::whereHas('roles', fn ($q) => $q->where('role', 'wakil_rt'))->first();
        $this->sekretaris = User::whereHas('roles', fn ($q) => $q->where('role', 'sekretaris'))->first();
        $this->bendahara = User::whereHas('roles', fn ($q) => $q->where('role', 'bendahara'))->first();

        $this->rt05 = $this->ketuaRt->rt;
        $this->rw03 = Rw::first();

        // Ambil warga aktif di RT 05
        $wargaList = User::where('rt_id', $this->rt05->id)
            ->whereHas('roles', fn ($q) => $q->where('role', 'warga'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('role', ['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'ketua_rw']))
            ->take(2)
            ->get();

        $this->warga1 = $wargaList[0];
        $this->warga2 = $wargaList[1];

        // Buat RT 06 sebagai boundary testing isolasi tenant
        $this->rt06 = Rt::create([
            'rw_id' => $this->rt05->rw_id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);
    }

    // =========================================================================
    // 1. MODEL, ATTRIBUTES & CASTING
    // =========================================================================

    public function test_kalender_event_can_be_created_with_complete_attributes(): void
    {
        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Kerja Bakti Lapangan RT',
            'deskripsi' => 'Mempersiapkan lapangan untuk perlombaan 17 Agustus',
            'tanggal' => '2026-10-15',
            'waktu_mulai' => '07:30',
            'waktu_selesai' => '11:00',
            'lokasi' => 'Lapangan Voli RT 05',
            'kategori' => KalenderEvent::KATEGORI_KEGIATAN,
            'sumber' => KalenderEvent::SUMBER_MANUAL,
            'announcement_id' => null,
            'is_cancelled' => false,
            'pembatalan_alasan' => null,
            'created_by' => $this->sekretaris->id,
        ]);

        $this->assertDatabaseHas('kalender_events', [
            'id' => $event->id,
            'judul' => 'Kerja Bakti Lapangan RT',
            'waktu_mulai' => '07:30',
            'waktu_selesai' => '11:00',
            'lokasi' => 'Lapangan Voli RT 05',
            'kategori' => 'KEGIATAN',
            'is_cancelled' => 0,
        ]);

        // Assert casting
        $this->assertInstanceOf(Carbon::class, $event->tanggal);
        $this->assertEquals('2026-10-15', $event->tanggal->toDateString());
        $this->assertIsBool($event->is_cancelled);
        $this->assertFalse($event->is_cancelled);
    }

    public function test_kalender_event_relations(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Posyandu Balita',
            'konten' => 'Pelayanan posyandu balita rutin bulanan',
            'tipe' => 'INFO',
            'is_pinned' => false,
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Posyandu Balita Rutin',
            'tanggal' => '2026-10-20',
            'kategori' => KalenderEvent::KATEGORI_POSYANDU,
            'sumber' => KalenderEvent::SUMBER_ANNOUNCEMENT,
            'announcement_id' => $announcement->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        // Test belongsTo creator
        $this->assertInstanceOf(User::class, $event->creator);
        $this->assertEquals($this->ketuaRt->id, $event->creator->id);

        // Test belongsTo announcement
        $this->assertInstanceOf(Announcement::class, $event->announcement);
        $this->assertEquals($announcement->id, $event->announcement->id);

        // Test Announcement hasOne kalenderEvent
        $this->assertInstanceOf(KalenderEvent::class, $announcement->kalenderEvent);
        $this->assertEquals($event->id, $announcement->kalenderEvent->id);
    }

    // =========================================================================
    // 2. SCOPES (FOR_SCOPE, ACTIVE, UPCOMING, FOR_MONTH, KATEGORI)
    // =========================================================================

    public function test_scope_for_scope_isolates_rt_and_rw_events(): void
    {
        // Event RT 05
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Rapat RT 05',
            'tanggal' => '2026-10-10',
            'created_by' => $this->ketuaRt->id,
        ]);

        // Event RT 06
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt06->id,
            'judul' => 'Rapat RT 06',
            'tanggal' => '2026-10-10',
            'created_by' => $this->superAdmin->id,
        ]);

        // Event RW 03
        KalenderEvent::create([
            'scope_type' => 'rw',
            'scope_id' => $this->rw03->id,
            'judul' => 'Turnamen Olahraga RW 03',
            'tanggal' => '2026-10-10',
            'created_by' => $this->ketuaRw->id,
        ]);

        $rt05Events = KalenderEvent::forScope('rt', $this->rt05->id)->get();
        $this->assertCount(1, $rt05Events);
        $this->assertEquals('Rapat RT 05', $rt05Events->first()->judul);

        $rt06Events = KalenderEvent::forScope('rt', $this->rt06->id)->get();
        $this->assertCount(1, $rt06Events);
        $this->assertEquals('Rapat RT 06', $rt06Events->first()->judul);

        $rwEvents = KalenderEvent::forScope('rw', $this->rw03->id)->get();
        $this->assertCount(1, $rwEvents);
        $this->assertEquals('Turnamen Olahraga RW 03', $rwEvents->first()->judul);
    }

    public function test_scope_active_excludes_explicitly_cancelled_events(): void
    {
        // Event aktif
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Kegiatan Aktif',
            'tanggal' => '2026-10-15',
            'is_cancelled' => false,
            'created_by' => $this->ketuaRt->id,
        ]);

        // Event dibatalkan
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Kegiatan Dibatalkan',
            'tanggal' => '2026-10-15',
            'is_cancelled' => true,
            'pembatalan_alasan' => 'Hujan badai',
            'created_by' => $this->ketuaRt->id,
        ]);

        $activeEvents = KalenderEvent::active()->get();
        $this->assertCount(1, $activeEvents);
        $this->assertEquals('Kegiatan Aktif', $activeEvents->first()->judul);
    }

    public function test_scope_active_excludes_events_linked_to_deactivated_announcements(): void
    {
        $announcementDeactivated = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Ditarik',
            'konten' => 'Pengumuman ini ditarik oleh RT',
            'tipe' => 'INFO',
            'is_deactivated' => true,
            'is_replaced' => false,
        ]);

        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Dari Pengumuman Nonaktif',
            'tanggal' => '2026-10-25',
            'sumber' => KalenderEvent::SUMBER_ANNOUNCEMENT,
            'announcement_id' => $announcementDeactivated->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        $announcementActive = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Aktif',
            'konten' => 'Pengumuman valid',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Dari Pengumuman Aktif',
            'tanggal' => '2026-10-25',
            'sumber' => KalenderEvent::SUMBER_ANNOUNCEMENT,
            'announcement_id' => $announcementActive->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        $activeEvents = KalenderEvent::active()->get();
        $this->assertCount(1, $activeEvents);
        $this->assertEquals('Event Dari Pengumuman Aktif', $activeEvents->first()->judul);
    }

    public function test_scope_active_excludes_events_linked_to_replaced_announcements(): void
    {
        $announcementReplaced = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman V1 Digantikan',
            'konten' => 'Pengumuman versi lama',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => true,
        ]);

        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Dari Pengumuman Versi Lama',
            'tanggal' => '2026-10-25',
            'sumber' => KalenderEvent::SUMBER_ANNOUNCEMENT,
            'announcement_id' => $announcementReplaced->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        $activeEvents = KalenderEvent::active()->get();
        $this->assertCount(0, $activeEvents);
    }

    public function test_scope_upcoming_filters_past_events_and_orders_by_date_and_time(): void
    {
        Carbon::setTestNow('2026-10-05 10:00:00');

        // Kemarin (harus tidak masuk)
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Kemarin',
            'tanggal' => '2026-10-04',
            'created_by' => $this->ketuaRt->id,
        ]);

        // Hari ini jam 15:00
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Hari Ini Sore',
            'tanggal' => '2026-10-05',
            'waktu_mulai' => '15:00',
            'created_by' => $this->ketuaRt->id,
        ]);

        // Hari ini jam 08:00
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Hari Ini Pagi',
            'tanggal' => '2026-10-05',
            'waktu_mulai' => '08:00',
            'created_by' => $this->ketuaRt->id,
        ]);

        // Besok
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Besok',
            'tanggal' => '2026-10-06',
            'waktu_mulai' => '09:00',
            'created_by' => $this->ketuaRt->id,
        ]);

        $upcoming = KalenderEvent::upcoming()->get();

        $this->assertCount(3, $upcoming);
        // Pastikan urut kronologis: Hari Ini Pagi -> Hari Ini Sore -> Besok
        $this->assertEquals('Event Hari Ini Pagi', $upcoming[0]->judul);
        $this->assertEquals('Event Hari Ini Sore', $upcoming[1]->judul);
        $this->assertEquals('Event Besok', $upcoming[2]->judul);

        Carbon::setTestNow(); // reset
    }

    public function test_scope_for_month_and_kategori(): void
    {
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Acara Oktober 1',
            'tanggal' => '2026-10-05',
            'kategori' => KalenderEvent::KATEGORI_RAPAT,
            'created_by' => $this->ketuaRt->id,
        ]);

        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Acara Oktober 2',
            'tanggal' => '2026-10-25',
            'kategori' => KalenderEvent::KATEGORI_POSYANDU,
            'created_by' => $this->ketuaRt->id,
        ]);

        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Acara November',
            'tanggal' => '2026-11-10',
            'kategori' => KalenderEvent::KATEGORI_RAPAT,
            'created_by' => $this->ketuaRt->id,
        ]);

        $octoberEvents = KalenderEvent::forMonth(2026, 10)->get();
        $this->assertCount(2, $octoberEvents);

        $rapatEvents = KalenderEvent::kategori('RAPAT')->get();
        $this->assertCount(2, $rapatEvents);
    }

    // =========================================================================
    // 3. ACCESSORS & HELPERS
    // =========================================================================

    public function test_model_helpers_and_accessors(): void
    {
        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Kerja Bakti',
            'tanggal' => '2026-10-15',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '11:00',
            'kategori' => KalenderEvent::KATEGORI_KEGIATAN,
            'sumber' => KalenderEvent::SUMBER_MANUAL,
            'created_by' => $this->ketuaRt->id,
        ]);

        $this->assertEquals('08:00 - 11:00 WIB', $event->formatted_waktu);
        $this->assertEquals('Kegiatan Warga', $event->kategori_label);
        $this->assertStringContainsString('emerald', $event->kategori_badge_class);
        $this->assertEquals('bg-emerald-500', $event->kategori_dot_class);
        $this->assertFalse($event->is_linked_to_announcement);
        $this->assertEquals('Agenda RT', $event->source_badge_label);

        // Uji jika hanya ada waktu_mulai
        $event->waktu_selesai = null;
        $this->assertEquals('08:00 WIB', $event->formatted_waktu);

        // Uji jika waktu fleksibel
        $event->waktu_mulai = null;
        $this->assertEquals('Sepanjang hari', $event->formatted_waktu);

        // Uji badge pengumuman
        $event->sumber = KalenderEvent::SUMBER_ANNOUNCEMENT;
        $event->announcement_id = 999;
        $this->assertTrue($event->is_linked_to_announcement);
        $this->assertEquals('Dari Pengumuman', $event->source_badge_label);
    }

    // =========================================================================
    // 4. DATABASE CONSTRAINTS (ANTI-DUPLICATION)
    // =========================================================================

    public function test_unique_constraint_on_announcement_id_prevents_duplicate_calendar_events(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Kerja Bakti',
            'konten' => 'Isi pengumuman',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        // Event pertama sukses
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Kerja Bakti Event 1',
            'tanggal' => '2026-10-15',
            'sumber' => KalenderEvent::SUMBER_ANNOUNCEMENT,
            'announcement_id' => $announcement->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        // Event kedua dengan announcement_id yang sama harus melempar exception QueryException
        $this->expectException(QueryException::class);

        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Kerja Bakti Event 2 (Duplikat Terlarang)',
            'tanggal' => '2026-10-15',
            'sumber' => KalenderEvent::SUMBER_ANNOUNCEMENT,
            'announcement_id' => $announcement->id,
            'created_by' => $this->ketuaRt->id,
        ]);
    }

    public function test_multiple_standalone_events_can_have_null_announcement_id(): void
    {
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Standalone Event 1',
            'tanggal' => '2026-10-15',
            'announcement_id' => null,
            'created_by' => $this->ketuaRt->id,
        ]);

        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Standalone Event 2',
            'tanggal' => '2026-10-15',
            'announcement_id' => null,
            'created_by' => $this->ketuaRt->id,
        ]);

        $this->assertCount(2, KalenderEvent::all());
    }

    // =========================================================================
    // 5. SCOPE AUTHORIZER & RBAC
    // =========================================================================

    public function test_can_view_kalender_authorization(): void
    {
        // 1. Warga RT 05 boleh melihat kalender RT 05
        $this->assertTrue(ScopeAuthorizer::canViewKalender($this->warga1, 'rt', $this->rt05->id));

        // 2. Warga RT 05 boleh melihat kalender RW 03
        $this->assertTrue(ScopeAuthorizer::canViewKalender($this->warga1, 'rw', $this->rw03->id));

        // 3. Warga RT 05 DILARANG melihat kalender RT 06 (lintas-tenant)
        $this->assertFalse(ScopeAuthorizer::canViewKalender($this->warga1, 'rt', $this->rt06->id));

        // 4. Ketua RW boleh melihat kalender RW dan RT di bawahnya (RT 05 dan RT 06)
        $this->assertTrue(ScopeAuthorizer::canViewKalender($this->ketuaRw, 'rw', $this->rw03->id));
        $this->assertTrue(ScopeAuthorizer::canViewKalender($this->ketuaRw, 'rt', $this->rt05->id));
        $this->assertTrue(ScopeAuthorizer::canViewKalender($this->ketuaRw, 'rt', $this->rt06->id));

        // 5. Super admin boleh melihat semua
        $this->assertTrue(ScopeAuthorizer::canViewKalender($this->superAdmin, 'rt', $this->rt05->id));
        $this->assertTrue(ScopeAuthorizer::canViewKalender($this->superAdmin, 'rt', $this->rt06->id));
        $this->assertTrue(ScopeAuthorizer::canViewKalender($this->superAdmin, 'rw', $this->rw03->id));
    }

    public function test_can_manage_kalender_authorization(): void
    {
        // 1. Pengurus RT (Ketua, Wakil, Sekretaris) boleh kelola kalender RT 05
        $this->assertTrue(ScopeAuthorizer::canManageKalender($this->ketuaRt, 'rt', $this->rt05->id));
        $this->assertTrue(ScopeAuthorizer::canManageKalender($this->wakilRt, 'rt', $this->rt05->id));
        $this->assertTrue(ScopeAuthorizer::canManageKalender($this->sekretaris, 'rt', $this->rt05->id));

        // 2. Warga dan Bendahara DILARANG kelola kalender RT (hanya read-only)
        $this->assertFalse(ScopeAuthorizer::canManageKalender($this->warga1, 'rt', $this->rt05->id));
        $this->assertFalse(ScopeAuthorizer::canManageKalender($this->bendahara, 'rt', $this->rt05->id));

        // 3. Pengurus RT DILARANG kelola kalender RT lain (RT 06)
        $this->assertFalse(ScopeAuthorizer::canManageKalender($this->ketuaRt, 'rt', $this->rt06->id));

        // 4. Pengurus RT DILARANG kelola kalender RW
        $this->assertFalse(ScopeAuthorizer::canManageKalender($this->ketuaRt, 'rw', $this->rw03->id));

        // 5. Ketua RW boleh kelola kalender RW
        $this->assertTrue(ScopeAuthorizer::canManageKalender($this->ketuaRw, 'rw', $this->rw03->id));

        // 6. Ketua RW DILARANG kelola kalender operasional RT secara langsung (bukan scope ketua RT)
        $this->assertFalse(ScopeAuthorizer::canManageKalender($this->ketuaRw, 'rt', $this->rt05->id));

        // 7. Super Admin boleh kelola semua
        $this->assertTrue(ScopeAuthorizer::canManageKalender($this->superAdmin, 'rt', $this->rt05->id));
        $this->assertTrue(ScopeAuthorizer::canManageKalender($this->superAdmin, 'rw', $this->rw03->id));
    }
}
