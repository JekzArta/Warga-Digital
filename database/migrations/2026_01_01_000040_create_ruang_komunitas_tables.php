<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // announcements (layer 1 — Ruang Komunitas)
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->enum('scope_type', ['rt', 'rw']);
            $table->unsignedBigInteger('scope_id'); // rt.id atau rw.id sesuai scope_type
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('judul');
            $table->text('konten');
            $table->enum('tipe', ['INFO', 'PENTING', 'MENDESAK'])->default('INFO');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('announcement_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->text('konten');
            $table->timestamps();
        });

        // chat_messages (layer 2 — Chat Bebas, broadcast via Reverb)
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->enum('scope_type', ['rt', 'rw']);
            $table->unsignedBigInteger('scope_id');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->text('konten');
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
        });

        // forum_categories, forum_threads, forum_posts (layer 3 — Forum)
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();
            $table->enum('scope_type', ['rt', 'rw']);
            $table->unsignedBigInteger('scope_id');
            $table->string('nama');
            $table->string('deskripsi')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('forum_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('forum_categories')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('judul');
            $table->text('konten');
            $table->enum('status', ['aktif', 'closed', 'dihapus'])->default('aktif');
            $table->boolean('is_pinned')->default(false);
            $table->json('author_role_snapshot'); // ["Wakil RT", "Bendahara"]
            $table->timestamps();
        });

        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_post_id')->nullable()->constrained('forum_posts')->nullOnDelete();
            $table->text('konten');
            $table->json('author_role_snapshot');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
        Schema::dropIfExists('forum_threads');
        Schema::dropIfExists('forum_categories');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('announcement_comments');
        Schema::dropIfExists('announcements');
    }
};
