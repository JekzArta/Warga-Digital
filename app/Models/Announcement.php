<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'author_id',
        'judul',
        'konten',
        'tipe',
        'is_pinned',
        'expired_at',
        'is_deactivated',
        'deactivated_at',
        'deactivated_by',
        'deactivation_reason',
        'replaces_announcement_id',
        'is_replaced',
        'forum_thread_id',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_deactivated' => 'boolean',
            'is_replaced' => 'boolean',
            'expired_at' => 'date',
            'deactivated_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AnnouncementComment::class, 'announcement_id');
    }

    /**
     * Pengumuman versi sebelumnya yang digantikan oleh pengumuman ini.
     */
    public function previous(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'replaces_announcement_id');
    }

    /**
     * Pengumuman versi baru (Pembaruan langsung) yang menggantikan pengumuman ini.
     */
    public function successor(): HasOne
    {
        return $this->hasOne(Announcement::class, 'replaces_announcement_id');
    }

    /**
     * Relasi opsional ke Forum Warga Terkait (Layer 3).
     */
    public function forumThread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'forum_thread_id');
    }

    /**
     * Agenda Kalender yang terhubung dengan pengumuman ini (opsional).
     */
    public function kalenderEvent(): HasOne
    {
        return $this->hasOne(KalenderEvent::class, 'announcement_id');
    }

    /**
     * Pengurus yang menonaktifkan pengumuman ini.
     */
    public function deactivator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    /**
     * Riwayat aktivitas publik khusus pengumuman ini (transparansi warga).
     * Dibatasi ketat hanya pada: ANNOUNCEMENT_UPDATED dan ANNOUNCEMENT_DEACTIVATED.
     */
    public function publicActivities(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'target_id')
            ->select([
                'id',
                'target_type',
                'target_id',
                'actor_nama',
                'actor_role',
                'aksi',
                'alasan',
                'rt_id',
                'rw_id',
                'created_at',
            ])
            ->whereIn('target_type', ['announcements', 'announcement'])
            ->whereIn('aksi', [
                \App\Constants\AuditAction::ANNOUNCEMENT_UPDATED,
                \App\Constants\AuditAction::ANNOUNCEMENT_DEACTIVATED,
            ])
            ->latest('created_at');
    }

    /**
     * Scope untuk active feed warga:
     * - Belum digantikan (is_replaced = false)
     * - Belum dinonaktifkan (is_deactivated = false)
     * - Belum kadaluarsa (expired_at null atau >= hari ini)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_deactivated', false)
            ->where('is_replaced', false)
            ->where(function (Builder $q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now()->startOfDay());
            });
    }

    /**
     * Aksesor status lifecycle: ACTIVE, EXPIRED, DEACTIVATED, atau REPLACED.
     */
    public function getLifecycleStatusAttribute(): string
    {
        if ($this->is_deactivated) {
            return 'DEACTIVATED';
        }

        if ($this->is_replaced) {
            return 'REPLACED';
        }

        if ($this->expired_at && $this->expired_at->isPast() && ! $this->expired_at->isToday()) {
            return 'EXPIRED';
        }

        return 'ACTIVE';
    }

    /**
     * Mengecek apakah pengumuman ini dapat dibuatkan pembaruan.
     */
    public function canBeUpdated(): bool
    {
        return ! $this->is_deactivated && ! $this->is_replaced && is_null($this->successor);
    }

    /**
     * Mengecek apakah pengumuman ini dapat dihubungkan ke Forum.
     */
    public function canLinkForum(): bool
    {
        return is_null($this->forum_thread_id) && ! $this->is_deactivated && ! $this->is_replaced;
    }

    /**
     * Mengambil versi paling mutakhir (terbaru) dari rantai pembaruan.
     */
    public function getLatestVersion(): Announcement
    {
        $current = $this;
        $visited = [$current->id];
        $safetyCounter = 0;

        while ($current->successor && $safetyCounter < 50) {
            $next = $current->successor;
            if (in_array($next->id, $visited)) {
                break;
            }
            $visited[] = $next->id;
            $current = $next;
            $safetyCounter++;
        }

        return $current;
    }

    /**
     * Mengambil versi akar (awal/V1) dari rantai pembaruan.
     */
    public function getRootVersion(): Announcement
    {
        $current = $this;
        $visited = [$current->id];
        $safetyCounter = 0;

        while ($current->previous && $safetyCounter < 50) {
            $prev = $current->previous;
            if (in_array($prev->id, $visited)) {
                break;
            }
            $visited[] = $prev->id;
            $current = $prev;
            $safetyCounter++;
        }

        return $current;
    }

    /**
     * Memeriksa apakah ini merupakan versi paling mutakhir dalam rantai suksesi.
     */
    public function isLatestVersion(): bool
    {
        return is_null($this->successor);
    }

    /**
     * Memeriksa apakah pengumuman dapat menerima komentar/tanggapan baru.
     * HARD REQUIREMENT: Hanya versi aktif yang belum digantikan dan belum dinonaktifkan yang boleh.
     */
    public function canReceiveComments(): bool
    {
        return ! $this->is_replaced && ! $this->is_deactivated;
    }
}
