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
        Schema::create('logbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained('themes');
            $table->foreignId('program_id')->constrained('programs');
            $table->foreignId('activity_type_id')->constrained('activity_types');
            $table->foreignId('location_id')->constrained('locations');
            $table->dateTime('log_date');
            $table->unsignedInteger('community_count')->default(0);
            $table->string('health_status', 100)->default('Normal');
            $table->text('progress_note');
            $table->text('personal_info')->nullable();
            $table->string('documentation')->nullable();
            $table->string('status', 50)->default('Submitted');
            $table->timestamps();

            $table->index('log_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbooks');
    }
};
