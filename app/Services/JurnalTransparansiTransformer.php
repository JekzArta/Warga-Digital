<?php

namespace App\Services;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class JurnalTransparansiTransformer
{
    /**
     * Transformasi satu instance AuditLog menjadi payload jurnal_transparansi.
     */
    public function transform(AuditLog $auditLog): ?array
    {
        $scope = $this->resolveScope($auditLog);
        if (! $scope) {
            Log::warning("JurnalTransparansiTransformer: Gagal menyelesaikan scope untuk AuditLog #{$auditLog->id}");
            return null;
        }

        $actorLabel = $this->transformActorLabel($auditLog);
        $targetTitle = $this->transformTargetTitle($auditLog);
        $publicReason = $this->sanitizePublicReason($auditLog->alasan_publik);

        return [
            'audit_log_id' => $auditLog->id,
            'scope_type'   => $scope['scope_type'],
            'scope_id'     => $scope['scope_id'],
            'rw_id'        => $scope['rw_id'],
            'event_type'   => $auditLog->aksi,
            'actor_label'  => mb_substr($actorLabel, 0, 100),
            'target_title' => mb_substr($targetTitle, 0, 255),
            'public_reason'=> $publicReason,
            'target_type'  => $auditLog->target_type,
            'target_id'    => $auditLog->target_id,
            'occurred_at'  => $auditLog->created_at ?? now(),
        ];
    }

    /**
     * Selesaikan scope autorisasi dari snapshot AuditLog.
     */
    public function resolveScope(AuditLog $auditLog): ?array
    {
        if ($auditLog->rt_id !== null) {
            $rwId = $auditLog->rw_id ?? $auditLog->rt?->rw_id;
            if ($rwId === null) {
                // Fallback aman bila relasi rt tidak termuat atau rw_id null
                $rwId = 1;
            }

            return [
                'scope_type' => 'rt',
                'scope_id'   => $auditLog->rt_id,
                'rw_id'      => $rwId,
            ];
        }

        if ($auditLog->rw_id !== null) {
            return [
                'scope_type' => 'rw',
                'scope_id'   => $auditLog->rw_id,
                'rw_id'      => $auditLog->rw_id,
            ];
        }

        return null;
    }

    /**
     * Transformasi snapshot actor audit menjadi label publik aman privasi.
     *
     * Rules:
     * - Pengumuman: "Nama Lengkap — Jabatan"
     * - Moderasi / Koreksi / UMKM: "Jabatan" saja (anonimitas personal pengurus terjaga)
     */
    public function transformActorLabel(AuditLog $auditLog): string
    {
        $role = $auditLog->actor_role;
        $nama = $auditLog->actor_nama;

        $nomorRt = $auditLog->rt?->nomor_rt;
        $nomorRw = $auditLog->rw?->nomor_rw;

        $roleLabel = $this->formatRoleLabel($role, $nomorRt, $nomorRw);

        $isAnnouncement = in_array($auditLog->aksi, [
            AuditAction::ANNOUNCEMENT_CREATED,
            AuditAction::ANNOUNCEMENT_UPDATED,
            AuditAction::ANNOUNCEMENT_DEACTIVATED,
        ], true);

        if ($isAnnouncement) {
            $displayName = ! empty($nama) ? $nama : 'Pengurus';
            return "{$displayName} — {$roleLabel}";
        }

        // Moderasi Forum, Koreksi Kas, Review UMKM -> Jabatan saja
        return $roleLabel;
    }

    /**
     * Format label jabatan sesuai penomoran RT/RW.
     */
    protected function formatRoleLabel(?string $role, ?int $nomorRt = null, ?int $nomorRw = null): string
    {
        $padRt = $nomorRt ? str_pad($nomorRt, 2, '0', STR_PAD_LEFT) : null;
        $padRw = $nomorRw ? str_pad($nomorRw, 2, '0', STR_PAD_LEFT) : null;

        return match ($role) {
            'super_admin' => 'Super Admin',
            'ketua_rw'    => 'Ketua RW' . ($padRw ? ' ' . $padRw : ''),
            'ketua_rt'    => 'Ketua RT' . ($padRt ? ' ' . $padRt : ''),
            'wakil_rt'    => 'Wakil RT' . ($padRt ? ' ' . $padRt : ''),
            'sekretaris'  => 'Sekretaris' . ($padRt ? ' RT ' . $padRt : ''),
            'bendahara'   => 'Bendahara' . ($padRt ? ' RT ' . $padRt : ''),
            'warga'       => 'Warga',
            default       => $padRt ? "Pengurus RT {$padRt}" : ($padRw ? "Pengurus RW {$padRw}" : 'Pengurus'),
        };
    }

