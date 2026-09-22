<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Super Admin memiliki akses lintas-seluruh tenant
        if ($user->is_super_admin) {
            return;
        }

        // Jika user adalah Ketua RW, mereka bisa melihat data lintas RT di RW mereka
        if ($user->hasRole('ketua_rw') && $user->rw_id) {
            // Jika model berelasi dengan RT, kita filter berdasar rt.rw_id
            // Namun untuk model direct rt_id, kita pastikan RT-nya ada di RW tersebut
            $builder->whereHas('rt', function ($query) use ($user) {
                $query->where('rw_id', $user->rw_id);
            });
            return;
        }

        // Untuk Warga dan Pengurus RT (Ketua RT, Wakil RT, Sekretaris, Bendahara)
        if ($user->rt_id) {
            $builder->where($model->qualifyColumn('rt_id'), $user->rt_id);
        }
    }
}
