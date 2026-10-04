<?php

namespace Tests\Feature;

use App\Models\KasTransaksi;
use App\Models\Rt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KasViewPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected User $warga;
    protected User $bendahara;
    protected User $ketuaRt;
    protected User $ketuaRw;
    protected Rt $rt5;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->warga = User::where('nik', '3273021005050010')->first();
        $this->bendahara = User::where('nik', '3273021005050004')->first();
        $this->ketuaRt = User::where('nik', '3273021005050001')->first();
        $this->ketuaRw = User::where('nik', '3273021005030001')->first();
        $this->rt5 = Rt::where('nomor_rt', 5)->first();
    }

    /**
     * 1. Halaman /kas dapat diakses warga biasa: menampilkan metrik saldo, grafik, mutasi, tanpa tombol aksi.
     */
    public function test_warga_can_view_kas_without_management_actions(): void
    {
        $response = $this->actingAs($this->warga)->get(route('kas.index'));

        $response->assertStatus(200);
        $response->assertSee('Transparansi Kas RT');
        $response->assertSee('Saldo Kas RT Saat Ini');
        $response->assertSee('Uang Masuk Bulan Ini');
        $response->assertSee('Uang Keluar Bulan Ini');
        $response->assertSee('Perbandingan Arus Kas 6 Bulan Terakhir');

        // Warga TIDAK boleh melihat tombol catat, tombol koreksi, ataupun form modal di DOM
        $response->assertDontSee('Catat Pemasukan');
        $response->assertDontSee('Catat Pengeluaran');
        $response->assertDontSee('>Koreksi<', false);
        $response->assertDontSee('Simpan Transaksi');
        $response->assertDontSee('Simpan Koreksi Transaksi');
    }

    /**
     * 2. Pengurus (Bendahara/Ketua RT) melihat tombol manajemen kas dan form modal.
     */
    public function test_bendahara_and_ketua_rt_see_management_buttons_and_modals(): void
    {
        $response = $this->actingAs($this->bendahara)->get(route('kas.index'));

        $response->assertStatus(200);
        $response->assertSee('Catat Pemasukan');
        $response->assertSee('Catat Pengeluaran');
        $response->assertSee('Koreksi');
        $response->assertSee('Simpan Transaksi');
        $response->assertSee('Simpan Koreksi Transaksi');
    }

    /**
     * 3. Ketua RW bersifat read-only: melihat pemilih RT, tanpa tombol aksi atau form modal.
     */
    public function test_ketua_rw_has_rt_selector_and_is_read_only(): void
    {
        $response = $this->actingAs($this->ketuaRw)->get(route('kas.index'));

        $response->assertStatus(200);
        $response->assertSee('Pilih RT:');
        $response->assertSee('RT 05');

        // RW DILARANG memiliki tombol catat, koreksi, ataupun form modal
        $response->assertDontSee('Catat Pemasukan');
        $response->assertDontSee('Catat Pengeluaran');
        $response->assertDontSee('>Koreksi<', false);
        $response->assertDontSee('Simpan Transaksi');
        $response->assertDontSee('Simpan Koreksi Transaksi');
    }

    /**
     * 4. Filter tabel TIDAK mengubah nilai saldo kas berjalan utama.
     */
    public function test_filters_do_not_alter_total_running_balance(): void
    {
        // Hitung saldo awal tanpa filter
        $responseAwal = $this->actingAs($this->warga)->get(route('kas.index'));
        $saldoAwal = $responseAwal->viewData('saldoBerjalan');

        // Terapkan filter hanya untuk jenis = masuk
        $responseFilter = $this->actingAs($this->warga)->get(route('kas.index', ['jenis' => 'masuk']));
        $saldoSesudahFilter = $responseFilter->viewData('saldoBerjalan');

        // Saldo berjalan utama HARUS IDENTIK dan tidak terpengaruh filter tabel
        $this->assertEquals($saldoAwal, $saldoSesudahFilter);
    }

    /**
     * 5. Transaksi hasil koreksi menampilkan badge Terkoreksi dan histori aman.
     */
    public function test_corrected_transaction_displays_badge_and_riwayat(): void
    {
        // Buat T1 lalu koreksi jadi T2
        $t1 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $this->rt5->id,
            'input_by' => $this->bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 250000,
            'keterangan' => 'Pembelian ATK Awal',
            'tanggal' => '2026-10-01',
        ]);

        $t2 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $this->rt5->id,
            'input_by' => $this->bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 200000,
            'keterangan' => 'Pembelian ATK Revisi',
            'tanggal' => '2026-10-01',
            'is_koreksi' => true,
            'koreksi_dari_id' => $t1->id,
            'catatan_koreksi' => 'Diskon nota toko Rp 50.000',
        ]);

        $response = $this->actingAs($this->warga)->get(route('kas.index'));

        $response->assertStatus(200);
        $response->assertSee('Terkoreksi (1x)');
        $response->assertSee('Diskon nota toko Rp 50.000');

        // Pastikan tidak ada data teknis audit (IP address atau ID user) yang bocor
        $response->assertDontSee('ip_address');
        $response->assertDontSee('user_id');
    }

    /**
     * 6. Error validasi form render di dalam modal yang bersangkutan.
     */
    public function test_validation_errors_render_inside_appropriate_modals(): void
    {
        // 1. Submit invalid input kas baru
        $response = $this->actingAs($this->bendahara)
            ->from(route('kas.index'))
            ->post(route('kas.store'), [
                'nominal' => -500, // Invalid
            ]);

        $response->assertRedirect(route('kas.index'));
        $follow = $this->actingAs($this->bendahara)->get(route('kas.index'));
        $follow->assertSee('Periksa kembali isian formulir:');

        // 2. Submit invalid koreksi kas
        $t = KasTransaksi::first();
        $responseKoreksi = $this->actingAs($this->bendahara)
            ->from(route('kas.index'))
            ->post(route('kas.koreksi', $t->id), [
                'is_koreksi_form' => 1,
                'alasan_koreksi' => '', // Invalid kosong
            ]);

        $responseKoreksi->assertRedirect(route('kas.index'));
        $followKoreksi = $this->actingAs($this->bendahara)->get(route('kas.index'));
        $followKoreksi->assertSee('Gagal menyimpan koreksi:');
    }

    /**
     * 7. Wakil RT dan Super Admin juga memiliki hak akses penuh ke form dan modal manajemen.
     */
    public function test_wakil_rt_and_super_admin_can_view_and_manage_kas(): void
    {
        $wakilRt = User::where('nik', '3273021005050002')->first();
        $superAdmin = User::where('email', 'admin@wargadigital.id')->first();

        // Wakil RT
        $resWakil = $this->actingAs($wakilRt)->get(route('kas.index'));
        $resWakil->assertStatus(200);
        $resWakil->assertSee('Catat Pemasukan');
        $resWakil->assertSee('Catat Pengeluaran');
        $resWakil->assertSee('Simpan Transaksi');
        $resWakil->assertSee('Simpan Koreksi Transaksi');

        // Super Admin
        $resAdmin = $this->actingAs($superAdmin)->get(route('kas.index', ['rt_id' => $this->rt5->id]));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Catat Pemasukan');
        $resAdmin->assertSee('Catat Pengeluaran');
        $resAdmin->assertSee('Simpan Transaksi');
        $resAdmin->assertSee('Simpan Koreksi Transaksi');
    }

    /**
     * 8. Atribut pencegahan double-submit dan preservasi data koreksi terpasang di modal.
     */
    public function test_double_submit_protection_and_koreksi_context_preservation(): void
    {
        $response = $this->actingAs($this->bendahara)->get(route('kas.index'));

        // Cek proteksi double submit pada form input
        $response->assertSee('@submit="isSubmitting = true"', false);
        $response->assertSee(':disabled="isSubmitting"', false);

        // Cek proteksi double submit pada form koreksi
        $response->assertSee('@submit="isSubmittingKoreksi = true"', false);
        $response->assertSee(':disabled="isSubmittingKoreksi"', false);

        // Cek label domain resmi
        $response->assertSee('Alasan Koreksi Resmi');
        $response->assertSee('Data Transaksi Sebelumnya');
    }

    /**
     * 9. Integrasi Navigasi: Dashboard widget dan sidebar menu mengarah ke kas.index dengan active state yang benar.
     */
    public function test_navigation_links_and_active_states_in_sidebar_and_dashboard(): void
    {
        // 1. Cek dashboard widget mengarah ke route('kas.index')
        $resDashboard = $this->actingAs($this->warga)->get(route('dashboard'));
        $resDashboard->assertStatus(200);
        $resDashboard->assertSee(route('kas.index'));

        // 2. Cek sidebar menu mengarah ke route('kas.index') dan berstatus aktif saat di /kas
        $resKas = $this->actingAs($this->warga)->get(route('kas.index'));
        $resKas->assertStatus(200);
        $resKas->assertSee(route('kas.index'));

        // Cek indikator visual aktif pada sidebar saat berada di route kas.*
        $resKas->assertSee('bg-white/10 text-white font-semibold shadow-xs', false);
        $resKas->assertSee('Kas & Iuran', false);
    }
}


