<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'surat_pengajuan' => \App\Models\SuratPengajuan::class,
            'announcements'   => \App\Models\Announcement::class,
            'announcement'    => \App\Models\Announcement::class,
            'forum_thread'    => \App\Models\ForumThread::class,
            'forum_threads'   => \App\Models\ForumThread::class,
            'forum_posts'     => \App\Models\ForumPost::class,
            'users'           => \App\Models\User::class,
            'kas_transaksi'   => \App\Models\KasTransaksi::class,
            'umkm_listing'    => \App\Models\UmkmListing::class,
            'umkm_listings'   => \App\Models\UmkmListing::class,
        ]);
    }
}
