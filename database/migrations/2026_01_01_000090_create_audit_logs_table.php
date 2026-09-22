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
        // audit_logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->nullable()->constrained('rt')->nullOnDelete();
            $table->foreignId('rw_id')->nullable()->constrained('rw')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('aksi');        // approve_surat | tolak_surat | hapus_thread | assign_role | koreksi_kas | dll
            $table->string('target_type'); // surat_pengajuan | forum_thread | umkm_listing | kas_transaksi | user_role | dll
            $table->unsignedBigInteger('target_id');
            $table->json('sebelum')->nullable();
            $table->json('sesudah')->nullable();
            $table->text('alasan')->nullable();
            $table->timestamps();

            $table->index(['rt_id', 'created_at']);
            $table->index(['rw_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
