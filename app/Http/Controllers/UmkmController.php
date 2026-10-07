<?php

namespace App\Http\Controllers;

use App\Constants\AuditAction;
use App\Http\Requests\StoreUmkmListingRequest;
use App\Http\Requests\UpdateUmkmListingRequest;
use App\Models\UmkmListing;
use App\Services\AuditLogger;
use App\Services\ScopeAuthorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UmkmController extends Controller
{
    /**
     * Menyiapkan data etalase publik (cakupan se-RW), usaha saya, dan meja kurasi UMKM.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $targetRtId = (int) ($user?->rt_id ?? 0);
        $userRwId = (int) ($user?->rw_id ?? $user?->rt?->rw_id ?? 0);

        // 1. Etalase publik: listing disetujui dalam cakupan RW (atau global untuk Super Admin)
        $query = UmkmListing::withoutGlobalScopes()
            ->disetujui()
            ->with(['user', 'rt']);

        if (! $user?->is_super_admin) {
            $query->whereHas('rt', function ($sub) use ($userRwId) {
                $sub->where('rw_id', $userRwId);
            });
        }

        // Filter RT di dalam RW (Anti-spoofing)
        if ($request->filled('rt_id') && $request->input('rt_id') !== 'semua') {
            $requestedRtId = (int) $request->input('rt_id');
            if ($user?->is_super_admin) {
                $query->where('rt_id', $requestedRtId);
            } else {
                $isRtInUserRw = \App\Models\Rt::where('id', $requestedRtId)->where('rw_id', $userRwId)->exists();
                if ($isRtInUserRw) {
                    $query->where('rt_id', $requestedRtId);
                } else {
                    // Manipulasi cross-RW: kembalikan 0 hasil secara aman
                    $query->whereRaw('1 = 0');
                }
            }
        }

        // Filter pencarian: nama atau deskripsi
        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('deskripsi', 'like', "%{$q}%");
            });
        }

        // Filter kategori: barang atau jasa
        if ($request->filled('kategori') && in_array($request->input('kategori'), ['barang', 'jasa'], true)) {
            $query->where('kategori', $request->input('kategori'));
        }

        $etalase = $query->latest()->paginate(12)->withQueryString();

        // 2. Usaha Saya: listing milik user yang sedang login
        $usahaSaya = $user ? UmkmListing::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->with(['user', 'rt', 'takedownBy'])
            ->latest()
            ->get() : collect();

        // 3. Meja Kurasi: hanya untuk reviewer yang sah di RT-nya
        $canReview = $user && ScopeAuthorizer::canReviewUmkm($user, $targetRtId);
        $mejaKurasi = $canReview
            ? UmkmListing::withoutGlobalScopes()
                ->when(! $user->is_super_admin || $targetRtId > 0, fn ($q) => $q->where('rt_id', $targetRtId))
                ->menunggu()
                ->with(['user', 'rt'])
                ->latest()
                ->get()
            : collect();

        // Daftar RT untuk dropdown filter etalase
        $daftarRt = $userRwId > 0
            ? \App\Models\Rt::where('rw_id', $userRwId)->orderBy('nomor_rt')->get()
            : \App\Models\Rt::orderBy('nomor_rt')->get();

        $activeKategori = $request->input('kategori', 'semua');
        $activeRt = $request->input('rt_id', 'semua');
        $searchQuery = $request->input('q', '');

        // Fallback JSON jika Blade belum tersedia
        if (! view()->exists('umkm.index')) {
            return response()->json([
                'etalase' => $etalase,
                'usaha_saya' => $usahaSaya,
                'meja_kurasi' => $mejaKurasi,
                'can_review' => $canReview,
                'active_kategori' => $activeKategori,
                'active_rt' => $activeRt,
                'search_query' => $searchQuery,
                'daftar_rt' => $daftarRt,
            ]);
        }

        return view('umkm.index', compact('etalase', 'usahaSaya', 'mejaKurasi', 'canReview', 'activeKategori', 'activeRt', 'searchQuery', 'daftarRt'));
    }

    /**
     * Menyimpan listing UMKM baru diajukan oleh warga (selalu berstatus MENUNGGU).
     */
    public function store(StoreUmkmListingRequest $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->rt_id, 403, 'Data wilayah Anda tidak valid.');

        // Prasyarat bisnis: nomor HP wajib terisi di profil
        if (empty(trim((string) $user->no_hp))) {
            return back()->withErrors([
                'no_hp' => 'Nomor WhatsApp wajib diisi di profil sebelum membuka listing UMKM.',
            ])->withInput();
        }

        $validated = $request->validated();

        // Upload foto jika ada
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $rtId = (int) $user->rt_id;
            $userId = (int) $user->id;
            $fotoPath = $request->file('foto')->store("umkm/{$rtId}/{$userId}", 'public');
        }

        // Server adalah sumber kebenaran: user_id & rt_id diambil dari Auth user
        UmkmListing::withoutGlobalScopes()->create([
            'user_id' => (int) $user->id,
            'rt_id' => (int) $user->rt_id,
            'kategori' => $validated['kategori'],
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'],
            'harga' => $validated['kategori'] === 'barang' ? ($validated['harga'] ?? null) : null,
            'foto_url' => $fotoPath,
            'template_pesan_wa' => $validated['template_pesan_wa'] ?? null,
            'status' => UmkmListing::STATUS_MENUNGGU,
            'alasan_tolak' => null,
            'reviewed_by' => null,
        ]);

        return redirect()->route('umkm.index')
            ->with('success', 'Listing UMKM berhasil diajukan dan sedang menunggu kurasi pengurus.')
            ->with('tab', 'usaha-saya');
    }

    /**
     * Memperbarui / merevisi listing UMKM.
     * Perubahan substantif atau revisi setelah DITOLAK/DITAKEDOWN/NONAKTIF mengembalikan status ke MENUNGGU.
     */
    public function update(UpdateUmkmListingRequest $request, int|string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $listing = UmkmListing::withoutGlobalScopes()->findOrFail($id);

        // Otorisasi kelola listing
        abort_unless(ScopeAuthorizer::canManageUmkm($user, $listing), 403, 'Anda tidak berhak mengelola listing ini.');

        $validated = $request->validated();

        // Upload foto baru jika ada
        $fotoPath = $listing->foto_url;
        $isFotoChanged = false;
        if ($request->hasFile('foto')) {
            $newFotoPath = $request->file('foto')->store("umkm/{$listing->rt_id}/{$listing->user_id}", 'public');
            $oldFotoPath = $listing->foto_url;
            $fotoPath = $newFotoPath;
            $isFotoChanged = true;

            // Hapus foto lama jika ada
            if ($oldFotoPath && Storage::disk('public')->exists($oldFotoPath)) {
                Storage::disk('public')->delete($oldFotoPath);
            }
        }

        // Deteksi perubahan substantif
        $isSubstantiveChange = $listing->nama !== $validated['nama']
            || $listing->deskripsi !== $validated['deskripsi']
            || (string) $listing->harga !== (string) ($validated['harga'] ?? null)
            || $listing->kategori !== $validated['kategori']
            || (string) $listing->template_pesan_wa !== (string) ($validated['template_pesan_wa'] ?? null)
            || $isFotoChanged;

        $status = $listing->status;
        $reviewedBy = $listing->reviewed_by;
        $alasanTolak = $listing->alasan_tolak;
        $takedownBy = $listing->takedown_by;
        $alasanTakedown = $listing->alasan_takedown;
        $takedownAt = $listing->takedown_at;

        // Aturan status revisi:
        // 1. Listing DITOLAK, DITAKEDOWN, atau NONAKTIF setelah diedit kembali ke MENUNGGU
        // 2. Listing DISETUJUI jika mengalami perubahan substantif kembali ke MENUNGGU
        if ($isSubstantiveChange || in_array($listing->status, [UmkmListing::STATUS_DITOLAK, UmkmListing::STATUS_DITAKEDOWN, UmkmListing::STATUS_NONAKTIF], true)) {
            $status = UmkmListing::STATUS_MENUNGGU;
            $reviewedBy = null;
            $alasanTolak = null;
            $takedownBy = null;
            $alasanTakedown = null;
            $takedownAt = null;
        }

        $listing->update([
            'kategori' => $validated['kategori'],
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'],
            'harga' => $validated['kategori'] === 'barang' ? ($validated['harga'] ?? null) : null,
            'foto_url' => $fotoPath,
            'template_pesan_wa' => $validated['template_pesan_wa'] ?? null,
            'status' => $status,
            'alasan_tolak' => $alasanTolak,
            'reviewed_by' => $reviewedBy,
            'takedown_by' => $takedownBy,
            'alasan_takedown' => $alasanTakedown,
            'takedown_at' => $takedownAt,
        ]);

        return redirect()->route('umkm.index')
            ->with('success', 'Listing UMKM berhasil diperbarui.')
            ->with('tab', 'usaha-saya');
    }

    /**
     * Menonaktifkan listing UMKM milik warga sendiri (ditarik dari etalase).
     */
    public function nonaktifkan(int|string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $listing = UmkmListing::withoutGlobalScopes()->findOrFail($id);

        // Hanya pemilik listing atau super admin yang boleh menonaktifkan
        abort_unless($user->is_super_admin || (int) $user->id === (int) $listing->user_id, 403, 'Anda tidak berhak menonaktifkan listing ini.');

        if (! in_array($listing->status, [UmkmListing::STATUS_DISETUJUI, UmkmListing::STATUS_MENUNGGU], true)) {
            return back()->with('error', 'Hanya listing yang sedang aktif atau menunggu yang dapat dinonaktifkan.');
        }

        $listing->update([
            'status' => UmkmListing::STATUS_NONAKTIF,
        ]);

        return redirect()->route('umkm.index')
            ->with('success', "Listing usaha \"{$listing->nama}\" berhasil dinonaktifkan dari etalase.")
            ->with('tab', 'usaha-saya');
    }

    /**
     * Mengajukan pengaktifan kembali listing yang berstatus NONAKTIF.
     * Sesuai aturan: NONAKTIF -> MENUNGGU (wajib kurasi ulang oleh RT).
     * Listing DITAKEDOWN TIDAK BOLEH langsung aktifkan kembali (wajib lewat form revisi).
     */
    public function aktifkan(int|string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $listing = UmkmListing::withoutGlobalScopes()->findOrFail($id);

        // Hanya pemilik listing atau super admin
        abort_unless($user->is_super_admin || (int) $user->id === (int) $listing->user_id, 403, 'Anda tidak berhak mengaktifkan listing ini.');

        if ($listing->status === UmkmListing::STATUS_DITAKEDOWN) {
            abort(422, 'Listing yang di-takedown pengurus harus diperbaiki melalui form revisi sebelum dapat diajukan kembali.');
        }

        if ($listing->status !== UmkmListing::STATUS_NONAKTIF) {
            return back()->with('error', 'Hanya listing dengan status NONAKTIF yang dapat diaktifkan kembali.');
        }

        // Transisi: NONAKTIF -> MENUNGGU (wajib kurasi RT)
        $listing->update([
            'status' => UmkmListing::STATUS_MENUNGGU,
            'reviewed_by' => null,
            'alasan_tolak' => null,
        ]);

        return redirect()->route('umkm.index')
            ->with('success', "Listing usaha \"{$listing->nama}\" berhasil diajukan kembali dan sedang menunggu kurasi pengurus RT.")
            ->with('tab', 'usaha-saya');
    }

    /**
     * Men-takedown listing UMKM oleh pengurus berwenang (RT / RW / Super Admin) dengan alasan wajib.
     * Terintegrasi secara atomik dengan AuditLogger.
     */
    public function takedown(Request $request, int|string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $listing = UmkmListing::withoutGlobalScopes()->with(['rt'])->findOrFail($id);

        abort_unless(ScopeAuthorizer::canTakedownUmkm($user, $listing), 403, 'Anda tidak memiliki wewenang untuk men-takedown listing UMKM ini.');

        $validated = $request->validate([
            'alasan_takedown' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'alasan_takedown.required' => 'Alasan takedown wajib diisi.',
            'alasan_takedown.min' => 'Alasan takedown minimal 5 karakter.',
            'alasan_takedown.max' => 'Alasan takedown maksimal 1000 karakter.',
        ]);

        DB::transaction(function () use ($listing, $user, $validated) {
            $sebelum = [
                'status' => $listing->status,
                'takedown_by' => $listing->takedown_by,
                'alasan_takedown' => $listing->alasan_takedown,
                'takedown_at' => $listing->takedown_at?->toIso8601String(),
            ];

            $now = now();
            $listing->update([
                'status' => UmkmListing::STATUS_DITAKEDOWN,
                'takedown_by' => $user->id,
                'alasan_takedown' => $validated['alasan_takedown'],
                'takedown_at' => $now,
            ]);

            $sesudah = [
                'status' => UmkmListing::STATUS_DITAKEDOWN,
                'takedown_by' => $user->id,
                'alasan_takedown' => $validated['alasan_takedown'],
                'takedown_at' => $now->toIso8601String(),
            ];

            AuditLogger::log(
                aksi: AuditAction::UMKM_LISTING_TAKEDOWN,
                targetType: 'umkm_listings',
                targetId: (int) $listing->id,
                sebelum: $sebelum,
                sesudah: $sesudah,
                alasan: $validated['alasan_takedown'],
                rtId: (int) $listing->rt_id,
                rwId: (int) ($listing->rt?->rw_id ?? $user->rw_id ?? 0),
                actor: $user
            );
        });

        return redirect()->route('umkm.index')
            ->with('success', "Listing UMKM \"{$listing->nama}\" berhasil di-takedown dari etalase warga.")
            ->with('tab', 'etalase');
    }

    /**
     * Menghapus listing UMKM dan membersihkan berkas foto terkait jika ada.
     */
    public function destroy(int|string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $listing = UmkmListing::withoutGlobalScopes()->findOrFail($id);

        abort_unless(ScopeAuthorizer::canManageUmkm($user, $listing), 403, 'Anda tidak berhak menghapus listing ini.');

        $fotoPath = $listing->foto_url;

        $listing->delete();

        // Bersihkan berkas foto jika ada
        if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
            Storage::disk('public')->delete($fotoPath);
        }

        return redirect()->route('umkm.index')
            ->with('success', 'Listing UMKM berhasil dihapus.')
            ->with('tab', 'usaha-saya');
    }

    /**
     * Menyetujui listing UMKM (khusus reviewer dalam scope RT).
     * Terintegrasi secara atomik dengan AuditLogger.
     */
    public function approve(int|string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $listing = UmkmListing::withoutGlobalScopes()->findOrFail($id);

        abort_unless(ScopeAuthorizer::canReviewUmkm($user, $listing), 403, 'Anda tidak memiliki wewenang untuk menyetujui listing UMKM ini.');

        if ($listing->status !== UmkmListing::STATUS_MENUNGGU) {
            abort(422, 'Hanya listing dengan status MENUNGGU yang dapat disetujui.');
        }

        DB::transaction(function () use ($listing, $user) {
            $sebelum = [
                'status' => $listing->status,
                'reviewed_by' => $listing->reviewed_by,
            ];

            $listing->update([
                'status' => UmkmListing::STATUS_DISETUJUI,
                'reviewed_by' => $user->id,
                'alasan_tolak' => null,
            ]);

            $sesudah = [
                'status' => UmkmListing::STATUS_DISETUJUI,
                'reviewed_by' => $user->id,
            ];

            AuditLogger::log(
                aksi: AuditAction::UMKM_LISTING_APPROVED,
                targetType: 'umkm_listings',
                targetId: $listing->id,
                sebelum: $sebelum,
                sesudah: $sesudah,
                alasan: null,
                rtId: (int) $listing->rt_id,
                actor: $user
            );
        });

        return redirect()->route('umkm.index')
            ->with('success', "Listing UMKM \"{$listing->nama}\" berhasil disetujui.")
            ->with('tab', 'kurasi');
    }

    /**
     * Menolak listing UMKM dengan alasan penolakan wajib (khusus reviewer dalam scope RT).
     * Terintegrasi secara atomik dengan AuditLogger.
     */
    public function tolak(Request $request, int|string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $listing = UmkmListing::withoutGlobalScopes()->findOrFail($id);

        abort_unless(ScopeAuthorizer::canReviewUmkm($user, $listing), 403, 'Anda tidak memiliki wewenang untuk menolak listing UMKM ini.');

        if ($listing->status !== UmkmListing::STATUS_MENUNGGU) {
            abort(422, 'Hanya listing dengan status MENUNGGU yang dapat ditolak.');
        }

        $validated = $request->validate([
            'alasan_tolak' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'alasan_tolak.required' => 'Alasan penolakan wajib diisi.',
            'alasan_tolak.min' => 'Alasan penolakan minimal 5 karakter.',
            'alasan_tolak.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ]);

        DB::transaction(function () use ($listing, $user, $validated) {
            $sebelum = [
                'status' => $listing->status,
                'reviewed_by' => $listing->reviewed_by,
                'alasan_tolak' => $listing->alasan_tolak,
            ];

            $listing->update([
                'status' => UmkmListing::STATUS_DITOLAK,
                'reviewed_by' => $user->id,
                'alasan_tolak' => $validated['alasan_tolak'],
            ]);

            $sesudah = [
                'status' => UmkmListing::STATUS_DITOLAK,
                'reviewed_by' => $user->id,
                'alasan_tolak' => $validated['alasan_tolak'],
            ];

            AuditLogger::log(
                aksi: AuditAction::UMKM_LISTING_REJECTED,
                targetType: 'umkm_listings',
                targetId: $listing->id,
                sebelum: $sebelum,
                sesudah: $sesudah,
                alasan: $validated['alasan_tolak'],
                rtId: (int) $listing->rt_id,
                actor: $user
            );
        });

        return redirect()->route('umkm.index')
            ->with('success', "Listing UMKM \"{$listing->nama}\" telah ditolak dengan alasan resmi.")
            ->with('tab', 'kurasi');
    }

    /**
     * Memperbarui nomor WhatsApp milik user yang sedang terautentikasi.
     */
    public function updateNoHp(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        // Bersihkan spasi atau dash dari input nomor
        $cleanNoHp = preg_replace('/[^0-9+]/', '', (string) $request->input('no_hp'));
        $request->merge(['no_hp' => $cleanNoHp]);

        $validated = $request->validate([
            'no_hp' => [
                'required',
                'string',
                'min:9',
                'max:20',
                'regex:/^(\+62|62|08)[0-9]{7,15}$/',
            ],
        ], [
            'no_hp.required' => 'Nomor WhatsApp wajib diisi.',
            'no_hp.min' => 'Nomor WhatsApp minimal 9 digit.',
            'no_hp.max' => 'Nomor WhatsApp maksimal 20 digit.',
            'no_hp.regex' => 'Format nomor WhatsApp tidak valid. Gunakan awalan 08 atau 62 (contoh: 081234567890).',
        ]);

        // Server kebenaran: hanya update profil user yang login
        $user->update([
            'no_hp' => $validated['no_hp'],
        ]);

        return back()
            ->with('success', 'Nomor WhatsApp profil berhasil diperbarui.')
            ->with('tab', 'usaha-saya')
            ->with('open_create_modal', true);
    }
}
