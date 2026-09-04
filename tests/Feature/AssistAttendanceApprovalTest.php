<?php

namespace Tests\Feature;

use App\Livewire\AssistAttendanceList;
use App\Models\AssistAttendance;
use App\Models\Program;
use App\Models\Region;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssistAttendanceApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_host_can_approve_assist_attendance(): void
    {
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $helper = Student::create(['email' => 'mira@student.demo', 'name' => 'Mira', 'region_id' => $region->id]);
        $host = Student::create(['email' => 'azmi@student.demo', 'name' => 'Azmi', 'region_id' => $region->id]);
        $program = Program::create(['name' => 'Program Uji']);

        $assist = AssistAttendance::create([
            'helper_student_id' => $helper->id,
            'host_student_id' => $host->id,
            'program_id' => $program->id,
            'assist_date' => today(),
            'hours' => 2,
            'approval_status' => 'Menunggu',
        ]);

        $this->actingAs($host);
        Livewire::test(AssistAttendanceList::class)->call('approve', $assist->id);

        $this->assertSame('Disetujui', $assist->fresh()->approval_status);
    }

    public function test_non_host_cannot_approve_assist_attendance(): void
    {
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $helper = Student::create(['email' => 'mira@student.demo', 'name' => 'Mira', 'region_id' => $region->id]);
        $host = Student::create(['email' => 'azmi@student.demo', 'name' => 'Azmi', 'region_id' => $region->id]);
        $bystander = Student::create(['email' => 'rafi@student.demo', 'name' => 'Rafi', 'region_id' => $region->id]);
        $program = Program::create(['name' => 'Program Uji']);

        $assist = AssistAttendance::create([
            'helper_student_id' => $helper->id,
            'host_student_id' => $host->id,
            'program_id' => $program->id,
            'assist_date' => today(),
            'hours' => 2,
            'approval_status' => 'Menunggu',
        ]);

        $this->actingAs($bystander);

        try {
            Livewire::test(AssistAttendanceList::class)->call('approve', $assist->id);
        } catch (\Throwable) {
            // Ditolak lewat exception (403) ATAU lewat respons Livewire -
            // yang penting statusnya di database tidak berubah, dicek di bawah.
        }

        $this->assertSame('Menunggu', $assist->fresh()->approval_status);
    }
}
