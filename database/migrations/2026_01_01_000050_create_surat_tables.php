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
        // surat_pengajuan
        Schema::create('surat_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->constrained('rt')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('jenis_surat', ['SKD', 'SKTM', 'SKU', 'SPKK', 'SKL', 'SKKm']);
            $table->string('nomor_surat')->nullable();
            $table->json('form_data'); // field dinamis per jenis surat
            $table->enum('status', ['MENUNGGU', 'DIREVIEW', 'DISETUJUI', 'DITOLAK', 'PERLU_KELENGKAPAN'])->default('MENUNGGU');
            $table->text('alasan_tolak')->nullable();
            $table->string('pdf_url')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rt_id', 'status']);
        });

        // surat_kelengkapan
        Schema::create('surat_kelengkapan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('surat_pengajuan')->onDelete('cascade');
            $table->text('pesan');
            $table->string('file_url')->nullable();
            $table->enum('dari_role', ['rt', 'warga']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_kelengkapan');
        Schema::dropIfExists('surat_pengajuan');
    }
};
