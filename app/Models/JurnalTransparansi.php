<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalTransparansi extends Model
{
    use HasFactory;

    protected $table = 'jurnal_transparansi';

    /**
     * Tabel bersifat append-only, tidak memiliki kolom updated_at.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'audit_log_id',
        'scope_type',
        'scope_id',
        'rw_id',
        'event_type',
        'actor_label',
        'target_title',
        'public_reason',
        'target_type',
        'target_id',
        'occurred_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Internal audit log reference.
     */
    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class, 'audit_log_id');
    }
}
