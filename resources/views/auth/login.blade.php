@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto my-8" x-data="{
    fillDemo(login, pwd) {
        $refs.loginInput.value = login;
        $refs.pwdInput.value = pwd;
    }
}">
    <!-- Login Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-emerald-600 rounded-2xl mx-auto flex items-center justify-center text-white font-bold text-2xl shadow-sm mb-4">
                WD
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Masuk ke Warga Digital</h1>
            <p class="text-sm text-slate-500 mt-1.5">Portal layanan administrasi & komunitas warga RT/RW</p>
        </div>

        <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
            @csrf

            <!-- NIK / Email Input -->
            <div>
                <label for="login" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                    NIK (Warga / Pengurus) atau Email (Admin)
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="login" 
                        name="login" 
                        x-ref="loginInput"
                        value="{{ old('login') }}" 
                        placeholder="Contoh: 3273021005050001" 
                        required 
                        autofocus
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm text-slate-900 transition-all placeholder:text-slate-400"
                    >
                </div>
            </div>

            <!-- Password Input -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                    Kata Sandi
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    x-ref="pwdInput"
                    placeholder="Masukkan kata sandi akun Anda" 
                    required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm text-slate-900 transition-all placeholder:text-slate-400"
                >
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Ingat saya di perangkat ini</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-sm hover:shadow transition-all cursor-pointer flex items-center justify-center gap-2"
            >
                <span>Masuk ke Akun</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>

        <!-- Activation Notice -->
        <div class="mt-6 pt-6 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-500 mb-2">Belum pernah login atau belum punya kata sandi?</p>
            <a href="{{ route('first-time.form') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100/70 border border-emerald-200/60 px-3.5 py-1.5 rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Aktivasi Akun Warga Baru</span>
            </a>
        </div>
    </div>

    <!-- Demo Quick-Fill Box (Sangat berguna untuk Presentasi & Tanya Jawab Juri) -->
    <div class="mt-6 bg-slate-100/90 rounded-2xl border border-slate-200/80 p-5">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Akses Cepat Demo Juri</span>
        </div>
        <p class="text-xs text-slate-500 mb-3">Klik tombol role di bawah untuk mengisi form login secara otomatis:</p>
        <div class="grid grid-cols-2 gap-2 text-xs">
            <button type="button" @click="fillDemo('3273021005050001', 'password123')" class="p-2 bg-white rounded-lg border border-slate-200 hover:border-emerald-500 hover:text-emerald-700 text-left font-medium transition-colors cursor-pointer">
                🏛️ <strong class="block text-slate-800">Ketua RT 05</strong>
                <span class="text-[10px] text-slate-400">Akses penuh RT</span>
            </button>
            <button type="button" @click="fillDemo('3273021005030001', 'password123')" class="p-2 bg-white rounded-lg border border-slate-200 hover:border-emerald-500 hover:text-emerald-700 text-left font-medium transition-colors cursor-pointer">
                🏢 <strong class="block text-slate-800">Ketua RW 03</strong>
                <span class="text-[10px] text-slate-400">Akses lingkup RW</span>
            </button>
            <button type="button" @click="fillDemo('3273021005050004', 'password123')" class="p-2 bg-white rounded-lg border border-slate-200 hover:border-emerald-500 hover:text-emerald-700 text-left font-medium transition-colors cursor-pointer">
                💰 <strong class="block text-slate-800">Bendahara RT</strong>
                <span class="text-[10px] text-slate-400">Kelola kas RT</span>
            </button>
            <button type="button" @click="fillDemo('3273021005050010', 'password123')" class="p-2 bg-white rounded-lg border border-slate-200 hover:border-emerald-500 hover:text-emerald-700 text-left font-medium transition-colors cursor-pointer">
                👥 <strong class="block text-slate-800">Warga RT 05</strong>
                <span class="text-[10px] text-slate-400">Warga aktif</span>
            </button>
        </div>
    </div>
</div>
@endsection
