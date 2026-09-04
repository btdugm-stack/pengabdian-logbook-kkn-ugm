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
        // Tidak ada tabel `users` generik - mahasiswa (students) adalah satu-satunya
        // model Authenticatable di aplikasi ini, dibuat lewat migration terpisah.
        // Kolom di bawah tetap bernama `user_id` (bukan `student_id`) karena
        // Illuminate\Session\DatabaseSessionHandler menulis ke nama itu secara
        // hardcoded, terlepas dari model Authenticatable yang dipakai.
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
    }
};
