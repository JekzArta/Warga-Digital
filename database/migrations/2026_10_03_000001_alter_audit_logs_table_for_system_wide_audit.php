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
        Schema::table('audit_logs', function (Blueprint $table) {
            // Drop existing foreign key constraint on user_id if not SQLite
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['user_id']);
                $table->unsignedBigInteger('user_id')->nullable()->change();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            }

            // Tambahkan snapshot actor & security context jika belum ada
            if (!Schema::hasColumn('audit_logs', 'actor_nama')) {
                $table->string('actor_nama', 100)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('audit_logs', 'actor_role')) {
                $table->string('actor_role', 50)->nullable()->after('actor_nama');
            }
            if (!Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('actor_role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['actor_nama', 'actor_role', 'ip_address']);

            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['user_id']);
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            }
        });
    }
};
