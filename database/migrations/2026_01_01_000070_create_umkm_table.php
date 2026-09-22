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
        // umkm_listing
        Schema::create('umkm_listing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // penjual
            $table->foreignId('rt_id')->constrained('rt')->onDelete('cascade');
            $table->enum('kategori', ['jasa', 'barang']);
            $table->string('nama');
            $table->text('deskripsi');
            $table->unsignedBigInteger('harga')->nullable(); // null utk jasa (butuh nego)
            $table->string('foto_url')->nullable();
            $table->text('template_pesan_wa')->nullable(); // custom per listing, khusus kategori barang
            $table->enum('status', ['MENUNGGU', 'DISETUJUI', 'DITOLAK'])->default('MENUNGGU');
            $table->text('alasan_tolak')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rt_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('umkm_listing');
    }
};
