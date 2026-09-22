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
        // kas_transaksi
        Schema::create('kas_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->constrained('rt')->onDelete('cascade');
            $table->foreignId('input_by')->constrained('users')->onDelete('cascade');
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->string('kategori');
            $table->unsignedBigInteger('nominal');
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->boolean('is_koreksi')->default(false);
            $table->foreignId('koreksi_dari_id')->nullable()->constrained('kas_transaksi')->nullOnDelete();
            $table->text('catatan_koreksi')->nullable();
            $table->timestamps();

            $table->index(['rt_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_transaksi');
    }
};
