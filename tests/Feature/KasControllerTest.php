<?php

namespace Tests\Feature;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\KasTransaksi;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KasControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $bendahara;
    protected User $ketuaRt;
    protected User $wakilRt;
    protected User $sekretaris;
    protected User $warga;
    protected User $ketuaRw;
    protected User $bendaharaRtLain;
    protected Rt $rt5;
    protected Rt $rt6;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->bendahara = User::where('nik', '3273021005050004')->first();
        $this->ketuaRt = User::where('nik', '3273021005050001')->first();
        $this->wakilRt = User::where('nik', '3273021005050002')->first();
        $this->sekretaris = User::where('nik', '3273021005050003')->first();
        $this->warga = User::where('nik', '3273021005050010')->first();
        $this->ketuaRw = User::where('nik', '3273021005030001')->first();

        $this->rt5 = Rt::where('nomor_rt', 5)->first();

        // Buat RT 06 dan Bendahara RT 06 untuk pengujian lintas tenant
        $rw = Rw::first();
        $this->rt6 = Rt::create([
            'rw_id' => $rw->id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);

        $this->bendaharaRtLain = User::create([
            'kode_warga' => 'WRG-990001',
            'rt_id' => $this->rt6->id,
            'rw_id' => $rw->id,
            'nik' => '3273021005060004',
            'nama' => 'Bendahara RT 06',
            'status' => 'aktif',
            'password' => bcrypt('password123'),
        ]);
        UserRole::create([
            'user_id' => $this->bendaharaRtLain->id,
            'role' => 'bendahara',
            'assigned_at' => now(),
        ]);
    }

    /**
     * 1. Bendahara dapat membuat transaksi kas baru.
     */
    public function test_bendahara_can_create_kas_transaction(): void
    {
        $response = $this->actingAs($this->bendahara)->post(route('kas.store'), [
            'jenis' => 'masuk',
            'kategori' => 'Iuran Warga Bulanan',
            'nominal' => 1500000,
            'tanggal' => '2026-10-01',
            'keterangan' => 'Penerimaan iuran 30 KK',
        ]);

        $response->assertRedirect(route('kas.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kas_transaksi', [
            'rt_id' => $this->rt5->id,
            'input_by' => $this->bendahara->id,
            'jenis' => 'masuk',
            'kategori' => 'Iuran Warga Bulanan',
            'nominal' => 1500000,
            'is_koreksi' => false,
            'koreksi_dari_id' => null,
        ]);
    }

    /**
     * 2. Ketua RT dapat membuat transaksi kas baru.
     */
    public function test_ketua_rt_can_create_kas_transaction(): void
    {
        $response = $this->actingAs($this->ketuaRt)->post(route('kas.store'), [
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 200000,
            'tanggal' => '2026-10-02',
            'keterangan' => 'Pembelian kertas HVS dan tinta stempel',
        ]);

        $response->assertRedirect(route('kas.index'));
        $this->assertDatabaseHas('kas_transaksi', [
            'rt_id' => $this->rt5->id,
            'input_by' => $this->ketuaRt->id,
            'jenis' => 'keluar',
            'nominal' => 200000,
        ]);
    }

    /**
     * 3. Wakil RT dapat membuat transaksi kas baru.
     */
    public function test_wakil_rt_can_create_kas_transaction(): void
    {
        $response = $this->actingAs($this->wakilRt)->post(route('kas.store'), [
            'jenis' => 'keluar',
            'kategori' => 'Honor Kebersihan & Keamanan',
            'nominal' => 600000,
            'tanggal' => '2026-10-02',
            'keterangan' => 'Honor sampah pos barat',
        ]);

        $response->assertRedirect(route('kas.index'));
        $this->assertDatabaseHas('kas_transaksi', [
            'rt_id' => $this->rt5->id,
            'input_by' => $this->wakilRt->id,
            'jenis' => 'keluar',
            'nominal' => 600000,
        ]);
    }

    /**
     * 4. Warga biasa DITOLAK saat mencoba membuat transaksi kas (HTTP 403).
     */
    public function test_warga_cannot_create_kas_transaction(): void
    {
        $response = $this->actingAs($this->warga)->post(route('kas.store'), [
            'jenis' => 'masuk',
            'kategori' => 'Iuran Warga Bulanan',
            'nominal' => 50000,
            'tanggal' => '2026-10-01',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 5. Sekretaris DITOLAK saat mencoba membuat transaksi kas (HTTP 403).
     */
    public function test_sekretaris_cannot_create_kas_transaction(): void
    {
        $response = $this->actingAs($this->sekretaris)->post(route('kas.store'), [
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 50000,
            'tanggal' => '2026-10-01',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 6. Ketua RW DITOLAK saat mencoba membuat transaksi kas RT (HTTP 403).
     */
    public function test_ketua_rw_cannot_create_kas_transaction(): void
    {
        $response = $this->actingAs($this->ketuaRw)->post(route('kas.store'), [
            'jenis' => 'masuk',
            'kategori' => 'Iuran Warga Bulanan',
            'nominal' => 100000,
            'tanggal' => '2026-10-01',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 7. Pengurus RT lain DITOLAK saat mencoba membuat transaksi di RT lain.
     */
    public function test_pengurus_rt_lain_cannot_create_transaction_for_different_rt(): void
    {
        // Bendahara RT 06 hanya bisa mencatat untuk RT 06.
        // Server context Auth::user()->rt_id mengunci transaksi ke RT 06,
        // tidak akan pernah masuk ke RT 05 meskipun request menyertakan hidden rt_id=5.
        $response = $this->actingAs($this->bendaharaRtLain)->post(route('kas.store'), [
            'rt_id' => $this->rt5->id, // Manipulasi request parameter jahat
            'jenis' => 'masuk',
            'kategori' => 'Iuran Warga Bulanan',
            'nominal' => 750000,
            'tanggal' => '2026-10-01',
        ]);

        $response->assertRedirect(route('kas.index'));

        // Tidak boleh ada data tercatat di RT 05
        $this->assertDatabaseMissing('kas_transaksi', [
            'rt_id' => $this->rt5->id,
            'nominal' => 750000,
        ]);

        // Harus masuk ke RT 06 milik bendahara tersebut
        $this->assertDatabaseHas('kas_transaksi', [
            'rt_id' => $this->rt6->id,
            'nominal' => 750000,
        ]);
    }

    /**
     * 8. Transaksi baru berhasil dibuat beserta catatan Audit Trail lengkap.
     */
    public function test_transaction_creation_records_audit_trail_snapshot(): void
    {
        $this->actingAs($this->bendahara)->post(route('kas.store'), [
            'jenis' => 'masuk',
            'kategori' => 'Sumbangan / Donasi Warga',
            'nominal' => 1000000,
            'tanggal' => '2026-10-01',
            'keterangan' => 'Donasi hamba Allah untuk gapura',
        ]);

        $kas = KasTransaksi::withoutGlobalScopes()->where('nominal', 1000000)->first();
        $this->assertNotNull($kas);

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::KAS_TRANSACTION_CREATED,
            'target_type' => 'kas_transaksi',
            'target_id' => $kas->id,
            'user_id' => $this->bendahara->id,
            'actor_nama' => $this->bendahara->nama,
            'actor_role' => 'bendahara',
            'rt_id' => $this->rt5->id,
        ]);

        $log = AuditLog::where('target_id', $kas->id)->latest()->first();
        $this->assertNull($log->sebelum);
        $this->assertEquals(1000000, $log->sesudah['nominal']);
        $this->assertEquals('masuk', $log->sesudah['jenis']);
        $this->assertEquals('Sumbangan / Donasi Warga', $log->sesudah['kategori']);
    }

    /**
     * 9 & 10. Koreksi berhasil membuat record baru bertaut koreksi_dari_id, audit log tercatat, dan transaksi lama tetap utuh.
     */
    public function test_koreksi_creates_new_record_with_audit_trail_leaving_old_record_untouched(): void
    {
        // 1. Buat transaksi awal T1 (Rp 500.000)
        $t1 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $this->rt5->id,
            'input_by' => $this->bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 500000,
            'keterangan' => 'Beli semen 10 sak',
            'tanggal' => '2026-10-01',
            'is_koreksi' => false,
        ]);

        // 2. Lakukan koreksi T1 -> T2 (Rp 50.000)
        $response = $this->actingAs($this->bendahara)->post(route('kas.koreksi', $t1->id), [
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 50000,
            'tanggal' => '2026-10-01',
            'keterangan' => 'Beli semen 1 sak',
            'alasan_koreksi' => 'Salah ketik kelebihan angka nol satu digit.',
        ]);

        $response->assertRedirect(route('kas.index'));
        $response->assertSessionHas('success');

        // Transaksi lama T1 HARUS TETAP UTUH di database (tidak di-update/tidak dihapus)
        $t1Fresh = KasTransaksi::withoutGlobalScopes()->find($t1->id);
        $this->assertEquals(500000, $t1Fresh->nominal);
        $this->assertEquals('Beli semen 10 sak', $t1Fresh->keterangan);
        $this->assertFalse($t1Fresh->is_koreksi);
        $this->assertNull($t1Fresh->koreksi_dari_id);

        // Transaksi baru T2 berhasil dibuat dengan pointer koreksi_dari_id = T1
        $t2 = KasTransaksi::withoutGlobalScopes()->where('koreksi_dari_id', $t1->id)->first();
        $this->assertNotNull($t2);
        $this->assertEquals(50000, $t2->nominal);
        $this->assertTrue($t2->is_koreksi);
        $this->assertEquals('Salah ketik kelebihan angka nol satu digit.', $t2->catatan_koreksi);

        // Audit log tercatat dengan diff sebelum vs sesudah dan alasan wajib
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::KAS_TRANSACTION_CORRECTED,
            'target_type' => 'kas_transaksi',
            'target_id' => $t2->id,
            'alasan' => 'Salah ketik kelebihan angka nol satu digit.',
            'actor_nama' => $this->bendahara->nama,
            'actor_role' => 'bendahara',
        ]);

        $log = AuditLog::where('target_id', $t2->id)->latest()->first();
        $this->assertEquals(500000, $log->sebelum['nominal']);
        $this->assertEquals(50000, $log->sesudah['nominal']);
    }

    /**
     * 11. Koreksi terhadap transaksi non-leaf (yang sudah pernah dikoreksi lagi) DITOLAK (HTTP 422).
     */
    public function test_koreksi_on_non_leaf_transaction_is_rejected(): void
    {
        // T1
        $t1 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $this->rt5->id,
            'input_by' => $this->bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 500000,
            'keterangan' => 'T1',
            'tanggal' => '2026-10-01',
        ]);

        // T2 (koreksi T1)
        $t2 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $this->rt5->id,
            'input_by' => $this->bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 50000,
            'keterangan' => 'T2',
            'tanggal' => '2026-10-01',
            'is_koreksi' => true,
            'koreksi_dari_id' => $t1->id,
            'catatan_koreksi' => 'Koreksi 1',
        ]);

        // Mencoba mengoreksi T1 kembali HARUS DITOLAK karena T1 sudah punya penerus (T2)
        $response = $this->actingAs($this->bendahara)->post(route('kas.koreksi', $t1->id), [
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 45000,
            'tanggal' => '2026-10-01',
            'keterangan' => 'Koreksi cabang jahat',
            'alasan_koreksi' => 'Mencoba membuat cabang koreksi baru dari T1.',
        ]);

        $response->assertStatus(422);

        // Tidak boleh ada transaksi baru yang koreksi_dari_id = T1 selain T2
        $this->assertEquals(1, KasTransaksi::withoutGlobalScopes()->where('koreksi_dari_id', $t1->id)->count());
    }

    /**
     * 12. Koreksi tanpa alasan koreksi DITOLAK (Validasi error 422).
     */
    public function test_koreksi_without_reason_is_rejected(): void
    {
        $t1 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $this->rt5->id,
            'input_by' => $this->bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 500000,
            'tanggal' => '2026-10-01',
        ]);

        $response = $this->actingAs($this->bendahara)->post(route('kas.koreksi', $t1->id), [
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 50000,
            'tanggal' => '2026-10-01',
            'keterangan' => 'Beli semen 1 sak',
            'alasan_koreksi' => '', // Alasan kosong
        ]);

        $response->assertSessionHasErrors('alasan_koreksi');
    }

    /**
     * 13. Pengurus RT lain dilarang mengoreksi transaksi milik RT 05.
     */
    public function test_cross_rt_officer_cannot_correct_transaction(): void
    {
        $t1 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $this->rt5->id,
            'input_by' => $this->bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 500000,
            'tanggal' => '2026-10-01',
        ]);

        $response = $this->actingAs($this->bendaharaRtLain)->post(route('kas.koreksi', $t1->id), [
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 50000,
            'tanggal' => '2026-10-01',
            'keterangan' => 'Mencoba mengoreksi RT lain',
            'alasan_koreksi' => 'Mencoba mengubah kas RT tetangga.',
        ]);

        // Ditolak dengan 403 atau 404 (karena TenantScope menyembunyikan transaksi RT 05 dari RT 06)
        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));
    }

    /**
     * 14. Validasi server-side: Kategori tidak cocok dengan jenis transaksi ditolak.
     */
    public function test_incompatible_category_preset_is_rejected(): void
    {
        // Memilih kategori pengeluaran saat jenis adalah pemasukan
        $response = $this->actingAs($this->bendahara)->post(route('kas.store'), [
            'jenis' => 'masuk',
            'kategori' => 'Honor Kebersihan & Keamanan', // Preset pengeluaran!
            'nominal' => 500000,
            'tanggal' => '2026-10-01',
        ]);

        $response->assertSessionHasErrors('kategori');

        // Memilih kategori pemasukan saat jenis adalah pengeluaran
        $response2 = $this->actingAs($this->bendahara)->post(route('kas.store'), [
            'jenis' => 'keluar',
            'kategori' => 'Iuran Warga Bulanan', // Preset pemasukan!
            'nominal' => 500000,
            'tanggal' => '2026-10-01',
        ]);

        $response2->assertSessionHasErrors('kategori');
    }

    /**
     * 15. Validasi server-side: Nominal nol atau negatif ditolak.
     */
    public function test_nominal_must_be_positive_integer(): void
    {
        $response = $this->actingAs($this->bendahara)->post(route('kas.store'), [
            'jenis' => 'masuk',
            'kategori' => 'Iuran Warga Bulanan',
            'nominal' => 0,
            'tanggal' => '2026-10-01',
        ]);

        $response->assertSessionHasErrors('nominal');

        $response2 = $this->actingAs($this->bendahara)->post(route('kas.store'), [
            'jenis' => 'masuk',
            'kategori' => 'Iuran Warga Bulanan',
            'nominal' => -50000,
            'tanggal' => '2026-10-01',
        ]);

        $response2->assertSessionHasErrors('nominal');
    }

    /**
     * 16. Atomicity: Jika penyimpanan audit gagal, transaksi kas ikut rollback.
     */
    public function test_atomicity_transaction_rolls_back_if_audit_logger_fails(): void
    {
        $initialCount = KasTransaksi::withoutGlobalScopes()->count();

        // Simulasikan kegagalan saat AuditLog::create terpanggil
        AuditLog::saving(function () {
            throw new \Exception('Simulated database deadlock / audit log disk full');
        });

        try {
            $this->actingAs($this->bendahara)->post(route('kas.store'), [
                'jenis' => 'masuk',
                'kategori' => 'Iuran Warga Bulanan',
                'nominal' => 300000,
                'tanggal' => '2026-10-01',
            ]);
        } catch (\Exception $e) {
            // Tangkap exception simulasi
        }

        // Hitung kembali record di kas_transaksi: HARUS TETAP SAMA seperti sebelum request (rolled back!)
        $finalCount = KasTransaksi::withoutGlobalScopes()->count();
        $this->assertEquals($initialCount, $finalCount, 'Transaksi kas harus rollback jika audit log gagal');
    }

    /**
     * 17. Ketua RW dapat memilih dan melihat kas RT dalam lingkup RW binaannya.
     */
    public function test_ketua_rw_can_select_and_view_rts_within_rw_scope(): void
    {
        $response = $this->actingAs($this->ketuaRw)->get(route('kas.index', ['rt_id' => $this->rt6->id]));

        $response->assertStatus(200);
        $this->assertEquals($this->rt6->id, $response->viewData('targetRtId'));
    }

    /**
     * 18. Cross-Tenant Isolation: Ketua RW DITOLAK (403) jika memanipulasi rt_id ke luar RW binaannya.
     */
    public function test_ketua_rw_cannot_view_rt_outside_own_rw_scope(): void
    {
        // Buat RW 04 dan RT 01 di bawah RW 04
        $klien = \App\Models\Klien::first();
        $rwLain = Rw::create([
            'klien_id' => $klien->id,
            'kode_rw' => '32.73.02.1005-RW04',
            'nomor_rw' => 4,
            'nama' => 'RW 04 Sekeloa',
        ]);
        $rtLuarRw = Rt::create([
            'rw_id' => $rwLain->id,
            'kode_rt' => '32.73.02.1005-RW04-RT01',
            'nomor_rt' => 1,
            'nama' => 'RT 01 RW 04',
        ]);

        // Ketua RW 03 mencoba mengakses RT 01 di RW 04 melalui manipulasi query parameter
        $response = $this->actingAs($this->ketuaRw)->get(route('kas.index', ['rt_id' => $rtLuarRw->id]));

        $response->assertStatus(403);
    }

    /**
     * 19. Warga biasa tidak dapat memanipulasi rt_id (server mengunci pada RT-nya sendiri).
     */
    public function test_warga_cannot_manipulate_rt_id_query_parameter(): void
    {
        // Warga RT 05 mencoba memanipulasi query ?rt_id={rt6}
        $response = $this->actingAs($this->warga)->get(route('kas.index', ['rt_id' => $this->rt6->id]));

        $response->assertStatus(200);
        // Server tetap mengunci targetRtId pada RT 05
        $this->assertEquals($this->rt5->id, $response->viewData('targetRtId'));
    }
}

