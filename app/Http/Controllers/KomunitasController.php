<?php

namespace App\Http\Controllers;

use App\Constants\AuditAction;
use App\Events\ChatMessageSent;
use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\ChatMessage;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\KalenderEvent;
use App\Services\AuditLogger;
use App\Services\ScopeAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class KomunitasController extends Controller
{
    /**
     * Hub utama Ruang Komunitas (3 Layer: Pengumuman, Chat Bebas, Forum Warga).
     * Mendukung peralihan 2 scope paralel (RT & RW).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $requestedScope = strtolower($request->input('scope', 'rt'));
        if (! in_array($requestedScope, ['rt', 'rw'])) {
            $requestedScope = 'rt';
        }

        // Canonical scope ID selalu diturunkan dari server
        $scopeId = ScopeAuthorizer::resolveScopeId($user, $requestedScope);

        // Jika user adalah Ketua RW (rt_id null) dan memilih tab RT, fallback mulus ke scope RW
        if (! $scopeId && $requestedScope === 'rt' && $user->rw_id) {
            $requestedScope = 'rw';
            $scopeId = ScopeAuthorizer::resolveScopeId($user, 'rw');
        }

        if (! $scopeId || ! ScopeAuthorizer::canAccess($user, $requestedScope, $scopeId)) {
            abort(403, 'Anda tidak memiliki akses ke ruang lingkup wilayah ini.');
        }

        $activeTab = strtolower($request->input('tab', 'pengumuman'));
        if (! in_array($activeTab, ['pengumuman', 'chat', 'forum'])) {
            $activeTab = 'pengumuman';
        }

        // Layer 1: Announcements (Pengumuman Resmi — Hanya versi Aktif yang muncul di Active Feed)
        $announcements = Announcement::with([
                'author',
                'comments.author',
                'forumThread',
                'previous.author',
                'previous.comments',
                'previous.previous',
                'successor',
                'publicActivities.rt',
                'publicActivities.rw',
                'kalenderEvent',
            ])
            ->active()
            ->where('scope_type', $requestedScope)
            ->where('scope_id', $scopeId)
            ->orderByDesc('is_pinned')
            ->latest('created_at')
            ->get();

        // Daftar Thread Forum di scope yang sama untuk dropdown "Hubungkan ke Forum"
        $availableThreads = ForumThread::whereHas('category', function ($q) use ($requestedScope, $scopeId) {
                $q->where('scope_type', $requestedScope)
                    ->where('scope_id', $scopeId);
            })
            ->where('status', 'aktif')
            ->latest('created_at')
            ->get(['id', 'judul', 'category_id']);

        // Layer 3: Forum Categories (Kategori Topik Rembuk Warga)
        $forumCategories = ForumCategory::where('scope_type', $requestedScope)
            ->where('scope_id', $scopeId)
            ->orderBy('urutan')
            ->get();

        // Layer 3: Forum Threads
        $selectedCategoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $threadsQuery = ForumThread::with(['author', 'category'])
            ->withCount('posts')
            ->whereHas('category', function ($q) use ($requestedScope, $scopeId) {
                $q->where('scope_type', $requestedScope)
                    ->where('scope_id', $scopeId);
            })
            ->where('status', '!=', 'dihapus')
            ->orderByDesc('is_pinned')
            ->latest('created_at');

        if ($selectedCategoryId) {
            $threadsQuery->where('category_id', $selectedCategoryId);
        }

        $threads = $threadsQuery->paginate(15)->withQueryString();

        $canPublishAnnouncement = ScopeAuthorizer::canPublishAnnouncement($user, $requestedScope, $scopeId);
        $canModerateForum = ScopeAuthorizer::canModerateForum($user, $requestedScope, $scopeId);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'scope_type' => $requestedScope,
                'scope_id' => $scopeId,
                'active_tab' => $activeTab,
                'can_publish_announcement' => $canPublishAnnouncement,
                'can_moderate_forum' => $canModerateForum,
                'announcements' => $announcements,
                'available_threads' => $availableThreads,
                'forum_categories' => $forumCategories,
                'threads' => $threads,
            ]);
        }

        $pageTitle = match ($activeTab) {
            'chat' => 'Chat Bebas',
            'forum' => 'Forum Warga',
            default => 'Pengumuman Resmi',
        };

        return view('komunitas.index', compact(
            'pageTitle',
            'requestedScope',
            'scopeId',
            'activeTab',
            'announcements',
            'availableThreads',
            'forumCategories',
            'threads',
            'selectedCategoryId',
            'canPublishAnnouncement',
            'canModerateForum'
        ));
    }

    /**
     * Menerbitkan pengumuman resmi baru (Layer 1).
     * Khusus pengurus (Ketua RT/RW, Wakil RT, Sekretaris).
     */
    public function storePengumuman(Request $request)
    {
        $validated = $request->validate([
            'scope_type' => ['required', 'in:rt,rw'],
            'scope_id' => ['nullable', 'integer'],
            'judul' => ['required', 'string', 'max:255'],
            'konten' => ['required', 'string'],
            'tipe' => ['required', 'in:INFO,PENTING,MENDESAK'],
            'is_pinned' => ['nullable', 'boolean'],
            'expired_at' => ['nullable', 'date'],
            'forum_thread_id' => ['nullable', 'integer', 'exists:forum_threads,id'],
            'is_agenda' => ['nullable', 'boolean'],
            'jadwalkan_kalender' => ['nullable', 'boolean'],
            'agenda_tanggal' => ['nullable', 'date'],
            'tanggal' => ['nullable', 'date'],
            'agenda_waktu_mulai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'waktu_mulai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'agenda_waktu_selesai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'waktu_selesai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'agenda_lokasi' => ['nullable', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'agenda_kategori' => ['nullable', 'in:KEGIATAN,RAPAT,POSYANDU,LAINNYA'],
            'kategori' => ['nullable', 'in:KEGIATAN,RAPAT,POSYANDU,LAINNYA'],
        ], [
            'agenda_waktu_mulai.regex' => 'Format waktu mulai harus berupa HH:MM (contoh: 08:30).',
            'waktu_mulai.regex' => 'Format waktu mulai harus berupa HH:MM (contoh: 08:30).',
            'agenda_waktu_selesai.regex' => 'Format waktu selesai harus berupa HH:MM (contoh: 11:00).',
            'waktu_selesai.regex' => 'Format waktu selesai harus berupa HH:MM (contoh: 11:00).',
            'agenda_kategori.in' => 'Kategori agenda tidak valid.',
            'kategori.in' => 'Kategori agenda tidak valid.',
        ]);

        $user = Auth::user();
        $scopeType = strtolower($validated['scope_type']);

        // Anti-spoofing check
        if ($request->filled('scope_id')) {
            if (! ScopeAuthorizer::canAccess($user, $scopeType, $request->input('scope_id'))) {
                abort(403, 'Akses ke scope wilayah ini tidak diizinkan.');
            }
        }

        $canonicalScopeId = ScopeAuthorizer::resolveScopeId($user, $scopeType);
        if (! $canonicalScopeId) {
            abort(403, 'Akun Anda tidak terhubung dengan wilayah scope ini.');
        }

        // Cek wewenang penerbitan pengurus
        if (! ScopeAuthorizer::canPublishAnnouncement($user, $scopeType, $canonicalScopeId)) {
            abort(403, 'Hanya pengurus yang berwenang menerbitkan pengumuman.');
        }

        // Validasi anti cross-scope untuk forum_thread_id
        if (! empty($validated['forum_thread_id'])) {
            $thread = ForumThread::with('category')->findOrFail($validated['forum_thread_id']);
            if ($thread->category->scope_type !== $scopeType || (int) $thread->category->scope_id !== (int) $canonicalScopeId) {
                abort(422, 'Thread forum harus berada dalam lingkup wilayah yang sama.');
            }
        }

        // Resolusi metadata agenda kalender
        $isAgenda = $request->boolean('is_agenda') || $request->boolean('jadwalkan_kalender');
        $agendaTanggal = $validated['agenda_tanggal'] ?? $validated['tanggal'] ?? null;
        $agendaKategori = $validated['agenda_kategori'] ?? $validated['kategori'] ?? null;
        $agendaWaktuMulai = $validated['agenda_waktu_mulai'] ?? $validated['waktu_mulai'] ?? null;
        $agendaWaktuSelesai = $validated['agenda_waktu_selesai'] ?? $validated['waktu_selesai'] ?? null;
        $agendaLokasi = $validated['agenda_lokasi'] ?? $validated['lokasi'] ?? null;

        if ($isAgenda) {
            if (empty($agendaTanggal)) {
                throw ValidationException::withMessages([
                    'agenda_tanggal' => ['Tanggal kegiatan wajib diisi jika agenda kalender diaktifkan.'],
                    'tanggal' => ['Tanggal kegiatan wajib diisi jika agenda kalender diaktifkan.'],
                ]);
            }
            if (empty($agendaKategori)) {
                throw ValidationException::withMessages([
                    'agenda_kategori' => ['Kategori kegiatan wajib dipilih jika agenda kalender diaktifkan.'],
                    'kategori' => ['Kategori kegiatan wajib dipilih jika agenda kalender diaktifkan.'],
                ]);
            }
            if (! empty($agendaWaktuMulai) && ! empty($agendaWaktuSelesai)) {
                if (strcmp($agendaWaktuSelesai, $agendaWaktuMulai) < 0) {
                    throw ValidationException::withMessages([
                        'agenda_waktu_selesai' => ['Waktu selesai tidak boleh lebih awal dari waktu mulai.'],
                        'waktu_selesai' => ['Waktu selesai tidak boleh lebih awal dari waktu mulai.'],
                    ]);
                }
            }
        }

        // Eksekusi atomik dalam transaksi database
        $announcement = DB::transaction(function () use (
            $validated, $scopeType, $canonicalScopeId, $user, $request,
            $isAgenda, $agendaTanggal, $agendaKategori, $agendaWaktuMulai, $agendaWaktuSelesai, $agendaLokasi
        ) {
            $ann = Announcement::create([
                'scope_type' => $scopeType,
                'scope_id' => $canonicalScopeId,
                'author_id' => $user->id,
                'judul' => $validated['judul'],
                'konten' => $validated['konten'],
                'tipe' => $validated['tipe'],
                'is_pinned' => (bool) ($request->input('is_pinned', false)),
                'expired_at' => $validated['expired_at'] ?? null,
                'is_deactivated' => false,
                'is_replaced' => false,
                'forum_thread_id' => $validated['forum_thread_id'] ?? null,
            ]);

            if ($isAgenda) {
                KalenderEvent::create([
                    'scope_type' => $scopeType,
                    'scope_id' => $canonicalScopeId,
                    'judul' => $ann->judul,
                    'deskripsi' => $ann->konten,
                    'tanggal' => $agendaTanggal,
                    'waktu_mulai' => $agendaWaktuMulai ?: null,
                    'waktu_selesai' => $agendaWaktuSelesai ?: null,
                    'lokasi' => $agendaLokasi ?: null,
                    'kategori' => $agendaKategori,
                    'sumber' => KalenderEvent::SUMBER_ANNOUNCEMENT,
                    'announcement_id' => $ann->id,
                    'is_cancelled' => false,
                    'pembatalan_alasan' => null,
                    'created_by' => $user->id,
                ]);
            }

            // Audit Trail resmi untuk aksi pengurus (tidak ada duplicate audit kalender)
            AuditLogger::log(
                aksi: AuditAction::ANNOUNCEMENT_CREATED,
                targetType: 'announcements',
                targetId: $ann->id,
                sebelum: null,
                sesudah: [
                    'judul' => $ann->judul,
                    'tipe' => $ann->tipe,
                    'is_pinned' => $ann->is_pinned,
                    'expired_at' => $ann->expired_at?->toDateString(),
                    'forum_thread_id' => $ann->forum_thread_id,
                    'is_agenda' => $isAgenda,
                ],
                alasan: 'Penerbitan pengumuman resmi ' . strtoupper($scopeType),
                rtId: $scopeType === 'rt' ? $canonicalScopeId : null,
                rwId: $scopeType === 'rw' ? $canonicalScopeId : ($user->rw_id ?? $user->rt?->rw_id)
            );

            return $ann;
        });

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman resmi berhasil diterbitkan.',
                'data' => $announcement->load(['author', 'forumThread', 'kalenderEvent']),
            ], 201);
        }

        return redirect()->route('komunitas.index', ['scope' => $scopeType, 'tab' => 'pengumuman'])
            ->with('success', 'Pengumuman resmi berhasil diterbitkan.');
    }

    /**
     * Menerbitkan Pembaruan Pengumuman Resmi (Layer 1 — BUKAN Edit In-Place).
     * Membuat record pengumuman baru (B) yang menunjuk ke pengumuman lama (A).
     * Pengumuman lama ditandai Replaced secara atomik dalam satu transaksi database.
     * Menerapkan Anti-Branching: 1 pengumuman hanya boleh memiliki 1 direct successor.
     */
    public function storePembaruan(Request $request, int $id)
    {
        $oldAnnouncement = Announcement::findOrFail($id);
        $user = Auth::user();

        // Otorisasi pengurus sesuai scope pengumuman lama
        if (! ScopeAuthorizer::canPublishAnnouncement($user, $oldAnnouncement->scope_type, $oldAnnouncement->scope_id)) {
            abort(403, 'Anda tidak memiliki wewenang untuk menerbitkan pembaruan pada pengumuman ini.');
        }

        // Anti-Branching & Status Guard: Hanya pengumuman aktif & belum memiliki successor yang boleh diperbarui
        if (! $oldAnnouncement->canBeUpdated()) {
            abort(422, 'Pengumuman ini telah digantikan oleh pembaruan lain atau telah dinonaktifkan.');
        }

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'konten' => ['required', 'string'],
            'tipe' => ['required', 'in:INFO,PENTING,MENDESAK'],
            'is_pinned' => ['nullable', 'boolean'],
            'expired_at' => ['nullable', 'date'],
            'forum_thread_id' => ['nullable', 'integer', 'exists:forum_threads,id'],
            'alasan' => ['nullable', 'string', 'max:500'],
            'is_agenda' => ['nullable', 'boolean'],
            'jadwalkan_kalender' => ['nullable', 'boolean'],
            'agenda_tanggal' => ['nullable', 'date'],
            'tanggal' => ['nullable', 'date'],
            'agenda_waktu_mulai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'waktu_mulai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'agenda_waktu_selesai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'waktu_selesai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'agenda_lokasi' => ['nullable', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'agenda_kategori' => ['nullable', 'in:KEGIATAN,RAPAT,POSYANDU,LAINNYA'],
            'kategori' => ['nullable', 'in:KEGIATAN,RAPAT,POSYANDU,LAINNYA'],
        ], [
            'agenda_waktu_mulai.regex' => 'Format waktu mulai harus berupa HH:MM.',
            'waktu_mulai.regex' => 'Format waktu mulai harus berupa HH:MM.',
            'agenda_waktu_selesai.regex' => 'Format waktu selesai harus berupa HH:MM.',
            'waktu_selesai.regex' => 'Format waktu selesai harus berupa HH:MM.',
            'agenda_kategori.in' => 'Kategori agenda tidak valid.',
            'kategori.in' => 'Kategori agenda tidak valid.',
        ]);

        // Validasi anti cross-scope untuk forum_thread_id jika disertakan
        if (! empty($validated['forum_thread_id'])) {
            $thread = ForumThread::with('category')->findOrFail($validated['forum_thread_id']);
            if ($thread->category->scope_type !== $oldAnnouncement->scope_type || (int) $thread->category->scope_id !== (int) $oldAnnouncement->scope_id) {
                abort(422, 'Thread forum harus berada dalam lingkup wilayah yang sama dengan pengumuman.');
            }
        }

        // Resolusi metadata agenda kalender
        $isAgenda = $request->boolean('is_agenda') || $request->boolean('jadwalkan_kalender');
        $agendaTanggal = $validated['agenda_tanggal'] ?? $validated['tanggal'] ?? null;
        $agendaKategori = $validated['agenda_kategori'] ?? $validated['kategori'] ?? null;
        $agendaWaktuMulai = $validated['agenda_waktu_mulai'] ?? $validated['waktu_mulai'] ?? null;
        $agendaWaktuSelesai = $validated['agenda_waktu_selesai'] ?? $validated['waktu_selesai'] ?? null;
        $agendaLokasi = $validated['agenda_lokasi'] ?? $validated['lokasi'] ?? null;

        if ($isAgenda) {
            if (empty($agendaTanggal)) {
                throw ValidationException::withMessages([
                    'agenda_tanggal' => ['Tanggal kegiatan wajib diisi jika agenda kalender diaktifkan.'],
                    'tanggal' => ['Tanggal kegiatan wajib diisi jika agenda kalender diaktifkan.'],
                ]);
            }
            if (empty($agendaKategori)) {
                throw ValidationException::withMessages([
                    'agenda_kategori' => ['Kategori kegiatan wajib dipilih jika agenda kalender diaktifkan.'],
                    'kategori' => ['Kategori kegiatan wajib dipilih jika agenda kalender diaktifkan.'],
                ]);
            }
            if (! empty($agendaWaktuMulai) && ! empty($agendaWaktuSelesai)) {
                if (strcmp($agendaWaktuSelesai, $agendaWaktuMulai) < 0) {
                    throw ValidationException::withMessages([
                        'agenda_waktu_selesai' => ['Waktu selesai tidak boleh lebih awal dari waktu mulai.'],
                        'waktu_selesai' => ['Waktu selesai tidak boleh lebih awal dari waktu mulai.'],
                    ]);
                }
            }
        }

        // Eksekusi atomik: Tandai old sebagai REPLACED, terbitkan new sebagai ACTIVE successor
        $newAnnouncement = DB::transaction(function () use (
            $validated, $oldAnnouncement, $user, $request,
            $isAgenda, $agendaTanggal, $agendaKategori, $agendaWaktuMulai, $agendaWaktuSelesai, $agendaLokasi
        ) {
            // 1. Kunci dan tandai pengumuman lama sebagai REPLACED
            $oldAnnouncement->is_replaced = true;
            $oldAnnouncement->save();

            // 2. Terbitkan record pengumuman baru yang mereferensikan pengumuman lama
            $new = Announcement::create([
                'scope_type' => $oldAnnouncement->scope_type,
                'scope_id' => $oldAnnouncement->scope_id,
                'author_id' => $user->id,
                'judul' => $validated['judul'],
                'konten' => $validated['konten'],
                'tipe' => $validated['tipe'],
                'is_pinned' => (bool) ($request->input('is_pinned', false)),
                'expired_at' => $validated['expired_at'] ?? null,
                'replaces_announcement_id' => $oldAnnouncement->id,
                'is_replaced' => false,
                'is_deactivated' => false,
                'forum_thread_id' => $validated['forum_thread_id'] ?? null,
            ]);

            // 3. Kalender Synchronization Lifecycle (Update Matrix A, B, C, D)
            $existingEvent = KalenderEvent::where('announcement_id', $oldAnnouncement->id)->first();

            if ($isAgenda) {
                if ($existingEvent) {
                    // CASE B: V1 ON -> V2 ON (relink announcement_id ke $new->id, update metadata, event ID tetap sama)
                    $existingEvent->update([
                        'announcement_id' => $new->id,
                        'scope_type' => $new->scope_type,
                        'scope_id' => $new->scope_id,
                        'judul' => $new->judul,
                        'deskripsi' => $new->konten,
                        'tanggal' => $agendaTanggal,
                        'waktu_mulai' => $agendaWaktuMulai ?: null,
                        'waktu_selesai' => $agendaWaktuSelesai ?: null,
                        'lokasi' => $agendaLokasi ?: null,
                        'kategori' => $agendaKategori,
                    ]);
                } else {
                    // CASE A: V1 OFF -> V2 ON (buat KalenderEvent baru linked ke $new->id)
                    KalenderEvent::create([
                        'scope_type' => $new->scope_type,
                        'scope_id' => $new->scope_id,
                        'judul' => $new->judul,
                        'deskripsi' => $new->konten,
                        'tanggal' => $agendaTanggal,
                        'waktu_mulai' => $agendaWaktuMulai ?: null,
                        'waktu_selesai' => $agendaWaktuSelesai ?: null,
                        'lokasi' => $agendaLokasi ?: null,
                        'kategori' => $agendaKategori,
                        'sumber' => KalenderEvent::SUMBER_ANNOUNCEMENT,
                        'announcement_id' => $new->id,
                        'is_cancelled' => false,
                        'pembatalan_alasan' => null,
                        'created_by' => $user->id,
                    ]);
                }
            } else {
                if ($existingEvent) {
                    // CASE C: V1 ON -> V2 OFF (hapus KalenderEvent agar tidak ada stale event aktif di kalender)
                    $existingEvent->delete();
                }
                // CASE D: V1 OFF -> V2 OFF (tidak ada aksi)
            }

            // 4. Catat audit trail akuntabel (Hanya ANNOUNCEMENT_UPDATED, tidak ada duplicate audit kalender)
            AuditLogger::log(
                aksi: AuditAction::ANNOUNCEMENT_UPDATED,
                targetType: 'announcements',
                targetId: $new->id,
                sebelum: [
                    'id' => $oldAnnouncement->id,
                    'judul' => $oldAnnouncement->judul,
                    'is_replaced' => false,
                ],
                sesudah: [
                    'id' => $new->id,
                    'judul' => $new->judul,
                    'replaces_announcement_id' => $oldAnnouncement->id,
                    'is_replaced' => false,
                    'is_agenda' => $isAgenda,
                ],
                alasan: !empty($validated['alasan']) ? $validated['alasan'] : null,
                rtId: $oldAnnouncement->scope_type === 'rt' ? $oldAnnouncement->scope_id : null,
                rwId: $oldAnnouncement->scope_type === 'rw' ? $oldAnnouncement->scope_id : ($user->rw_id ?? $user->rt?->rw_id)
            );

            return $new;
        });

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pembaruan pengumuman resmi berhasil diterbitkan.',
                'data' => $newAnnouncement->load(['author', 'previous', 'forumThread', 'kalenderEvent']),
            ], 201);
        }

        return redirect()->route('komunitas.index', ['scope' => $newAnnouncement->scope_type, 'tab' => 'pengumuman'])
            ->with('success', 'Pembaruan pengumuman resmi berhasil diterbitkan.');
    }

    /**
     * Menonaktifkan Pengumuman Resmi (Layer 1 — BUKAN Hapus Permanen).
     * Record dan komentar tetap dipertahankan, namun hilang dari Active Feed warga.
     * Mewajibkan pengisian alasan pengurus dan mencatatnya ke AuditLogger.
     */
    public function deactivatePengumuman(Request $request, int $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = Auth::user();

        if (! ScopeAuthorizer::canPublishAnnouncement($user, $announcement->scope_type, $announcement->scope_id)) {
            abort(403, 'Anda tidak memiliki wewenang untuk menonaktifkan pengumuman ini.');
        }

        $validated = $request->validate([
            'alasan' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $announcement->update([
            'is_deactivated' => true,
            'deactivated_at' => now(),
            'deactivated_by' => $user->id,
            'deactivation_reason' => $validated['alasan'],
        ]);

        AuditLogger::log(
            aksi: AuditAction::ANNOUNCEMENT_DEACTIVATED,
            targetType: 'announcements',
            targetId: $announcement->id,
            sebelum: ['is_deactivated' => false],
            sesudah: [
                'is_deactivated' => true,
                'deactivated_at' => now()->toIso8601String(),
                'deactivation_reason' => $validated['alasan'],
            ],
            alasan: $validated['alasan'],
            rtId: $announcement->scope_type === 'rt' ? $announcement->scope_id : null,
            rwId: $announcement->scope_type === 'rw' ? $announcement->scope_id : ($user->rw_id ?? $user->rt?->rw_id)
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman resmi berhasil dinonaktifkan.',
                'data' => $announcement,
            ]);
        }

        return redirect()->route('komunitas.index', ['scope' => $announcement->scope_type, 'tab' => 'pengumuman'])
            ->with('success', 'Pengumuman resmi berhasil dinonaktifkan.');
    }

    /**
     * Menghubungkan pengumuman ke Thread Forum Warga Terkait (Layer 1 ↔ Layer 3).
     * Hanya dapat dilakukan jika pengumuman belum memiliki Forum Terkait.
     * Thread forum wajib berada pada lingkup wilayah (scope) yang sama.
     */
    public function linkForum(Request $request, int $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = Auth::user();

        if (! ScopeAuthorizer::canPublishAnnouncement($user, $announcement->scope_type, $announcement->scope_id)) {
            abort(403, 'Anda tidak memiliki wewenang untuk menghubungkan pengumuman ini ke forum.');
        }

        if (! $announcement->canLinkForum()) {
            abort(422, 'Pengumuman ini sudah memiliki Forum Terkait atau tidak aktif.');
        }

        $validated = $request->validate([
            'forum_thread_id' => ['required', 'integer', 'exists:forum_threads,id'],
        ]);

        $thread = ForumThread::with('category')->findOrFail($validated['forum_thread_id']);
        if ($thread->category->scope_type !== $announcement->scope_type || (int) $thread->category->scope_id !== (int) $announcement->scope_id) {
            abort(422, 'Thread forum harus berada dalam lingkup wilayah yang sama dengan pengumuman.');
        }

        $announcement->update([
            'forum_thread_id' => $thread->id,
        ]);

        AuditLogger::log(
            aksi: AuditAction::ANNOUNCEMENT_FORUM_LINKED,
            targetType: 'announcements',
            targetId: $announcement->id,
            sebelum: ['forum_thread_id' => null],
            sesudah: ['forum_thread_id' => $thread->id],
            alasan: "Menghubungkan pengumuman ke thread forum: {$thread->judul}",
            rtId: $announcement->scope_type === 'rt' ? $announcement->scope_id : null,
            rwId: $announcement->scope_type === 'rw' ? $announcement->scope_id : ($user->rw_id ?? $user->rt?->rw_id)
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman berhasil dihubungkan ke Forum Warga.',
                'data' => $announcement->load('forumThread'),
            ]);
        }

        return redirect()->route('komunitas.index', ['scope' => $announcement->scope_type, 'tab' => 'pengumuman'])
            ->with('success', 'Pengumuman berhasil dihubungkan ke Forum Warga.');
    }

    /**
     * Menyematkan atau melepas sematan (toggle pin/unpin) pada pengumuman resmi (Layer 1).
     * Khusus pengurus berwenang (Ketua RT/RW, Wakil RT, Sekretaris).
     */
    public function togglePinPengumuman(Request $request, int $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = Auth::user();

        if (! ScopeAuthorizer::canPublishAnnouncement($user, $announcement->scope_type, $announcement->scope_id)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengubah status sematan pengumuman ini.');
        }

        $oldPinned = (bool) $announcement->is_pinned;
        $newPinned = ! $oldPinned;
        $announcement->is_pinned = $newPinned;
        $announcement->save();

        $actionName = $newPinned ? AuditAction::ANNOUNCEMENT_PINNED : AuditAction::ANNOUNCEMENT_UNPINNED;
        $deskripsi = ($newPinned ? 'Menyematkan' : 'Melepas sematan') . " pengumuman: {$announcement->judul}";

        AuditLogger::log(
            aksi: $actionName,
            targetType: 'announcements',
            targetId: $announcement->id,
            sebelum: ['is_pinned' => $oldPinned],
            sesudah: ['is_pinned' => $newPinned],
            alasan: $deskripsi,
            rtId: $announcement->scope_type === 'rt' ? $announcement->scope_id : null,
            rwId: $announcement->scope_type === 'rw' ? $announcement->scope_id : ($user->rw_id ?? $user->rt?->rw_id)
        );

        $msg = $newPinned ? 'Pengumuman berhasil disematkan di posisi teratas.' : 'Sematan pengumuman berhasil dilepas.';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'data' => $announcement,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Menampilkan detail satu Pengumuman Resmi (Layer 1).
     * Dapat membuka versi aktif maupun versi riwayat (lama/digantikan/dinonaktifkan).
     * Menerapkan prinsip NON-REDIRECT: Versi lama tetap terbuka sebagai arsip histori.
     */
    public function showPengumuman(Request $request, int $id)
    {
        $announcement = Announcement::with([
            'author',
            'comments.author',
            'forumThread',
            'previous.author',
            'previous.comments',
            'previous.previous',
            'successor.author',
            'successor.comments',
            'publicActivities.rt',
            'publicActivities.rw',
            'kalenderEvent',
        ])->findOrFail($id);

        $user = Auth::user();

        if (! ScopeAuthorizer::canAccess($user, $announcement->scope_type, $announcement->scope_id)) {
            abort(403, 'Anda tidak memiliki akses ke pengumuman wilayah ini.');
        }

        $canPublishAnnouncement = ScopeAuthorizer::canPublishAnnouncement($user, $announcement->scope_type, $announcement->scope_id);
        $latestAnnouncement = $announcement->getLatestVersion();
        $previousAnnouncement = $announcement->previous;
        $successorAnnouncement = $announcement->successor;

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $announcement,
                'latest' => $latestAnnouncement,
                'previous' => $previousAnnouncement,
                'successor' => $successorAnnouncement,
                'can_receive_comments' => $announcement->canReceiveComments(),
            ]);
        }

        $pageTitle = $announcement->judul . ' — Pengumuman Resmi';

        return view('komunitas.pengumuman', compact(
            'announcement',
            'latestAnnouncement',
            'previousAnnouncement',
            'successorAnnouncement',
            'canPublishAnnouncement',
            'pageTitle'
        ));
    }

    /**
     * Mengirim komentar/tanggapan pada Pengumuman (Layer 1).
     * Terbuka untuk seluruh warga aktif di scope yang bersangkutan.
     * HARD REQUIREMENT: Versi lama (digantikan) dan versi dinonaktifkan adalah READ-ONLY untuk tanggapan baru!
     */
    public function storePengumumanKomentar(Request $request, int $id)
    {
        $validated = $request->validate([
            'konten' => ['required', 'string', 'max:1000'],
        ]);

        $announcement = Announcement::findOrFail($id);
        $user = Auth::user();

        if (! ScopeAuthorizer::canAccess($user, $announcement->scope_type, $announcement->scope_id)) {
            abort(403, 'Akses ke pengumuman wilayah ini tidak diizinkan.');
        }

        // HARD REQUIREMENT: Versi lama harus READ-ONLY untuk tanggapan baru!
        if (! $announcement->canReceiveComments()) {
            abort(422, 'Pengumuman ini telah digantikan oleh pembaruan terbaru atau telah dinonaktifkan. Tanggapan baru hanya dapat dikirimkan pada versi terbaru.');
        }

        $comment = AnnouncementComment::create([
            'announcement_id' => $announcement->id,
            'author_id' => $user->id,
            'konten' => $validated['konten'],
        ]);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tanggapan berhasil dikirim.',
                'data' => $comment->load('author'),
            ], 201);
        }

        return redirect()->back()->with('success', 'Tanggapan berhasil dikirim.');
    }

    /**
     * Mengambil riwayat 50 pesan terakhir pada Chat Bebas (Layer 2).
     * Otorisasi diperiksa via ScopeAuthorizer.
     * scope_id selalu diturunkan dari server (database), bukan dari input client.
     */
    public function getChatMessages(Request $request): JsonResponse
    {
        $request->validate([
            'scope_type' => ['required', 'in:rt,rw'],
            'scope_id' => ['nullable', 'integer'],
        ]);

        $user = Auth::user();
        $scopeType = strtolower($request->input('scope_type'));

        // Jika client sengaja mengirimkan scope_id yang tidak berhak diaksesnya -> tolak HTTP 403
        if ($request->filled('scope_id')) {
            if (! ScopeAuthorizer::canAccess($user, $scopeType, $request->input('scope_id'))) {
                abort(403, 'Akses ke scope wilayah ini tidak diizinkan.');
            }
        }

        // scope_id kanonikal selalu diturunkan dari data user yang sedang login di server
        $canonicalScopeId = ScopeAuthorizer::resolveScopeId($user, $scopeType);

        if (! $canonicalScopeId) {
            abort(403, 'Akun Anda tidak terhubung dengan wilayah scope ini.');
        }

        $messages = ChatMessage::with('author')
            ->where('scope_type', $scopeType)
            ->where('scope_id', $canonicalScopeId)
            ->latest('id')
            ->take(50)
            ->get()
            ->reverse()
            ->values()
            ->map(function (ChatMessage $msg) {
                return [
                    'id' => $msg->id,
                    'scope_type' => $msg->scope_type,
                    'scope_id' => (int) $msg->scope_id,
                    'author_id' => (int) $msg->author_id,
                    'konten' => $msg->konten,
                    'created_at' => $msg->created_at?->toISOString() ?? now()->toISOString(),
                    'created_at_human' => $msg->created_at?->diffForHumans() ?? 'baru saja',
                    'author' => [
                        'id' => $msg->author?->id,
                        'nama' => $msg->author?->nama,
                        'role_badge' => $msg->author?->getHighestRoleBadge() ?? 'Warga',
                    ],
                ];
            });

        return response()->json([
            'status' => 'success',
            'scope_type' => $scopeType,
            'scope_id' => $canonicalScopeId,
            'data' => $messages,
        ]);
    }

    /**
     * Mengirimkan pesan baru pada Chat Bebas (Layer 2).
     * Pesan disimpan ke database dan disiarkan secara real-time via WebSocket (Reverb).
     * Otorisasi diperiksa via ScopeAuthorizer.
     */
    public function sendChatMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope_type' => ['required', 'in:rt,rw'],
            'scope_id' => ['nullable', 'integer'],
            'konten' => ['required', 'string', 'max:2000'],
        ]);

        $user = Auth::user();
        $scopeType = strtolower($validated['scope_type']);

        // Uji Keamanan: Jika client mencoba memalsukan scope_id -> tolak HTTP 403
        if ($request->filled('scope_id')) {
            if (! ScopeAuthorizer::canAccess($user, $scopeType, $request->input('scope_id'))) {
                abort(403, 'Akses ke scope wilayah ini tidak diizinkan.');
            }
        }

        // scope_id SELALU diturunkan dari server, tidak pernah mempercayai client
        $canonicalScopeId = ScopeAuthorizer::resolveScopeId($user, $scopeType);

        if (! $canonicalScopeId) {
            abort(403, 'Akun Anda tidak terhubung dengan wilayah scope ini.');
        }

        $message = ChatMessage::create([
            'scope_type' => $scopeType,
            'scope_id' => $canonicalScopeId,
            'author_id' => $user->id,
            'konten' => $validated['konten'],
        ]);

        // Muat relasi author untuk payload broadcast dan response JSON
        $message->load('author');

        // Pancarkan event real-time ke Private Channel Reverb (Graceful jika server websocket offline)
        try {
            broadcast(new ChatMessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Pesan chat berhasil disimpan ke database, namun broadcast Reverb gagal disiarkan: ' . $e->getMessage());
        }

        $payload = [
            'id' => $message->id,
            'scope_type' => $message->scope_type,
            'scope_id' => (int) $message->scope_id,
            'author_id' => (int) $message->author_id,
            'konten' => $message->konten,
            'created_at' => $message->created_at?->toISOString() ?? now()->toISOString(),
            'created_at_human' => $message->created_at?->diffForHumans() ?? 'baru saja',
            'author' => [
                'id' => $message->author?->id,
                'nama' => $message->author?->nama,
                'role_badge' => $message->author?->getHighestRoleBadge() ?? 'Warga',
            ],
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan berhasil dikirim.',
            'data' => $payload,
        ], 201);
    }

    /**
     * Membuat thread usulan baru pada Forum Warga (Layer 3).
     * Otomatis mengunci author_role_snapshot permanen.
     */
    public function storeThread(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:forum_categories,id'],
            'judul' => ['required', 'string', 'max:255'],
            'konten' => ['required', 'string'],
        ]);

        $category = ForumCategory::findOrFail($validated['category_id']);
        $user = Auth::user();

        if (! ScopeAuthorizer::canAccess($user, $category->scope_type, $category->scope_id)) {
            abort(403, 'Akses ke kategori forum wilayah ini tidak diizinkan.');
        }

        $thread = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $user->id,
            'judul' => $validated['judul'],
            'konten' => $validated['konten'],
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => $user->getRoleSnapshot(),
        ]);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Thread usulan berhasil dipublikasikan.',
                'data' => $thread->load(['author', 'category']),
            ], 201);
        }

        return redirect()->route('komunitas.forum.thread.show', $thread->id)
            ->with('success', 'Thread usulan berhasil dipublikasikan.');
    }

    /**
     * Menampilkan rincian thread forum beserta seluruh tanggapannya (Layer 3).
     */
    public function showThread(Request $request, int $id)
    {
        $thread = ForumThread::with([
            'category',
            'author',
            'posts' => function ($q) {
                $q->whereNull('parent_post_id')->with(['author', 'replies.author'])->oldest('created_at');
            },
        ])->findOrFail($id);

        $user = Auth::user();

        if ($thread->status === 'dihapus') {
            abort(404, 'Thread ini telah dihapus oleh moderator.');
        }

        if (! ScopeAuthorizer::canAccess($user, $thread->category->scope_type, $thread->category->scope_id)) {
            abort(403, 'Akses ke thread forum wilayah ini tidak diizinkan.');
        }

        $canModerate = ScopeAuthorizer::canModerateForum($user, $thread->category->scope_type, $thread->category->scope_id);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $thread,
                'can_moderate' => $canModerate,
            ]);
        }

        return view('komunitas.thread', compact('thread', 'canModerate'));
    }

    /**
     * Mengirim tanggapan/balasan pada thread forum (Layer 3).
     * Mengunci author_role_snapshot secara independen.
     */
    public function storePost(Request $request, int $id)
    {
        $validated = $request->validate([
            'konten' => ['required', 'string'],
            'parent_post_id' => ['nullable', 'exists:forum_posts,id'],
        ]);

        $thread = ForumThread::with('category')->findOrFail($id);
        $user = Auth::user();

        if (! ScopeAuthorizer::canAccess($user, $thread->category->scope_type, $thread->category->scope_id)) {
            abort(403, 'Akses ke thread forum wilayah ini tidak diizinkan.');
        }

        if ($thread->status !== 'aktif') {
            abort(422, 'Diskusi pada thread ini sudah ditutup atau tidak aktif.');
        }

        if (! empty($validated['parent_post_id'])) {
            $parentPost = ForumPost::findOrFail($validated['parent_post_id']);
            if ($parentPost->thread_id !== $thread->id) {
                abort(422, 'Balasan tidak sesuai dengan thread yang dituju.');
            }
        }

        $post = ForumPost::create([
            'thread_id' => $thread->id,
            'author_id' => $user->id,
            'parent_post_id' => $validated['parent_post_id'] ?? null,
            'konten' => $validated['konten'],
            'author_role_snapshot' => $user->getRoleSnapshot(),
        ]);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tanggapan berhasil dikirim.',
                'data' => $post->load('author'),
            ], 201);
        }

        return redirect()->route('komunitas.forum.thread.show', $thread->id)
            ->with('success', 'Tanggapan berhasil dikirim.');
    }

    /**
     * Moderasi thread (pin, unpin, close, reopen, hapus) oleh pengurus (Layer 3).
     * Wajib mencantumkan alasan moderasi dan dicatat ke audit_logs.
     */
    public function moderateThread(Request $request, int $id)
    {
        $validated = $request->validate([
            'aksi' => ['required', 'in:pin,unpin,close,reopen,hapus'],
            'alasan' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $thread = ForumThread::with('category')->findOrFail($id);
        $user = Auth::user();

        if (! ScopeAuthorizer::canModerateForum($user, $thread->category->scope_type, $thread->category->scope_id)) {
            abort(403, 'Hanya Ketua RT/Wakil RT (lingkup RT) atau Ketua RW (lingkup RW) yang berwenang memoderasi forum.');
        }

        $sebelum = [
            'status' => $thread->status,
            'is_pinned' => (bool) $thread->is_pinned,
        ];

        switch ($validated['aksi']) {
            case 'pin':
                $thread->is_pinned = true;
                break;
            case 'unpin':
                $thread->is_pinned = false;
                break;
            case 'close':
                $thread->status = 'closed';
                break;
            case 'reopen':
                $thread->status = 'aktif';
                break;
            case 'hapus':
                $thread->status = 'dihapus';
                break;
        }

        $thread->save();

        $sesudah = [
            'status' => $thread->status,
            'is_pinned' => (bool) $thread->is_pinned,
        ];

        $aksiConstant = match ($validated['aksi']) {
            'pin' => AuditAction::FORUM_THREAD_PINNED,
            'unpin' => AuditAction::FORUM_THREAD_UNPINNED,
            'close' => AuditAction::FORUM_THREAD_CLOSED,
            'reopen' => AuditAction::FORUM_THREAD_REOPENED,
            'hapus' => AuditAction::FORUM_THREAD_DELETED,
        };

        AuditLogger::log(
            aksi: $aksiConstant,
            targetType: 'forum_threads',
            targetId: $thread->id,
            sebelum: $sebelum,
            sesudah: $sesudah,
            alasan: $validated['alasan'],
            rtId: $thread->category->scope_type === 'rt' ? (int) $thread->category->scope_id : null,
            rwId: $thread->category->scope_type === 'rw' ? (int) $thread->category->scope_id : ($user->rw_id ?? $user->rt?->rw_id)
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Tindakan moderasi {$validated['aksi']} berhasil diterapkan.",
                'data' => $thread,
            ]);
        }

        if ($validated['aksi'] === 'hapus') {
            return redirect()->route('komunitas.index', ['scope' => $thread->category->scope_type, 'tab' => 'forum'])
                ->with('success', 'Thread berhasil dihapus oleh moderator.');
        }

        return redirect()->route('komunitas.forum.thread.show', $thread->id)
            ->with('success', "Tindakan moderasi {$validated['aksi']} berhasil diterapkan.");
    }
}
