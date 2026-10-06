<?php

namespace App\Http\Controllers;

use App\Constants\AuditAction;
use App\Models\GaleriAlbum;
use App\Models\GaleriFoto;
use App\Models\Rt;
use App\Services\AuditLogger;
use App\Services\ScopeAuthorizer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GaleriController extends Controller
{
    /**
     * Tampilkan browser album galeri kegiatan.
     * Scope:
     * - Warga/Bendahara/Pengurus RT: album RT sendiri
     * - Ketua RW: album seluruh RT di RW-nya (dengan filter ?rt_id=)
     * - Super Admin: global
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Cek hak akses dasar melihat galeri
        if ($user->rt_id && ! ScopeAuthorizer::canViewGaleri($user, $user->rt_id)) {
            abort(403, 'Anda tidak memiliki hak akses melihat Galeri Kegiatan.');
        }

        // Query dasar album dengan eager loading coverFoto dan hitung foto (Zero N+1)
        $query = GaleriAlbum::with(['coverFoto', 'creator', 'rt'])
            ->withCount('fotos')
            ->latest('tanggal_kegiatan')
            ->latest('id');

        $userRwId = $user->rw_id ?? $user->rt?->rw_id;

        // Filter RT (khusus jika ada request ?rt_id=)
        if ($request->filled('rt_id') && $request->input('rt_id') !== 'semua') {
            $requestedRtId = (int) $request->input('rt_id');

            if ($user->is_super_admin) {
                $query->where('rt_id', $requestedRtId);
            } elseif ($user->hasRole('ketua_rw') && $userRwId) {
                // Anti-spoofing Ketua RW: pastikan RT benar-benar berada di RW binaannya
                $isRtInUserRw = Rt::where('id', $requestedRtId)->where('rw_id', $userRwId)->exists();
                if ($isRtInUserRw) {
                    $query->where('rt_id', $requestedRtId);
                } else {
                    // Manipulasi cross-RW: kembalikan 0 hasil secara aman
                    $query->whereRaw('1 = 0');
                }
            } else {
                // Untuk Warga dan Pengurus RT biasa: tolak jika mencoba intip RT lain
                if ((int) $user->rt_id !== $requestedRtId) {
                    abort(403, 'Anda tidak memiliki hak akses melihat galeri RT lain.');
                }
            }
        }

        $albums = $query->paginate(12)->withQueryString();

        // Cek hak kelola:
        // Pengurus RT dapat mengelola di RT-nya; Super Admin global
        $canManage = $user->rt_id
            ? ScopeAuthorizer::canManageGaleri($user, $user->rt_id)
            : (bool) $user->is_super_admin;

        // Daftar RT untuk filter (tersedia bagi Ketua RW & Super Admin)
        $daftarRt = $userRwId
            ? Rt::where('rw_id', $userRwId)->orderBy('nomor_rt')->get()
            : Rt::orderBy('nomor_rt')->get();

        $activeRt = $request->input('rt_id', 'semua');

        // Fallback JSON jika Blade belum dibuat (Tahap 2)
        if (! view()->exists('galeri.index')) {
            return response()->json([
                'success' => true,
                'albums' => $albums,
                'can_manage' => $canManage,
                'active_rt' => $activeRt,
                'daftar_rt' => $daftarRt,
            ]);
        }

        return view('galeri.index', compact('albums', 'canManage', 'activeRt', 'daftarRt'));
    }

    /**
     * Tampilkan detail isi album kegiatan beserta foto-fotonya.
     */
    public function show(int $albumId)
    {
        $user = Auth::user();

        $album = GaleriAlbum::findOrFail($albumId);

        // Otorisasi hak melihat album
        if (! ScopeAuthorizer::canViewGaleri($user, $album->rt_id)) {
            abort(403, 'Anda tidak memiliki hak akses melihat album kegiatan ini.');
        }

        $album->load(['creator', 'rt']);

        $fotos = $album->fotos()
            ->with('uploader')
            ->oldest('created_at')
            ->oldest('id')
            ->paginate(24);

        $canManage = ScopeAuthorizer::canManageGaleri($user, $album->rt_id);

        if (! view()->exists('galeri.show')) {
            return response()->json([
                'success' => true,
                'album' => $album,
                'fotos' => $fotos,
                'can_manage' => $canManage,
            ]);
        }

        return view('galeri.show', compact('album', 'fotos', 'canManage'));
    }

    /**
     * Buat album kegiatan baru.
     */
    public function storeAlbum(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Tentukan RT target secara server-side
        $targetRtId = $user->is_super_admin && $request->filled('rt_id')
            ? (int) $request->input('rt_id')
            : (int) $user->rt_id;

        if (! $targetRtId || ! ScopeAuthorizer::canManageGaleri($user, $targetRtId)) {
            abort(403, 'Anda tidak memiliki hak akses membuat album kegiatan di RT ini.');
        }

        $validated = $request->validate([
            'judul' => 'required|string|max:150',
            'tanggal_kegiatan' => 'required|date',
            'deskripsi' => 'nullable|string|max:1000',
        ], [
            'judul.required' => 'Judul album kegiatan wajib diisi.',
            'judul.max' => 'Judul album maksimal 150 karakter.',
            'tanggal_kegiatan.required' => 'Tanggal pelaksanaan kegiatan wajib diisi.',
            'tanggal_kegiatan.date' => 'Format tanggal kegiatan tidak valid.',
            'deskripsi.max' => 'Deskripsi kegiatan maksimal 1000 karakter.',
        ]);

        $album = DB::transaction(function () use ($validated, $targetRtId, $user) {
            $album = GaleriAlbum::create([
                'rt_id' => $targetRtId,
                'judul' => $validated['judul'],
                'tanggal_kegiatan' => $validated['tanggal_kegiatan'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'created_by' => $user->id,
            ]);

            AuditLogger::log(
                AuditAction::GALERI_ALBUM_CREATED,
                'galeri_album',
                $album->id,
                null,
                [
                    'judul' => $album->judul,
                    'tanggal_kegiatan' => Carbon::parse($album->tanggal_kegiatan)->format('Y-m-d'),
                    'deskripsi' => $album->deskripsi,
                    'rt_id' => $album->rt_id,
                ],
                null,
                $album->rt_id
            );

            return $album;
        });

        return redirect()->route('galeri.show', $album->id)
            ->with('success', 'Album kegiatan berhasil dibuat.');
    }

    /**
     * Perbarui metadata info album kegiatan (judul, tanggal, deskripsi).
     */
    public function updateAlbum(Request $request, int $albumId): RedirectResponse
    {
        $user = Auth::user();

        $album = GaleriAlbum::findOrFail($albumId);

        if (! ScopeAuthorizer::canManageGaleri($user, $album->rt_id)) {
            abort(403, 'Anda tidak memiliki hak akses mengubah info album kegiatan ini.');
        }

        $validated = $request->validate([
            'judul' => 'required|string|max:150',
            'tanggal_kegiatan' => 'required|date',
            'deskripsi' => 'nullable|string|max:1000',
        ], [
            'judul.required' => 'Judul album kegiatan wajib diisi.',
            'judul.max' => 'Judul album maksimal 150 karakter.',
            'tanggal_kegiatan.required' => 'Tanggal pelaksanaan kegiatan wajib diisi.',
            'tanggal_kegiatan.date' => 'Format tanggal kegiatan tidak valid.',
            'deskripsi.max' => 'Deskripsi kegiatan maksimal 1000 karakter.',
        ]);

        $oldSnapshot = [
            'judul' => $album->judul,
            'tanggal_kegiatan' => $album->tanggal_kegiatan?->format('Y-m-d'),
            'deskripsi' => $album->deskripsi,
        ];

        DB::transaction(function () use ($album, $validated, $oldSnapshot) {
            $album->update([
                'judul' => $validated['judul'],
                'tanggal_kegiatan' => $validated['tanggal_kegiatan'],
                'deskripsi' => $validated['deskripsi'] ?? null,
            ]);

            AuditLogger::log(
                AuditAction::GALERI_ALBUM_UPDATED,
                'galeri_album',
                $album->id,
                $oldSnapshot,
                [
                    'judul' => $album->judul,
                    'tanggal_kegiatan' => Carbon::parse($album->tanggal_kegiatan)->format('Y-m-d'),
                    'deskripsi' => $album->deskripsi,
                ],
                null,
                $album->rt_id
            );
        });

        return redirect()->route('galeri.show', $album->id)
            ->with('success', 'Info album kegiatan berhasil diperbarui.');
    }

    /**
     * Hapus album kegiatan beserta seluruh foto anaknya dan direktori fisiknya.
     * Pola: DB-first transactional deletion + post-commit physical storage cleanup.
     */
    public function destroyAlbum(Request $request, int $albumId): RedirectResponse
    {
        $user = Auth::user();

        $album = GaleriAlbum::findOrFail($albumId);

        if (! ScopeAuthorizer::canManageGaleri($user, $album->rt_id)) {
            abort(403, 'Anda tidak memiliki hak akses menghapus album kegiatan ini.');
        }

        // Kumpulkan metadata sebelum data DB dihapus
        $targetAlbumId = $album->id;
        $targetRtId = $album->rt_id;
        $judul = $album->judul;
        $tanggal = $album->tanggal_kegiatan?->format('Y-m-d');
        $fotoCount = $album->fotos()->count();

        // Phase 1: Transactional Database Deletion
        DB::transaction(function () use ($album, $targetAlbumId, $targetRtId, $judul, $tanggal, $fotoCount) {
            AuditLogger::log(
                AuditAction::GALERI_ALBUM_DELETED,
                'galeri_album',
                $targetAlbumId,
                [
                    'judul' => $judul,
                    'tanggal_kegiatan' => $tanggal,
                    'rt_id' => $targetRtId,
                    'foto_terhapus_count' => $fotoCount,
                ],
                null,
                null,
                $targetRtId
            );

            $album->delete();
        });

        // Phase 2: Post-Commit Physical Storage Cleanup
        try {
            Storage::disk('public')->deleteDirectory("galeri/{$targetAlbumId}");
        } catch (\Throwable $e) {
            Log::warning("Gagal membersihkan direktori fisik galeri/{$targetAlbumId}: " . $e->getMessage());
        }

        return redirect()->route('galeri.index')
            ->with('success', 'Album kegiatan beserta seluruh fotonya berhasil dihapus.');
    }

    /**
     * Unggah multi-foto (maks 10 foto) ke dalam album kegiatan.
     * Mencegah partial failure: rollback DB dan bersihkan physical files jika operasi gagal.
     */
    public function storeFotos(Request $request, int $albumId): RedirectResponse
    {
        $user = Auth::user();

        $album = GaleriAlbum::findOrFail($albumId);

        if (! ScopeAuthorizer::canManageGaleri($user, $album->rt_id)) {
            abort(403, 'Anda tidak memiliki hak akses mengunggah foto ke album ini.');
        }

        $request->validate([
            'fotos' => 'required|array|min:1|max:10',
            'fotos.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:3072',
        ], [
            'fotos.required' => 'Pilih minimal satu foto untuk diunggah.',
            'fotos.array' => 'Format berkas unggahan tidak valid.',
            'fotos.min' => 'Pilih minimal satu foto untuk diunggah.',
            'fotos.max' => 'Maksimal 10 foto dalam satu kali unggahan.',
            'fotos.*.required' => 'Setiap berkas foto wajib valid.',
            'fotos.*.image' => 'Berkas harus berupa gambar.',
            'fotos.*.mimes' => 'Format gambar harus JPEG, JPG, PNG, atau WEBP.',
            'fotos.*.max' => 'Ukuran setiap gambar maksimal 3MB.',
        ]);

        $uploadedPaths = [];

        DB::beginTransaction();
        try {
            foreach ($request->file('fotos') as $file) {
                // Simpan ke storage dengan hash name unik
                $path = $file->store("galeri/{$album->id}", 'public');
                $uploadedPaths[] = $path;

                GaleriFoto::create([
                    'album_id' => $album->id,
                    'foto_url' => $path,
                    'uploaded_by' => $user->id,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            // Bersihkan file fisik yang sudah terlanjur tersimpan di disk sebelum terjadi kegagalan
            foreach ($uploadedPaths as $path) {
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Throwable $storageEx) {
                    Log::warning("Gagal membersihkan file partial upload {$path}: " . $storageEx->getMessage());
                }
            }

            Log::error("Gagal mengunggah foto ke album {$album->id}: " . $e->getMessage());

            return redirect()->back()
                ->withErrors(['fotos' => 'Terjadi kesalahan saat menyimpan foto. Operasi dibatalkan secara aman.']);
        }

        $count = count($uploadedPaths);

        return redirect()->route('galeri.show', $album->id)
            ->with('success', "{$count} foto dokumentasi berhasil diunggah.");
    }

    /**
     * Hapus satu foto dari album kegiatan.
     */
    public function destroyFoto(Request $request, int $albumId, int $fotoId): RedirectResponse
    {
        $user = Auth::user();

        $album = GaleriAlbum::findOrFail($albumId);

        if (! ScopeAuthorizer::canManageGaleri($user, $album->rt_id)) {
            abort(403, 'Anda tidak memiliki hak akses menghapus foto dari album ini.');
        }

        $foto = GaleriFoto::where('album_id', $album->id)->findOrFail($fotoId);

        $path = $foto->foto_url;

        DB::transaction(function () use ($foto) {
            $foto->delete();
        });

        // Hapus berkas fisik foto
        if ($path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                Log::warning("Gagal menghapus berkas fisik foto {$path}: " . $e->getMessage());
            }
        }

        return redirect()->route('galeri.show', $album->id)
            ->with('success', 'Foto dokumentasi berhasil dihapus.');
    }
}
