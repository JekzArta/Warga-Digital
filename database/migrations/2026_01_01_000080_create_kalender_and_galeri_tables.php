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
        // kalender_events
        Schema::create('kalender_events', function (Blueprint $table) {
            $table->id();
            $table->enum('scope_type', ['rt', 'rw']);
            $table->unsignedBigInteger('scope_id');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->date('tanggal');
            $table->enum('sumber', ['manual', 'announcement'])->default('manual');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['scope_type', 'scope_id', 'tanggal']);
        });

        // galeri_album, galeri_foto
        Schema::create('galeri_album', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->constrained('rt')->onDelete('cascade');
            $table->string('judul');
            $table->date('tanggal_kegiatan');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['rt_id', 'tanggal_kegiatan']);
        });

        Schema::create('galeri_foto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('galeri_album')->onDelete('cascade');
            $table->string('foto_url');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('galeri_foto');
        Schema::dropIfExists('galeri_album');
        Schema::dropIfExists('kalender_events');
    }
};
