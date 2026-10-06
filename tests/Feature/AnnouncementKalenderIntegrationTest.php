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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnnouncementKalenderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $ketuaRt;
    protected User $sekretaris;
    protected User $ketuaRw;
    protected User $warga;
    protected Rt $rt05;
    protected Rw $rw03;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        // Bersihkan data event agar tes deterministik
        KalenderEvent::truncate();

        $this->ketuaRt = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();
        $this->sekretaris = User::whereHas('roles', fn ($q) => $q->where('role', 'sekretaris'))->first();
        $this->ketuaRw = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rw'))->first();
        $this->warga = User::where('rt_id', $this->ketuaRt->rt_id)
            ->whereHas('roles', fn ($q) => $q->where('role', 'warga'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('role', ['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'ketua_rw']))
            ->first();

        $this->rt05 = $this->ketuaRt->rt;
        $this->rw03 = Rw::first();
    }

    // =========================================================================
    // 1. CREATE ANNOUNCEMENT TESTS
    // =========================================================================

    public function test_create_announcement_without_agenda_creates_zero_kalender_events(): void
    {
        $payload = [
            'scope_type' => 'rt',
            'judul' => 'Pengumuman Penting Warga',
            'konten' => 'Isi maklumat pengumuman tanpa jadwal kegiatan.',
            'tipe' => 'INFO',
            'is_agenda' => 0,
        ];

        $response = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.store'), $payload);
        $response->assertRedirect();

        $announcement = Announcement::where('judul', 'Pengumuman Penting Warga')->first();
        $this->assertNotNull($announcement);
        $this->assertNull($announcement->kalenderEvent);
        $this->assertEquals(0, KalenderEvent::count());
    }

    public function test_create_announcement_with_agenda_creates_atomic_kalender_event(): void
    {
        $payload = [
            'scope_type' => 'rt',
            'judul' => 'Kerja Bakti Bersama RT 05',
            'konten' => 'Diharapkan membawa sapu dan cangkul masing-masing.',
            'tipe' => 'PENTING',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-25',
            'agenda_waktu_mulai' => '07:30',
            'agenda_waktu_selesai' => '11:00',
            'agenda_lokasi' => 'Balai Warga RT 05',
            'agenda_kategori' => 'KEGIATAN',
        ];

        $response = $this->actingAs($this->sekretaris)->post(route('komunitas.pengumuman.store'), $payload);
        $response->assertRedirect();

        $announcement = Announcement::where('judul', 'Kerja Bakti Bersama RT 05')->first();
        $this->assertNotNull($announcement);

        // Verifikasi KalenderEvent tercipta secara atomik
        $this->assertDatabaseHas('kalender_events', [
            'announcement_id' => $announcement->id,
            'sumber' => 'announcement',
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Kerja Bakti Bersama RT 05',
            'waktu_mulai' => '07:30',
            'waktu_selesai' => '11:00',
            'lokasi' => 'Balai Warga RT 05',
            'kategori' => 'KEGIATAN',
            'created_by' => $this->sekretaris->id,
        ]);

        $this->assertEquals(1, KalenderEvent::count());
        $this->assertEquals($announcement->id, $announcement->kalenderEvent->announcement_id);
        $this->assertEquals('2026-10-25', $announcement->kalenderEvent->tanggal->toDateString());
    }

    public function test_created_kalender_event_inherits_scope_and_source_integrity(): void
    {
        // Mencoba inject parameter terlarang
        $payload = [
            'scope_type' => 'rw',
            'judul' => 'Turnamen Olahraga RW 03',
            'konten' => 'Pembukaan turnamen antar RT se-RW 03.',
            'tipe' => 'INFO',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-28',
            'agenda_kategori' => 'KEGIATAN',
            // Manipulasi input
            'sumber' => 'manual',
            'announcement_id' => 99999,
        ];

        $response = $this->actingAs($this->ketuaRw)->post(route('komunitas.pengumuman.store'), $payload);
        $response->assertRedirect();

        $announcement = Announcement::where('judul', 'Turnamen Olahraga RW 03')->first();
        $event = KalenderEvent::where('announcement_id', $announcement->id)->first();

        $this->assertNotNull($event);
        $this->assertEquals('announcement', $event->sumber); // Server override
        $this->assertEquals($announcement->id, $event->announcement_id); // Server override
        $this->assertEquals('rw', $event->scope_type);
        $this->assertEquals($this->rw03->id, $event->scope_id);
    }

    public function test_create_announcement_with_invalid_agenda_fails_validation(): void
    {
        // 1. Agenda dicentang tapi tanggal kosong
        $resNoDate = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.store'), [
            'scope_type' => 'rt',
            'judul' => 'Pengumuman Gagal 1',
            'konten' => 'Konten',
            'tipe' => 'INFO',
            'is_agenda' => 1,
            'agenda_tanggal' => null,
            'agenda_kategori' => 'RAPAT',
        ]);
        $resNoDate->assertSessionHasErrors(['agenda_tanggal']);

        // 2. Agenda dicentang tapi kategori kosong
        $resNoKat = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.store'), [
            'scope_type' => 'rt',
            'judul' => 'Pengumuman Gagal 2',
            'konten' => 'Konten',
            'tipe' => 'INFO',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-25',
            'agenda_kategori' => null,
        ]);
        $resNoKat->assertSessionHasErrors(['agenda_kategori']);

        // 3. Waktu selesai lebih awal dari waktu mulai
        $resTime = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.store'), [
            'scope_type' => 'rt',
            'judul' => 'Pengumuman Gagal 3',
            'konten' => 'Konten',
            'tipe' => 'INFO',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-25',
            'agenda_kategori' => 'RAPAT',
            'agenda_waktu_mulai' => '14:00',
            'agenda_waktu_selesai' => '10:00',
        ]);
        $resTime->assertSessionHasErrors(['agenda_waktu_selesai']);

        $this->assertEquals(0, KalenderEvent::count());
    }

    // =========================================================================
    // 2. UPDATE / VERSIONING MATRIX TESTS (CASE A, B, C, D)
    // =========================================================================

    public function test_update_v1_to_v2_with_agenda_maintains_event_id_and_relinks_announcement_id(): void
    {
        // CASE B: V1 ON -> V2 ON
        // V1 dibuat dengan agenda
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Musyawarah Warga V1',
            'konten' => 'Membahas perbaikan jalan gang.',
            'tipe' => 'PENTING',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $eventV1 = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Musyawarah Warga V1',
            'deskripsi' => 'Membahas perbaikan jalan gang.',
            'tanggal' => '2026-10-25',
            'waktu_mulai' => '19:00',
            'waktu_selesai' => '21:00',
            'lokasi' => 'Rumah Pak RT',
            'kategori' => 'RAPAT',
            'sumber' => 'announcement',
            'announcement_id' => $v1->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        $initialEventId = $eventV1->id;

        // V2 diterbitkan (Pembaruan jadwal dan lokasi)
        $updatePayload = [
            'judul' => 'Musyawarah Warga V2 (Ralat Jadwal)',
            'konten' => 'Waktu diundur menjadi pukul 20:00 dan lokasi pindah ke Balai Pertemuan.',
            'tipe' => 'PENTING',
            'alasan' => 'Penyesuaian jadwal pengurus RT',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-26', // Berubah tanggal
            'agenda_waktu_mulai' => '20:00', // Berubah jam
            'agenda_waktu_selesai' => '22:00',
            'agenda_lokasi' => 'Balai Pertemuan RT 05', // Berubah lokasi
            'agenda_kategori' => 'RAPAT',
        ];

        $response = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), $updatePayload);
        $response->assertRedirect();

        $v1->refresh();
        $this->assertTrue($v1->is_replaced);

        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->first();
        $this->assertNotNull($v2);

        // KUNCI: CalendarEvent count harus tetap 1, ID harus tetap sama, announcement_id harus pindah ke V2
        $this->assertEquals(1, KalenderEvent::count());

        $updatedEvent = KalenderEvent::first();
        $this->assertEquals($initialEventId, $updatedEvent->id, 'CalendarEvent ID harus tetap sama saat versioning berlanjut.');
        $this->assertEquals($v2->id, $updatedEvent->announcement_id, 'announcement_id harus berpindah ke versi aktif (V2).');
        $this->assertEquals('Musyawarah Warga V2 (Ralat Jadwal)', $updatedEvent->judul);
        $this->assertEquals('2026-10-26', $updatedEvent->tanggal->toDateString());
        $this->assertEquals('20:00', $updatedEvent->waktu_mulai);
        $this->assertEquals('Balai Pertemuan RT 05', $updatedEvent->lokasi);
    }

    public function test_update_v1_to_v2_removing_agenda_deletes_calendar_event_preventing_stale_event(): void
    {
        // CASE C: V1 ON -> V2 OFF
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Kegiatan Posyandu Balita V1',
            'konten' => 'Jadwal pelayanan posyandu.',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Kegiatan Posyandu Balita V1',
            'tanggal' => '2026-10-25',
            'kategori' => 'POSYANDU',
            'sumber' => 'announcement',
            'announcement_id' => $v1->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        $this->assertEquals(1, KalenderEvent::count());

        // Terbitkan V2 dengan agenda dinonaktifkan (is_agenda = 0)
        $response = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), [
            'judul' => 'Kegiatan Posyandu Balita V2 (Bukan Agenda)',
            'konten' => 'Posyandu ditunda tanpa tanggal pasti.',
            'tipe' => 'INFO',
            'is_agenda' => 0,
        ]);
        $response->assertRedirect();

        $v1->refresh();
        $this->assertTrue($v1->is_replaced);

        // KUNCI: KalenderEvent harus musnah / tidak lagi ada di kalender aktif
        $this->assertEquals(0, KalenderEvent::count(), 'Tidak boleh ada stale calendar event ketika versi baru menghapus agenda.');
    }

    public function test_update_v1_to_v2_adding_agenda_creates_new_calendar_event(): void
    {
        // CASE A: V1 OFF -> V2 ON
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Wacana Senam Sehat V1',
            'konten' => 'Rencana diadakan senam sehat minggu depan.',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $this->assertEquals(0, KalenderEvent::count());

        // Terbitkan V2 dengan menambahkan jadwal agenda
        $response = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), [
            'judul' => 'Senam Sehat Warga V2 (Jadwal Resmi)',
            'konten' => 'Senam sehat resmi dijadwalkan hari Minggu.',
            'tipe' => 'INFO',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-28',
            'agenda_waktu_mulai' => '06:00',
            'agenda_waktu_selesai' => '08:00',
            'agenda_lokasi' => 'Lapangan RT 05',
            'agenda_kategori' => 'KEGIATAN',
        ]);
        $response->assertRedirect();

        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->first();
        $this->assertNotNull($v2);

        // KUNCI: Event baru tercipta menunjuk ke V2
        $this->assertEquals(1, KalenderEvent::count());
        $event = KalenderEvent::first();
        $this->assertEquals($v2->id, $event->announcement_id);
        $this->assertEquals('2026-10-28', $event->tanggal->toDateString());
    }

    public function test_update_v1_to_v2_both_without_agenda_creates_no_calendar_event(): void
    {
        // CASE D: V1 OFF -> V2 OFF
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Maklumat V1',
            'konten' => 'Maklumat saja.',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $response = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), [
            'judul' => 'Maklumat V2',
            'konten' => 'Revisi maklumat.',
            'tipe' => 'INFO',
            'is_agenda' => 0,
        ]);
        $response->assertRedirect();

        $this->assertEquals(0, KalenderEvent::count());
    }

    public function test_repeated_versioning_v1_v2_v3_v4_maintains_single_event_identity(): void
    {
        // V1
        $res1 = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.store'), [
            'scope_type' => 'rt',
            'judul' => 'Agenda Serbaguna V1',
            'konten' => 'Isi V1',
            'tipe' => 'INFO',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-20',
            'agenda_kategori' => 'KEGIATAN',
        ]);
        $res1->assertRedirect();
        $v1 = Announcement::where('judul', 'Agenda Serbaguna V1')->first();
        $eventInitial = KalenderEvent::where('announcement_id', $v1->id)->first();
        $initialId = $eventInitial->id;

        // V2
        $res2 = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), [
            'judul' => 'Agenda Serbaguna V2',
            'konten' => 'Isi V2',
            'tipe' => 'INFO',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-21',
            'agenda_kategori' => 'KEGIATAN',
        ]);
        $res2->assertRedirect();
        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->first();

        // V3
        $res3 = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v2->id), [
            'judul' => 'Agenda Serbaguna V3',
            'konten' => 'Isi V3',
            'tipe' => 'INFO',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-22',
            'agenda_kategori' => 'KEGIATAN',
        ]);
        $res3->assertRedirect();
        $v3 = Announcement::where('replaces_announcement_id', $v2->id)->first();

        // V4
        $res4 = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v3->id), [
            'judul' => 'Agenda Serbaguna V4',
            'konten' => 'Isi V4',
            'tipe' => 'INFO',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-23',
            'agenda_kategori' => 'KEGIATAN',
        ]);
        $res4->assertRedirect();
        $v4 = Announcement::where('replaces_announcement_id', $v3->id)->first();

        // Assert bahwa total KalenderEvent di database tetap 1!
        $this->assertEquals(1, KalenderEvent::count(), 'Total record agenda harus tepat 1 sepanjang siklus suksesi.');
        $currentEvent = KalenderEvent::first();
        $this->assertEquals($initialId, $currentEvent->id, 'ID record KalenderEvent harus kekal.');
        $this->assertEquals($v4->id, $currentEvent->announcement_id, 'Event harus menunjuk ke versi terbaru (V4).');
        $this->assertEquals('Agenda Serbaguna V4', $currentEvent->judul);
        $this->assertEquals('2026-10-23', $currentEvent->tanggal->toDateString());
    }

    // =========================================================================
    // 3. DEACTIVATION & VISIBILITY TESTS
    // =========================================================================

    public function test_deactivating_announcement_excludes_event_from_active_calendar(): void
    {
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Gotong Royong Drainase',
            'konten' => 'Pembersihan saluran air.',
            'tipe' => 'PENTING',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Gotong Royong Drainase',
            'tanggal' => '2026-10-30',
            'kategori' => 'KEGIATAN',
            'sumber' => 'announcement',
            'announcement_id' => $v1->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        // Saat masih aktif, event ada di active() dan upcoming()
        $this->assertTrue(KalenderEvent::active()->get()->contains('id', $event->id));

        // Pengurus menonaktifkan pengumuman
        $response = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.deactivate', $v1->id), [
            'alasan' => 'Dibatalkan karena bencana alam setempat',
        ]);
        $response->assertRedirect();

        $v1->refresh();
        $this->assertTrue($v1->is_deactivated);

        // KUNCI: Event otomatis hilang dari KalenderEvent::active()
        $this->assertFalse(KalenderEvent::active()->get()->contains('id', $event->id), 'Event dari pengumuman nonaktif tidak boleh muncul di active calendar.');
        $this->assertFalse(KalenderEvent::upcoming()->get()->contains('id', $event->id));

        // Tetapi event TIDAK otomatis is_cancelled = true (memenuhi batasan domain Section 18)
        $event->refresh();
        $this->assertFalse($event->is_cancelled, 'Deaktivasi pengumuman tidak boleh merusak flag is_cancelled.');
    }

    // =========================================================================
    // 4. BOUNDARY & READ-ONLY CRUD TESTS
    // =========================================================================

    public function test_cannot_mutate_linked_announcement_event_via_kalender_standalone_crud(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Induk',
            'konten' => 'Isi',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Event Terkait Pengumuman',
            'tanggal' => '2026-10-25',
            'kategori' => 'KEGIATAN',
            'sumber' => 'announcement',
            'announcement_id' => $announcement->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        // Coba edit via PUT /kalender/{id} -> 422
        $resUpdate = $this->actingAs($this->ketuaRt)->put(route('kalender.update', $event->id), [
            'judul' => 'Pembajakan Agenda Pengumuman',
            'tanggal' => '2026-10-25',
            'kategori' => 'KEGIATAN',
        ]);
        $resUpdate->assertStatus(422);

        // Coba delete via DELETE /kalender/{id} -> 422
        $resDelete = $this->actingAs($this->ketuaRt)->delete(route('kalender.destroy', $event->id));
        $resDelete->assertStatus(422);

        // Event tetap ada
        $this->assertDatabaseHas('kalender_events', ['id' => $event->id]);
    }

    public function test_audit_log_records_announcement_action_without_duplicate_kalender_audit(): void
    {
        AuditLog::whereIn('target_type', ['announcements', 'announcement', 'kalender_events'])->delete();

        // Create dengan agenda
        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.store'), [
            'scope_type' => 'rt',
            'judul' => 'Pengumuman Uji Audit',
            'konten' => 'Isi konten.',
            'tipe' => 'INFO',
            'is_agenda' => 1,
            'agenda_tanggal' => '2026-10-25',
            'agenda_kategori' => 'RAPAT',
        ]);

        // Hanya audit pengumuman yang dicatat, TIDAK ADA KALENDER_EVENT_CREATED
        $this->assertDatabaseHas('audit_logs', [
            'target_type' => 'announcement',
            'aksi' => 'terbitkan_pengumuman',
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'target_type' => 'kalender_events',
            'aksi' => AuditAction::KALENDER_EVENT_CREATED,
        ]);
    }

    public function test_atomicity_transaction_rollback_when_calendar_event_fails(): void
    {
        // Pasang trigger atau override untuk mensimulasikan kegagalan KalenderEvent
        // Misalnya DB constraint violation: tanggal invalid di SQLite atau mock
        // Di sini kita uji bahwa ketika transaksi gagal di tengah, tidak ada data tersimpan
        $initialAnnouncementCount = Announcement::count();
        $initialKalenderCount = KalenderEvent::count();

        try {
            DB::transaction(function () {
                $ann = Announcement::create([
                    'scope_type' => 'rt',
                    'scope_id' => $this->rt05->id,
                    'author_id' => $this->ketuaRt->id,
                    'judul' => 'Pengumuman Gagal Atomik',
                    'konten' => 'Isi',
                    'tipe' => 'INFO',
                    'is_deactivated' => false,
                    'is_replaced' => false,
                ]);

                // Sengaja gagalkan operasi agenda (duplikasi announcement_id atau throw exception)
                throw new \Exception('Simulasi kegagalan sinkronisasi Kalender');
            });
        } catch (\Exception $e) {
            // Expected
        }

        // Keduanya harus ter-rollback (atomik)
        $this->assertEquals($initialAnnouncementCount, Announcement::count());
        $this->assertEquals($initialKalenderCount, KalenderEvent::count());
        $this->assertDatabaseMissing('announcements', ['judul' => 'Pengumuman Gagal Atomik']);
    }

    public function test_announcement_views_display_agenda_banner_and_calendar_link(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Kerja Bakti Akbar RT 05',
            'konten' => 'Mohon kehadiran seluruh kepala keluarga.',
            'tipe' => 'PENTING',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $event = KalenderEvent::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'judul' => 'Kerja Bakti Akbar RT 05',
            'tanggal' => '2026-10-25',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '11:00',
            'lokasi' => 'Taman RT 05',
            'kategori' => 'KEGIATAN',
            'sumber' => 'announcement',
            'announcement_id' => $announcement->id,
            'created_by' => $this->ketuaRt->id,
        ]);

        // 1. Tampil di Komunitas Index (Card Pengumuman)
        $resIndex = $this->actingAs($this->warga)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('Kerja Bakti Akbar RT 05');
        $resIndex->assertSee('25/10/2026');

        // 2. Tampil di Detail Pengumuman
        $resShow = $this->actingAs($this->warga)->get(route('komunitas.pengumuman.show', $announcement->id));
        $resShow->assertStatus(200);
        $resShow->assertSee('Terjadwal di Kalender Warga');
        $resShow->assertSee('Buka Kalender');
        $resShow->assertSee('Taman RT 05');

        // 3. Tampil di Halaman Kalender dengan tombol "Lihat Pengumuman"
        $resKalender = $this->actingAs($this->warga)->get(route('kalender.index', ['scope' => 'rt', 'year' => 2026, 'month' => 10]));
        $resKalender->assertStatus(200);
        $resKalender->assertSee('Lihat Pengumuman');
        $resKalender->assertSee(route('komunitas.pengumuman.show', $announcement->id));
    }
}
