<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmListing extends Model
{
    use HasFactory, TenantScoped;

    // Status Enum Constants
    public const STATUS_MENUNGGU = 'MENUNGGU';
    public const STATUS_DISETUJUI = 'DISETUJUI';
    public const STATUS_DITOLAK = 'DITOLAK';
    public const STATUS_NONAKTIF = 'NONAKTIF';
    public const STATUS_DITAKEDOWN = 'DITAKEDOWN';

    // Kategori Enum Constants
    public const KATEGORI_JASA = 'jasa';
    public const KATEGORI_BARANG = 'barang';

    protected $table = 'umkm_listing';

    protected $fillable = [
        'user_id',
        'rt_id',
        'kategori',
        'nama',
        'deskripsi',
        'harga',
        'foto_url',
        'template_pesan_wa',
        'status',
        'alasan_tolak',
        'reviewed_by',
        'takedown_by',
        'alasan_takedown',
        'takedown_at',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'takedown_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi Eloquent
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'rt_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function takedownBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'takedown_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Local Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDisetujui(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_DISETUJUI);
    }

    public function scopeMenunggu(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_MENUNGGU);
    }

    public function scopeDitolak(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_DITOLAK);
    }

    public function scopeNonaktif(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_NONAKTIF);
    }

    public function scopeDitakedown(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_DITAKEDOWN);
    }

    public function scopeMilikUser(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeKategori(\Illuminate\Database\Eloquent\Builder $query, string $kategori): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('kategori', $kategori);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Format harga Rupiah untuk barang, atau teks negosiasi untuk jasa/null.
     */
    public function getFormattedHargaAttribute(): string
    {
        if ($this->kategori === self::KATEGORI_BARANG && $this->harga !== null) {
            return 'Rp ' . number_format($this->harga, 0, ',', '.');
        }

        return 'Tanya Penjual / Negosiasi';
    }

    /**
     * Generator tautan WhatsApp resmi dengan nomor dinormalisasi dan pesan ter-encode.
     */
    public function getWhatsappLinkAttribute(): ?string
    {
        $rawPhone = $this->user?->no_hp;
        if (empty($rawPhone)) {
            return null;
        }

        // 1. Normalisasi nomor telepon: buang karakter selain digit
        $phone = preg_replace('/[^0-9]/', '', (string) $rawPhone);

        // Jika diawali 0, ganti dengan 62 (contoh: 0812... -> 62812...)
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if (empty($phone)) {
            return null;
        }

        // 2. Tentukan isi pesan teks (custom template atau default berdasarkan kategori)
        if (! empty(trim((string) $this->template_pesan_wa))) {
            $pesan = trim((string) $this->template_pesan_wa);
        } else {
            $penjual = $this->user?->nama ?? 'Penjual';

            if ($this->kategori === self::KATEGORI_BARANG) {
                $hargaStr = $this->harga !== null
                    ? 'Rp ' . number_format($this->harga, 0, ',', '.')
                    : 'yang tertera';
                $pesan = "Halo {$penjual}, saya tertarik membeli \"{$this->nama}\" seharga {$hargaStr} yang saya lihat di platform Warga Digital. Apakah stok masih tersedia?";
            } else {
                $pesan = "Halo {$penjual}, saya tertarik dengan jasa \"{$this->nama}\" yang terdaftar di Warga Digital. Boleh tanya info tarif dan jadwal layanannya?";
            }
        }

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($pesan);
    }
}
