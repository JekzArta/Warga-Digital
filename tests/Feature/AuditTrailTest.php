<?php

namespace Tests\Feature;

use App\Constants\AuditAction;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\ForumCategory;
use App\Models\ForumThread;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\SuratPengajuan;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $ketuaRw;
    protected User $ketuaRt5;
    protected User $wakilRt5;
    protected User $sekretarisRt5;
    protected User $bendaharaRt5;
    protected User $wargaRt5;
    protected Rt $rt5;
    protected Rt $rt6;
    protected Rw $rw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->superAdmin = User::where('email', 'admin@wargadigital.id')->firstOrFail();
        $this->ketuaRw = User::where('nik', '3273021005030001')->firstOrFail();
        $this->ketuaRt5 = User::where('nik', '3273021005050001')->firstOrFail();
        $this->wakilRt5 = User::where('nik', '3273021005050002')->firstOrFail();
        $this->sekretarisRt5 = User::where('nik', '3273021005050003')->firstOrFail();
        $this->bendaharaRt5 = User::where('nik', '3273021005050004')->firstOrFail();
        $this->wargaRt5 = User::where('nik', '3273021005050010')->firstOrFail();

        $this->rt5 = Rt::where('nomor_rt', 5)->firstOrFail();
        $this->rw = Rw::firstOrFail();

        // Siapkan RT 06 untuk pengujian isolasi tenant/scope
        $this->rt6 = Rt::firstOrCreate(
            ['rw_id' => $this->rw->id, 'nomor_rt' => 6],
            ['kode_rt' => '32.73.02.1005-RW03-RT06', 'nama' => 'RT 06 Sekeloa', 'format_nomor_surat' => '{nomor}/RT06/SK/{tahun}']
        );
    }

    /**
     * A. Verifikasi Migration dan Skema audit_logs.
     */
    public function test_schema_audit_logs_has_actor_snapshot_and_nullable_user(): void
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'actor_nama'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'actor_role'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'ip_address'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'user_id'));

        // Pastikan record dapat disimpan dengan user_id null
        $log = AuditLog::create([
            'aksi' => AuditAction::SURAT_SUBMITTED,
            'target_type' => 'surat_pengajuan',
            'target_id' => 999,
            'user_id' => null,
            'actor_nama' => 'Anonim',
            'actor_role' => 'warga',
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'user_id' => null,
            'actor_nama' => 'Anonim',
            'actor_role' => 'warga',
        ]);
    }

    /**
     * B. Verifikasi AuditLogger mencatat authenticated actor snapshot secara otomatis.
     */
    public function test_audit_logger_captures_authenticated_actor_snapshot(): void
    {
        $this->actingAs($this->ketuaRt5);

        $log = AuditLogger::log(
            aksi: AuditAction::SURAT_REVIEWED,
            targetType: 'surat_pengajuan',
            targetId: 101,
            sebelum: ['status' => 'MENUNGGU'],
            sesudah: ['status' => 'DIREVIEW'],
            alasan: 'Membuka berkas'
        );

        $this->assertEquals($this->ketuaRt5->id, $log->user_id);
        $this->assertEquals($this->ketuaRt5->nama, $log->actor_nama);
        $this->assertEquals('ketua_rt', $log->actor_role);
        $this->assertEquals(AuditAction::SURAT_REVIEWED, $log->aksi);
    }

    /**
     * C. HARD REQUIREMENT: Hapus Fallback User ID Palsu (user_id = 1).
     * Jika aksi dilakukan tanpa sesi user, user_id HARUS null, bukan 1 (Super Admin).
     */
    public function test_audit_logger_without_auth_does_not_fallback_to_user_id_one(): void
    {
        Auth::logout();

        $log = AuditLogger::log(
            aksi: 'SYSTEM_EVENT',
            targetType: 'surat_pengajuan',
            targetId: 202,
            alasan: 'Aksi latar belakang tanpa sesi'
        );

        $this->assertNull($log->user_id, 'user_id harus NULL jika tanpa auth sesi, dilarang fallback ke ID 1!');
        $this->assertNotEquals(1, $log->user_id);
    }

    /**
     * D. Verifikasi Target Polymorphic Relation via morphMap.
     */
    public function test_audit_log_target_resolves_all_polymorphic_types(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'form_data' => ['keperluan' => 'KTP Baru'],
            'status' => 'MENUNGGU',
        ]);

        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'author_id' => $this->ketuaRt5->id,
            'judul' => 'Kerja Bakti Akbar',
            'konten' => 'Gotong royong',
            'tipe' => 'info',
        ]);

        $category = ForumCategory::firstOrCreate(
            ['nama' => 'Umum RT 05'],
            ['scope_type' => 'rt', 'scope_id' => $this->rt5->id, 'deskripsi' => 'Diskusi umum']
        );

        $thread = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $this->wargaRt5->id,
            'author_role_snapshot' => ['Warga'],
            'judul' => 'Lampu Jalan Padam',
            'konten' => 'Mohon diperbaiki',
            'status' => 'aktif',
        ]);

        // 1. Target: surat_pengajuan
        $logSurat = AuditLog::create([
            'aksi' => AuditAction::SURAT_SUBMITTED,
            'target_type' => 'surat_pengajuan',
            'target_id' => $surat->id,
        ]);
        $this->assertInstanceOf(SuratPengajuan::class, $logSurat->target);
        $this->assertEquals($surat->id, $logSurat->target->id);

        // 2. Target: announcements (canonical)
        $logAnnounce = AuditLog::create([
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'target_type' => 'announcements',
            'target_id' => $announcement->id,
        ]);
        $this->assertInstanceOf(Announcement::class, $logAnnounce->target);
        $this->assertEquals($announcement->id, $logAnnounce->target->id);

        // 3. Target: announcement (legacy singular alias)
        $logAnnounceLegacy = AuditLog::create([
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'target_type' => 'announcement',
            'target_id' => $announcement->id,
        ]);
        $this->assertInstanceOf(Announcement::class, $logAnnounceLegacy->target);
        $this->assertEquals($announcement->id, $logAnnounceLegacy->target->id);

        // 4. Target: forum_threads
        $logThread = AuditLog::create([
            'aksi' => AuditAction::FORUM_THREAD_PINNED,
            'target_type' => 'forum_threads',
            'target_id' => $thread->id,
        ]);
        $this->assertInstanceOf(ForumThread::class, $logThread->target);
        $this->assertEquals($thread->id, $logThread->target->id);

        // 5. Target: users
        $logUser = AuditLog::create([
            'aksi' => AuditAction::AUTH_ACCOUNT_ACTIVATED,
            'target_type' => 'users',
            'target_id' => $this->wargaRt5->id,
        ]);
        $this->assertInstanceOf(User::class, $logUser->target);
        $this->assertEquals($this->wargaRt5->id, $logUser->target->id);
    }

    /**
     * E. Verifikasi Query Alias Builder untuk backward compatibility query legacy strings.
     */
    public function test_audit_log_query_builder_matches_both_legacy_and_canonical(): void
    {
        // Buat record dengan aksi kanonikal
        $log = AuditLog::create([
            'aksi' => AuditAction::SURAT_APPROVED,
            'target_type' => 'surat_pengajuan',
            'target_id' => 888,
        ]);

        // Query memakai legacy 'approve_surat' harus berhasil menemukan record kanonikal
        $foundViaLegacy = AuditLog::where('aksi', 'approve_surat')->where('target_id', 888)->first();
        $this->assertNotNull($foundViaLegacy);
        $this->assertEquals($log->id, $foundViaLegacy->id);

        // Query memakai kanonikal juga harus berhasil
        $foundViaCanonical = AuditLog::where('aksi', AuditAction::SURAT_APPROVED)->where('target_id', 888)->first();
        $this->assertNotNull($foundViaCanonical);
        $this->assertEquals($log->id, $foundViaCanonical->id);
    }

    /**
     * F. Tutup Gap: SURAT_SUBMITTED tercatat saat warga mengajukan surat baru.
     */
    public function test_surat_submission_creates_surat_submitted_audit_log(): void
    {
        $this->actingAs($this->wargaRt5);

        $fileKtp = \Illuminate\Http\UploadedFile::fake()->create('ktp_warga.jpg', 200, 'image/jpeg');

        $response = $this->post(route('surat.store'), [
            'jenis_surat' => 'SKD',
            'alamat_domisili' => 'Jl. Sekeloa No. 15 RT 05 RW 03',
            'lama_tinggal' => '4 Tahun',
            'keperluan' => 'Pendaftaran Sekolah',
            'dokumen_ktp_kk' => [$fileKtp],
        ]);

        $surat = SuratPengajuan::where('user_id', $this->wargaRt5->id)->latest('id')->first();
        $this->assertNotNull($surat);

        $response->assertRedirect(route('surat.show', $surat->id));

        // Cek bahwa audit record dibuat untuk SURAT_SUBMITTED
        $this->assertDatabaseHas('audit_logs', [
            'target_type' => 'surat_pengajuan',
            'target_id' => $surat->id,
            'aksi' => AuditAction::SURAT_SUBMITTED,
            'user_id' => $this->wargaRt5->id,
            'actor_nama' => $this->wargaRt5->nama,
            'actor_role' => 'warga',
        ]);
    }

    /**
     * G. Verifikasi Otorisasi RBAC Meja Audit (/admin/audit).
     */
    public function test_authorization_warga_is_forbidden_and_officials_are_allowed(): void
    {
        // 1. Warga -> 403 Forbidden
        $this->actingAs($this->wargaRt5);
        $this->get(route('admin.audit.index'))->assertStatus(403);

        // 2. Sekretaris RT -> 200 OK
        $this->actingAs($this->sekretarisRt5);
        $this->get(route('admin.audit.index'))->assertStatus(200);

        // 3. Bendahara RT -> 200 OK
        $this->actingAs($this->bendaharaRt5);
        $this->get(route('admin.audit.index'))->assertStatus(200);

        // 4. Ketua RT -> 200 OK
        $this->actingAs($this->ketuaRt5);
        $this->get(route('admin.audit.index'))->assertStatus(200);

        // 5. Wakil RT -> 200 OK
        $this->actingAs($this->wakilRt5);
        $this->get(route('admin.audit.index'))->assertStatus(200);

        // 6. Ketua RW -> 200 OK
        $this->actingAs($this->ketuaRw);
        $this->get(route('admin.audit.index'))->assertStatus(200);

        // 7. Super Admin -> 200 OK
        $this->actingAs($this->superAdmin);
        $this->get(route('admin.audit.index'))->assertStatus(200);
    }

    /**
     * H. Verifikasi Pencegahan Kebocoran Scope Wilayah (Multi-Tenant Scope).
     * Pengurus RT 05 TIDAK boleh melihat audit milik RT 06.
     */
    public function test_scope_leak_prevention_rt5_cannot_see_rt6_audit(): void
    {
        // Buat audit log di RT 05
        $logRt5 = AuditLog::create([
            'rt_id' => $this->rt5->id,
            'rw_id' => $this->rw->id,
            'user_id' => $this->ketuaRt5->id,
            'actor_nama' => 'Ketua RT 05',
            'actor_role' => 'ketua_rt',
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'target_type' => 'announcements',
            'target_id' => 5001,
            'alasan' => 'Audit Rahasia RT 05',
        ]);

        // Buat audit log di RT 06
        $logRt6 = AuditLog::create([
            'rt_id' => $this->rt6->id,
            'rw_id' => $this->rw->id,
            'user_id' => null,
            'actor_nama' => 'Ketua RT 06',
            'actor_role' => 'ketua_rt',
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'target_type' => 'announcements',
            'target_id' => 6001,
            'alasan' => 'Audit Rahasia RT 06',
        ]);

        // Login sebagai Ketua RT 05
        $this->actingAs($this->ketuaRt5);
        $response = $this->get(route('admin.audit.index'));

        $response->assertStatus(200);
        $response->assertSee('Audit Rahasia RT 05');
        $response->assertDontSee('Audit Rahasia RT 06');
    }

    /**
     * I. Verifikasi Pagination Meja Audit (20 per page).
     */
    public function test_audit_viewer_pagination(): void
    {
        $this->actingAs($this->ketuaRt5);

        // Buat 25 audit logs di RT 05
        for ($i = 1; $i <= 25; $i++) {
            AuditLog::create([
                'rt_id' => $this->rt5->id,
                'rw_id' => $this->rw->id,
                'user_id' => $this->ketuaRt5->id,
                'actor_nama' => $this->ketuaRt5->nama,
                'actor_role' => 'ketua_rt',
                'aksi' => AuditAction::SURAT_REVIEWED,
                'target_type' => 'surat_pengajuan',
                'target_id' => 1000 + $i,
                'alasan' => "Batch pagination item {$i}",
            ]);
        }

        $response = $this->get(route('admin.audit.index'));
        $response->assertStatus(200);

        // Halaman 1 harus memuat item ke-25 (terbaru), tapi tidak lebih dari 20 item
        $response->assertSee('Batch pagination item 25');
        $this->assertEquals(20, $response->viewData('logs')->count());
        $this->assertGreaterThanOrEqual(25, $response->viewData('logs')->total());
    }

    /**
     * J. Verifikasi Actor Deletion Safety (nullOnDelete).
     * Jika user dihapus dari database, audit logs tetap bertahan dengan user_id NULL dan snapshot utuh.
     */
    public function test_actor_deletion_preserves_audit_record_with_null_user_id(): void
    {
        // Buat user sementara
        $tempUser = User::create([
            'kode_warga' => 'TEMP-001',
            'rt_id' => $this->rt5->id,
            'rw_id' => $this->rw->id,
            'nik' => '3273021005059999',
            'nama' => 'Pejabat Sementara RT',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $this->actingAs($tempUser);

        $log = AuditLogger::log(
            aksi: AuditAction::SURAT_APPROVED,
            targetType: 'surat_pengajuan',
            targetId: 777,
            alasan: 'Persetujuan oleh Pejabat Sementara'
        );

        $this->assertEquals($tempUser->id, $log->user_id);
        $this->assertEquals('Pejabat Sementara RT', $log->actor_nama);

        // Hapus user dari database
        $tempUser->delete();

        // Reload log dari database
        $reloadedLog = AuditLog::find($log->id);

        $this->assertNotNull($reloadedLog, 'Audit log tidak boleh terhapus saat user dihapus!');
        $this->assertNull($reloadedLog->user_id, 'user_id harus berubah menjadi NULL via nullOnDelete!');
        $this->assertEquals('Pejabat Sementara RT', $reloadedLog->actor_nama, 'Snapshot actor_nama harus tetap tersimpan!');
        $this->assertEquals('Pejabat Sementara RT', $reloadedLog->actor_nama_display);
    }
}
