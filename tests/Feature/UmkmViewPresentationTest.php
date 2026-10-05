<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Rt;
use App\Models\UmkmListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UmkmViewPresentationTest extends TestCase
{
    use RefreshDatabase;

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

        // Bersihkan data UMKM & audit log awal agar tes deterministik
        UmkmListing::withoutGlobalScopes()->delete();
        AuditLog::where('target_type', 'umkm_listings')->orWhere('target_type', 'umkm_listing')->delete();

        $this->rt05 = Rt::where('nomor_rt', 5)->first();

        // Buat RT 06 sebagai boundary testing isolasi tenant
        $this->rt06 = Rt::create([
            'rw_id' => $this->rt05->rw_id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);

        $wargaList = User::where('rt_id', $this->rt05->id)
            ->whereHas('roles', fn ($q) => $q->where('role', 'warga'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('role', ['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'ketua_rw']))
            ->take(2)
            ->get();

        $this->warga1 = $wargaList[0];
        $this->warga2 = $wargaList[1];

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

        $this->warga1->update(['no_hp' => '081234567891']);
        $this->warga2->update(['no_hp' => '089876543210']);
    }

    /**
     * 1. Halaman UMKM dapat dirender dengan status HTTP 200.
     */
    public function test_halaman_umkm_dapat_dirender(): void
    {
        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Katalog UMKM & Jasa Warga', false);
        $response->assertSee('Dukung usaha tetangga sekitar RT');
        $response->assertSee('RT 0' . $this->warga1->rt->nomor_rt);
        $response->assertSee('Buka Usaha Warga');
    }

    /**
     * 2. Listing DISETUJUI tampil di etalase katalog.
     */
    public function test_listing_disetujui_tampil_di_etalase(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Keripik Tempe Renyah',
            'deskripsi' => 'Keripik tempe olahan rumahan khas RT 05.',
            'harga' => 15000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Keripik Tempe Renyah');
        $response->assertSee('Keripik tempe olahan rumahan khas RT 05.');
        $response->assertSee('Rp 15.000');
    }

    /**
     * 3. Listing MENUNGGU dan DITOLAK tidak tampil di etalase warga.
     */
    public function test_listing_menunggu_dan_ditolak_tidak_tampil_di_etalase(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Produk Rahasia Menunggu',
            'deskripsi' => 'Belum disetujui pengurus RT.',
            'harga' => 10000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Jasa Ditolak Pengurus',
            'deskripsi' => 'Tidak memenuhi syarat lingkungan.',
            'harga' => null,
            'status' => UmkmListing::STATUS_DITOLAK,
            'alasan_tolak' => 'Tidak sesuai ketentuan',
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Produk Rahasia Menunggu');
        $response->assertDontSee('Jasa Ditolak Pengurus');
    }

    /**
     * 4. Nama listing dan nama penjual (user->nama) tampil di card katalog.
     */
    public function test_nama_listing_dan_penjual_tampil(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Madu Murni Alami',
            'deskripsi' => 'Madu hutan asli tanpa pemanis buatan.',
            'harga' => 75000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga2)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Madu Murni Alami');
        $response->assertSee($this->warga1->nama);
    }

    /**
     * 5. Formatted harga / tarif tampil sesuai accessor formatted_harga.
     */
    public function test_formatted_harga_tampil(): void
    {
        // Barang dengan harga terdefinisi
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Bolu Kukus Lembut',
            'deskripsi' => 'Kue bolu kukus pandan manis.',
            'harga' => 35000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Jasa dengan harga null (Tanya Penjual / Negosiasi)
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Jasa Jahit & Vermak',
            'deskripsi' => 'Vermak celana jeans dan pasang kancing.',
            'harga' => null,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Jasa dengan harga mulai (Mulai Rp xx.xxx)
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Jasa Servis Komputer',
            'deskripsi' => 'Instal ulang dan ganti pasta pendingin.',
            'harga' => 50000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Rp 35.000');
        $response->assertSee('Tanya Penjual / Negosiasi');
    }

    /**
     * 6. Badge kategori Barang dan Jasa tampil secara human-readable.
     */
    public function test_badge_kategori_tampil(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kue Kering Nastar',
            'deskripsi' => 'Nastar selai nanas asli.',
            'harga' => 45000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Jasa Desain Poster',
            'deskripsi' => 'Pembuatan brosur dan pamflet digital.',
            'harga' => null,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Barang');
        $response->assertSee('Jasa');
    }

    /**
     * 7. WhatsApp CTA menggunakan accessor whatsapp_link dengan target blank.
     */
    public function test_whatsapp_cta_menggunakan_whatsapp_link(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Sambal Bawang Pedas',
            'deskripsi' => 'Sambal botolan pedas nikmat.',
            'harga' => 20000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga2)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee($listing->whatsapp_link, false);
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
        $response->assertSee('Hubungi Penjual');

        // Nomor telepon mentah TIDAK ditampilkan sebagai teks biasa di card
        $response->assertDontSee('>081234567891<', false);
        $response->assertDontSee('>+6281234567891<', false);
    }

    /**
     * 8. WhatsApp link bekerja untuk berbagai variasi template (default & custom).
     */
    public function test_whatsapp_link_variasi_template(): void
    {
        // Barang dengan custom template
        $barangCustom = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Brownies Lumer Coklat',
            'deskripsi' => 'Brownies panggang lezat.',
            'harga' => 40000,
            'template_pesan_wa' => 'Halo Kak, apakah brownies lumer masih ada stok?',
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Jasa dengan custom template
        $jasaCustom = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Jasa Cuci Motor Panggilan',
            'deskripsi' => 'Cuci motor kinclong langsung ke rumah.',
            'harga' => 25000,
            'template_pesan_wa' => 'Halo Mas, mau tanya jadwal cuci motor hari ini?',
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee($barangCustom->whatsapp_link, false);
        $response->assertSee($jasaCustom->whatsapp_link, false);
    }

    /**
     * 9. Filter kategori Barang dan Jasa bekerja dan membatasi data.
     */
    public function test_filter_kategori_barang_dan_jasa(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Keripik Pisang Manis',
            'deskripsi' => 'Keripik pisang renyah.',
            'harga' => 12000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Jasa Potong Rambut Pria',
            'deskripsi' => 'Pangkas rambut rapi gaya modern.',
            'harga' => 30000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Filter Barang
        $resBarang = $this->actingAs($this->warga1)->get(route('umkm.index', ['kategori' => 'barang']));
        $resBarang->assertStatus(200);
        $resBarang->assertSee('Keripik Pisang Manis');
        $resBarang->assertDontSee('Jasa Potong Rambut Pria');

        // Filter Jasa
        $resJasa = $this->actingAs($this->warga1)->get(route('umkm.index', ['kategori' => 'jasa']));
        $resJasa->assertStatus(200);
        $resJasa->assertSee('Jasa Potong Rambut Pria');
        $resJasa->assertDontSee('Keripik Pisang Manis');

        // Tanpa filter (Semua)
        $resSemua = $this->actingAs($this->warga1)->get(route('umkm.index'));
        $resSemua->assertStatus(200);
        $resSemua->assertSee('Keripik Pisang Manis');
        $resSemua->assertSee('Jasa Potong Rambut Pria');
    }

    /**
     * 10. Pencarian berdasarkan nama dan deskripsi bekerja dan query dipertahankan.
     */
    public function test_search_nama_dan_deskripsi(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kue Nastar Spesial Nanas',
            'deskripsi' => 'Kue kering mentega wangi.',
            'harga' => 60000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Teknisi AC Dingin',
            'deskripsi' => 'Layanan panggilan cuci evaporator dan cek freon rutin.',
            'harga' => null,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Search berdasarkan nama
        $resNama = $this->actingAs($this->warga1)->get(route('umkm.index', ['q' => 'Nastar']));
        $resNama->assertStatus(200);
        $resNama->assertSee('Kue Nastar Spesial Nanas');
        $resNama->assertDontSee('Teknisi AC Dingin');
        $resNama->assertSee('value="Nastar"', false);

        // Search berdasarkan deskripsi
        $resDeskripsi = $this->actingAs($this->warga1)->get(route('umkm.index', ['q' => 'freon']));
        $resDeskripsi->assertStatus(200);
        $resDeskripsi->assertSee('Teknisi AC Dingin');
        $resDeskripsi->assertDontSee('Kue Nastar Spesial Nanas');
        $resDeskripsi->assertSee('value="freon"', false);
    }

    /**
     * 11. Kombinasi search query dan kategori filter bekerja bersamaan.
     */
    public function test_kombinasi_search_dan_kategori(): void
    {
        // Barang dengan kata 'spesial'
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kopi Bubuk Spesial Robusta',
            'deskripsi' => 'Kopi giling segar nikmat.',
            'harga' => 30000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Jasa dengan kata 'spesial'
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Jasa Pijat Refleksi Spesial',
            'deskripsi' => 'Pijat kebugaran tubuh lelah.',
            'harga' => null,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index', [
            'q' => 'spesial',
            'kategori' => 'barang',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Kopi Bubuk Spesial Robusta');
        $response->assertDontSee('Jasa Pijat Refleksi Spesial');
    }

    /**
     * 12. Pagination membatasi 12 item dan mempertahankan parameter q dan kategori.
     */
    public function test_pagination_dan_query_persistence(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            UmkmListing::withoutGlobalScopes()->create([
                'user_id' => $this->warga1->id,
                'rt_id' => $this->rt05->id,
                'kategori' => UmkmListing::KATEGORI_BARANG,
                'nama' => sprintf('Produk Usaha Ke-%02d', $i),
                'deskripsi' => 'Deskripsi produk warga nomor ' . $i,
                'harga' => 10000 * $i,
                'status' => UmkmListing::STATUS_DISETUJUI,
            ]);
        }

        // Akses halaman 1 dengan filter kategori=barang
        $resPage1 = $this->actingAs($this->warga1)->get(route('umkm.index', ['kategori' => 'barang']));
        $resPage1->assertStatus(200);
        $this->assertCount(12, $resPage1->viewData('etalase'));

        // Akses halaman 2
        $resPage2 = $this->actingAs($this->warga1)->get(route('umkm.index', ['kategori' => 'barang', 'page' => 2]));
        $resPage2->assertStatus(200);
        $this->assertCount(3, $resPage2->viewData('etalase'));

        // Link pagination mempertahankan kategori=barang
        $resPage1->assertSee('kategori=barang', false);
    }

    /**
     * 13. Empty state tampil informatif ketika pencarian / filter tidak menghasilkan produk.
     */
    public function test_empty_state_ketika_tidak_ada_hasil_search_atau_filter(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kue Cubit Lembut',
            'deskripsi' => 'Kue manis jajanan sore.',
            'harga' => 10000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index', ['q' => 'keyword_mustahil_ada']));

        $response->assertStatus(200);
        $response->assertSee('Tidak menemukan produk atau jasa yang sesuai');
        $response->assertSee('keyword_mustahil_ada');
        $response->assertSee('Reset Pencarian & Filter', false);
    }

    /**
     * 14. Empty state tampil ramah ketika RT belum memiliki listing yang disetujui sama sekali.
     */
    public function test_empty_state_ketika_rt_belum_memiliki_listing_disetujui(): void
    {
        // Pastikan tidak ada listing disetujui
        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada usaha warga yang tayang');
        $response->assertSee('Saat ada produk kuliner, kerajinan, atau layanan jasa warga');
    }

    /**
     * 15. Fallback placeholder visual ditampilkan dengan rapi ketika foto_url bernilai NULL.
     */
    public function test_fallback_placeholder_ketika_foto_url_null(): void
    {
        // Listing tanpa foto
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kerupuk Ikan Gurih',
            'deskripsi' => 'Kerupuk renyah tanpa pengawet.',
            'harga' => 8000,
            'foto_url' => null,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Listing dengan foto
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Jasa Cuci Sepatu',
            'deskripsi' => 'Deep cleaning sepatu kesayangan.',
            'harga' => 35000,
            'foto_url' => 'umkm/cuci_sepatu.jpg',
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada foto');
        $response->assertSee(Storage::url('umkm/cuci_sepatu.jpg'), false);
    }

    /**
     * 16. Isolasi tenant: listing disetujui milik RT 06 TIDAK bocor ke etalase warga RT 05.
     */
    public function test_isolasi_tenant_cross_rt_tidak_bocor(): void
    {
        // Listing RT 06
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->wargaRt06->id,
            'rt_id' => $this->rt06->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Es Cendol Durian Asli RT 06',
            'deskripsi' => 'Minuman segar khusus warga RT 06.',
            'harga' => 18000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Warga RT 05 membuka /umkm
        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Es Cendol Durian Asli RT 06');

        // Warga RT 06 membuka /umkm -> melihat listingnya sendiri
        $responseRt06 = $this->actingAs($this->wargaRt06)->get(route('umkm.index'));
        $responseRt06->assertStatus(200);
        $responseRt06->assertSee('Es Cendol Durian Asli RT 06');
    }

    // =========================================================================
    // TAHAP 4: PENGUJIAN USAHA SAYA, MODAL, REVISI & NO HP GATE
    // =========================================================================

    /**
     * 17. Tab Usaha Saya hanya menampilkan listing milik user yang sedang terautentikasi.
     */
    public function test_tab_usaha_saya_hanya_menampilkan_listing_milik_user_login(): void
    {
        // Listing milik warga1
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Keripik Singkong Renyah Warga 1',
            'deskripsi' => 'Keripik singkong gurih khas RT 05.',
            'harga' => 15000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Listing milik warga2
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga2->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_JASA,
            'nama' => 'Jasa Cuci Sepatu Warga 2',
            'deskripsi' => 'Cuci sepatu bersih kilat.',
            'harga' => 30000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Warga 1 membuka tab Usaha Saya
        $response = $this->actingAs($this->warga1)->get(route('umkm.index', ['tab' => 'usaha-saya']));

        $response->assertStatus(200);
        $response->assertSee('Daftar Usaha & Jasa Milik Anda', false);

        // Listing warga 1 tampil pada koleksi Usaha Saya
        $usahaSayaWarga1 = $response->viewData('usahaSaya');
        $this->assertTrue($usahaSayaWarga1->contains('nama', 'Keripik Singkong Renyah Warga 1'));
        $this->assertFalse($usahaSayaWarga1->contains('nama', 'Jasa Cuci Sepatu Warga 2'));
    }

    /**
     * 18. Status badge Menunggu Verifikasi tampil benar pada Usaha Saya.
     */
    public function test_status_badge_menunggu_verifikasi_tampil_pada_usaha_saya(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kue Lapis Legit Menunggu',
            'deskripsi' => 'Kue lapis legit harum wijsman.',
            'harga' => 85000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index', ['tab' => 'usaha-saya']));

        $response->assertStatus(200);
        $response->assertSee('Kue Lapis Legit Menunggu');
        $response->assertSee('Menunggu Verifikasi');
        $response->assertSee('Usaha sedang dalam antrean verifikasi pengurus RT');
    }

    /**
     * 19. Status badge Disetujui & Tayang tampil benar pada Usaha Saya.
     */
    public function test_status_badge_disetujui_dan_tayang_tampil_pada_usaha_saya(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Sambal Cumi Asin Tayang',
            'deskripsi' => 'Sambal cumi pedas gurih botol kaca.',
            'harga' => 35000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index', ['tab' => 'usaha-saya']));

        $response->assertStatus(200);
        $response->assertSee('Sambal Cumi Asin Tayang');
        $response->assertSee('Disetujui &amp; Tayang', false);
        $response->assertSee('Mengubah data substantif akan mengirim ulang usaha ini untuk verifikasi pengurus');
    }

    /**
     * 20. Status Ditolak menampilkan alasan penolakan secara transparan dan tombol Edit & Ajukan Ulang.
     */
    public function test_status_badge_ditolak_dan_alasan_penolakan_transparan(): void
    {
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Minyak Goreng Curah Ditolak',
            'deskripsi' => 'Dijual per liter wadah plastik.',
            'harga' => 14000,
            'status' => UmkmListing::STATUS_DITOLAK,
            'alasan_tolak' => 'Foto produk tidak jelas dan tidak mencantumkan merk resmi.',
        ]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index', ['tab' => 'usaha-saya']));

        $response->assertStatus(200);
        $response->assertSee('Minyak Goreng Curah Ditolak');
        $response->assertSee('Ditolak');
        $response->assertSee('Alasan Penolakan Pengurus:');
        $response->assertSee('Foto produk tidak jelas dan tidak mencantumkan merk resmi.');
        $response->assertSee('Edit &amp; Ajukan Ulang', false);
    }

    /**
     * 21. Empty state Usaha Saya tampil jika user belum memiliki listing apa pun.
     */
    public function test_empty_state_usaha_saya_ketika_user_belum_punya_listing(): void
    {
        $response = $this->actingAs($this->warga1)->get(route('umkm.index', ['tab' => 'usaha-saya']));

        $response->assertStatus(200);
        $response->assertSee('Anda belum memiliki usaha yang didaftarkan');
        $response->assertSee('+ Buka Usaha Sekarang');
    }

    /**
     * 22. No HP gate modal diinisialisasi ketika user belum memiliki no_hp di profil.
     */
    public function test_no_hp_gate_modal_tampil_ketika_user_belum_memiliki_no_hp(): void
    {
        $this->warga1->update(['no_hp' => null]);

        $response = $this->actingAs($this->warga1)->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('hasNoHp: false', false);
        $response->assertSee('Lengkapi Nomor WhatsApp');
        $response->assertSee('Simpan Nomor WhatsApp');
    }

    /**
     * 23. User dapat memperbarui nomor WhatsApp miliknya melalui flow updateNoHp.
     */
    public function test_user_dapat_mengupdate_no_hp_melalui_flow_modal(): void
    {
        $this->warga1->update(['no_hp' => null]);

        $response = $this->actingAs($this->warga1)->post(route('umkm.updateNoHp'), [
            'no_hp' => '081299887766',
        ]);

        $response->assertRedirect();
        $this->warga1->refresh();
        $this->assertEquals('081299887766', $this->warga1->no_hp);
    }

    /**
     * 24. Validasi no_hp: nomor tidak valid akan mengembalikan error sesi.
     */
    public function test_validasi_no_hp_format_tidak_valid(): void
    {
        $response = $this->actingAs($this->warga1)->post(route('umkm.updateNoHp'), [
            'no_hp' => '12345',
        ]);

        $response->assertSessionHasErrors('no_hp');
    }

    /**
     * 25. User dengan no_hp dapat membuat listing baru dan berstatus MENUNGGU.
     */
    public function test_user_dengan_no_hp_dapat_submit_tambah_listing(): void
    {
        $response = $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'kategori' => 'barang',
            'nama' => 'Bolu Kukus Mekar',
            'deskripsi' => 'Bolu kukus warna-warni lembut.',
            'harga' => 20000,
            'template_pesan_wa' => 'Halo Bu, mau pesan bolu kukus 2 kotak ya.',
        ]);

        $response->assertRedirect(route('umkm.index'));
        $this->assertDatabaseHas('umkm_listing', [
            'user_id' => $this->warga1->id,
            'nama' => 'Bolu Kukus Mekar',
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);
    }

    /**
     * 26. Form Jasa mengizinkan harga kosong (null) saat diajukan.
     */
    public function test_form_jasa_mengizinkan_harga_kosong(): void
    {
        $response = $this->actingAs($this->warga1)->post(route('umkm.store'), [
            'kategori' => 'jasa',
            'nama' => 'Jasa Desain Rumah Minimalis',
            'deskripsi' => 'Konsultasi denah dan fasad rumah.',
            'harga' => null,
        ]);

        $response->assertRedirect(route('umkm.index'));
        $listing = UmkmListing::withoutGlobalScopes()->where('nama', 'Jasa Desain Rumah Minimalis')->first();
        $this->assertNotNull($listing);
        $this->assertNull($listing->harga);
        $this->assertEquals('Tanya Penjual / Negosiasi', $listing->formatted_harga);
    }

    /**
     * 27. Revisi listing DITOLAK mengembalikan status ke MENUNGGU dan mereset alasan_tolak & reviewed_by.
     */
    public function test_revisi_listing_ditolak_mengubah_status_menjadi_menunggu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kerupuk Ikan Tenggiri Ditolak',
            'deskripsi' => 'Deskripsi lama.',
            'harga' => 15000,
            'status' => UmkmListing::STATUS_DITOLAK,
            'alasan_tolak' => 'Tolong jelaskan isi berat per bungkus.',
            'reviewed_by' => $this->warga2->id,
        ]);

        $response = $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Kerupuk Ikan Tenggiri Asli 250gr',
            'deskripsi' => 'Kerupuk ikan tenggiri gurih isi 250 gram kemasan pouch kedap udara.',
            'harga' => 18000,
        ]);

        $response->assertRedirect(route('umkm.index'));

        $listing->refresh();
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->status);
        $this->assertNull($listing->alasan_tolak);
        $this->assertNull($listing->reviewed_by);
        $this->assertEquals('Kerupuk Ikan Tenggiri Asli 250gr', $listing->nama);
    }

    /**
     * 28. Edit substantif listing DISETUJUI mengembalikan status ke MENUNGGU.
     */
    public function test_edit_substantif_listing_disetujui_mengembalikan_status_ke_menunggu(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kue Brownies Panggang',
            'deskripsi' => 'Deskripsi lama.',
            'harga' => 45000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($this->warga1)->put(route('umkm.update', $listing->id), [
            'kategori' => 'barang',
            'nama' => 'Kue Brownies Panggang Topping Almond',
            'deskripsi' => 'Deskripsi baru dengan almond panggang.',
            'harga' => 50000,
        ]);

        $response->assertRedirect(route('umkm.index'));

        $listing->refresh();
        $this->assertEquals(UmkmListing::STATUS_MENUNGGU, $listing->status);
    }

    /**
     * 29. User dapat menghapus listing miliknya melalui route destroy.
     */
    public function test_user_dapat_menghapus_listing_milik_sendiri(): void
    {
        $listing = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Barang Mau Dihapus',
            'deskripsi' => 'Akan segera dihapus pemilik.',
            'harga' => 10000,
            'status' => UmkmListing::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->warga1)->delete(route('umkm.destroy', $listing->id));

        $response->assertRedirect(route('umkm.index'));
        $this->assertDatabaseMissing('umkm_listing', [
            'id' => $listing->id,
        ]);
    }

    /**
     * 30. Otorisasi form: user TIDAK DAPAT mengedit atau menghapus listing milik orang lain.
     */
    public function test_user_tidak_dapat_mengedit_atau_menghapus_listing_orang_lain(): void
    {
        $listingWarga1 = UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $this->warga1->id,
            'rt_id' => $this->rt05->id,
            'kategori' => UmkmListing::KATEGORI_BARANG,
            'nama' => 'Kue Milik Warga 1',
            'deskripsi' => 'Tidak boleh diubah warga 2.',
            'harga' => 15000,
            'status' => UmkmListing::STATUS_DISETUJUI,
        ]);

        // Warga 2 mencoba update listing warga 1 -> 403 Forbidden
        $responseEdit = $this->actingAs($this->warga2)->put(route('umkm.update', $listingWarga1->id), [
            'kategori' => 'barang',
            'nama' => 'Kue Dihack Warga 2',
            'deskripsi' => 'Deskripsi bajakan.',
            'harga' => 99999,
        ]);
        $responseEdit->assertStatus(403);

        // Warga 2 mencoba delete listing warga 1 -> 403 Forbidden
        $responseDelete = $this->actingAs($this->warga2)->delete(route('umkm.destroy', $listingWarga1->id));
        $responseDelete->assertStatus(403);
    }
}
