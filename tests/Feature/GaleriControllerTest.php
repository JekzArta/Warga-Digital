<?php

namespace Tests\Feature;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\GaleriAlbum;
use App\Models\GaleriFoto;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GaleriControllerTest extends TestCase
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

        Storage::fake('public');

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

        // Buat RT 06 dalam RW 03 (sama RW)
        $this->rt06 = Rt::create([
            'rw_id' => $this->rt05->rw_id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);

        // Buat RW & RT Asing (berbeda RW)
        $foreignRw = Rw::create([
            'klien_id' => $this->rw03->klien_id,
            'kode_rw' => '32.73.02.1005-RW99',
            'nomor_rw' => 99,
            'nama' => 'RW 99 Asing',
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
    // 1. READ TESTS (index & show)
    // ==========================================

    public function test_warga_dan_bendahara_dapat_melihat_galeri_rt_sendiri(): void
    {
        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Kegiatan RT 05',
            'tanggal_kegiatan' => '2026-08-17',
            'created_by' => $this->sekretaris->id,
        ]);

        // Warga
        $this->actingAs($this->warga1)
            ->get(route('galeri.index'))
            ->assertOk();

        $this->actingAs($this->warga1)
            ->get(route('galeri.show', $album->id))
            ->assertOk();

        // Bendahara
        $this->actingAs($this->bendahara)
            ->get(route('galeri.index'))
            ->assertOk();

        $this->actingAs($this->bendahara)
            ->get(route('galeri.show', $album->id))
            ->assertOk();
    }

    public function test_pengurus_rt_dapat_melihat_galeri_rt_sendiri(): void
    {
        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Rapat Pengurus',
            'tanggal_kegiatan' => '2026-09-01',
            'created_by' => $this->sekretaris->id,
        ]);

        foreach ([$this->sekretaris, $this->ketuaRt, $this->wakilRt] as $pengurus) {
            $this->actingAs($pengurus)
                ->get(route('galeri.index'))
                ->assertOk();

            $this->actingAs($pengurus)
                ->get(route('galeri.show', $album->id))
                ->assertOk();
        }
    }

    public function test_ketua_rw_dapat_melihat_seluruh_rt_binaannya(): void
    {
        $album05 = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album RT 05',
            'tanggal_kegiatan' => '2026-09-01',
            'created_by' => $this->sekretaris->id,
        ]);

        $album06 = GaleriAlbum::create([
            'rt_id' => $this->rt06->id,
            'judul' => 'Album RT 06',
            'tanggal_kegiatan' => '2026-09-02',
            'created_by' => $this->sekretaris->id,
        ]);

        $this->actingAs($this->ketuaRw)
            ->get(route('galeri.index'))
            ->assertOk();

        $this->actingAs($this->ketuaRw)
            ->get(route('galeri.show', $album05->id))
            ->assertOk();

        $this->actingAs($this->ketuaRw)
            ->get(route('galeri.show', $album06->id))
            ->assertOk();

        // Filter ke RT 05
        $this->actingAs($this->ketuaRw)
            ->get(route('galeri.index', ['rt_id' => $this->rt05->id]))
            ->assertOk();
    }

    public function test_super_admin_dapat_melihat_galeri_secara_global(): void
    {
        $albumForeign = GaleriAlbum::create([
            'rt_id' => $this->foreignRt->id,
            'judul' => 'Album Asing',
            'tanggal_kegiatan' => '2026-09-01',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('galeri.index'))
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->get(route('galeri.show', $albumForeign->id))
            ->assertOk();
    }

    public function test_cross_tenant_read_ditolak(): void
    {
        $albumForeign = GaleriAlbum::create([
            'rt_id' => $this->foreignRt->id,
            'judul' => 'Album RT Asing',
            'tanggal_kegiatan' => '2026-09-01',
            'created_by' => $this->superAdmin->id,
        ]);

        // Warga RT 05 mencoba akses album RT Asing (terisolasi TenantScope -> 404)
        $this->actingAs($this->warga1)
            ->get(route('galeri.show', $albumForeign->id))
            ->assertNotFound();

        // Ketua RW 03 mencoba akses album RT Asing di luar RW-nya -> 404
        $this->actingAs($this->ketuaRw)
            ->get(route('galeri.show', $albumForeign->id))
            ->assertNotFound();

        // Warga mencoba filter rt_id asing di index -> 403
        $this->actingAs($this->warga1)
            ->get(route('galeri.index', ['rt_id' => $this->rt06->id]))
            ->assertForbidden();
    }

    // ==========================================
    // 2. ALBUM MUTATION TESTS (create, update, delete)
    // ==========================================

    public function test_warga_dan_bendahara_tidak_dapat_create_update_delete_album(): void
    {
        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Awal',
            'tanggal_kegiatan' => '2026-09-01',
            'created_by' => $this->sekretaris->id,
        ]);

        foreach ([$this->warga1, $this->bendahara] as $actor) {
            // Create
            $this->actingAs($actor)
                ->post(route('galeri.album.store'), [
                    'judul' => 'Coba Buat Album',
                    'tanggal_kegiatan' => '2026-09-02',
                ])
                ->assertForbidden();

            // Update
            $this->actingAs($actor)
                ->put(route('galeri.album.update', $album->id), [
                    'judul' => 'Coba Ubah Album',
                    'tanggal_kegiatan' => '2026-09-02',
                ])
                ->assertForbidden();

            // Delete
            $this->actingAs($actor)
                ->delete(route('galeri.album.destroy', $album->id))
                ->assertForbidden();
        }
    }

    public function test_ketua_rw_tidak_dapat_manage_album_rt(): void
    {
        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album RT 05',
            'tanggal_kegiatan' => '2026-09-01',
            'created_by' => $this->sekretaris->id,
        ]);

        $this->actingAs($this->ketuaRw)
            ->post(route('galeri.album.store'), [
                'judul' => 'Album RW',
                'tanggal_kegiatan' => '2026-09-02',
            ])
            ->assertForbidden();

        $this->actingAs($this->ketuaRw)
            ->put(route('galeri.album.update', $album->id), [
                'judul' => 'Ubah oleh RW',
                'tanggal_kegiatan' => '2026-09-02',
            ])
            ->assertForbidden();

        $this->actingAs($this->ketuaRw)
            ->delete(route('galeri.album.destroy', $album->id))
            ->assertForbidden();
    }

    public function test_pengurus_rt_dapat_create_update_dan_delete_album_scope_sendiri(): void
    {
        $this->actingAs($this->sekretaris);

        // 1. Create Album
        $createResponse = $this->post(route('galeri.album.store'), [
            'judul' => 'Kerja Bakti Saluran',
            'tanggal_kegiatan' => '2026-10-04',
            'deskripsi' => 'Pembersihan saluran air menyambut musim hujan.',
        ]);

        $album = GaleriAlbum::where('judul', 'Kerja Bakti Saluran')->first();
        $this->assertNotNull($album);
        $this->assertEquals($this->rt05->id, $album->rt_id);
        $this->assertEquals($this->sekretaris->id, $album->created_by);
        $createResponse->assertRedirect(route('galeri.show', $album->id));

        // Audit Trail Created
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::GALERI_ALBUM_CREATED,
            'target_type' => 'galeri_album',
            'target_id' => $album->id,
            'user_id' => $this->sekretaris->id,
        ]);

        // 2. Update Album
        $updateResponse = $this->put(route('galeri.album.update', $album->id), [
            'judul' => 'Kerja Bakti Saluran Selesai',
            'tanggal_kegiatan' => '2026-10-04',
            'deskripsi' => 'Dokumentasi penataan saluran bersama warga.',
        ]);

        $album->refresh();
        $this->assertEquals('Kerja Bakti Saluran Selesai', $album->judul);
        $updateResponse->assertRedirect(route('galeri.show', $album->id));

        // Audit Trail Updated
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::GALERI_ALBUM_UPDATED,
            'target_type' => 'galeri_album',
            'target_id' => $album->id,
            'user_id' => $this->sekretaris->id,
        ]);

        // 3. Delete Album
        $deleteResponse = $this->delete(route('galeri.album.destroy', $album->id));
        $deleteResponse->assertRedirect(route('galeri.index'));

        $this->assertDatabaseMissing('galeri_album', ['id' => $album->id]);

        // Audit Trail Deleted
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => AuditAction::GALERI_ALBUM_DELETED,
            'target_type' => 'galeri_album',
            'target_id' => $album->id,
            'user_id' => $this->sekretaris->id,
        ]);
    }

    public function test_manipulasi_rt_id_dari_request_tidak_bisa_memindahkan_ownership_album(): void
    {
        $this->actingAs($this->sekretaris); // Sekretaris RT 05

        // Coba kirim rt_id = RT 06
        $this->post(route('galeri.album.store'), [
            'rt_id' => $this->rt06->id,
            'judul' => 'Album Spoofed',
            'tanggal_kegiatan' => '2026-10-04',
        ]);

        $album = GaleriAlbum::where('judul', 'Album Spoofed')->first();
        $this->assertNotNull($album);
        // Ownership tetap terkunci pada RT 05 milik sekretaris
        $this->assertEquals($this->rt05->id, $album->rt_id);
    }

    public function test_super_admin_dapat_manage_album_global(): void
    {
        $this->actingAs($this->superAdmin);

        // Super Admin buat album di RT Asing
        $this->post(route('galeri.album.store'), [
            'rt_id' => $this->foreignRt->id,
            'judul' => 'Album oleh Super Admin',
            'tanggal_kegiatan' => '2026-10-04',
        ]);

        $album = GaleriAlbum::withoutGlobalScopes()->where('judul', 'Album oleh Super Admin')->first();
        $this->assertNotNull($album);
        $this->assertEquals($this->foreignRt->id, $album->rt_id);
    }

    // ==========================================
    // 3. MULTI-UPLOAD FOTO TESTS
    // ==========================================

    public function test_single_upload_foto_berhasil_dan_menggunakan_hash_name(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Foto',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->sekretaris->id,
        ]);

        $file = UploadedFile::fake()->image('foto_kegiatan.jpg', 600, 400)->size(500);

        $response = $this->post(route('galeri.foto.store', $album->id), [
            'fotos' => [$file],
        ]);

        $response->assertRedirect(route('galeri.show', $album->id));

        $this->assertEquals(1, $album->fotos()->count());
        $foto = $album->fotos()->first();

        // Memastikan tersimpan di subdirektori galeri/{album_id} dengan hash name
        $this->assertStringStartsWith("galeri/{$album->id}/", $foto->foto_url);
        $this->assertStringNotContainsString('foto_kegiatan.jpg', $foto->foto_url);

        Storage::disk('public')->assertExists($foto->foto_url);
    }

    public function test_multi_upload_sampai_10_foto_berhasil(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Batch',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->sekretaris->id,
        ]);

        $files = [];
        for ($i = 1; $i <= 10; $i++) {
            $files[] = UploadedFile::fake()->image("foto_{$i}.png", 400, 400)->size(200);
        }

        $response = $this->post(route('galeri.foto.store', $album->id), [
            'fotos' => $files,
        ]);

        $response->assertRedirect(route('galeri.show', $album->id));
        $this->assertEquals(10, $album->fotos()->count());

        foreach ($album->fotos as $foto) {
            Storage::disk('public')->assertExists($foto->foto_url);
        }
    }

    public function test_upload_lebih_dari_10_foto_ditolak_validasi(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Limit Test',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->sekretaris->id,
        ]);

        $files = [];
        for ($i = 1; $i <= 11; $i++) {
            $files[] = UploadedFile::fake()->image("foto_{$i}.jpg")->size(100);
        }

        $response = $this->post(route('galeri.foto.store', $album->id), [
            'fotos' => $files,
        ]);

        $response->assertSessionHasErrors('fotos');
        $this->assertEquals(0, $album->fotos()->count());
    }

    public function test_upload_file_lebih_dari_3mb_ditolak_validasi(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Size Test',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->sekretaris->id,
        ]);

        $oversizedFile = UploadedFile::fake()->image('besar.jpg')->size(4000); // 4MB

        $response = $this->post(route('galeri.foto.store', $album->id), [
            'fotos' => [$oversizedFile],
        ]);

        $response->assertSessionHasErrors('fotos.0');
        $this->assertEquals(0, $album->fotos()->count());
    }

    public function test_upload_mime_type_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Mime Test',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->sekretaris->id,
        ]);

        $pdfFile = UploadedFile::fake()->create('dokumen.pdf', 500, 'application/pdf');

        $response = $this->post(route('galeri.foto.store', $album->id), [
            'fotos' => [$pdfFile],
        ]);

        $response->assertSessionHasErrors('fotos.0');
        $this->assertEquals(0, $album->fotos()->count());
    }

    public function test_cross_tenant_upload_ditolak(): void
    {
        $this->actingAs($this->sekretaris); // RT 05

        $albumRT06 = GaleriAlbum::create([
            'rt_id' => $this->rt06->id,
            'judul' => 'Album RT 06',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->superAdmin->id,
        ]);

        $file = UploadedFile::fake()->image('foto.jpg')->size(200);

        // Akses ke album RT 06 oleh sekretaris RT 05 ditolak (TenantScope -> 404)
        $this->post(route('galeri.foto.store', $albumRT06->id), [
            'fotos' => [$file],
        ])->assertNotFound();

        $this->assertEquals(0, $albumRT06->fotos()->count());
    }

    // ==========================================
    // 4. PARTIAL FAILURE CLEANUP TEST
    // ==========================================

    public function test_partial_failure_upload_membersihkan_physical_files_dan_db(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Partial Failure',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->sekretaris->id,
        ]);

        // Simulasikan kegagalan saat iterasi upload ke-2
        $file1 = UploadedFile::fake()->image('valid1.jpg')->size(100);
        $file2 = UploadedFile::fake()->image('valid2.jpg')->size(100);

        // Buat mock intersep DB atau exception
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'insert into "galeri_foto"') || str_contains($query->sql, 'insert into `galeri_foto`')) {
                static $counter = 0;
                $counter++;
                if ($counter >= 2) {
                    throw new \RuntimeException('Simulasi error database saat insert foto ke-2');
                }
            }
        });

        $response = $this->post(route('galeri.foto.store', $album->id), [
            'fotos' => [$file1, $file2],
        ]);

        $response->assertSessionHasErrors('fotos');

        // DB record harus 0 karena rollback
        $this->assertEquals(0, GaleriFoto::where('album_id', $album->id)->count());

        // Tidak boleh ada physical files tersisa di folder galeri/{album->id}
        $filesInStorage = Storage::disk('public')->files("galeri/{$album->id}");
        $this->assertEmpty($filesInStorage, 'Physical files harus bersih tanpa orphan files setelah upload gagal.');
    }

    // ==========================================
    // 5. DELETE FOTO & DELETE ALBUM TESTS
    // ==========================================

    public function test_destroy_foto_menghapus_physical_file_dan_record_db(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Foto Hapus',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->sekretaris->id,
        ]);

        $file = UploadedFile::fake()->image('hapus.jpg')->size(200);
        $this->post(route('galeri.foto.store', $album->id), ['fotos' => [$file]]);

        $foto = $album->fotos()->first();
        $path = $foto->foto_url;
        Storage::disk('public')->assertExists($path);

        $initialAuditCount = AuditLog::count();

        // Hapus foto
        $deleteResponse = $this->delete(route('galeri.foto.destroy', [$album->id, $foto->id]));
        $deleteResponse->assertRedirect(route('galeri.show', $album->id));

        $this->assertDatabaseMissing('galeri_foto', ['id' => $foto->id]);
        Storage::disk('public')->assertMissing($path);

        // Mutasi foto tidak membuat audit log GALERI_ALBUM_* baru
        $this->assertEquals($initialAuditCount, AuditLog::count());
    }

    public function test_foto_album_lain_tidak_dapat_dihapus_melalui_manipulasi_id(): void
    {
        $this->actingAs($this->sekretaris);

        $albumA = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album A',
            'tanggal_kegiatan' => '2026-10-01',
            'created_by' => $this->sekretaris->id,
        ]);
        $albumB = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album B',
            'tanggal_kegiatan' => '2026-10-02',
            'created_by' => $this->sekretaris->id,
        ]);

        $fotoB = GaleriFoto::create([
            'album_id' => $albumB->id,
            'foto_url' => 'galeri/b/foto.jpg',
            'uploaded_by' => $this->sekretaris->id,
        ]);

        // Coba tembak destroy fotoB menggunakan URL albumA
        $this->delete(route('galeri.foto.destroy', [$albumA->id, $fotoB->id]))
            ->assertNotFound();

        $this->assertDatabaseHas('galeri_foto', ['id' => $fotoB->id]);
    }

    public function test_destroy_album_menghapus_child_foto_dan_membersihkan_direktori_storage(): void
    {
        $this->actingAs($this->sekretaris);

        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Bersama Foto',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->sekretaris->id,
        ]);

        $file1 = UploadedFile::fake()->image('f1.jpg')->size(100);
        $file2 = UploadedFile::fake()->image('f2.jpg')->size(100);
        $this->post(route('galeri.foto.store', $album->id), ['fotos' => [$file1, $file2]]);

        $this->assertEquals(2, $album->fotos()->count());
        $filesInStorage = Storage::disk('public')->files("galeri/{$album->id}");
        $this->assertCount(2, $filesInStorage);

        // Hapus album
        $this->delete(route('galeri.album.destroy', $album->id))
            ->assertRedirect(route('galeri.index'));

        // DB album & child fotos terhapus
        $this->assertDatabaseMissing('galeri_album', ['id' => $album->id]);
        $this->assertDatabaseMissing('galeri_foto', ['album_id' => $album->id]);

        // Direktori fisik storage dibersihkan
        $remainingFiles = Storage::disk('public')->files("galeri/{$album->id}");
        $this->assertEmpty($remainingFiles);

        // Audit Trail tercatat dengan metadata akurat
        $audit = AuditLog::where('aksi', AuditAction::GALERI_ALBUM_DELETED)
            ->where('target_id', $album->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('Album Bersama Foto', $audit->sebelum['judul']);
        $this->assertEquals(2, $audit->sebelum['foto_terhapus_count']);
        $this->assertEquals($this->rt05->id, $audit->rt_id);
    }
}
