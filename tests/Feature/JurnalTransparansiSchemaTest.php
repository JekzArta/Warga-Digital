<?php

namespace Tests\Feature;

use App\Constants\AuditAction;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\JurnalTransparansi;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JurnalTransparansiSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $ketuaRt5;
    protected Rt $rt5;
    protected Rw $rw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->superAdmin = User::where('email', 'admin@wargadigital.id')->firstOrFail();
        $this->ketuaRt5 = User::where('nik', '3273021005050001')->firstOrFail();
        $this->rt5 = Rt::where('nomor_rt', 5)->firstOrFail();
        $this->rw = Rw::firstOrFail();
    }

    /**
     * Helper untuk membuat audit log baseline.
     */
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
            'alasan' => 'Catatan forensik audit internal',
        ], $attributes));
    }

    /**
     * A. Existing audit compatibility: Historical audit_logs row tetap dapat dibaca ketika alasan_publik = NULL.
     */
    public function test_existing_audit_logs_can_be_read_with_null_alasan_publik(): void
    {
        $this->assertTrue(
            Schema::hasColumn('audit_logs', 'alasan_publik'),
            'Kolom alasan_publik wajib ada di tabel audit_logs.'
        );

        // Simulasi record audit historis yang dibuat tanpa menyediakan alasan_publik
        $historicalLog = $this->createAuditLog([
            'alasan' => 'Audit sebelum dual-reason aktif',
        ]);

        $fresh = AuditLog::findOrFail($historicalLog->id);
        $this->assertNull($fresh->alasan_publik);
        $this->assertEquals('Audit sebelum dual-reason aktif', $fresh->alasan);
        $this->assertEquals(AuditAction::ANNOUNCEMENT_CREATED, $fresh->aksi);
    }

    /**
     * B. alasan_publik nullable: database menerima alasan_publik = NULL dan juga alasan_publik dengan teks.
     */
    public function test_audit_logs_alasan_publik_is_nullable_and_accepts_text(): void
    {
        // 1. Log dengan alasan_publik null
        $logNull = $this->createAuditLog([
            'alasan' => 'Catatan internal forensik',
            'alasan_publik' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $logNull->id,
            'alasan' => 'Catatan internal forensik',
            'alasan_publik' => null,
        ]);

        // 2. Log dengan alasan_publik terisi
        $logWithPublic = $this->createAuditLog([
            'aksi' => AuditAction::ANNOUNCEMENT_DEACTIVATED,
            'alasan' => 'Internal alasan takedown',
            'alasan_publik' => 'Kegiatan kerja bakti telah selesai dilaksanakan',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $logWithPublic->id,
            'alasan' => 'Internal alasan takedown',
            'alasan_publik' => 'Kegiatan kerja bakti telah selesai dilaksanakan',
        ]);
    }

    /**
     * C. Projection required fields: jurnal_transparansi membutuhkan semua field wajib.
     */
    public function test_jurnal_transparansi_requires_all_mandatory_fields(): void
    {
        $log = $this->createAuditLog();

        // Valid creation works
        $journal = JurnalTransparansi::create([
            'audit_log_id' => $log->id,
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'rw_id' => $this->rw->id,
            'event_type' => AuditAction::ANNOUNCEMENT_CREATED,
            'actor_label' => 'Bambang Hartono — Ketua RT 05',
            'target_title' => 'Kerja Bakti Lingkungan',
            'public_reason' => null,
            'target_type' => 'announcements',
            'target_id' => 10,
            'occurred_at' => now(),
        ]);

        $this->assertDatabaseHas('jurnal_transparansi', [
            'id' => $journal->id,
            'audit_log_id' => $log->id,
            'scope_type' => 'rt',
            'event_type' => AuditAction::ANNOUNCEMENT_CREATED,
        ]);

        // Attempting to create without event_type fails database NOT NULL constraint
        $this->expectException(QueryException::class);
        DB::table('jurnal_transparansi')->insert([
            'audit_log_id' => $log->id + 100,
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'rw_id' => $this->rw->id,
            // 'event_type' missing / null
            'actor_label' => 'Ketua RT',
            'target_title' => 'Judul',
            'target_type' => 'announcements',
            'target_id' => 10,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * D. Unique audit anchor: Insert dua projection rows dengan audit_log_id yang sama harus di-reject.
     */
    public function test_jurnal_transparansi_rejects_duplicate_audit_log_id(): void
    {
        $log = $this->createAuditLog();

        JurnalTransparansi::create([
            'audit_log_id' => $log->id,
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'rw_id' => $this->rw->id,
            'event_type' => AuditAction::ANNOUNCEMENT_CREATED,
            'actor_label' => 'Bambang Hartono — Ketua RT 05',
            'target_title' => 'Kerja Bakti Lingkungan',
            'public_reason' => null,
            'target_type' => 'announcements',
            'target_id' => 10,
            'occurred_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        // Insert duplicate audit_log_id
        JurnalTransparansi::create([
            'audit_log_id' => $log->id, // Duplikat
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'rw_id' => $this->rw->id,
            'event_type' => AuditAction::ANNOUNCEMENT_CREATED,
            'actor_label' => 'Bambang Hartono — Ketua RT 05',
            'target_title' => 'Kerja Bakti Lingkungan Duplikat',
            'public_reason' => null,
            'target_type' => 'announcements',
            'target_id' => 11,
            'occurred_at' => now(),
        ]);
    }

    /**
     * E. No updated_at: Pastikan schema dan model tidak memiliki updated_at.
     */
    public function test_jurnal_transparansi_schema_has_no_updated_at(): void
    {
        $this->assertFalse(
            Schema::hasColumn('jurnal_transparansi', 'updated_at'),
            'Tabel jurnal_transparansi tidak boleh memiliki kolom updated_at.'
        );

        $this->assertTrue(
            Schema::hasColumn('jurnal_transparansi', 'created_at'),
            'Tabel jurnal_transparansi wajib memiliki kolom created_at.'
        );

        $this->assertNull(
            JurnalTransparansi::UPDATED_AT,
            'Model JurnalTransparansi harus mengonfigurasi UPDATED_AT = null.'
        );
    }

    /**
     * F. No destructive cascade: Penghapusan source target TIDAK menghapus projection.
     */
    public function test_target_deletion_does_not_cascade_delete_jurnal_transparansi(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'author_id' => $this->ketuaRt5->id,
            'judul' => 'Pengumuman Uji Hapus',
            'konten' => 'Konten uji coba penghapusan',
            'tipe' => 'INFO',
        ]);

        $log = $this->createAuditLog([
            'target_id' => $announcement->id,
        ]);

        $journal = JurnalTransparansi::create([
            'audit_log_id' => $log->id,
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'rw_id' => $this->rw->id,
            'event_type' => AuditAction::ANNOUNCEMENT_CREATED,
            'actor_label' => 'Bambang Hartono — Ketua RT 05',
            'target_title' => 'Pengumuman Uji Hapus',
            'public_reason' => null,
            'target_type' => 'announcements',
            'target_id' => $announcement->id,
            'occurred_at' => now(),
        ]);

        // Hapus announcement target
        $announcement->delete();

        // Verifikasi projection tetap utuh di database
        $this->assertDatabaseHas('jurnal_transparansi', [
            'id' => $journal->id,
            'target_id' => $announcement->id,
        ]);
    }

    /**
     * G. Schema indices: Verifikasi keberadaan index yang direkomendasikan.
     */
    public function test_jurnal_transparansi_has_expected_indices(): void
    {
        $this->assertTrue(
            Schema::hasIndex('jurnal_transparansi', ['audit_log_id']),
            'Tabel jurnal_transparansi harus memiliki index unik pada audit_log_id.'
        );

        $this->assertTrue(
            Schema::hasIndex('jurnal_transparansi', ['scope_type', 'scope_id', 'occurred_at']),
            'Tabel jurnal_transparansi harus memiliki index pada (scope_type, scope_id, occurred_at).'
        );

        $this->assertTrue(
            Schema::hasIndex('jurnal_transparansi', ['rw_id', 'occurred_at']),
            'Tabel jurnal_transparansi harus memiliki index pada (rw_id, occurred_at).'
        );

        $this->assertTrue(
            Schema::hasIndex('jurnal_transparansi', ['event_type', 'occurred_at']),
            'Tabel jurnal_transparansi harus memiliki index pada (event_type, occurred_at).'
        );
    }
}
