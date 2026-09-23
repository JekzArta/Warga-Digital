<?php

namespace App\Services;

use App\Models\Rt;
use App\Models\SuratPengajuan;
use Carbon\Carbon;

class SuratNumberGenerator
{
    /**
     * Konversi bulan angka ke angka Romawi.
     */
    public static function toRomanMonth(int $month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return $romans[$month] ?? 'I';
    }

    /**
     * Generate nomor surat resmi untuk RT.
     * Format default: {nomor}/RT{nomor_rt}-RW{nomor_rw}/SK/{bulan_romawi}/{tahun}
     * Contoh: 002/RT05-RW03/SK/IX/2026
     */
    public static function generate(Rt $rt, string $jenisSurat = 'SKD', ?Carbon $date = null): string
    {
        $date = $date ?? now();
        $year = $date->year;
        $monthRoman = self::toRomanMonth($date->month);

        // Hitung berapa surat yang sudah memiliki nomor di RT ini pada tahun yang sama
        $count = SuratPengajuan::withoutGlobalScopes()
            ->where('rt_id', $rt->id)
            ->whereYear('created_at', $year)
            ->whereNotNull('nomor_surat')
            ->count();

        $nomorUrut = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        $template = $rt->format_nomor_surat ?: '{nomor}/RT0{nomor_rt}-RW0{nomor_rw}/{jenis}/{bulan_romawi}/{tahun}';

        $rwNomor = $rt->rw ? $rt->rw->nomor_rw : 1;

        $replacements = [
            '{nomor}' => $nomorUrut,
            '{nomor_rt}' => $rt->nomor_rt,
            '{nomor_rw}' => $rwNomor,
            '{jenis}' => $jenisSurat,
            '{bulan_romawi}' => $monthRoman,
            '{tahun}' => $year,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
