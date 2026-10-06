<?php

namespace Tests\Feature;

use App\Constants\AuditAction;
use App\Models\GaleriAlbum;
use App\Models\GaleriFoto;
use App\Models\Klien;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Services\ScopeAuthorizer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GaleriModelAndAuthorizationTest extends TestCase
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
    protected Rw $rw03;
    protected Rt $foreignRt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->superAdmin = User::where('is_super_admin', true)->first();
        $this->ketuaRw = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rw'))->first();
        $this->ketuaRt = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();
        $this->wakilRt = User::whereHas('roles', fn ($q) => $q->where('role', 'wakil_rt'))->first();
        $this->sekretaris = User::whereHas('roles', fn ($q) => $q->where('role', 'sekretaris'))->first();
        $this->bendahara = User::whereHas('roles', fn ($q) => $q->where('role', 'bendahara'))->first();

        $this->rt05 = $this->ketuaRt->rt;
        $this->rw03 = Rw::first();

        $wargaList = User::where('rt_id', $this->rt05->id)
            ->whereHas('roles', fn ($q) => $q->where('role', 'warga'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('role', ['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'ketua_rw']))
            ->take(2)
            ->get();

        $this->warga1 = $wargaList[0];
        $this->warga2 = $wargaList[1];

        // Buat RT 06 dalam RW 03 yang sama
        $this->rt06 = Rt::create([
            'rw_id' => $this->rt05->rw_id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);

        // Buat RW & RT lain (wilayah asing) untuk boundary testing
        $foreignRw = Rw::create([
            'klien_id' => $this->rw03->klien_id,
            'kode_rw' => '32.73.02.1005-RW99',
            'nomor_rw' => 99,
            'nama' => 'RW 99 Sekeloa Asing',
        ]);

        $this->foreignRt = Rt::create([
            'rw_id' => $foreignRw->id,
            'kode_rt' => '32.73.02.1005-RW99-RT01',
            'nomor_rt' => 1,
            'nama' => 'RT 01 RW 99 Asing',
            'format_nomor_surat' => '{nomor}/RT01-RW99/SK/{bulan_romawi}/{tahun}',
        ]);
    }

    // ==========================================
    // 1. MODEL TESTS (GaleriAlbum & GaleriFoto)
    // ==========================================

    public function test_galeri_album_dapat_menyimpan_dan_mengubah_kolom_deskripsi(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Kerja Bakti Saluran Air',
            'deskripsi' => 'Kerja bakti bersama warga membersihkan saluran air menjelang musim hujan.',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->sekretaris->id,
        ]);

        $this->assertDatabaseHas('galeri_album', [
            'id' => $album->id,
            'judul' => 'Kerja Bakti Saluran Air',
            'deskripsi' => 'Kerja bakti bersama warga membersihkan saluran air menjelang musim hujan.',
        ]);

        // Update deskripsi
        $album->update([
            'deskripsi' => 'Pembaruan: Kegiatan selesai dengan partisipasi 30 warga.',
        ]);

        $this->assertEquals('Pembaruan: Kegiatan selesai dengan partisipasi 30 warga.', $album->fresh()->deskripsi);
    }

    public function test_cover_foto_mengembalikan_foto_tertua(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Lomba 17 Agustus',
            'deskripsi' => 'Dokumentasi lomba tarik tambang dan balap karung.',
            'tanggal_kegiatan' => '2026-08-17',
            'created_by' => $this->sekretaris->id,
        ]);

        // Buat 3 foto dengan urutan created_at berbeda
        $foto1 = GaleriFoto::create([
            'album_id' => $album->id,
            'foto_url' => 'galeri/1/foto1.jpg',
            'uploaded_by' => $this->sekretaris->id,
            'created_at' => Carbon::parse('2026-08-17 10:00:00'),
        ]);

        $foto2 = GaleriFoto::create([
            'album_id' => $album->id,
            'foto_url' => 'galeri/1/foto2.jpg',
            'uploaded_by' => $this->sekretaris->id,
            'created_at' => Carbon::parse('2026-08-17 11:00:00'),
        ]);

        $foto3 = GaleriFoto::create([
            'album_id' => $album->id,
            'foto_url' => 'galeri/1/foto3.jpg',
            'uploaded_by' => $this->sekretaris->id,
            'created_at' => Carbon::parse('2026-08-17 12:00:00'),
        ]);

        $album = $album->fresh();

        $this->assertNotNull($album->coverFoto);
        $this->assertEquals($foto1->id, $album->coverFoto->id);
        $this->assertEquals('galeri/1/foto1.jpg', $album->coverFoto->foto_url);
    }

    public function test_album_tanpa_foto_menghasilkan_cover_foto_null(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Rapat Rencana Ronda',
            'deskripsi' => null,
            'tanggal_kegiatan' => '2026-10-05',
            'created_by' => $this->sekretaris->id,
        ]);

        $this->assertNull($album->coverFoto);
        $this->assertEquals(0, $album->fotos()->count());
    }

    public function test_eager_loading_cover_foto_dan_fotos_count_bebas_leakage(): void
    {
        $this->actingAs($this->sekretaris);

        // Album A
        $albumA = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album A',
            'tanggal_kegiatan' => '2026-09-01',
            'created_by' => $this->sekretaris->id,
        ]);
        $fotoA1 = GaleriFoto::create([
            'album_id' => $albumA->id,
            'foto_url' => 'galeri/a/cover_a.jpg',
            'uploaded_by' => $this->sekretaris->id,
            'created_at' => Carbon::parse('2026-09-01 08:00:00'),
        ]);
        GaleriFoto::create([
            'album_id' => $albumA->id,
            'foto_url' => 'galeri/a/foto_a2.jpg',
            'uploaded_by' => $this->sekretaris->id,
            'created_at' => Carbon::parse('2026-09-01 09:00:00'),
        ]);

        // Album B
        $albumB = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album B',
            'tanggal_kegiatan' => '2026-09-02',
            'created_by' => $this->sekretaris->id,
        ]);
        $fotoB1 = GaleriFoto::create([
            'album_id' => $albumB->id,
            'foto_url' => 'galeri/b/cover_b.jpg',
            'uploaded_by' => $this->sekretaris->id,
            'created_at' => Carbon::parse('2026-09-02 08:00:00'),
        ]);

        // Album C (Kosong)
        $albumC = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album C Kosong',
            'tanggal_kegiatan' => '2026-09-03',
            'created_by' => $this->sekretaris->id,
        ]);

        // Eager load seperti di controller
        $albums = GaleriAlbum::whereIn('id', [$albumA->id, $albumB->id, $albumC->id])
            ->with(['coverFoto', 'creator'])
            ->withCount('fotos')
            ->orderBy('id')
            ->get();

        $loadedA = $albums->firstWhere('id', $albumA->id);
        $loadedB = $albums->firstWhere('id', $albumB->id);
        $loadedC = $albums->firstWhere('id', $albumC->id);

        // Album A
        $this->assertEquals(2, $loadedA->fotos_count);
        $this->assertNotNull($loadedA->coverFoto);
        $this->assertEquals($fotoA1->id, $loadedA->coverFoto->id);
        $this->assertEquals('galeri/a/cover_a.jpg', $loadedA->coverFoto->foto_url);

        // Album B
        $this->assertEquals(1, $loadedB->fotos_count);
        $this->assertNotNull($loadedB->coverFoto);
        $this->assertEquals($fotoB1->id, $loadedB->coverFoto->id);
        $this->assertEquals('galeri/b/cover_b.jpg', $loadedB->coverFoto->foto_url);

        // Album C
        $this->assertEquals(0, $loadedC->fotos_count);
        $this->assertNull($loadedC->coverFoto);
    }

    // ==========================================
    // 2. AUTHORIZATION TESTS (ScopeAuthorizer)
    // ==========================================

    public function test_warga_dan_bendahara_dapat_view_tetapi_tidak_dapat_manage(): void
    {
        // Warga
        $this->assertTrue(ScopeAuthorizer::canViewGaleri($this->warga1, $this->rt05->id));
        $this->assertFalse(ScopeAuthorizer::canManageGaleri($this->warga1, $this->rt05->id));

        // Bendahara
        $this->assertTrue(ScopeAuthorizer::canViewGaleri($this->bendahara, $this->rt05->id));
        $this->assertFalse(ScopeAuthorizer::canManageGaleri($this->bendahara, $this->rt05->id));
    }

    public function test_sekretaris_ketua_rt_dan_wakil_rt_dapat_view_dan_manage_rt_sendiri(): void
    {
        // Sekretaris
        $this->assertTrue(ScopeAuthorizer::canViewGaleri($this->sekretaris, $this->rt05->id));
        $this->assertTrue(ScopeAuthorizer::canManageGaleri($this->sekretaris, $this->rt05->id));

        // Wakil RT
        $this->assertTrue(ScopeAuthorizer::canViewGaleri($this->wakilRt, $this->rt05->id));
        $this->assertTrue(ScopeAuthorizer::canManageGaleri($this->wakilRt, $this->rt05->id));

        // Ketua RT
        $this->assertTrue(ScopeAuthorizer::canViewGaleri($this->ketuaRt, $this->rt05->id));
        $this->assertTrue(ScopeAuthorizer::canManageGaleri($this->ketuaRt, $this->rt05->id));
    }

    public function test_ketua_rw_dapat_view_rt_binaannya_tetapi_tidak_dapat_manage(): void
    {
        // RT 05 (dalam RW 03)
        $this->assertTrue(ScopeAuthorizer::canViewGaleri($this->ketuaRw, $this->rt05->id));
        $this->assertFalse(ScopeAuthorizer::canManageGaleri($this->ketuaRw, $this->rt05->id));

        // RT 06 (juga dalam RW 03)
        $this->assertTrue(ScopeAuthorizer::canViewGaleri($this->ketuaRw, $this->rt06->id));
        $this->assertFalse(ScopeAuthorizer::canManageGaleri($this->ketuaRw, $this->rt06->id));
    }

    public function test_ketua_rw_tidak_dapat_view_atau_manage_rt_di_luar_rw_binaannya(): void
    {
        $this->assertFalse(ScopeAuthorizer::canViewGaleri($this->ketuaRw, $this->foreignRt->id));
        $this->assertFalse(ScopeAuthorizer::canManageGaleri($this->ketuaRw, $this->foreignRt->id));
    }

    public function test_cross_tenant_view_dan_manage_ditolak_untuk_warga_dan_pengurus_rt(): void
    {
        // Warga RT 05 mencoba akses RT 06
        $this->assertFalse(ScopeAuthorizer::canViewGaleri($this->warga1, $this->rt06->id));
        $this->assertFalse(ScopeAuthorizer::canManageGaleri($this->warga1, $this->rt06->id));

        // Sekretaris RT 05 mencoba akses RT 06
        $this->assertFalse(ScopeAuthorizer::canViewGaleri($this->sekretaris, $this->rt06->id));
        $this->assertFalse(ScopeAuthorizer::canManageGaleri($this->sekretaris, $this->rt06->id));

        // Ketua RT 05 mencoba manage RT 06
        $this->assertFalse(ScopeAuthorizer::canViewGaleri($this->ketuaRt, $this->rt06->id));
        $this->assertFalse(ScopeAuthorizer::canManageGaleri($this->ketuaRt, $this->rt06->id));
    }

    public function test_super_admin_memiliki_akses_global_view_dan_manage(): void
    {
        // Super admin pada RT 05
        $this->assertTrue(ScopeAuthorizer::canViewGaleri($this->superAdmin, $this->rt05->id));
        $this->assertTrue(ScopeAuthorizer::canManageGaleri($this->superAdmin, $this->rt05->id));

        // Super admin pada foreign RT
        $this->assertTrue(ScopeAuthorizer::canViewGaleri($this->superAdmin, $this->foreignRt->id));
        $this->assertTrue(ScopeAuthorizer::canManageGaleri($this->superAdmin, $this->foreignRt->id));
    }

    public function test_user_null_atau_non_aktif_ditolak(): void
    {
        $this->assertFalse(ScopeAuthorizer::canViewGaleri(null, $this->rt05->id));
        $this->assertFalse(ScopeAuthorizer::canManageGaleri(null, $this->rt05->id));

        // User non aktif (status belum_daftar)
        $this->warga1->update(['status' => 'belum_daftar']);
        $this->assertFalse(ScopeAuthorizer::canViewGaleri($this->warga1->fresh(), $this->rt05->id));
        $this->assertFalse(ScopeAuthorizer::canManageGaleri($this->warga1->fresh(), $this->rt05->id));
    }

    // ==========================================
    // 3. AUDIT ACTION CONSTANTS TESTS
    // ==========================================

    public function test_konstanta_audit_action_galeri_tersedia_dan_valid(): void
    {
        // 1. Nilai konstanta
        $this->assertEquals('GALERI_ALBUM_CREATED', AuditAction::GALERI_ALBUM_CREATED);
        $this->assertEquals('GALERI_ALBUM_UPDATED', AuditAction::GALERI_ALBUM_UPDATED);
        $this->assertEquals('GALERI_ALBUM_DELETED', AuditAction::GALERI_ALBUM_DELETED);

        // 2. Label presentasi UI
        $this->assertEquals('Membuat Album Kegiatan', AuditAction::getLabel(AuditAction::GALERI_ALBUM_CREATED));
        $this->assertEquals('Memperbarui Info Album', AuditAction::getLabel(AuditAction::GALERI_ALBUM_UPDATED));
        $this->assertEquals('Menghapus Album Kegiatan', AuditAction::getLabel(AuditAction::GALERI_ALBUM_DELETED));

        // 3. Modul mapping
        $this->assertEquals('Galeri', AuditAction::getModule(AuditAction::GALERI_ALBUM_CREATED));
        $this->assertEquals('Galeri', AuditAction::getModule(AuditAction::GALERI_ALBUM_UPDATED));
        $this->assertEquals('Galeri', AuditAction::getModule(AuditAction::GALERI_ALBUM_DELETED));
        $this->assertEquals('Galeri', AuditAction::getModule('custom_action', 'galeri_album'));
    }
}
