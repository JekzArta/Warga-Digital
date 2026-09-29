@extends('layouts.app', ['title' => 'Ruang Komunitas — Warga Digital'])

@section('content')
<div class="space-y-6" x-data="{ 
    activeTab: '{{ $activeTab }}',
    showPengumumanModal: false,
    showThreadModal: false
}">
    <!-- Top Header & Scope Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 font-bold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Ruang Komunitas</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Platform terstruktur untuk pengumuman resmi, obrolan santai, dan musyawarah warga.</p>
        </div>

        <!-- Scope Switcher (RT vs RW) -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-100/80 rounded-xl border border-slate-200/60 self-start sm:self-auto">
            @if(auth()->user()->rt_id)
            <a href="{{ route('komunitas.index', ['scope' => 'rt', 'tab' => $activeTab]) }}" 
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $requestedScope === 'rt' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                Lingkup RT ({{ auth()->user()->rt?->kode_rt ?? 'RT 05' }})
            </a>
            @endif
            <a href="{{ route('komunitas.index', ['scope' => 'rw', 'tab' => $activeTab]) }}" 
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $requestedScope === 'rw' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                Kawasan RW ({{ auth()->user()->rw?->kode_rw ?? auth()->user()->rt?->rw?->kode_rw ?? 'RW 03' }})
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-sm flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200/80 text-rose-800 text-sm shadow-xs">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Layer Navigation Tabs -->
    <div class="border-b border-slate-200/80 flex items-center gap-2">
        <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'pengumuman']) }}"
           class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'pengumuman' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            <span>Pengumuman Resmi</span>
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">{{ $announcements->count() }}</span>
        </a>

        <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'chat']) }}"
           class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'chat' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <span>Chat Bebas</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 animate-pulse">Live</span>
        </a>

        <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'forum']) }}"
           class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'forum' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
            </svg>
            <span>Forum Warga</span>
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">{{ $threads->total() }}</span>
        </a>
    </div>

    <!-- ==================== TAB 1: PENGUMUMAN RESMI ==================== -->
    @if($activeTab === 'pengumuman')
    <div class="space-y-4">
        <!-- Action Header -->
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Daftar Pengumuman {{ strtoupper($requestedScope) }}</h2>
            @if($canPublishAnnouncement)
            <button @click="showPengumumanModal = true" type="button" class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Terbitkan Pengumuman</span>
            </button>
            @endif
        </div>

        @if($announcements->isEmpty())
        <div class="p-8 text-center bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <p class="text-sm text-slate-500">Belum ada pengumuman resmi yang diterbitkan untuk lingkup ini.</p>
        </div>
        @else
        <div class="space-y-4">
            @foreach($announcements as $anc)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden transition-all hover:border-slate-300">
                <div class="p-5">
                    <!-- Top metadata -->
                    <div class="flex items-center justify-between gap-3 mb-2.5">
                        <div class="flex items-center gap-2">
                            <!-- Urgency badge -->
                            @if($anc->tipe === 'MENDESAK')
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200/80 uppercase">Mendesak</span>
                            @elseif($anc->tipe === 'PENTING')
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-50 text-amber-700 border border-amber-200/80 uppercase">Penting</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 uppercase">Info</span>
                            @endif

                            @if($anc->is_pinned)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-500/10 text-amber-700 border border-amber-300/40">
                                    <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                                    </svg>
                                    Disematkan
                                </span>
                            @endif
                        </div>

                        <span class="text-xs text-slate-400 font-medium">{{ $anc->created_at?->diffForHumans() }}</span>
                    </div>

                    <!-- Title & Content -->
                    <h3 class="text-base font-bold text-slate-800 leading-snug mb-2">{{ $anc->judul }}</h3>
                    <p class="text-sm text-slate-600 whitespace-pre-line leading-relaxed">{{ $anc->konten }}</p>

                    <!-- Author footer -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-700">{{ $anc->author?->nama }}</span>
                            <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-medium text-[10px]">{{ $anc->author?->getHighestRoleBadge() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Collapsible Comments Section (Alpine Accordion) -->
                <div x-data="{ openComments: false }" class="bg-slate-50/70 border-t border-slate-100 p-4">
                    <button @click="openComments = !openComments" type="button" class="flex items-center gap-2 text-xs font-semibold text-emerald-700 hover:text-emerald-800 transition-all">
                        <svg class="w-3.5 h-3.5 transition-transform" :class="openComments ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span>Tanggapan Warga ({{ $anc->comments->count() }})</span>
                    </button>

                    <div x-show="openComments" x-collapse class="mt-3 space-y-3 pt-2">
                        <!-- Comment list -->
                        @foreach($anc->comments as $cmt)
                        <div class="p-3 bg-white rounded-xl border border-slate-200/60 text-xs shadow-2xs">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-slate-800">{{ $cmt->author?->nama }}</span>
                                    <span class="text-[9px] px-1 rounded bg-slate-100 text-slate-600">{{ $cmt->author?->getHighestRoleBadge() }}</span>
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $cmt->created_at?->diffForHumans() }}</span>
                            </div>
                            <p class="text-slate-600">{{ $cmt->konten }}</p>
                        </div>
                        @endforeach

                        <!-- Add Comment Form -->
                        <form action="{{ route('komunitas.pengumuman.komentar', $anc->id) }}" method="POST" class="pt-2">
                            @csrf
                            <div class="flex gap-2">
                                <input type="text" name="konten" required placeholder="Tuliskan tanggapan Anda..." class="flex-1 text-xs px-3 py-2 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all">Kirim</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Modal Buat Pengumuman -->
        @if($canPublishAnnouncement)
        <div x-show="showPengumumanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
            <div @click.away="showPengumumanModal = false" class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-slate-200">
                <h3 class="text-base font-bold text-slate-800 mb-4">Terbitkan Pengumuman Resmi ({{ strtoupper($requestedScope) }})</h3>
                <form action="{{ route('komunitas.pengumuman.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="scope_type" value="{{ $requestedScope }}">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Pengumuman</label>
                        <input type="text" name="judul" required placeholder="Contoh: Kerja Bakti Hari Minggu" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Urgensi</label>
                        <select name="tipe" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                            <option value="INFO">INFO (Netral / Informasi Biasa)</option>
                            <option value="PENTING">PENTING (Harap Menjadi Perhatian)</option>
                            <option value="MENDESAK">MENDESAK (Darurat / Tindakan Cepat)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Pengumuman</label>
                        <textarea name="konten" rows="4" required placeholder="Tuliskan isi rincian pengumuman..." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_pinned" id="is_pinned" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="is_pinned" class="text-xs text-slate-600 font-medium">Sematkan di posisi teratas (Pin)</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showPengumumanModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs">Terbitkan</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- ==================== TAB 2: CHAT BEBAS REAL-TIME ==================== -->
    @if($activeTab === 'chat')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-[600px]"
         x-data="{
             messages: [],
             newMessage: '',
             loading: true,
             connected: false,
             sending: false,
             scopeType: '{{ $requestedScope }}',
             scopeId: {{ $scopeId }},
             async init() {
                 await this.loadMessages();
                 this.setupEcho();
             },
             async loadMessages() {
                 try {
                     const res = await fetch(`{{ route('komunitas.chat.messages') }}?scope_type=${this.scopeType}`);
                     const data = await res.json();
                     if (data.status === 'success') {
                         this.messages = data.data;
                     }
                 } catch (e) {
                     console.error('Error fetching chat messages', e);
                 } finally {
                     this.loading = false;
                     this.scrollToBottom();
                 }
             },
             setupEcho() {
                 if (window.Echo) {
                     const channelName = `chat.${this.scopeType}.${this.scopeId}`;
                     window.Echo.private(channelName)
                         .listen('ChatMessageSent', (e) => {
                             if (!this.messages.find(m => m.id === e.id)) {
                                 this.messages.push(e);
                                 this.scrollToBottom();
                             }
                         });
                     this.connected = true;
                 }
             },
             async sendMessage() {
                 if (!this.newMessage.trim() || this.sending) return;
                 this.sending = true;
                 try {
                     const res = await fetch(`{{ route('komunitas.chat.send') }}`, {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                             'Accept': 'application/json'
                         },
                         body: JSON.stringify({
                             scope_type: this.scopeType,
                             konten: this.newMessage
                         })
                     });
                     const data = await res.json();
                     if (data.status === 'success') {
                         if (!this.messages.find(m => m.id === data.data.id)) {
                             this.messages.push(data.data);
                             this.scrollToBottom();
                         }
                         this.newMessage = '';
                     }
                 } catch (e) {
                     console.error('Failed to send message', e);
                 } finally {
                     this.sending = false;
                 }
             },
             scrollToBottom() {
                 this.$nextTick(() => {
                     const el = this.$refs.chatBox;
                     if (el) el.scrollTop = el.scrollHeight;
                 });
             }
         }">
        <!-- Chat Header -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-2.5 h-2.5 rounded-full" :class="connected ? 'bg-emerald-500 animate-pulse' : 'bg-amber-400'"></div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800">Obrolan Santai {{ strtoupper($requestedScope) }}</h3>
                    <p class="text-[10px] text-slate-400" x-text="connected ? 'Terkoneksi ke Laravel Reverb (Real-Time)' : 'Menghubungkan ke jaringan obrolan...'"></p>
                </div>
            </div>
            <span class="text-[11px] font-semibold text-slate-500 bg-white px-2.5 py-1 rounded-lg border border-slate-200">
                Scope: {{ strtoupper($requestedScope) }}
            </span>
        </div>

        <!-- Chat Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="chatBox">
            <template x-if="loading">
                <div class="text-center py-10 text-xs text-slate-400">Memuat percakapan...</div>
            </template>

            <template x-for="msg in messages" :key="msg.id">
                <div class="flex flex-col" :class="msg.author_id === {{ auth()->id() }} ? 'items-end' : 'items-start'">
                    <div class="flex items-center gap-1.5 mb-1 px-1 text-[11px] text-slate-400">
                        <span class="font-bold text-slate-700" x-text="msg.author.nama"></span>
                        <span class="text-[9px] px-1 py-0.2 rounded bg-slate-100 text-slate-600" x-text="msg.author.role_badge"></span>
                        <span class="text-[10px]" x-text="msg.created_at_human"></span>
                    </div>
                    <div class="max-w-[75%] px-4 py-2.5 rounded-2xl text-xs leading-relaxed"
                         :class="msg.author_id === {{ auth()->id() }} ? 'bg-emerald-600 text-white rounded-tr-xs shadow-xs' : 'bg-slate-100 text-slate-800 rounded-tl-xs'">
                        <span x-text="msg.konten" class="whitespace-pre-line"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Chat Input Form -->
        <form @submit.prevent="sendMessage" class="p-3 border-t border-slate-100 bg-white flex gap-2">
            <input type="text" x-model="newMessage" placeholder="Ketik pesan santai..." 
                   class="flex-1 text-xs px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <button type="submit" :disabled="sending || !newMessage.trim()"
                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                <span>Kirim</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>
    </div>
    @endif

    <!-- ==================== TAB 3: FORUM WARGA ==================== -->
    @if($activeTab === 'forum')
    <div class="space-y-4">
        <!-- Action Header & Category Filter -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <!-- Category Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'forum']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all {{ is_null($selectedCategoryId) ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                    Semua Kategori
                </a>
                @foreach($forumCategories as $cat)
                <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'forum', 'category_id' => $cat->id]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all {{ $selectedCategoryId === $cat->id ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                    {{ $cat->nama }}
                </a>
                @endforeach
            </div>

            <button @click="showThreadModal = true" type="button" class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buat Usulan Baru</span>
            </button>
        </div>

        <!-- Thread List -->
        @if($threads->isEmpty())
        <div class="p-8 text-center bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <p class="text-sm text-slate-500">Belum ada diskusi topik pada kategori atau lingkup wilayah ini.</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($threads as $th)
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1.5 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                            {{ $th->category?->nama }}
                        </span>

                        @if($th->is_pinned)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-700 border border-amber-300/40">
                            Sematkan
                        </span>
                        @endif

                        @if($th->status === 'closed')
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                            Ditutup
                        </span>
                        @else
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Aktif
                        </span>
                        @endif
                    </div>

                    <a href="{{ route('komunitas.forum.thread.show', $th->id) }}" class="text-base font-bold text-slate-800 hover:text-emerald-700 transition-all block leading-snug">
                        {{ $th->judul }}
                    </a>

                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <span class="font-medium text-slate-600">{{ $th->author?->nama }}</span>
                        @if(!empty($th->author_role_snapshot))
                            <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 text-[10px]">{{ implode(', ', $th->author_role_snapshot) }}</span>
                        @endif
                        <span>•</span>
                        <span>{{ $th->created_at?->diffForHumans() }}</span>
                    </div>
                </div>

                <!-- Reply count badge -->
                <div class="flex items-center gap-2 shrink-0">
                    <div class="flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200/60 text-xs font-semibold text-slate-600">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span>{{ $th->posts_count }} Tanggapan</span>
                    </div>
                    <a href="{{ route('komunitas.forum.thread.show', $th->id) }}" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all">
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

        <!-- Modal Buat Usulan Thread Baru -->
        <div x-show="showThreadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
            <div @click.away="showThreadModal = false" class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-slate-200">
                <h3 class="text-base font-bold text-slate-800 mb-4">Buat Usulan Diskusi Baru ({{ strtoupper($requestedScope) }})</h3>
                <form action="{{ route('komunitas.forum.thread.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Topik</label>
                        <select name="category_id" required class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($forumCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Usulan</label>
                        <input type="text" name="judul" required placeholder="Contoh: Pengadaan Tempat Sampah Terpilah" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Uraian Usulan / Pertanyaan</label>
                        <textarea name="konten" rows="4" required placeholder="Jelaskan gagasan atau usulan Anda..." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 text-[11px] text-slate-500">
                        <span class="font-bold text-slate-700">Snapshot Jabatan:</span> Postingan Anda akan dikunci dengan status identitas: <span class="font-bold text-emerald-700">{{ auth()->user()->getHighestRoleBadge() }}</span>.
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showThreadModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs">Publikasikan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
