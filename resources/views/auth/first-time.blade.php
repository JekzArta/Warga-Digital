@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto my-8" x-data="{
    fillDemo() {
        $refs.nikInput.value = '3273021005050041';
        $refs.tglInput.value = '1995-08-17';
        $refs.pwd1.value = 'password123';
        $refs.pwd2.value = 'password123';
    }
}">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl mx-auto flex items-center justify-center border border-emerald-200/80 mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Aktivasi Akun Warga</h1>
            <p class="text-sm text-slate-500 mt-1.5">Untuk warga yang datanya telah didaftarkan oleh pengurus RT</p>
        </div>

        <form method="POST" action="{{ route('first-time.activate') }}" class="space-y-4">
            @csrf

            <!-- NIK Input -->
            <div>
                <label for="nik" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                    Nomor Induk Kependudukan (NIK)
                </label>
                <input 
                    type="text" 
                    id="nik" 
                    name="nik" 
                    x-ref="nikInput"
                    value="{{ old('nik', $prefill_nik ?? '') }}" 
                    placeholder="16 digit nomor KTP Anda" 
                    maxlength="16"
                    required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm text-slate-900 transition-all placeholder:text-slate-400"
                >
            </div>

            <!-- Tanggal Lahir (KTP) -->
            <div>
                <label for="tanggal_lahir" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                    Tanggal Lahir (Verifikasi Identitas)
                </label>
                <input 
                    type="date" 
                    id="tanggal_lahir" 
                    name="tanggal_lahir" 
                    x-ref="tglInput"
                    value="{{ old('tanggal_lahir') }}" 
                    required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm text-slate-900 transition-all"
                >
                <p class="text-[11px] text-slate-500 mt-1">Sistem mencocokkan tanggal lahir dengan arsip RT untuk mencegah penyalahgunaan NIK.</p>
            </div>

            <!-- Password Baru -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                    Kata Sandi Baru
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    x-ref="pwd1"
                    placeholder="Minimal 8 karakter" 
                    required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm text-slate-900 transition-all placeholder:text-slate-400"
                >
            </div>

            <!-- Konfirmasi Password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                    Ulangi Kata Sandi Baru
                </label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    x-ref="pwd2"
                    placeholder="Ketik ulang kata sandi baru" 
                    required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm text-slate-900 transition-all placeholder:text-slate-400"
                >
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-sm hover:shadow transition-all cursor-pointer flex items-center justify-center gap-2 mt-2"
            >
                <span>Aktifkan Akun & Masuk</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center">
            <a href="{{ route('login') }}" class="text-xs font-medium text-slate-500 hover:text-emerald-600">
                &larr; Kembali ke Halaman Masuk
            </a>
        </div>
    </div>

    <!-- Demo Quick-Fill untuk pengujian juri -->
    <div class="mt-6 bg-slate-100/90 rounded-2xl border border-slate-200/80 p-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Demo Simulasi Aktivasi</span>
        </div>
        <p class="text-xs text-slate-500 mb-3">Klik untuk menguji skenario warga baru yang belum mengaktifkan akun:</p>
        <button type="button" @click="fillDemo()" class="w-full p-2.5 bg-white rounded-lg border border-slate-200 hover:border-emerald-500 hover:text-emerald-700 text-left font-medium text-xs transition-colors cursor-pointer flex items-center justify-between">
            <div>
                <strong class="block text-slate-800">Warga Belum Daftar: "Siti Rahmawati"</strong>
                <span class="text-[11px] text-slate-400">NIK: 3273021005050041 • Lahir: 17 Agt 1995</span>
            </div>
            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Isi Otomatis</span>
        </button>
    </div>
</div>
@endsection
