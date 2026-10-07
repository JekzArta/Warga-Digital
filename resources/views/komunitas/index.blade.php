@extends('layouts.app', ['title' => ($pageTitle ?? 'Ruang Komunitas') . ' — Warga Digital', 'pageTitle' => $pageTitle ?? 'Ruang Komunitas'])

@section('content')
<div class="space-y-6" x-data="{ 
    activeTab: '{{ $activeTab }}',
    showPengumumanModal: false,
    showThreadModal: false,
    showPembaruanModal: false,
    showDeactivateModal: false,
    showLinkForumModal: false,
    selectedAnnouncement: null
}">
    <!-- Scope & Wilayah Switcher -->
    @include('komunitas.partials.scope-switcher')

    <!-- Error Alert Banner -->
    @if($errors->any())
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200/90 text-rose-800 text-sm shadow-xs">
        <div class="flex items-center gap-2 font-semibold mb-1">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>Terdapat beberapa kesalahan pengisian formulir:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-700 pl-6">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Layer Navigation Tabs -->
    <div class="border-b border-stone-200/90 flex items-center gap-2">
        <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'pengumuman']) }}"
           class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'pengumuman' ? 'border-emerald-700 text-emerald-800' : 'border-transparent text-stone-500 hover:text-stone-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            <span>Pengumuman Resmi</span>
            <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-100 text-stone-600">{{ $announcements->count() }}</span>
        </a>

        <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'chat']) }}"
           class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'chat' ? 'border-emerald-700 text-emerald-800' : 'border-transparent text-stone-500 hover:text-stone-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <span>Chat Bebas</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 animate-pulse">Live</span>
        </a>

        <a href="{{ route('komunitas.index', ['scope' => $requestedScope, 'tab' => 'forum']) }}"
           class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'forum' ? 'border-emerald-700 text-emerald-800' : 'border-transparent text-stone-500 hover:text-stone-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
            </svg>
            <span>Forum Warga</span>
            <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-100 text-stone-600">{{ $threads->total() }}</span>
        </a>
    </div>

    <!-- Active Tab Presentation Layer -->
    @if($activeTab === 'pengumuman')
        @include('komunitas.partials.tab-pengumuman')
    @elseif($activeTab === 'chat')
        @include('komunitas.partials.tab-chat')
    @elseif($activeTab === 'forum')
        @include('komunitas.partials.tab-forum')
    @endif

    <!-- Modular Interactive Modals -->
    @include('komunitas.partials.modals.modal-pengumuman')
    @include('komunitas.partials.modals.modal-pembaruan')
    @include('komunitas.partials.modals.modal-deactivate')
    @include('komunitas.partials.modals.modal-link-forum')
    @include('komunitas.partials.modals.modal-thread')
</div>
@endsection
