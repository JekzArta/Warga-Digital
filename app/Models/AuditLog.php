<?php

namespace App\Models;

use App\Constants\AuditAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'rt_id',
        'rw_id',
        'user_id',
        'actor_nama',
        'actor_role',
        'ip_address',
        'aksi',
        'target_type',
        'target_id',
        'sebelum',
        'sesudah',
        'alasan',
        'alasan_publik',
    ];

    /**
     * Sembunyikan field internal & sensitif dari serialisasi array/JSON.
     */
    protected $hidden = [
        'ip_address',
        'user_id',
        'sebelum',
        'sesudah',
    ];

    protected function casts(): array
    {
        return [
            'sebelum' => 'array',
            'sesudah' => 'array',
        ];
    }

    /**
     * Custom Eloquent Builder untuk kompatibilitas backward query legacy action strings.
     */
    public function newEloquentBuilder($query): AuditLogBuilder
    {
        return new AuditLogBuilder($query);
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'rt_id');
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class, 'rw_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Polymorphic relation to target entity via morphMap.
     */
    public function target(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id')->withDefault();
    }

    /**
     * Label presentasi aksi audit dalam Bahasa Indonesia.
     */
    public function getAksiLabelAttribute(): string
    {
        return AuditAction::getLabel($this->aksi);
    }

    /**
     * Modul dari aksi audit.
     */
    public function getModulAttribute(): string
    {
        return AuditAction::getModule($this->aksi, $this->target_type);
    }

    /**
     * Nama actor dengan snapshot priority dan fallback ke user relation.
     */
    public function getActorNamaDisplayAttribute(): string
    {
        if (!empty($this->actor_nama)) {
            return $this->actor_nama;
        }

        if ($this->user) {
            return $this->user->nama;
        }

        return 'Sistem / Anonim';
    }

    /**
     * Label role actor saat aksi terjadi.
     */
    public function getActorRoleDisplayAttribute(): string
    {
        $role = $this->actor_role;

        if (empty($role) && $this->user) {
            return $this->user->getHighestRoleBadge();
        }

        return match ($role) {
            'super_admin' => 'Super Admin',
            'ketua_rw' => 'Ketua RW',
            'ketua_rt' => 'Ketua RT',
            'wakil_rt' => 'Wakil RT',
            'sekretaris' => 'Sekretaris',
            'bendahara' => 'Bendahara',
            'warga' => 'Warga',
            default => $role ? ucwords(str_replace('_', ' ', $role)) : 'Pengurus',
        };
    }

    /**
     * Label aktor publik untuk transparansi warga: "Nama Lengkap — Jabatan" (contoh: "Bambang Hartono — Ketua RT 05")
     */
    public function getPublicActorLabelAttribute(): string
    {
        $nama = $this->actor_nama ?? $this->user?->nama ?? 'Pengurus';
        $role = $this->actor_role;

        if (empty($role) && $this->user) {
            $role = $this->user->getHighestRoleCanonical();
        }

        $nomorRt = $this->rt?->nomor_rt ? str_pad($this->rt->nomor_rt, 2, '0', STR_PAD_LEFT) : null;
        if (! $nomorRt && $this->user?->rt?->nomor_rt) {
            $nomorRt = str_pad($this->user->rt->nomor_rt, 2, '0', STR_PAD_LEFT);
        }

        $nomorRw = $this->rw?->nomor_rw ? str_pad($this->rw->nomor_rw, 2, '0', STR_PAD_LEFT) : null;
        if (! $nomorRw && ($this->user?->rw?->nomor_rw ?? $this->user?->rt?->rw?->nomor_rw)) {
            $nomorRw = str_pad($this->user->rw?->nomor_rw ?? $this->user->rt->rw->nomor_rw, 2, '0', STR_PAD_LEFT);
        }

        $roleLabel = match ($role) {
            'super_admin' => 'Super Admin',
            'ketua_rw' => 'Ketua RW' . ($nomorRw ? ' ' . $nomorRw : ''),
            'ketua_rt' => 'Ketua RT' . ($nomorRt ? ' ' . $nomorRt : ''),
            'wakil_rt' => 'Wakil RT' . ($nomorRt ? ' ' . $nomorRt : ''),
            'sekretaris' => 'Sekretaris' . ($nomorRt ? ' RT ' . $nomorRt : ''),
            'bendahara' => 'Bendahara' . ($nomorRt ? ' RT ' . $nomorRt : ''),
            'warga' => 'Warga',
            default => 'Pengurus',
        };

        return "{$nama} — {$roleLabel}";
    }

    /**
     * Deskripsi ringkas tindakan publik untuk warga.
     */
    public function getPublicActionTextAttribute(): string
    {
        return match ($this->aksi) {
            AuditAction::ANNOUNCEMENT_UPDATED => 'Pengumuman diperbarui',
            AuditAction::ANNOUNCEMENT_DEACTIVATED => 'Pengumuman dinonaktifkan',
            default => 'Aktivitas pengumuman dicatat',
        };
    }

    /**
     * Deskripsi ringkas target untuk kartu audit.
     */
    public function getTargetDescriptionAttribute(): string
    {
        $target = $this->target;

        if ($target instanceof SuratPengajuan) {
            $nomor = $target->nomor_surat ?? ('#' . $target->id);
            return "Surat {$nomor} ({$target->jenis_surat})";
        }

        if ($target instanceof Announcement) {
            return "Pengumuman: {$target->judul}";
        }

        if ($target instanceof ForumThread) {
            return "Thread: {$target->judul}";
        }

        if ($target instanceof ForumPost) {
            return "Balasan Forum pada Thread #{$target->thread_id}";
        }

        if ($target instanceof User) {
            return "Warga: {$target->nama}";
        }

        if ($target instanceof KasTransaksi) {
            return "Transaksi Kas: {$target->keterangan}";
        }

        if ($target instanceof UmkmListing) {
            return "Usaha UMKM: {$target->nama_usaha}";
        }

        if ($target instanceof KalenderEvent) {
            return "Agenda: {$target->judul}";
        }

        if ($target instanceof GaleriAlbum) {
            return "Album: {$target->judul}";
        }

        // Fallback jika model target sudah dihapus atau tidak ditemukan
        $typeLabel = ucwords(str_replace('_', ' ', $this->target_type));
        return "{$typeLabel} #{$this->target_id}";
    }
}
