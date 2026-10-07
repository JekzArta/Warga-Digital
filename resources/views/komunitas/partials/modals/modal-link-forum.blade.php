@if($canPublishAnnouncement)
<div x-show="showLinkForumModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs" 
     style="display: none;">
    <div @click.away="showLinkForumModal = false" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-stone-200/90">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </span>
            <div>
                <h3 class="text-base font-bold text-stone-900">Hubungkan ke Forum Warga</h3>
                <p class="text-xs text-stone-500 font-medium" x-text="selectedAnnouncement?.judul"></p>
            </div>
        </div>

        <form :action="`{{ url('/komunitas/pengumuman') }}/${selectedAnnouncement?.id}/link-forum`" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-stone-700 mb-1">Pilih Thread Forum Diskusi <span class="text-rose-500">*</span></label>
                <select name="forum_thread_id" required 
                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
                    <option value="">-- Pilih Thread Forum Terkait --</option>
                    @foreach($availableThreads as $thr)
                        <option value="{{ $thr->id }}">{{ $thr->judul }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-stone-400 mt-1.5">Warga akan melihat tombol pintasan langsung menuju ruang diskusi forum pada pengumuman ini.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-stone-100">
                <button type="button" @click="showLinkForumModal = false" class="px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-100 rounded-xl transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">Hubungkan Forum</button>
            </div>
        </form>
    </div>
</div>
@endif
