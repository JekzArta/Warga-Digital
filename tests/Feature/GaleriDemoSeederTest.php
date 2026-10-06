<?php

namespace Tests\Feature;

use App\Models\GaleriAlbum;
use App\Models\GaleriFoto;
use App\Models\Rt;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GaleriDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pakai disk public untuk storage fake / test
        Storage::fake('public');
    }

    /**
     * DemoSeeder membuat 4 album kegiatan Galeri pada RT 05 Sekeloa dengan relasi valid.
     */
    public function test_demo_seeder_creates_galeri_albums_with_valid_relations(): void
    {
        $this->seed(DemoSeeder::class);

        $rt05 = Rt::where('nomor_rt', 5)->first();
        $this->assertNotNull($rt05);

        $ketuaRt = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();
        $this->assertNotNull($ketuaRt);

        $albums = GaleriAlbum::withoutGlobalScopes()->where('rt_id', $rt05->id)->get();

        $this->assertCount(4, $albums);

        $expectedJudul = [
            'Kerja Bakti Lingkungan & Penataan Drainase',
            'Peringatan HUT RI & Lomba Warga RT 05',
            'Kajian Ramadhan & Buka Puasa Bersama',
            'Pelayanan Posyandu Balita & Lansia Melati',
        ];

        foreach ($expectedJudul as $judul) {
            $album = $albums->firstWhere('judul', $judul);
            $this->assertNotNull($album, "Album '{$judul}' harus ada.");
            $this->assertEquals($rt05->id, $album->rt_id);
            $this->assertEquals($ketuaRt->id, $album->created_by);
            $this->assertNotEmpty($album->deskripsi);
            $this->assertNotNull($album->tanggal_kegiatan);

            // Setiap album memiliki foto dokumentasi
            $fotos = GaleriFoto::where('album_id', $album->id)->get();
            $this->assertGreaterThanOrEqual(2, $fotos->count());

            foreach ($fotos as $foto) {
                $this->assertEquals($ketuaRt->id, $foto->uploaded_by);
                $this->assertStringStartsWith("galeri/{$album->id}/", $foto->foto_url);
            }
        }
    }

    /**
     * DemoSeeder membuat berkas fisik foto pada disk public.
     */
    public function test_demo_seeder_creates_physical_storage_files_for_photos(): void
    {
        $this->seed(DemoSeeder::class);

        $albums = GaleriAlbum::withoutGlobalScopes()->get();
        $this->assertNotEmpty($albums);

        foreach ($albums as $album) {
            $fotos = GaleriFoto::where('album_id', $album->id)->get();
            $this->assertNotEmpty($fotos);

            foreach ($fotos as $foto) {
                Storage::disk('public')->assertExists($foto->foto_url);
            }
        }
    }

    /**
     * DemoSeeder bersifat idempotent: dijalankan berulang kali tidak menduplikasi album maupun foto.
     */
    public function test_demo_seeder_is_idempotent_on_repeated_runs(): void
    {
        // Jalankan seeder pertama kali
        $this->seed(DemoSeeder::class);

        $albumCountFirstRun = GaleriAlbum::withoutGlobalScopes()->count();
        $fotoCountFirstRun = GaleriFoto::count();

        $this->assertEquals(4, $albumCountFirstRun);
        $this->assertEquals(12, $fotoCountFirstRun); // 3 + 4 + 2 + 3 = 12 foto total

        $rt05 = Rt::where('nomor_rt', 5)->first();
        $ketuaRt = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();

        // Jalankan seeder Galeri kedua kali
        (new DemoSeeder())->seedGaleri($rt05, $ketuaRt);

        $albumCountSecondRun = GaleriAlbum::withoutGlobalScopes()->count();
        $fotoCountSecondRun = GaleriFoto::count();

        // Jumlah album dan foto harus tetap sama persis (tidak ada duplikasi)
        $this->assertEquals($albumCountFirstRun, $albumCountSecondRun);
        $this->assertEquals($fotoCountFirstRun, $fotoCountSecondRun);
    }
}
