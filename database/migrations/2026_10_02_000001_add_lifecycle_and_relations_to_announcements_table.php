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
        Schema::table('announcements', function (Blueprint $table) {
            // Expiry date (Masa berlaku sampai hari tersebut)
            $table->date('expired_at')->nullable()->after('is_pinned');

            // Deactivation fields (Nonaktifkan pengumuman resmi dengan alasan wajib)
            $table->boolean('is_deactivated')->default(false)->after('expired_at');
            $table->timestamp('deactivated_at')->nullable()->after('is_deactivated');
            $table->foreignId('deactivated_by')->nullable()->after('deactivated_at')->constrained('users')->nullOnDelete();
            $table->text('deactivation_reason')->nullable()->after('deactivated_by');

            // Self-referencing relationship untuk Pembaruan (BUKAN Edit in-place)
            // UNIQUE constraint menjamin ANTI-BRANCHING di level database (1 announcement maksimal 1 direct successor)
            $table->foreignId('replaces_announcement_id')->nullable()->unique()->after('deactivation_reason')->constrained('announcements')->nullOnDelete();
            $table->boolean('is_replaced')->default(false)->after('replaces_announcement_id');

            // Optional 1-to-1 relationship ke Forum Warga (Layer 3)
            $table->foreignId('forum_thread_id')->nullable()->after('is_replaced')->constrained('forum_threads')->nullOnDelete();

            // Index komposit untuk performa query active feed
            $table->index(['scope_type', 'scope_id', 'is_deactivated', 'is_replaced'], 'announcements_active_feed_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex('announcements_active_feed_idx');
            $table->dropForeign(['forum_thread_id']);
            $table->dropForeign(['replaces_announcement_id']);
            $table->dropForeign(['deactivated_by']);

            $table->dropColumn([
                'expired_at',
                'is_deactivated',
                'deactivated_at',
                'deactivated_by',
                'deactivation_reason',
                'replaces_announcement_id',
                'is_replaced',
                'forum_thread_id',
            ]);
        });
    }
};
