<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KalenderEvent extends Model
{
    use HasFactory;

    protected $table = 'kalender_events';

    // Konstanta Kategori Agenda Terstandarisasi
    public const KATEGORI_KEGIATAN = 'KEGIATAN';
    public const KATEGORI_RAPAT = 'RAPAT';
    public const KATEGORI_POSYANDU = 'POSYANDU';
    public const KATEGORI_LAINNYA = 'LAINNYA';

    public const KATEGORI_LIST = [
        self::KATEGORI_KEGIATAN,
        self::KATEGORI_RAPAT,
        self::KATEGORI_POSYANDU,
        self::KATEGORI_LAINNYA,
    ];

    // Konstanta Sumber Agenda
    public const SUMBER_MANUAL = 'manual';
    public const SUMBER_ANNOUNCEMENT = 'announcement';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'judul',
        'deskripsi',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'lokasi',
        'kategori',
        'sumber',
        'announcement_id',
        'is_cancelled',
        'pembatalan_alasan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'is_cancelled' => 'boolean',
        ];
    }

    /**
     * User (pengurus) yang membuat event ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pengumuman sumber resmi (jika event merupakan representasi dari Pengumuman).
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id');
    }

    /**
     * Scope untuk memfilter event berdasarkan ruang lingkup wilayah (RT atau RW).
     */
    public function scopeForScope(Builder $query, string $scopeType, int|string $scopeId): Builder
    {
        return $query->where('scope_type', strtolower($scopeType))
            ->where('scope_id', (int) $scopeId);
    }

    /**
     * Scope untuk menyaring event berdasarkan visibilitas user (RT user + RW terkait).
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->is_super_admin) {
            return $query;
        }

        $rwId = $user->rw_id ?? $user->rt?->rw_id;

        return $query->where(function (Builder $q) use ($user, $rwId) {
            if ($user->rt_id) {
                $q->where(function (Builder $sub) use ($user) {
                    $sub->where('scope_type', 'rt')
                        ->where('scope_id', (int) $user->rt_id);
                });
                if ($rwId) {
                    $q->orWhere(function (Builder $sub) use ($rwId) {
                        $sub->where('scope_type', 'rw')
                            ->where('scope_id', (int) $rwId);
                    });
                }
            } elseif ($rwId) {
                $rtIds = Rt::where('rw_id', $rwId)->pluck('id');
                $q->where(function (Builder $sub) use ($rwId) {
                    $sub->where('scope_type', 'rw')
                        ->where('scope_id', (int) $rwId);
                });
                if ($rtIds->isNotEmpty()) {
                    $q->orWhere(function (Builder $sub) use ($rtIds) {
                        $sub->where('scope_type', 'rt')
                            ->whereIn('scope_id', $rtIds);
                    });
                }
            } else {
                $q->whereRaw('1 = 0');
            }
        });
    }

    /**
     * Scope untuk event aktif:
     * 1. Tidak dibatalkan secara eksplisit (is_cancelled = false)
     * 2. Jika berasal dari Pengumuman resmi (sumber = announcement), pengumuman induk
     *    harus aktif (is_deactivated = false dan is_replaced = false).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_cancelled', false)
            ->where(function (Builder $q) {
                $q->whereNull('announcement_id')
                    ->orWhereHas('announcement', function (Builder $annQuery) {
                        $annQuery->where('is_deactivated', false)
                            ->where('is_replaced', false);
                    });
            });
    }

    /**
     * Scope untuk agenda mendatang yang diurutkan kronologis.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->active()
            ->where('tanggal', '>=', now()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('waktu_mulai');
    }

    /**
     * Scope untuk menyaring agenda dalam rentang bulan tertentu.
     */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month);
    }

    /**
     * Scope untuk menyaring berdasarkan kategori agenda.
     */
    public function scopeKategori(Builder $query, string $kategori): Builder
    {
        return $query->where('kategori', strtoupper($kategori));
    }

    /**
     * Format rentang waktu kegiatan yang ramah pengguna.
     */
    public function getFormattedWaktuAttribute(): string
    {
        if ($this->waktu_mulai && $this->waktu_selesai) {
            return "{$this->waktu_mulai} - {$this->waktu_selesai} WIB";
        }

        if ($this->waktu_mulai) {
            return "{$this->waktu_mulai} WIB";
        }

        return 'Sepanjang hari';
    }

    /**
     * Label presentasi bahasa Indonesia untuk kategori agenda.
     */
    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori) {
            self::KATEGORI_KEGIATAN => 'Kegiatan Warga',
            self::KATEGORI_RAPAT => 'Rapat Warga',
            self::KATEGORI_POSYANDU => 'Posyandu',
            self::KATEGORI_LAINNYA => 'Lainnya',
            default => 'Kegiatan',
        };
    }

    /**
     * Kelas styling badge Tailwind yang serasi dengan palet Figma.
     */
    public function getKategoriBadgeClassAttribute(): string
    {
        return match ($this->kategori) {
            self::KATEGORI_KEGIATAN => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            self::KATEGORI_RAPAT => 'bg-amber-50 text-amber-700 border border-amber-200',
            self::KATEGORI_POSYANDU => 'bg-rose-50 text-rose-700 border border-rose-200',
            self::KATEGORI_LAINNYA => 'bg-sky-50 text-sky-700 border border-sky-200',
            default => 'bg-stone-50 text-stone-700 border border-stone-200',
        };
    }

    /**
     * Titik indikator warna (dot) pada kalender bulanan.
     */
    public function getKategoriDotClassAttribute(): string
    {
        return match ($this->kategori) {
            self::KATEGORI_KEGIATAN => 'bg-emerald-500',
            self::KATEGORI_RAPAT => 'bg-amber-500',
            self::KATEGORI_POSYANDU => 'bg-rose-500',
            self::KATEGORI_LAINNYA => 'bg-sky-500',
            default => 'bg-stone-500',
        };
    }

    /**
     * Memeriksa apakah event ini memiliki tautan ke pengumuman aktif.
     */
    public function getIsLinkedToAnnouncementAttribute(): bool
    {
        return $this->sumber === self::SUMBER_ANNOUNCEMENT && ! empty($this->announcement_id);
    }

    /**
     * Label badge sumber informasi (asal agenda).
     */
    public function getSourceBadgeLabelAttribute(): string
    {
        if ($this->is_linked_to_announcement) {
            return 'Dari Pengumuman';
        }

        return $this->scope_type === 'rt' ? 'Agenda RT' : 'Agenda RW';
    }
}
