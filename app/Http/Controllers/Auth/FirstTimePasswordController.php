<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class FirstTimePasswordController extends Controller
{
    /**
     * Tampilkan form aktivasi akun pertama kali untuk warga.
     */
    public function showForm(Request $request): View
    {
        return view('auth.first-time', [
            'prefill_nik' => session('prefill_nik', ''),
        ]);
    }

    /**
     * Proses aktivasi akun warga:
     * Verifikasi kecocokan NIK dan Tanggal Lahir yang diimpor oleh pengurus RT.
     */
    public function activate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'digits:16'],
            'tanggal_lahir' => ['required', 'date'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nik.required' => 'NIK 16 digit wajib diisi.',
            'nik.digits' => 'NIK harus berjumlah tepat 16 digit angka.',
            'tanggal_lahir.required' => 'Tanggal lahir sesuai KTP wajib diisi untuk verifikasi identitas.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter demi keamanan akun Anda.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = User::where('nik', $validated['nik'])->first();

        if (!$user) {
            return back()->withInput($request->except('password', 'password_confirmation'))->withErrors([
                'nik' => 'NIK tidak terdaftar dalam data warga RT/RW. Silakan hubungi pengurus RT Anda.',
            ]);
        }

        if ($user->status === 'aktif') {
            return redirect()->route('login')->with('info', 'Akun Anda sudah pernah diaktifkan sebelumnya. Silakan langsung login.');
        }

        // Verifikasi kecocokan tanggal lahir
        if (!$user->tanggal_lahir || $user->tanggal_lahir->format('Y-m-d') !== $validated['tanggal_lahir']) {
            return back()->withInput($request->except('password', 'password_confirmation'))->withErrors([
                'tanggal_lahir' => 'Tanggal lahir tidak cocok dengan data KTP yang tercatat di RT. Pastikan data benar.',
            ]);
        }

        // Simpan password baru dan aktifkan akun
        $user->update([
            'password' => Hash::make($validated['password']),
            'status' => 'aktif',
            'last_login' => now(),
        ]);

        // Catat di audit trail
        AuditLogger::log(
            aksi: 'aktivasi_akun_warga',
            targetType: 'users',
            targetId: $user->id,
            sebelum: ['status' => 'belum_daftar'],
            sesudah: ['status' => 'aktif'],
            alasan: 'Warga melakukan aktivasi mandiri pertama kali via verifikasi NIK dan tanggal lahir',
            rtId: $user->rt_id,
            rwId: $user->rw_id ?? $user->rt?->rw_id
        );

        // Langsung login-kan warga
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', "Selamat datang di Warga Digital, {$user->nama}! Akun Anda telah aktif.");
    }
}
