<?php

namespace Tests\Feature;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\Rt;
use App\Models\UmkmListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UmkmCurationPresentationTest extends TestCase
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
    protected User $wargaRt06;
    protected Rt $rt05;
    protected Rt $rt06;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);
        Storage::fake('public');

        // Bersihkan data UMKM & audit log awal agar tes mandiri dan deterministik
        UmkmListing::withoutGlobalScopes()->delete();
        AuditLog::where('target_type', 'umkm_listings')->orWhere('target_type', 'umkm_listing')->delete();

        $this->superAdmin = User::where('is_super_admin', true)->first();
        $this->ketuaRw = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rw'))->first();
        $this->ketuaRt = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();
        $this->wakilRt = User::whereHas('roles', fn ($q) => $q->where('role', 'wakil_rt'))->first();
        $this->sekretaris = User::whereHas('roles', fn ($q) => $q->where('role', 'sekretaris'))->first();
        $this->bendahara = User::whereHas('roles', fn ($q) => $q->where('role', 'bendahara'))->first();

        $this->rt05 = $this->ketuaRt->rt;

        $wargaList = User::where('rt_id', $this->rt05->id)
            ->whereHas('roles', fn ($q) => $q->where('role', 'warga'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('role', ['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'ketua_rw']))
            ->take(2)
            ->get();

        $this->warga1 = $wargaList[0];
        $this->warga2 = $wargaList[1];

        $this->warga1->update(['no_hp' => '081234567891']);
        $this->warga2->update(['no_hp' => '081234567892']);

        // Buat RT 06 sebagai boundary tenant testing
        $this->rt06 = Rt::create([
            'rw_id' => $this->rt05->rw_id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);

        $this->wargaRt06 = User::create([
            'rt_id' => $this->rt06->id,
            'kode_warga' => 'WRG-RT06-001',
            'nik' => '3273021005060001',
            'nama' => 'Warga RT 06',
            'email' => 'warga.rt06@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'status' => 'aktif',
            'no_hp' => '085512345678',
        ]);
        \App\Models\UserRole::create([
            'user_id' => $this->wargaRt06->id,
            'role' => 'warga',
            'assigned_at' => now(),
        ]);
    }

    /**
     * 1. Reviewer Sekretaris dapat melihat tab dan antrean Meja Kurasi.
     */
    public function test_reviewer_sekretaris_dapat_melihat_tab_dan_antrean_meja_kurasi(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kue Nastar Keju Spesial',
            'deskripsi' => 'Toples 500gr gurih dan wangi.',
            'harga' => 85000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->sekretaris)->get(route('umkm.index', ['tab' => 'kurasi']));

        $response->assertStatus(200);
        $response->assertSee('Meja Kurasi');
        $response->assertSee('Meja Kurasi RT — Antrean Usaha Warga');
        $response->assertSee('1 Menunggu Review');
        $response->assertSee('Kue Nastar Keju Spesial');
        $response->assertSee('Setujui Usaha');
        $response->assertSee('Tolak');
    }

    /**
     * 2. Reviewer Ketua RT dapat melihat tab dan antrean Meja Kurasi.
     */
    public function test_reviewer_ketua_rt_dapat_melihat_tab_dan_antrean_meja_kurasi(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Jasa Cuci Sepatu Kilat',
            'deskripsi' => 'Deep clean sepatu kanvas dan kulit.',
            'harga' => 35000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->ketuaRt)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Meja Kurasi');
        $response->assertSee('Jasa Cuci Sepatu Kilat');
    }

    /**
     * 3. Reviewer Wakil RT dapat melihat tab dan antrean Meja Kurasi.
     */
    public function test_reviewer_wakil_rt_dapat_melihat_tab_dan_antrean_meja_kurasi(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Madu Murni Hutan',
            'deskripsi' => 'Madu lebah liar asli.',
            'harga' => 120000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->wakilRt)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Meja Kurasi');
        $response->assertSee('Madu Murni Hutan');
    }

    /**
     * 4. Super Admin dapat melihat tab dan antrean Meja Kurasi.
     */
    public function test_reviewer_super_admin_dapat_melihat_tab_dan_antrean_meja_kurasi(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Sambal Cumi Pedas',
            'deskripsi' => 'Sambal kemasan botol 150gr.',
            'harga' => 25000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Meja Kurasi');
        $response->assertSee('Sambal Cumi Pedas');
    }

    /**
     * 5. Non-reviewer Warga biasa TIDAK melihat tab dan konten Meja Kurasi di UI.
     */
    public function test_non_reviewer_warga_tidak_melihat_tab_meja_kurasi(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Keripik Kentang Balado',
            'deskripsi' => 'Renyah dan gurih.',
            'harga' => 18000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Meja Kurasi');
        $response->assertDontSee('Setujui Usaha');
        $response->assertDontSee('Tolak Pengajuan Usaha');
    }

    /**
     * 6. Non-reviewer Bendahara TIDAK melihat tab Meja Kurasi di UI.
     */
    public function test_non_reviewer_bendahara_tidak_melihat_tab_meja_kurasi(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kue Lapis Legit',
            'deskripsi' => 'Kue tradisional lembut.',
            'harga' => 150000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->bendahara)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Meja Kurasi');
        $response->assertDontSee('Setujui Usaha');
    }

    /**
     * 7. Non-reviewer Ketua RW TIDAK melihat tab Meja Kurasi di UI.
     */
    public function test_non_reviewer_ketua_rw_tidak_melihat_tab_meja_kurasi(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kue Lapis Legit RW',
            'deskripsi' => 'Kue tradisional lembut.',
            'harga' => 150000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->ketuaRw)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Meja Kurasi');
        $response->assertDontSee('Setujui Usaha');
    }

    /**
     * 8. Isolasi Tenant: Reviewer RT 05 hanya melihat antrean di RT 05, tidak melihat RT 06.
     */
    public function test_isolasi_tenant_reviewer_hanya_melihat_antrean_di_rt_sendiri(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Produk Khusus RT 05',
            'deskripsi' => 'Hanya ada di RT 05.',
            'harga' => 20000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt06->id,
            'rt_id' => $this->rt06->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Produk Wilayah RT 06',
            'deskripsi' => 'Hanya ada di RT 06.',
            'harga' => 30000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->sekretaris)->get(route('umkm.index', ['tab' => 'kurasi']));

        $response->assertStatus(200);
        $response->assertSee('Produk Khusus RT 05');
        $response->assertDontSee('Produk Wilayah RT 06');
    }

    /**
     * 9. Antrean Meja Kurasi HANYA menampilkan status MENUNGGU (tidak menampilkan DISETUJUI / DITOLAK).
     */
    public function test_antrean_meja_kurasi_hanya_menampilkan_status_menunggu(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Usaha Menunggu Verifikasi',
            'deskripsi' => 'Harus ada di meja kurasi.',
            'harga' => 10000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Usaha Sudah Disetujui',
            'deskripsi' => 'Sudah tayang di etalase.',
            'harga' => 20000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Usaha Sudah Ditolak',
            'deskripsi' => 'Sudah ditolak pengurus.',
            'harga' => 30000,
            'status' => UmkmListing::STATUS_DITOLAK,
            'alasan_tolak' => 'Tidak memenuhi syarat lingkungan.',
        ]);

        $response = $this->actingAs($this->sekretaris)->get(route('umkm.index', ['tab' => 'kurasi']));

        $response->assertStatus(200);
        $response->assertSee('1 Menunggu Review');
        $response->assertSee('Usaha Menunggu Verifikasi');
    }

    /**
     * 10. Detail listing kurasi dirender lengkap: nama, foto, penjual, harga, deskripsi, template WA.
     */
    public function test_detail_listing_kurasi_dirender_lengkap(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Bakso Goreng Renyah Pak RT',
            'deskripsi' => 'Bakso goreng isi ayam dan udang renyah.',
            'harga' => 25000,
            'template_pesan_wa' => 'Halo Pak, saya mau pesan bakso goreng 2 porsi ya!',
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->sekretaris)->get(route('umkm.index', ['tab' => 'kurasi']));

        $response->assertStatus(200);
        $response->assertSee('oleh');
        $response->assertSee($this->warga1->nama);
        $response->assertSee('Rp 25.000');
        $response->assertSee('Bakso goreng isi ayam dan udang renyah.');
        $response->assertSee('Halo Pak, saya mau pesan bakso goreng 2 porsi ya!');
    }

    /**
     * 11. Alur persetujuan: tombol Setujui -> request approve -> status DISETUJUI -> audit trail tercatat.
     */
    public function test_alur_persetujuan_listing_umkm_berhasil_dan_masuk_etalase_serta_tercatat_audit(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Bolu Gulung Pandan Keju',
            'deskripsi' => 'Bolu kukus pandan wangi lembut.',
            'harga' => 40000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->sekretaris)->post(route('umkm.approve', $listing->id));

        $response->assertRedirect(route('umkm.index'));
        $response->assertSessionHas('success');

        $listing->refresh();
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $listing->status);
        $this->assertEquals($this->sekretaris->id, $listing->reviewed_by);

        // Verifikasi audit log tercatat
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::UMKM_LISTING_APPROVED,
            'target_type' => 'umkm_listings',
            'target_id' => $listing->id,
            'user_id' => $this->sekretaris->id,
            'rt_id' => $this->rt05->id,
        ]);

        // Verifikasi listing kini muncul di etalase warga
        $resEtalase = $this->actingAs($this->warga2)->get(route('umkm.index', ['tab' => 'etalase']));
        $resEtalase->assertSee('Bolu Gulung Pandan Keju');
    }

    /**
     * 12. Alur penolakan: tombol Tolak -> modal dengan alasan valid -> status DITOLAK -> audit trail tercatat.
     */
    public function test_alur_penolakan_listing_umkm_dengan_alasan_valid_berhasil_dan_tercatat_audit(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kopi Bubuk Campuran Gelap',
            'deskripsi' => 'Kopi murni robusta.',
            'harga' => 15000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $alasan = 'Mohon lampirkan foto kemasan asli dan cantumkan izin edar P-IRT.';
        $response = $this->actingAs($this->ketuaRt)->post(route('umkm.tolak', $listing->id), [
            'alasan_tolak' => $alasan,
        ]);

        $response->assertRedirect(route('umkm.index'));
        $response->assertSessionHas('success');

        $listing->refresh();
        $this->assertEquals(UmkmListing::STATUS_DITOLAK, $listing->status);
        $this->assertEquals($this->ketuaRt->id, $listing->reviewed_by);
        $this->assertEquals($alasan, $listing->alasan_tolak);

        // Verifikasi audit log penolakan tercatat
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::UMKM_LISTING_REJECTED,
            'target_type' => 'umkm_listings',
            'target_id' => $listing->id,
            'user_id' => $this->ketuaRt->id,
            'alasan' => $alasan,
        ]);
    }

    /**
     * 13. Validasi penolakan: alasan kosong atau kurang dari 5 karakter ditolak oleh server.
     */
    public function test_penolakan_tanpa_alasan_atau_kurang_dari_5_karakter_gagal_validasi(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kue Cubit Coklat',
            'deskripsi' => 'Kue cubit setengah matang.',
            'harga' => 10000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        // Alasan kosong
        $resKosong = $this->actingAs($this->sekretaris)->post(route('umkm.tolak', $listing->id), [
            'alasan_tolak' => '',
        ]);
        $resKosong->assertSessionHasErrors('alasan_tolak');

        // Alasan < 5 karakter
        $resPendek = $this->actingAs($this->sekretaris)->post(route('umkm.tolak', $listing->id), [
            'alasan_tolak' => 'Gak',
        ]);
        $resPendek->assertSessionHasErrors('alasan_tolak');

        $listing->refresh();
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->status);
    }

    /**
     * 14. Alasan penolakan pengurus tampil secara transparan bagi pemilik di tab Usaha Saya.
     */
    public function test_alasan_penolakan_kurasi_dapat_dilihat_pemilik_di_usaha_saya(): void
    {
        $alasan = 'Foto produk buram dan deskripsi belum menjelaskan berat bersih kemasan.';
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kerupuk Kulit Sapi Renyah',
            'deskripsi' => 'Kerupuk kulit sapi asli.',
            'harga' => 20000,
            'status' => UmkmListing::STATUS_DITOLAK,
            'alasan_tolak' => $alasan,
            'reviewed_by' => $this->sekretaris->id,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index', ['tab' => 'usaha-saya']));

        $response->assertStatus(200);
        $response->assertSee('Ditolak');
        $response->assertSee('Alasan Penolakan Pengurus:');
        $response->assertSee($alasan);
        $response->assertSee('Edit &amp; Ajukan Ulang', false);
    }

    /**
     * 15. Stale state: listing yang sudah disetujui atau ditolak tidak dapat diproses ulang (422 Unprocessable).
     */
    public function test_listing_yang_sudah_disetujui_atau_ditolak_tidak_dapat_diproses_ulang(): void
    {
        $listingDisetujui = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Listing Sudah Disetujui',
            'deskripsi' => 'Deskripsi.',
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $listingDitolak = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Listing Sudah Ditolak',
            'deskripsi' => 'Deskripsi.',
            'status' => UmkmListing::STATUS_DITOLAK,
            'alasan_tolak' => 'Alasan lama.',
        ]);

        // Coba approve listing yang sudah disetujui
        $resApprove = $this->actingAs($this->sekretaris)->post(route('umkm.approve', $listingDisetujui->id));
        $resApprove->assertStatus(422);

        // Coba tolak listing yang sudah ditolak
        $resTolak = $this->actingAs($this->sekretaris)->post(route('umkm.tolak', $listingDitolak->id), [
            'alasan_tolak' => 'Mencoba menolak ulang listing yang sudah ditolak',
        ]);
        $resTolak->assertStatus(422);
    }

    /**
     * 16. Isolasi Tenant Server: Reviewer RT 05 TIDAK DAPAT approve atau reject listing RT 06 (403 Forbidden).
     */
    public function test_reviewer_tidak_dapat_approve_atau_reject_listing_lintas_rt(): void
    {
        $listingRt06 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt06->id,
            'rt_id' => $this->rt06->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Listing Asli RT 06',
            'deskripsi' => 'Deskripsi RT 06.',
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        // Sekretaris RT 05 mencoba approve listing RT 06
        $resApprove = $this->actingAs($this->sekretaris)->post(route('umkm.approve', $listingRt06->id));
        $resApprove->assertStatus(403);

        // Sekretaris RT 05 mencoba reject listing RT 06
        $resReject = $this->actingAs($this->sekretaris)->post(route('umkm.tolak', $listingRt06->id), [
            'alasan_tolak' => 'Penolakan lintas wilayah RT',
        ]);
        $resReject->assertStatus(403);

        $listingRt06->refresh();
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listingRt06->status);
    }

    /**
     * 17. Server Authorization: Warga, Bendahara, dan Ketua RW ditolak saat memanggil route approve/tolak (403 Forbidden).
     */
    public function test_non_reviewer_tidak_dapat_melakukan_approve_atau_reject_secara_langsung(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Listing Uji Otorisasi',
            'deskripsi' => 'Deskripsi listing.',
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        // Warga biasa mencoba approve -> 403
        $this->actingAs($this->warga2)->post(route('umkm.approve', $listing->id))->assertStatus(403);
        // Warga biasa mencoba tolak -> 403
        $this->actingAs($this->warga2)->post(route('umkm.tolak', $listing->id), ['alasan_tolak' => 'Tolak warga'])->assertStatus(403);

        // Bendahara mencoba approve -> 403
        $this->actingAs($this->bendahara)->post(route('umkm.approve', $listing->id))->assertStatus(403);
        // Bendahara mencoba tolak -> 403
        $this->actingAs($this->bendahara)->post(route('umkm.tolak', $listing->id), ['alasan_tolak' => 'Tolak bendahara'])->assertStatus(403);

        // Ketua RW mencoba approve -> 403
        $this->actingAs($this->ketuaRw)->post(route('umkm.approve', $listing->id))->assertStatus(403);
        // Ketua RW mencoba tolak -> 403
        $this->actingAs($this->ketuaRw)->post(route('umkm.tolak', $listing->id), ['alasan_tolak' => 'Tolak ketua rw'])->assertStatus(403);

        $listing->refresh();
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->status);
    }

    /**
     * 18. Empty state Meja Kurasi tampil informatif ketika semua pengajuan sudah ditinjau.
     */
    public function test_empty_state_meja_kurasi_ketika_semua_pengajuan_sudah_ditinjau(): void
    {
        // Tanpa listing berstatus MENUNGGU
        $response = $this->actingAs($this->sekretaris)->get(route('umkm.index', ['tab' => 'kurasi']));

        $response->assertStatus(200);
        $response->assertSee('Semua pengajuan sudah ditinjau');
        $response->assertSee('0 Menunggu Review');
    }
}
