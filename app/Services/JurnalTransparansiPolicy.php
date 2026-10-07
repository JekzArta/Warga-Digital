<?php

namespace App\Services;

use App\Constants\AuditAction;
use App\Models\AuditLog;

class JurnalTransparansiPolicy
{
    /**
     * Whitelist 7 aksi audit yang secara semantik diizinkan menjadi entri Jurnal Transparansi.
     */
    public const WHITELIST = [
        AuditAction::ANNOUNCEMENT_CREATED,
        AuditAction::ANNOUNCEMENT_UPDATED,
        AuditAction::ANNOUNCEMENT_DEACTIVATED,
        AuditAction::FORUM_THREAD_CLOSED,
        AuditAction::FORUM_THREAD_REOPENED,
        AuditAction::KAS_TRANSACTION_CORRECTED,
        AuditAction::UMKM_LISTING_APPROVED,
    ];

    /**
     * 5 aksi yang mewajibkan penjelasan publik eksplisit (public reason).
     */
    public const EVENTS_REQUIRING_PUBLIC_REASON = [
        AuditAction::ANNOUNCEMENT_UPDATED,
        AuditAction::ANNOUNCEMENT_DEACTIVATED,
        AuditAction::FORUM_THREAD_CLOSED,
        AuditAction::FORUM_THREAD_REOPENED,
        AuditAction::KAS_TRANSACTION_CORRECTED,
    ];

    /**
     * Tentukan apakah satu record AuditLog eligible untuk diproyeksikan ke Jurnal Transparansi.
     */
    public function isEligible(AuditLog $log): bool
    {
        // 1. Default DENY: Hanya aksi di dalam whitelist yang eligible
        if (! in_array($log->aksi, self::WHITELIST, true)) {
            return false;
        }

        // 2. Evaluasi semantik khusus untuk pembaruan pengumuman
        if ($log->aksi === AuditAction::ANNOUNCEMENT_UPDATED) {
            if (! $this->isSubstantiveUpdate($log)) {
                return false;
            }
        }

        // 3. Evaluasi alasan publik wajib untuk 5 aksi moderasi/koreksi
        if (in_array($log->aksi, self::EVENTS_REQUIRING_PUBLIC_REASON, true)) {
            if (empty(trim($log->alasan_publik ?? ''))) {
                return false;
            }
        }

        // 4. Evaluasi keamanan privasi dan format pada alasan publik (jika ada)
        if (! empty($log->alasan_publik)) {
            if (! $this->passesPublicSafetyValidation($log->alasan_publik)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifikasi pembaruan pengumuman memiliki perubahan substantif.
     */
    public function isSubstantiveUpdate(AuditLog $log): bool
    {
        // Syarat 1: Alasan publik wajib terisi
        if (empty(trim($log->alasan_publik ?? ''))) {
            return false;
        }

        $sebelum = is_array($log->sebelum) ? $log->sebelum : [];
        $sesudah = is_array($log->sesudah) ? $log->sesudah : [];

        if (empty($sebelum) && empty($sesudah)) {
            return false;
        }

        // Syarat 2: Bandingkan judul (normalisasi spasi berulang)
        $judulSebelum = preg_replace('/\s+/', ' ', trim($sebelum['judul'] ?? ''));
        $judulSesudah = preg_replace('/\s+/', ' ', trim($sesudah['judul'] ?? ''));

        if ($judulSebelum !== '' && $judulSesudah !== '' && $judulSebelum !== $judulSesudah) {
            return true;
        }

        // Syarat 3: Bandingkan konten (jika ada snapshot konten)
        $kontenSebelum = preg_replace('/\s+/', ' ', trim($sebelum['konten'] ?? ''));
        $kontenSesudah = preg_replace('/\s+/', ' ', trim($sesudah['konten'] ?? ''));
        if ($kontenSebelum !== '' && $kontenSesudah !== '' && $kontenSebelum !== $kontenSesudah) {
            return true;
        }

        // Syarat 4: Bandingkan atribut agenda / tanggal / waktu / lokasi
        $substantiveKeys = [
            'is_agenda',
            'tanggal',
            'tanggal_mulai',
            'tanggal_selesai',
            'waktu_mulai',
            'waktu_selesai',
            'lokasi',
            'kategori',
        ];

        foreach ($substantiveKeys as $key) {
            if (array_key_exists($key, $sesudah) && array_key_exists($key, $sebelum)) {
                if ($sebelum[$key] !== $sesudah[$key]) {
                    return true;
                }
            } elseif (array_key_exists($key, $sesudah) && ! array_key_exists($key, $sebelum)) {
                return true;
            }
        }

        // Jika hanya perbedaan formatting/whitespace atau ID internal -> ineligible (DROP)
        return false;
    }

    /**
     * Validasi defensif keselamatan publik pada teks alasan publik.
     *
     * Catatan Arsitektur: Validasi regex ini adalah pola defense-in-depth berlapis,
     * bukan jaminan privasi absolut (NLP moderation).
     */
    public function passesPublicSafetyValidation(?string $reason): bool
    {
        if ($reason === null) {
            return true;
        }

        $trimmed = trim($reason);
        if ($trimmed === '') {
            return false;
        }

        // 1. Batas panjang maksimum 500 karakter
        if (mb_strlen($trimmed) > 500) {
            return false;
        }

        // 2. Tolak keberadaan markup HTML
        if (preg_match('/<[^>]+>/', $reason)) {
            return false;
        }

        // 3. Tolak pola angka 16 digit berturut-turut (deteksi defensif pola NIK)
        if (preg_match('/\b[0-9]{16}\b/', $reason)) {
            return false;
        }

        // 4. Tolak pola nomor telepon seluler Indonesia (+62 / 62 / 08...)
        if (preg_match('/\b(?:\+?62|0)8[0-9]{7,12}\b/', $reason)) {
            return false;
        }

        return true;
    }
}
