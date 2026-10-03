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

    /**
     * Dapatkan canonical role identifier tertinggi untuk audit snapshot (SDD §3.3)
     */
    public function getHighestRoleCanonical(): string
    {
        if ($this->is_super_admin) {
            return 'super_admin';
        }

        $roles = $this->getActiveRoleNames();

        if (in_array('ketua_rw', $roles)) return 'ketua_rw';
        if (in_array('ketua_rt', $roles)) return 'ketua_rt';
        if (in_array('wakil_rt', $roles)) return 'wakil_rt';
        if (in_array('sekretaris', $roles)) return 'sekretaris';
        if (in_array('bendahara', $roles)) return 'bendahara';

        return 'warga';
    }

    /**
     * Memeriksa apakah user memiliki hak akses ke scope wilayah tertentu.
     */
    public function canAccessScope(string $scopeType, int|string $scopeId): bool
    {
        return \App\Services\ScopeAuthorizer::canAccess($this, $scopeType, $scopeId);
    }

    /**
     * Mengambil array snapshot role aktif untuk dikunci secara permanen pada post/thread forum.
     * Mengikuti hierarki SDD §3.3: Ketua RW > Ketua RT = Wakil RT > Sekretaris > Bendahara > Warga
     *
     * @return array<string>
     */
    public function getRoleSnapshot(): array
    {
        if ($this->is_super_admin) {
            return ['Super Admin'];
        }

        $roles = $this->getActiveRoleNames();
        if (empty($roles)) {
            return ['Warga'];
        }

        $hierarchy = [
            'ketua_rw' => 'Ketua RW',
            'ketua_rt' => 'Ketua RT',
            'wakil_rt' => 'Wakil RT',
            'sekretaris' => 'Sekretaris',
            'bendahara' => 'Bendahara',
            'warga' => 'Warga',
        ];

        $snapshot = [];
        foreach ($hierarchy as $roleKey => $badgeLabel) {
            if (in_array($roleKey, $roles)) {
                $snapshot[] = $badgeLabel;
            }
        }

        return ! empty($snapshot) ? array_values(array_unique($snapshot)) : ['Warga'];
    }
}
