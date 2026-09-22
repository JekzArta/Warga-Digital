<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\ChatMessage;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\KasTransaksi;
use App\Models\Klien;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\SuratKelengkapan;
use App\Models\SuratPengajuan;
use App\Models\UmkmListing;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Klien: Kelurahan Sekeloa (Kode Kemendagri: 32.73.02.1005)
        $klien = Klien::create([
            'kode_wilayah' => '32.73.02.1005',
            'nama' => 'Kelurahan Sekeloa',
            'tenor_lisensi' => '3_tahun',
            'tanggal_mulai' => Carbon::parse('2026-01-01'),
            'tanggal_berakhir' => Carbon::parse('2029-01-01'),
            'status' => 'aktif',
        ]);

        // 2. RW: RW 03
        $rw = Rw::create([
            'klien_id' => $klien->id,
            'kode_rw' => '32.73.02.1005-RW03',
            'nomor_rw' => 3,
            'nama' => 'RW 03 Sekeloa',
        ]);

        // 3. RT: RT 05
        $rt = Rt::create([
            'rw_id' => $rw->id,
            'kode_rt' => '32.73.02.1005-RW03-RT05',
            'nomor_rt' => 5,
            'nama' => 'RT 05 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT05-RW03/SK/{bulan_romawi}/{tahun}',
        ]);

        $defaultPassword = Hash::make('password123');

        // 4. Akun Super Admin
        $superAdmin = User::create([
            'kode_warga' => 'ADM-000001',
            'email' => 'admin@wargadigital.id',
            'nama' => 'Super Administrator',
            'password' => $defaultPassword,
            'status' => 'aktif',
            'is_super_admin' => true,
            'last_login' => now(),
        ]);
        UserRole::create([
            'user_id' => $superAdmin->id,
            'role' => 'super_admin',
            'assigned_at' => now(),
        ]);

        // 5. Akun Ketua RW 03
        $ketuaRw = User::create([
            'kode_warga' => 'WRG-000001',
            'rw_id' => $rw->id,
            'nik' => '3273021005030001',
            'nama' => 'H. Ahmad Supriyadi',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1968-05-12',
            'alamat' => 'Jl. Sekeloa No. 12, RW 03',
            'no_hp' => '081234567801',
            'password' => $defaultPassword,
            'status' => 'aktif',
            'last_login' => now(),
        ]);
        UserRole::create([
            'user_id' => $ketuaRw->id,
            'role' => 'ketua_rw',
            'assigned_at' => now(),
        ]);

        // 6. Akun Ketua RT 05
        $ketuaRt = User::create([
            'kode_warga' => 'WRG-000002',
            'rt_id' => $rt->id,
            'rw_id' => $rw->id,
            'nik' => '3273021005050001',
            'nama' => 'Bambang Hartono',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1975-03-20',
            'alamat' => 'Jl. Sekeloa Girang No. 5, RT 05',
            'no_hp' => '081234567802',
            'password' => $defaultPassword,
            'status' => 'aktif',
            'last_login' => now(),
        ]);
        UserRole::create([
            'user_id' => $ketuaRt->id,
            'role' => 'ketua_rt',
            'assigned_at' => now(),
        ]);

        // 7. Akun Wakil RT 05 (Identik hak akses dengan Ketua RT)
        $wakilRt = User::create([
            'kode_warga' => 'WRG-000003',
            'rt_id' => $rt->id,
            'rw_id' => $rw->id,
            'nik' => '3273021005050002',
            'nama' => 'Dedi Kurniawan',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1980-07-14',
            'alamat' => 'Jl. Sekeloa Tengah No. 8, RT 05',
            'no_hp' => '081234567803',
            'password' => $defaultPassword,
            'status' => 'aktif',
            'last_login' => now(),
        ]);
        UserRole::create([
            'user_id' => $wakilRt->id,
            'role' => 'wakil_rt',
            'assigned_at' => now(),
        ]);

        // 8. Akun Sekretaris RT 05
        $sekretaris = User::create([
            'kode_warga' => 'WRG-000004',
            'rt_id' => $rt->id,
            'rw_id' => $rw->id,
            'nik' => '3273021005050003',
            'nama' => 'Dewi Anggraeni',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '1988-11-22',
            'alamat' => 'Jl. Sekeloa Girang No. 11, RT 05',
            'no_hp' => '081234567804',
            'password' => $defaultPassword,
            'status' => 'aktif',
            'last_login' => now(),
        ]);
        UserRole::create([
            'user_id' => $sekretaris->id,
            'role' => 'sekretaris',
            'assigned_at' => now(),
        ]);

        // 9. Akun Bendahara RT 05
        $bendahara = User::create([
            'kode_warga' => 'WRG-000005',
            'rt_id' => $rt->id,
            'rw_id' => $rw->id,
            'nik' => '3273021005050004',
            'nama' => 'Ratna Sari',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '1982-09-05',
            'alamat' => 'Jl. Sekeloa Girang No. 15, RT 05',
            'no_hp' => '081234567805',
            'password' => $defaultPassword,
            'status' => 'aktif',
            'last_login' => now(),
        ]);
        UserRole::create([
            'user_id' => $bendahara->id,
            'role' => 'bendahara',
            'assigned_at' => now(),
        ]);

        // 10. 50 Warga RT 05 (30 Aktif, 20 Belum Daftar)
        $namaWargaAktif = [
            'Hendra Pratama', 'Agus Setiawan', 'Budi Santoso', 'Slamet Riyadi', 'Eko Prasetyo',
            'Sri Wahyuni', 'Endang Sulastri', 'Nani Suryani', 'Iwan Falsafah', 'Rudi Hermawan',
            'Farhan Maulana', 'Kurniawan Dwi', 'Yusuf Habibi', 'Lukman Hakim', 'Aris Munandar',
            'Tri Susanti', 'Nurul Hidayah', 'Fitri Handayani', 'Indah Permatasari', 'Maya Safitri',
            'Andi Saputra', 'Fajar Ramadhan', 'Galih Pratama', 'Ilham Nurhuda', 'Bayu Anggoro',
            'Mega Utami', 'Rina Marlina', 'Desi Ratnasari', 'Tuti Alawiyah', 'Lestari Handayani'
        ];

        $wargaAktifList = [];
        foreach ($namaWargaAktif as $idx => $nama) {
            $num = str_pad($idx + 10, 4, '0', STR_PAD_LEFT);
            $w = User::create([
                'kode_warga' => "WRG-00{$num}",
                'rt_id' => $rt->id,
                'rw_id' => $rw->id,
                'nik' => "327302100505{$num}",
                'nama' => $nama,
                'jenis_kelamin' => ($idx % 2 == 0) ? 'L' : 'P',
                'tanggal_lahir' => Carbon::parse('1985-01-01')->addDays($idx * 130),
                'alamat' => "Jl. Sekeloa Girang RT 05 No. " . ($idx + 10),
                'no_hp' => "08129876" . str_pad($idx + 10, 4, '0', STR_PAD_LEFT),
                'password' => $defaultPassword,
                'status' => 'aktif',
                'last_login' => now()->subDays(rand(1, 10)),
            ]);
            UserRole::create([
                'user_id' => $w->id,
                'role' => 'warga',
                'assigned_at' => now(),
            ]);
            $wargaAktifList[] = $w;
        }

        // 20 Warga Belum Daftar (Untuk simulasi aktivasi warga baru)
        $namaWargaBelum = [
            'Siti Rahmawati', 'Rian Hidayat', 'Asep Saepuloh', 'Cecep Supriatna', 'Ujang Komarudin',
            'Yayan Sofyan', 'Taufik Hidayat', 'Dadan Ramdani', 'Wawan Gunawan', 'Tata Rustandi',
            'Aan Maryani', 'Enok Jubaedah', 'Kokom Komalasari', 'Neneng Hasanah', 'Ai Rohaeti',
            'Imas Masitoh', 'Elis Lisnawati', 'Iin Indrawati', 'Euis Kartini', 'Popon Suryati'
        ];

        foreach ($namaWargaBelum as $idx => $nama) {
            $num = str_pad($idx + 41, 4, '0', STR_PAD_LEFT);
            $w = User::create([
                'kode_warga' => "WRG-00{$num}",
                'rt_id' => $rt->id,
                'rw_id' => $rw->id,
                'nik' => "327302100505{$num}",
                'nama' => $nama,
                'jenis_kelamin' => ($idx % 2 == 0) ? 'P' : 'L',
                'tanggal_lahir' => ($idx === 0) ? Carbon::parse('1995-08-17') : Carbon::parse('1990-01-01')->addDays($idx * 150),
                'alamat' => "Jl. Sekeloa Girang RT 05 No. " . ($idx + 40),
                'no_hp' => null,
                'password' => null, // Password belum ada sampai warga aktivasi
                'status' => 'belum_daftar',
            ]);
            UserRole::create([
                'user_id' => $w->id,
                'role' => 'warga',
                'assigned_at' => now(),
            ]);
        }

        // 11. Kategori Forum RT & RW
        $katRt = ['Keamanan Lingkungan', 'Kebersihan & Sampah', 'Pembangunan Fasilitas', 'Sosial & Keagamaan', 'Usulan Warga'];
        foreach ($katRt as $i => $namaKat) {
            ForumCategory::create([
                'scope_type' => 'rt',
                'scope_id' => $rt->id,
                'nama' => $namaKat,
                'deskripsi' => "Diskusi seputar {$namaKat} di lingkup RT 05",
                'urutan' => $i + 1,
            ]);
        }

        $katRw = ['Koordinasi Lintas RT', 'Sosialisasi Kelurahan', 'Program Kerja RW 03'];
        foreach ($katRw as $i => $namaKat) {
            ForumCategory::create([
                'scope_type' => 'rw',
                'scope_id' => $rw->id,
                'nama' => $namaKat,
                'deskripsi' => "Pembahasan tingkat RW 03 untuk {$namaKat}",
                'urutan' => $i + 1,
            ]);
        }

        // 12. Announcements (Pengumuman Resmi)
        Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $rt->id,
            'author_id' => $ketuaRt->id,
            'judul' => 'Kerja Bakti Akbar Bersih Lingkungan Menyambut Musim Hujan',
            'konten' => 'Diberitahukan kepada seluruh warga RT 05 untuk menghadiri kerja bakti pembersihan saluran air pada Minggu pagi pukul 07.30 WIB di pos kamling.',
            'tipe' => 'PENTING',
            'is_pinned' => true,
        ]);

        Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $rt->id,
            'author_id' => $sekretaris->id,
            'judul' => 'Jadwal Posyandu Balita dan Cek Kesehatan Lansia',
            'konten' => 'Pelayanan posyandu akan dilaksanakan hari Kamis tanggal 25 di rumah Bu RT. Mohon warga yang memiliki balita hadir tepat waktu.',
            'tipe' => 'INFO',
            'is_pinned' => false,
        ]);

        Announcement::create([
            'scope_type' => 'rw',
            'scope_id' => $rw->id,
            'author_id' => $ketuaRw->id,
            'judul' => 'Pemberitahuan Pemeliharaan Pipa Air Bersih PDAM',
            'konten' => 'Info dari PDAM Kota: akan ada pemadaman aliran air sementara selama 6 jam pada malam Rabu untuk penggantian valve utama.',
            'tipe' => 'MENDESAK',
            'is_pinned' => true,
        ]);

        // 13. Riwayat Transparansi Kas RT (6 Bulan Terakhir)
        $kategoriMasuk = ['Iuran Keamanan Warga', 'Iuran Kebersihan', 'Donasi Warga', 'Kas Awal'];
        $kategoriKeluar = ['Honor Petugas Kebersihan', 'Honor Petugas Jaga', 'Perbaikan Lampu Jalan', 'Konsumsi Kerja Bakti'];

        for ($bulan = 5; $bulan >= 0; $bulan--) {
            $tglBulan = now()->subMonths($bulan)->startOfMonth();

            // Pemasukan iuran
            KasTransaksi::withoutGlobalScopes()->create([
                'rt_id' => $rt->id,
                'input_by' => $bendahara->id,
                'jenis' => 'masuk',
                'kategori' => 'Iuran Warga Bulanan',
                'nominal' => 2500000,
                'keterangan' => "Penerimaan iuran warga bulan " . $tglBulan->translatedFormat('F Y'),
                'tanggal' => $tglBulan->copy()->addDays(5),
            ]);

            // Pengeluaran rutin
            KasTransaksi::withoutGlobalScopes()->create([
                'rt_id' => $rt->id,
                'input_by' => $bendahara->id,
                'jenis' => 'keluar',
                'kategori' => 'Honor Kebersihan & Keamanan',
                'nominal' => 1200000,
                'keterangan' => "Honor petugas kebersihan & pos jaga " . $tglBulan->translatedFormat('F Y'),
                'tanggal' => $tglBulan->copy()->addDays(20),
            ]);
        }

        // 14. Sample Pengajuan Surat (Berbagai Status)
        $wargaPemohon = $wargaAktifList[0];
        SuratPengajuan::withoutGlobalScopes()->create([
            'rt_id' => $rt->id,
            'user_id' => $wargaPemohon->id,
            'jenis_surat' => 'SKD',
            'nomor_surat' => null,
            'form_data' => [
                'keperluan' => 'Pendaftaran BPJS Kesehatan Mandiri',
                'keterangan_tambahan' => 'Telah berdomisili selama 3 tahun',
            ],
            'status' => 'MENUNGGU',
        ]);

        $wargaPemohon2 = $wargaAktifList[1];
        SuratPengajuan::withoutGlobalScopes()->create([
            'rt_id' => $rt->id,
            'user_id' => $wargaPemohon2->id,
            'jenis_surat' => 'SKU',
            'nomor_surat' => '001/RT05-RW03/SK/IX/2026',
            'form_data' => [
                'nama_usaha' => 'Warung Kelontong Berkah Jaya',
                'bidang_usaha' => 'Perdagangan Sembako',
                'alamat_usaha' => 'Jl. Sekeloa Girang No. 12',
            ],
            'status' => 'DISETUJUI',
            'reviewed_by' => $ketuaRt->id,
        ]);

        // 15. Sample Listing UMKM
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $wargaAktifList[2]->id,
            'rt_id' => $rt->id,
            'kategori' => 'barang',
            'nama' => 'Keripik Tempe Renyah Bu Budi',
            'deskripsi' => 'Keripik tempe olahan rumahan khas Bandung, gurih tanpa bahan pengawet. Kemasan 250gr.',
            'harga' => 15000,
            'template_pesan_wa' => 'Halo Bu Budi, saya ingin pesan Keripik Tempe Renyah lewat Warga Digital.',
            'status' => 'DISETUJUI',
            'reviewed_by' => $sekretaris->id,
        ]);

        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => $wargaAktifList[3]->id,
            'rt_id' => $rt->id,
            'kategori' => 'jasa',
            'nama' => 'Jasa Servis & Cuci AC Pak Slamet',
            'deskripsi' => 'Melayani servis berkala, cuci AC, dan isi freon rumah tangga. Berpengalaman 10 tahun.',
            'harga' => null,
            'template_pesan_wa' => null,
            'status' => 'MENUNGGU',
        ]);
    }
}
