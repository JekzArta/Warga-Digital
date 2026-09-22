<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait TenantScoped
{
    /**
     * Boot the trait to attach the global scope and auto-fill rt_id on creation.
     */
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->rt_id) && Auth::check() && Auth::user()->rt_id) {
                $model->rt_id = Auth::user()->rt_id;
            }
        });
    }
}
