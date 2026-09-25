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
     * Dapatkan format jabatan dan sub-jabatan penandatangan secara dinamis berdasarkan role reviewer.
     */
    public static function getJabatanPenandatangan($reviewer, $rt, $rw): array
    {
        $roles = $reviewer->getActiveRoleNames();

        if (in_array('ketua_rt', $roles)) {
            $namaJabatan = 'Ketua RT';
        } elseif (in_array('wakil_rt', $roles)) {
            $namaJabatan = 'Wakil RT';
        } elseif (in_array('sekretaris', $roles)) {
            $namaJabatan = 'Sekretaris RT';
        } elseif (in_array('ketua_rw', $roles)) {
            $nomorRw = $rw ? str_pad($rw->nomor_rw, 2, '0', STR_PAD_LEFT) : '03';
            return [
                "Ketua RW {$nomorRw}",
                "Pengurus RW {$nomorRw}",
            ];
        } else {
            $namaJabatan = 'Pengurus RT';
        }

        $nomorRt = $rt ? str_pad($rt->nomor_rt, 2, '0', STR_PAD_LEFT) : '05';
        $nomorRw = $rw ? str_pad($rw->nomor_rw, 2, '0', STR_PAD_LEFT) : '03';

        return [
            "{$namaJabatan} {$nomorRt} / RW {$nomorRw}",
            "{$namaJabatan} {$nomorRt}",
        ];
    }

    /**
     * Generate PDF stream atau download untuk surat pengajuan yang telah disetujui.
     */
    public static function generate(SuratPengajuan $surat)
    {
        Carbon::setLocale('id');

        if (!$surat->reviewer) {
            throw new \InvalidArgumentException('Dokumen tidak dapat diterbitkan karena data penandatangan belum tersedia.');
        }

        $user = $surat->user;
        $rt = $surat->rt;
        $rw = $rt->rw;
        $klien = $rw->klien;
        $reviewer = $surat->reviewer;

        [$jabatanPenandatangan, $subJabatanPenandatangan] = self::getJabatanPenandatangan($reviewer, $rt, $rw);

        // Kode hash verifikasi dokumen untuk keaslian surat
        $verificationCode = strtoupper(substr(hash('sha256', ($surat->nomor_surat ?? 'WD') . ($surat->id) . ($surat->created_at)), 0, 16));

        // Alamat pemohon: prioritaskan alamat domisili yang diinput warga pada surat (misal SKD), fallback ke profil user
        $alamatCetak = !empty($surat->form_data['alamat_domisili'])
            ? $surat->form_data['alamat_domisili']
            : ($user->alamat ?? ('RT 0' . ($rt->nomor_rt ?? 5) . ' / RW 0' . ($rw->nomor_rw ?? 3)));

        $data = [
            'surat' => $surat,
            'user' => $user,
            'rt' => $rt,
            'rw' => $rw,
            'klien' => $klien,
            'reviewer' => $reviewer,
            'jabatanPenandatangan' => $jabatanPenandatangan,
            'subJabatanPenandatangan' => $subJabatanPenandatangan,
            'namaJenisSurat' => self::getNamaJenisSurat($surat->jenis_surat),
            'verificationCode' => $verificationCode,
            'tanggalSurat' => Carbon::parse($surat->updated_at)->translatedFormat('d F Y'),
            'alamatCetak' => $alamatCetak,
        ];

        $pdf = Pdf::loadView('surat.pdf.template', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}
