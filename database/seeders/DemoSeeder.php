<?php

namespace Database\Seeders;

use App\Constants\AuditAction;
use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\GaleriAlbum;
use App\Models\GaleriFoto;
use App\Models\KalenderEvent;
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
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Carbon::setLocale('id');

        // 1. Klien: Kelurahan Sekeloa (Kode Kemendagri: 32.73.02.1005)
        $klien = Klien::firstOrCreate(
            ['kode_wilayah' => '32.73.02.1005'],
            [
                'nama' => 'Kelurahan Sekeloa',
                'kecamatan' => 'Coblong',
                'kota' => 'Bandung',
                'tenor_lisensi' => '3_tahun',
                'tanggal_mulai' => Carbon::parse('2026-01-01'),
                'tanggal_berakhir' => Carbon::parse('2029-01-01'),
                'status' => 'aktif',
            ]
        );

        // 2. RW: RW 03
        $rw = Rw::firstOrCreate(
            ['kode_rw' => '32.73.02.1005-RW03'],
            [
                'klien_id' => $klien->id,
                'nomor_rw' => 3,
                'nama' => 'RW 03 Sekeloa',
            ]
        );

        // 3. RT: RT 05
        $rt = Rt::firstOrCreate(
            ['kode_rt' => '32.73.02.1005-RW03-RT05'],
            [
                'rw_id' => $rw->id,
                'nomor_rt' => 5,
                'nama' => 'RT 05 Sekeloa',
                'format_nomor_surat' => '{nomor}/RT05-RW03/SK/{bulan_romawi}/{tahun}',
            ]
        );

        $defaultPassword = Hash::make('password123');

        // 4. Akun Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@wargadigital.id'],
            [
                'kode_warga' => 'ADM-000001',
                'nama' => 'Super Administrator',
                'password' => $defaultPassword,
                'status' => 'aktif',
                'is_super_admin' => true,
                'last_login' => now(),
            ]
        );
        UserRole::firstOrCreate([
            'user_id' => $superAdmin->id,
            'role' => 'super_admin',
        ], [
            'assigned_at' => now(),
        ]);

        // 5. Akun Ketua RW 03
        $ketuaRw = User::firstOrCreate(
            ['nik' => '3273021005030001'],
            [
                'kode_warga' => 'WRG-000001',
                'rw_id' => $rw->id,
                'nama' => 'H. Ahmad Supriyadi',
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '1968-05-12',
                'alamat' => 'Jl. Sekeloa No. 12, RW 03',
                'no_hp' => '081234567801',
                'password' => $defaultPassword,
                'status' => 'aktif',
                'last_login' => now(),
            ]
        );
        UserRole::firstOrCreate([
            'user_id' => $ketuaRw->id,
            'role' => 'ketua_rw',
        ], [
            'assigned_at' => now(),
        ]);

        // 6. Akun Ketua RT 05
        $ketuaRt = User::firstOrCreate(
            ['nik' => '3273021005050001'],
            [
                'kode_warga' => 'WRG-000002',
                'rt_id' => $rt->id,
                'rw_id' => $rw->id,
                'nama' => 'Bambang Hartono',
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '1975-03-20',
                'alamat' => 'Jl. Sekeloa Girang No. 5, RT 05',
                'no_hp' => '081234567802',
                'password' => $defaultPassword,
                'status' => 'aktif',
                'last_login' => now(),
            ]
        );
        UserRole::firstOrCreate([
            'user_id' => $ketuaRt->id,
            'role' => 'ketua_rt',
        ], [
            'assigned_at' => now(),
        ]);

        // 7. Akun Wakil RT 05 (Identik hak akses dengan Ketua RT)
        $wakilRt = User::firstOrCreate(
            ['nik' => '3273021005050002'],
            [
                'kode_warga' => 'WRG-000003',
                'rt_id' => $rt->id,
                'rw_id' => $rw->id,
                'nama' => 'Dedi Kurniawan',
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '1980-07-14',
                'alamat' => 'Jl. Sekeloa Tengah No. 8, RT 05',
                'no_hp' => '081234567803',
                'password' => $defaultPassword,
                'status' => 'aktif',
                'last_login' => now(),
            ]
        );
        UserRole::firstOrCreate([
            'user_id' => $wakilRt->id,
            'role' => 'wakil_rt',
        ], [
            'assigned_at' => now(),
        ]);

        // 8. Akun Sekretaris RT 05
        $sekretaris = User::firstOrCreate(
            ['nik' => '3273021005050003'],
            [
                'kode_warga' => 'WRG-000004',
                'rt_id' => $rt->id,
                'rw_id' => $rw->id,
                'nama' => 'Dewi Anggraeni',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '1988-11-22',
                'alamat' => 'Jl. Sekeloa Girang No. 11, RT 05',
                'no_hp' => '081234567804',
                'password' => $defaultPassword,
                'status' => 'aktif',
                'last_login' => now(),
            ]
        );
        UserRole::firstOrCreate([
            'user_id' => $sekretaris->id,
            'role' => 'sekretaris',
        ], [
            'assigned_at' => now(),
        ]);

        // 9. Akun Bendahara RT 05
        $bendahara = User::firstOrCreate(
            ['nik' => '3273021005050004'],
            [
                'kode_warga' => 'WRG-000005',
                'rt_id' => $rt->id,
                'rw_id' => $rw->id,
                'nama' => 'Ratna Sari',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '1982-09-05',
                'alamat' => 'Jl. Sekeloa Girang No. 15, RT 05',
                'no_hp' => '081234567805',
                'password' => $defaultPassword,
                'status' => 'aktif',
                'last_login' => now(),
            ]
        );
        UserRole::firstOrCreate([
            'user_id' => $bendahara->id,
            'role' => 'bendahara',
        ], [
            'assigned_at' => now(),
        ]);

        // 10. 30 Warga Aktif RT 05 (WRG-000006 s/d WRG-000035)
        // WRG-000006: Hendra Pratama (No HP Zaky: 082117081871)
        $namaWargaAktif = [
            'Hendra Pratama', // WRG-000006
            'Agus Setiawan',  // WRG-000007
            'Budi Santoso',   // WRG-000008
            'Slamet Riyadi',  // WRG-000009
            'Eko Prasetyo',   // WRG-000010
            'Sri Wahyuni', 'Endang Sulastri', 'Nani Suryani', 'Iwan Falsafah', 'Rudi Hermawan',
            'Farhan Maulana', 'Kurniawan Dwi', 'Yusuf Habibi', 'Lukman Hakim', 'Aris Munandar',
            'Tri Susanti', 'Nurul Hidayah', 'Fitri Handayani', 'Indah Permatasari', 'Maya Safitri',
            'Andi Saputra', 'Fajar Ramadhan', 'Galih Pratama', 'Ilham Nurhuda', 'Bayu Anggoro',
            'Mega Utami', 'Rina Marlina', 'Desi Ratnasari', 'Tuti Alawiyah', 'Lestari Handayani'
        ];

        $wargaAktifList = [];
        foreach ($namaWargaAktif as $idx => $nama) {
            $nomorWarga = $idx + 6;
            $kodeFormatted = 'WRG-' . str_pad($nomorWarga, 6, '0', STR_PAD_LEFT);
            $nikFormatted = '327302100505' . str_pad($nomorWarga, 4, '0', STR_PAD_LEFT);

            // Warga pertama (Hendra Pratama) dipasangkan nomor WhatsApp aktif demo Zaky
            $noHp = ($idx === 0) ? '082117081871' : ('08129876' . str_pad($nomorWarga, 4, '0', STR_PAD_LEFT));

            $w = User::firstOrCreate(
                ['nik' => $nikFormatted],
                [
                    'kode_warga' => $kodeFormatted,
                    'rt_id' => $rt->id,
                    'rw_id' => $rw->id,
                    'nama' => $nama,
                    'jenis_kelamin' => ($idx % 2 == 0) ? 'L' : 'P',
                    'tanggal_lahir' => Carbon::parse('1985-01-01')->addDays($idx * 130),
                    'alamat' => "Jl. Sekeloa Girang RT 05 No. " . ($idx + 10),
                    'no_hp' => $noHp,
                    'password' => $defaultPassword,
                    'status' => 'aktif',
                    'last_login' => now()->subDays(rand(1, 5)),
                ]
            );
            UserRole::firstOrCreate([
                'user_id' => $w->id,
                'role' => 'warga',
            ], [
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
            $nomorWarga = $idx + 36;
            $kodeFormatted = 'WRG-' . str_pad($nomorWarga, 6, '0', STR_PAD_LEFT);
            $nikFormatted = '327302100505' . str_pad($nomorWarga, 4, '0', STR_PAD_LEFT);

            $w = User::firstOrCreate(
                ['nik' => $nikFormatted],
                [
                    'kode_warga' => $kodeFormatted,
                    'rt_id' => $rt->id,
                    'rw_id' => $rw->id,
                    'nama' => $nama,
                    'jenis_kelamin' => ($idx % 2 == 0) ? 'P' : 'L',
                    'tanggal_lahir' => ($idx === 0) ? Carbon::parse('1995-08-17') : Carbon::parse('1990-01-01')->addDays($idx * 150),
                    'alamat' => "Jl. Sekeloa Girang RT 05 No. " . ($idx + 40),
                    'no_hp' => null,
                    'password' => null,
                    'status' => 'belum_daftar',
                ]
            );
            UserRole::firstOrCreate([
                'user_id' => $w->id,
                'role' => 'warga',
            ], [
                'assigned_at' => now(),
            ]);
        }

        // 11. Kategori Forum RT & RW
        $katRt = ['Keamanan Lingkungan', 'Kebersihan & Sampah', 'Pembangunan Fasilitas', 'Sosial & Keagamaan', 'Usulan Warga'];
        $kategoriRtModels = [];
        foreach ($katRt as $i => $namaKat) {
            $kategoriRtModels[$namaKat] = ForumCategory::firstOrCreate(
                [
                    'scope_type' => 'rt',
                    'scope_id' => $rt->id,
                    'nama' => $namaKat,
                ],
                [
                    'deskripsi' => "Diskusi seputar {$namaKat} di lingkup RT 05",
                    'urutan' => $i + 1,
                ]
            );
        }

        $katRw = ['Koordinasi Lintas RT', 'Sosialisasi Kelurahan', 'Program Kerja RW 03'];
        $kategoriRwModels = [];
        foreach ($katRw as $i => $namaKat) {
            $kategoriRwModels[$namaKat] = ForumCategory::firstOrCreate(
                [
                    'scope_type' => 'rw',
                    'scope_id' => $rw->id,
                    'nama' => $namaKat,
                ],
                [
                    'deskripsi' => "Pembahasan tingkat RW 03 untuk {$namaKat}",
                    'urutan' => $i + 1,
                ]
            );
        }

        // 12. Forum Threads & Posts Realistis
        $thread1 = ForumThread::firstOrCreate(
            [
                'category_id' => $kategoriRtModels['Kebersihan & Sampah']->id,
                'judul' => 'Jadwal Pengangkutan Sampah Daur Ulang & Bank Sampah RT 05',
            ],
            [
                'author_id' => $wargaAktifList[0]->id, // Hendra Pratama
                'konten' => 'Selamat sore bapak/ibu pengurus, mau tanya apakah jadwal penjemputan sampah anorganik untuk bank sampah masih setiap hari Sabtu pagi? Mohon infonya.',
                'status' => 'aktif',
                'is_pinned' => true,
                'author_role_snapshot' => $wargaAktifList[0]->getRoleSnapshot(),
            ]
        );

        ForumPost::firstOrCreate(
            [
                'thread_id' => $thread1->id,
                'author_id' => $sekretaris->id,
            ],
            [
                'konten' => 'Sore Pak Hendra. Betul pak, penjemputan bank sampah tetap setiap Sabtu pukul 08.00 - 10.00 WIB di pos kamling RT. Warga bisa mulai mengumpulkan botol dan kardus terpilah.',
                'author_role_snapshot' => $sekretaris->getRoleSnapshot(),
            ]
        );

        ForumPost::firstOrCreate(
            [
                'thread_id' => $thread1->id,
                'author_id' => $ketuaRt->id,
            ],
            [
                'konten' => 'Terima kasih Bu Dewi atas konfirmasinya. Mari bapak ibu kita dukung program pilah sampah dari rumah demi kebersihan lingkungan RT 05.',
                'author_role_snapshot' => $ketuaRt->getRoleSnapshot(),
            ]
        );

        $thread2 = ForumThread::firstOrCreate(
            [
                'category_id' => $kategoriRtModels['Keamanan Lingkungan']->id,
                'judul' => 'Usulan Penambahan Titik CCTV di Gang Belakang',
            ],
            [
                'author_id' => $wargaAktifList[2]->id, // Budi Santoso
                'konten' => 'Izin mengusulkan Pak RT, mengingat penerangan di gang belakang agak minim, apakah memungkinkan ada penambahan 1 titik CCTV untuk keamanan bersama?',
                'status' => 'aktif',
                'is_pinned' => false,
                'author_role_snapshot' => $wargaAktifList[2]->getRoleSnapshot(),
            ]
        );

        ForumPost::firstOrCreate(
            [
                'thread_id' => $thread2->id,
                'author_id' => $wakilRt->id,
            ],
            [
                'konten' => 'Usulan yang sangat baik Pak Budi. Nanti akan kita agendakan untuk dibahas bersama Bendahara dan warga pada rapat RT terdekat.',
                'author_role_snapshot' => $wakilRt->getRoleSnapshot(),
            ]
        );

        // 13. Pengumuman Resmi (Announcements)
        Announcement::firstOrCreate(
            [
                'scope_type' => 'rt',
                'scope_id' => $rt->id,
                'judul' => 'Kerja Bakti Akbar Bersih Lingkungan Menyambut Musim Hujan',
            ],
            [
                'author_id' => $ketuaRt->id,
                'konten' => 'Diberitahukan kepada seluruh warga RT 05 untuk menghadiri kerja bakti pembersihan saluran drainase utama pada Minggu pagi pukul 07.30 WIB di pos kamling. Mohon membawa perlengkapan masing-masing.',
                'tipe' => 'PENTING',
                'is_pinned' => true,
            ]
        );

        Announcement::firstOrCreate(
            [
                'scope_type' => 'rt',
                'scope_id' => $rt->id,
                'judul' => 'Jadwal Pelayanan Posyandu Balita dan Cek Kesehatan Lansia',
            ],
            [
                'author_id' => $sekretaris->id,
                'konten' => 'Pelayanan posyandu Melati akan dilaksanakan hari Kamis tanggal 15 di Balai Warga RT 05 mulai pukul 08.30 WIB. Mohon warga yang memiliki balita dan lansia hadir tepat waktu.',
                'tipe' => 'INFO',
                'is_pinned' => false,
            ]
        );

        Announcement::firstOrCreate(
            [
                'scope_type' => 'rw',
                'scope_id' => $rw->id,
                'judul' => 'Pemberitahuan Pemeliharaan Pipa Air Bersih PDAM',
            ],
            [
                'author_id' => $ketuaRw->id,
                'konten' => 'Info dari PDAM Kota Bandung: akan ada pemadaman aliran air sementara selama 6 jam pada malam Rabu untuk penggantian valve utama di jalur Sekeloa.',
                'tipe' => 'MENDESAK',
                'is_pinned' => true,
            ]
        );

        // 14. Chat Bebas Real-time (Lingkup RT 05 & RW 03)
        $chatDataRt = [
            [
                'author' => $wargaAktifList[0], // Hendra Pratama
                'konten' => 'Selamat pagi bapak-bapak dan ibu-ibu sekalian. Mau tanya untuk kerja bakti hari Minggu nanti kumpulnya di pos kamling jam berapa ya?',
                'created_at' => now()->subHours(3),
            ],
            [
                'author' => $sekretaris, // Dewi Anggraeni
                'konten' => 'Pagi Pak Hendra. Sesuai pengumuman resmi dan kalender agenda, kumpul jam 07.30 WIB ya pak, membawa cangkul atau sapu lidi masing-masing.',
                'created_at' => now()->subHours(2)->subMinutes(50),
            ],
            [
                'author' => $ketuaRt, // Bambang Hartono
                'konten' => 'Betul bapak/ibu, nanti setelah pembersihan selokan utama ada sarapan lontong sayur bareng di pos kamling RT. Ditunggu kehadirannya ya.',
                'created_at' => now()->subHours(2)->subMinutes(30),
            ],
            [
                'author' => $wargaAktifList[2], // Budi Santoso
                'konten' => 'Siap Pak RT, saya dan pemuda gang 2 hadir tepat waktu!',
                'created_at' => now()->subHours(2)->subMinutes(10),
            ],
            [
                'author' => $wargaAktifList[0], // Hendra Pratama
                'konten' => 'Mantap Pak RT, saya nanti bantu bawa gerobak dorong untuk angkut sampah rumput dan tanah selokan.',
                'created_at' => now()->subHour(),
            ],
        ];

        if (! app()->environment('testing')) {
            foreach ($chatDataRt as $chat) {
                ChatMessage::firstOrCreate(
                    [
                        'scope_type' => 'rt',
                        'scope_id' => $rt->id,
                        'author_id' => $chat['author']->id,
                        'konten' => $chat['konten'],
                    ],
                    [
                        'created_at' => $chat['created_at'],
                        'updated_at' => $chat['created_at'],
                    ]
                );
            }

            $chatDataRw = [
                [
                    'author' => $ketuaRt, // Bambang Hartono (Ketua RT 05)
                    'konten' => "Assalamu'alaikum Pak RW dan rekan-rekan Ketua RT, laporan dari RT 05 Sekeloa situasi lingkungan aman dan kondusif.",
                    'created_at' => now()->subHours(5),
                ],
                [
                    'author' => $ketuaRw, // H. Ahmad Supriyadi (Ketua RW 03)
                    'konten' => "Wa'alaikumsalam Pak Bambang. Terima kasih laporannya. Terkait pemadaman PDAM malam Rabu mohon diingatkan kembali ke warga ya.",
                    'created_at' => now()->subHours(4),
                ],
            ];

            foreach ($chatDataRw as $chat) {
                ChatMessage::firstOrCreate(
                    [
                        'scope_type' => 'rw',
                        'scope_id' => $rw->id,
                        'author_id' => $chat['author']->id,
                        'konten' => $chat['konten'],
                    ],
                    [
                        'created_at' => $chat['created_at'],
                        'updated_at' => $chat['created_at'],
                    ]
                );
            }
        }

        // 15. Kalender Kegiatan Lingkungan (Sinkron & Terstruktur)
        KalenderEvent::firstOrCreate(
            [
                'scope_type' => 'rt',
                'scope_id' => $rt->id,
                'judul' => 'Kerja Bakti Akbar Bersih Lingkungan RT 05',
            ],
            [
                'deskripsi' => 'Gotong royong warga membersihkan selokan utama dan merapikan tanaman liar menyambut musim hujan.',
                'tanggal' => Carbon::now()->addDays(4)->format('Y-m-d'),
                'waktu_mulai' => '07:30',
                'waktu_selesai' => '11:00',
                'lokasi' => 'Pos Kamling RT 05 Sekeloa',
                'kategori' => KalenderEvent::KATEGORI_KEGIATAN,
                'sumber' => KalenderEvent::SUMBER_MANUAL,
                'is_cancelled' => false,
                'created_by' => $ketuaRt->id,
            ]
        );

        KalenderEvent::firstOrCreate(
            [
                'scope_type' => 'rt',
                'scope_id' => $rt->id,
                'judul' => 'Pelayanan Posyandu Balita & Lansia Melati',
            ],
            [
                'deskripsi' => 'Pemeriksaan rutin tumbuh kembang balita, imunisasi dasar, dan cek tekanan darah lansia.',
                'tanggal' => Carbon::now()->addDays(8)->format('Y-m-d'),
                'waktu_mulai' => '08:30',
                'waktu_selesai' => '11:30',
                'lokasi' => 'Balai Pertemuan RT 05',
                'kategori' => KalenderEvent::KATEGORI_POSYANDU,
                'sumber' => KalenderEvent::SUMBER_MANUAL,
                'is_cancelled' => false,
                'created_by' => $sekretaris->id,
            ]
        );

        KalenderEvent::firstOrCreate(
            [
                'scope_type' => 'rw',
                'scope_id' => $rw->id,
                'judul' => 'Rapat Koordinasi Pengurus RT & RW 03',
            ],
            [
                'deskripsi' => 'Evaluasi program kebersihan lingkungan dan pemantapan keamanan wilayah se-RW 03.',
                'tanggal' => Carbon::now()->addDays(13)->format('Y-m-d'),
                'waktu_mulai' => '19:30',
                'waktu_selesai' => '21:30',
                'lokasi' => 'Balai RW 03 Sekeloa',
                'kategori' => KalenderEvent::KATEGORI_RAPAT,
                'sumber' => KalenderEvent::SUMBER_MANUAL,
                'is_cancelled' => false,
                'created_by' => $ketuaRw->id,
            ]
        );

        // 16. Transparansi Anggaran Kas RT (6 Bulan + 1 Koreksi Forensik Append-Only)
        for ($bulan = 5; $bulan >= 0; $bulan--) {
            $tglBulan = now()->subMonths($bulan)->startOfMonth();

            // Pemasukan iuran warga bulanan
            KasTransaksi::withoutGlobalScopes()->firstOrCreate(
                [
                    'rt_id' => $rt->id,
                    'tanggal' => $tglBulan->copy()->addDays(5)->format('Y-m-d'),
                    'jenis' => 'masuk',
                ],
                [
                    'input_by' => $bendahara->id,
                    'kategori' => 'Iuran Warga Bulanan',
                    'nominal' => 2500000,
                    'keterangan' => "Penerimaan iuran warga bulan " . $tglBulan->translatedFormat('F Y'),
                    'is_koreksi' => false,
                ]
            );

            // Pengeluaran honor kebersihan & keamanan
            KasTransaksi::withoutGlobalScopes()->firstOrCreate(
                [
                    'rt_id' => $rt->id,
                    'tanggal' => $tglBulan->copy()->addDays(20)->format('Y-m-d'),
                    'jenis' => 'keluar',
                ],
                [
                    'input_by' => $bendahara->id,
                    'kategori' => 'Honor Kebersihan & Keamanan',
                    'nominal' => 1200000,
                    'keterangan' => "Honor petugas kebersihan & pos jaga " . $tglBulan->translatedFormat('F Y'),
                    'is_koreksi' => false,
                ]
            );
        }

        // Transaksi belanja lampu yang dikoreksi (Demo Anti-Fraud Kas)
        $kasLama = KasTransaksi::withoutGlobalScopes()->firstOrCreate(
            [
                'rt_id' => $rt->id,
                'keterangan' => 'Pembelian 5 unit lampu LED penerangan gang RT 05',
            ],
            [
                'input_by' => $bendahara->id,
                'jenis' => 'keluar',
                'kategori' => 'Perbaikan Sarana & Fasilitas Lingkungan',
                'nominal' => 350000,
                'tanggal' => now()->subDays(6),
                'is_koreksi' => false,
            ]
        );

        KasTransaksi::withoutGlobalScopes()->firstOrCreate(
            [
                'rt_id' => $rt->id,
                'koreksi_dari_id' => $kasLama->id,
            ],
            [
                'input_by' => $bendahara->id,
                'jenis' => 'keluar',
                'kategori' => 'Perbaikan Sarana & Fasilitas Lingkungan',
                'nominal' => 425000,
                'keterangan' => 'Pembelian 5 unit lampu LED penerangan gang RT 05 & kabel outdoor 20m (Revisi Faktur)',
                'tanggal' => now()->subDays(5),
                'is_koreksi' => true,
                'catatan_koreksi' => 'Penyesuaian faktur toko listrik: ada tambahan kabel roll outdoor dan fiting gantung yang tercantum di struk susulan.',
            ]
        );

        // 17. Skenario Pengajuan Surat (Resmi Siap Demo Walkthrough)
        // SURAT 1: SKU (Surat Keterangan Usaha) — STATUS: DISETUJUI (Siap Download PDF & Verifikasi Publik)
        $wargaPemohonSku = $wargaAktifList[0]; // Hendra Pratama (No HP: 082117081871)
        $suratDisetujui = SuratPengajuan::withoutGlobalScopes()->firstOrCreate(
            [
                'rt_id' => $rt->id,
                'nomor_surat' => '001/RT05-RW03/SK/X/2026',
            ],
            [
                'user_id' => $wargaPemohonSku->id,
                'jenis_surat' => 'SKU',
                'kode_verifikasi' => 'WD-2026-SKU-81871',
                'form_data' => [
                    'nama_usaha' => 'Warung Berkah Hendra',
                    'bidang_usaha' => 'Perdagangan & Kuliner Olahan',
                    'alamat_usaha' => 'Jl. Sekeloa Girang RT 05 No. 10',
                    'keperluan' => 'Pengajuan Sertifikasi Halal & Legalitas Bantuan Modal Usaha Mikro (BPUM)',
                    'lampiran' => [
                        'dokumen_ktp' => [
                            [
                                'path' => 'surat_dokumen/ktp_sample.jpg',
                                'nama' => 'KTP_Hendra_Pratama.jpg',
                                'label' => 'KTP Asli',
                                'size' => 102400,
                            ],
                        ],
                        'dokumen_usaha' => [
                            [
                                'path' => 'surat_dokumen/tempat_usaha.jpg',
                                'nama' => 'Foto_Tempat_Usaha.jpg',
                                'label' => 'Foto Tempat Usaha',
                                'size' => 153600,
                            ],
                        ],
                    ],
                ],
                'status' => 'DISETUJUI',
                'reviewed_by' => $ketuaRt->id,
            ]
        );

        // SURAT 2: SKD (Surat Keterangan Domisili) — STATUS: MENUNGGU (Siap Di-Approve Live di Depan Juri)
        $wargaPemohonSkd = $wargaAktifList[1]; // Agus Setiawan
        SuratPengajuan::withoutGlobalScopes()->firstOrCreate(
            [
                'rt_id' => $rt->id,
                'user_id' => $wargaPemohonSkd->id,
                'jenis_surat' => 'SKD',
                'status' => 'MENUNGGU',
            ],
            [
                'nomor_surat' => null,
                'kode_verifikasi' => null,
                'form_data' => [
                    'keperluan' => 'Persyaratan Pembukaan Rekening Bank & Administrasi Domisili Tempat Tinggal',
                    'alamat_domisili' => 'Jl. Sekeloa Girang RT 05 No. 11',
                    'lampiran' => [
                        'dokumen_ktp_kk' => [
                            [
                                'path' => 'surat_dokumen/ktp_sample.jpg',
                                'nama' => 'KTP_Agus_Setiawan.jpg',
                                'label' => 'KTP Asli / Kartu Keluarga',
                                'size' => 102400,
                            ],
                        ],
                    ],
                ],
                'reviewed_by' => null,
            ]
        );

        // 18. Sample Listing UMKM (Foto Realistis + WhatsApp Riil ke Nomor Zaky)
        $wargaUmkm = $wargaAktifList[0]; // Hendra Pratama (HP: 082117081871)

        $umkm1 = UmkmListing::withoutGlobalScopes()->firstOrCreate(
            [
                'user_id' => $wargaUmkm->id,
                'nama' => 'Keripik Tempe Bandung Asli & Renyah',
            ],
            [
                'rt_id' => $rt->id,
                'kategori' => 'barang',
                'deskripsi' => 'Keripik tempe kedelai murni pilihan khas Bandung, gurih renyah tanpa bahan pengawet. Kemasan kedap udara 100gr.',
                'harga' => 15000,
                'foto_url' => 'umkm/keripik_tempe.jpg',
                'template_pesan_wa' => 'Halo Mas Hendra, saya tertarik membeli "Keripik Tempe Bandung Asli & Renyah" seharga Rp 15.000 lewat platform Warga Digital. Apakah stok masih tersedia?',
                'status' => 'DISETUJUI',
                'reviewed_by' => $sekretaris->id,
            ]
        );

        UmkmListing::withoutGlobalScopes()->firstOrCreate(
            [
                'user_id' => $wargaUmkm->id,
                'nama' => 'Nasi Liwet Komplit Daun Pisang',
            ],
            [
                'rt_id' => $rt->id,
                'kategori' => 'barang',
                'deskripsi' => 'Nasi liwet wangi rempah Sunda dengan lauk ayam goreng lengkuas empuk, tahu tempe goreng, lalapan segar, petai, dan sambal terasi dadak pedas nikmat.',
                'harga' => 25000,
                'foto_url' => 'umkm/nasi_liwet.jpg',
                'template_pesan_wa' => 'Halo Bu Hendra, mau pesan "Nasi Liwet Komplit Daun Pisang" untuk makan siang/acara keluarga via Warga Digital. Apakah bisa dipesan hari ini?',
                'status' => 'DISETUJUI',
                'reviewed_by' => $ketuaRt->id,
            ]
        );

        UmkmListing::withoutGlobalScopes()->firstOrCreate(
            [
                'user_id' => $wargaUmkm->id,
                'nama' => 'Jasa Cuci & Servis AC Rumah Tangga',
            ],
            [
                'rt_id' => $rt->id,
                'kategori' => 'jasa',
                'deskripsi' => 'Layanan servis rutin, cuci AC bersih bergaransi, dan isi freon indoor/outdoor. Teknisi jujur, teliti, dan berpengalaman 10 tahun.',
                'harga' => null,
                'foto_url' => 'umkm/servis_ac.jpg',
                'template_pesan_wa' => 'Halo Pak Hendra, saya mau pesan "Jasa Cuci & Servis AC" untuk rumah di RT 05. Boleh info jadwal kunjungan dan estimasi biayanya?',
                'status' => 'DISETUJUI',
                'reviewed_by' => $ketuaRt->id,
            ]
        );

        // Listing 4: MENUNGGU KURASI (Untuk mendemokan Meja Kurasi Pengurus RT)
        UmkmListing::withoutGlobalScopes()->firstOrCreate(
            [
                'user_id' => $wargaAktifList[2]->id, // Budi Santoso
                'nama' => 'Kerajinan Anyaman Bambu Kreatif Sekeloa',
            ],
            [
                'rt_id' => $rt->id,
                'kategori' => 'barang',
                'deskripsi' => 'Keranjang belanja ramah lingkungan dan kap lampu artistik dari bambu tali alami karya warga RT 05 Sekeloa.',
                'harga' => 45000,
                'foto_url' => 'umkm/anyaman_bambu.jpg',
                'template_pesan_wa' => 'Halo Pak Budi, saya tertarik dengan kerajinan anyaman bambu di Warga Digital. Boleh lihat pilihan modelnya?',
                'status' => 'MENUNGGU',
                'reviewed_by' => null,
            ]
        );

        // 19. Sample Galeri Kegiatan Warga (RT 05 Sekeloa)
        $this->seedGaleri($rt, $ketuaRt);

        // 20. Audit Trail Awal (Riwayat Akuntabilitas Pengurus)
        $this->seedAuditLogs($rt, $rw, $ketuaRt, $sekretaris, $bendahara, $suratDisetujui, $umkm1, $kasLama);
    }

    /**
     * Seed data Galeri Kegiatan Warga (RT 05 Sekeloa) secara terstruktur.
     */
    public function seedGaleri(Rt $rt, User $ketuaRt): void
    {
        $demoAlbums = [
            [
                'id_ref' => 1,
                'judul' => 'Kerja Bakti Lingkungan & Penataan Drainase',
                'deskripsi' => 'Gotong royong warga RT 05 membersihkan saluran drainase utama dan perapihan tanaman pinggir jalan mengantisipasi musim hujan.',
                'tanggal_kegiatan' => Carbon::parse('2026-10-04'),
                'foto_file' => 'galeri/1/demo_1_1.jpg',
            ],
            [
                'id_ref' => 2,
                'judul' => 'Peringatan HUT RI & Lomba Warga RT 05',
                'deskripsi' => 'Semarak kemerdekaan Republik Indonesia dengan berbagai perlombaan anak-anak, ibu-ibu, dan malam pentas seni warga RT 05.',
                'tanggal_kegiatan' => Carbon::parse('2026-08-17'),
                'foto_file' => 'galeri/2/demo_2_1.jpg',
            ],
            [
                'id_ref' => 3,
                'judul' => 'Kajian Silaturahmi & Buka Puasa Bersama',
                'deskripsi' => 'Kegiatan silaturahmi warga, tausiyah keagamaan, serta buka puasa bersama seluruh warga RT 05 bertempat di balai warga.',
                'tanggal_kegiatan' => Carbon::parse('2026-03-22'),
                'foto_file' => 'galeri/3/demo_3_1.jpg',
            ],
            [
                'id_ref' => 4,
                'judul' => 'Pelayanan Posyandu Balita & Lansia Melati',
                'deskripsi' => 'Pemeriksaan tumbuh kembang balita, imunisasi rutin, dan cek tekanan darah berkala untuk warga lansia RT 05 Sekeloa.',
                'tanggal_kegiatan' => Carbon::parse('2026-09-15'),
                'foto_file' => 'galeri/4/demo_4_1.jpg',
            ],
        ];

        foreach ($demoAlbums as $albumData) {
            $album = GaleriAlbum::withoutGlobalScopes()->firstOrCreate(
                [
                    'rt_id' => $rt->id,
                    'judul' => $albumData['judul'],
                ],
                [
                    'deskripsi' => $albumData['deskripsi'],
                    'tanggal_kegiatan' => $albumData['tanggal_kegiatan'],
                    'created_by' => $ketuaRt->id,
                ]
            );

            GaleriFoto::firstOrCreate(
                [
                    'album_id' => $album->id,
                    'foto_url' => $albumData['foto_file'],
                ],
                [
                    'uploaded_by' => $ketuaRt->id,
                ]
            );
        }
    }

    /**
     * Seed log audit realistis awal untuk Meja Audit Pengurus.
     */
    public function seedAuditLogs(Rt $rt, Rw $rw, User $ketuaRt, User $sekretaris, User $bendahara, SuratPengajuan $surat, UmkmListing $umkm, KasTransaksi $kas): void
    {
        // 1. Audit persetujuan surat SKU
        AuditLog::firstOrCreate(
            [
                'target_type' => 'surat_pengajuan',
                'target_id' => $surat->id,
                'aksi' => AuditAction::SURAT_APPROVED,
            ],
            [
                'rt_id' => $rt->id,
                'rw_id' => $rw->id,
                'user_id' => $ketuaRt->id,
                'actor_nama' => $ketuaRt->nama,
                'actor_role' => 'ketua_rt',
                'ip_address' => '127.0.0.1',
                'sebelum' => ['status' => 'MENUNGGU'],
                'sesudah' => ['status' => 'DISETUJUI', 'nomor_surat' => $surat->nomor_surat],
                'alasan' => 'Berkas KTP dan foto tempat usaha telah diverifikasi lengkap dan valid.',
                'alasan_publik' => 'Berkas KTP dan foto tempat usaha telah diverifikasi lengkap dan valid.',
                'created_at' => now()->subDays(1),
            ]
        );

        // 2. Audit kurasi listing UMKM
        AuditLog::firstOrCreate(
            [
                'target_type' => 'umkm_listing',
                'target_id' => $umkm->id,
                'aksi' => AuditAction::UMKM_LISTING_APPROVED,
            ],
            [
                'rt_id' => $rt->id,
                'rw_id' => $rw->id,
                'user_id' => $sekretaris->id,
                'actor_nama' => $sekretaris->nama,
                'actor_role' => 'sekretaris',
                'ip_address' => '127.0.0.1',
                'sebelum' => ['status' => 'MENUNGGU'],
                'sesudah' => ['status' => 'DISETUJUI'],
                'alasan' => 'Produk kuliner warga RT 05 memenuhi standar kebersihan dan informasi harga jelas.',
                'alasan_publik' => 'Produk kuliner warga RT 05 memenuhi standar kebersihan.',
                'created_at' => now()->subDays(2),
            ]
        );

        // 3. Audit koreksi kas transaksi
        AuditLog::firstOrCreate(
            [
                'target_type' => 'kas_transaksi',
                'target_id' => $kas->id,
                'aksi' => AuditAction::KAS_TRANSACTION_CORRECTED,
            ],
            [
                'rt_id' => $rt->id,
                'rw_id' => $rw->id,
                'user_id' => $bendahara->id,
                'actor_nama' => $bendahara->nama,
                'actor_role' => 'bendahara',
                'ip_address' => '127.0.0.1',
                'sebelum' => ['nominal' => 350000],
                'sesudah' => ['nominal' => 425000],
                'alasan' => 'Penyesuaian faktur toko listrik: ada tambahan kabel roll outdoor dan fiting gantung yang tercantum di struk susulan.',
                'alasan_publik' => 'Penyesuaian faktur pembelian lampu penerangan dan kabel outdoor.',
                'created_at' => now()->subDays(5),
            ]
        );
    }
}
