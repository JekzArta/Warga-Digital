<?php

use App\Http\Controllers\Auth\FirstTimePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
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
});
