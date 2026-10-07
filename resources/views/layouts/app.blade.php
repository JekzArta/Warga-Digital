<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f6f1e4]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Warga Digital — Platform Administrasi & Komunitas RT/RW' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: light;
            --cream: #f6f1e4;
            --cream-soft: #faf7f0;
            --paper: #ffffff;
            --ink: #111827;
            --ink-secondary: #374151;
            --muted: #4b5563;
            --green: #10231e;
            --green-soft: #1b342d;
            --button-primary: #e5a53f;
            --orange: #d97706;
            --line: #e4ded1;
            --line-light: #ece7dc;
            --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
            --font: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            font-family: var(--font);
            background-color: var(--cream);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Custom scrollbar for sidebar & content */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(16, 35, 30, 0.15);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(16, 35, 30, 0.3);
        }

        [x-cloak] {
            display: none !important;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-[#111827] antialiased flex bg-[#f6f1e4]" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div 
        x-show="sidebarOpen" 
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-[#10231e]/70 z-40 lg:hidden backdrop-blur-xs"
        @click="sidebarOpen = false"
        style="display: none;"
    ></div>

    <!-- LEFT SIDEBAR (Deep Evergreen #10231e matching brand identity) -->
    <aside 
        class="fixed inset-y-0 left-0 z-50 w-64 bg-[#10231e] text-slate-300 flex flex-col justify-between transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static shrink-0 border-r border-white/10 shadow-2xl lg:shadow-none"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Top: Brand Header & Navigation Items -->
        <div class="flex flex-col flex-1  overflow-y-auto">
            <!-- Brand Logo -->
            <div class="h-20 flex items-center gap-3 px-6 border-b border-white/10">
                <img src="{{ asset('assets/logo.png') }}" alt="Logo Warga Digital" class="w-10 h-10 rounded-2xl border border-white/10 object-cover shadow-md shrink-0">
                <div>
                    <span class="text-base font-extrabold text-white tracking-tight block leading-tight">Warga Digital</span>
                    <span class="text-[10px] text-[#e5a53f] font-bold tracking-widest uppercase block mt-0.5">Portal RT/RW</span>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="p-4 space-y-6 text-sm">
                <!-- Group: UTAMA -->
                <div>
                    <span class="text-[10px] font-bold text-white/40 tracking-widest uppercase px-3 block mb-1.5">Utama</span>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                        <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Beranda</span>
                    </a>
                </div>

                <!-- Group: LAYANAN -->
                <div>
                    <span class="text-[10px] font-bold text-white/40 tracking-widest uppercase px-3 block mb-1.5">Layanan</span>
                    <div class="space-y-1">
                        <a href="{{ route('surat.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ request()->routeIs('surat.*') && !request()->routeIs('admin.surat.*') ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                            <svg class="w-4 h-4 {{ request()->routeIs('surat.*') && !request()->routeIs('admin.surat.*') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Pengajuan Surat</span>
                        </a>

                        @if(auth()->user()?->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris']))
                        <a href="{{ route('admin.surat.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ request()->routeIs('admin.surat.*') ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 {{ request()->routeIs('admin.surat.*') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span>Meja Verifikasi RT</span>
                            </div>
                            <span class="px-2 py-0.5 text-[9px] font-extrabold rounded-md {{ request()->routeIs('admin.surat.*') ? 'bg-[#e5a53f] text-[#10231e]' : 'bg-[#e5a53f]/20 text-emerald-400 border border-[#e5a53f]/30' }}">Admin</span>
                        </a>
                        @endif

                        @if(auth()->user()?->is_super_admin || auth()->user()?->hasRole(['ketua_rw', 'ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara']))
                        <a href="{{ route('admin.audit.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ request()->routeIs('admin.audit.*') ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 {{ request()->routeIs('admin.audit.*') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Meja Audit</span>
                            </div>
                            <span class="px-2 py-0.5 text-[9px] font-extrabold rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Audit</span>
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Group: KOMUNITAS -->
                <div>
                    <span class="text-[10px] font-bold text-white/40 tracking-widest uppercase px-3 block mb-1.5">Komunitas</span>
                    <div class="space-y-1">
                        <a href="{{ route('komunitas.index', ['tab' => 'pengumuman']) }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ request()->routeIs('komunitas.index') && (request('tab') === 'pengumuman' || !request()->has('tab')) ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                            <svg class="w-4 h-4 {{ request()->routeIs('komunitas.index') && (request('tab') === 'pengumuman' || !request()->has('tab')) ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                            <span>Pengumuman</span>
                        </a>
                        <a href="{{ route('komunitas.index', ['tab' => 'chat']) }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ request()->routeIs('komunitas.*') && request('tab') === 'chat' ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                            <svg class="w-4 h-4 {{ request()->routeIs('komunitas.*') && request('tab') === 'chat' ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <span>Chat Bebas</span>
                        </a>
                        <a href="{{ route('komunitas.index', ['tab' => 'forum']) }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ (request()->routeIs('komunitas.*') && request('tab') === 'forum') || request()->routeIs('komunitas.forum.*') ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                            <svg class="w-4 h-4 {{ (request()->routeIs('komunitas.*') && request('tab') === 'forum') || request()->routeIs('komunitas.forum.*') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                            </svg>
                            <span>Forum Warga</span>
                        </a>
                        <a href="{{ route('kalender.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ request()->routeIs('kalender.*') ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                            <svg class="w-4 h-4 {{ request()->routeIs('kalender.*') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Kalender</span>
                        </a>
                        <a href="{{ route('galeri.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ request()->routeIs('galeri.*') ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                            <svg class="w-4 h-4 {{ request()->routeIs('galeri.*') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Galeri Kegiatan</span>
                        </a>
                    </div>
                </div>

                <!-- Group: KEUANGAN & EKONOMI -->
                <div>
                    <span class="text-[10px] font-bold text-white/40 tracking-widest uppercase px-3 block mb-1.5">Keuangan & Ekonomi</span>
                    <div class="space-y-1">
                        <a href="{{ route('umkm.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ request()->routeIs('umkm.*') ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                            <svg class="w-4 h-4 {{ request()->routeIs('umkm.*') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>UMKM Warga</span>
                        </a>
                        <a href="{{ route('kas.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 active:scale-[0.98] {{ request()->routeIs('kas.*') ? 'bg-white/10 text-white font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium' }}">
                            <svg class="w-4 h-4 {{ request()->routeIs('kas.*') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <span>Kas & Iuran</span>
                        </a>
                    </div>
                </div>

                <!-- Group: AKUN -->
                <div>
                    <span class="text-[10px] font-bold text-white/40 tracking-widest uppercase px-3 block mb-1.5">Akun</span>
                    <div class="space-y-1">
                        @auth
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-rose-300/90 hover:text-white hover:bg-rose-500/20 transition-all duration-150 active:scale-[0.98] cursor-pointer text-left">
                                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        @endauth
                    </div>
                </div>
            </nav>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-white/10 text-[11px] text-stone-400 flex justify-between items-center">
            <span class="font-bold text-white/70">Warga Digital</span>
            <span class="font-mono text-[10px] text-white/40">v1.0.0</span>
        </div>
    </aside>

    <!-- RIGHT CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- TOP HEADER BAR (Frosted Cream sticky topbar) -->
        <header class="h-20 bg-[#f6f1e4]/90 backdrop-blur-md border-b border-[#e4ded1] px-4 sm:px-8 flex items-center justify-between sticky top-0 z-30 transition-colors">
            <!-- Left: Page Title & Mobile Toggle -->
            <div class="flex items-center gap-3">
                <button 
                    type="button" 
                    @click="sidebarOpen = true"
                    class="lg:hidden p-2 rounded-xl text-stone-700 hover:bg-[#e4ded1]/60 transition-colors cursor-pointer"
                    aria-label="Buka navigasi menu"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <img src="{{ asset('assets/logo.png') }}" alt="Logo Warga Digital" class="w-8 h-8 rounded-xl object-cover lg:hidden shrink-0 shadow-xs border border-[#e4ded1]">
                <h1 class="text-xl sm:text-2xl font-extrabold text-[#111827] tracking-tight">
                    {{ $pageTitle ?? 'Beranda' }}
                </h1>
            </div>

            <!-- Right: Tenant Badge, Notification Bell, User Capsule -->
            <div class="flex items-center gap-2.5 sm:gap-3.5">
                @auth
                    <!-- Tenant Scope Pill (e.g. RT 05 • RW 03) -->
                    <div class="bg-[#e8ede7] text-[#10231e] px-3.5 py-1.5 rounded-full text-xs font-bold border border-[#c8d4c7] flex items-center gap-1.5 shadow-2xs">
                        <span>
                            @if(Auth::user()->is_super_admin)
                                SUPER ADMIN
                            @elseif(Auth::user()->rt)
                                RT 0{{ Auth::user()->rt->nomor_rt }} • RW 0{{ Auth::user()->rt->rw->nomor_rw }}
                            @elseif(Auth::user()->rw)
                                RW 0{{ Auth::user()->rw->nomor_rw }}
                            @else
                                WARGA
                            @endif
                        </span>
                    </div>

                    <!-- User Profile Pill -->
                    @php
                        $namaParts = explode(' ', Auth::user()->nama);
                        $initials = strtoupper(substr($namaParts[0] ?? 'W', 0, 1) . substr($namaParts[1] ?? ($namaParts[0] ?? 'D'), 0, 1));
                    @endphp
                    <div class="bg-white text-stone-900 pl-1.5 pr-3.5 py-1.5 rounded-full flex items-center gap-2 border border-[#e4ded1] shadow-2xs">
                        <div class="w-7 h-7 rounded-full bg-[#c68b59] text-white flex items-center justify-center font-bold text-xs shadow-inner shrink-0">
                            {{ $initials }}
                        </div>
                        <div class="flex flex-col text-left leading-tight">
                            <span class="text-xs font-bold text-[#111827] truncate max-w-[110px] sm:max-w-none">{{ Auth::user()->nama }}</span>
                            <span class="text-[10px] text-stone-500 font-medium">{{ Auth::user()->getHighestRoleBadge() }}</span>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl bg-[#10231e] hover:bg-[#1b342d] text-white text-xs font-bold transition-all shadow-xs active:scale-95">
                        Masuk
                    </a>
                @endauth
            </div>
        </header>

        <!-- MAIN BODY CONTENT -->
        <main class="flex-1 p-4 sm:p-8 max-w-[1400px] w-full mx-auto">
            <!-- Flash Messages (Refined Editorial Style) -->
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="mb-6 p-4 rounded-2xl bg-[#ecf7ed] border border-[#c3e6cb] text-[#1e5d36] flex items-center justify-between gap-3 text-sm shadow-xs">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">{{ session('success') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="text-emerald-800/60 hover:text-emerald-900 transition-colors p-1 rounded-lg hover:bg-emerald-600/10 cursor-pointer" title="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="mb-6 p-4 rounded-2xl bg-[#fdf2f2] border border-[#f8b4b4] text-[#9b1c1c] flex items-center justify-between gap-3 text-sm shadow-xs">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-semibold">{{ session('error') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="text-rose-700/60 hover:text-rose-900 transition-colors p-1 rounded-lg hover:bg-rose-600/10 cursor-pointer" title="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            @if(session('info'))
                <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="mb-6 p-4 rounded-2xl bg-[#f0f9ff] border border-[#bae6fd] text-[#0369a1] flex items-center justify-between gap-3 text-sm shadow-xs">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-sky-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">{{ session('info') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="text-sky-700/60 hover:text-sky-900 transition-colors p-1 rounded-lg hover:bg-sky-600/10 cursor-pointer" title="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

</body>
</html>
