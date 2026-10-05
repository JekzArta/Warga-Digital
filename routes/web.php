<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\FirstTimePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\KomunitasController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\UmkmController;
use App\Http\Controllers\VerifikasiSuratController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root ke dashboard jika login, atau ke login page jika belum
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Verifikasi Publik Dokumen Resmi (Bisa diakses pihak ketiga tanpa login, rate-limited 10 req/menit/IP)
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/verifikasi', [VerifikasiSuratController::class, 'show'])->name('verifikasi.index');
    Route::get('/verifikasi/{kode}', [VerifikasiSuratController::class, 'show'])->name('verifikasi.detail');
});

// Otentikasi Publik / Guest
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    Route::get('/aktivasi-akun', [FirstTimePasswordController::class, 'showForm'])->name('first-time.form');
    Route::post('/aktivasi-akun', [FirstTimePasswordController::class, 'activate'])->name('first-time.activate');
});

// Area Terproteksi Sesi Warga & Pengurus
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Fitur 1: Pengajuan Surat Otomatis
    Route::prefix('surat')->name('surat.')->group(function () {
        Route::get('/', [SuratController::class, 'index'])->name('index');
        Route::get('/buat/{jenis}', [SuratController::class, 'create'])->name('create');
        Route::post('/', [SuratController::class, 'store'])->name('store');
        Route::get('/{id}', [SuratController::class, 'show'])->name('show');
        Route::get('/{id}/unduh-pdf', [SuratController::class, 'downloadPdf'])->name('download-pdf');
        Route::post('/{id}/kelengkapan', [SuratController::class, 'uploadKelengkapan'])->name('kelengkapan');
    });

    // Meja Moderasi Surat Pengurus RT (Ketua RT, Wakil RT, Sekretaris)
    Route::middleware('role:ketua_rt,wakil_rt,sekretaris')->prefix('admin/surat')->name('admin.surat.')->group(function () {
        Route::get('/', [SuratController::class, 'adminIndex'])->name('index');
        Route::post('/{id}/approve', [SuratController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [SuratController::class, 'reject'])->name('reject');
        Route::post('/{id}/minta-kelengkapan', [SuratController::class, 'requestCompletion'])->name('request-completion');
    });

    // Fitur 4: Meja Audit Akuntabilitas (System-Wide Audit Trail)
    Route::middleware('role:super_admin,ketua_rw,ketua_rt,wakil_rt,sekretaris,bendahara')->prefix('admin/audit')->name('admin.audit.')->group(function () {
        Route::get('/', [AuditController::class, 'index'])->name('index');
    });

    // Fitur 2: Ruang Komunitas (3 Layer: Pengumuman, Chat Bebas, Forum Warga)
    Route::prefix('komunitas')->name('komunitas.')->group(function () {
        // Hub / Beranda Komunitas
        Route::get('/', [KomunitasController::class, 'index'])->name('index');

        // Layer 1: Announcement (Pengumuman)
        Route::get('/pengumuman/{id}', [KomunitasController::class, 'showPengumuman'])->name('pengumuman.show');
        Route::post('/pengumuman', [KomunitasController::class, 'storePengumuman'])->name('pengumuman.store');
        Route::post('/pengumuman/{id}/toggle-pin', [KomunitasController::class, 'togglePinPengumuman'])->name('pengumuman.toggle-pin');
        Route::post('/pengumuman/{id}/komentar', [KomunitasController::class, 'storePengumumanKomentar'])->name('pengumuman.komentar');
        Route::post('/pengumuman/{id}/pembaruan', [KomunitasController::class, 'storePembaruan'])->name('pengumuman.pembaruan');
        Route::post('/pengumuman/{id}/deactivate', [KomunitasController::class, 'deactivatePengumuman'])->name('pengumuman.deactivate');
        Route::post('/pengumuman/{id}/link-forum', [KomunitasController::class, 'linkForum'])->name('pengumuman.link-forum');

        // Layer 2: Chat Bebas
        Route::get('/chat/messages', [KomunitasController::class, 'getChatMessages'])->name('chat.messages');
        Route::post('/chat/messages', [KomunitasController::class, 'sendChatMessage'])->name('chat.send');

        // Layer 3: Forum Warga
        Route::post('/forum/thread', [KomunitasController::class, 'storeThread'])->name('forum.thread.store');
        Route::get('/forum/thread/{id}', [KomunitasController::class, 'showThread'])->name('forum.thread.show');
        Route::post('/forum/thread/{id}/post', [KomunitasController::class, 'storePost'])->name('forum.post.store');
        Route::post('/forum/thread/{id}/moderate', [KomunitasController::class, 'moderateThread'])->name('forum.thread.moderate');
    });

    // Fitur 3: Transparansi Anggaran (Kas RT)
    Route::prefix('kas')->name('kas.')->group(function () {
        Route::get('/', [KasController::class, 'index'])->name('index');
        Route::post('/', [KasController::class, 'store'])
            ->middleware('role:bendahara,ketua_rt,wakil_rt,super_admin')
            ->name('store');
        Route::post('/{id}/koreksi', [KasController::class, 'koreksi'])
            ->middleware('role:bendahara,ketua_rt,wakil_rt,super_admin')
            ->name('koreksi');
    });

    // Fitur 5: UMKM (Jasa & Barang)
    Route::prefix('umkm')->name('umkm.')->group(function () {
        Route::get('/', [UmkmController::class, 'index'])->name('index');
        Route::post('/', [UmkmController::class, 'store'])->name('store');
        Route::match(['post', 'patch'], '/no-hp', [UmkmController::class, 'updateNoHp'])->name('updateNoHp');
        Route::match(['put', 'patch'], '/{id}', [UmkmController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [UmkmController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::post('/{id}/approve', [UmkmController::class, 'approve'])->whereNumber('id')->name('approve');
        Route::post('/{id}/tolak', [UmkmController::class, 'tolak'])->whereNumber('id')->name('tolak');
    });
});

