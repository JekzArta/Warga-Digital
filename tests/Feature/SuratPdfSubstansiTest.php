<?php

namespace Tests\Feature;

use App\Models\Rt;
use App\Models\SuratPengajuan;
use App\Models\User;
use App\Services\SuratPdfGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratPdfSubstansiTest extends TestCase
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
     * Helper untuk membuat instance SuratPengajuan berstatus DISETUJUI dengan data spesifik.
     */
    protected function createApprovedSurat(string $jenisSurat, array $formData): SuratPengajuan
    {
        return SuratPengajuan::create([
            'rt_id' => $this->rt->id,
            'user_id' => $this->warga->id,
            'jenis_surat' => $jenisSurat,
            'nomor_surat' => "042/{$jenisSurat}/RT05-RW03/IX/2026",
            'kode_verifikasi' => 'A1B2C3D4E5F67890',
            'form_data' => $formData,
            'status' => 'DISETUJUI',
            'reviewed_by' => $this->ketuaRt->id,
        ]);
    }

    /**
     * Helper untuk render HTML template PDF melalui generator.
     */
    protected function renderPdfHtml(SuratPengajuan $surat): string
    {
        $user = $surat->user;
        $rt = $surat->rt;
        $rw = $rt->rw;
        $klien = $rw->klien;
        $reviewer = $surat->reviewer;

        [$jabatanPenandatangan, $subJabatanPenandatangan] = SuratPdfGenerator::getJabatanPenandatangan($reviewer, $rt, $rw);

        $alamatCetak = !empty($surat->form_data['alamat_domisili'])
            ? $surat->form_data['alamat_domisili']
            : ($user->alamat ?? ('RT 0' . ($rt->nomor_rt ?? 5) . ' / RW 0' . ($rw->nomor_rw ?? 3)));

        $data = [
            'surat' => $surat,
            'user' => $user,
            'rt' => $rt,
            'rw' => $rw,
            'klien' => $klien,
            'reviewer' => $reviewer,
            'jabatanPenandatangan' => $jabatanPenandatangan,
            'subJabatanPenandatangan' => $subJabatanPenandatangan,
            'namaJenisSurat' => SuratPdfGenerator::getNamaJenisSurat($surat->jenis_surat),
            'verificationCode' => $surat->kode_verifikasi,
            'tanggalSurat' => Carbon::parse($surat->updated_at)->translatedFormat('d F Y'),
            'alamatCetak' => $alamatCetak,
        ];

        return view('surat.pdf.template', $data)->render();
    }

    /**
     * Uji P0: SKL (Surat Keterangan Kelahiran)
     * - Objek utama adalah bayi/kelahiran.
     * - Pemohon adalah pelapor/orang tua.
     * - Field nama anak, jenis kelamin, tanggal lahir, orang tua wajib tercetak.
     */
    public function test_skl_renders_baby_information_and_reporter_identity(): void
    {
        $surat = $this->createApprovedSurat('SKL', [
            'nama_anak' => 'Ahmad Fauzi Zaky',
            'jenis_kelamin_anak' => 'L',
            'tanggal_lahir_anak' => '2026-09-15',
            'nama_ibu' => 'Siti Nurhaliza',
            'nama_ayah' => 'Budi Santoso',
            'keperluan' => 'Penerbitan Akta Kelahiran Disdukcapil',
        ]);

        $html = $this->renderPdfHtml($surat);

        // Header & Judul
        $this->assertStringContainsString('SURAT KETERANGAN KELAHIRAN', $html);
        $this->assertStringContainsString('042/SKL/RT05-RW03/IX/2026', $html);

        // Data Pelapor (Orang Tua)
        $this->assertStringContainsString('Data Pelapor / Orang Tua', $html);
        $this->assertStringContainsString(strtoupper($this->warga->nama), $html);
        $this->assertStringContainsString($this->warga->nik, $html);

        // Data Subjek Utama (Bayi / Anak)
        $this->assertStringContainsString('Data Kelahiran Bayi / Anak', $html);
        $this->assertStringContainsString('AHMAD FAUZI ZAKY', $html);
        $this->assertStringContainsString('Laki-Laki', $html);
        $this->assertStringContainsString('15 September 2026', $html);
        $this->assertStringContainsString('SITI NURHALIZA', $html);
        $this->assertStringContainsString('BUDI SANTOSO', $html);

        // Keterangan & Tujuan
        $this->assertStringContainsString('anak tersebut di atas adalah benar putra/putri dari pasangan suami istri', $html);
        $this->assertStringContainsString('Penerbitan Akta Kelahiran Disdukcapil', $html);
    }

    /**
     * Uji P0: SKKm (Surat Keterangan Kematian)
     * - Objek utama adalah almarhum/kematian.
     * - Pemohon adalah pelapor/keluarga.
     * - Field nama almarhum, tanggal meninggal, tempat meninggal wajib tercetak.
     * - NEGATIVE ASSERTION: Field 'penyebab' kematian TIDAK BOLEH tercetak di PDF.
     */
    public function test_skkm_renders_deceased_information_and_omits_medical_cause_of_death(): void
    {
        $surat = $this->createApprovedSurat('SKKm', [
            'nama_almarhum' => 'Haji Mansyur Hidayat',
            'tanggal_meninggal' => '2026-09-20',
            'tempat_meninggal' => 'Rumah Sakit Hasan Sadikin Bandung',
            'penyebab' => 'Komplikasi Gagal Ginjal Kronis Stadium 4', // data sensitif medis
            'keperluan' => 'Pencatatan Akta Kematian dan Pengurusan Waris',
        ]);

        $html = $this->renderPdfHtml($surat);

        // Header & Judul
        $this->assertStringContainsString('SURAT KETERANGAN KEMATIAN', $html);
        $this->assertStringContainsString('042/SKKm/RT05-RW03/IX/2026', $html);

        // Data Pelapor / Keluarga
        $this->assertStringContainsString('Data Pelapor / Keluarga', $html);
        $this->assertStringContainsString(strtoupper($this->warga->nama), $html);

        // Data Subjek Utama (Almarhum)
        $this->assertStringContainsString('Data Almarhum / Almarhumah', $html);
        $this->assertStringContainsString('HAJI MANSYUR HIDAYAT', $html);
        $this->assertStringContainsString('20 September 2026', $html);
        $this->assertStringContainsString('Rumah Sakit Hasan Sadikin Bandung', $html);

        // NEGATIVE ASSERTION: Penyebab kematian tidak boleh ada di dokumen PDF
        $this->assertStringNotContainsString('Komplikasi Gagal Ginjal Kronis Stadium 4', $html);
        $this->assertStringNotContainsString('Penyebab Kematian', $html);

        // Keterangan Kematian
        $this->assertStringContainsString('telah meninggal dunia warga dengan rincian data sebagai berikut', $html);
        $this->assertStringContainsString('almarhum/almarhumah tersebut di atas semasa hidupnya adalah benar warga', $html);
    }

    /**
     * Uji P1: SKTM (Surat Keterangan Tidak Mampu)
     * - Kondisi ekonomi dicetak kualitatif.
     * - Jumlah tanggungan tercetak.
     * - NEGATIVE ASSERTION: Angka nominal penghasilan rupiah TIDAK BOLEH tercetak di PDF.
     */
    public function test_sktm_renders_qualitative_poverty_status_and_omits_nominal_income(): void
    {
        $surat = $this->createApprovedSurat('SKTM', [
            'pekerjaan' => 'Buruh Cuci Harian',
            'penghasilan_per_bulan' => 'Rp 850.000 / Bulan', // nominal sensitif
            'jumlah_tanggungan' => 4,
            'keperluan' => 'Pengajuan KIP Kuliah 2026',
        ]);

        $html = $this->renderPdfHtml($surat);

        // Header & Judul
        $this->assertStringContainsString('SURAT KETERANGAN TIDAK MAMPU', $html);

        // Pekerjaan pemohon
        $this->assertStringContainsString('Buruh Cuci Harian', $html);

        // Keterangan kualitatif dan tanggungan
        $this->assertStringContainsString('keluarga berpenghasilan rendah / kurang mampu', $html);
        $this->assertStringContainsString('4 orang', $html);

        // NEGATIVE ASSERTION: Angka nominal penghasilan rupiah tidak boleh dicetak
        $this->assertStringNotContainsString('Rp 850.000', $html);
        $this->assertStringNotContainsString('850.000', $html);
        $this->assertStringNotContainsString('Penghasilan Per Bulan', $html);
    }

    /**
     * Uji P1: SPKK (Surat Pengantar Kartu Keluarga)
     * - Objek permohonan Kartu Keluarga tercetak lengkap: alasan, kepala KK, jumlah anggota.
     */
    public function test_spkk_renders_family_card_request_details(): void
    {
        $surat = $this->createApprovedSurat('SPKK', [
            'alasan_permohonan' => 'Membentuk Keluarga Baru',
            'nama_kepala_keluarga' => 'Bambang Pamungkas',
            'jumlah_anggota' => 2,
            'keperluan' => 'Penerbitan Kartu Keluarga Baru Pasca Menikah',
        ]);

        $html = $this->renderPdfHtml($surat);

        // Header & Judul
        $this->assertStringContainsString('SURAT PENGANTAR KARTU KELUARGA', $html);

        // Rincian Permohonan KK
        $this->assertStringContainsString('Rincian Permohonan Kartu Keluarga', $html);
        $this->assertStringContainsString('Membentuk Keluarga Baru', $html);
        $this->assertStringContainsString('BAMBANG PAMUNGKAS', $html);
        $this->assertStringContainsString('2 Orang / Jiwa', $html);

        // Redaksi Surat Pengantar
        $this->assertStringContainsString('Surat pengantar ini dibuat dan diberikan', $html);
        $this->assertStringContainsString('Demikian surat pengantar ini kami terbitkan', $html);
    }

    /**
     * Uji P2: SKU (Surat Keterangan Usaha)
     * - Field lama_usaha yang sebelumnya hilang kini wajib tercetak.
     * - Kalimat penegasan memiliki dan menjalankan kegiatan usaha tercetak.
     */
    public function test_sku_renders_business_duration_and_operational_statement(): void
    {
        $surat = $this->createApprovedSurat('SKU', [
            'nama_usaha' => 'Kedai Kopi Sekeloa Barokah',
            'bidang_usaha' => 'Minuman dan Makanan Ringan',
            'lama_usaha' => '2 Tahun 6 Bulan',
            'alamat_usaha' => 'Jl. Sekeloa Raya No. 45 RT 04 RW 03',
            'keperluan' => 'Syarat Pengajuan KUR Mikro Bank Mandiri',
        ]);

        $html = $this->renderPdfHtml($surat);

        // Header & Judul
        $this->assertStringContainsString('SURAT KETERANGAN USAHA', $html);

        // Data Usaha Lengkap
        $this->assertStringContainsString('KEDAI KOPI SEKELOA BAROKAH', $html);
        $this->assertStringContainsString('Minuman dan Makanan Ringan', $html);
        $this->assertStringContainsString('2 Tahun 6 Bulan', $html); // field lama_usaha
        $this->assertStringContainsString('Jl. Sekeloa Raya No. 45 RT 04 RW 03', $html);

        // Keterangan eksplisit kepemilikan dan kegiatan usaha
        $this->assertStringContainsString('benar memiliki serta menjalankan kegiatan usaha tersebut di alamat yang tercantum', $html);
    }

    /**
     * Uji P2: SKD (Surat Keterangan Domisili)
     * - Field lama_tinggal yang sebelumnya hilang kini wajib tercetak.
     */
    public function test_skd_renders_residence_duration(): void
    {
        $surat = $this->createApprovedSurat('SKD', [
            'alamat_domisili' => 'Jl. Dipatiukur Gg. Sekeloa No. 12 RT 05 RW 03',
            'lama_tinggal' => '7 Tahun',
            'keperluan' => 'Persyaratan Pembukaan Rekening Bank',
        ]);

        $html = $this->renderPdfHtml($surat);

        // Header & Judul
        $this->assertStringContainsString('SURAT KETERANGAN DOMISILI', $html);

        // Alamat dan Lama Tinggal
        $this->assertStringContainsString('Jl. Dipatiukur Gg. Sekeloa No. 12 RT 05 RW 03', $html);
        $this->assertStringContainsString('7 Tahun', $html); // field lama_tinggal

        // Keterangan domisili sah
        $this->assertStringContainsString('bertempat tinggal dan berdomisili sah di lingkungan RT', $html);
    }

    /**
     * Uji TAHAP 3 & TAHAP 4: Wording Stempel Digital & Pencegahan Pemotongan Layout
     * - Stempel "TERVERIFIKASI SECARA ELEKTRONIK"
     * - Mikro-teks tanpa tanda tangan basah
     * - CSS page-break-inside: avoid
     */
    public function test_verification_wording_and_layout_safeguards(): void
    {
        $surat = $this->createApprovedSurat('SKD', [
            'alamat_domisili' => 'Jl. Sekeloa No. 10',
            'lama_tinggal' => '3 Tahun',
            'keperluan' => 'Uji Layout',
        ]);

        $html = $this->renderPdfHtml($surat);

        // Stempel baru
        $this->assertStringContainsString('TERVERIFIKASI SECARA ELEKTRONIK', $html);
        $this->assertStringNotContainsString('TERVERIFIKASI SISTEM', $html);

        // Mikro-teks
        $this->assertStringContainsString('Dokumen ini telah disetujui secara elektronik dan sah tanpa tanda tangan basah.', $html);

        // Footer verifikasi tetap aman
        $this->assertStringContainsString('DOKUMEN ELEKTRONIK RESMI', $html);
        $this->assertStringContainsString('A1B2C3D4E5F67890', $html);

        // CSS safeguard
        $this->assertStringContainsString('page-break-inside: avoid', $html);
    }
}
