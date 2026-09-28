<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('klien', function (Blueprint $table) {
            $table->string('kecamatan')->nullable()->after('nama');
            $table->string('kota')->nullable()->after('kecamatan');
        });

        // Backfill data existing client (Kelurahan Sekeloa) secara aman
        DB::table('klien')->whereNull('kecamatan')->update([
            'kecamatan' => 'Coblong',
            'kota' => 'Bandung',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('klien', function (Blueprint $table) {
            $table->dropColumn(['kecamatan', 'kota']);
        });
    }
};
