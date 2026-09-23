<?php

use App\Http\Controllers\Auth\FirstTimePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuratController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root ke dashboard jika login, atau ke login page jika belum
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
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
});

