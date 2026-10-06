<?php

namespace Tests\Feature;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\Rt;
use App\Models\UmkmListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UmkmControllerTest extends TestCase
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

        // Pastikan warga1 punya no_hp aktif
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
    }

    // =========================================================================
    // 1. CREATE SCENARIOS (Items 1 - 7)
    // =========================================================================

    public function test_01_authenticated_user_dengan_no_hp_dapat_membuat_listing(): void
    {
        $response = $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'kategori' => 'barang',
            'nama' => 'Kue Brownies Coklat',
            'deskripsi' => 'Brownies panggang lezat',
            'harga' => 45000,
        ]);

        $response->assertRedirect(route('umkm.index'));
        $this->assertDatabaseHas('umkm_listing', [
            'user_id' => $this->warga1->id,
            'nama' => 'Kue Brownies Coklat',
            'status' => 'MENUNGGU',
        ]);
    }

    public function test_02_user_tanpa_no_hp_tidak_dapat_membuat_listing(): void
    {
        $this->warga1->update(['no_hp' => null]);

        $response = $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'kategori' => 'barang',
            'nama' => 'Kue Brownies Coklat',
            'deskripsi' => 'Brownies panggang lezat',
            'harga' => 45000,
        ]);

        $response->assertSessionHasErrors('no_hp');
        $this->assertDatabaseCount('umkm_listing', 0);
    }

    public function test_03_listing_baru_selalu_berstatus_menunggu(): void
    {
        $this->actingAs($this->ketuaRt)->post(route('umkm.store'), [
            'kategori' => 'jasa',
            'nama' => 'Jasa Desain Spanduk',
            'deskripsi' => 'Desain cepat untuk warga',
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->where('nama', 'Jasa Desain Spanduk')->first();
        $this->assertNotNull($listing);
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->status);
    }

    public function test_04_user_id_selalu_berasal_dari_auth_user(): void
    {
        $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'user_id' => $this->warga2->id, // Manipulasi request
            'kategori' => 'barang',
            'nama' => 'Madu Murni',
            'deskripsi' => 'Madu hutan',
            'harga' => 80000,
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->where('nama', 'Madu Murni')->first();
        $this->assertEquals($this->warga1->id, $listing->user_id);
    }

    public function test_05_rt_id_selalu_berasal_dari_tenant_user(): void
    {
        $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'rt_id' => $this->rt06->id, // Manipulasi request
            'kategori' => 'barang',
            'nama' => 'Kacang Bawang',
            'deskripsi' => 'Gurih dan renyah',
            'harga' => 20000,
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->where('nama', 'Kacang Bawang')->first();
        $this->assertEquals($this->rt05->id, $listing->rt_id);
    }

    public function test_06_request_tidak_dapat_memaksa_status_disetujui(): void
    {
        $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'status' => 'DISETUJUI', // Coba bypass status
            'kategori' => 'barang',
            'nama' => 'Kue Kering Nastar',
            'deskripsi' => 'Nastar keju butter',
            'harga' => 75000,
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->where('nama', 'Kue Kering Nastar')->first();
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->status);
    }

    public function test_07_request_tidak_dapat_memaksa_rt_id_tenant_lain(): void
    {
        $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'rt_id' => 9999,
            'kategori' => 'jasa',
            'nama' => 'Jasa Cuci Karpet',
            'deskripsi' => 'Bersih wangi',
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->where('nama', 'Jasa Cuci Karpet')->first();
        $this->assertEquals($this->rt05->id, $listing->rt_id);
    }

    // =========================================================================
    // 2. UPDATE SCENARIOS (Items 8 - 18)
    // =========================================================================

    public function test_08_owner_dapat_mengedit_listing_sendiri(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Nama Lama',
            'deskripsi' => 'Deskripsi lama',
            'harga' => 10000,
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Nama Baru',
            'deskripsi' => 'Deskripsi baru',
            'harga' => 12000,
        ]);

        $response->assertRedirect(route('umkm.index'));
        $this->assertEquals('Nama Baru', $listing->fresh()->nama);
    }

    public function test_09_user_lain_tidak_dapat_mengedit_listing(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Nama Awal',
            'deskripsi' => 'Deskripsi awal',
            'harga' => 10000,
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->warga2)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Nama Bajakan',
            'deskripsi' => 'Deskripsi bajakan',
            'harga' => 15000,
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Nama Awal', $listing->fresh()->nama);
    }

    public function test_10_listing_ditolak_setelah_revisi_kembali_menunggu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Barang Ditolak',
            'deskripsi' => 'Keterangan lama',
            'harga' => 20000,
            'status' => 'DITOLAK',
            'alasan_tolak' => 'Foto buram dan deskripsi kurang jelas',
            'reviewed_by' => $this->sekretaris->id,
        ]);

        $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Barang Diperbaiki',
            'deskripsi' => 'Keterangan lengkap',
            'harga' => 20000,
        ]);

        $fresh = $listing->fresh();
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $fresh->status);
    }

    public function test_11_alasan_tolak_direset_saat_resubmit(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Barang Ditolak',
            'deskripsi' => 'Keterangan',
            'harga' => 20000,
            'status' => 'DITOLAK',
            'alasan_tolak' => 'Harga tidak wajar',
            'reviewed_by' => $this->sekretaris->id,
        ]);

        $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Barang Ditolak',
            'deskripsi' => 'Keterangan diperbarui',
            'harga' => 15000,
        ]);

        $this->assertNull($listing->fresh()->alasan_tolak);
    }

    public function test_12_reviewed_by_direset_saat_resubmit(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Barang Ditolak',
            'deskripsi' => 'Keterangan',
            'status' => 'DITOLAK',
            'reviewed_by' => $this->sekretaris->id,
        ]);

        $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Barang Resubmit',
            'deskripsi' => 'Keterangan',
        ]);

        $this->assertNull($listing->fresh()->reviewed_by);
    }

    public function test_13_listing_disetujui_setelah_perubahan_nama_kembali_menunggu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Nama Tayang',
            'deskripsi' => 'Deskripsi',
            'harga' => 10000,
            'status' => 'DISETUJUI',
            'reviewed_by' => $this->ketuaRt->id,
        ]);

        $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Nama Diubah Substantif',
            'deskripsi' => 'Deskripsi',
            'harga' => 10000,
        ]);

        $fresh = $listing->fresh();
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $fresh->status);
        $this->assertNull($fresh->reviewed_by);
    }

    public function test_14_perubahan_deskripsi_mengembalikan_status_menunggu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'jasa',
            'nama' => 'Servis Pompa',
            'deskripsi' => 'Deskripsi lama',
            'status' => 'DISETUJUI',
            'reviewed_by' => $this->ketuaRt->id,
        ]);

        $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'jasa',
            'nama' => 'Servis Pompa',
            'deskripsi' => 'Deskripsi baru yang diubah',
        ]);

        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
    }

    public function test_15_perubahan_harga_mengembalikan_status_menunggu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kue Lapis',
            'deskripsi' => 'Lapis legit',
            'harga' => 50000,
            'status' => 'DISETUJUI',
        ]);

        $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Kue Lapis',
            'deskripsi' => 'Lapis legit',
            'harga' => 60000,
        ]);

        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
    }

    public function test_16_perubahan_kategori_mengembalikan_status_menunggu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Bimbel Matematika',
            'deskripsi' => 'Buku materi',
            'harga' => 30000,
            'status' => 'DISETUJUI',
        ]);

        $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'jasa', // Berubah dari barang ke jasa
            'nama' => 'Bimbel Matematika',
            'deskripsi' => 'Buku materi',
            'harga' => null,
        ]);

        $fresh = $listing->fresh();
        $this->assertEquals('jasa', $fresh->kategori);
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $fresh->status);
    }

    public function test_17_perubahan_foto_mengembalikan_status_menunggu(): void
    {
        $oldFile = UploadedFile::fake()->image('old.jpg');
        $oldPath = $oldFile->store('umkm/5/' . $this->warga1->id, 'public');

        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Keripik',
            'deskripsi' => 'Enak',
            'harga' => 10000,
            'foto_url' => $oldPath,
            'status' => 'DISETUJUI',
        ]);

        $newFile = UploadedFile::fake()->image('new.jpg');
        $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Keripik',
            'deskripsi' => 'Enak',
            'harga' => 10000,
            'foto' => $newFile,
        ]);

        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
    }

    public function test_18_perubahan_template_wa_mengembalikan_status_menunggu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Minyak Goreng',
            'deskripsi' => 'Minyak kelapa',
            'harga' => 25000,
            'template_pesan_wa' => 'Pesan template awal',
            'status' => 'DISETUJUI',
        ]);

        $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Minyak Goreng',
            'deskripsi' => 'Minyak kelapa',
            'harga' => 25000,
            'template_pesan_wa' => 'Template pesan diubah baru',
        ]);

        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
    }

    // =========================================================================
    // 3. DELETE SCENARIOS (Items 19 - 21)
    // =========================================================================

    public function test_19_owner_dapat_menghapus_listing_sendiri(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Mau Dihapus',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->warga1)->delete(route('umkm.destroy', $listing->id));
        $response->assertRedirect(route('umkm.index'));
        $this->assertDatabaseMissing('umkm_listing', ['id' => $listing->id]);
    }

    public function test_20_user_lain_tidak_dapat_menghapus_listing(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Milik Warga 1',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->warga2)->delete(route('umkm.destroy', $listing->id));
        $response->assertStatus(403);
        $this->assertDatabaseHas('umkm_listing', ['id' => $listing->id]);
    }

    public function test_21_foto_terkait_ikut_dibersihkan_saat_listing_dihapus(): void
    {
        $file = UploadedFile::fake()->image('produk_delete.jpg');
        $path = $file->store('umkm/5/' . $this->warga1->id, 'public');
        Storage::disk('public')->assertExists($path);

        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Dengan Foto',
            'deskripsi' => 'Deskripsi',
            'foto_url' => $path,
            'status' => 'MENUNGGU',
        ]);

        $this->actingAs($this->warga1)->delete(route('umkm.destroy', $listing->id));
        $this->assertDatabaseMissing('umkm_listing', ['id' => $listing->id]);
        Storage::disk('public')->assertMissing($path);
    }

    // =========================================================================
    // 4. APPROVAL SCENARIOS (Items 22 - 31)
    // =========================================================================

    public function test_22_sekretaris_dapat_approve_listing_menunggu_dalam_scope_rt(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kerajinan Tangan',
            'deskripsi' => 'Anyaman bambu',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->sekretaris)->post(route('umkm.approve', $listing->id));
        $response->assertRedirect(route('umkm.index'));

        $fresh = $listing->fresh();
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $fresh->status);
        $this->assertEquals($this->sekretaris->id, $fresh->reviewed_by);
    }

    public function test_23_ketua_rt_dapat_approve(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kerajinan',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->actingAs($this->ketuaRt)->post(route('umkm.approve', $listing->id));
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $listing->fresh()->status);
    }

    public function test_24_wakil_rt_dapat_approve(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kerajinan',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->actingAs($this->wakilRt)->post(route('umkm.approve', $listing->id));
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $listing->fresh()->status);
    }

    public function test_25_super_admin_dapat_approve_sesuai_scope_global(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kerajinan',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->actingAs($this->superAdmin)->post(route('umkm.approve', $listing->id));
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $listing->fresh()->status);
    }

    public function test_26_warga_tidak_dapat_approve(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kerajinan',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->warga2)->post(route('umkm.approve', $listing->id));
        $response->assertStatus(403);
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
    }

    public function test_27_bendahara_tidak_dapat_approve(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kerajinan',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->bendahara)->post(route('umkm.approve', $listing->id));
        $response->assertStatus(403);
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
    }

    public function test_28_ketua_rw_tidak_dapat_approve(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kerajinan',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->ketuaRw)->post(route('umkm.approve', $listing->id));
        $response->assertStatus(403);
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
    }

    public function test_29_reviewer_lintas_rt_tidak_dapat_approve(): void
    {
        $listingRt06 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt06->id,
            'kategori' => 'barang',
            'nama' => 'Produk RT 06',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->sekretaris)->post(route('umkm.approve', $listingRt06->id));
        $response->assertStatus(403);
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listingRt06->fresh()->status);
    }

    public function test_30_listing_disetujui_tidak_dapat_diapprove_ulang(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Sudah Tayang',
            'deskripsi' => 'Deskripsi',
            'status' => 'DISETUJUI',
        ]);

        $response = $this->actingAs($this->sekretaris)->post(route('umkm.approve', $listing->id));
        $response->assertStatus(422);
    }

    public function test_31_listing_ditolak_tidak_dapat_diapprove_tanpa_revisi(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Ditolak',
            'deskripsi' => 'Deskripsi',
            'status' => 'DITOLAK',
        ]);

        $response = $this->actingAs($this->sekretaris)->post(route('umkm.approve', $listing->id));
        $response->assertStatus(422);
    }

    // =========================================================================
    // 5. REJECTION SCENARIOS (Items 32 - 38)
    // =========================================================================

    public function test_32_reviewer_sah_dapat_reject_listing_menunggu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Ditolak Sah',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->sekretaris)->post(route('umkm.tolak', $listing->id), [
            'alasan_tolak' => 'Kategori produk tidak sesuai izin lingkungan RT',
        ]);

        $response->assertRedirect(route('umkm.index'));
        $this->assertEquals(UmkmListing::STATUS_DITOLAK, $listing->fresh()->status);
    }

    public function test_33_alasan_reject_wajib_diisi(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->sekretaris)->post(route('umkm.tolak', $listing->id), [
            'alasan_tolak' => '',
        ]);

        $response->assertSessionHasErrors('alasan_tolak');
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
    }

    public function test_34_alasan_reject_kurang_dari_5_karakter_ditolak(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->sekretaris)->post(route('umkm.tolak', $listing->id), [
            'alasan_tolak' => 'Gak', // Hanya 3 karakter
        ]);

        $response->assertSessionHasErrors('alasan_tolak');
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
    }

    public function test_35_alasan_reject_tersimpan(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $alasan = 'Mohon unggah foto produk asli kemasan, bukan comotan internet.';
        $this->actingAs($this->sekretaris)->post(route('umkm.tolak', $listing->id), [
            'alasan_tolak' => $alasan,
        ]);

        $this->assertEquals($alasan, $listing->fresh()->alasan_tolak);
    }

    public function test_36_reviewed_by_tersimpan_saat_reject(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Review',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->actingAs($this->ketuaRt)->post(route('umkm.tolak', $listing->id), [
            'alasan_tolak' => 'Barang dilarang dijual di perumahan warga.',
        ]);

        $this->assertEquals($this->ketuaRt->id, $listing->fresh()->reviewed_by);
    }

    public function test_37_listing_ditolak_tidak_dapat_direject_ulang(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Ditolak',
            'deskripsi' => 'Deskripsi',
            'status' => 'DITOLAK',
            'alasan_tolak' => 'Alasan sebelumnya',
        ]);

        $response = $this->actingAs($this->sekretaris)->post(route('umkm.tolak', $listing->id), [
            'alasan_tolak' => 'Mencoba tolak lagi',
        ]);

        $response->assertStatus(422);
    }

    public function test_38_reviewer_lintas_rt_tidak_dapat_reject(): void
    {
        $listingRt06 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt06->id,
            'kategori' => 'barang',
            'nama' => 'Produk RT 06',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($this->sekretaris)->post(route('umkm.tolak', $listingRt06->id), [
            'alasan_tolak' => 'Tolak lintas RT',
        ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // 6. AUDIT TRAIL SCENARIOS (Items 39 - 42)
    // =========================================================================

    public function test_39_approve_menghasilkan_audit_log_umkm_listing_approved(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Keripik Singkong',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->actingAs($this->sekretaris)->post(route('umkm.approve', $listing->id));

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::UMKM_LISTING_APPROVED,
            'target_type' => 'umkm_listings',
            'target_id' => $listing->id,
            'user_id' => $this->sekretaris->id,
            'rt_id' => $this->rt05->id,
        ]);
    }

    public function test_40_reject_menghasilkan_audit_log_umkm_listing_rejected_dengan_alasan(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Keripik Basi',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $alasan = 'Kemasan tidak higienis dan membahayakan kesehatan warga.';
        $this->actingAs($this->ketuaRt)->post(route('umkm.tolak', $listing->id), [
            'alasan_tolak' => $alasan,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::UMKM_LISTING_REJECTED,
            'target_type' => 'umkm_listings',
            'target_id' => $listing->id,
            'user_id' => $this->ketuaRt->id,
            'alasan' => $alasan,
        ]);
    }

    public function test_41_gagal_update_tidak_meninggalkan_audit_palsu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Tetap',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        // Aksi gagal karena hak akses ditolak
        $this->actingAs($this->warga2)->post(route('umkm.approve', $listing->id));

        $this->assertDatabaseMissing('audit_logs', [
            'target_id' => $listing->id,
        ]);
    }

    public function test_42_gagal_audit_tidak_meninggalkan_state_bisnis_setengah_jadi(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Rollback Test',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        // Simulasikan kegagalan transaksi di dalam DB::transaction
        try {
            DB::transaction(function () use ($listing) {
                $listing->update(['status' => UmkmListing::STATUS_DISETUJUI]);
                throw new \Exception('Simulasi kegagalan database pada audit logging');
            });
        } catch (\Throwable) {
            // expected
        }

        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
    }

    // =========================================================================
    // 7. FILE UPLOAD SCENARIOS (Items 43 - 47)
    // =========================================================================

    public function test_43_foto_valid_dapat_disimpan_ke_storage_public(): void
    {
        $foto = UploadedFile::fake()->image('produk_baru.jpg', 600, 600);

        $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'kategori' => 'barang',
            'nama' => 'Kue Nastar Spesial',
            'deskripsi' => 'Toples 500gr',
            'harga' => 85000,
            'foto' => $foto,
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->where('nama', 'Kue Nastar Spesial')->first();
        $this->assertNotNull($listing->foto_url);
        Storage::disk('public')->assertExists($listing->foto_url);
    }

    public function test_44_file_tidak_valid_ditolak(): void
    {
        $filePdf = UploadedFile::fake()->create('dokumen.pdf', 100);

        $response = $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'kategori' => 'barang',
            'nama' => 'Produk Invalid Foto',
            'deskripsi' => 'Deskripsi',
            'foto' => $filePdf,
        ]);

        $response->assertSessionHasErrors('foto');
        $this->assertDatabaseMissing('umkm_listing', ['nama' => 'Produk Invalid Foto']);
    }

    public function test_45_foto_lama_terhapus_ketika_foto_diganti(): void
    {
        $foto1 = UploadedFile::fake()->image('foto1.jpg');
        $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'kategori' => 'barang',
            'nama' => 'Produk Ganti Foto',
            'deskripsi' => 'Deskripsi',
            'harga' => 10000,
            'foto' => $foto1,
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->where('nama', 'Produk Ganti Foto')->first();
        $oldPath = $listing->foto_url;
        Storage::disk('public')->assertExists($oldPath);

        $foto2 = UploadedFile::fake()->image('foto2.jpg');
        $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Produk Ganti Foto',
            'deskripsi' => 'Deskripsi',
            'harga' => 10000,
            'foto' => $foto2,
        ]);

        $newPath = $listing->fresh()->foto_url;
        $this->assertNotEquals($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_46_foto_terhapus_ketika_listing_dihapus(): void
    {
        $foto = UploadedFile::fake()->image('foto_akan_hapus.jpg');
        $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'kategori' => 'barang',
            'nama' => 'Akan Dihapus',
            'deskripsi' => 'Deskripsi',
            'harga' => 10000,
            'foto' => $foto,
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->where('nama', 'Akan Dihapus')->first();
        $path = $listing->foto_url;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->warga1)->delete(route('umkm.destroy', $listing->id));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_47_database_tidak_menyimpan_absolute_filesystem_path(): void
    {
        $foto = UploadedFile::fake()->image('relative_test.png');
        $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'kategori' => 'barang',
            'nama' => 'Produk Relative Path',
            'deskripsi' => 'Deskripsi',
            'foto' => $foto,
        ]);

        $listing = UmkmListing::withoutGlobalScopes()->where('nama', 'Produk Relative Path')->first();
        $this->assertStringStartsNotWith('/', $listing->foto_url);
        $this->assertStringStartsNotWith('C:\\', $listing->foto_url);
        $this->assertStringStartsWith('umkm/', $listing->foto_url);
    }

    // =========================================================================
    // 8. NO HP SCENARIOS (Items 48 - 50)
    // =========================================================================

    public function test_48_user_dapat_mengubah_no_hp_miliknya_sendiri(): void
    {
        $response = $this->actingAs($this->warga1)->post(route('umkm.updateNoHp'), [
            'no_hp' => '087712345678',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('087712345678', $this->warga1->fresh()->no_hp);
    }

    public function test_49_user_tidak_dapat_mengubah_no_hp_user_lain_melalui_request(): void
    {
        $warga2OldNoHp = $this->warga2->no_hp;

        $this->actingAs($this->warga1)->post(route('umkm.updateNoHp'), [
            'user_id' => $this->warga2->id, // Manipulasi request
            'no_hp' => '089999999999',
        ]);

        // Nomor warga 1 berubah, nomor warga 2 tidak tersentuh
        $this->assertEquals('089999999999', $this->warga1->fresh()->no_hp);
        $this->assertEquals($warga2OldNoHp, $this->warga2->fresh()->no_hp);
    }

    public function test_50_endpoint_no_hp_tidak_dapat_dipakai_mengubah_ownership_listing(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Warga 1',
            'deskripsi' => 'Deskripsi',
            'status' => 'MENUNGGU',
        ]);

        $this->actingAs($this->warga2)->post(route('umkm.updateNoHp'), [
            'listing_id' => $listing->id,
            'user_id' => $this->warga2->id,
            'no_hp' => '085555555555',
        ]);

        // Ownership listing tetap milik warga 1
        $this->assertEquals($this->warga1->id, $listing->fresh()->user_id);
    }

    // =========================================================================
    // 9. LIFECYCLE MODERASI: NONAKTIF, REAKTIVASI KURASI RT, & TAKEDOWN PENGURUS (Items 51 - 62)
    // =========================================================================

    public function test_51_pemilik_dapat_menonaktifkan_listing_miliknya_sendiri(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kue Nastar Warga 1',
            'deskripsi' => 'Nastar keju gurih',
            'harga' => 60000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga1)->post(route('umkm.nonaktifkan', $listing->id));

        $response->assertRedirect(route('umkm.index'));
        $this->assertEquals(UmkmListing::STATUS_NONAKTIF, $listing->fresh()->status);
    }

    public function test_52_non_pemilik_dan_warga_lain_dilarang_menonaktifkan_listing_orang_lain(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kue Nastar Warga 1',
            'deskripsi' => 'Nastar keju gurih',
            'harga' => 60000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga2)->post(route('umkm.nonaktifkan', $listing->id));

        $response->assertStatus(403);
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $listing->fresh()->status);
    }

    public function test_53_pemilik_dapat_mengaktifkan_kembali_listing_nonaktif_dan_wajib_masuk_kurasi_menunggu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kue Nastar Warga 1',
            'deskripsi' => 'Nastar keju gurih',
            'harga' => 60000,
            'status' => UmkmListing::STATUS_NONAKTIF,
            'reviewed_by' => $this->ketuaRt->id,
        ]);

        $response = $this->actingAs($this->warga1)->post(route('umkm.aktifkan', $listing->id));

        $response->assertRedirect(route('umkm.index'));
        // Wajib kembali ke MENUNGGU, tidak boleh langsung DISETUJUI
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->fresh()->status);
        $this->assertNull($listing->fresh()->reviewed_by);
    }

    public function test_54_listing_ditakedown_dilarang_langsung_diaktifkan_kembali_tanpa_revisi(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Kena Takedown',
            'deskripsi' => 'Deskripsi lama',
            'harga' => 50000,
            'status' => UmkmListing::STATUS_DITAKEDOWN,
            'takedown_by' => $this->ketuaRt->id,
            'alasan_takedown' => 'Produk perlu dikoreksi izin edarnya.',
            'takedown_at' => now(),
        ]);

        $response = $this->actingAs($this->warga1)->post(route('umkm.aktifkan', $listing->id));

        $response->assertStatus(422);
        $this->assertEquals(UmkmListing::STATUS_DITAKEDOWN, $listing->fresh()->status);
    }

    public function test_55_pemilik_dapat_merevisi_listing_ditakedown_dan_status_kembali_menunggu_serta_membersihkan_data_takedown(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Kena Takedown',
            'deskripsi' => 'Deskripsi lama',
            'harga' => 50000,
            'status' => UmkmListing::STATUS_DITAKEDOWN,
            'takedown_by' => $this->ketuaRt->id,
            'alasan_takedown' => 'Produk perlu dikoreksi izin edarnya.',
            'takedown_at' => now(),
        ]);

        $response = $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Produk Kena Takedown (Sudah Direvisi)',
            'deskripsi' => 'Deskripsi baru yang sudah memenuhi ketentuan lingkungan RT.',
            'harga' => 50000,
        ]);

        $response->assertRedirect(route('umkm.index'));
        $fresh = $listing->fresh();
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $fresh->status);
        $this->assertEquals('Produk Kena Takedown (Sudah Direvisi)', $fresh->nama);
        $this->assertNull($fresh->takedown_by);
        $this->assertNull($fresh->alasan_takedown);
        $this->assertNull($fresh->takedown_at);
    }

    public function test_56_ketua_rt_wakil_rt_dan_sekretaris_dapat_takedown_listing_warga_di_rt_nya_dengan_alasan_dan_audit_log(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Bermasalah',
            'deskripsi' => 'Deskripsi',
            'harga' => 15000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->ketuaRt)->post(route('umkm.takedown', $listing->id), [
            'alasan_takedown' => 'Dikeluhkan oleh warga sekitar karena menimbulkan kebisingan.',
        ]);

        $response->assertRedirect(route('umkm.index'));
        $fresh = $listing->fresh();
        $this->assertEquals(UmkmListing::STATUS_DITAKEDOWN, $fresh->status);
        $this->assertEquals($this->ketuaRt->id, $fresh->takedown_by);
        $this->assertEquals('Dikeluhkan oleh warga sekitar karena menimbulkan kebisingan.', $fresh->alasan_takedown);
        $this->assertNotNull($fresh->takedown_at);

        // Pastikan tercatat di audit_logs
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::UMKM_LISTING_TAKEDOWN,
            'target_type' => 'umkm_listing',
            'target_id' => $listing->id,
            'user_id' => $this->ketuaRt->id,
            'alasan' => 'Dikeluhkan oleh warga sekitar karena menimbulkan kebisingan.',
        ]);
    }

    public function test_57_ketua_rt_dilarang_takedown_listing_di_luar_rt_wilayahnya(): void
    {
        $wargaRt06 = User::create([
            'rt_id' => $this->rt06->id,
            'kode_warga' => 'WRG-RT06-TKD',
            'nik' => '3273021005060099',
            'nama' => 'Warga RT 06',
            'email' => 'warga.rt06.tkd@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'status' => 'aktif',
            'no_hp' => '087788991122',
        ]);
        $listingRt06 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $wargaRt06->id,
            'rt_id' => $this->rt06->id,
            'kategori' => 'barang',
            'nama' => 'Produk RT 06',
            'deskripsi' => 'Deskripsi RT 06',
            'harga' => 25000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Ketua RT 05 mencoba takedown listing RT 06 -> 403 Forbidden
        $response = $this->actingAs($this->ketuaRt)->post(route('umkm.takedown', $listingRt06->id), [
            'alasan_takedown' => 'Mencoba intervensi RT tetangga.',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $listingRt06->fresh()->status);
    }

    public function test_58_ketua_rw_dapat_takedown_lintas_rt_dalam_rw_binaannya(): void
    {
        $wargaRt06 = User::create([
            'rt_id' => $this->rt06->id,
            'kode_warga' => 'WRG-RT06-RW',
            'nik' => '3273021005060088',
            'nama' => 'Warga RT 06 RW',
            'email' => 'warga.rt06.rw@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'status' => 'aktif',
            'no_hp' => '087788991133',
        ]);
        $listingRt06 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $wargaRt06->id,
            'rt_id' => $this->rt06->id,
            'kategori' => 'barang',
            'nama' => 'Produk RT 06 untuk RW Takedown',
            'deskripsi' => 'Deskripsi RT 06',
            'harga' => 25000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Ketua RW 03 takedown listing RT 06 (masih di bawah RW 03) -> Sukses
        $response = $this->actingAs($this->ketuaRw)->post(route('umkm.takedown', $listingRt06->id), [
            'alasan_takedown' => 'Dikeluhkan warga lintas RT karena mengganggu ketertiban umum.',
        ]);

        $response->assertRedirect(route('umkm.index'));
        $this->assertEquals(UmkmListing::STATUS_DITAKEDOWN, $listingRt06->fresh()->status);
        $this->assertEquals($this->ketuaRw->id, $listingRt06->fresh()->takedown_by);

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::UMKM_LISTING_TAKEDOWN,
            'target_type' => 'umkm_listing',
            'target_id' => $listingRt06->id,
            'user_id' => $this->ketuaRw->id,
        ]);
    }

    public function test_59_ketua_rw_dilarang_takedown_listing_di_rw_lain(): void
    {
        $klien = \App\Models\Klien::first();
        $rwLain = \App\Models\Rw::create([
            'klien_id' => $klien->id,
            'kode_rw' => '32.73.02.1005-RW99',
            'nomor_rw' => 99,
            'nama' => 'RW 99 Sekeloa',
        ]);
        $rtLuarRw = Rt::create([
            'rw_id' => $rwLain->id,
            'kode_rt' => '32.73.02.1005-RW99-RT01',
            'nomor_rt' => 1,
            'nama' => 'RT 01 RW 99',
        ]);
        $wargaRwLain = User::create([
            'rt_id' => $rtLuarRw->id,
            'kode_warga' => 'WRG-RW99-001',
            'nik' => '3273021005990001',
            'nama' => 'Warga RW 99',
            'email' => 'warga.rw99@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'status' => 'aktif',
            'no_hp' => '087799990001',
        ]);
        $listingRwLain = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $wargaRwLain->id,
            'rt_id' => $rtLuarRw->id,
            'kategori' => 'barang',
            'nama' => 'Produk RW 99',
            'deskripsi' => 'Deskripsi',
            'harga' => 30000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Ketua RW 03 mencoba takedown listing di RW 99 -> 403 Forbidden
        $response = $this->actingAs($this->ketuaRw)->post(route('umkm.takedown', $listingRwLain->id), [
            'alasan_takedown' => 'Intervensi RW lain.',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $listingRwLain->fresh()->status);
    }

    public function test_60_bendahara_dilarang_melakukan_takedown_umkm(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Warga 1',
            'deskripsi' => 'Deskripsi',
            'harga' => 10000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Bendahara mencoba takedown -> 403 Forbidden
        $response = $this->actingAs($this->bendahara)->post(route('umkm.takedown', $listing->id), [
            'alasan_takedown' => 'Bendahara mencoba takedown.',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $listing->fresh()->status);
    }

    public function test_61_takedown_tanpa_alasan_atau_alasan_kurang_dari_5_karakter_ditolak_validasi(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Warga 1',
            'deskripsi' => 'Deskripsi',
            'harga' => 10000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->ketuaRt)->post(route('umkm.takedown', $listing->id), [
            'alasan_takedown' => '1234', // Kurang dari 5 karakter
        ]);

        $response->assertSessionHasErrors('alasan_takedown');
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $listing->fresh()->status);
    }

    public function test_62_warga_biasa_dilarang_melakukan_takedown(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Warga 1',
            'deskripsi' => 'Deskripsi',
            'harga' => 10000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga2)->post(route('umkm.takedown', $listing->id), [
            'alasan_takedown' => 'Warga biasa mau takedown tetangga.',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(UmkmListing::STATUS_DISETUJUI, $listing->fresh()->status);
    }
}
