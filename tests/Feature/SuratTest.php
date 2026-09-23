<?php

namespace Tests\Feature;

use App\Models\Rt;
use App\Models\Rw;
use App\Models\SuratKelengkapan;
use App\Models\SuratPengajuan;
use App\Models\User;
use App\Models\UserRole;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratTest extends TestCase
{
    use RefreshDatabase;

    protected User $wargaRt5;
    protected User $ketuaRt5;
    protected User $wakilRt5;
    protected User $wargaRt6;
    protected Rt $rt5;
    protected Rt $rt6;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        // Cari user yang sudah ada dari DemoSeeder
        $this->wargaRt5 = User::where('nik', '3273021005050010')->firstOrFail();
        $this->ketuaRt5 = User::where('nik', '3273021005050001')->firstOrFail();
        $this->wakilRt5 = User::where('nik', '3273021005050002')->firstOrFail();
        $this->rt5 = Rt::where('nomor_rt', 5)->firstOrFail();

        // Buat atau cari RT 06 dan warganya untuk uji isolasi multi-tenant
        $this->rt6 = Rt::firstOrCreate(
            ['rw_id' => $this->rt5->rw_id, 'nomor_rt' => 6],
            ['kode_rt' => 'RT-06', 'nama' => 'RT 06']
        );

        $this->wargaRt6 = User::firstOrCreate(
            ['nik' => '3273021005060099'],
            [
                'kode_warga' => 'WRG-RT06-099',
                'rt_id' => $this->rt6->id,
                'rw_id' => $this->rt5->rw_id,
                'email' => 'warga.rt6@wargadigital.id',
                'nama' => 'Warga RT Enam',
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '1990-01-01',
                'alamat' => 'Jl. Sekeloa RT 06',
                'no_hp' => '081299999999',
                'password' => bcrypt('password123'),
                'status' => 'aktif',
            ]
        );
    }

    public function test_warga_can_view_surat_catalog(): void
    {
        $response = $this->actingAs($this->wargaRt5)->get(route('surat.index'));

        $response->assertStatus(200);
        $response->assertSee('Pengajuan surat');
        $response->assertSee('Surat Domisili (SKD)');
        $response->assertSee('Surat Keterangan Usaha (SKU)');
        $response->assertSee('Surat Keterangan Tidak Mampu (SKTM)');
    }

    public function test_warga_can_submit_surat_application(): void
    {
        $response = $this->actingAs($this->wargaRt5)->post(route('surat.store'), [
            'jenis_surat' => 'SKD',
            'alamat_domisili' => 'Jl. Sekeloa No. 15 RT 05 RW 03',
            'lama_tinggal' => '4 Tahun',
            'keperluan' => 'Persyaratan Pembukaan Rekening Bank BCA',
        ]);

        $surat = SuratPengajuan::where('user_id', $this->wargaRt5->id)
            ->where('jenis_surat', 'SKD')
            ->latest('id')
            ->first();

        $this->assertNotNull($surat);
        $this->assertEquals('MENUNGGU', $surat->status);
        $this->assertEquals($this->rt5->id, $surat->rt_id);
        $this->assertEquals('Persyaratan Pembukaan Rekening Bank BCA', $surat->form_data['keperluan']);

        $response->assertRedirect(route('surat.show', $surat->id));
    }

    public function test_ketua_rt_can_view_meja_verifikasi_and_approve_surat(): void
    {
        // Buat surat pengajuan
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKU',
            'form_data' => [
                'nama_usaha' => 'Toko Kelontong Berkah',
                'bidang_usaha' => 'Perdagangan Sembako',
                'alamat_usaha' => 'Jl. Sekeloa No. 5 RT 05',
                'keperluan' => 'Pengajuan Pinjaman Modal Usaha',
            ],
            'status' => 'MENUNGGU',
        ]);

        // Ketua RT akses meja kerja
        $resAdmin = $this->actingAs($this->ketuaRt5)->get(route('admin.surat.index'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Meja Verifikasi Surat');

        // Ketua RT approve surat
        $resApprove = $this->actingAs($this->ketuaRt5)->post(route('admin.surat.approve', $surat->id));
        $resApprove->assertRedirect(route('surat.show', $surat->id));

        $surat->refresh();
        $this->assertEquals('DISETUJUI', $surat->status);
        $this->assertNotNull($surat->nomor_surat);
        $this->assertStringContainsString('RT05-RW03', $surat->nomor_surat);
        $this->assertEquals($this->ketuaRt5->id, $surat->reviewed_by);

        // Pastikan tercatat di audit trail
        $audit = AuditLog::where('aksi', 'approve_surat')
            ->where('target_id', $surat->id)
            ->first();
        $this->assertNotNull($audit);
    }

    public function test_wakil_rt_has_identical_permission_to_approve_surat(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKTM',
            'form_data' => [
                'pekerjaan' => 'Buruh Cuci',
                'penghasilan_per_bulan' => 'Rp 1.000.000',
                'jumlah_tanggungan' => 3,
                'keperluan' => 'Keringanan Biaya Sekolah Anak',
            ],
            'status' => 'MENUNGGU',
        ]);

        // Wakil RT menyetujui surat
        $response = $this->actingAs($this->wakilRt5)->post(route('admin.surat.approve', $surat->id));
        $response->assertRedirect(route('surat.show', $surat->id));

        $surat->refresh();
        $this->assertEquals('DISETUJUI', $surat->status);
        $this->assertNotNull($surat->nomor_surat);
    }

    public function test_ketua_rt_can_reject_surat_with_reason(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'form_data' => [
                'alamat_domisili' => 'Alamat Luar Wilayah',
                'keperluan' => 'Urus Berkas',
            ],
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->ketuaRt5)->post(route('admin.surat.reject', $surat->id), [
            'alasan_tolak' => 'Alamat yang diajukan tidak berada di lingkungan wilayah RT 05.',
        ]);

        $response->assertRedirect(route('surat.show', $surat->id));

        $surat->refresh();
        $this->assertEquals('DITOLAK', $surat->status);
        $this->assertEquals('Alamat yang diajukan tidak berada di lingkungan wilayah RT 05.', $surat->alasan_tolak);

        // Audit log tercatat
        $audit = AuditLog::where('aksi', 'tolak_surat')
            ->where('target_id', $surat->id)
            ->first();
        $this->assertNotNull($audit);
        $this->assertEquals('Alamat yang diajukan tidak berada di lingkungan wilayah RT 05.', $audit->alasan);
    }

    public function test_request_completion_and_warga_reply_reverts_to_menunggu(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SPKK',
            'form_data' => [
                'alasan_permohonan' => 'Membentuk Keluarga Baru',
                'nama_kepala_keluarga' => 'Hendra Pratama',
                'jumlah_anggota' => 2,
                'keperluan' => 'Penerbitan Kartu Keluarga Baru',
            ],
            'status' => 'MENUNGGU',
        ]);

        // Ketua RT minta kelengkapan
        $resReq = $this->actingAs($this->ketuaRt5)->post(route('admin.surat.request-completion', $surat->id), [
            'pesan' => 'Mohon lampirkan foto Buku Nikah asli.',
        ]);
        $resReq->assertRedirect(route('surat.show', $surat->id));

        $surat->refresh();
        $this->assertEquals('PERLU_KELENGKAPAN', $surat->status);

        // Warga kirim tanggapan perbaikan
        $resReply = $this->actingAs($this->wargaRt5)->post(route('surat.kelengkapan', $surat->id), [
            'pesan' => 'Baik Pak RT, berikut sudah saya lampirkan foto buku nikahnya.',
        ]);
        $resReply->assertRedirect(route('surat.show', $surat->id));

        $surat->refresh();
        // Sesuai alur SDD: status kembali ke MENUNGGU
        $this->assertEquals('MENUNGGU', $surat->status);
    }

    public function test_approved_surat_generates_valid_pdf_download(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'nomor_surat' => '001/RT05-RW03/SKD/IX/2026',
            'form_data' => [
                'alamat_domisili' => 'Jl. Sekeloa RT 05 RW 03',
                'keperluan' => 'Pencetakan Kartu BPJS Kesehatan',
            ],
            'status' => 'DISETUJUI',
            'reviewed_by' => $this->ketuaRt5->id,
        ]);

        $response = $this->actingAs($this->wargaRt5)->get(route('surat.download-pdf', $surat->id));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_tenant_isolation_prevents_viewing_surat_of_other_rt(): void
    {
        // Surat milik RT 05
        $suratRt5 = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'form_data' => ['keperluan' => 'Rahasia RT 5'],
            'status' => 'MENUNGGU',
        ]);

        // Warga RT 06 mencoba mengakses surat milik warga RT 05
        // Karena TenantScope aktif, kueri findOrFail pada RT 06 akan menghasilkan 404 (tidak ditemukan di lingkup RT 06)
        $response = $this->actingAs($this->wargaRt6)->get(route('surat.show', $suratRt5->id));

        $response->assertStatus(404);
    }
}
