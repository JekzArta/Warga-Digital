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
        $fileKtp = \Illuminate\Http\UploadedFile::fake()->create('ktp_warga.jpg', 200, 'image/jpeg');

        $response = $this->actingAs($this->wargaRt5)->post(route('surat.store'), [
            'jenis_surat' => 'SKD',
            'alamat_domisili' => 'Jl. Sekeloa No. 15 RT 05 RW 03',
            'lama_tinggal' => '4 Tahun',
            'keperluan' => 'Persyaratan Pembukaan Rekening Bank BCA',
            'dokumen_ktp_kk' => [$fileKtp],
        ]);

        $surat = SuratPengajuan::where('user_id', $this->wargaRt5->id)
            ->where('jenis_surat', 'SKD')
            ->latest('id')
            ->first();

        $this->assertNotNull($surat);
        $this->assertEquals('MENUNGGU', $surat->status);
        $this->assertEquals($this->rt5->id, $surat->rt_id);
        $this->assertEquals('Persyaratan Pembukaan Rekening Bank BCA', $surat->form_data['keperluan']);
        $this->assertNotEmpty($surat->form_data['lampiran']['dokumen_ktp_kk']);

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

    public function test_warga_can_view_surat_show_with_uploaded_file_and_kelengkapan(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'form_data' => [
                'alamat_domisili' => 'Jl. Sekeloa No 10',
                'keperluan' => 'Daftar Kuliah',
                'dokumen_url' => 'surat_dokumen/sample.jpg',
                'dokumen_nama' => 'ktp_asli.jpg',
            ],
            'status' => 'PERLU_KELENGKAPAN',
        ]);

        SuratKelengkapan::create([
            'pengajuan_id' => $surat->id,
            'pesan' => 'Ini berkas tambahan revisi',
            'file_url' => 'surat_kelengkapan/revisi.jpg',
            'dari_role' => 'warga',
        ]);

        $response = $this->actingAs($this->wargaRt5)->get(route('surat.show', $surat->id));

        $response->assertStatus(200);
        $response->assertSee('Berkas Lampiran: ktp_asli.jpg');
        $response->assertSee('Lihat Berkas Perbaikan Terlampir');
    }

    /**
     * TEST 1 — SKD + Ketua RT:
     * PDF terbit dengan nama Ketua RT, jabatan Ketua RT, nomor surat konsisten,
     * terminologi tanpa kata 'pengantar', tanpa glyph '?', dan muat 1 halaman A4.
     */
    public function test_skd_approved_by_ketua_rt_produces_correct_single_page_pdf(): void
    {
        $ketuaRtBaru = User::create([
            'kode_warga' => 'WRG-RT05-998',
            'rt_id' => $this->rt5->id,
            'rw_id' => $this->rt5->rw_id,
            'nik' => '3273021005059998',
            'nama' => 'H. Suherman Sastrawan',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1970-01-01',
            'alamat' => 'Jl. Sekeloa RT 05',
            'no_hp' => '081299998888',
            'password' => bcrypt('password123'),
            'status' => 'aktif',
        ]);
        UserRole::create([
            'user_id' => $ketuaRtBaru->id,
            'role' => 'ketua_rt',
            'assigned_at' => now(),
        ]);

        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'form_data' => [
                'alamat_domisili' => 'Jl. Sekeloa No. 15 RT 05 RW 03',
                'lama_tinggal' => '3 Tahun',
                'keperluan' => 'Pencetakan Kartu Keluarga Baru',
            ],
            'status' => 'MENUNGGU',
        ]);

        // Ketua RT melakukan approval
        $this->actingAs($ketuaRtBaru)->post(route('admin.surat.approve', $surat->id));
        $surat->refresh();

        $this->assertEquals('DISETUJUI', $surat->status);
        $this->assertEquals($ketuaRtBaru->id, $surat->reviewed_by);
        $this->assertNotNull($surat->nomor_surat);

        // Generate PDF
        $pdf = \App\Services\SuratPdfGenerator::generate($surat);
        $output = $pdf->output();

        // 1. Pastikan PDF valid
        $this->assertNotEmpty($output);

        // 2. Pastikan tepat 1 halaman A4
        $pageCount = $pdf->getDomPDF()->get_canvas()->get_page_count();
        $this->assertEquals(1, $pageCount, 'PDF SKD harus muat dalam 1 halaman A4 secara natural.');

        // 3. Render HTML template untuk verifikasi konten tekstual
        $renderedHtml = view('surat.pdf.template', [
            'surat' => $surat,
            'user' => $surat->user,
            'rt' => $surat->rt,
            'rw' => $surat->rt->rw,
            'klien' => $surat->rt->rw->klien,
            'reviewer' => $surat->reviewer,
            'jabatanPenandatangan' => \App\Services\SuratPdfGenerator::getJabatanPenandatangan($surat->reviewer, $surat->rt, $surat->rt->rw)[0],
            'subJabatanPenandatangan' => \App\Services\SuratPdfGenerator::getJabatanPenandatangan($surat->reviewer, $surat->rt, $surat->rt->rw)[1],
            'namaJenisSurat' => \App\Services\SuratPdfGenerator::getNamaJenisSurat($surat->jenis_surat),
            'verificationCode' => 'VALIDATIONTESTCODE',
            'tanggalSurat' => '23 September 2026',
        ])->render();

        // Verifikasi nama dan jabatan penandatangan
        $this->assertStringContainsString('H. Suherman Sastrawan', $renderedHtml);
        $this->assertStringContainsString('Ketua RT 05 / RW 03', $renderedHtml);
        $this->assertStringNotContainsString('Bambang Hartono', $renderedHtml);

        // Verifikasi terminologi SKD (tidak boleh mengandung 'pengantar')
        $this->assertStringContainsString('Surat keterangan ini dibuat', $renderedHtml);
        $this->assertStringNotContainsString('Surat keterangan pengantar ini', $renderedHtml);

        // Verifikasi tidak ada karakter '?' hasil glyph rusak
        $this->assertStringNotContainsString('? TERVERIFIKASI', $renderedHtml);
        $this->assertStringContainsString('TERVERIFIKASI SISTEM', $renderedHtml);

        // Verifikasi nomor surat tercetak konsisten
        $this->assertStringContainsString($surat->nomor_surat, $renderedHtml);
    }

    /**
     * TEST 2 — SKD + Wakil RT:
     * Wakil RT approve -> PDF menampilkan nama Wakil RT dan jabatan Wakil RT (bukan Ketua RT).
     */
    public function test_skd_approved_by_wakil_rt_displays_wakil_rt_title(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'form_data' => [
                'alamat_domisili' => 'Jl. Sekeloa No. 10',
                'keperluan' => 'Pendaftaran BPJS',
            ],
            'status' => 'MENUNGGU',
        ]);

        // Wakil RT approve
        $this->actingAs($this->wakilRt5)->post(route('admin.surat.approve', $surat->id));
        $surat->refresh();

        $this->assertEquals('DISETUJUI', $surat->status);
        $this->assertEquals($this->wakilRt5->id, $surat->reviewed_by);

        // Periksa resolusi jabatan di SuratPdfGenerator
        [$jabatan, $subJabatan] = \App\Services\SuratPdfGenerator::getJabatanPenandatangan($surat->reviewer, $surat->rt, $surat->rt->rw);
        $this->assertEquals('Wakil RT 05 / RW 03', $jabatan);
        $this->assertEquals('Wakil RT 05', $subJabatan);
        $this->assertStringNotContainsString('Ketua RT', $jabatan);

        // Generate PDF
        $pdf = \App\Services\SuratPdfGenerator::generate($surat);
        $this->assertNotEmpty($pdf->output());

        // Render template dan cek teks
        $renderedHtml = view('surat.pdf.template', [
            'surat' => $surat,
            'user' => $surat->user,
            'rt' => $surat->rt,
            'rw' => $surat->rt->rw,
            'klien' => $surat->rt->rw->klien,
            'reviewer' => $surat->reviewer,
            'jabatanPenandatangan' => $jabatan,
            'subJabatanPenandatangan' => $subJabatan,
            'namaJenisSurat' => \App\Services\SuratPdfGenerator::getNamaJenisSurat($surat->jenis_surat),
            'verificationCode' => 'VALIDATIONTESTCODE',
            'tanggalSurat' => '23 September 2026',
        ])->render();

        $this->assertStringContainsString($this->wakilRt5->nama, $renderedHtml);
        $this->assertStringContainsString('Wakil RT 05 / RW 03', $renderedHtml);
        $this->assertStringNotContainsString('Ketua RT 05 / RW 03', $renderedHtml);
    }

    /**
     * TEST 3 — Render Ulang:
     * Render / download PDF berulang kali tidak mengubah nomor surat, reviewer, atau data.
     */
    public function test_re_rendering_pdf_maintains_consistent_nomor_surat_and_data(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'nomor_surat' => '009/RT05-RW03/SKD/IX/2026',
            'form_data' => [
                'alamat_domisili' => 'Jl. Sekeloa RT 05 RW 03',
                'keperluan' => 'Urus Paspor',
            ],
            'status' => 'DISETUJUI',
            'reviewed_by' => $this->ketuaRt5->id,
        ]);

        $initialNomor = $surat->nomor_surat;
        $initialReviewer = $surat->reviewed_by;

        // Render 1
        $pdf1 = \App\Services\SuratPdfGenerator::generate($surat);
        $this->assertNotEmpty($pdf1->output());

        // Render 2
        $pdf2 = \App\Services\SuratPdfGenerator::generate($surat);
        $this->assertNotEmpty($pdf2->output());

        // Download via controller
        $resDownload = $this->actingAs($this->wargaRt5)->get(route('surat.download-pdf', $surat->id));
        $resDownload->assertStatus(200);

        // Verifikasi integritas data di database
        $surat->refresh();
        $this->assertEquals($initialNomor, $surat->nomor_surat, 'Nomor surat tidak boleh berubah saat re-render.');
        $this->assertEquals($initialReviewer, $surat->reviewed_by, 'Reviewer tidak boleh berubah saat re-render.');
        $this->assertEquals('Urus Paspor', $surat->form_data['keperluan']);
    }

    /**
     * TEST 4 — Missing Reviewer Safety:
     * Jika reviewed_by null, sistem tidak mencetak 'Bambang Hartono' atau nama dummy,
     * melainkan melempar InvalidArgumentException dan redirect dengan pesan error.
     */
    public function test_missing_reviewer_safety_prevents_pdf_generation(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'nomor_surat' => '010/RT05-RW03/SKD/IX/2026',
            'form_data' => ['keperluan' => 'Tes Keamanan'],
            'status' => 'DISETUJUI',
            'reviewed_by' => null, // Simulasi data tidak lengkap
        ]);

        // 1. SuratPdfGenerator melempar exception yang jelas
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dokumen tidak dapat diterbitkan karena data penandatangan belum tersedia.');
        \App\Services\SuratPdfGenerator::generate($surat);
    }

    public function test_missing_reviewer_redirects_with_error_flash_in_controller(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'nomor_surat' => '011/RT05-RW03/SKD/IX/2026',
            'form_data' => ['keperluan' => 'Tes Controller Guard'],
            'status' => 'DISETUJUI',
            'reviewed_by' => null,
        ]);

        // 2. Controller downloadPdf memvalidasi dan redirect dengan pesan error
        $response = $this->actingAs($this->wargaRt5)->get(route('surat.download-pdf', $surat->id));
        $response->assertRedirect(route('surat.show', $surat->id));
        $response->assertSessionHas('error', 'Dokumen tidak dapat diterbitkan karena data penandatangan belum tersedia.');
    }

    /**
     * TEST 5 — Test Input:
     * Input keperluan 'Halooo' tetap dipertahankan sebagai data input tanpa modifikasi.
     */
    public function test_input_keperluan_halooo_is_preserved_without_alteration(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'nomor_surat' => '012/RT05-RW03/SKD/IX/2026',
            'form_data' => [
                'alamat_domisili' => 'Jl. Sekeloa RT 05 RW 03',
                'keperluan' => 'Halooo',
            ],
            'status' => 'DISETUJUI',
            'reviewed_by' => $this->ketuaRt5->id,
        ]);

        $pdf = \App\Services\SuratPdfGenerator::generate($surat);
        $this->assertNotEmpty($pdf->output());

        $surat->refresh();
        $this->assertEquals('Halooo', $surat->form_data['keperluan'], 'Nilai keperluan "Halooo" tidak boleh dimodifikasi.');

        // Pastikan muncul di template
        $renderedHtml = view('surat.pdf.template', [
            'surat' => $surat,
            'user' => $surat->user,
            'rt' => $surat->rt,
            'rw' => $surat->rt->rw,
            'klien' => $surat->rt->rw->klien,
            'reviewer' => $surat->reviewer,
            'jabatanPenandatangan' => 'Ketua RT 05 / RW 03',
            'subJabatanPenandatangan' => 'Ketua RT 05',
            'namaJenisSurat' => \App\Services\SuratPdfGenerator::getNamaJenisSurat($surat->jenis_surat),
            'verificationCode' => 'VALIDATIONTESTCODE',
            'tanggalSurat' => '23 September 2026',
        ])->render();

        $this->assertStringContainsString('"Halooo"', $renderedHtml);
    }

    /**
     * Uji Perbaikan Bug 1: PDF SKD memakai alamat domisili yang diinput warga pada form_data,
     * bukan alamat lama pada profil user.
     */
    public function test_skd_pdf_renders_alamat_domisili_from_form_data(): void
    {
        // Alamat KTP profil adalah $this->wargaRt5->alamat
        $alamatDomisiliBaru = 'Jl. Tubagus Ismail VII No. 42B RT 05 RW 03';

        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt5->id,
            'user_id' => $this->wargaRt5->id,
            'jenis_surat' => 'SKD',
            'nomor_surat' => '020/RT05-RW03/SKD/IX/2026',
            'form_data' => [
                'alamat_domisili' => $alamatDomisiliBaru,
                'lama_tinggal' => '2 Tahun',
                'keperluan' => 'Pendaftaran Domisili Usaha',
            ],
            'status' => 'DISETUJUI',
            'reviewed_by' => $this->ketuaRt5->id,
        ]);

        $pdf = \App\Services\SuratPdfGenerator::generate($surat);
        $this->assertNotEmpty($pdf->output());

        $renderedHtml = view('surat.pdf.template', [
            'surat' => $surat,
            'user' => $surat->user,
            'rt' => $surat->rt,
            'rw' => $surat->rt->rw,
            'klien' => $surat->rt->rw->klien,
            'reviewer' => $surat->reviewer,
            'jabatanPenandatangan' => 'Ketua RT 05 / RW 03',
            'subJabatanPenandatangan' => 'Ketua RT 05',
            'namaJenisSurat' => \App\Services\SuratPdfGenerator::getNamaJenisSurat($surat->jenis_surat),
            'verificationCode' => 'DOMISILIVALIDATION',
            'tanggalSurat' => '25 September 2026',
            'alamatCetak' => $alamatDomisiliBaru,
        ])->render();

        $this->assertStringContainsString($alamatDomisiliBaru, $renderedHtml);
    }

    /**
     * Uji Redesain Section 3: Warga bisa upload multiple files per slot,
     * mengisi catatan pemohon, dan dokumen pendukung lain.
     */
    public function test_warga_can_submit_surat_with_multiple_files_per_slot_and_catatan(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $fotoUsaha1 = \Illuminate\Http\UploadedFile::fake()->create('toko_depan.jpg', 300, 'image/jpeg');
        $fotoUsaha2 = \Illuminate\Http\UploadedFile::fake()->create('toko_dalam.jpg', 350, 'image/jpeg');
        $ktpPemilik = \Illuminate\Http\UploadedFile::fake()->create('ktp_asli.jpg', 250, 'image/jpeg');
        $dokTambahan = \Illuminate\Http\UploadedFile::fake()->create('surat_sewa.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->wargaRt5)->post(route('surat.store'), [
            'jenis_surat' => 'SKU',
            'nama_usaha' => 'Laundry Express Bersih',
            'bidang_usaha' => 'Jasa Cuci Pakaian',
            'alamat_usaha' => 'Jl. Sekeloa No. 20 RT 05',
            'lama_usaha' => '6 Bulan',
            'keperluan' => 'Pencairan KUR Bank Mandiri',
            'catatan_pemohon' => 'Tolong dibantu segera ya Pak RT, berkas dibutuhkan besok siang.',
            'dokumen_usaha' => [$fotoUsaha1, $fotoUsaha2],
            'dokumen_ktp' => [$ktpPemilik],
            'dokumen_pendukung_lain' => [$dokTambahan],
        ]);

        $surat = SuratPengajuan::where('user_id', $this->wargaRt5->id)
            ->where('jenis_surat', 'SKU')
            ->latest('id')
            ->first();

        $this->assertNotNull($surat);
        $this->assertEquals('MENUNGGU', $surat->status);
        $this->assertEquals('Tolong dibantu segera ya Pak RT, berkas dibutuhkan besok siang.', $surat->form_data['catatan_pemohon']);

        // Verifikasi struktur lampiran terpisah per kategori slot
        $lampiran = $surat->form_data['lampiran'];
        $this->assertCount(2, $lampiran['dokumen_usaha'], 'Slot dokumen usaha harus berisi 2 file.');
        $this->assertCount(1, $lampiran['dokumen_ktp'], 'Slot dokumen KTP harus berisi 1 file.');
        $this->assertCount(1, $lampiran['dokumen_pendukung_lain'], 'Slot lampiran pendukung lain harus berisi 1 file.');

        $this->assertEquals('Foto Tempat Usaha', $lampiran['dokumen_usaha'][0]['label']);
        $this->assertEquals('KTP', $lampiran['dokumen_ktp'][0]['label']);
        $this->assertEquals('Lampiran Pendukung Lain', $lampiran['dokumen_pendukung_lain'][0]['label']);

        // Pastikan halaman show menampilkan berkas-berkas tersebut
        $resShow = $this->actingAs($this->wargaRt5)->get(route('surat.show', $surat->id));
        $resShow->assertStatus(200);
        $resShow->assertSee('Tolong dibantu segera ya Pak RT');
        $resShow->assertSee('toko_depan.jpg');
        $resShow->assertSee('toko_dalam.jpg');
        $resShow->assertSee('ktp_asli.jpg');
        $resShow->assertSee('surat_sewa.pdf');
    }

    /**
     * Uji Validasi Section 3: Dokumen wajib tidak boleh kosong jika tidak ada lampiran.
     */
    public function test_validation_requires_mandatory_documents_per_letter_type(): void
    {
        $response = $this->actingAs($this->wargaRt5)->post(route('surat.store'), [
            'jenis_surat' => 'SKU',
            'nama_usaha' => 'Warung Kopi',
            'bidang_usaha' => 'Kuliner',
            'alamat_usaha' => 'Jl. Sekeloa RT 05',
            'keperluan' => 'Izin Lingkungan',
            // sengaja tidak melampirkan dokumen_usaha dan dokumen_ktp
        ]);

        $response->assertSessionHasErrors(['dokumen_usaha', 'dokumen_ktp']);
    }
}
