<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Klien extends Model
{
    use HasFactory;

    protected $table = 'klien';

    protected $fillable = [
        'kode_wilayah',
        'nama',
        'tenor_lisensi',
        'tanggal_mulai',
        'tanggal_berakhir',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_berakhir' => 'date',
        ];
    }

    public function rws(): HasMany
    {
        return $this->hasMany(Rw::class, 'klien_id');
    }
}
