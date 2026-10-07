<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\JurnalTransparansi;
use Illuminate\Database\QueryException;

class JurnalTransparansiProjector
{
    public function __construct(
        protected JurnalTransparansiPolicy $policy,
        protected JurnalTransparansiTransformer $transformer
    ) {}

    /**
     * Proyeksikan satu AuditLog menjadi satu entri JurnalTransparansi secara idempoten.
     */
    public function project(AuditLog $auditLog): ?JurnalTransparansi
    {
        // 1. Evaluasi Policy kelayakan event
        if (! $this->policy->isEligible($auditLog)) {
            return null;
        }

        // 2. Fast-path Idempotency: Jika proyeksi untuk audit_log_id ini sudah ada, kembalikan langsung
        $existing = JurnalTransparansi::where('audit_log_id', $auditLog->id)->first();
        if ($existing) {
            return $existing;
        }

        // 3. Transformasi payload proyeksi
        $payload = $this->transformer->transform($auditLog);
        if (! $payload) {
            return null;
        }

        // 4. Persistensi idempoten dengan penanganan race-condition pada UNIQUE(audit_log_id)
        try {
            return JurnalTransparansi::create($payload);
        } catch (QueryException $e) {
            // Jika terjadi konflik UNIQUE karena concurrency, ambil baris yang berhasil dibuat
            $raceWinner = JurnalTransparansi::where('audit_log_id', $auditLog->id)->first();
            if ($raceWinner) {
                return $raceWinner;
            }

            // Jika error disebabkan hal lain (misal constraint pelanggaran skema), teruskan exception
            throw $e;
        }
    }
}
