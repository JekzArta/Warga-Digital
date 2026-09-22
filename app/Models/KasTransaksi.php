<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasTransaksi extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'kas_transaksi';

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

    public function koreksiDari(): BelongsTo
    {
        return $this->belongsTo(KasTransaksi::class, 'koreksi_dari_id');
    }
}
