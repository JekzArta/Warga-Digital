<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SuratPengajuan;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);
    }
    public function test_wakil_rt_has_identical_permission_to_ketua_rt(): void
    {
        $wakilRt = User::where('nik', '3273021005050002')->first();
        $this->assertNotNull($wakilRt);

        $this->assertTrue($wakilRt->hasRole('wakil_rt'));
        $this->assertTrue($wakilRt->hasRole('ketua_rt'), 'Penyetaraan wakil_rt = ketua_rt harus otomatis true');
    }

    /**
     * Pastikan TenantScope membatasi query hanya pada rt_id user yang login.
     */
    public function test_tenant_scope_isolates_records_by_rt_id(): void
    {
        $ketuaRt = User::where('nik', '3273021005050001')->first();
        $this->actingAs($ketuaRt);

        // Surat pengajuan di RT 05
        $suratList = SuratPengajuan::all();
        $this->assertNotEmpty($suratList);

        foreach ($suratList as $surat) {
            $this->assertEquals($ketuaRt->rt_id, $surat->rt_id, 'Semua data yang diambil harus terisolasi sesuai rt_id');
        }
    }

    /**
     * Pastikan pencatatan audit log bekerja dengan baik.
     */
    public function test_audit_logger_creates_record(): void
    {
        $ketuaRt = User::where('nik', '3273021005050001')->first();
        $this->actingAs($ketuaRt);

        $log = AuditLogger::log(
            aksi: 'test_action',
            targetType: 'surat_pengajuan',
            targetId: 999,
            sebelum: ['status' => 'MENUNGGU'],
            sesudah: ['status' => 'DISETUJUI'],
            alasan: 'Uji coba audit logger'
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'aksi' => 'test_action',
            'user_id' => $ketuaRt->id,
            'rt_id' => $ketuaRt->rt_id,
        ]);
    }
}
