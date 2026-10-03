<?php

namespace App\Http\Controllers;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Services\ScopeAuthorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuditController extends Controller
{
    /**
     * Meja Audit Akuntabilitas sistem-wide.
     * Read-only viewer tindakan administratif dengan filter modul, aksi, tanggal, pencarian, dan pagination.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. RBAC Guard: Warga dilarang membuka Meja Audit (403)
        if (! ScopeAuthorizer::canAccessAuditTrail($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses Meja Audit Akuntabilitas.');
        }

        // 2. Base Query dengan Eager Loading untuk mencegah N+1
        $query = AuditLog::with(['user', 'rt', 'rw', 'target'])->latest('id');

        // 3. Wilayah Scope Filter di Database Layer
        // Super Admin: Global access
        // Ketua RW: Scope wilayah RW + seluruh RT di bawahnya
        // Pengurus RT (Ketua RT, Wakil RT, Sekretaris, Bendahara): Hanya RT sendiri
        if (! $user->is_super_admin) {
            $isRw = $user->hasRole('ketua_rw');

            if ($isRw) {
                $rwId = $user->rw_id ?? $user->rt?->rw_id;
                if ($rwId) {
                    $query->where(function ($q) use ($rwId) {
                        $q->where('rw_id', $rwId)
                          ->orWhereIn('rt_id', function ($sub) use ($rwId) {
                              $sub->select('id')->from('rt')->where('rw_id', $rwId);
                          });
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                // Pengurus RT
                if ($user->rt_id) {
                    $query->where('rt_id', $user->rt_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        // 4. Filter Modul
        $modul = strtolower($request->query('modul', 'semua'));
        if ($modul !== 'semua' && ! empty($modul)) {
            match ($modul) {
                'surat' => $query->where(function ($q) {
                    $q->where('target_type', 'surat_pengajuan')
                      ->orWhere('aksi', 'LIKE', 'SURAT_%');
                }),
                'pengumuman' => $query->where(function ($q) {
                    $q->whereIn('target_type', ['announcements', 'announcement'])
                      ->orWhere('aksi', 'LIKE', 'ANNOUNCEMENT_%');
                }),
                'forum' => $query->where(function ($q) {
                    $q->whereIn('target_type', ['forum_threads', 'forum_thread', 'forum_posts'])
                      ->orWhere('aksi', 'LIKE', 'FORUM_%');
                }),
                'kependudukan' => $query->where(function ($q) {
                    $q->where('target_type', 'users')
                      ->orWhere('aksi', 'LIKE', 'USER_%')
                      ->orWhere('aksi', 'LIKE', 'AUTH_%');
                }),
                'keuangan' => $query->where(function ($q) {
                    $q->where('target_type', 'kas_transaksi')
                      ->orWhere('aksi', 'LIKE', 'KAS_%');
                }),
                'umkm' => $query->where(function ($q) {
                    $q->where('target_type', 'umkm_listings')
                      ->orWhere('aksi', 'LIKE', 'UMKM_%');
                }),
                default => null,
            };
        }

        // 5. Filter Aksi Spesifik
        $aksi = $request->query('aksi', 'semua');
        if ($aksi !== 'semua' && ! empty($aksi)) {
            $query->where('aksi', $aksi);
        }

        // 6. Filter Tanggal
        $tanggal = strtolower($request->query('tanggal', 'semua'));
        if ($tanggal === 'hari_ini') {
            $query->whereDate('created_at', today());
        } elseif ($tanggal === '7_hari') {
            $query->where('created_at', '>=', now()->subDays(7)->startOfDay());
        } elseif ($tanggal === '30_hari') {
            $query->where('created_at', '>=', now()->subDays(30)->startOfDay());
        }

        // 7. Pencarian Teks
        $search = trim($request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('actor_nama', 'LIKE', "%{$search}%")
                  ->orWhere('alasan', 'LIKE', "%{$search}%")
                  ->orWhere('target_id', 'LIKE', "%{$search}%")
                  ->orWhere('aksi', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('nama', 'LIKE', "%{$search}%");
                  });
            });
        }

        // 8. Pagination 20 per halaman
        $logs = $query->paginate(20)->withQueryString();

        // 9. Hak akses melihat technical context (IP Address)
        $canViewTechnicalDetail = $user->is_super_admin || $user->hasRole(['ketua_rw', 'ketua_rt', 'wakil_rt']);

        // Data opsi aksi untuk filter dropdown
        $actionOptions = [
            'SURAT_SUBMITTED' => 'Pengajuan Surat Baru',
            'SURAT_REVIEWED' => 'Membuka / Mereview Surat',
            'SURAT_COMPLETION_REQUESTED' => 'Meminta Kelengkapan Berkas',
            'SURAT_COMPLETION_SUBMITTED' => 'Mengunggah Perbaikan Berkas',
            'SURAT_APPROVED' => 'Menyetujui Surat',
            'SURAT_REJECTED' => 'Menolak Surat',
            'ANNOUNCEMENT_CREATED' => 'Menerbitkan Pengumuman',
            'ANNOUNCEMENT_UPDATED' => 'Memperbarui Pengumuman',
            'ANNOUNCEMENT_DEACTIVATED' => 'Menonaktifkan Pengumuman',
            'ANNOUNCEMENT_PINNED' => 'Menyematkan Pengumuman',
            'ANNOUNCEMENT_UNPINNED' => 'Melepas Sematan Pengumuman',
            'ANNOUNCEMENT_FORUM_LINKED' => 'Hubungkan Pengumuman ke Forum',
            'FORUM_THREAD_PINNED' => 'Menyematkan Thread Forum',
            'FORUM_THREAD_CLOSED' => 'Menutup Thread Forum',
            'FORUM_THREAD_DELETED' => 'Menghapus Thread Forum',
            'AUTH_ACCOUNT_ACTIVATED' => 'Aktivasi Akun Warga',
        ];

        return view('admin.audit.index', compact(
            'logs',
            'modul',
            'aksi',
            'tanggal',
            'search',
            'canViewTechnicalDetail',
            'actionOptions'
        ));
    }
}
