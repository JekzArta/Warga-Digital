<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratKelengkapan extends Model
{
    use HasFactory;

    protected $table = 'surat_kelengkapan';

    protected $fillable = [
        'pengajuan_id',
        'pesan',
        'file_url',
        'dari_role',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(SuratPengajuan::class, 'pengajuan_id');
    }
}
