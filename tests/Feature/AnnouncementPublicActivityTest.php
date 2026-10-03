<?php

namespace Tests\Feature;

use App\Constants\AuditAction;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnnouncementPublicActivityTest extends TestCase
{
    use RefreshDatabase;

    protected User $wargaRt5;
    protected User $wargaRt6;
    protected User $ketuaRt5;
    protected User $ketuaRw;
    protected User $superAdmin;
    protected Rt $rt5;
    protected Rt $rt6;
    protected Rw $rw3;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app-id',
            'broadcasting.connections.reverb.options.host' => 'localhost',
            'broadcasting.connections.reverb.options.port' => 8080,
            'broadcasting.connections.reverb.options.scheme' => 'http',
        ]);

        require base_path('routes/channels.php');

        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->rw3 = Rw::firstOrFail();
        $this->rt5 = Rt::where('nomor_rt', 5)->firstOrFail();

        $this->rt6 = Rt::firstOrCreate(
            ['nomor_rt' => 6],
            [
                'rw_id' => $this->rw3->id,
                'kode_rt' => '32.73.02.1005-RW03-RT06',
                'nama' => 'RT 06 Sekeloa',
                'format_nomor_surat' => '{nomor}/RT06/SK/{tahun}',
            ]
        );

        $this->wargaRt5 = User::where('nik', '3273021005050010')->firstOrFail();
        $this->ketuaRt5 = User::where('nik', '3273021005050001')->firstOrFail();
        $this->ketuaRw = User::where('nik', '3273021005030001')->firstOrFail();
        $this->superAdmin = User::where('email', 'admin@wargadigital.id')->firstOrFail();

        // Buat warga RT 06
        $this->wargaRt6 = User::create([
            'kode_warga' => 'WRG-990001',
            'rt_id' => $this->rt6->id,
            'rw_id' => $this->rw3->id,
            'nik' => '3273021006060001',
            'nama' => 'Cecep Warga RT 06',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1992-04-10',
            'alamat' => 'Jl. Sekeloa RT 06 No. 1',
            'password' => Hash::make('password'),
            'status' => 'aktif',
        ]);
        UserRole::create([
            'user_id' => $this->wargaRt6->id,
            'role' => 'warga',
            'assigned_at' => now(),
        ]);
    }

    /**
     * Scenario A — Updated: V1 -> V2 menampilkan public activity dengan actor snapshot, timestamp, label, dan alasan jika ada.
     */
    public function test_scenario_a_updated_shows_public_activity_to_authorized_warga(): void
    {
        // 1. Buat pengumuman V1 oleh Ketua RT 05
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'author_id' => $this->ketuaRt5->id,
            'judul' => 'Kerja Bakti Bersama V1',
            'konten' => 'Kerja bakti dimulai pukul 07.00 WIB.',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        // 2. Terbitkan pembaruan V2 melalui endpoint dengan alasan
        $response = $this->actingAs($this->ketuaRt5)->post(
            route('komunitas.pengumuman.pembaruan', $v1->id),
            [
                'judul' => 'Kerja Bakti Bersama V2',
                'konten' => 'Kerja bakti diundur menjadi pukul 08.00 WIB.',
                'tipe' => 'PENTING',
                'alasan' => 'Ralat waktu pelaksanaan kegiatan',
            ]
        );
        $response->assertRedirect();

        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->firstOrFail();

        // 3. Akses V2 sebagai warga RT 05
        $viewResponse = $this->actingAs($this->wargaRt5)->get(route('komunitas.pengumuman.show', $v2->id));
        $viewResponse->assertStatus(200);

        // Assert: Bagian Aktivitas Pengumuman tampil
        $viewResponse->assertSee('Aktivitas Pengumuman');
        $viewResponse->assertSee($this->ketuaRt5->nama);
        $viewResponse->assertSee('Ketua RT 05');
        $viewResponse->assertSee('Pengumuman diperbarui');
        $viewResponse->assertSee('Alasan: Ralat waktu pelaksanaan kegiatan');

        // Pastikan juga tampil di active feed komunitas/index
        $feedResponse = $this->actingAs($this->wargaRt5)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $feedResponse->assertStatus(200);
        $feedResponse->assertSee('Aktivitas Pengumuman');
        $feedResponse->assertSee('Pengumuman diperbarui');
        $feedResponse->assertSee('Alasan: Ralat waktu pelaksanaan kegiatan');
    }

    /**
     * Scenario A.2 — Updated tanpa alasan: Hanya tampil "Pengumuman diperbarui" tanpa blok alasan.
     */
    public function test_scenario_a_updated_without_reason(): void
    {
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'author_id' => $this->ketuaRt5->id,
            'judul' => 'Pengumuman Awal',
            'konten' => 'Konten awal.',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $this->actingAs($this->ketuaRt5)->post(
            route('komunitas.pengumuman.pembaruan', $v1->id),
            [
                'judul' => 'Pengumuman Revisi',
                'konten' => 'Konten revisi tanpa alasan.',
                'tipe' => 'INFO',
                'alasan' => '',
            ]
        );

        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->firstOrFail();

        $viewResponse = $this->actingAs($this->wargaRt5)->get(route('komunitas.pengumuman.show', $v2->id));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Pengumuman diperbarui');
        $viewResponse->assertDontSee('Alasan:');
    }

    /**
     * Scenario B — Deactivated: Pengumuman yang dinonaktifkan menampilkan aktivitas deactivation beserta alasannya.
     */
    public function test_scenario_b_deactivated_shows_deactivation_activity(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'author_id' => $this->ketuaRt5->id,
            'judul' => 'Jadwal Ronda Malam Spesial',
            'konten' => 'Ronda malam spesial malam minggu.',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        // Nonaktifkan pengumuman
        $response = $this->actingAs($this->ketuaRt5)->post(
            route('komunitas.pengumuman.deactivate', $announcement->id),
            ['alasan' => 'Jadwal kegiatan dibatalkan']
        );
        $response->assertRedirect();

        // Buka halaman detail pengumuman sebagai warga RT 05
        $viewResponse = $this->actingAs($this->wargaRt5)->get(route('komunitas.pengumuman.show', $announcement->id));
        $viewResponse->assertStatus(200);

        // Verifikasi banner deactivation
        $viewResponse->assertSee('Pengumuman Dinonaktifkan');
        // Verifikasi card public activity
        $viewResponse->assertSee('Aktivitas Pengumuman');
        $viewResponse->assertSee($this->ketuaRt5->nama);
        $viewResponse->assertSee('Ketua RT 05');
        $viewResponse->assertSee('Pengumuman dinonaktifkan');
        $viewResponse->assertSee('Alasan: Jadwal kegiatan dibatalkan');
    }

    /**
     * Scenario C — Internal Data Not Exposed:
     * View maupun response tidak mengandung ip_address, user_id, json diff sebelum/sesudah, atau metadata teknis.
     */
    public function test_scenario_c_internal_audit_data_is_never_exposed_to_public(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'author_id' => $this->ketuaRt5->id,
            'judul' => 'Piknik Warga',
            'konten' => 'Piknik ke Lembang.',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $fakeIp = '192.168.123.45';
        $sensitiveJsonMarker = 'SENSITIVE_INTERNAL_DIFF_MARKER_999';

        AuditLog::create([
            'rt_id' => $this->rt5->id,
            'rw_id' => $this->rw3->id,
            'user_id' => $this->ketuaRt5->id,
            'actor_nama' => $this->ketuaRt5->nama,
            'actor_role' => 'ketua_rt',
            'ip_address' => $fakeIp,
            'aksi' => AuditAction::ANNOUNCEMENT_UPDATED,
            'target_type' => 'announcements',
            'target_id' => $announcement->id,
            'sebelum' => ['internal_key' => $sensitiveJsonMarker],
            'sesudah' => ['internal_key' => $sensitiveJsonMarker],
            'alasan' => 'Ralat lokasi piknik',
        ]);

        // 1. Verifikasi HTML View
        $viewResponse = $this->actingAs($this->wargaRt5)->get(route('komunitas.pengumuman.show', $announcement->id));
        $viewResponse->assertStatus(200);

        $html = $viewResponse->getContent();
        $this->assertStringNotContainsString($fakeIp, $html);
        $this->assertStringNotContainsString($sensitiveJsonMarker, $html);
        $this->assertStringNotContainsString('"sebelum":', $html);
        $this->assertStringNotContainsString('"sesudah":', $html);
        $this->assertStringNotContainsString('"ip_address":', $html);

        // 2. Verifikasi JSON Response jika requested
        $jsonResponse = $this->actingAs($this->wargaRt5)->json('GET', route('komunitas.pengumuman.show', $announcement->id));
        $jsonResponse->assertStatus(200);

        $jsonString = $jsonResponse->getContent();
        $this->assertStringNotContainsString($fakeIp, $jsonString);
        $this->assertStringNotContainsString($sensitiveJsonMarker, $jsonString);
        $this->assertStringNotContainsString('"ip_address"', $jsonString);
        $this->assertStringNotContainsString('"sebelum"', $jsonString);
        $this->assertStringNotContainsString('"sesudah"', $jsonString);
    }

    /**
     * Scenario D — Scope Authorization:
     * User dari RT lain dilarang (HTTP 403) melihat pengumuman RT 05 beserta aktivitas publiknya.
     */
    public function test_scenario_d_other_rt_cannot_see_announcement_or_public_activity(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'author_id' => $this->ketuaRt5->id,
            'judul' => 'Pengumuman Khusus RT 05',
            'konten' => 'Hanya untuk warga RT 05.',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        AuditLog::create([
            'rt_id' => $this->rt5->id,
            'rw_id' => $this->rw3->id,
            'user_id' => $this->ketuaRt5->id,
            'actor_nama' => $this->ketuaRt5->nama,
            'actor_role' => 'ketua_rt',
            'aksi' => AuditAction::ANNOUNCEMENT_UPDATED,
            'target_type' => 'announcements',
            'target_id' => $announcement->id,
            'alasan' => 'Ralat khusus RT 05',
        ]);

        // Warga RT 06 mencoba membuka detail pengumuman RT 05 -> Harus 403
        $response = $this->actingAs($this->wargaRt6)->get(route('komunitas.pengumuman.show', $announcement->id));
        $response->assertStatus(403);
    }

    /**
     * Scenario E — Version Isolation:
     * Aktivitas V2 tidak muncul di halaman V1, dan aktivitas V1 tidak tertukar dengan V2.
     */
    public function test_scenario_e_version_isolation_activities_do_not_leak_across_versions(): void
    {
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'author_id' => $this->ketuaRt5->id,
            'judul' => 'Pengumuman Awal V1',
            'konten' => 'Konten V1.',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => true,
        ]);

        $v2 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'author_id' => $this->ketuaRt5->id,
            'judul' => 'Pengumuman Lanjutan V2',
            'konten' => 'Konten V2.',
            'tipe' => 'PENTING',
            'replaces_announcement_id' => $v1->id,
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        // Audit log V2: diperbarui
        AuditLog::create([
            'rt_id' => $this->rt5->id,
            'rw_id' => $this->rw3->id,
            'user_id' => $this->ketuaRt5->id,
            'actor_nama' => $this->ketuaRt5->nama,
            'actor_role' => 'ketua_rt',
            'aksi' => AuditAction::ANNOUNCEMENT_UPDATED,
            'target_type' => 'announcements',
            'target_id' => $v2->id,
            'alasan' => 'Alasan Spesifik Versi Dua',
        ]);

        // Buka V2: Harus ada aktivitas V2
        $responseV2 = $this->actingAs($this->wargaRt5)->get(route('komunitas.pengumuman.show', $v2->id));
        $responseV2->assertStatus(200);
        $responseV2->assertSee('Alasan Spesifik Versi Dua');

        // Buka V1: TIDAK boleh ada aktivitas V2!
        $responseV1 = $this->actingAs($this->wargaRt5)->get(route('komunitas.pengumuman.show', $v1->id));
        $responseV1->assertStatus(200);
        $responseV1->assertDontSee('Alasan Spesifik Versi Dua');
    }

    /**
     * Scenario F — Existing Audit Internal:
     * Warga tetap 403 saat mencoba mengakses /admin/audit, sedangkan pengurus/admin tetap diizinkan.
     */
    public function test_scenario_f_admin_audit_remains_strictly_forbidden_for_warga(): void
    {
        // 1. Warga RT 05 mencoba akses /admin/audit -> 403
        $wargaResponse = $this->actingAs($this->wargaRt5)->get(route('admin.audit.index'));
        $wargaResponse->assertStatus(403);

        // 2. Warga RT 06 mencoba akses /admin/audit -> 403
        $warga6Response = $this->actingAs($this->wargaRt6)->get(route('admin.audit.index'));
        $warga6Response->assertStatus(403);

        // 3. Pengurus berwenang (Ketua RT 05) boleh mengakses Meja Audit
        $rtResponse = $this->actingAs($this->ketuaRt5)->get(route('admin.audit.index'));
        $rtResponse->assertStatus(200);

        // 4. Ketua RW boleh mengakses Meja Audit
        $rwResponse = $this->actingAs($this->ketuaRw)->get(route('admin.audit.index'));
        $rwResponse->assertStatus(200);

        // 5. Super Admin boleh mengakses Meja Audit
        $saResponse = $this->actingAs($this->superAdmin)->get(route('admin.audit.index'));
        $saResponse->assertStatus(200);
    }

    /**
     * Scenario G — No Duplicate Logging:
     * Membuka halaman pengumuman berkali-kali adalah murni operasi READ dan tidak membuat audit log baru.
     */
    public function test_scenario_g_viewing_announcement_does_not_create_audit_logs(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt5->id,
            'author_id' => $this->ketuaRt5->id,
            'judul' => 'Pengumuman Read Only Test',
            'konten' => 'Mengecek idempotency view.',
            'tipe' => 'INFO',
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $initialAuditCount = AuditLog::count();

        // Warga membuka detail 5 kali
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->wargaRt5)->get(route('komunitas.pengumuman.show', $announcement->id));
            $response->assertStatus(200);
        }

        // Buka feed 3 kali
        for ($i = 0; $i < 3; $i++) {
            $feedResponse = $this->actingAs($this->wargaRt5)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
            $feedResponse->assertStatus(200);
        }

        $this->assertEquals($initialAuditCount, AuditLog::count(), 'Operasi membaca pengumuman tidak boleh membuat audit log baru.');
    }
}
