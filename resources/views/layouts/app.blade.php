<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Warga Digital — Platform Administrasi & Komunitas RT/RW' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800 antialiased flex flex-col" x-data="{ mobileMenuOpen: false }">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold text-xl shadow-sm">
                            WD
                        </div>
                        <div>
                            <span class="text-lg font-bold text-slate-900 tracking-tight block leading-tight">Warga Digital</span>
                            <span class="text-xs text-slate-500 font-medium block">RT/RW Terpadu</span>
                        </div>
                    </a>
                </div>

                <!-- Center: Tenant Scope Badge -->
                @auth
                <div class="hidden md:flex items-center gap-2 bg-slate-100/80 px-3 py-1.5 rounded-full border border-slate-200 text-xs text-slate-600 font-medium">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Wilayah: </span>
                    <strong class="text-slate-800">
                        @if(Auth::user()->is_super_admin)
                            Super Admin (Lintas Wilayah)
                        @elseif(Auth::user()->rt)
                            RT 0{{ Auth::user()->rt->nomor_rt }} / RW 0{{ Auth::user()->rt->rw->nomor_rw }}, {{ Auth::user()->rt->rw->klien->nama ?? 'Kelurahan' }}
                        @elseif(Auth::user()->rw)
                            RW 0{{ Auth::user()->rw->nomor_rw }}, {{ Auth::user()->rw->klien->nama ?? 'Kelurahan' }}
                        @else
                            Warga Digital
                        @endif
                    </strong>
                </div>
                @endauth

                <!-- Right: User Profile & Logout -->
                <div class="flex items-center gap-3">
                    @auth
                        <!-- User Role Badge -->
                        <div class="hidden sm:flex flex-col text-right">
                            <span class="text-sm font-semibold text-slate-800 leading-tight">{{ Auth::user()->nama }}</span>
                            <span class="text-xs font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md inline-block self-end border border-emerald-200/60 mt-0.5">
                                {{ Auth::user()->getHighestRoleBadge() }}
                            </span>
                        </div>

                        <!-- Logout Form -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="p-2 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer text-xs sm:text-sm font-medium flex items-center gap-1.5 border border-slate-200/80" title="Keluar dari sistem">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="hidden sm:inline">Keluar</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Masuk</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3 text-sm shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-6 p-4 rounded-xl bg-sky-50 border border-sky-200 text-sky-800 flex items-center gap-3 text-sm shadow-xs">
                <svg class="w-5 h-5 text-sky-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-xs">
                <div class="flex items-center gap-2 font-semibold mb-1">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span>Terjadi kesalahan:</span>
                </div>
                <ul class="list-disc list-inside ml-2 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500 mt-auto">
        <div class="max-w-7xl mx-auto px-4">
            <span>&copy; 2026 Warga Digital — Tim CodeRanger (SATU CREANOVA 2026)</span>
        </div>
    </footer>

</body>
</html>
