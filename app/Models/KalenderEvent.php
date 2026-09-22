<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KalenderEvent extends Model
{
    use HasFactory;

    protected $table = 'kalender_events';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'judul',
        'deskripsi',
        'tanggal',
        'sumber',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
