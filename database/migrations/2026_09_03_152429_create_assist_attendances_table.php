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
        Schema::create('assist_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('helper_student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('host_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('program_id')->constrained('programs');
            $table->date('assist_date');
            $table->decimal('hours', 4, 1)->default(0);
            $table->string('role_note')->nullable();
            $table->enum('approval_status', ['Menunggu', 'Disetujui', 'Ditolak'])->default('Menunggu');
            $table->timestamps();

            $table->index('assist_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assist_attendances');
    }
};
