<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Super Admin memiliki akses menyeluruh
        if ($user->is_super_admin) {
            return $next($request);
        }

        // Penyetaraan mutlak sesuai AGENTS.md & SDD: wakil_rt = ketua_rt
        if (in_array('ketua_rt', $roles) && !in_array('wakil_rt', $roles)) {
            $roles[] = 'wakil_rt';
        }
        if (in_array('wakil_rt', $roles) && !in_array('ketua_rt', $roles)) {
            $roles[] = 'ketua_rt';
        }

        if (!$user->hasRole($roles)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk fitur atau halaman ini.');
        }

        return $next($request);
    }
}
