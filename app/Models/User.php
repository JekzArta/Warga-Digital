<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'kode_warga',
        'rt_id',
        'rw_id',
        'nik',
        'email',
        'nama',
        'jenis_kelamin',
        'tanggal_lahir',
        'alamat',
        'no_hp',
        'password',
        'status',
        'is_super_admin',
        'last_login',
    ];

    /**
     * PENTING (UU PDP & Aturan AGENTS.md):
     * NIK tidak boleh pernah muncul di response JSON atau Blade view secara publik.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'nik',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'tanggal_lahir' => 'date',
            'last_login' => 'datetime',
            'is_super_admin' => 'boolean',
        ];
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'rt_id');
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class, 'rw_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function activeRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id')->whereNull('revoked_at');
    }

    /**
     * Cek apakah user memiliki role tertentu.
     * Aturan:
     * - super_admin selalu lolos
     * - wakil_rt permission identik dengan ketua_rt
     */
    public function hasRole(string|array $roles): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $roles = (array) $roles;

        // Penyetaraan: wakil_rt = ketua_rt
        if (in_array('ketua_rt', $roles) && !in_array('wakil_rt', $roles)) {
            $roles[] = 'wakil_rt';
        }
        if (in_array('wakil_rt', $roles) && !in_array('ketua_rt', $roles)) {
            $roles[] = 'ketua_rt';
        }

        return $this->activeRoles()->whereIn('role', $roles)->exists();
    }

    /**
     * Ambil nama-nama role aktif user sebagai array.
     */
    public function getActiveRoleNames(): array
    {
        if ($this->is_super_admin) {
            return ['super_admin'];
        }

        return $this->activeRoles()->pluck('role')->toArray();
    }

    /**
     * Dapatkan badge role tertinggi untuk tampilan (SDD §3.3)
     */
    public function getHighestRoleBadge(): string
    {
        if ($this->is_super_admin) {
            return 'Super Admin';
        }

        $roles = $this->getActiveRoleNames();

        if (in_array('ketua_rw', $roles)) return 'Ketua RW';
        if (in_array('ketua_rt', $roles)) return 'Ketua RT';
        if (in_array('wakil_rt', $roles)) return 'Wakil RT';
        if (in_array('sekretaris', $roles)) return 'Sekretaris';
        if (in_array('bendahara', $roles)) return 'Bendahara';

        return 'Warga';
    }
}
