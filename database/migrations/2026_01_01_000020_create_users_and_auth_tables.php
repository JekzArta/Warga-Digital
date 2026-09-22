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
        // users — mencakup Warga & seluruh Pengurus (RT-scoped via rt_id, RW-scoped via rw_id)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('kode_warga', 12)->unique()->nullable(); // WRG-000123
            $table->foreignId('rt_id')->nullable()->constrained('rt')->nullOnDelete(); // diisi utk warga/sekretaris/bendahara/wakil_rt/ketua_rt
            $table->foreignId('rw_id')->nullable()->constrained('rw')->nullOnDelete(); // diisi utk ketua_rw
            $table->string('nik', 16)->unique()->nullable(); // identity anchor backend-only, unik GLOBAL
            $table->string('email')->unique()->nullable();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_hp')->nullable(); // opsional, wajib sebelum buka UMKM
            $table->string('password')->nullable();
            $table->enum('status', ['belum_daftar', 'aktif'])->default('belum_daftar');
            $table->boolean('is_super_admin')->default(false);
            $table->timestamp('last_login')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
