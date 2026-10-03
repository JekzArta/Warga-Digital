<?php

namespace App\Constants;

class AuditAction
{
    // Surat
    public const SURAT_SUBMITTED = 'SURAT_SUBMITTED';
    public const SURAT_REVIEWED = 'SURAT_REVIEWED';
    public const SURAT_COMPLETION_REQUESTED = 'SURAT_COMPLETION_REQUESTED';
    public const SURAT_COMPLETION_SUBMITTED = 'SURAT_COMPLETION_SUBMITTED';
    public const SURAT_APPROVED = 'SURAT_APPROVED';
    public const SURAT_REJECTED = 'SURAT_REJECTED';

    // Pengumuman
    public const ANNOUNCEMENT_CREATED = 'ANNOUNCEMENT_CREATED';
    public const ANNOUNCEMENT_UPDATED = 'ANNOUNCEMENT_UPDATED';
    public const ANNOUNCEMENT_DEACTIVATED = 'ANNOUNCEMENT_DEACTIVATED';
    public const ANNOUNCEMENT_PINNED = 'ANNOUNCEMENT_PINNED';
    public const ANNOUNCEMENT_UNPINNED = 'ANNOUNCEMENT_UNPINNED';
    public const ANNOUNCEMENT_FORUM_LINKED = 'ANNOUNCEMENT_FORUM_LINKED';

    // Forum
    public const FORUM_THREAD_PINNED = 'FORUM_THREAD_PINNED';
    public const FORUM_THREAD_UNPINNED = 'FORUM_THREAD_UNPINNED';
    public const FORUM_THREAD_CLOSED = 'FORUM_THREAD_CLOSED';
    public const FORUM_THREAD_REOPENED = 'FORUM_THREAD_REOPENED';
    public const FORUM_THREAD_DELETED = 'FORUM_THREAD_DELETED';
    public const FORUM_POST_DELETED = 'FORUM_POST_DELETED';

    // Auth & User Management
    public const AUTH_ACCOUNT_ACTIVATED = 'AUTH_ACCOUNT_ACTIVATED';
    public const USER_ROLE_ASSIGNED = 'USER_ROLE_ASSIGNED';
    public const USER_ROLE_REVOKED = 'USER_ROLE_REVOKED';
    public const USER_MUTATION_RT = 'USER_MUTATION_RT';
    public const USER_DATA_CORRECTED = 'USER_DATA_CORRECTED';

    // Keuangan & Kas
    public const KAS_TRANSACTION_CREATED = 'KAS_TRANSACTION_CREATED';
    public const KAS_TRANSACTION_CORRECTED = 'KAS_TRANSACTION_CORRECTED';

    // UMKM
    public const UMKM_LISTING_APPROVED = 'UMKM_LISTING_APPROVED';
    public const UMKM_LISTING_REJECTED = 'UMKM_LISTING_REJECTED';

    /**
     * Legacy aliases mapping ke canonical constant.
     */
    public static function toCanonical(string $action): string
    {
        return match ($action) {
            'approve_surat' => self::SURAT_APPROVED,
            'tolak_surat' => self::SURAT_REJECTED,
            'review_surat' => self::SURAT_REVIEWED,
            'kirim_kelengkapan_surat' => self::SURAT_COMPLETION_SUBMITTED,
            'minta_kelengkapan_surat' => self::SURAT_COMPLETION_REQUESTED,
            'terbitkan_pengumuman' => self::ANNOUNCEMENT_CREATED,
            'pin_thread' => self::FORUM_THREAD_PINNED,
            'unpin_thread' => self::FORUM_THREAD_UNPINNED,
            'close_thread' => self::FORUM_THREAD_CLOSED,
            'reopen_thread' => self::FORUM_THREAD_REOPENED,
            'hapus_thread' => self::FORUM_THREAD_DELETED,
            'hapus_post' => self::FORUM_POST_DELETED,
            'aktivasi_akun_warga' => self::AUTH_ACCOUNT_ACTIVATED,
            default => $action,
        };
    }

