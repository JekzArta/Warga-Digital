<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JurnalTransparansiDispatcher
{
    public function __construct(
        protected JurnalTransparansiProjector $projector
    ) {}

    /**
     * Dispatch proyeksi setelah transaksi database commit, atau sinkron jika di luar transaksi.
     * Kegagalan proyeksi ditangkap dan diisolasi dengan Log::warning agar tidak membatalkan transaksi bisnis.
     */
    public function dispatchProjection(AuditLog $auditLog): void
    {
        $callback = function () use ($auditLog) {
            try {
                $this->projector->project($auditLog);
            } catch (\Throwable $e) {
                Log::warning("JurnalTransparansiDispatcher: Gagal memproyeksikan AuditLog #{$auditLog->id}: " . $e->getMessage(), [
                    'audit_log_id' => $auditLog->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($callback);
        } else {
            $callback();
        }
    }

    /**
     * Static helper untuk memudahkan pemanggilan dari dispatch layer.
     */
    public static function dispatch(AuditLog $auditLog): void
    {
        app(self::class)->dispatchProjection($auditLog);
    }
}
