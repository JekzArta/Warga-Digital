<?php

namespace App\Services;

use App\Models\SuratPengajuan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;

class SuratPdfGenerator
{
    /**
     * Map nama lengkap jenis surat.
     */
    public static function getNamaJenisSurat(string $kode): string
    {
        $map = [
            'SKD' => 'Surat Keterangan Domisili',
            'SKTM' => 'Surat Keterangan Tidak Mampu',
            'SKU' => 'Surat Keterangan Usaha',
            'SPKK' => 'Surat Pengantar Kartu Keluarga',
            'SKL' => 'Surat Keterangan Kelahiran',
            'SKKm' => 'Surat Keterangan Kematian',
        ];

        return $map[$kode] ?? 'Surat Keterangan';
    }

    /**
     * Generate PDF stream atau download untuk surat pengajuan yang telah disetujui.
     */
    public static function generate(SuratPengajuan $surat)
    {
        Carbon::setLocale('id');

        $user = $surat->user;
        $rt = $surat->rt;
        $rw = $rt->rw;
        $klien = $rw->klien;

        // Kode hash verifikasi dokumen untuk keaslian surat
        $verificationCode = strtoupper(substr(hash('sha256', ($surat->nomor_surat ?? 'WD') . ($surat->id) . ($surat->created_at)), 0, 16));

        $data = [
            'surat' => $surat,
            'user' => $user,
            'rt' => $rt,
            'rw' => $rw,
            'klien' => $klien,
            'namaJenisSurat' => self::getNamaJenisSurat($surat->jenis_surat),
            'verificationCode' => $verificationCode,
            'tanggalSurat' => Carbon::parse($surat->updated_at)->translatedFormat('d F Y'),
        ];

        $pdf = Pdf::loadView('surat.pdf.template', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}
