<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantScope
{
    /**
     * Pastikan konteks tenant aktif dan lisensi klien valid.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Set context tenant pada request
            $request->attributes->set('tenant_rt_id', $user->rt_id);
            $request->attributes->set('tenant_rw_id', $user->rw_id ?? $user->rt?->rw_id);

            // Cek status lisensi klien (Kecamatan/Kelurahan) jika bukan super admin
            if (!$user->is_super_admin) {
                $klien = $user->rt?->rw?->klien ?? $user->rw?->klien;
                if ($klien && $klien->status !== 'aktif') {
                    abort(403, 'Akses Dibekukan: Lisensi sistem untuk wilayah Anda sedang nonaktif atau kedaluwarsa.');
                }
            }
        }

        return $next($request);
    }
}
