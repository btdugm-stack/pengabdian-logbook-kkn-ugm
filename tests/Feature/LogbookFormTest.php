<?php

namespace Tests\Feature;

use App\Livewire\LogbookForm;
use App\Models\DailyAttendance;
use App\Models\Region;
use App\Models\Student;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogbookFormTest extends TestCase
{
    use RefreshDatabase;

    private function checkedInStudent(string $condition = 'Sakit Ringan'): Student
    {
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $student = Student::create(['email' => 'azmi@student.demo', 'name' => 'Azmi', 'region_id' => $region->id]);
        DailyAttendance::create([
            'student_id' => $student->id,
            'attendance_date' => today(),
            'check_in_time' => now(),
            'condition' => $condition,
            'condition_note' => $condition !== 'Sehat' ? 'Catatan uji.' : null,
            'region_id' => $region->id,
        ]);

        return $student;
    }

    public function test_submitting_a_new_theme_name_creates_and_reuses_it(): void
    {
        $student = $this->checkedInStudent();
        $this->actingAs($student);

        Livewire::test(LogbookForm::class)
            ->set('log_date', now()->format('Y-m-d\TH:i'))
            ->set('theme_new', 'Tema Baru Dari Form')
            ->set('program_name', '')
            ->set('program_new', 'Program Baru')
            ->set('activity_type_new', 'Jenis Baru')
            ->set('location_new', 'Lokasi Baru')
            ->set('progress_note', 'Catatan kegiatan uji coba.')
            ->call('save', 'submit')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('logbooks', 1);
        $this->assertDatabaseHas('themes', ['name' => 'Tema Baru Dari Form']);

        $theme = Theme::where('name', 'Tema Baru Dari Form')->first();
        $logbook = $student->logbooks()->first();
        $this->assertSame($theme->id, $logbook->theme_id);

        // Kondisi kesehatan dibersamai dari presensi hari itu, bukan diinput ulang.
        $this->assertSame('Sakit Ringan', $logbook->health_status);
    }

    public function test_missing_required_master_fields_blocks_submission(): void
    {
        $student = $this->checkedInStudent();
        $this->actingAs($student);

        Livewire::test(LogbookForm::class)
            ->set('progress_note', 'Catatan tanpa tema/program/lokasi.')
            ->call('save', 'submit')
            ->assertHasErrors('master');

        $this->assertDatabaseCount('logbooks', 0);
    }

    public function test_submission_is_blocked_without_todays_attendance(): void
    {
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $student = Student::create(['email' => 'azmi@student.demo', 'name' => 'Azmi', 'region_id' => $region->id]);
        $this->actingAs($student);

        Livewire::test(LogbookForm::class)
            ->set('theme_new', 'Tema')
            ->set('program_new', 'Program')
            ->set('activity_type_new', 'Jenis')
            ->set('location_new', 'Lokasi')
            ->set('progress_note', 'Catatan.')
            ->call('save', 'submit')
            ->assertHasErrors('attendance');

        $this->assertDatabaseCount('logbooks', 0);
    }
}
