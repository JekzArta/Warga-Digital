<?php

namespace App\Http\Controllers;

use App\Models\SuratPengajuan;
use App\Services\SuratPdfGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VerifikasiSuratController extends Controller
{
    /**
     * Pesan kesalahan generik untuk mencegah enumeration dan information leakage.
     */
    protected const GENERIC_ERROR_MESSAGE = 'Kode verifikasi tidak ditemukan atau dokumen tidak dapat diverifikasi.';

    /**
     * Menampilkan halaman verifikasi dokumen publik atau memproses pengecekan kode jika diberikan.
     */
    public function show(Request $request, ?string $kode = null)
    {
        Carbon::setLocale('id');

        // Ambil kode dari route parameter atau query string ?kode=...
        $rawKode = $kode ?? $request->query('kode');

        // Jika halaman dibuka murni tanpa parameter kode, tampilkan form awal
        if ($rawKode === null || trim($rawKode) === '') {
            return view('verifikasi.index', [
                'kodeInput' => '',
                'hasil' => null,
                'error' => null,
            ]);
        }

        $kodeInput = strtoupper(trim($rawKode));

        // Sanitasi dasar format kode: heksadesimal 16 karakter
        // Jika format malformed/rusak, kembalikan pesan generik tanpa error 500
        if (!preg_match('/^[A-F0-9]{16}$/', $kodeInput)) {
            return view('verifikasi.index', [
                'kodeInput' => $kodeInput,
                'hasil' => null,
                'error' => self::GENERIC_ERROR_MESSAGE,
            ]);
        }

        // Query database secara eksplisit:
        // 1. withoutGlobalScopes() wajib karena publik/tamu tidak memiliki sesi rt_id
        // 2. Pilih HANYA field yang diizinkan (Zero Model Serialization, No NIK, No Alamat Lengkap)
        $surat = SuratPengajuan::withoutGlobalScopes()
            ->where('kode_verifikasi', $kodeInput)
            ->select(['id', 'rt_id', 'user_id', 'jenis_surat', 'nomor_surat', 'kode_verifikasi', 'status', 'form_data', 'reviewed_by', 'updated_at'])
            ->with([
                'user' => function ($query) {
                    $query->select(['id', 'nama']); // Hanya ID dan Nama lengkap pemohon
                },
                'reviewer' => function ($query) {
                    $query->select(['id', 'nama', 'is_super_admin']);
                },
                'rt' => function ($query) {
                    $query->select(['id', 'rw_id', 'nomor_rt', 'nama']);
                },
                'rt.rw' => function ($query) {
                    $query->select(['id', 'klien_id', 'nomor_rw']);
                },
                'rt.rw.klien' => function ($query) {
                    $query->select(['id', 'nama', 'kode_wilayah', 'kecamatan', 'kota']);
                },
            ])
            ->first();

        // Pemeriksaan Status Lifecycle:
        // Surat yang statusnya BUKAN 'DISETUJUI' (misal: MENUNGGU, DITOLAK, PERLU_KELENGKAPAN)
        // WAJIB mendapatkan response error generik yang sama persis dengan kode tidak ditemukan
        if (!$surat || $surat->status !== 'DISETUJUI') {
            return view('verifikasi.index', [
                'kodeInput' => $kodeInput,
                'hasil' => null,
                'error' => self::GENERIC_ERROR_MESSAGE,
            ]);
        }

        $rt = $surat->rt;
        $rw = $rt?->rw;
        $klien = $rw?->klien;
        $namaKelurahan = $klien?->nama ? trim(str_ireplace('Kelurahan', '', $klien->nama)) : 'Sekeloa';
        $kecamatan = $klien?->kecamatan ?: 'Coblong';
        $kota = $klien?->kota ?: 'Bandung';

        $reviewer = $surat->reviewer;
        $penandatanganNama = $reviewer?->nama ? strtoupper($reviewer->nama) : '—';
        $penandatanganJabatan = $reviewer ? SuratPdfGenerator::getJabatanPenandatangan($reviewer, $rt, $rw)[0] : 'Pengurus RT';

        // Ekstraksi selektif Objek Keterangan (Minimum Necessary Disclosure)
        // JANGAN mengekspos form_data mentah atau field sensitif ke view
        $formData = is_array($surat->form_data) ? $surat->form_data : [];
        $rincianObjek = [];

        switch ($surat->jenis_surat) {
            case 'SKU':
                $rincianObjek = [
                    [
                        'label' => 'Nama Usaha',
                        'value' => !empty($formData['nama_usaha']) ? (string) $formData['nama_usaha'] : '—',
                    ],
                    [
                        'label' => 'Bidang Usaha',
                        'value' => !empty($formData['bidang_usaha']) ? (string) $formData['bidang_usaha'] : '—',
                    ],
                ];
                break;

            case 'SPKK':
                $rincianObjek = [
                    [
                        'label' => 'Nama Kepala Keluarga',
                        'value' => !empty($formData['nama_kepala_keluarga']) ? (string) $formData['nama_kepala_keluarga'] : '—',
                    ],
                ];
                break;

            case 'SKL':
                $rincianObjek = [
                    [
                        'label' => 'Nama Pelapor (Orang Tua)',
                        'value' => strtoupper($surat->user->nama ?? '—'),
                    ],
                    [
                        'label' => 'Nama Bayi / Anak',
                        'value' => !empty($formData['nama_anak']) ? strtoupper((string) $formData['nama_anak']) : '—',
                    ],
                ];
                break;

            case 'SKKm':
                $rincianObjek = [
                    [
                        'label' => 'Nama Pelapor (Ahli Waris)',
                        'value' => strtoupper($surat->user->nama ?? '—'),
                    ],
                    [
                        'label' => 'Nama Almarhum / Almarhumah',
                        'value' => !empty($formData['nama_almarhum']) ? strtoupper((string) $formData['nama_almarhum']) : '—',
                    ],
                ];
                break;

            case 'SKD':
            case 'SKTM':
            default:
                // Minimum disclosure: subjek/pemohon adalah orang yang sama, tidak ada objek tambahan
                $rincianObjek = [];
                break;
        }

        // Transformasi ke DTO Array datar (Data Transfer Object)
        // Memastikan tidak ada properti objek model atau data sensitif yang bocor ke view Blade
        $hasil = [
            'nomor_surat' => $surat->nomor_surat,
            'jenis_surat_kode' => $surat->jenis_surat,
            'jenis_surat' => SuratPdfGenerator::getNamaJenisSurat($surat->jenis_surat),
            'nama_pemohon' => strtoupper($surat->user->nama ?? '—'),
            'rt_rw' => sprintf('RT %02d / RW %02d', $rt->nomor_rt ?? 5, $rw->nomor_rw ?? 3),
            'kelurahan' => $namaKelurahan,
            'kecamatan' => $kecamatan,
            'kota' => $kota,
            'penandatangan_nama' => $penandatanganNama,
            'penandatangan_jabatan' => $penandatanganJabatan,
            'tanggal_terbit' => Carbon::parse($surat->updated_at)->translatedFormat('d F Y'),
            'kode_verifikasi' => $surat->kode_verifikasi,
            'status_konfirmasi' => 'Kode verifikasi cocok dengan data penerbitan yang tercatat pada sistem Warga Digital.',
            'rincian_objek' => $rincianObjek,
        ];

        return view('verifikasi.index', [
            'kodeInput' => $kodeInput,
            'hasil' => $hasil,
            'error' => null,
        ]);
    }
}
