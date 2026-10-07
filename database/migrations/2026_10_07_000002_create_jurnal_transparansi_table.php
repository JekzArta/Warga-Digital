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
        Schema::create('jurnal_transparansi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_log_id')->unique();
            $table->enum('scope_type', ['rt', 'rw']);
            $table->unsignedBigInteger('scope_id');
            $table->unsignedBigInteger('rw_id');
            $table->string('event_type', 50);
            $table->string('actor_label', 100);
            $table->string('target_title', 255);
            $table->text('public_reason')->nullable();
            $table->string('target_type', 50);
            $table->unsignedBigInteger('target_id');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['scope_type', 'scope_id', 'occurred_at'], 'jt_scope_occurred_idx');
            $table->index(['rw_id', 'occurred_at'], 'jt_rw_occurred_idx');
            $table->index(['event_type', 'occurred_at'], 'jt_event_occurred_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_transparansi');
    }
};
