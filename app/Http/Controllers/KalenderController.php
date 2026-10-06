<?php

namespace App\Http\Controllers;

use App\Constants\AuditAction;
use App\Models\KalenderEvent;
use App\Services\AuditLogger;
use App\Services\ScopeAuthorizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KalenderController extends Controller
{
    /**
     * Tampilkan halaman utama Kalender: Month View dan Agenda Terdekat.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        Carbon::setLocale('id');

        // 1. Tentukan Ruang Lingkup Wilayah (Default: RT Pengguna)
        $requestedScope = strtolower($request->input('scope', 'rt'));
        if (! in_array($requestedScope, ['rt', 'rw'], true)) {
            $requestedScope = 'rt';
        }

        // Canonical scope ID selalu diturunkan dari server
        $scopeId = ScopeAuthorizer::resolveScopeId($user, $requestedScope);

        // Jika user adalah Ketua RW (rt_id null) dan memilih tab RT, fallback mulus ke scope RW
        if (! $scopeId && $requestedScope === 'rt' && $user->rw_id) {
            $requestedScope = 'rw';
            $scopeId = ScopeAuthorizer::resolveScopeId($user, 'rw');
        }

        if (! $scopeId || ! ScopeAuthorizer::canViewKalender($user, $requestedScope, $scopeId)) {
            abort(403, 'Anda tidak memiliki hak akses melihat Kalender di ruang lingkup wilayah ini.');
        }

        // 2. Validasi Bulan & Tahun untuk Month View
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $year = max(2020, min(2035, $year));
        $month = max(1, min(12, $month));

        $currentMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $prevMonthDate = $currentMonth->copy()->subMonth();
        $nextMonthDate = $currentMonth->copy()->addMonth();

        $daysInMonth = $currentMonth->daysInMonth;
        // 0 = Minggu, 1 = Senin, ..., 6 = Sabtu
        $firstDayOfWeek = $currentMonth->dayOfWeek;

        // 3. Query Agenda Kalender Bulan Tersebut (Single Table, Active Only)
        $monthEvents = KalenderEvent::forScope($requestedScope, $scopeId)
            ->active()
            ->forMonth($year, $month)
            ->orderBy('tanggal')
            ->orderBy('waktu_mulai')
            ->get();

        // Kelompokkan event berdasarkan tanggal (YYYY-MM-DD) untuk penanda titik pada kalender grid
        $eventsByDate = $monthEvents->groupBy(fn ($e) => $e->tanggal->format('Y-m-d'));

        // 4. Query Agenda Terdekat (Upcoming)
        $upcomingEvents = KalenderEvent::forScope($requestedScope, $scopeId)
            ->upcoming()
            ->take(10)
            ->get();

        $displayedEvents = $monthEvents->concat($upcomingEvents)->unique('id')->sortBy('tanggal')->values();

        // Hak kelola agenda (Ketua RT/Wakil/Sekretaris untuk RT, Ketua RW untuk RW)
        $canManage = ScopeAuthorizer::canManageKalender($user, $requestedScope, $scopeId);

        // Daftar kategori terstandarisasi untuk modal input
        $kategoriList = KalenderEvent::KATEGORI_LIST;

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'scope' => $requestedScope,
                    'scope_id' => $scopeId,
                    'can_manage' => $canManage,
                    'year' => $year,
                    'month' => $month,
                    'month_events' => $monthEvents,
                    'upcoming_events' => $upcomingEvents,
                ],
            ]);
        }

        return view('kalender.index', [
            'user' => $user,
            'requestedScope' => $requestedScope,
            'scopeId' => $scopeId,
            'canManage' => $canManage,
            'year' => $year,
            'month' => $month,
            'currentMonth' => $currentMonth,
            'prevMonthDate' => $prevMonthDate,
            'nextMonthDate' => $nextMonthDate,
            'daysInMonth' => $daysInMonth,
            'firstDayOfWeek' => $firstDayOfWeek,
            'monthEvents' => $monthEvents,
            'eventsByDate' => $eventsByDate,
            'upcomingEvents' => $upcomingEvents,
            'displayedEvents' => $displayedEvents,
            'kategoriList' => $kategoriList,
        ]);
    }

    /**
     * Simpan agenda kalender standalone baru (hanya pengurus berwenang).
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // 1. Otorisasi Ruang Lingkup: Selalu diturunkan dari server
        $scopeType = strtolower($request->input('scope_type', 'rt'));
        if (! in_array($scopeType, ['rt', 'rw'], true)) {
            $scopeType = 'rt';
        }

        $scopeId = ScopeAuthorizer::resolveScopeId($user, $scopeType);

        if (! $scopeId || ! ScopeAuthorizer::canManageKalender($user, $scopeType, $scopeId)) {
            abort(403, 'Anda tidak memiliki wewenang menambahkan agenda pada ruang lingkup wilayah ini.');
        }

        // 2. Validasi Input
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'waktu_selesai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'kategori' => ['required', 'in:KEGIATAN,RAPAT,POSYANDU,LAINNYA'],
        ], [
            'judul.required' => 'Judul agenda wajib diisi.',
            'tanggal.required' => 'Tanggal kegiatan wajib diisi.',
            'tanggal.date' => 'Format tanggal kegiatan tidak valid.',
            'waktu_mulai.regex' => 'Format waktu mulai harus berupa HH:MM (contoh: 08:30).',
            'waktu_selesai.regex' => 'Format waktu selesai harus berupa HH:MM (contoh: 11:00).',
            'kategori.in' => 'Kategori agenda tidak valid.',
        ]);

        // Validasi semantik urutan waktu: waktu selesai tidak boleh lebih awal dari waktu mulai
        if (! empty($validated['waktu_mulai']) && ! empty($validated['waktu_selesai'])) {
            if (strcmp($validated['waktu_selesai'], $validated['waktu_mulai']) < 0) {
                return back()->withErrors(['waktu_selesai' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.'])->withInput();
            }
        }

        // 3. Simpan Agenda Standalone (sumber = manual, announcement_id = null)
        $event = KalenderEvent::create([
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'tanggal' => $validated['tanggal'],
            'waktu_mulai' => $validated['waktu_mulai'] ?? null,
            'waktu_selesai' => $validated['waktu_selesai'] ?? null,
            'lokasi' => $validated['lokasi'] ?? null,
            'kategori' => $validated['kategori'],
            'sumber' => KalenderEvent::SUMBER_MANUAL,
            'announcement_id' => null,
            'is_cancelled' => false,
            'pembatalan_alasan' => null,
            'created_by' => $user->id,
        ]);

        // 4. Catat ke Audit Trail Akuntabilitas
        AuditLogger::log(
            aksi: AuditAction::KALENDER_EVENT_CREATED,
            targetType: 'kalender_events',
            targetId: $event->id,
            sebelum: null,
            sesudah: [
                'judul' => $event->judul,
                'tanggal' => $event->tanggal->toDateString(),
                'waktu_mulai' => $event->waktu_mulai,
                'waktu_selesai' => $event->waktu_selesai,
                'lokasi' => $event->lokasi,
                'kategori' => $event->kategori,
                'scope_type' => $event->scope_type,
                'scope_id' => $event->scope_id,
            ],
            alasan: "Menambahkan agenda kalender: {$event->judul}",
            rtId: $scopeType === 'rt' ? $scopeId : null,
            rwId: $scopeType === 'rw' ? $scopeId : ($user->rw_id ?? $user->rt?->rw_id)
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Agenda berhasil ditambahkan ke Kalender.',
                'data' => $event,
            ], 201);
        }

        // Redirect dengan mempertahankan konteks bulan dan scope
        $eventDate = Carbon::parse($event->tanggal);
        return redirect()->route('kalender.index', [
            'scope' => $scopeType,
            'year' => $eventDate->year,
            'month' => $eventDate->month,
        ])->with('success', 'Agenda berhasil ditambahkan ke Kalender.');
    }

    /**
     * Perbarui agenda kalender standalone.
     */
    public function update(Request $request, int $id)
    {
        $user = Auth::user();
        $event = KalenderEvent::findOrFail($id);

        // 1. Otorisasi Scope Pengguna
        if (! ScopeAuthorizer::canManageKalender($user, $event->scope_type, $event->scope_id)) {
            abort(403, 'Anda tidak berwenang mengedit agenda ini.');
        }

        // 2. Boundary: Linked Announcement Event dilarang diedit via Standalone CRUD
        if ($event->sumber !== KalenderEvent::SUMBER_MANUAL || ! empty($event->announcement_id)) {
            abort(422, 'Agenda yang terhubung dengan Pengumuman resmi tidak dapat diedit langsung dari Kalender.');
        }

        // 3. Validasi Input
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'waktu_selesai' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'kategori' => ['required', 'in:KEGIATAN,RAPAT,POSYANDU,LAINNYA'],
            'is_cancelled' => ['nullable', 'boolean'],
            'pembatalan_alasan' => ['nullable', 'string', 'max:255'],
        ], [
            'judul.required' => 'Judul agenda wajib diisi.',
            'tanggal.required' => 'Tanggal kegiatan wajib diisi.',
            'tanggal.date' => 'Format tanggal kegiatan tidak valid.',
            'waktu_mulai.regex' => 'Format waktu mulai harus berupa HH:MM.',
            'waktu_selesai.regex' => 'Format waktu selesai harus berupa HH:MM.',
            'kategori.in' => 'Kategori agenda tidak valid.',
        ]);

        if (! empty($validated['waktu_mulai']) && ! empty($validated['waktu_selesai'])) {
            if (strcmp($validated['waktu_selesai'], $validated['waktu_mulai']) < 0) {
                return back()->withErrors(['waktu_selesai' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.'])->withInput();
            }
        }

        // Snapshot sebelum perubahan untuk audit trail
        $sebelum = [
            'judul' => $event->judul,
            'tanggal' => $event->tanggal->toDateString(),
            'waktu_mulai' => $event->waktu_mulai,
            'waktu_selesai' => $event->waktu_selesai,
            'lokasi' => $event->lokasi,
            'kategori' => $event->kategori,
            'is_cancelled' => $event->is_cancelled,
            'pembatalan_alasan' => $event->pembatalan_alasan,
        ];

        $isCancelled = (bool) ($request->input('is_cancelled', false));

        $event->update([
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'tanggal' => $validated['tanggal'],
            'waktu_mulai' => $validated['waktu_mulai'] ?? null,
            'waktu_selesai' => $validated['waktu_selesai'] ?? null,
            'lokasi' => $validated['lokasi'] ?? null,
            'kategori' => $validated['kategori'],
            'is_cancelled' => $isCancelled,
            'pembatalan_alasan' => $isCancelled ? ($validated['pembatalan_alasan'] ?? null) : null,
        ]);

        $sesudah = [
            'judul' => $event->judul,
            'tanggal' => $event->tanggal->toDateString(),
            'waktu_mulai' => $event->waktu_mulai,
            'waktu_selesai' => $event->waktu_selesai,
            'lokasi' => $event->lokasi,
            'kategori' => $event->kategori,
            'is_cancelled' => $event->is_cancelled,
            'pembatalan_alasan' => $event->pembatalan_alasan,
        ];

        // 4. Catat Audit Trail
        AuditLogger::log(
            aksi: AuditAction::KALENDER_EVENT_UPDATED,
            targetType: 'kalender_events',
            targetId: $event->id,
            sebelum: $sebelum,
            sesudah: $sesudah,
            alasan: $isCancelled
                ? ('Pembatalan agenda: ' . ($event->pembatalan_alasan ?? 'Dibatalkan pengurus'))
                : "Memperbarui agenda kalender: {$event->judul}",
            rtId: $event->scope_type === 'rt' ? $event->scope_id : null,
            rwId: $event->scope_type === 'rw' ? $event->scope_id : ($user->rw_id ?? $user->rt?->rw_id)
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Agenda berhasil diperbarui.',
                'data' => $event,
            ]);
        }

        $eventDate = Carbon::parse($event->tanggal);
        return redirect()->route('kalender.index', [
            'scope' => $event->scope_type,
            'year' => $eventDate->year,
            'month' => $eventDate->month,
        ])->with('success', 'Agenda berhasil diperbarui.');
    }

    /**
     * Hapus agenda kalender standalone.
     */
    public function destroy(Request $request, int $id)
    {
        $user = Auth::user();
        $event = KalenderEvent::find($id);

        if (! $event) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Agenda sudah tidak ditemukan atau telah dihapus sebelumnya.',
                ]);
            }

            return redirect()->route('kalender.index')->with('info', 'Agenda sudah tidak ditemukan atau telah dihapus.');
        }

        // 1. Otorisasi Scope Pengguna
        if (! ScopeAuthorizer::canManageKalender($user, $event->scope_type, $event->scope_id)) {
            abort(403, 'Anda tidak berwenang menghapus agenda ini.');
        }

        // 2. Boundary: Linked Announcement Event dilarang dihapus via Standalone CRUD
        if ($event->sumber !== KalenderEvent::SUMBER_MANUAL || ! empty($event->announcement_id)) {
            abort(422, 'Agenda yang terhubung dengan Pengumuman resmi tidak dapat dihapus langsung dari Kalender.');
        }

        $sebelum = [
            'judul' => $event->judul,
            'tanggal' => $event->tanggal->toDateString(),
            'waktu_mulai' => $event->waktu_mulai,
            'waktu_selesai' => $event->waktu_selesai,
            'lokasi' => $event->lokasi,
            'kategori' => $event->kategori,
            'scope_type' => $event->scope_type,
            'scope_id' => $event->scope_id,
        ];

        $eventId = $event->id;
        $eventJudul = $event->judul;
        $scopeType = $event->scope_type;
        $eventDate = Carbon::parse($event->tanggal);

        $rtId = $event->scope_type === 'rt' ? $event->scope_id : null;
        $rwId = $event->scope_type === 'rw' ? $event->scope_id : ($user->rw_id ?? $user->rt?->rw_id);

        $event->delete();

        // 3. Catat Audit Trail
        AuditLogger::log(
            aksi: AuditAction::KALENDER_EVENT_DELETED,
            targetType: 'kalender_events',
            targetId: $eventId,
            sebelum: $sebelum,
            sesudah: null,
            alasan: "Menghapus agenda kalender: {$eventJudul}",
            rtId: $rtId,
            rwId: $rwId
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Agenda berhasil dihapus dari Kalender.',
            ]);
        }

        return redirect()->route('kalender.index', [
            'scope' => $scopeType,
            'year' => $eventDate->year,
            'month' => $eventDate->month,
        ])->with('success', 'Agenda berhasil dihapus dari Kalender.');
    }
}
