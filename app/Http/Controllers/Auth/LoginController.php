<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login single portal.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi user.
     * Mendukung login dengan NIK (warga/pengurus) atau Email (super admin).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'NIK atau Email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginInput = trim($credentials['login']);
        $password = $credentials['password'];

        // Cek apakah input berupa email atau NIK
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        $user = $isEmail
            ? User::where('email', $loginInput)->first()
            : User::where('nik', $loginInput)->first();

        if (!$user) {
            return back()->withInput($request->only('login'))->withErrors([
                'login' => 'Akun tidak ditemukan. Periksa kembali NIK atau Email Anda.',
            ]);
        }

        // Cek apakah warga belum melakukan aktivasi password pertama kali
        if ($user->status === 'belum_daftar') {
            return redirect()->route('first-time.form')->with([
                'info' => 'Akun Anda belum diaktifkan. Silakan selesaikan proses aktivasi akun warga di bawah ini.',
                'prefill_nik' => $loginInput,
            ]);
        }

        // Verifikasi kata sandi
        if (!Hash::check($password, $user->password)) {
            return back()->withInput($request->only('login'))->withErrors([
                'password' => 'Kata sandi yang Anda masukkan salah.',
            ]);
        }

        // Login ke session
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->update(['last_login' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Logout pengguna dari sistem.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
