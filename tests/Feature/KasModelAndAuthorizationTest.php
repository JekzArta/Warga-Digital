<?php

namespace Tests\Feature;

use App\Models\KasTransaksi;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Services\ScopeAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KasModelAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);
    }

    /**
     * Test 1: Scope aktif hanya mengambil leaf node (versi mutakhir) dan mengecualikan transaksi yang sudah dikoreksi.
     */
    public function test_scope_aktif_only_returns_latest_uncorrected_transactions(): void
    {
        $bendahara = User::where('nik', '3273021005050004')->first(); // Bendahara RT 05
        $rt = $bendahara->rt;

        // Bersihkan data kas demo untuk isolasi pengujian model murni
        KasTransaksi::withoutGlobalScopes()->delete();

        // 1. Buat transaksi awal (T1)
        $t1 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $rt->id,
            'input_by' => $bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 500000,
            'keterangan' => 'Beli semen 10 sak',
            'tanggal' => '2026-10-01',
            'is_koreksi' => false,
            'koreksi_dari_id' => null,
        ]);

        $this->assertEquals(1, KasTransaksi::withoutGlobalScopes()->aktif()->count());
        $this->assertEquals(500000, KasTransaksi::withoutGlobalScopes()->aktif()->first()->nominal);

        // 2. Buat koreksi pertama (T2) menunjuk ke T1
        $t2 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $rt->id,
            'input_by' => $bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 50000,
            'keterangan' => 'Beli semen 1 sak',
            'tanggal' => '2026-10-01',
            'is_koreksi' => true,
            'koreksi_dari_id' => $t1->id,
            'catatan_koreksi' => 'Salah ketik kelebihan nol, riil beli 1 sak',
        ]);

        // T1 harus tereksklusi dari scopeAktif, hanya T2 yang muncul
        $aktifList = KasTransaksi::withoutGlobalScopes()->aktif()->get();
        $this->assertCount(1, $aktifList);
        $this->assertEquals($t2->id, $aktifList->first()->id);
        $this->assertEquals(50000, $aktifList->first()->nominal);

        // 3. Buat koreksi kedua (T3) menunjuk ke T2
        $t3 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $rt->id,
            'input_by' => $bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional & ATK RT',
            'nominal' => 60000,
            'keterangan' => 'Beli semen 1 sak + ongkir',
            'tanggal' => '2026-10-01',
            'is_koreksi' => true,
            'koreksi_dari_id' => $t2->id,
            'catatan_koreksi' => 'Penyesuaian nota susulan ongkos kirim toko Rp 10.000',
        ]);

        // T1 dan T2 tereksklusi, hanya T3 yang aktif
        $aktifList2 = KasTransaksi::withoutGlobalScopes()->aktif()->get();
        $this->assertCount(1, $aktif2 = $aktifList2);
        $this->assertEquals($t3->id, $aktifList2->first()->id);
        $this->assertEquals(60000, $aktifList2->first()->nominal);

        // Total transaksi fisik di database tetap 3 (tidak ada yang di-hard delete)
        $this->assertEquals(3, KasTransaksi::withoutGlobalScopes()->count());
    }

    /**
     * Test 2: Rantai traversal getRiwayatKoreksi mengembalikan riwayat T1 -> T2 -> T3 secara urut.
     */
    public function test_get_riwayat_koreksi_traversal(): void
    {
        $bendahara = User::where('nik', '3273021005050004')->first();
        $rt = $bendahara->rt;

        KasTransaksi::withoutGlobalScopes()->delete();

        $t1 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $rt->id,
            'input_by' => $bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional',
            'nominal' => 500000,
            'keterangan' => 'T1',
            'tanggal' => '2026-10-01',
        ]);

        $t2 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $rt->id,
            'input_by' => $bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional',
            'nominal' => 50000,
            'keterangan' => 'T2',
            'tanggal' => '2026-10-01',
            'is_koreksi' => true,
            'koreksi_dari_id' => $t1->id,
            'catatan_koreksi' => 'Koreksi pertama',
        ]);

        $t3 = KasTransaksi::withoutGlobalScopes()->create([
            'rt_id' => $rt->id,
            'input_by' => $bendahara->id,
            'jenis' => 'keluar',
            'kategori' => 'Operasional',
            'nominal' => 60000,
            'keterangan' => 'T3',
            'tanggal' => '2026-10-01',
            'is_koreksi' => true,
            'koreksi_dari_id' => $t2->id,
            'catatan_koreksi' => 'Koreksi kedua',
        ]);

        $riwayat = $t3->getRiwayatKoreksi();

        $this->assertCount(3, $riwayat);
        $this->assertEquals($t1->id, $riwayat[0]->id);
        $this->assertEquals($t2->id, $riwayat[1]->id);
        $this->assertEquals($t3->id, $riwayat[2]->id);

        $this->assertEquals(2, $t3->jumlah_koreksi);
        $this->assertEquals(0, $t1->jumlah_koreksi);
    }

    /**
     * Test 3: ScopeAuthorizer::canManageKas menegakkan RBAC ketat.
     */
    public function test_scope_authorizer_can_manage_kas_rbac(): void
    {
        $bendahara = User::where('nik', '3273021005050004')->first();
        $ketuaRt = User::where('nik', '3273021005050001')->first();
        $wakilRt = User::where('nik', '3273021005050002')->first();
        $sekretaris = User::where('nik', '3273021005050003')->first();
        $warga = User::where('nik', '3273021005050010')->first();
        $ketuaRw = User::where('nik', '3273021005030001')->first();
        $superAdmin = User::where('is_super_admin', true)->first();

        $rtId = $ketuaRt->rt_id;

        // Berhak (Write access: Bendahara, Ketua RT, Wakil RT, Super Admin)
        $this->assertTrue(ScopeAuthorizer::canManageKas($bendahara, $rtId));
        $this->assertTrue(ScopeAuthorizer::canManageKas($ketuaRt, $rtId));
        $this->assertTrue(ScopeAuthorizer::canManageKas($wakilRt, $rtId));
        $this->assertTrue(ScopeAuthorizer::canManageKas($superAdmin, $rtId));

        // Tidak berhak (Read-only atau luar wewenang: Warga, Sekretaris, Ketua RW)
        $this->assertFalse(ScopeAuthorizer::canManageKas($warga, $rtId));
        $this->assertFalse(ScopeAuthorizer::canManageKas($sekretaris, $rtId));
        $this->assertFalse(ScopeAuthorizer::canManageKas($ketuaRw, $rtId));

        // Pengurus RT lain dilarang memanipulasi kas RT 05
        $otherRtId = 999;
        $this->assertFalse(ScopeAuthorizer::canManageKas($bendahara, $otherRtId));
    }

    /**
     * Test 4: ScopeAuthorizer::canViewKas mengizinkan Warga RT dan Ketua RW yang membawahi.
     */
    public function test_scope_authorizer_can_view_kas(): void
    {
        $wargaRt5 = User::where('nik', '3273021005050010')->first();
        $ketuaRw = User::where('nik', '3273021005030001')->first(); // RW 15 yang membawahi RT 05
        $superAdmin = User::where('is_super_admin', true)->first();

        $rt5Id = $wargaRt5->rt_id;

        // Warga RT 05 boleh melihat kas RT 05
        $this->assertTrue(ScopeAuthorizer::canViewKas($wargaRt5, $rt5Id));

        // Warga RT 05 dilarang melihat kas RT 999
        $this->assertFalse(ScopeAuthorizer::canViewKas($wargaRt5, 999));

        // Ketua RW boleh melihat kas RT binaannya
        $this->assertTrue(ScopeAuthorizer::canViewKas($ketuaRw, $rt5Id));

        // Super Admin boleh melihat kas mana saja
        $this->assertTrue(ScopeAuthorizer::canViewKas($superAdmin, $rt5Id));
    }
}
