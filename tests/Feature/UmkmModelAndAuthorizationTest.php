<?php

namespace Tests\Feature;

use App\Http\Requests\StoreUmkmListingRequest;
use App\Http\Requests\UpdateUmkmListingRequest;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\UmkmListing;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ScopeAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UmkmModelAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $ketuaRw;
    protected User $ketuaRt;
    protected User $wakilRt;
    protected User $sekretaris;
    protected User $bendahara;
    protected User $warga1;
    protected User $warga2;
    protected Rt $rt05;
    protected Rt $rt06;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        // Bersihkan data UMKM seeder agar tes terisolasi dan deterministik
        UmkmListing::withoutGlobalScopes()->delete();

        $this->superAdmin = User::where('is_super_admin', true)->first();
        $this->ketuaRw = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rw'))->first();
        $this->ketuaRt = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();
        $this->wakilRt = User::whereHas('roles', fn ($q) => $q->where('role', 'wakil_rt'))->first();
        $this->sekretaris = User::whereHas('roles', fn ($q) => $q->where('role', 'sekretaris'))->first();
        $this->bendahara = User::whereHas('roles', fn ($q) => $q->where('role', 'bendahara'))->first();

        $this->rt05 = $this->ketuaRt->rt;

        // Ambil dua warga aktif di RT 05 yang tidak merangkap pengurus
        $wargaList = User::where('rt_id', $this->rt05->id)
            ->whereHas('roles', fn ($q) => $q->where('role', 'warga'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('role', ['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'ketua_rw']))
            ->take(2)
            ->get();

        $this->warga1 = $wargaList[0];
        $this->warga2 = $wargaList[1];

        // Buat RT 06 sebagai boundary testing isolasi tenant
        $this->rt06 = Rt::create([
            'rw_id' => $this->rt05->rw_id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);
    }

    // =========================================================================
    // 1. PENGUJIAN MODEL & RELASI (Items 1 - 7)
    // =========================================================================

    /**
     * Test 1: Relasi user() menghubungkan listing ke penjualnya.
     */
    public function test_01_relasi_user(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Madu Murni Hutan',
            'deskripsi' => 'Madu alami hasil lebah liar.',
            'harga' => 85000,
            'status' => 'DISETUJUI',
        ]);

        $this->assertInstanceOf(User::class, $listing->user);
        $this->assertEquals($this->warga1->id, $listing->user->id);
        $this->assertTrue($this->warga1->umkmListings->contains('id', $listing->id));
    }

    /**
     * Test 2: Relasi rt() menghubungkan listing ke RT wilayah.
     */
    public function test_02_relasi_rt(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'jasa',
            'nama' => 'Tukang Jahit Pakaian Bu Siti',
            'deskripsi' => 'Menerima jahit kebaya dan permak celana.',
            'status' => 'MENUNGGU',
        ]);

        $this->assertInstanceOf(Rt::class, $listing->rt);
        $this->assertEquals($this->rt05->id, $listing->rt->id);
        $this->assertTrue($this->rt05->umkmListings->contains('id', $listing->id));
    }

    /**
     * Test 3: Relasi reviewer() menghubungkan listing ke pengurus yang meninjau.
     */
    public function test_03_relasi_reviewer(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Keripik Singkong Renyah',
            'deskripsi' => 'Cemilan gurih pedas.',
            'harga' => 15000,
            'status' => 'DISETUJUI',
            'reviewed_by' => $this->sekretaris->id,
        ]);

        $this->assertInstanceOf(User::class, $listing->reviewer);
        $this->assertEquals($this->sekretaris->id, $listing->reviewer->id);
    }

    /**
     * Test 4: Scope disetujui() hanya mengembalikan listing dengan status DISETUJUI.
     */
    public function test_04_scope_disetujui(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Barang Disetujui',
            'deskripsi' => 'Deskripsi',
            'harga' => 10000,
            'status' => 'DISETUJUI',
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Barang Menunggu',
            'deskripsi' => 'Deskripsi',
            'harga' => 10000,
            'status' => 'MENUNGGU',
        ]);

        $disetujui = UmkmListing::withoutGlobalScopes()->disetujui()->get();
        $this->assertCount(1, $disetujui);
        $this->assertEquals('Barang Disetujui', $disetujui->first()->nama);
    }

    /**
     * Test 5: Scope menunggu() hanya mengembalikan listing dengan status MENUNGGU.
     */
    public function test_05_scope_menunggu(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Barang Disetujui',
            'deskripsi' => 'Deskripsi',
            'status' => 'DISETUJUI',
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'jasa',
            'nama' => 'Jasa Menunggu',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $menunggu = UmkmListing::withoutGlobalScopes()->menunggu()->get();
        $this->assertCount(1, $menunggu);
        $this->assertEquals('Jasa Menunggu', $menunggu->first()->nama);
    }

    /**
     * Test 6: Scope milikUser() menyaring listing berdasarkan user_id pemilik.
     */
    public function test_06_scope_milik_user(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Barang Warga 1',
            'deskripsi' => 'Deskripsi',
            'status' => 'DISETUJUI',
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Barang Warga 2',
            'deskripsi' => 'Deskripsi',
            'status' => 'DISETUJUI',
        ]);

        $milikWarga1 = UmkmListing::withoutGlobalScopes()->milikUser($this->warga1->id)->get();
        $this->assertCount(1, $milikWarga1);
        $this->assertEquals('Barang Warga 1', $milikWarga1->first()->nama);
    }

    /**
     * Test 7: Scope kategori() menyaring listing berdasarkan jenis kategori (jasa/barang).
     */
    public function test_07_scope_kategori(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Fisik',
            'deskripsi' => 'Deskripsi',
            'status' => 'DISETUJUI',
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'jasa',
            'nama' => 'Layanan Servis',
            'deskripsi' => 'Deskripsi',
            'status' => 'DISETUJUI',
        ]);

        $barang = UmkmListing::withoutGlobalScopes()->kategori('barang')->get();
        $jasa = UmkmListing::withoutGlobalScopes()->kategori('jasa')->get();

        $this->assertCount(1, $barang);
        $this->assertEquals('Produk Fisik', $barang->first()->nama);

        $this->assertCount(1, $jasa);
        $this->assertEquals('Layanan Servis', $jasa->first()->nama);
    }

    // =========================================================================
    // 2. PENGUJIAN FORMAT HARGA & HELPER (Items 8 - 9)
    // =========================================================================

    /**
     * Test 8: Barang dengan harga menghasilkan format Rupiah resmi.
     */
    public function test_08_barang_dengan_harga_menghasilkan_format_rupiah(): void
    {
        $listing = new UmkmListing([
            'kategori' => 'barang',
            'nama' => 'Kerupuk Ikan',
            'harga' => 15000,
        ]);

        $this->assertEquals('Rp 15.000', $listing->formatted_harga);
    }

    /**
     * Test 9: Jasa atau barang dengan harga NULL menghasilkan teks fallback negosiasi.
     */
    public function test_09_jasa_atau_harga_null_menghasilkan_fallback_negosiasi(): void
    {
        $jasa = new UmkmListing([
            'kategori' => 'jasa',
            'nama' => 'Reparasi Pompa Air',
            'harga' => null,
        ]);
        $this->assertEquals('Tanya Penjual / Negosiasi', $jasa->formatted_harga);

        $barangTanpaHarga = new UmkmListing([
            'kategori' => 'barang',
            'nama' => 'Kue Custom Acara',
            'harga' => null,
        ]);
        $this->assertEquals('Tanya Penjual / Negosiasi', $barangTanpaHarga->formatted_harga);
    }

    // =========================================================================
    // 3. PENGUJIAN WHATSAPP HELPER (Items 10 - 14)
    // =========================================================================

    /**
     * Test 10: Nomor handphone awalan 08xx dinormalisasi menjadi 628xx pada tautan WhatsApp.
     */
    public function test_10_nomor_08xx_dinormalisasi_menjadi_628xx(): void
    {
        $this->warga1->update(['no_hp' => '081234567890']);

        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Roti Bakar',
            'deskripsi' => 'Roti bakar aneka rasa',
            'harga' => 20000,
            'status' => 'DISETUJUI',
        ]);

        $link = $listing->whatsapp_link;
        $this->assertNotNull($link);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $link);
    }

    /**
     * Test 11: Nomor handphone format +628xx tetap normal dan valid.
     */
    public function test_11_nomor_plus_628xx_tetap_benar(): void
    {
        $this->warga1->update(['no_hp' => '+6281234567890']);

        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kue Lumpur',
            'deskripsi' => 'Kue tradisional',
            'harga' => 12000,
            'status' => 'DISETUJUI',
        ]);

        $link = $listing->whatsapp_link;
        $this->assertNotNull($link);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $link);
    }

    /**
     * Test 12: Default template pesan WhatsApp untuk kategori Barang memuat nama dan harga.
     */
    public function test_12_default_template_barang_benar(): void
    {
        $this->warga1->update([
            'nama' => 'Bu Ani',
            'no_hp' => '081298765432',
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Keripik Tempe',
            'deskripsi' => 'Cemilan gurih',
            'harga' => 15000,
            'template_pesan_wa' => null,
            'status' => 'DISETUJUI',
        ]);

        $link = $listing->whatsapp_link;
        $expectedText = 'Halo Bu Ani, saya tertarik membeli "Keripik Tempe" seharga Rp 15.000 yang saya lihat di platform Warga Digital. Apakah stok masih tersedia?';

        $this->assertStringContainsString(rawurlencode($expectedText), $link);
    }

    /**
     * Test 13: Default template pesan WhatsApp untuk kategori Jasa memuat pertanyaan tarif dan jadwal.
     */
    public function test_13_default_template_jasa_benar(): void
    {
        $this->warga1->update([
            'nama' => 'Pak Slamet',
            'no_hp' => '081298765432',
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'jasa',
            'nama' => 'Servis AC Ruangan',
            'deskripsi' => 'Perbaikan dan cuci AC',
            'harga' => null,
            'template_pesan_wa' => null,
            'status' => 'DISETUJUI',
        ]);

        $link = $listing->whatsapp_link;
        $expectedText = 'Halo Pak Slamet, saya tertarik dengan jasa "Servis AC Ruangan" yang terdaftar di Warga Digital. Boleh tanya info tarif dan jadwal layanannya?';

        $this->assertStringContainsString(rawurlencode($expectedText), $link);
    }

    /**
     * Test 14: Custom template digunakan jika disediakan oleh penjual.
     */
    public function test_14_custom_template_digunakan_jika_tersedia(): void
    {
        $this->warga1->update(['no_hp' => '081298765432']);

        $customTemplate = 'Halo Kak, saya mau order paket katering arisan 50 boks ya!';

        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Nasi Kotak Ayam Bakar',
            'deskripsi' => 'Paket komplit',
            'harga' => 25000,
            'template_pesan_wa' => $customTemplate,
            'status' => 'DISETUJUI',
        ]);

        $link = $listing->whatsapp_link;
        $this->assertStringContainsString(rawurlencode($customTemplate), $link);
    }

    // =========================================================================
    // 4. PENGUJIAN OTORISASI RBAC & TENANT ISOLATION (Items 15 - 24)
    // =========================================================================

    /**
     * Test 15: Warga dapat mengelola (canManageUmkm) listing miliknya sendiri.
     */
    public function test_15_warga_dapat_manage_listing_miliknya(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Warga 1',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->assertTrue(ScopeAuthorizer::canManageUmkm($this->warga1, $listing));
    }

    /**
     * Test 16: Warga TIDAK dapat mengelola listing milik warga lain.
     */
    public function test_16_warga_tidak_dapat_manage_listing_warga_lain(): void
    {
        $listingWarga2 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Warga 2',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->assertFalse(ScopeAuthorizer::canManageUmkm($this->warga1, $listingWarga2));
    }

    /**
     * Test 17: Sekretaris RT dapat mereview (canReviewUmkm) listing dalam scope RT-nya.
     */
    public function test_17_sekretaris_dapat_review(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->assertTrue(ScopeAuthorizer::canReviewUmkm($this->sekretaris, $listing));
    }

    /**
     * Test 18: Ketua RT dapat mereview listing dalam scope RT-nya.
     */
    public function test_18_ketua_rt_dapat_review(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->assertTrue(ScopeAuthorizer::canReviewUmkm($this->ketuaRt, $listing));
    }

    /**
     * Test 19: Wakil RT memiliki hak review identik dengan Ketua RT.
     */
    public function test_19_wakil_rt_dapat_review(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->assertTrue(ScopeAuthorizer::canReviewUmkm($this->wakilRt, $listing));
    }

    /**
     * Test 20: Super Admin memiliki akses review global.
     */
    public function test_20_super_admin_dapat_review_global(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->assertTrue(ScopeAuthorizer::canReviewUmkm($this->superAdmin, $listing));
    }

    /**
     * Test 21: Warga TIDAK dapat mereview listing UMKM.
     */
    public function test_21_warga_tidak_dapat_review(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->assertFalse(ScopeAuthorizer::canReviewUmkm($this->warga1, $listing));
    }

    /**
     * Test 22: Bendahara RT TIDAK memiliki hak review listing UMKM.
     */
    public function test_22_bendahara_tidak_dapat_review(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->assertFalse(ScopeAuthorizer::canReviewUmkm($this->bendahara, $listing));
    }

    /**
     * Test 23: Ketua RW TIDAK dapat mereview listing operasional RT.
     */
    public function test_23_ketua_rw_tidak_dapat_review(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->assertFalse(ScopeAuthorizer::canReviewUmkm($this->ketuaRw, $listing));
    }

    /**
     * Test 24: Reviewer TIDAK dapat mereview listing di luar RT wilayahnya (Tenant Isolation).
     */
    public function test_24_reviewer_tidak_dapat_review_listing_lintas_rt(): void
    {
        // Buat listing di RT 06
        $listingRt06 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt06->id,
            'kategori' => 'barang',
            'nama' => 'Produk RT 06',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        // Sekretaris dan Ketua RT 05 ditolak saat mencoba mereview listing RT 06
        $this->assertFalse(ScopeAuthorizer::canReviewUmkm($this->sekretaris, $listingRt06));
        $this->assertFalse(ScopeAuthorizer::canReviewUmkm($this->ketuaRt, $listingRt06));
    }

    // =========================================================================
    // 5. PENGUJIAN FORM REQUEST & VALIDASI BACKEND (Items 25 - 30)
    // =========================================================================

    /**
     * Test 25: Validasi menolak kategori yang tidak valid (bukan jasa/barang).
     */
    public function test_25_kategori_invalid_ditolak(): void
    {
        $rules = (new StoreUmkmListingRequest)->rules();

        $validator = Validator::make([
            'kategori' => 'elektronik', // Invalid enum
            'nama' => 'Laptop Bekas',
            'deskripsi' => 'Masih mulus',
            'harga' => 3000000,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('kategori', $validator->errors()->toArray());
    }

    /**
     * Test 26: Validasi menolak jika nama produk/jasa kosong.
     */
    public function test_26_nama_kosong_ditolak(): void
    {
        $rules = (new StoreUmkmListingRequest)->rules();

        $validator = Validator::make([
            'kategori' => 'barang',
            'nama' => '', // Kosong
            'deskripsi' => 'Deskripsi produk',
            'harga' => 10000,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('nama', $validator->errors()->toArray());
    }

    /**
     * Test 27: Validasi menolak jika deskripsi kosong.
     */
    public function test_27_deskripsi_kosong_ditolak(): void
    {
        $rules = (new StoreUmkmListingRequest)->rules();

        $validator = Validator::make([
            'kategori' => 'barang',
            'nama' => 'Kue Donat',
            'deskripsi' => '', // Kosong
            'harga' => 5000,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('deskripsi', $validator->errors()->toArray());
    }

    /**
     * Test 28: Validasi menolak nilai harga yang bernilai negatif.
     */
    public function test_28_harga_negatif_ditolak(): void
    {
        $rules = (new StoreUmkmListingRequest)->rules();

        $validator = Validator::make([
            'kategori' => 'barang',
            'nama' => 'Kue Donat',
            'deskripsi' => 'Donat kentang empuk',
            'harga' => -5000, // Negatif
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('harga', $validator->errors()->toArray());
    }

    /**
     * Test 29: Kategori Jasa dengan harga NULL adalah valid.
     */
    public function test_29_kategori_jasa_dengan_harga_null_valid(): void
    {
        $rules = (new StoreUmkmListingRequest)->rules();

        $validator = Validator::make([
            'kategori' => 'jasa',
            'nama' => 'Jasa Cuci Motor',
            'deskripsi' => 'Cuci motor bersih kilap dengan sampo salju',
            'harga' => null, // Valid untuk jasa
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    /**
     * Test 30: Custom WhatsApp template valid untuk kategori Jasa maupun Barang.
     */
    public function test_30_custom_whatsapp_template_valid_untuk_jasa_dan_barang(): void
    {
        $rules = (new StoreUmkmListingRequest)->rules();

        // Valid untuk Jasa
        $validatorJasa = Validator::make([
            'kategori' => 'jasa',
            'nama' => 'Servis Pompa Air',
            'deskripsi' => 'Panggilan 24 jam',
            'harga' => null,
            'template_pesan_wa' => 'Halo Pak, pompa air saya mati total. Kapan bisa dicek ke rumah?',
        ], $rules);

        $this->assertFalse($validatorJasa->fails());

        // Valid untuk Barang
        $validatorBarang = Validator::make([
            'kategori' => 'barang',
            'nama' => 'Madu Hutan',
            'deskripsi' => 'Madu murni 500ml',
            'harga' => 85000,
            'template_pesan_wa' => 'Halo, mau pesan madu hutan botol besar bisa kirim ke blok C?',
        ], $rules);

        $this->assertFalse($validatorBarang->fails());
    }
}
