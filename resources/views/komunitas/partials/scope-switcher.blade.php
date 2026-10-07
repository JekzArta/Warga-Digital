<!-- Top Header & Scope Switcher -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-stone-200/90">
    <div>
        <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 font-bold text-sm border border-emerald-100/80 shadow-2xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </span>
            <h1 class="text-xl font-bold text-stone-900 tracking-tight">Ruang Komunitas</h1>
        </div>
        <p class="text-xs text-stone-500 mt-1 leading-relaxed">Platform terstruktur untuk pengumuman resmi, obrolan santai, dan musyawarah warga.</p>
    </div>

    <!-- Scope Switcher (RT vs RW) -->
    <div class="flex items-center gap-1.5 p-1 bg-stone-100/90 rounded-xl border border-stone-200/80 self-start sm:self-auto">
        @if(auth()->user()->rt_id)
        <a href="{{ route('komunitas.index', ['scope' => 'rt', 'tab' => $activeTab]) }}" 
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $requestedScope === 'rt' ? 'bg-white text-emerald-800 shadow-xs border border-stone-200/60' : 'text-stone-500 hover:text-stone-800' }}">
            Lingkup RT ({{ auth()->user()->rt?->kode_rt ?? 'RT 05' }})
        </a>
        @endif
        <a href="{{ route('komunitas.index', ['scope' => 'rw', 'tab' => $activeTab]) }}" 
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $requestedScope === 'rw' ? 'bg-white text-emerald-800 shadow-xs border border-stone-200/60' : 'text-stone-500 hover:text-stone-800' }}">
            Kawasan RW ({{ auth()->user()->rw?->kode_rw ?? auth()->user()->rt?->rw?->kode_rw ?? 'RW 03' }})
        </a>
    </div>
</div>
