<?php

namespace Tests\Feature;

use App\Livewire\LogbookForm;
use App\Models\DailyAttendance;
use App\Models\Location;
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
            ->assertRedirect(route('logbooks.index'));

        $this->assertDatabaseCount('logbooks', 1);
        $this->assertDatabaseHas('themes', ['name' => 'Tema Baru Dari Form']);

        $theme = Theme::where('name', 'Tema Baru Dari Form')->first();
        $logbook = $student->logbooks()->first();
        $this->assertSame($theme->id, $logbook->theme_id);

        // Kondisi kesehatan dibersamai dari presensi hari itu, bukan diinput ulang.
        $this->assertSame('Sakit Ringan', $logbook->health_status);
    }

    public function test_submission_reports_each_incomplete_field(): void
    {
        $this->actingAs($this->checkedInStudent());

        Livewire::test(LogbookForm::class)
            ->set('theme_new', 'Tema Terisi')
            ->call('save', 'submit')
            ->assertHasErrors(['program_name', 'activity_type_name', 'location_name', 'progress_note'])
            ->assertHasNoErrors(['theme_name', 'log_date'])
            ->assertSee('Lengkapi atau perbaiki 4 isian berikut')
            ->assertSee('Pilih program kerja dari daftar atau ketik program baru.');

        $this->assertDatabaseCount('logbooks', 0);
    }

    public function test_current_location_coordinate_alone_satisfies_the_location_field(): void
    {
        $student = $this->checkedInStudent();
        $this->actingAs($student);

        Livewire::test(LogbookForm::class)
            ->set('theme_new', 'Tema')
            ->set('program_new', 'Program')
            ->set('activity_type_new', 'Jenis')
            ->set('coordinate', '-7.7712345, 110.3776543')
            ->set('progress_note', 'Catatan.')
            ->call('save', 'submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('logbooks.index'));

        $location = $student->logbooks()->sole()->location;
        $this->assertSame('Koordinat -7.77123, 110.37765', $location->name);
        $this->assertEqualsWithDelta(-7.7712345, (float) $location->latitude, 0.0000001);
    }

    public function test_coordinate_near_a_registered_location_reuses_it(): void
    {
        $student = $this->checkedInStudent();
        $this->actingAs($student);
        $hall = Location::create(['name' => 'Balai Desa Candirejo', 'latitude' => -7.7712, 'longitude' => 110.3776]);
        Location::create(['name' => 'Posyandu Jauh', 'latitude' => -7.7800, 'longitude' => 110.3776]);

        Livewire::test(LogbookForm::class)
            ->set('theme_new', 'Tema')
            ->set('program_new', 'Program')
            ->set('activity_type_new', 'Jenis')
            ->set('coordinate', '-7.7713, 110.3777')
            ->set('progress_note', 'Catatan.')
            ->call('save', 'submit')
            ->assertRedirect(route('logbooks.index'));

        $this->assertSame($hall->id, $student->logbooks()->sole()->location_id);
        $this->assertDatabaseCount('locations', 2);
    }

    public function test_auto_named_locations_are_not_offered_in_the_location_dropdown(): void
    {
        $this->actingAs($this->checkedInStudent());
        Location::create(['name' => 'Balai Desa Candirejo']);
        Location::nearOrCreateFromCoordinates(-7.5, 110.5);

        Livewire::test(LogbookForm::class)
            ->assertSee('Balai Desa Candirejo')
            ->assertDontSee('Koordinat -7.50000');
    }

    public function test_typed_master_name_reuses_existing_row_despite_case_and_spacing(): void
    {
        $student = $this->checkedInStudent();
        $this->actingAs($student);
        $theme = Theme::create(['name' => 'Digitalisasi Desa']);
        $location = Location::create(['name' => 'Balai Desa Candirejo']);

        Livewire::test(LogbookForm::class)
            ->set('theme_new', '  digitalisasi   DESA ')
            ->set('program_new', 'Program Baru')
            ->set('activity_type_new', 'Jenis Baru')
            ->set('location_new', 'balai desa candirejo')
            ->set('progress_note', 'Catatan.')
            ->call('save', 'submit')
            ->assertRedirect(route('logbooks.index'));

        $logbook = $student->logbooks()->sole();
        $this->assertSame($theme->id, $logbook->theme_id);
        $this->assertSame($location->id, $logbook->location_id);
        $this->assertDatabaseCount('themes', 1);
        $this->assertDatabaseCount('locations', 1);
    }

    public function test_master_fields_are_searchable_and_list_existing_names(): void
    {
        $this->actingAs($this->checkedInStudent());
        Theme::create(['name' => 'Digitalisasi Desa']);

        Livewire::test(LogbookForm::class)
            ->assertSeeHtml('role="combobox"')
            ->assertSee('Digitalisasi Desa')
            ->assertDontSeeHtml('<select');
    }

    public function test_period_and_theme_from_the_students_kkn_data_fill_a_new_logbook(): void
    {
        $student = $this->checkedInStudent();
        $student->update(['kkn_period' => 'Periode 2 Tahun 2026', 'kkn_theme' => 'Digitalisasi Desa']);
        $this->actingAs($student);

        // Tema tidak disentuh di form: terisi dari Data KKN.
        Livewire::test(LogbookForm::class)
            ->assertSet('theme_name', 'Digitalisasi Desa')
            ->assertSee('Periode 2 Tahun 2026')
            ->set('program_new', 'Program')
            ->set('activity_type_new', 'Jenis')
            ->set('location_new', 'Lokasi')
            ->set('progress_note', 'Catatan.')
            ->call('save', 'submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('logbooks.index'));

        $logbook = $student->logbooks()->sole();
        $this->assertSame('Periode 2 Tahun 2026', $logbook->kkn_period);
        $this->assertSame('Digitalisasi Desa', $logbook->theme->name);
    }

    public function test_correcting_kkn_data_applies_to_new_logbooks_but_not_old_ones(): void
    {
        $student = $this->checkedInStudent();
        $student->update(['kkn_period' => 'Periode Salah', 'kkn_theme' => 'Tema Salah']);
        $this->actingAs($student);
        $fill = fn () => Livewire::test(LogbookForm::class)
            ->set('program_new', 'Program')->set('activity_type_new', 'Jenis')->set('location_new', 'Lokasi')
            ->set('progress_note', 'Catatan.')
            ->call('save', 'submit');
        $fill();

        $this->patch(route('profile.update'), ['name' => $student->name, 'kkn_period' => 'Periode 2 Tahun 2026', 'kkn_theme' => 'Tema Benar'])
            ->assertRedirect(route('profile.edit'));
        $fill();

        $this->assertSame(['Periode Salah', 'Periode 2 Tahun 2026'], $student->logbooks()->orderBy('id')->pluck('kkn_period')->all());
        $this->assertSame('Tema Benar', $student->logbooks()->latest('id')->first()->theme->name);
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
