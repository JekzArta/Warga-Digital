<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\ChatMessage;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Services\AuditLogger;
use App\Services\ScopeAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Layer 1: Announcements (Pengumuman Resmi)
        $announcements = Announcement::with(['author', 'comments.author'])
            ->where('scope_type', $requestedScope)
            ->where('scope_id', $scopeId)
            ->orderByDesc('is_pinned')
            ->latest('created_at')
            ->get();

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
                'forum_categories' => $forumCategories,
                'threads' => $threads,
            ]);
        }

        return view('komunitas.index', compact(
            'requestedScope',
            'scopeId',
            'activeTab',
            'announcements',
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

        $announcement = Announcement::create([
            'scope_type' => $scopeType,
            'scope_id' => $canonicalScopeId,
            'author_id' => $user->id,
            'judul' => $validated['judul'],
            'konten' => $validated['konten'],
            'tipe' => $validated['tipe'],
            'is_pinned' => (bool) ($request->input('is_pinned', false)),
        ]);

        // Audit Trail resmi untuk aksi pengurus
        AuditLogger::log(
            aksi: 'terbitkan_pengumuman',
            targetType: 'announcement',
            targetId: $announcement->id,
            sebelum: null,
            sesudah: [
                'judul' => $announcement->judul,
                'tipe' => $announcement->tipe,
                'is_pinned' => $announcement->is_pinned,
            ],
            alasan: 'Penerbitan pengumuman resmi ' . strtoupper($scopeType),
            rtId: $scopeType === 'rt' ? $canonicalScopeId : null,
            rwId: $scopeType === 'rw' ? $canonicalScopeId : ($user->rw_id ?? $user->rt?->rw_id)
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman resmi berhasil diterbitkan.',
                'data' => $announcement->load('author'),
            ], 201);
        }

        return redirect()->route('komunitas.index', ['scope' => $scopeType, 'tab' => 'pengumuman'])
            ->with('success', 'Pengumuman resmi berhasil diterbitkan.');
    }

    /**
     * Mengirim komentar/tanggapan pada Pengumuman (Layer 1).
     * Terbuka untuk seluruh warga aktif di scope yang bersangkutan.
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

        // Pancarkan event real-time ke Private Channel Reverb
        broadcast(new ChatMessageSent($message))->toOthers();

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

        $aksiLabel = match ($validated['aksi']) {
            'pin' => 'pin_thread',
            'unpin' => 'unpin_thread',
            'close' => 'close_thread',
            'reopen' => 'reopen_thread',
            'hapus' => 'hapus_thread',
        };

        AuditLogger::log(
            aksi: $aksiLabel,
            targetType: 'forum_thread',
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
