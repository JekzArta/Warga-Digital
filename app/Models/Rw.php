<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rw extends Model
{
    use HasFactory;

    protected $table = 'rw';

    protected $fillable = [
        'klien_id',
        'kode_rw',
        'nomor_rw',
        'nama',
    ];

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class, 'klien_id');
    }

    public function rts(): HasMany
    {
        return $this->hasMany(Rt::class, 'rw_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rw_id');
    }
}