    /**
     * Transformasi target domain menjadi judul publik yang aman dari kebocoran data.
     */
    public function transformTargetTitle(AuditLog $auditLog): string
    {
        $sebelum = is_array($auditLog->sebelum) ? $auditLog->sebelum : [];
        $sesudah = is_array($auditLog->sesudah) ? $auditLog->sesudah : [];

        switch ($auditLog->aksi) {
            case AuditAction::ANNOUNCEMENT_CREATED:
            case AuditAction::ANNOUNCEMENT_UPDATED:
            case AuditAction::ANNOUNCEMENT_DEACTIVATED:
                $judul = $sesudah['judul'] ?? $sebelum['judul'] ?? null;
                if (! $judul) {
                    $judul = $auditLog->target?->judul;
                }

                if (! empty($judul)) {
                    $cleanJudul = trim(strip_tags($judul));
                    return 'Pengumuman: ' . mb_substr($cleanJudul, 0, 240);
                }

                return 'Pengumuman Resmi Lingkungan';

            case AuditAction::FORUM_THREAD_CLOSED:
            case AuditAction::FORUM_THREAD_REOPENED:
                // DILARANG menggunakan judul thread mentah demi privasi topik musyawarah
                $kategori = $sesudah['kategori'] ?? $sebelum['kategori'] ?? null;
                if (! $kategori) {
                    $kategori = $auditLog->target?->category?->nama;
                }

                if (! empty($kategori)) {
                    $cleanKategori = trim(strip_tags($kategori));
                    return "Musyawarah Warga: Kategori {$cleanKategori}";
                }

                return 'Musyawarah Komunitas Warga';

            case AuditAction::KAS_TRANSACTION_CORRECTED:
                // DILARANG menggunakan keterangan transaksi kas mentah
                $kategori = $sesudah['kategori'] ?? $sebelum['kategori'] ?? null;
                if (! $kategori) {
                    $kategori = $auditLog->target?->kategori;
                }

                if (! empty($kategori)) {
                    $cleanKategori = trim(strip_tags($kategori));
                    return "Pos Anggaran: {$cleanKategori} (Koreksi Pembukuan)";
                }

                return 'Koreksi Pencatatan Kas RT';

            case AuditAction::UMKM_LISTING_APPROVED:
                // Hanya gunakan nama usaha dan kategori. Tidak menyertakan harga, telp, atau copy promosi.
                $nama = $sesudah['nama_usaha'] ?? $auditLog->target?->nama_usaha ?? null;
                $kategori = $sesudah['kategori'] ?? $auditLog->target?->kategori ?? null;

                if (! empty($nama) && ! empty($kategori)) {
                    $cleanNama = trim(strip_tags($nama));
                    $cleanKategori = trim(strip_tags($kategori));
                    return "Usaha Warga: {$cleanNama} ({$cleanKategori})";
                } elseif (! empty($nama)) {
                    $cleanNama = trim(strip_tags($nama));
                    return "Usaha Warga: {$cleanNama}";
                }

                return 'Pendaftaran Usaha Warga';

            default:
                $targetLabel = ucwords(str_replace('_', ' ', $auditLog->target_type));
                return "Aktivitas Tata Kelola: {$targetLabel}";
        }
    }

    /**
     * Sanitasi teknis alasan publik: trim, strip markup HTML, dan batasi panjang <= 500.
     */
    public function sanitizePublicReason(?string $reason): ?string
    {
        if ($reason === null) {
            return null;
        }

        $clean = trim(strip_tags($reason));
        if ($clean === '') {
            return null;
        }

        return mb_substr($clean, 0, 500);
    }
}
