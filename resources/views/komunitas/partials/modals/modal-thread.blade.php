<div x-show="showThreadModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs" 
     style="display: none;">
    <div @click.away="showThreadModal = false" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-stone-200/90 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                    </svg>
                </span>
                <div>
                    <h3 class="text-base font-bold text-stone-900">Buat Usulan Diskusi Baru</h3>
                    <p class="text-xs text-stone-500">Lingkup Wilayah: <span class="font-semibold text-emerald-800">{{ strtoupper($requestedScope) }}</span></p>
                </div>
            </div>
            <button type="button" @click="showThreadModal = false" class="text-stone-400 hover:text-stone-600 p-1 rounded-lg hover:bg-stone-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form action="{{ route('komunitas.forum.thread.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-stone-700 mb-1">Kategori Topik <span class="text-rose-500">*</span></label>
                <select name="category_id" required 
                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($forumCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-stone-700 mb-1">Judul Usulan / Topik <span class="text-rose-500">*</span></label>
                <input type="text" name="judul" required placeholder="Contoh: Pengadaan Tempat Sampah Terpilah di Jalur Utama" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
            </div>

            <div>
                <label class="block text-xs font-semibold text-stone-700 mb-1">Uraian Usulan / Pertanyaan <span class="text-rose-500">*</span></label>
                <textarea name="konten" rows="4" required placeholder="Jelaskan gagasan atau usulan Anda dengan santun dan konstruktif..." 
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600"></textarea>
            </div>

            <div class="p-3.5 rounded-xl bg-stone-50/80 border border-stone-200/80 text-[11px] text-stone-500">
                <span class="font-bold text-stone-700">Snapshot Jabatan:</span> Usulan Anda akan tercatat dengan status jabatan: <span class="font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">{{ auth()->user()->getHighestRoleBadge() }}</span>.
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-stone-100">
                <button type="button" @click="showThreadModal = false" class="px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-100 rounded-xl transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">Publikasikan Usulan</button>
            </div>
        </form>
    </div>
</div>
