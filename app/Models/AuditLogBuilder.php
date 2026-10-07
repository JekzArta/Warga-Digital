<?php

namespace App\Models;

use App\Constants\AuditAction;
use Illuminate\Database\Eloquent\Builder;

class AuditLogBuilder extends Builder
{
    /**
     * Legacy alias map for backward compatibility with existing tests and callsites.
     */
    protected static array $legacyAliases = [
        AuditAction::SURAT_APPROVED => ['SURAT_APPROVED', 'approve_surat'],
        AuditAction::SURAT_REJECTED => ['SURAT_REJECTED', 'tolak_surat'],
        AuditAction::SURAT_REVIEWED => ['SURAT_REVIEWED', 'review_surat'],
        AuditAction::SURAT_COMPLETION_SUBMITTED => ['SURAT_COMPLETION_SUBMITTED', 'kirim_kelengkapan_surat'],
        AuditAction::SURAT_COMPLETION_REQUESTED => ['SURAT_COMPLETION_REQUESTED', 'minta_kelengkapan_surat'],
        AuditAction::ANNOUNCEMENT_CREATED => ['ANNOUNCEMENT_CREATED', 'terbitkan_pengumuman'],
        AuditAction::AUTH_ACCOUNT_ACTIVATED => ['AUTH_ACCOUNT_ACTIVATED', 'aktivasi_akun_warga'],
        AuditAction::FORUM_THREAD_PINNED => ['FORUM_THREAD_PINNED', 'pin_thread'],
        AuditAction::FORUM_THREAD_UNPINNED => ['FORUM_THREAD_UNPINNED', 'unpin_thread'],
        AuditAction::FORUM_THREAD_CLOSED => ['FORUM_THREAD_CLOSED', 'close_thread'],
        AuditAction::FORUM_THREAD_REOPENED => ['FORUM_THREAD_REOPENED', 'reopen_thread'],
        AuditAction::FORUM_THREAD_DELETED => ['FORUM_THREAD_DELETED', 'hapus_thread'],
        AuditAction::FORUM_POST_DELETED => ['FORUM_POST_DELETED', 'hapus_post'],
    ];

    /**
     * Target type alias map for backward compatibility with historical rows and callsites.
     */
    protected static array $targetTypeAliases = [
        'announcements' => ['announcements', 'announcement'],
        'announcement'  => ['announcements', 'announcement'],
        'forum_threads' => ['forum_threads', 'forum_thread'],
        'forum_thread'  => ['forum_threads', 'forum_thread'],
        'umkm_listings' => ['umkm_listings', 'umkm_listing'],
        'umkm_listing'  => ['umkm_listings', 'umkm_listing'],
    ];

    /**
     * Intercept where('aksi', ...) and where('target_type', ...) equality conditions
     * to support both legacy and canonical action/target names.
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $numArgs = func_num_args();

        if (is_string($column) && $column === 'aksi') {
            $checkVal = ($numArgs === 2) ? $operator : $value;
            $op = ($numArgs === 2) ? '=' : $operator;

            if ($op === '=' && is_string($checkVal)) {
                $canonical = AuditAction::toCanonical($checkVal);
                if (isset(self::$legacyAliases[$canonical])) {
                    return $this->whereIn('aksi', self::$legacyAliases[$canonical], $boolean);
                }
            }
        }

        if (is_string($column) && $column === 'target_type') {
            $checkVal = ($numArgs === 2) ? $operator : $value;
            $op = ($numArgs === 2) ? '=' : $operator;

            if ($op === '=' && is_string($checkVal)) {
                if (isset(self::$targetTypeAliases[$checkVal])) {
                    return $this->whereIn('target_type', self::$targetTypeAliases[$checkVal], $boolean);
                }
            }
        }

        return parent::where($column, $operator, $value, $boolean);
    }
}
