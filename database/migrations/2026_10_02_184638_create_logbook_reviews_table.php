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
        Schema::create('logbook_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logbook_id')->constrained('logbooks')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('decision', 20);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Status lama "Reviewed" (satu-satunya hasil reviu sebelum ada tiga keputusan) setara dengan disetujui.
        DB::table('logbooks')->where('status', 'Reviewed')->update(['status' => 'Approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('logbooks')->where('status', 'Approved')->update(['status' => 'Reviewed']);

        Schema::dropIfExists('logbook_reviews');
    }
};
