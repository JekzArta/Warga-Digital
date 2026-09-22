<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F4EFEA]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Warga Digital — Platform Administrasi & Komunitas RT/RW' }}</title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        /* Custom scrollbar for sidebar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.15);
            border-radius: 4px;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800 antialiased flex bg-[#F4EFEA]" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div 
        x-show="sidebarOpen" 
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 z-40 lg:hidden backdrop-blur-xs"
        @click="sidebarOpen = false"
        style="display: none;"
    ></div>

    <!-- LEFT SIDEBAR (Dark Theme #131919 matching Figma) -->
    <aside 
        class="fixed inset-y-0 left-0 z-50 w-64 bg-[#131919] text-slate-300 flex flex-col justify-between transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static shrink-0 border-r border-slate-800/80 shadow-xl lg:shadow-none"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Top: Brand Header & Navigation Items -->
        <div class="flex flex-col flex-1 overflow-y-auto">
            <!-- Brand Logo -->
            <div class="h-20 flex items-center gap-3 px-6 border-b border-white/5">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center text-white shadow-md shadow-emerald-950/40">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <span class="text-base font-bold text-white tracking-tight block leading-none">Warga Digital</span>
                    <span class="text-[11px] text-emerald-400/90 font-medium block mt-1 tracking-wider uppercase">Portal RT/RW</span>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="p-4 space-y-6 text-sm">
                <!-- Group: UTAMA -->
                <div>
                    <span class="text-[10px] font-bold text-slate-500 tracking-wider uppercase px-3 block mb-1">Utama</span>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Beranda</span>
                    </a>
                </div>

                <!-- Group: LAYANAN -->
                <div>
                    <span class="text-[10px] font-bold text-slate-500 tracking-wider uppercase px-3 block mb-1">Layanan</span>
                    <a href="#surat" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Pengajuan Surat</span>
                    </a>
                </div>

                <!-- Group: KOMUNITAS -->
                <div>
                    <span class="text-[10px] font-bold text-slate-500 tracking-wider uppercase px-3 block mb-1">Komunitas</span>
                    <div class="space-y-1">
                        <a href="#pengumuman" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                            <span>Pengumuman</span>
                        </a>
                        <a href="#forum" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <span>Forum Warga</span>
                        </a>
                        <a href="#kalender" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Kalender</span>
                        </a>
                        <a href="#galeri" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Galeri Kegiatan</span>
                        </a>
                    </div>
                </div>

                <!-- Group: KEUANGAN & EKONOMI -->
                <div>
                    <span class="text-[10px] font-bold text-slate-500 tracking-wider uppercase px-3 block mb-1">Keuangan & Ekonomi</span>
                    <div class="space-y-1">
                        <a href="#umkm" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>Belanja UMKM Sini</span>
                        </a>
                        <a href="#anggaran" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <span>Kas & Iuran</span>
                        </a>
                    </div>
                </div>

                <!-- Group: AKUN & KONTROL -->
                <div>
                    <span class="text-[10px] font-bold text-slate-500 tracking-wider uppercase px-3 block mb-1">Akun</span>
                    <div class="space-y-1">
                        @auth
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-950/20 transition-all cursor-pointer text-left">
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
        <div class="p-4 border-t border-white/5 text-[11px] text-slate-500 flex justify-between items-center">
            <span>Warga Digital</span>
            <span class="font-mono text-[10px] text-slate-600">V.1.0</span>
        </div>
    </aside>

    <!-- RIGHT CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- TOP HEADER BAR (Matching Figma) -->
        <header class="h-20 bg-[#F4EFEA] border-b border-stone-300/60 px-4 sm:px-8 flex items-center justify-between sticky top-0 z-30">
            <!-- Left: Page Title & Mobile Toggle -->
            <div class="flex items-center gap-3">
                <button 
                    type="button" 
                    @click="sidebarOpen = true"
                    class="lg:hidden p-2 rounded-xl text-stone-700 hover:bg-stone-200/80 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-xl sm:text-2xl font-bold text-stone-900 tracking-tight">
                    {{ $pageTitle ?? 'Beranda' }}
                </h1>
            </div>

            <!-- Right: Tenant Badge, Notification Bell, User Capsule -->
            <div class="flex items-center gap-2.5 sm:gap-3.5">
                @auth
                    <!-- Tenant Scope Pill (e.g. RT 05 • RW 03) -->
                    <div class="bg-[#E4ECE5] text-[#2C6E49] px-3.5 py-1.5 rounded-full text-xs font-bold border border-[#CFDFD1] flex items-center gap-1.5 shadow-2xs">
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

                    <!-- Notification Bell -->
                    <button class="w-9 h-9 rounded-full bg-white border border-stone-300/80 text-stone-700 flex items-center justify-center hover:bg-stone-100 transition-colors relative shadow-2xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="w-2 h-2 rounded-full bg-rose-500 absolute top-2 right-2 ring-2 ring-white"></span>
                    </button>

                    <!-- User Profile Pill -->
                    @php
                        $namaParts = explode(' ', Auth::user()->nama);
                        $initials = strtoupper(substr($namaParts[0] ?? 'W', 0, 1) . substr($namaParts[1] ?? ($namaParts[0] ?? 'D'), 0, 1));
                    @endphp
                    <div class="bg-[#E7D6C4] text-stone-900 pl-1.5 pr-3.5 py-1.5 rounded-full flex items-center gap-2 border border-[#D9C4AF] shadow-2xs">
                        <div class="w-7 h-7 rounded-full bg-[#C68B59] text-white flex items-center justify-center font-bold text-xs shadow-inner">
                            {{ $initials }}
                        </div>
                        <div class="flex flex-col text-left leading-none">
                            <span class="text-xs font-bold text-stone-900 truncate max-w-[110px] sm:max-w-none">{{ Auth::user()->nama }}</span>
                            <span class="text-[10px] text-stone-600 font-medium mt-0.5">{{ Auth::user()->getHighestRoleBadge() }}</span>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs">
                        Masuk
                    </a>
                @endauth
            </div>
        </header>

        <!-- MAIN BODY CONTENT -->
        <main class="flex-1 p-4 sm:p-8 max-w-[1400px] w-full mx-auto">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-6 p-4 rounded-2xl bg-[#EAF5EC] border border-[#BFDFCA] text-[#1E5D36] flex items-center gap-3 text-sm shadow-xs">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="mb-6 p-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-800 flex items-center gap-3 text-sm shadow-xs">
                    <svg class="w-5 h-5 text-sky-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

</body>
</html>
