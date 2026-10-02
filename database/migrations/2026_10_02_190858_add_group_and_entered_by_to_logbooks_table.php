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
        Schema::table('logbooks', function (Blueprint $table) {
            // Logbook kegiatan kelompok yang diisi ketua (atau admin atas nama ketua).
            $table->boolean('is_group')->default(false)->after('status');
            // Terisi bila logbook diinput/dikoreksi admin atas nama pemiliknya.
            $table->foreignId('entered_by')->nullable()->after('is_group')->constrained('students')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entered_by');
            $table->dropColumn('is_group');
        });
    }
};
