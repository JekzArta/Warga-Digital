<?php

namespace Tests\Feature;

use App\Constants\AuditAction;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\KalenderEvent;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KalenderControllerTest extends TestCase
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

        // Bersihkan data event agar tes deterministik
        KalenderEvent::truncate();
        AuditLog::where('target_type', 'kalender_events')->delete();

        $this->superAdmin = User::where('is_super_admin', true)->first();
        $this->ketuaRw = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rw'))->first();
        $this->ketuaRt = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();
        $this->wakilRt = User::whereHas('roles', fn ($q) => $q->where('role', 'wakil_rt'))->first();
        $this->sekretaris = User::whereHas('roles', fn ($q) => $q->where('role', 'sekretaris'))->first();
        $this->bendahara = User::whereHas('roles', fn ($q) => $q->where('role', 'bendahara'))->first();

        $this->rt05 = $this->ketuaRt->rt;
        $this->rw03 = Rw::first();

        $wargaList = User::where('rt_id', $this->rt05->id)
            ->whereHas('roles', fn ($q) => $q->where('role', 'warga'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('role', ['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'ketua_rw']))
            ->take(2)
            ->get();

        $this->warga1 = $wargaList[0];
        $this->warga2 = $wargaList[1];

        // Buat RT 06 sebagai boundary tenant testing
        $this->rt06 = Rt::create([
            'rw_id' => $this->rt05->rw_id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);
    }

    // =========================================================================
    // 1. READ / INDEX TESTS
    // =========================================================================

    public function test_guest_cannot_access_calendar_redirects_to_login(): void
    {
        $response = $this->get(route('kalender.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authorized_warga_can_open_calendar_index(): void
    {
        $response = $this->actingAs($this->warga1)->get(route('kalender.index'));

        $response->assertStatus(200);
        $response->assertViewIs('kalender.index');
        $response->assertViewHas(['monthEvents', 'upcomingEvents', 'year', 'month', 'canManage']);
        $this->assertFalse($response->viewData('canManage'));
    }

    public function test_month_navigation_and_query_filtering(): void
    {
        // Event di Oktober 2026
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Agenda Oktober RT 05',
            'tanggal' => '2026-10-15',
            'kategori' => KalenderEvent::KATEGORI_KEGIATAN,
            'created_by' => $this->ketuaRt->id,
        ]);

        // Event di November 2026
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Agenda November RT 05',
            'tanggal' => '2026-11-20',
            'kategori' => KalenderEvent::KATEGORI_RAPAT,
            'created_by' => $this->ketuaRt->id,
        ]);

        // Akses Oktober 2026
        $resOct = $this->actingAs($this->warga1)->get(route('kalender.index', ['year' => 2026, 'month' => 10]));
        $resOct->assertStatus(200);
        $monthEventsOct = $resOct->viewData('monthEvents');
        $this->assertTrue($monthEventsOct->contains('judul', 'Agenda Oktober RT 05'));
        $this->assertFalse($monthEventsOct->contains('judul', 'Agenda November RT 05'));

        // Akses November 2026
        $resNov = $this->actingAs($this->warga1)->get(route('kalender.index', ['year' => 2026, 'month' => 11]));
        $resNov->assertStatus(200);
        $monthEventsNov = $resNov->viewData('monthEvents');
        $this->assertTrue($monthEventsNov->contains('judul', 'Agenda November RT 05'));
        $this->assertFalse($monthEventsNov->contains('judul', 'Agenda Oktober RT 05'));

        // Uji respons JSON endpoint
        $resJson = $this->actingAs($this->warga1)->getJson(route('kalender.index', ['year' => 2026, 'month' => 10]));
        $resJson->assertStatus(200);
        $this->assertCount(1, $resJson->json('data.month_events'));
    }

    public function test_scope_switcher_rt_and_rw(): void
    {
        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Acara Khusus RT 05',
            'tanggal' => '2026-10-10',
            'created_by' => $this->ketuaRt->id,
        ]);

        KalenderEvent::create([
            'scope_type' => 'rw',
            'scope_id' => $this->rw03->id,
            'judul' => 'Acara Umum RW 03',
            'tanggal' => '2026-10-12',
            'created_by' => $this->ketuaRw->id,
        ]);

        // Scope RT
        $resRt = $this->actingAs($this->warga1)->get(route('kalender.index', ['scope' => 'rt', 'year' => 2026, 'month' => 10]));
        $resRt->assertSee('Acara Khusus RT 05');
        $resRt->assertDontSee('Acara Umum RW 03');

        // Scope RW
        $resRw = $this->actingAs($this->warga1)->get(route('kalender.index', ['scope' => 'rw', 'year' => 2026, 'month' => 10]));
        $resRw->assertSee('Acara Umum RW 03');
        $resRw->assertDontSee('Acara Khusus RT 05');
    }

    public function test_empty_state_is_displayed_when_no_events(): void
    {
        $response = $this->actingAs($this->warga1)->get(route('kalender.index', ['year' => 2026, 'month' => 10]));
        $response->assertStatus(200);
        $response->assertSee('Belum ada agenda mendatang');
    }

    // =========================================================================
    // 2. CREATE STANDALONE EVENT TESTS
    // =========================================================================

    public function test_authorized_manager_can_create_standalone_event(): void
    {
        $payload = [
            'scope_type' => 'rt',
            'judul' => 'Kerja Bakti Warga RT 05',
            'deskripsi' => 'Mempersiapkan selokan dan taman',
            'tanggal' => '2026-10-18',
            'waktu_mulai' => '07:00',
            'waktu_selesai' => '10:30',
            'lokasi' => 'Balai Warga RT 05',
            'kategori' => 'KEGIATAN',
        ];

        $response = $this->actingAs($this->sekretaris)->post(route('kalender.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kalender_events', [
            'judul' => 'Kerja Bakti Warga RT 05',
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'sumber' => 'manual',
            'announcement_id' => null,
            'kategori' => 'KEGIATAN',
            'waktu_mulai' => '07:00',
            'waktu_selesai' => '10:30',
            'created_by' => $this->sekretaris->id,
        ]);

        // Verifikasi Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::KALENDER_EVENT_CREATED,
            'target_type' => 'kalender_events',
            'user_id' => $this->sekretaris->id,
            'rt_id' => $this->rt05->id,
        ]);
    }

    public function test_warga_and_bendahara_cannot_create_event(): void
    {
        $payload = [
            'scope_type' => 'rt',
            'judul' => 'Agenda Ilegal Warga',
            'tanggal' => '2026-10-20',
            'kategori' => 'KEGIATAN',
        ];

        // Warga dilarang
        $resWarga = $this->actingAs($this->warga1)->post(route('kalender.store'), $payload);
        $resWarga->assertStatus(403);

        // Bendahara dilarang (read-only)
        $resBendahara = $this->actingAs($this->bendahara)->post(route('kalender.store'), $payload);
        $resBendahara->assertStatus(403);

        $this->assertDatabaseMissing('kalender_events', [
            'judul' => 'Agenda Ilegal Warga',
        ]);
    }

    public function test_validation_rules_on_create(): void
    {
        // 1. Missing Judul & Tanggal & Kategori
        $res = $this->actingAs($this->ketuaRt)->post(route('kalender.store'), []);
        $res->assertSessionHasErrors(['judul', 'tanggal', 'kategori']);

        // 2. Kategori Invalid
        $resKat = $this->actingAs($this->ketuaRt)->post(route('kalender.store'), [
            'judul' => 'Uji Kategori',
            'tanggal' => '2026-10-15',
            'kategori' => 'URGENT_INVALID',
        ]);
        $resKat->assertSessionHasErrors(['kategori']);

        // 3. Waktu Selesai < Waktu Mulai
        $resTime = $this->actingAs($this->ketuaRt)->post(route('kalender.store'), [
            'judul' => 'Uji Waktu Salah',
            'tanggal' => '2026-10-15',
            'kategori' => 'RAPAT',
            'waktu_mulai' => '14:00',
            'waktu_selesai' => '10:00', // salah!
        ]);
        $resTime->assertSessionHasErrors(['waktu_selesai']);
    }

    // =========================================================================
    // 3. UPDATE STANDALONE EVENT TESTS
    // =========================================================================

    public function test_authorized_manager_can_update_standalone_event(): void
    {
        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Rapat Awal',
            'tanggal' => '2026-10-15',
            'kategori' => 'RAPAT',
            'sumber' => 'manual',
            'created_by' => $this->ketuaRt->id,
        ]);

        $updatePayload = [
            'judul' => 'Rapat Pleno RT 05 Diperbarui',
            'tanggal' => '2026-10-16',
            'waktu_mulai' => '19:30',
            'waktu_selesai' => '21:00',
            'lokasi' => 'Rumah Pak RT',
            'kategori' => 'RAPAT',
            'deskripsi' => 'Agenda rapat diperluas',
        ];

        $response = $this->actingAs($this->wakilRt)->put(route('kalender.update', $event->id), $updatePayload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kalender_events', [
            'id' => $event->id,
            'judul' => 'Rapat Pleno RT 05 Diperbarui',
            'waktu_mulai' => '19:30',
        ]);
        $event->refresh();
        $this->assertEquals('2026-10-16', $event->tanggal->toDateString());

        // Verifikasi Audit Trail Update
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::KALENDER_EVENT_UPDATED,
            'target_type' => 'kalender_events',
            'target_id' => $event->id,
            'user_id' => $this->wakilRt->id,
        ]);
    }

    public function test_manager_can_mark_event_as_cancelled(): void
    {
        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Senam Pagi Bersama',
            'tanggal' => '2026-10-18',
            'kategori' => 'KEGIATAN',
            'sumber' => 'manual',
            'created_by' => $this->ketuaRt->id,
        ]);

        $response = $this->actingAs($this->ketuaRt)->put(route('kalender.update', $event->id), [
            'judul' => 'Senam Pagi Bersama',
            'tanggal' => '2026-10-18',
            'kategori' => 'KEGIATAN',
            'is_cancelled' => 1,
            'pembatalan_alasan' => 'Lapangan sedang direnovasi',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kalender_events', [
            'id' => $event->id,
            'is_cancelled' => 1,
            'pembatalan_alasan' => 'Lapangan sedang direnovasi',
        ]);

        // Event yang dicancel tidak muncul dalam active upcoming
        $upcoming = KalenderEvent::upcoming()->get();
        $this->assertFalse($upcoming->contains('id', $event->id));
    }

    public function test_cannot_update_linked_announcement_event_via_standalone_crud(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Resmi',
            'konten' => 'Isi',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Pengumuman',
            'tanggal' => '2026-10-20',
            'sumber' => 'announcement',
            'announcement_id' => $announcement->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        $response = $this->actingAs($this->ketuaRt)->put(route('kalender.update', $event->id), [
            'judul' => 'Coba Edit Ilegal',
            'tanggal' => '2026-10-20',
            'kategori' => 'KEGIATAN',
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // 4. DELETE STANDALONE EVENT TESTS
    // =========================================================================

    public function test_authorized_manager_can_delete_standalone_event(): void
    {
        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Dihapus',
            'tanggal' => '2026-10-22',
            'kategori' => 'LAINNYA',
            'sumber' => 'manual',
            'created_by' => $this->sekretaris->id,
        ]);

        $response = $this->actingAs($this->sekretaris)->delete(route('kalender.destroy', $event->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('kalender_events', [
            'id' => $event->id,
        ]);

        // Verifikasi Audit Trail Delete
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::KALENDER_EVENT_DELETED,
            'target_type' => 'kalender_events',
            'target_id' => $event->id,
            'user_id' => $this->sekretaris->id,
        ]);
    }

    public function test_unauthorized_user_cannot_delete_event(): void
    {
        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Terproteksi',
            'tanggal' => '2026-10-22',
            'kategori' => 'LAINNYA',
            'sumber' => 'manual',
            'created_by' => $this->ketuaRt->id,
        ]);

        $resWarga = $this->actingAs($this->warga1)->delete(route('kalender.destroy', $event->id));
        $resWarga->assertStatus(403);

        $resBendahara = $this->actingAs($this->bendahara)->delete(route('kalender.destroy', $event->id));
        $resBendahara->assertStatus(403);

        $this->assertDatabaseHas('kalender_events', ['id' => $event->id]);
    }

    public function test_cannot_delete_linked_announcement_event_via_standalone_crud(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Resmi',
            'konten' => 'Isi',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Pengumuman',
            'tanggal' => '2026-10-20',
            'sumber' => 'announcement',
            'announcement_id' => $announcement->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        $response = $this->actingAs($this->ketuaRt)->delete(route('kalender.destroy', $event->id));
        $response->assertStatus(422);

        $this->assertDatabaseHas('kalender_events', ['id' => $event->id]);
    }

    // =========================================================================
    // 5. TENANT ISOLATION TESTS
    // =========================================================================

    public function test_cross_tenant_manipulation_is_strictly_forbidden(): void
    {
        // Event di RT 06
        $eventRt06 = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt06->id,
            'judul' => 'Event Khusus RT 06',
            'tanggal' => '2026-10-25',
            'sumber' => 'manual',
            'created_by' => $this->superAdmin->id,
        ]);

        // Ketua RT 05 mencoba edit event RT 06 -> 403 Forbidden
        $resEdit = $this->actingAs($this->ketuaRt)->put(route('kalender.update', $eventRt06->id), [
            'judul' => 'Pembajakan Event RT 06',
            'tanggal' => '2026-10-25',
            'kategori' => 'KEGIATAN',
        ]);
        $resEdit->assertStatus(403);

        // Ketua RT 05 mencoba delete event RT 06 -> 403 Forbidden
        $resDelete = $this->actingAs($this->ketuaRt)->delete(route('kalender.destroy', $eventRt06->id));
        $resDelete->assertStatus(403);
    }

    // =========================================================================
    // 6. RW END-TO-END AUTHORIZATION & PAYLOAD INTEGRITY TESTS
    // =========================================================================

    public function test_ketua_rw_can_manage_rw_calendar_end_to_end(): void
    {
        // 1. Create RW Event
        $resCreate = $this->actingAs($this->ketuaRw)->post(route('kalender.store'), [
            'scope_type' => 'rw',
            'judul' => 'Turnamen Catur RW 03',
            'tanggal' => '2026-10-28',
            'waktu_mulai' => '13:00',
            'waktu_selesai' => '17:00',
            'lokasi' => 'Balai Warga RW 03',
            'kategori' => 'KEGIATAN',
        ]);

        $resCreate->assertRedirect();
        $this->assertDatabaseHas('kalender_events', [
            'judul' => 'Turnamen Catur RW 03',
            'scope_type' => 'rw',
            'scope_id' => $this->rw03->id,
            'sumber' => 'manual',
        ]);

        $eventRw = KalenderEvent::where('judul', 'Turnamen Catur RW 03')->first();

        // 2. Update RW Event
        $resUpdate = $this->actingAs($this->ketuaRw)->put(route('kalender.update', $eventRw->id), [
            'judul' => 'Turnamen Catur RW 03 (Jadwal Final)',
            'tanggal' => '2026-10-28',
            'waktu_mulai' => '13:30',
            'kategori' => 'KEGIATAN',
        ]);
        $resUpdate->assertRedirect();
        $this->assertDatabaseHas('kalender_events', [
            'id' => $eventRw->id,
            'judul' => 'Turnamen Catur RW 03 (Jadwal Final)',
        ]);

        // 3. Delete RW Event
        $resDelete = $this->actingAs($this->ketuaRw)->delete(route('kalender.destroy', $eventRw->id));
        $resDelete->assertRedirect();
        $this->assertDatabaseMissing('kalender_events', [
            'id' => $eventRw->id,
        ]);
    }

    public function test_pengurus_rt_cannot_manage_rw_events(): void
    {
        // 1. Ketua RT dilarang membuat event RW
        $resCreate = $this->actingAs($this->ketuaRt)->post(route('kalender.store'), [
            'scope_type' => 'rw',
            'judul' => 'Event RW Ilegal Oleh RT',
            'tanggal' => '2026-10-28',
            'kategori' => 'KEGIATAN',
        ]);
        $resCreate->assertStatus(403);

        // 2. Ketua RT dilarang mengubah event RW yang sudah ada
        $eventRw = KalenderEvent::create([
            'scope_type' => 'rw',
            'scope_id' => $this->rw03->id,
            'judul' => 'Musyawarah RW 03',
            'tanggal' => '2026-10-29',
            'kategori' => 'RAPAT',
            'sumber' => 'manual',
            'created_by' => $this->ketuaRw->id,
        ]);

        $resUpdate = $this->actingAs($this->ketuaRt)->put(route('kalender.update', $eventRw->id), [
            'judul' => 'Coba Edit RW',
            'tanggal' => '2026-10-29',
            'kategori' => 'RAPAT',
        ]);
        $resUpdate->assertStatus(403);

        // 3. Ketua RT dilarang menghapus event RW
        $resDelete = $this->actingAs($this->ketuaRt)->delete(route('kalender.destroy', $eventRw->id));
        $resDelete->assertStatus(403);
    }

    public function test_tampering_source_or_announcement_id_is_strictly_ignored_on_standalone_create(): void
    {
        // Injeksi parameter terlarang pada payload create
        $payload = [
            'scope_type' => 'rt',
            'judul' => 'Event Manipulasi Sumber',
            'tanggal' => '2026-10-20',
            'kategori' => 'KEGIATAN',
            'sumber' => 'announcement',       // Mencoba bypass sumber manual
            'announcement_id' => 99999,        // Mencoba bypass relasi
            'created_by' => 99999,             // Mencoba spoofing user
        ];

        $response = $this->actingAs($this->ketuaRt)->post(route('kalender.store'), $payload);
        $response->assertRedirect();

        // Server harus memaksakan sumber = manual, announcement_id = null, dan created_by = actor autentikasi
        $this->assertDatabaseHas('kalender_events', [
            'judul' => 'Event Manipulasi Sumber',
            'sumber' => 'manual',
            'announcement_id' => null,
            'created_by' => $this->ketuaRt->id,
        ]);
    }
}
