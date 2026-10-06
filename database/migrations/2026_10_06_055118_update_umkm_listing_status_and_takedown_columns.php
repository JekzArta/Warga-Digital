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
        Schema::table('umkm_listing', function (Blueprint $table) {
            $table->string('status', 30)->default('MENUNGGU')->change();

            if (!Schema::hasColumn('umkm_listing', 'takedown_by')) {
                $table->foreignId('takedown_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('umkm_listing', 'alasan_takedown')) {
                $table->text('alasan_takedown')->nullable()->after('takedown_by');
            }
            if (!Schema::hasColumn('umkm_listing', 'takedown_at')) {
                $table->timestamp('takedown_at')->nullable()->after('alasan_takedown');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('umkm_listing', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('umkm_listing', 'takedown_by')) {
                if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign(['takedown_by']);
                }
                $dropColumns[] = 'takedown_by';
            }
            if (Schema::hasColumn('umkm_listing', 'alasan_takedown')) {
                $dropColumns[] = 'alasan_takedown';
            }
            if (Schema::hasColumn('umkm_listing', 'takedown_at')) {
                $dropColumns[] = 'takedown_at';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
