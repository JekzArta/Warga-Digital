<?php

namespace App\Services;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Canonical target types for new audit records.
     */
    protected static array $canonicalTargetTypes = [
        'announcement' => 'announcements',
        'forum_thread' => 'forum_threads',
        'forum_post' => 'forum_posts',
        'user' => 'users',
    ];

    /**
     * Catat aksi sensitif ke dalam audit_logs.
     *
     * @param string $aksi Contoh: AuditAction::SURAT_APPROVED, 'approve_surat'
     * @param string $targetType Contoh: 'surat_pengajuan', 'announcements', 'forum_threads'
     * @param int $targetId
     * @param array|null $sebelum Data sebelum aksi
     * @param array|null $sesudah Data sesudah aksi
     * @param string|null $alasan Alasan keputusan (wajib pada penolakan/koreksi)
     * @param int|null $rtId Override rt_id jika berbeda dari user yang login
     * @param int|null $rwId Override rw_id jika berbeda dari user yang login
     * @param User|null $actor Override actor secara eksplisit jika perlu
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
        ?int $rwId = null,
        ?User $actor = null
    ): AuditLog {
        $user = $actor ?? Auth::user();

        $ipAddress = null;
        try {
            $ipAddress = request()?->ip();
        } catch (\Throwable) {
            $ipAddress = null;
        }

        return AuditLog::create([
            'rt_id' => $rtId ?? $user?->rt_id,
            'rw_id' => $rwId ?? $user?->rw_id ?? $user?->rt?->rw_id,
            // HARD REQUIREMENT: Hapus fallback user_id = 1. Actor unauthenticated bernilai NULL.
            'user_id' => $user?->id,
            'actor_nama' => $user?->nama,
            'actor_role' => $user?->getHighestRoleCanonical(),
            'ip_address' => $ipAddress,
            'aksi' => $aksi,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'sebelum' => $sebelum,
            'sesudah' => $sesudah,
            'alasan' => $alasan,
        ]);
    }
}
