<div class="space-y-4">
    <!-- Action Header & Category Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <!-- Category Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
            <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'forum']) }}" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ is_null($selectedCategoryId) ? 'bg-emerald-700 text-white shadow-xs' : 'bg-white text-stone-600 hover:bg-stone-50 border border-stone-200' }}">
                Semua Kategori
            </a>
            @foreach($forumCategories as $cat)
            <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'forum', 'category_id' => $cat->id]) }}" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ $selectedCategoryId === $cat->id ? 'bg-emerald-700 text-white shadow-xs' : 'bg-white text-stone-600 hover:bg-stone-50 border border-stone-200' }}">
                {{ $cat->nama }}
            </a>
            @endforeach
        </div>

        <button @click="showThreadModal = true" type="button" 
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Buat Usulan Baru</span>
        </button>
    </div>

    <!-- Thread List -->
    @if($threads->isEmpty())
    <div class="p-12 text-center bg-white rounded-2xl border border-stone-200/90 shadow-xs">
        <div class="w-12 h-12 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
            </svg>
        </div>
        <h4 class="text-sm font-bold text-stone-800 mb-1">Belum Ada Usulan Diskusi</h4>
        <p class="text-xs text-stone-500 max-w-sm mx-auto leading-relaxed">Belum ada diskusi topik pada kategori atau lingkup wilayah ini. Mulailah usulan baru untuk bermusyawarah bersama.</p>
    </div>
    @else
    <div class="space-y-3">
        @foreach($threads as $th)
        <div class="bg-white p-5 rounded-2xl border border-stone-200/90 shadow-xs hover:border-stone-300 transition-all flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-1.5 flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-stone-100 text-stone-700">
                        {{ $th->category?->nama }}
                    </span>

                    @if($th->is_pinned)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/10 text-amber-800 border border-amber-300/40">
                        <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                        </svg>
                        Disematkan
                    </span>
                    @endif

                    @if($th->status === 'closed')
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-stone-100 text-stone-500 border border-stone-200">
                        Ditutup
                    </span>
                    @else
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        Aktif
                    </span>
                    @endif
                </div>

                <a href="{{ route('komunitas.forum.thread.show', $th->id) }}" class="text-base font-bold text-stone-900 hover:text-emerald-800 transition-colors block leading-snug">
                    {{ $th->judul }}
                </a>

                <!-- Thread Content Excerpt / Snippet -->
                <p class="text-xs text-stone-600 line-clamp-2 leading-relaxed">
                    {{ \Illuminate\Support\Str::limit($th->konten, 180) }}
                </p>

                <div class="flex items-center gap-2 text-xs text-stone-400">
                    <span class="font-medium text-stone-700">{{ $th->author?->nama }}</span>
                    @if(!empty($th->author_role_snapshot))
                        <span class="px-1.5 py-0.2 rounded bg-stone-100 text-stone-600 text-[10px]">{{ implode(', ', $th->author_role_snapshot) }}</span>
                    @endif
                    <span>•</span>
                    <span>{{ $th->created_at?->diffForHumans() }}</span>
                </div>
            </div>

            <!-- Reply count badge -->
            <div class="flex items-center gap-2 shrink-0">
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-stone-50 border border-stone-200/70 text-xs font-semibold text-stone-600">
                    <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span>{{ $th->posts_count }} Tanggapan</span>
                </div>
                <a href="{{ route('komunitas.forum.thread.show', $th->id) }}" class="p-2 text-stone-400 hover:text-emerald-800 hover:bg-emerald-50 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
        @endforeach

        <div class="pt-2">
            {{ $threads->links() }}
        </div>
    </div>
    @endif
</div>
