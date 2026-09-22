<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rt extends Model
{
    use HasFactory;

    protected $table = 'rt';

    protected $fillable = [
        'rw_id',
        'kode_rt',
        'nomor_rt',
        'nama',
        'format_nomor_surat',
    ];

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class, 'rw_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rt_id');
    }

    public function suratPengajuan(): HasMany
    {
        return $this->hasMany(SuratPengajuan::class, 'rt_id');
    }

    public function kasTransaksi(): HasMany
    {
        return $this->hasMany(KasTransaksi::class, 'rt_id');
    }

    public function umkmListing(): HasMany
    {
        return $this->hasMany(UmkmListing::class, 'rt_id');
    }

    public function galeriAlbum(): HasMany
    {
        return $this->hasMany(GaleriAlbum::class, 'rt_id');
    }
}
