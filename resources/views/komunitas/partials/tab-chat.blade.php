<div class="bg-white rounded-2xl border border-stone-200/90 shadow-xs overflow-hidden flex flex-col h-[600px]"
     x-data="{
         messages: [],
         newMessage: '',
         loading: true,
         connected: false,
         sending: false,
         scopeType: '{{ $requestedScope }}',
         scopeId: {{ $scopeId }},
         pollInterval: null,
         async init() {
             await this.loadMessages();
             this.setupEcho();
             this.startPollingFallback();
         },
         async loadMessages() {
             try {
                 const res = await fetch(`{{ route('komunitas.chat.messages') }}?scope_type=${this.scopeType}`);
                 const data = await res.json();
                 if (data.status === 'success' && Array.isArray(data.data)) {
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
                     .listen('.ChatMessageSent', (e) => this.handleIncomingMessage(e))
                     .listen('ChatMessageSent', (e) => this.handleIncomingMessage(e));

                 // Pantau status koneksi socket Reverb
                 if (window.Echo.connector && window.Echo.connector.pusher) {
                     const pusher = window.Echo.connector.pusher;
                     if (pusher.connection.state === 'connected') {
                         this.connected = true;
                     }
                     pusher.connection.bind('connected', () => { this.connected = true; });
                     pusher.connection.bind('unavailable', () => { this.connected = false; });
                     pusher.connection.bind('failed', () => { this.connected = false; });
                     pusher.connection.bind('disconnected', () => { this.connected = false; });
                 }
             }
         },
         handleIncomingMessage(incoming) {
             if (!incoming || !incoming.id) return;
             if (!this.messages.some(m => m.id === incoming.id)) {
                 this.messages.push(incoming);
                 this.scrollToBottom();
             }
         },
         startPollingFallback() {
             // Polling fallback jika koneksi websocket terhalang firewall/offline
             this.pollInterval = setInterval(async () => {
                 if (!this.connected) {
                     await this.syncLatestMessages();
                 }
             }, 8000);
         },
         async syncLatestMessages() {
             try {
                 const res = await fetch(`{{ route('komunitas.chat.messages') }}?scope_type=${this.scopeType}`);
                 const data = await res.json();
                 if (data.status === 'success' && Array.isArray(data.data)) {
                     let hasNew = false;
                     data.data.forEach(incoming => {
                         if (!this.messages.some(m => m.id === incoming.id)) {
                             this.messages.push(incoming);
                             hasNew = true;
                         }
                     });
                     if (hasNew) this.scrollToBottom();
                 }
             } catch (e) {
                 // Polling fallback diam tanpa interupsi UI
             }
         },
         async sendMessage() {
             const text = this.newMessage.trim();
             if (!text || this.sending) return;
             this.sending = true;

             const headers = {
                 'Content-Type': 'application/json',
                 'X-CSRF-TOKEN': '{{ csrf_token() }}',
                 'Accept': 'application/json'
             };

             if (window.Echo && typeof window.Echo.socketId === 'function') {
                 const sockId = window.Echo.socketId();
                 if (sockId) headers['X-Socket-ID'] = sockId;
             }

             try {
                 const res = await fetch(`{{ route('komunitas.chat.send') }}`, {
                     method: 'POST',
                     headers: headers,
                     body: JSON.stringify({
                         scope_type: this.scopeType,
                         konten: text
                     })
                 });
                 const data = await res.json();
                 if (res.ok && data.status === 'success' && data.data) {
                     this.handleIncomingMessage(data.data);
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
    <div class="p-4 border-b border-stone-200/90 flex items-center justify-between bg-white">
        <div class="flex items-center gap-3">
            <div class="w-2.5 h-2.5 rounded-full" :class="connected ? 'bg-emerald-500 animate-pulse' : 'bg-amber-400'"></div>
            <div>
                <h3 class="text-xs font-bold text-stone-900">Obrolan Santai {{ strtoupper($requestedScope) }}</h3>
                <p class="text-[10px] text-stone-500" x-text="connected ? 'Terkoneksi ke Laravel Reverb (Real-Time)' : 'Menghubungkan ke jaringan obrolan...'"></p>
            </div>
        </div>
        <span class="text-[11px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full shadow-2xs">
            Wilayah: {{ strtoupper($requestedScope) }}
        </span>
    </div>

    <!-- Chat Messages Area -->
    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-[#faf7f4]" x-ref="chatBox">
        <template x-if="loading">
            <div class="h-full flex items-center justify-center py-10 text-xs text-stone-400">Memuat percakapan warga...</div>
        </template>

        <!-- Chat Empty State -->
        <template x-if="!loading && messages.length === 0">
            <div class="h-full flex flex-col items-center justify-center py-16 px-4 text-center">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-3 shadow-2xs border border-emerald-100/80">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-stone-800 mb-1">Belum Ada Percakapan</h4>
                <p class="text-xs text-stone-500 max-w-xs leading-relaxed">Jadilah warga pertama yang memulai silaturahmi di ruang obrolan santai ini.</p>
            </div>
        </template>

        <!-- Chat Messages List -->
        <template x-for="msg in messages" :key="msg.id">
            <div class="flex flex-col" :class="msg.author_id === {{ auth()->id() }} ? 'items-end' : 'items-start'">
                <div class="flex items-center gap-1.5 mb-1 px-1 text-[11px] text-stone-500">
                    <span class="font-bold text-stone-700" x-text="msg.author?.nama || 'Warga'"></span>
                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-stone-200/80 text-stone-700 font-semibold" x-text="msg.author?.role_badge || 'Warga'"></span>
                    <span class="text-[10px] text-stone-400" x-text="msg.created_at_human || 'baru saja'"></span>
                </div>
                <div class="max-w-[78%] px-4 py-2.5 rounded-2xl text-xs leading-relaxed shadow-xs transition-all"
                     :class="msg.author_id === {{ auth()->id() }} ? 'bg-emerald-700 text-white rounded-tr-xs' : 'bg-white text-stone-800 rounded-tl-xs border border-stone-200/90'">
                    <!-- Strict x-text escaping to prevent Stored XSS -->
                    <span x-text="msg.konten" class="whitespace-pre-line break-words font-medium"></span>
                </div>
            </div>
        </template>
    </div>

    <!-- Chat Input Form -->
    <form @submit.prevent="sendMessage" class="p-3 bg-white border-t border-stone-200/90 flex gap-2">
        <input type="text" x-model="newMessage" placeholder="Ketik pesan santai untuk warga..." 
               class="flex-1 text-xs px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
        <button type="submit" :disabled="sending || !newMessage.trim()"
                class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
            <span>Kirim</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
        </button>
    </form>
</div>
