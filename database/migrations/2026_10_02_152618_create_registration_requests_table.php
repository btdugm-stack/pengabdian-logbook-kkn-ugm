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
        Schema::create('registration_requests', function (Blueprint $table) {
            $table->id();
            $table->string('email', 150)->unique();
            $table->string('google_id', 120)->nullable();
            $table->string('name', 150);
            $table->string('requested_role', 30);
            $table->string('region_path', 600)->nullable();
            $table->string('faculty', 150)->nullable();
            $table->string('study_program', 150)->nullable();
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->default('Pending')->index();
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('students')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_requests');
    }
};
