<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Periode dan tema KKN per akun (diisi saat pendaftaran, bisa dikoreksi di
     * Data KKN) dan periode yang berlaku saat tiap logbook dibuat.
     */
    public function up(): void
    {
        foreach (['students', 'registration_requests'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('kkn_period', 100)->nullable()->after('study_program');
                $table->string('kkn_theme', 150)->nullable()->after('kkn_period');
            });
        }

        Schema::table('logbooks', function (Blueprint $table) {
            $table->string('kkn_period', 100)->nullable()->after('log_date');
        });
    }

    public function down(): void
    {
        foreach (['students', 'registration_requests'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['kkn_period', 'kkn_theme']);
            });
        }

        Schema::table('logbooks', function (Blueprint $table) {
            $table->dropColumn('kkn_period');
        });
    }
};
