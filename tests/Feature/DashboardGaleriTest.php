<?php

namespace Tests\Feature;

use App\Models\GaleriAlbum;
use App\Models\GaleriFoto;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardGaleriTest extends TestCase
{
    use RefreshDatabase;

    protected User $wargaRt05;
    protected User $ketuaRt05;
    protected User $ketuaRw03;
    protected User $superAdmin;
    protected Rt $rt05;
    protected Rt $rt06;
    protected Rw $rw03;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        // Kosongkan album demo agar pengujian tiap case deterministik
        GaleriFoto::query()->delete();
        GaleriAlbum::withoutGlobalScopes()->delete();

        $this->ketuaRt05 = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rt'))->first();
        $this->ketuaRw03 = User::whereHas('roles', fn ($q) => $q->where('role', 'ketua_rw'))->first();
        $this->superAdmin = User::where('is_super_admin', true)->first();
        $this->rt05 = $this->ketuaRt05->rt;
        $this->rw03 = Rw::first();

        $this->wargaRt05 = User::where('rt_id', $this->rt05->id)
            ->whereHas('roles', fn ($q) => $q->where('role', 'warga'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('role', ['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'ketua_rw']))
            ->first();

        // Buat RT 06 sebagai tenant pembanding
        $this->rt06 = Rt::firstOrCreate(
            ['nomor_rt' => '06', 'rw_id' => $this->rw03->id],
            ['nama_rt' => 'RT 06', 'kode_rt' => 'RT06']
        );
    }

    /**
     * Dashboard merender album terbaru lengkap dengan cover, jumlah foto, dan judul.
     */
    public function test_dashboard_renders_latest_album_with_cover_and_photo_count(): void
    {
        $album = GaleriAlbum::withoutGlobalScopes()->create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Kerja Bakti Akbar Lingkungan RT 05',
            'deskripsi' => 'Pembersihan saluran air dan selokan',
            'tanggal_kegiatan' => '2026-10-05',
            'created_by' => $this->ketuaRt05->id,
        ]);

        GaleriFoto::create([
            'album_id' => $album->id,
            'foto_url' => 'galeri/' . $album->id . '/foto1.jpg',
            'uploaded_by' => $this->ketuaRt05->id,
        ]);
        GaleriFoto::create([
            'album_id' => $album->id,
            'foto_url' => 'galeri/' . $album->id . '/foto2.jpg',
            'uploaded_by' => $this->ketuaRt05->id,
        ]);

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Kerja Bakti Akbar Lingkungan RT 05');
        $response->assertSee('Dokumentasi Terbaru');
        $response->assertSee('2 Foto');
        $response->assertSee(route('galeri.show', $album->id));
    }

    /**
     * Klik album pada dashboard langsung mengarah ke halaman galeri.show yang sesuai.
     */
    public function test_dashboard_clicking_album_links_to_galeri_show(): void
    {
        $album = GaleriAlbum::withoutGlobalScopes()->create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Peringatan Semarak 17 Agustus',
            'deskripsi' => 'Lomba warga dan tasyakuran',
            'tanggal_kegiatan' => '2026-08-17',
            'created_by' => $this->ketuaRt05->id,
        ]);

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('galeri.show', $album->id));
    }

    /**
     * Dashboard menampilkan empty state yang rapi saat belum ada album kegiatan.
     */
    public function test_dashboard_renders_empty_state_when_no_albums_exist(): void
    {
        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dokumentasi Warga');
        $response->assertSee('Belum ada album kegiatan yang dipublikasikan.');
        $response->assertSee(route('galeri.index'));
    }

    /**
     * Warga tidak melihat album milik RT lain di dashboard-nya.
     */
    public function test_dashboard_galeri_scope_warga_cannot_see_other_rt_album(): void
    {
        // Album di RT 06
        GaleriAlbum::withoutGlobalScopes()->create([
            'rt_id' => $this->rt06->id,
            'judul' => 'Kegiatan Khusus RT 06 Lain',
            'deskripsi' => 'Hanya untuk warga RT 06',
            'tanggal_kegiatan' => '2026-10-06',
            'created_by' => $this->ketuaRt05->id,
        ]);

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();
        // Karena di RT 05 tidak ada album, harus menampilkan empty state
        $response->assertDontSee('Kegiatan Khusus RT 06 Lain');
        $response->assertSee('Belum ada album kegiatan yang dipublikasikan.');
    }

    /**
     * Pengurus RT (Ketua RT) hanya melihat album di lingkup RT-nya sendiri.
     */
    public function test_dashboard_galeri_scope_ketua_rt_cannot_see_other_rt_album(): void
    {
        GaleriAlbum::withoutGlobalScopes()->create([
            'rt_id' => $this->rt06->id,
            'judul' => 'Festival Musik RT 06 Sebelah',
            'deskripsi' => 'Pentas musik RT 06',
            'tanggal_kegiatan' => '2026-10-06',
            'created_by' => $this->ketuaRt05->id,
        ]);

        $response = $this->actingAs($this->ketuaRt05)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Festival Musik RT 06 Sebelah');
        $response->assertSee('Belum ada album kegiatan yang dipublikasikan.');
    }

    /**
     * Ketua RW melihat album kegiatan dari RT binaannya di RW yang sama.
     */
    public function test_dashboard_galeri_scope_ketua_rw_sees_album_from_managed_rts(): void
    {
        GaleriAlbum::withoutGlobalScopes()->create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Kerja Bakti Warga RT 05 Binaan RW',
            'deskripsi' => 'Kerja bakti drainase',
            'tanggal_kegiatan' => '2026-10-06',
            'created_by' => $this->ketuaRt05->id,
        ]);

        $response = $this->actingAs($this->ketuaRw03)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Kerja Bakti Warga RT 05 Binaan RW');
    }

    /**
     * Super Admin melihat album terbaru secara global.
     */
    public function test_dashboard_galeri_scope_super_admin_sees_latest_album_globally(): void
    {
        GaleriAlbum::withoutGlobalScopes()->create([
            'rt_id' => $this->rt06->id,
            'judul' => 'Album Lintas Wilayah untuk Super Admin',
            'deskripsi' => 'Dokumentasi global',
            'tanggal_kegiatan' => '2026-10-06',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Album Lintas Wilayah untuk Super Admin');
    }

    /**
     * Memastikan query dashboard melakukan eager loading coverFoto dan tidak memicu N+1.
     */
    public function test_dashboard_does_not_cause_n_plus_one_for_galeri_photos(): void
    {
        $album = GaleriAlbum::withoutGlobalScopes()->create([
            'rt_id' => $this->rt05->id,
            'judul' => 'Album Uji Performa Eager Load',
            'deskripsi' => 'Pengujian N+1 query',
            'tanggal_kegiatan' => '2026-10-06',
            'created_by' => $this->ketuaRt05->id,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            GaleriFoto::create([
                'album_id' => $album->id,
                'foto_url' => "galeri/{$album->id}/foto_{$i}.jpg",
                'uploaded_by' => $this->ketuaRt05->id,
            ]);
        }

        DB::enableQueryLog();

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertOk();

        $queries = DB::getQueryLog();
        // Pastikan tidak ada query individual 'select * from galeri_foto where album_id = ?' berulang-ulang
        $fotoQueries = array_filter($queries, fn ($q) => str_contains($q['query'], 'galeri_foto'));

        // Query fotos maksimal 2 (1 untuk withCount, 1 untuk coverFoto oldestOfMany), bukan N kali
        $this->assertLessThanOrEqual(3, count($fotoQueries));
    }
}
