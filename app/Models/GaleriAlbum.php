<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GaleriAlbum extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'galeri_album';

    protected $fillable = [
        'rt_id',
        'judul',
        'tanggal_kegiatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kegiatan' => 'date',
        ];
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'rt_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(GaleriFoto::class, 'album_id');
    }
}
