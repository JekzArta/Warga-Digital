<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Catat aksi sensitif ke dalam audit_logs.
     *
     * @param string $aksi Contoh: 'approve_surat', 'tolak_surat', 'hapus_thread', 'assign_role'
     * @param string $targetType Contoh: 'surat_pengajuan', 'forum_thread', 'umkm_listing'
     * @param int $targetId
     * @param array|null $sebelum Data sebelum aksi
     * @param array|null $sesudah Data sesudah aksi
     * @param string|null $alasan Alasan keputusan (wajib pada penolakan/koreksi)
     * @param int|null $rtId Override rt_id jika berbeda dari user yang login
     * @param int|null $rwId Override rw_id jika berbeda dari user yang login
     * @return AuditLog
     */
    public static function log(
        string $aksi,
        string $targetType,
        int $targetId,
        ?array $sebelum = null,
        ?array $sesudah = null,
        ?string $alasan = null,
        ?int $rtId = null,
        ?int $rwId = null
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::create([
            'rt_id' => $rtId ?? $user?->rt_id,
            'rw_id' => $rwId ?? $user?->rw_id,
            'user_id' => $user?->id ?? 1, // Fallback sistem jika tanpa sesi
            'aksi' => $aksi,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'sebelum' => $sebelum,
            'sesudah' => $sesudah,
            'alasan' => $alasan,
        ]);
    }
}
