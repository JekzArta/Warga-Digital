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

    /**
     * Memeriksa apakah user berwenang mengelola listing UMKM (membuat atau mengedit/menghapus listing).
     * Sesuai aturan:
     * - Warga: hanya boleh mengelola listing miliknya sendiri di RT-nya
     * - Pengurus RT (Sekretaris, Bendahara, Ketua RT, Wakil RT): boleh mengelola di RT-nya
     * - Ketua RW: tidak untuk listing operasional RT
     * - Super Admin: akses global
     * - Isolasi tenant RT berlaku ketat (lintas RT ditolak).
     */
    public static function canManageUmkm(?User $user, \App\Models\UmkmListing|int $listingOrRtId, ?int $ownerId = null): bool
    {
        if (! $user || $user->status !== 'aktif') {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        if ($listingOrRtId instanceof \App\Models\UmkmListing) {
            $rtId = (int) $listingOrRtId->rt_id;
            $listingOwnerId = (int) $listingOrRtId->user_id;
        } else {
            $rtId = (int) $listingOrRtId;
            $listingOwnerId = $ownerId !== null ? (int) $ownerId : null;
        }

        // Isolasi Tenant: user harus terdaftar di RT yang sama
        if (! self::canAccess($user, 'rt', $rtId)) {
            return false;
        }

        // Ketua RW tidak mengelola listing operasional RT
        if ($user->hasRole('ketua_rw') && ! $user->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'warga'])) {
            return false;
        }

        // Pengurus RT (Ketua RT, Wakil RT, Sekretaris) boleh mengelola
        if ($user->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris'])) {
            return true;
        }

        // Warga biasa hanya boleh mengelola listing miliknya sendiri
        if ($user->hasRole('warga')) {
            if ($listingOwnerId === null) {
                return true;
            }
            return (int) $user->id === $listingOwnerId;
        }

        return false;
    }

    /**
     * Memeriksa apakah user berwenang men-takedown listing UMKM.
     * Sesuai aturan:
     * - Warga & Bendahara: DILARANG (false)
     * - Pengurus RT (Sekretaris, Ketua RT, Wakil RT): Boleh di RT wilayahnya
     * - Ketua RW: Boleh lintas RT di dalam RW binaannya
     * - Super Admin: Akses global
     */
    public static function canTakedownUmkm(?User $user, \App\Models\UmkmListing $listing): bool
    {
        if (! $user || $user->status !== 'aktif') {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        // Bendahara dan Warga biasa tidak berwenang men-takedown
        if ($user->hasRole('bendahara') && ! $user->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris', 'ketua_rw'])) {
            return false;
        }

        if ($user->hasRole('warga') && ! $user->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris', 'ketua_rw'])) {
            return false;
        }

        $rtId = (int) $listing->rt_id;
        $listingRwId = $listing->rt?->rw_id ?? \App\Models\Rt::where('id', $rtId)->value('rw_id');

        // Pengurus RT (Ketua RT, Wakil RT, Sekretaris) berwenang di RT yang sama
        if ($user->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris'])) {
            return self::canAccess($user, 'rt', $rtId);
        }

        // Ketua RW berwenang lintas RT di dalam RW yang sama
        if ($user->hasRole('ketua_rw')) {
            return $listingRwId !== null && self::canAccess($user, 'rw', $listingRwId);
        }

        return false;
    }

    /**
     * Memeriksa apakah user berwenang mereview (menyetujui / menolak) listing UMKM.
     * Sesuai matriks RBAC SDD §3.2 & §6.4:
     * - Scope RT: Sekretaris, Ketua RT, Wakil RT
     * - Dilarang: Warga, Bendahara, Ketua RW
     * - Super Admin: akses global
     * - Isolasi tenant RT berlaku ketat.
     */
    public static function canReviewUmkm(?User $user, \App\Models\UmkmListing|int $listingOrRtId): bool
    {
        if (! $user || $user->status !== 'aktif') {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        $rtId = $listingOrRtId instanceof \App\Models\UmkmListing
            ? (int) $listingOrRtId->rt_id
            : (int) $listingOrRtId;

        // Isolasi Tenant: reviewer harus berada di RT yang bersangkutan
        if (! self::canAccess($user, 'rt', $rtId)) {
            return false;
        }

        // Hanya Sekretaris, Ketua RT, dan Wakil RT
        return $user->hasRole(['sekretaris', 'ketua_rt', 'wakil_rt']);
    }

    /**
     * Memeriksa apakah user berwenang melihat agenda Kalender pada scope wilayah tertentu.
     * Sesuai aturan:
     * - Warga & Pengurus RT: boleh melihat kalender RT miliknya sendiri dan kalender RW-nya
     * - Ketua RW: boleh melihat kalender RW dan kalender RT mana pun di bawah RW-nya
     * - Super Admin: akses global
     */
    public static function canViewKalender(?User $user, string $scopeType, int|string $scopeId): bool
    {
        if (! $user || $user->status !== 'aktif') {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        $scopeType = strtolower($scopeType);
        $scopeId = (int) $scopeId;

        // Jika user adalah Ketua RW yang mengecek RT di bawahnya
        if ($user->hasRole('ketua_rw') && $user->rw_id && $scopeType === 'rt') {
            $rt = \App\Models\Rt::find($scopeId);
            return $rt !== null && (int) $rt->rw_id === (int) $user->rw_id;
        }

        return self::canAccess($user, $scopeType, $scopeId);
    }

    /**
     * Memeriksa apakah user berwenang mengelola (tambah/edit/hapus) agenda Kalender.
     * Sesuai keputusan desain:
     * - Scope RT: Ketua RT, Wakil RT, Sekretaris
     * - Scope RW: Ketua RW
     * - Warga, Bendahara: Dilarang (Read-Only)
     * - Super Admin: Akses global
     */
    public static function canManageKalender(?User $user, string $scopeType, int|string $scopeId): bool
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
     * Memeriksa apakah user berwenang melihat album dan foto galeri kegiatan RT tertentu.
     * Sesuai matriks RBAC SDD §3.2 & TenantScope:
     * - Warga, Bendahara, Sekretaris, Wakil RT, Ketua RT: hanya boleh melihat RT miliknya sendiri
     * - Ketua RW: boleh melihat galeri seluruh RT yang berada di bawah RW binaannya
     * - Super Admin: global access
     * - Cross-tenant: ditolak
     */
    public static function canViewGaleri(?User $user, int $rtId): bool
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

    /**
     * Memeriksa apakah user berwenang mengelola (buat/edit/hapus album & upload/hapus foto) galeri RT.
     * Sesuai matriks RBAC SDD §3.2:
     * - Scope RT: Sekretaris, Ketua RT, Wakil RT pada RT miliknya sendiri
     * - Warga, Bendahara, dan Ketua RW: Dilarang (Read-Only)
     * - Super Admin: Global access
     * - Cross-tenant: Ditolak
     */
    public static function canManageGaleri(?User $user, int $rtId): bool
    {
        if (! self::canAccess($user, 'rt', $rtId)) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasRole(['sekretaris', 'ketua_rt', 'wakil_rt']);
    }
}

