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
        Schema::table('kalender_events', function (Blueprint $table) {
            // Waktu pelaksanaan agenda dalam format lokal HH:MM (misal: "08:00")
            $table->string('waktu_mulai', 5)->nullable()->after('tanggal');
            $table->string('waktu_selesai', 5)->nullable()->after('waktu_mulai');

            // Lokasi spesifik kegiatan (misal: "Lapangan Voli RT 05")
            $table->string('lokasi', 255)->nullable()->after('waktu_selesai');

            // Kategori terstandarisasi untuk klasifikasi agenda
            $table->enum('kategori', ['KEGIATAN', 'RAPAT', 'POSYANDU', 'LAINNYA'])
                ->default('KEGIATAN')
                ->after('lokasi');

            // Relasi opsional ke Pengumuman Resmi (1-to-1 canonical link)
            // UNIQUE menjamin 1 pengumuman hanya memiliki 1 representasi KalenderEvent
            $table->foreignId('announcement_id')
                ->nullable()
                ->unique()
                ->after('sumber')
                ->constrained('announcements')
                ->cascadeOnDelete();

            // Status pembatalan eksplisit tanpa menghapus histori agenda
            $table->boolean('is_cancelled')->default(false)->after('announcement_id');
            $table->string('pembatalan_alasan', 255)->nullable()->after('is_cancelled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kalender_events', function (Blueprint $table) {
            $table->dropForeign(['announcement_id']);
            $table->dropUnique(['announcement_id']);
            $table->dropColumn([
                'waktu_mulai',
                'waktu_selesai',
                'lokasi',
                'kategori',
                'announcement_id',
                'is_cancelled',
                'pembatalan_alasan',
            ]);
        });
    }
};
