<?php

namespace Tests\Feature;

use App\Models\Rt;
use App\Models\SuratPengajuan;
use App\Models\User;
use App\Services\SuratNumberGenerator;
use App\Services\SuratPdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiSuratTest extends TestCase
{
    use RefreshDatabase;

    protected User $warga;
    protected User $ketuaRt;
    protected Rt $rt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->warga = User::where('nik', '3273021005050010')->firstOrFail();
        $this->ketuaRt = User::where('nik', '3273021005050001')->firstOrFail();
        $this->rt = Rt::where('nomor_rt', 5)->firstOrFail();
    }

    /**
     * Helper membuat surat yang disetujui beserta kode verifikasinya.
     */
    protected function createApprovedSurat(string $jenis = 'SKD', array $extraFormData = []): SuratPengajuan
    {
        $nomorSurat = SuratNumberGenerator::generate($this->rt, $jenis);
        $kodeVerifikasi = strtoupper(substr(hash('sha256', $nomorSurat . '1' . now()->toDateTimeString()), 0, 16));

        $formData = array_merge([
            'keperluan' => 'Keperluan dinas dan administrasi',
            'alamat_domisili' => 'Jl. Cisitu Lama No. 45 RT 05 RW 03',
        ], $extraFormData);

        return SuratPengajuan::create([
            'rt_id' => $this->rt->id,
            'user_id' => $this->warga->id,
            'jenis_surat' => $jenis,
            'nomor_surat' => $nomorSurat,
            'kode_verifikasi' => $kodeVerifikasi,
            'form_data' => $formData,
            'status' => 'DISETUJUI',
            'reviewed_by' => $this->ketuaRt->id,
        ]);
    }

    /**
     * 1. Kode valid dengan status DISETUJUI -> metadata yang diizinkan muncul
     */
    public function test_kode_valid_status_disetujui_menampilkan_metadata_yang_diizinkan(): void
    {
        $surat = $this->createApprovedSurat('SKD');

        // Uji akses via rute dengan parameter /verifikasi/{kode}
        $response = $this->get('/verifikasi/' . $surat->kode_verifikasi);

        $response->assertStatus(200);
        $response->assertSee('DATA TERCATAT RESMI');
        $response->assertSee('Kode ini cocok dengan data penerbitan yang tercatat di sistem Warga Digital.');
        $response->assertSee($surat->nomor_surat);
        $response->assertSee('Surat Keterangan Domisili');
        $response->assertSee(strtoupper($this->warga->nama));
        $response->assertSee('RT 05 / RW 03');
        $response->assertSee('Sekeloa');
        $response->assertSee($surat->kode_verifikasi);

        // Uji akses via form query string /verifikasi?kode=...
        $responseForm = $this->get('/verifikasi?kode=' . $surat->kode_verifikasi);
        $responseForm->assertStatus(200);
        $responseForm->assertSee($surat->nomor_surat);
    }

    /**
     * 2. Kode invalid / tidak ditemukan -> response generik tanpa membocorkan detail internal
     */
    public function test_kode_invalid_atau_tidak_ditemukan_menampilkan_pesan_generik(): void
    {
        $response = $this->get('/verifikasi/A1B2C3D4E5F67890');

        $response->assertStatus(200);
        $response->assertSee('Kode verifikasi tidak ditemukan atau dokumen tidak dapat diverifikasi.');
        $response->assertDontSee('DATA TERCATAT RESMI');
        $response->assertDontSee('Exception');
        $response->assertDontSee('SQLSTATE');
    }

    /**
     * 3. Kode format salah / malformed -> tidak menghasilkan error 500, melainkan pesan generik
     */
    public function test_kode_malformed_tidak_menyebabkan_error_500(): void
    {
        $malformedCodes = ['pendek', '12345', '!@#$%^&*()_+', 'hurufnonhexz12345', str_repeat('A', 50)];

        foreach ($malformedCodes as $badCode) {
            $response = $this->get('/verifikasi/' . urlencode($badCode));
            $response->assertStatus(200);
            $response->assertSee('Kode verifikasi tidak ditemukan atau dokumen tidak dapat diverifikasi.');
        }
    }

    /**
     * 4. Surat dengan status selain DISETUJUI -> tidak dikonfirmasi sebagai valid
     */
    public function test_surat_status_selain_disetujui_tidak_dikonfirmasi_valid_dan_berpesan_generik(): void
    {
        $statuses = ['MENUNGGU', 'DIREVIEW', 'PERLU_KELENGKAPAN', 'DITOLAK'];

        foreach ($statuses as $idx => $status) {
            $kode = sprintf('TESTSTATUS%06d', $idx);
            $surat = SuratPengajuan::create([
                'rt_id' => $this->rt->id,
                'user_id' => $this->warga->id,
                'jenis_surat' => 'SKD',
                'nomor_surat' => "TEST/{$status}/2026",
                'kode_verifikasi' => $kode,
                'form_data' => ['keperluan' => 'Testing status'],
                'status' => $status,
                'reviewed_by' => $this->ketuaRt->id,
            ]);

            $response = $this->get('/verifikasi/' . $kode);

            $response->assertStatus(200);
            $response->assertDontSee('DATA TERCATAT RESMI');
            $response->assertDontSee($surat->nomor_surat);
            // Harus menampilkan response error generik yang identik dengan kode tidak ditemukan
            $response->assertSee('Kode verifikasi tidak ditemukan atau dokumen tidak dapat diverifikasi.');
        }
    }

    /**
     * 5. Response / halaman tidak mengandung NIK, alamat lengkap, atau field sensitif untuk keenam jenis surat
     */
    public function test_halaman_verifikasi_tidak_membocorkan_nik_alamat_lengkap_dan_data_sensitif(): void
    {
        $testCases = [
            'SKD' => ['alamat_domisili' => 'Jl. Rahasia No. 99 RT 05 RW 03'],
            'SKU' => ['nama_usaha' => 'Warung Nasi Super Rahasia', 'bidang_usaha' => 'Kuliner Malam'],
            'SKTM' => ['alasan_sktm' => 'Keluarga prasejahtera pendapatan rendah'],
            'SPKK' => ['nama_kepala_keluarga' => 'Bambang Sukses', 'alasan_permohonan' => 'Pecah Kartu Keluarga'],
            'SKL' => ['nama_anak' => 'Anak Bayi Rahasia', 'nama_ibu' => 'Siti Rahasia'],
            'SKKm' => ['nama_almarhum' => 'Almarhum Rahasia', 'penyebab' => 'Penyakit Kronis Rahasia'],
        ];

        foreach ($testCases as $jenis => $extraData) {
            $surat = $this->createApprovedSurat($jenis, $extraData);
            $response = $this->get('/verifikasi/' . $surat->kode_verifikasi);

            $response->assertStatus(200);

            // NIK pemohon TIDAK BOLEH muncul dalam bentuk apa pun
            $response->assertDontSee($this->warga->nik);

            // Alamat profil lengkap TIDAK BOLEH bocor
            $response->assertDontSee($this->warga->alamat);

            // Data spesifik sensitif form_data TIDAK BOLEH bocor
            foreach ($extraData as $key => $val) {
                $response->assertDontSee($val);
            }

            // Tidak ada link unduh file atau lampiran
            $response->assertDontSee('unduh-pdf');
            $response->assertDontSee('storage/surat_dokumen');
        }
    }

    /**
     * 6. Rate limiting bekerja sesuai baseline yang ditetapkan (10 req/menit)
     */
    public function test_rate_limiting_bekerja_pada_rute_verifikasi(): void
    {
        // 10 request pertama diizinkan
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/verifikasi');
            $response->assertStatus(200);
        }

        // Request ke-11 harus diblokir oleh throttle middleware (HTTP 429 Too Many Requests)
        $response11 = $this->get('/verifikasi');
        $response11->assertStatus(429);
    }

    /**
     * 7. Tidak ada dua surat dengan kode verifikasi yang sama (uniqueness & collision handling)
     */
    public function test_keunikan_kode_verifikasi_dan_penanganan_collision(): void
    {
        $surat1 = $this->createApprovedSurat('SKD');
        $surat2 = $this->createApprovedSurat('SKU');

        $this->assertNotEmpty($surat1->kode_verifikasi);
        $this->assertNotEmpty($surat2->kode_verifikasi);
        $this->assertNotEquals($surat1->kode_verifikasi, $surat2->kode_verifikasi);
    }

    /**
     * 8. Endpoint hanya mengembalikan data record yang cocok dengan kode itu saja
     */
    public function test_endpoint_hanya_mengembalikan_record_yang_cocok_tidak_membocorkan_record_lain(): void
    {
        $suratA = $this->createApprovedSurat('SKD');
        $suratB = $this->createApprovedSurat('SKU');

        $responseA = $this->get('/verifikasi/' . $suratA->kode_verifikasi);
        $responseA->assertStatus(200);
        $responseA->assertSee($suratA->nomor_surat);
        $responseA->assertDontSee($suratB->nomor_surat);
    }

    /**
     * 9. Konsistensi: kode di PDF == kode di database == kode yang diterima endpoint
     */
    public function test_konsistensi_kode_pdf_sama_dengan_database_dan_endpoint_verifikasi(): void
    {
        $surat = $this->createApprovedSurat('SKD');

        // Pastikan kode di database ada
        $kodeDb = $surat->kode_verifikasi;
        $this->assertNotNull($kodeDb);

        // Render view PDF melalui helper SuratPdfGenerator
        $pdfHtml = view('surat.pdf.template', [
            'surat' => $surat,
            'user' => $surat->user,
            'rt' => $surat->rt,
            'rw' => $surat->rt->rw,
            'klien' => $surat->rt->rw->klien,
            'reviewer' => $surat->reviewer,
            'jabatanPenandatangan' => SuratPdfGenerator::getJabatanPenandatangan($surat->reviewer, $surat->rt, $surat->rt->rw)[0],
            'subJabatanPenandatangan' => SuratPdfGenerator::getJabatanPenandatangan($surat->reviewer, $surat->rt, $surat->rt->rw)[1],
            'namaJenisSurat' => SuratPdfGenerator::getNamaJenisSurat($surat->jenis_surat),
            'verificationCode' => $surat->kode_verifikasi,
            'tanggalSurat' => '26 September 2026',
            'alamatCetak' => 'Jl. Sekeloa',
        ])->render();

        // Kode di dalam PDF harus mencantumkan kode dari database
        $this->assertStringContainsString($kodeDb, $pdfHtml);

        // Dan endpoint verifikasi harus valid dengan kode tersebut
        $response = $this->get('/verifikasi/' . $kodeDb);
        $response->assertStatus(200);
        $response->assertSee('DATA TERCATAT RESMI');
    }

    /**
     * 10. Endpoint bisa diakses tanpa login, sementara rute terproteksi tetap aman
     */
    public function test_endpoint_bisa_diakses_tanpa_login_sedangkan_rute_lain_terproteksi(): void
    {
        // Publik bisa akses verifikasi
        $this->get('/verifikasi')->assertStatus(200);

        // Namun rute auth tetap redirect ke login
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/surat')->assertRedirect('/login');
        $this->get('/admin/surat')->assertRedirect('/login');
    }

    /**
     * 11. Workflow approval pengurus RT menghasilkan kode verifikasi dan mencatat audit log
     */
    public function test_workflow_approval_menghasilkan_kode_verifikasi_dan_audit_log(): void
    {
        $surat = SuratPengajuan::create([
            'rt_id' => $this->rt->id,
            'user_id' => $this->warga->id,
            'jenis_surat' => 'SKD',
            'form_data' => ['keperluan' => 'Melamar pekerjaan', 'alamat_domisili' => 'Jl. Sekeloa No. 12'],
            'status' => 'MENUNGGU',
        ]);

        $this->assertNull($surat->kode_verifikasi);
        $this->assertNull($surat->nomor_surat);

        // Ketua RT menyetujui surat
        $response = $this->actingAs($this->ketuaRt)->post(route('admin.surat.approve', $surat->id));
        $response->assertRedirect(route('surat.show', $surat->id));

        $suratFresh = $surat->fresh();
        $this->assertEquals('DISETUJUI', $suratFresh->status);
        $this->assertNotNull($suratFresh->nomor_surat);
        $this->assertNotNull($suratFresh->kode_verifikasi);
        $this->assertEquals(16, strlen($suratFresh->kode_verifikasi));

        // Verifikasi keabsahan melalui endpoint publik
        $this->post('/logout'); // Pastikan sudah logout
        $pubResponse = $this->get('/verifikasi/' . $suratFresh->kode_verifikasi);
        $pubResponse->assertStatus(200);
        $pubResponse->assertSee($suratFresh->nomor_surat);
        $pubResponse->assertSee('DATA TERCATAT RESMI');
    }
}
