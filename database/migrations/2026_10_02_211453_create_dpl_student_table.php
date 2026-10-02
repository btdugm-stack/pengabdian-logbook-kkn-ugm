<?php

use App\Models\Region;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penugasan DPL -> mahasiswa bimbingan. Menggantikan cakupan DPL yang
     * sebelumnya diturunkan dari wilayah di akunnya.
     */
    public function up(): void
    {
        Schema::create('dpl_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dpl_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['dpl_id', 'student_id']);
            $table->index('student_id');
        });

        // Supaya tidak ada DPL yang tiba-tiba kehilangan bimbingannya: peserta di
        // wilayah lama tiap DPL langsung ditugaskan kepadanya.
        $roleIds = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');
        $accountsWithRole = fn (array $roles) => DB::table('model_has_roles')
            ->whereIn('role_id', collect($roles)->map(fn ($role) => $roleIds[$role] ?? null)->filter()->all())
            ->select('model_id');

        $dpls = DB::table('students')->whereIn('id', $accountsWithRole(['dpl']))->whereNotNull('region_id')->get(['id', 'region_id']);

        foreach ($dpls as $dpl) {
            $regionIds = Region::find($dpl->region_id)?->subtreeIds() ?? [];

            $rows = DB::table('students')
                ->whereIn('id', $accountsWithRole(['mahasiswa', 'kormasit', 'korcam']))
                ->whereIn('region_id', $regionIds)
                ->pluck('id')
                ->map(fn ($studentId) => ['dpl_id' => $dpl->id, 'student_id' => $studentId, 'created_at' => now(), 'updated_at' => now()])
                ->all();

            DB::table('dpl_student')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dpl_student');
    }
};
