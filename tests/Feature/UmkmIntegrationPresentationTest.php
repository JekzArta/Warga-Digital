<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Rt;
use App\Models\UmkmListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UmkmIntegrationPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected User $wargaRt05;
    protected User $sekretarisRt05;
    protected User $bendaharaRt05;
    protected User $ketuaRt05;
    protected User $wakilRt05;
    protected User $ketuaRw;
    protected User $superAdmin;
    protected User $wargaRt06;
    protected Rt $rt05;
    protected Rt $rt06;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);
        Storage::fake('public');

        // Bersihkan data awal agar deterministik
        UmkmListing::withoutGlobalScopes()->delete();
        AuditLog::where('target_type', 'umkm_listings')->orWhere('target_type', 'umkm_listing')->delete();

        $this->rt05 = Rt::where('nomor_rt', 5)->first();

        // Buat RT 06 untuk uji tenant isolation
        $this->rt06 = Rt::create([
            'rw_id' => $this->rt05->rw_id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);

        $this->wargaRt05 = User::where('nik', '3273021005050010')->first();
        $this->sekretarisRt05 = User::where('nik', '3273021005050003')->first();
        $this->bendaharaRt05 = User::where('nik', '3273021005050004')->first();
        $this->ketuaRt05 = User::where('nik', '3273021005050001')->first();
        $this->wakilRt05 = User::where('nik', '3273021005050002')->first();
        $this->ketuaRw = User::where('nik', '3273021005030001')->first();
        $this->superAdmin = User::where('is_super_admin', true)->first();

        $this->wargaRt06 = User::create([
            'rt_id' => $this->rt06->id,
            'kode_warga' => 'WRG-RT06-001',
            'nik' => '3273021005060001',
            'nama' => 'Warga RT 06 Sekeloa',
            'email' => 'warga.rt06@example.com',
            'password' => Hash::make('password123'),
            'status' => 'aktif',
            'no_hp' => '081299990006',
        ]);
        \App\Models\UserRole::create([
            'user_id' => $this->wargaRt06->id,
            'role' => 'warga',
            'assigned_at' => now(),
        ]);
    }

    /**
     * 1. Menu UMKM di sidebar memiliki rute benar dan dapat dilihat oleh semua role.
     */
    public function test_sidebar_has_correct_umkm_route_and_label_for_all_roles(): void
    {
        $rolesToTest = [
            $this->wargaRt05,
            $this->sekretarisRt05,
            $this->bendaharaRt05,
            $this->ketuaRt05,
            $this->wakilRt05,
            $this->ketuaRw,
            $this->superAdmin,
        ];

        foreach ($rolesToTest as $user) {
            $response = $this->actingAs($user)->get(route('dashboard'));
            $response->assertStatus(200);
            $response->assertSee(route('umkm.index'));
            $response->assertSee('UMKM Warga');
        }
    }

    /**
     * 2. Menu UMKM di sidebar berstatus aktif saat pengguna berada pada rute umkm.*.
     */
    public function test_sidebar_umkm_menu_active_state_on_umkm_routes(): void
    {
        $response = $this->actingAs($this->wargaRt05)->get(route('umkm.index'));
        $response->assertStatus(200);
        $response->assertSee(route('umkm.index'));
        $response->assertSee('UMKM Warga');

        // Pastikan styling active terpasang untuk menu UMKM
        $response->assertSee('bg-white/10 text-white font-semibold shadow-xs', false);
        $response->assertSee('text-emerald-400', false);
    }

    /**
     * 3. Quick Action di Dashboard mengarah ke umkm.index dan link Lihat Semua di rekomendasi mengarah ke umkm.index.
     */
    public function test_dashboard_quick_action_and_header_link_to_umkm_index(): void
    {
        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Belanja UMKM');
        $response->assertSee('Dukung produk & jasa warga sekitar', false);
        $response->assertSee('Rekomendasi UMKM di Sekitar Anda');

        // Memastikan link Quick Action dan link Lihat Semua keduanya mengarah ke route('umkm.index')
        $response->assertSee(route('umkm.index'));
    }

    /**
     * 4. Dashboard menampilkan listing DISETUJUI, maksimal 3 listing terbaru.
     */
    public function test_dashboard_displays_approved_listings_max_three_latest_first(): void
    {
        // Buat 4 listing DISETUJUI dengan urutan waktu bertahap
        $listing1 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt05->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Tertua RT05',
            'deskripsi' => 'Deskripsi produk 1',
            'harga' => 10000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);
        $listing1->created_at = now()->subHours(4);
        $listing1->save();

        $listing2 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt05->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Kedua RT05',
            'deskripsi' => 'Deskripsi produk 2',
            'harga' => 20000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);
        $listing2->created_at = now()->subHours(3);
        $listing2->save();

        $listing3 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt05->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'jasa',
            'nama' => 'Jasa Ketiga RT05',
            'deskripsi' => 'Deskripsi jasa 3',
            'harga' => null,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);
        $listing3->created_at = now()->subHours(2);
        $listing3->save();

        $listing4 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt05->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Terbaru RT05',
            'deskripsi' => 'Deskripsi produk 4',
            'harga' => 45000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);
        $listing4->created_at = now()->subHour();
        $listing4->save();

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertStatus(200);

        // 3 listing terbaru harus tampil
        $response->assertSee('Produk Terbaru RT05');
        $response->assertSee('Rp 45.000');
        $response->assertSee('Jasa Ketiga RT05');
        $response->assertSee('Tanya Penjual / Negosiasi');
        $response->assertSee('Produk Kedua RT05');
        $response->assertSee('Rp 20.000');

        // Listing ke-4 (tertua) tidak boleh muncul karena batasan take(3)
        $response->assertDontSee('Produk Tertua RT05');
    }

    /**
     * 5. Dashboard TIDAK menampilkan listing MENUNGGU atau DITOLAK.
     */
    public function test_dashboard_does_not_display_menunggu_or_ditolak_listings(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt05->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Antrean Menunggu',
            'deskripsi' => 'Sedang dalam antrean kurasi',
            'harga' => 15000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt05->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Produk Sudah Ditolak RT',
            'deskripsi' => 'Ditolak karena tidak sesuai',
            'harga' => 25000,
            'status' => UmkmListing::STATUS_DITOLAK,
            'alasan_tolak' => 'Tidak memenuhi kriteria',
        ]);

        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Produk Antrean Menunggu');
        $response->assertDontSee('Produk Sudah Ditolak RT');
        // Pastikan empty state tampil karena tidak ada listing DISETUJUI
        $response->assertSee('Belum ada usaha warga yang tayang');
    }

    /**
     * 6. Dashboard menerapkan isolasi tenant secara ketat (RT 05 tidak melihat RT 06).
     */
    public function test_dashboard_strictly_respects_tenant_isolation(): void
    {
        // Listing disetujui milik RT 05
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt05->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Keripik Singkong Khas RT05',
            'deskripsi' => 'Gurih renyah warga 05',
            'harga' => 12000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Listing disetujui milik RT 06
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt06->id,
            'rt_id' => $this->rt06->id,
            'kategori' => 'barang',
            'nama' => 'Gudeg Kendil Asli RT06',
            'deskripsi' => 'Manis legit warga 06',
            'harga' => 35000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Warga RT 05 mengakses dashboard
        $responseRt05 = $this->actingAs($this->wargaRt05)->get(route('dashboard'));
        $responseRt05->assertStatus(200);
        $responseRt05->assertSee('Keripik Singkong Khas RT05');
        $responseRt05->assertDontSee('Gudeg Kendil Asli RT06');

        // Warga RT 06 mengakses dashboard
        $responseRt06 = $this->actingAs($this->wargaRt06)->get(route('dashboard'));
        $responseRt06->assertStatus(200);
        $responseRt06->assertSee('Gudeg Kendil Asli RT06');
        $responseRt06->assertDontSee('Keripik Singkong Khas RT05');
    }

    /**
     * 7. Dashboard menampilkan empty state yang ramah ketika belum ada listing yang disetujui.
     */
    public function test_dashboard_displays_empty_state_when_no_approved_listings(): void
    {
        $response = $this->actingAs($this->wargaRt05)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada usaha warga yang tayang');
        $response->assertSee('Dukung ekonomi tetangga atau jadilah yang pertama mempromosikan produk dan jasa Anda di lingkungan RT.');
        $response->assertSee('Lihat Katalog UMKM');
        $response->assertSee(route('umkm.index'));
    }

    /**
     * 8. Super Admin dapat melihat listing disetujui di dashboard tanpa exception.
     */
    public function test_super_admin_dashboard_can_view_recommendations(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt05->id,
            'rt_id' => $this->rt05->id,
            'kategori' => 'barang',
            'nama' => 'Kue Nastar Premium RT05',
            'deskripsi' => 'Nastar lezat',
            'harga' => 75000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Kue Nastar Premium RT05');
        $response->assertSee('Rp 75.000');
    }
}
