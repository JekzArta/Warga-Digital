<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class KasTransaksi extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'kas_transaksi';

    public const KATEGORI_MASUK_PRESETS = [
        'Saldo Awal',
        'Iuran Warga Bulanan',
        'Iuran Kebersihan & Keamanan',
        'Sumbangan / Donasi Warga',
        'Bantuan Pemerintah / Kelurahan',
        'Lain-lain (Pemasukan)',
    ];

    public const KATEGORI_KELUAR_PRESETS = [
        'Honor Kebersihan & Keamanan',
        'Perbaikan Sarana & Fasilitas Lingkungan',
        'Operasional & ATK RT',
        'Kegiatan Warga & Hari Besar',
        'Sosial & Santunan Duka',
        'Lain-lain (Pengeluaran)',
    ];

    protected $fillable = [
        'rt_id',
        'input_by',
        'jenis',
        'kategori',
        'nominal',
        'keterangan',
        'tanggal',
        'is_koreksi',
        'koreksi_dari_id',
        'catatan_koreksi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
            'is_koreksi' => 'boolean',
        ];
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'rt_id');
    }

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    /**
     * Transaksi sebelumnya yang dikoreksi oleh transaksi ini.
     */
    public function koreksiDari(): BelongsTo
    {
        return $this->belongsTo(KasTransaksi::class, 'koreksi_dari_id');
    }

    /**
     * Transaksi baru yang mengoreksi/menggantikan transaksi ini.
     */
    public function koreksiBerikutnya(): HasOne
    {
        return $this->hasOne(KasTransaksi::class, 'koreksi_dari_id');
    }

    /**
     * Scope untuk mengambil transaksi aktif (versi mutakhir / leaf node).
     * Transaksi yang sudah memiliki koreksiBerikutnya dikecualikan agar tidak double counting.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereDoesntHave('koreksiBerikutnya');
    }

    /**
     * Scope untuk pemasukan kas.
     */
    public function scopeMasuk(Builder $query): Builder
    {
        return $query->where('jenis', 'masuk');
    }

    /**
     * Scope untuk pengeluaran kas.
     */
    public function scopeKeluar(Builder $query): Builder
    {
        return $query->where('jenis', 'keluar');
    }

    /**
     * Mengambil seluruh riwayat rantai koreksi dari transaksi awal hingga versi mutakhir.
     * Mengembalikan collection berisi transaksi awal -> koreksi 1 -> koreksi 2 ...
     */
    public function getRiwayatKoreksi(): Collection
    {
        $riwayat = collect([$this]);
        $visited = [$this->id];

        // Telusuri ke belakang (ancestors)
        $current = $this;
        while ($current->koreksiDari && ! in_array($current->koreksi_dari_id, $visited)) {
            $visited[] = $current->koreksi_dari_id;
            $current = $current->koreksiDari;
            $riwayat->prepend($current);
        }

        // Telusuri ke depan jika method dipanggil dari transaksi yang sudah usang (descendants)
        $current = $this;
        while ($current->koreksiBerikutnya && ! in_array($current->koreksiBerikutnya->id, $visited)) {
            $visited[] = $current->koreksiBerikutnya->id;
            $current = $current->koreksiBerikutnya;
            $riwayat->push($current);
        }

        return $riwayat;
    }

    /**
     * Menghitung berapa kali transaksi ini telah mengalami koreksi ke belakang (ancestors).
     * Transaksi awal = 0. Koreksi pertama = 1. Koreksi kedua = 2.
     */
    public function getJumlahKoreksiAttribute(): int
    {
        $count = 0;
        $current = $this;
        $visited = [$this->id];
        while ($current->koreksiDari && ! in_array($current->koreksi_dari_id, $visited)) {
            $visited[] = $current->koreksi_dari_id;
            $current = $current->koreksiDari;
            $count++;
        }
        return $count;
    }
}