    /**
     * Label presentasi bahasa Indonesia untuk UI "Meja Audit Akuntabilitas".
     */
    public static function getLabel(string $action): string
    {
        return match (self::toCanonical($action)) {
            self::SURAT_SUBMITTED => 'Pengajuan Surat Baru',
            self::SURAT_REVIEWED => 'Membuka / Mereview Surat',
            self::SURAT_COMPLETION_REQUESTED => 'Meminta Kelengkapan Berkas',
            self::SURAT_COMPLETION_SUBMITTED => 'Mengunggah Perbaikan Berkas',
            self::SURAT_APPROVED => 'Menyetujui Surat',
            self::SURAT_REJECTED => 'Menolak Surat',

            self::ANNOUNCEMENT_CREATED => 'Menerbitkan Pengumuman',
            self::ANNOUNCEMENT_UPDATED => 'Memperbarui Pengumuman',
            self::ANNOUNCEMENT_DEACTIVATED => 'Menonaktifkan Pengumuman',
            self::ANNOUNCEMENT_PINNED => 'Menyematkan Pengumuman',
            self::ANNOUNCEMENT_UNPINNED => 'Melepas Sematan Pengumuman',
            self::ANNOUNCEMENT_FORUM_LINKED => 'Menghubungkan Pengumuman ke Forum',

            self::FORUM_THREAD_PINNED => 'Menyematkan Thread Forum',
            self::FORUM_THREAD_UNPINNED => 'Melepas Sematan Thread Forum',
            self::FORUM_THREAD_CLOSED => 'Menutup Thread Forum',
            self::FORUM_THREAD_REOPENED => 'Membuka Kembali Thread Forum',
            self::FORUM_THREAD_DELETED => 'Menghapus Thread Forum',
            self::FORUM_POST_DELETED => 'Menghapus Balasan Forum',

            self::AUTH_ACCOUNT_ACTIVATED => 'Aktivasi Akun Warga',
            self::USER_ROLE_ASSIGNED => 'Penetapan Role Pengurus',
            self::USER_ROLE_REVOKED => 'Pencabutan Role Pengurus',
            self::USER_MUTATION_RT => 'Mutasi Wilayah Warga',
            self::USER_DATA_CORRECTED => 'Koreksi Data Warga',

            self::KAS_TRANSACTION_CREATED => 'Pencatatan Transaksi Kas',
            self::KAS_TRANSACTION_CORRECTED => 'Koreksi Transaksi Kas',

            self::UMKM_LISTING_APPROVED => 'Menyetujui Usaha UMKM',
            self::UMKM_LISTING_REJECTED => 'Menolak Usaha UMKM',

            default => ucwords(str_replace(['_', '-'], ' ', strtolower($action))),
        };
    }

    /**
     * Dapatkan modul berdasarkan aksi atau target_type.
     */
    public static function getModule(string $action, ?string $targetType = null): string
    {
        $action = self::toCanonical($action);

        if (str_starts_with($action, 'SURAT_') || $targetType === 'surat_pengajuan') {
            return 'Surat';
        }
        if (str_starts_with($action, 'ANNOUNCEMENT_') || in_array($targetType, ['announcements', 'announcement'])) {
            return 'Pengumuman';
        }
        if (str_starts_with($action, 'FORUM_') || in_array($targetType, ['forum_threads', 'forum_thread', 'forum_posts'])) {
            return 'Forum';
        }
        if (str_starts_with($action, 'USER_') || str_starts_with($action, 'AUTH_') || $targetType === 'users') {
            return 'Kependudukan';
        }
        if (str_starts_with($action, 'KAS_') || $targetType === 'kas_transaksi') {
            return 'Keuangan';
        }
        if (str_starts_with($action, 'UMKM_') || $targetType === 'umkm_listings') {
            return 'UMKM';
        }

        return 'Sistem';
    }
}
