<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratPengajuan extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'surat_pengajuan';

    protected $fillable = [
        'rt_id',
        'user_id',
        'jenis_surat',
        'nomor_surat',
        'form_data',
        'status',
        'alasan_tolak',
        'pdf_url',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'form_data' => 'array',
        ];
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'rt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function kelengkapan(): HasMany
    {
        return $this->hasMany(SuratKelengkapan::class, 'pengajuan_id');
    }
}
