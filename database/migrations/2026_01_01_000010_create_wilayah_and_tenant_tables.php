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
        // klien — Kecamatan/Kelurahan sebagai pemegang lisensi
        Schema::create('klien', function (Blueprint $table) {
            $table->id();
            $table->string('kode_wilayah', 20)->unique(); // Contoh Kemendagri: 32.73.02.1005
            $table->string('nama');                        // "Kelurahan Sekeloa"
            $table->enum('tenor_lisensi', ['bulanan', 'tahunan', '3_tahun']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir');
            $table->enum('status', ['aktif', 'nonaktif', 'kedaluwarsa'])->default('aktif');
            $table->timestamps();
        });

        // rw
        Schema::create('rw', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klien_id')->constrained('klien')->onDelete('cascade');
            $table->string('kode_rw', 40);        // 32.73.02.1005-RW03
            $table->unsignedTinyInteger('nomor_rw');
            $table->string('nama')->nullable();
            $table->timestamps();
            $table->unique(['klien_id', 'nomor_rw']);
        });

        // rt
        Schema::create('rt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_id')->constrained('rw')->onDelete('cascade');
            $table->string('kode_rt', 50);        // 32.73.02.1005-RW03-RT05
            $table->unsignedTinyInteger('nomor_rt');
            $table->string('nama')->nullable();
            $table->string('format_nomor_surat')->nullable(); // custom template, null = pakai default
            $table->timestamps();
            $table->unique(['rw_id', 'nomor_rt']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rt');
        Schema::dropIfExists('rw');
        Schema::dropIfExists('klien');
    }
};
