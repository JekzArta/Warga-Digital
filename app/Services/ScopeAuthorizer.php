<?php

namespace App\Services;

use App\Models\User;

class ScopeAuthorizer
{
    /**
     * Memeriksa apakah user memiliki hak akses ke scope wilayah tertentu.
     * Aturan:
     * - User harus berstatus 'aktif'
     * - super_admin selalu lolos
     * - scope 'rt': user->rt_id harus identik dengan target scope_id
     * - scope 'rw': user->rw_id atau user->rt->rw_id harus identik dengan target scope_id
     */
    public static function canAccess(?User $user, string $scopeType, int|string $scopeId): bool
    {
        if (! $user || $user->status !== 'aktif') {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        $scopeId = (int) $scopeId;
        $userRwId = $user->rw_id ?? $user->rt?->rw_id;

        return match (strtolower($scopeType)) {
            'rt' => $user->rt_id !== null && (int) $user->rt_id === $scopeId,
            'rw' => $userRwId !== null && (int) $userRwId === $scopeId,
            default => false,
        };
    }

    /**
     * Menyelesaikan scope_id kanonikal milik user berdasarkan jenis scope.
     * Nilai ini selalu diturunkan dari server (database), bukan dari input client.
     */
    public static function resolveScopeId(?User $user, string $scopeType): ?int
    {
        if (! $user) {
            return null;
        }

        $userRwId = $user->rw_id ?? $user->rt?->rw_id;

        return match (strtolower($scopeType)) {
            'rt' => $user->rt_id ? (int) $user->rt_id : null,
            'rw' => $userRwId ? (int) $userRwId : null,
            default => null,
        };
    }

    /**
     * Memeriksa apakah user berwenang menerbitkan pengumuman resmi.
     * Sesuai matriks RBAC SDD §3.2:
     * - Scope RT: Ketua RT, Wakil RT, Sekretaris
     * - Scope RW: Ketua RW
     */
    public static function canPublishAnnouncement(?User $user, string $scopeType, int|string $scopeId): bool
    {
        if (! self::canAccess($user, $scopeType, $scopeId)) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        $scopeType = strtolower($scopeType);

        if ($scopeType === 'rt') {
            return $user->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris']);
        }

        if ($scopeType === 'rw') {
            return $user->hasRole('ketua_rw');
        }

        return false;
    }

    /**
     * Memeriksa apakah user berwenang melakukan tindakan moderasi forum (pin/close/hapus).
     * Sesuai matriks RBAC SDD §3.2 & §6.5:
     * - Scope RT: Ketua RT, Wakil RT
     * - Scope RW: Ketua RW
     * Catatan: Sekretaris dan Bendahara tidak memiliki wewenang moderasi forum.
     */
    public static function canModerateForum(?User $user, string $scopeType, int|string $scopeId): bool
    {
        if (! self::canAccess($user, $scopeType, $scopeId)) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        $scopeType = strtolower($scopeType);

        if ($scopeType === 'rt') {
            return $user->hasRole(['ketua_rt', 'wakil_rt']);
        }

        if ($scopeType === 'rw') {
            return $user->hasRole('ketua_rw');
        }

        return false;
    }

    /**
     * Memeriksa apakah user memiliki wewenang mengakses Meja Audit Akuntabilitas.
     * Sesuai matriks RBAC:
     * - Warga: Tidak boleh (403)
     * - Pengurus RT: Ketua RT, Wakil RT, Sekretaris, Bendahara (Read-only / Audit RT)
     * - Pengurus RW: Ketua RW (Audit RW + child RT)
     * - Super Admin: Global access
     */
    public static function canAccessAuditTrail(?User $user): bool
    {
        if (! $user || $user->status !== 'aktif') {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasRole(['ketua_rw', 'ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara']);
    }

    /**
     * Memeriksa apakah user berwenang mengelola transaksi kas RT (input pemasukan/pengeluaran & koreksi).
     * Sesuai matriks RBAC SDD §3.2:
     * - Scope RT: Bendahara, Ketua RT, Wakil RT
     * - Warga, Sekretaris, dan Ketua RW: Dilarang (Read-Only)
     */
    public static function canManageKas(?User $user, int $rtId): bool
    {
        if (! self::canAccess($user, 'rt', $rtId)) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasRole(['bendahara', 'ketua_rt', 'wakil_rt']);
    }

    /**
     * Memeriksa apakah user berwenang melihat transparansi kas RT tertentu.
     * Sesuai matriks RBAC SDD §3.2 & TenantScope:
     * - Warga dan Pengurus RT: hanya boleh melihat RT miliknya sendiri
     * - Ketua RW: boleh melihat RT mana pun yang berada di bawah RW-nya
     * - Super Admin: global access
     */
    public static function canViewKas(?User $user, int $rtId): bool
    {
        if (! $user || $user->status !== 'aktif') {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        if ($user->hasRole('ketua_rw') && $user->rw_id) {
            $rt = \App\Models\Rt::find($rtId);
            return $rt !== null && (int) $rt->rw_id === (int) $user->rw_id;
        }

        return self::canAccess($user, 'rt', $rtId);
    }
}
