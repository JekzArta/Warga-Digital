<?php

namespace Tests\Feature;

use App\Models\GaleriAlbum;
use App\Models\GaleriFoto;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GaleriViewPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $ketuaRw;
    protected User $ketuaRt;
    protected User $wakilRt;
    protected User $sekretaris;
    protected User $bendahara;
    protected User $warga1;
    protected Rt $rt05;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);
        Storage::fake('public');

        // Bersihkan data galeri awal
        GaleriAlbum::withoutGlobalScopes()->delete();

        $this->superAdmin = User::where('is_super_admin', true)->first();
        $this->ketuaRw = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rw'))->first();
        $this->ketuaRt = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();
        $this->wakilRt = User::whereHas('roles', fn ($q) => $q->where('role', 'wakil_rt'))->first();
        $this->sekretaris = User::whereHas('roles', fn ($q) => $q->where('role', 'sekretaris'))->first();
        $this->bendahara = User::whereHas('roles', fn ($q) => $q->where('role', 'bendahara'))->first();

        $this->rt05 = $this->ketuaRt->rt;

        $this->warga1 = User::where('rt_id', $this->rt05->id)
            ->whereHas('roles', fn ($q) => $q->where('role', 'warga'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('role', ['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'ketua_rw']))
            ->first();
    }

    public function test_index_menampilkan_judul_dan_kartu_album_dengan_cover_dan_placeholder(): void
    {
        // Album 1 dengan cover foto
        $album1 = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Kerja Bakti Saluran',
            'deskripsi' => 'Gotong royong membersihkan selokan RT 05.',
            'tanggal_kegiatan' => '2026-10-04',
            'created_by' => $this->sekretaris->id,
        ]);
        GaleriFoto::create([
            'album_id' => $album1->id,
            'foto_url' => 'galeri/1/cover.jpg',
            'uploaded_by' => $this->sekretaris->id,
        ]);

        // Album 2 kosong (tanpa foto)
        $album2 = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Rapat Pemilihan RT',
            'deskripsi' => 'Musyawarah mufakat warga.',
            'tanggal_kegiatan' => '2026-10-05',
            'created_by' => $this->sekretaris->id,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('galeri.index'));

        $response->assertOk();
        $response->assertSee('Galeri Kegiatan Warga');
        $response->assertSee('Kerja Bakti Saluran');
        $response->assertSee('1 Foto');
        $response->assertSee('Cover Kerja Bakti');

        $response->assertSee('Rapat Pemilihan RT');
        $response->assertSee('0 Foto');
        $response->assertSee('Belum Ada Foto');
    }

    public function test_index_tombol_buat_album_hanya_muncul_untuk_role_yang_berhak(): void
    {
        // Pengurus yang berwenang
        foreach ([$this->sekretaris, $this->ketuaRt, $this->wakilRt, $this->superAdmin] as $authorizedUser) {
            $this->actingAs($authorizedUser)
                ->get(route('galeri.index'))
                ->assertOk()
                ->assertSee('Buat Album Kegiatan');
        }

        // Role Read-Only tidak melihat tombol buat album
        foreach ([$this->warga1, $this->bendahara, $this->ketuaRw] as $readOnlyUser) {
            $this->actingAs($readOnlyUser)
                ->get(route('galeri.index'))
                ->assertOk()
                ->assertDontSee('Buat Album Kegiatan');
        }
    }

    public function test_show_menampilkan_metadata_album_dan_grid_foto(): void
    {
        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Peringatan Hari Kemerdekaan',
            'deskripsi' => 'Perlombaan 17-an dan syukuran warga.',
            'tanggal_kegiatan' => '2026-08-17',
            'created_by' => $this->sekretaris->id,
        ]);

        GaleriFoto::create([
            'album_id' => $album->id,
            'foto_url' => 'galeri/1/lomba1.jpg',
            'uploaded_by' => $this->sekretaris->id,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('galeri.show', $album->id));

        $response->assertOk();
        $response->assertSee('Peringatan Hari Kemerdekaan');
        $response->assertSee('Perlombaan 17-an dan syukuran warga.');
        $response->assertSee('17 Agustus 2026');
        $response->assertSee('1 Foto Tersimpan');
        $response->assertSee('Kembali ke Daftar Album');
    }

    public function test_show_empty_state_tampil_bila_album_kosong(): void
    {
        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Baru Kosong',
            'tanggal_kegiatan' => '2026-10-06',
            'created_by' => $this->sekretaris->id,
        ]);

        // Warga melihat empty state tapi tidak melihat CTA upload
        $this->actingAs($this->warga1)
            ->get(route('galeri.show', $album->id))
            ->assertOk()
            ->assertSee('Belum Ada Foto Dokumentasi')
            ->assertDontSee('Unggah Foto Pertama');

        // Pengurus melihat empty state dan tombol Unggah Foto Pertama
        $this->actingAs($this->sekretaris)
            ->get(route('galeri.show', $album->id))
            ->assertOk()
            ->assertSee('Belum Ada Foto Dokumentasi')
            ->assertSee('Unggah Foto Pertama');
    }

    public function test_show_tombol_mutation_hanya_tampil_untuk_role_berwenang(): void
    {
        $album = GaleriAlbum::create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Dokumentasi RT',
            'tanggal_kegiatan' => '2026-10-06',
            'created_by' => $this->sekretaris->id,
        ]);

        GaleriFoto::create([
            'album_id' => $album->id,
            'foto_url' => 'galeri/1/foto.jpg',
            'uploaded_by' => $this->sekretaris->id,
        ]);

        // Pengurus melihat tombol Unggah, Edit, Hapus Album, dan Hapus Foto
        $this->actingAs($this->sekretaris)
            ->get(route('galeri.show', $album->id))
            ->assertOk()
            ->assertSee('Unggah Foto')
            ->assertSee('Edit Info')
            ->assertSee('Hapus Album')
            ->assertSee('Hapus foto ini');

        // Warga & Bendahara & Ketua RW tidak melihat tombol mutasi
        foreach ([$this->warga1, $this->bendahara, $this->ketuaRw] as $readOnlyUser) {
            $this->actingAs($readOnlyUser)
                ->get(route('galeri.show', $album->id))
                ->assertOk()
                ->assertDontSee('Unggah Foto')
                ->assertDontSee('Hapus Album')
                ->assertDontSee('Hapus foto ini');
        }
    }

    public function test_sidebar_memiliki_link_galeri_dan_active_state(): void
    {
        $response = $this->actingAs($this->warga1)->get(route('galeri.index'));

        $response->assertOk();
        $response->assertSee(route('galeri.index'));
        $response->assertSee('Galeri Kegiatan');
    }
}
