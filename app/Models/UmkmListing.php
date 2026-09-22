<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmListing extends Model
{
    use HasFactory, TenantScoped;

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
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
        ];
    }

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
}
