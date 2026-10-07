<?php

namespace Tests\Feature;

use App\Constants\AuditAction;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\ForumCategory;
use App\Models\ForumThread;
use App\Models\JurnalTransparansi;
use App\Models\KasTransaksi;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\UmkmListing;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\JurnalTransparansiDispatcher;
use App\Services\JurnalTransparansiPolicy;
use App\Services\JurnalTransparansiProjector;
use App\Services\JurnalTransparansiTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JurnalTransparansiEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $ketuaRt5;
    protected Rt $rt5;
    protected Rw $rw;
    protected JurnalTransparansiPolicy $policy;
    protected JurnalTransparansiTransformer $transformer;
    protected JurnalTransparansiProjector $projector;
    protected JurnalTransparansiDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->ketuaRt5 = User::where('nik', '3273021005050001')->firstOrFail();
        $this->rt5 = Rt::where('nomor_rt', 5)->firstOrFail();
        $this->rw = Rw::firstOrFail();

        $this->policy = new JurnalTransparansiPolicy();
        $this->transformer = new JurnalTransparansiTransformer();
        $this->projector = new JurnalTransparansiProjector($this->policy, $this->transformer);
        $this->dispatcher = new JurnalTransparansiDispatcher($this->projector);
    }

    protected function createAuditLog(array $attributes = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'rt_id' => $this->rt5->id,
            'rw_id' => $this->rw->id,
            'user_id' => $this->ketuaRt5->id,
            'actor_nama' => $this->ketuaRt5->nama,
            'actor_role' => 'ketua_rt',
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'target_type' => 'announcements',
            'target_id' => 101,
            'sebelum' => null,
            'sesudah' => ['judul' => 'Kerja Bakti Lingkungan'],
            'alasan' => 'Catatan internal pengurus',
            'alasan_publik' => null,
        ], $attributes));
    }

    // ==========================================
    // 1. POLICY TESTS
    // ==========================================

    public function test_whitelist_event_is_eligible(): void
    {
        $whitelistEvents = [
            AuditAction::ANNOUNCEMENT_CREATED => ['alasan_publik' => null],
            AuditAction::ANNOUNCEMENT_DEACTIVATED => ['alasan_publik' => 'Kegiatan telah usai'],
            AuditAction::FORUM_THREAD_CLOSED => ['alasan_publik' => 'Mufakat telah tercapai'],
            AuditAction::FORUM_THREAD_REOPENED => ['alasan_publik' => 'Ada poin baru yang perlu dibahas'],
            AuditAction::KAS_TRANSACTION_CORRECTED => ['alasan_publik' => 'Koreksi penulisan nominal kuitansi'],
            AuditAction::UMKM_LISTING_APPROVED => ['alasan_publik' => null],
        ];

        foreach ($whitelistEvents as $aksi => $meta) {
            $log = $this->createAuditLog([
                'aksi' => $aksi,
                'alasan_publik' => $meta['alasan_publik'],
            ]);

            $this->assertTrue(
                $this->policy->isEligible($log),
                "Event {$aksi} seharusnya eligible ketika memenuhi syarat."
            );
        }
    }

    public function test_excluded_event_is_ineligible(): void
    {
        $excludedEvents = [
            AuditAction::SURAT_APPROVED,
            AuditAction::SURAT_REJECTED,
            AuditAction::FORUM_POST_DELETED,
            AuditAction::USER_ROLE_ASSIGNED,
            AuditAction::KAS_TRANSACTION_CREATED,
        ];

        foreach ($excludedEvents as $aksi) {
            $log = $this->createAuditLog([
                'aksi' => $aksi,
                'alasan_publik' => 'Alasan apa pun',
            ]);

            $this->assertFalse(
                $this->policy->isEligible($log),
                "Event {$aksi} di luar whitelist harus ineligible."
            );
        }
    }

    public function test_announcement_minor_update_is_ineligible(): void
    {
        // Update hanya spasi / formatting tidak boleh diproyeksikan
        $log = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_UPDATED,
            'sebelum' => ['judul' => 'Kerja Bakti RT 05'],
            'sesudah' => ['judul' => ' Kerja Bakti RT 05 '],
            'alasan_publik' => 'Koreksi spasi',
        ]);

        $this->assertFalse(
            $this->policy->isSubstantiveUpdate($log),
            'Perubahan spasi saja harus dianggap non-substantif.'
        );
        $this->assertFalse($this->policy->isEligible($log));
    }

    public function test_announcement_substantive_update_requires_public_reason(): void
    {
        // 1. Substantive update TANPA alasan publik -> Ineligible
        $logTanpaAlasan = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_UPDATED,
            'sebelum' => ['judul' => 'Kerja Bakti RT 05 Hari Sabtu'],
            'sesudah' => ['judul' => 'Kerja Bakti RT 05 DIUNDUR ke Hari Minggu'],
            'alasan_publik' => null,
        ]);

        $this->assertFalse(
            $this->policy->isEligible($logTanpaAlasan),
            'Update substantif tanpa alasan publik harus ditolak.'
        );

        // 2. Substantive update DENGAN alasan publik -> Eligible
        $logDenganAlasan = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_UPDATED,
            'sebelum' => ['judul' => 'Kerja Bakti RT 05 Hari Sabtu'],
            'sesudah' => ['judul' => 'Kerja Bakti RT 05 DIUNDUR ke Hari Minggu'],
            'alasan_publik' => 'Jadwal diundur karena cuaca ekstrem hari Sabtu',
        ]);

        $this->assertTrue(
            $this->policy->isEligible($logDenganAlasan),
            'Update substantif dengan alasan publik harus eligible.'
        );
    }

    public function test_public_safety_policy_rejects_nik_and_phone_number(): void
    {
        // 1. Tolak teks mengandung NIK 16 digit
        $logNik = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_DEACTIVATED,
            'alasan_publik' => 'Pengumuman warga 3273010101010001 ditutup',
        ]);
        $this->assertFalse($this->policy->isEligible($logNik));

        // 2. Tolak teks mengandung nomor HP
        $logHp = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_DEACTIVATED,
            'alasan_publik' => 'Hubungi pak RT di 081234567890 untuk info lanjut',
        ]);
        $this->assertFalse($this->policy->isEligible($logHp));

        // 3. Tolak markup HTML
        $logHtml = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_DEACTIVATED,
            'alasan_publik' => '<script>alert(1)</script>Penjelasan pengumuman',
        ]);
        $this->assertFalse($this->policy->isEligible($logHtml));
    }

    // ==========================================
    // 2. DUAL REASON TESTS
    // ==========================================

    public function test_audit_logger_persists_alasan_and_alasan_publik_separately(): void
    {
        $log = AuditLogger::log(
            aksi: AuditAction::ANNOUNCEMENT_DEACTIVATED,
            targetType: 'announcements',
            targetId: 201,
            sebelum: ['status' => 'aktif'],
            sesudah: ['status' => 'nonaktif'],
            alasan: 'Catatan investigasi internal pengurus',
            alasanPublik: 'Kegiatan kerja bakti telah selesai dilaksanakan'
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'alasan' => 'Catatan investigasi internal pengurus',
            'alasan_publik' => 'Kegiatan kerja bakti telah selesai dilaksanakan',
        ]);
    }

    public function test_public_reason_never_falls_back_to_internal_reason(): void
    {
        $log = AuditLogger::log(
            aksi: AuditAction::FORUM_THREAD_CLOSED,
            targetType: 'forum_threads',
            targetId: 301,
            alasan: 'Catatan rahasia pengurus',
            alasanPublik: null
        );

        $this->assertNull($log->alasan_publik);
        $this->assertEquals('Catatan rahasia pengurus', $log->alasan);

        // Policy harus menolak proyeksi karena alasan publik kosong, tidak boleh fallback ke alasan
        $this->assertFalse($this->policy->isEligible($log));
    }

    // ==========================================
    // 3. TRANSFORMER TESTS
    // ==========================================

    public function test_announcement_uses_safe_snapshot_title(): void
    {
        $log = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'sesudah' => ['judul' => 'Penyemprotan Disinfektan'],
        ]);

        $payload = $this->transformer->transform($log);
        $this->assertEquals('Pengumuman: Penyemprotan Disinfektan', $payload['target_title']);
    }

    public function test_forum_uses_category_not_raw_thread_title(): void
    {
        $log = $this->createAuditLog([
            'aksi' => AuditAction::FORUM_THREAD_CLOSED,
            'sebelum' => ['kategori' => 'Keamanan Lingkungan'],
            'sesudah' => ['kategori' => 'Keamanan Lingkungan'],
            'alasan_publik' => 'Musyawarah selesai dicapai',
        ]);

        $payload = $this->transformer->transform($log);
        $this->assertEquals('Musyawarah Warga: Kategori Keamanan Lingkungan', $payload['target_title']);
        $this->assertStringNotContainsString('Maling', $payload['target_title']);
    }

    public function test_kas_uses_budget_category_not_raw_note(): void
    {
        $log = $this->createAuditLog([
            'aksi' => AuditAction::KAS_TRANSACTION_CORRECTED,
            'sesudah' => ['kategori' => 'Operasional Kebersihan'],
            'alasan_publik' => 'Koreksi salah input kuitansi',
        ]);

        $payload = $this->transformer->transform($log);
        $this->assertEquals('Pos Anggaran: Operasional Kebersihan (Koreksi Pembukuan)', $payload['target_title']);
    }

    public function test_umkm_uses_name_and_category_only(): void
    {
        $log = $this->createAuditLog([
            'aksi' => AuditAction::UMKM_LISTING_APPROVED,
            'sesudah' => [
                'nama_usaha' => 'Warung Nasi Bu Siti',
                'kategori' => 'Kuliner',
                'no_wa' => '081234567890',
                'harga' => 15000,
            ],
            'alasan_publik' => null,
        ]);

        $payload = $this->transformer->transform($log);
        $this->assertEquals('Usaha Warga: Warung Nasi Bu Siti (Kuliner)', $payload['target_title']);
        $this->assertStringNotContainsString('081234567890', json_encode($payload));
        $this->assertStringNotContainsString('15000', json_encode($payload));
    }

    public function test_actor_label_is_snapshotted_from_audit(): void
    {
        // 1. Pengumuman -> "Nama — Jabatan"
        $logPengumuman = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'actor_nama' => 'Bambang Hartono',
            'actor_role' => 'ketua_rt',
        ]);
        $payloadPengumuman = $this->transformer->transform($logPengumuman);
        $this->assertEquals('Bambang Hartono — Ketua RT 05', $payloadPengumuman['actor_label']);

        // 2. Moderasi / Audit -> "Jabatan" saja
        $logModerasi = $this->createAuditLog([
            'aksi' => AuditAction::FORUM_THREAD_CLOSED,
            'actor_nama' => 'Bambang Hartono',
            'actor_role' => 'ketua_rt',
            'alasan_publik' => 'Diskusi telah mufakat',
        ]);
        $payloadModerasi = $this->transformer->transform($logModerasi);
        $this->assertEquals('Ketua RT 05', $payloadModerasi['actor_label']);
    }

    public function test_target_fallback_is_safe_when_source_missing(): void
    {
        // Audit log tanpa snapshot sesudah/sebelum dan ID fiktif
        $logPengumuman = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'target_id' => 999999,
            'sebelum' => null,
            'sesudah' => null,
        ]);
        $this->assertEquals('Pengumuman Resmi Lingkungan', $this->transformer->transformTargetTitle($logPengumuman));

        $logForum = $this->createAuditLog([
            'aksi' => AuditAction::FORUM_THREAD_CLOSED,
            'target_id' => 999999,
            'sebelum' => null,
            'sesudah' => null,
            'alasan_publik' => 'Selesai',
        ]);
        $this->assertEquals('Musyawarah Komunitas Warga', $this->transformer->transformTargetTitle($logForum));

        $logKas = $this->createAuditLog([
            'aksi' => AuditAction::KAS_TRANSACTION_CORRECTED,
            'target_id' => 999999,
            'sebelum' => null,
            'sesudah' => null,
            'alasan_publik' => 'Koreksi pembukuan',
        ]);
        $this->assertEquals('Koreksi Pencatatan Kas RT', $this->transformer->transformTargetTitle($logKas));

        $logUmkm = $this->createAuditLog([
            'aksi' => AuditAction::UMKM_LISTING_APPROVED,
            'target_id' => 999999,
            'sebelum' => null,
            'sesudah' => null,
        ]);
        $this->assertEquals('Pendaftaran Usaha Warga', $this->transformer->transformTargetTitle($logUmkm));
    }

    // ==========================================
    // 4. PROJECTOR TESTS
    // ==========================================

    public function test_projector_creates_one_projection(): void
    {
        $log = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'sesudah' => ['judul' => 'Pengumuman Baru'],
        ]);

        $projected = $this->projector->project($log);

        $this->assertNotNull($projected);
        $this->assertInstanceOf(JurnalTransparansi::class, $projected);
        $this->assertDatabaseHas('jurnal_transparansi', [
            'id' => $projected->id,
            'audit_log_id' => $log->id,
            'event_type' => AuditAction::ANNOUNCEMENT_CREATED,
        ]);
    }

    public function test_projector_skips_ineligible_event(): void
    {
        $log = $this->createAuditLog([
            'aksi' => AuditAction::SURAT_APPROVED,
        ]);

        $projected = $this->projector->project($log);

        $this->assertNull($projected);
        $this->assertDatabaseMissing('jurnal_transparansi', [
            'audit_log_id' => $log->id,
        ]);
    }

    public function test_projector_is_idempotent(): void
    {
        $log = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'sesudah' => ['judul' => 'Pengumuman Idempoten'],
        ]);

        $first = $this->projector->project($log);
        $second = $this->projector->project($log);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, JurnalTransparansi::where('audit_log_id', $log->id)->count());
    }

    public function test_projector_preserves_nullable_public_reason(): void
    {
        $log = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'sesudah' => ['judul' => 'Pengumuman Tanpa Alasan Publik'],
            'alasan_publik' => null,
        ]);

        $projected = $this->projector->project($log);

        $this->assertNotNull($projected);
        $this->assertNull($projected->public_reason);
    }

    // ==========================================
    // 5. POST-COMMIT DISPATCH ABSTRACTION TESTS
    // ==========================================

    public function test_projection_dispatch_runs_after_transaction_commit(): void
    {
        $log = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'sesudah' => ['judul' => 'Event Commit Transaction'],
        ]);

        DB::transaction(function () use ($log) {
            $this->dispatcher->dispatchProjection($log);
            // Sebelum transaksi commit, proyeksi belum masuk database
            $this->assertDatabaseMissing('jurnal_transparansi', [
                'audit_log_id' => $log->id,
            ]);
        });

        // Setelah commit berhasil, proyeksi harus telah tersimpan
        $this->assertDatabaseHas('jurnal_transparansi', [
            'audit_log_id' => $log->id,
        ]);
    }

    public function test_projection_dispatch_does_not_run_after_transaction_rollback(): void
    {
        $log = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_CREATED,
            'sesudah' => ['judul' => 'Event Rollback Transaction'],
        ]);

        try {
            DB::transaction(function () use ($log) {
                $this->dispatcher->dispatchProjection($log);
                throw new \RuntimeException('Simulasi rollback transaksi bisnis');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Karena rollback, callback afterCommit tidak pernah dipicu
        $this->assertDatabaseMissing('jurnal_transparansi', [
            'audit_log_id' => $log->id,
        ]);
    }

    public function test_projection_failure_isolated_from_caller(): void
    {
        $log = $this->createAuditLog();

        // Buat mock projector yang gagal / melempar exception fatal
        $mockProjector = \Mockery::mock(JurnalTransparansiProjector::class);
        $mockProjector->shouldReceive('project')
            ->once()
            ->andThrow(new \RuntimeException('Koneksi storage proyeksi terputus'));

        $faultyDispatcher = new JurnalTransparansiDispatcher($mockProjector);

        // Pemanggilan tidak boleh melempar exception ke pemanggil (terisolasi)
        $faultyDispatcher->dispatchProjection($log);

        $this->assertTrue(true, 'Dispatcher berhasil menangkap dan mengisolasi kegagalan proyeksi.');
    }
}
