<?php

namespace Tests\Feature;

use App\Livewire\AttendanceCheckIn;
use App\Models\Region;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    use RefreshDatabase;

    private function mahasiswa(): Student
    {
        $this->seed(RoleSeeder::class);
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $student = Student::create(['email' => 'azmi@student.demo', 'name' => 'Azmi', 'region_id' => $region->id]);
        $student->assignRole('mahasiswa');

        return $student;
    }

    public function test_check_in_creates_one_attendance_row_for_today(): void
    {
        $student = $this->mahasiswa();
        $this->actingAs($student);

        Livewire::test(AttendanceCheckIn::class)
            ->set('condition', 'Sehat')
            ->call('checkIn');

        $this->assertDatabaseCount('daily_attendances', 1);
        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $student->id,
            'condition' => 'Sehat',
        ]);
        $this->assertTrue($student->fresh()->todayAttendance()?->attendance_date->isToday());
    }

    public function test_checking_in_twice_the_same_day_updates_instead_of_duplicating(): void
    {
        $student = $this->mahasiswa();
        $this->actingAs($student);

        Livewire::test(AttendanceCheckIn::class)->set('condition', 'Sehat')->call('checkIn');
        Livewire::test(AttendanceCheckIn::class)
            ->set('condition', 'Sakit Ringan')
            ->set('condition_note', 'Demam ringan.')
            ->call('checkIn');

        $this->assertDatabaseCount('daily_attendances', 1);
        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $student->id,
            'condition' => 'Sakit Ringan',
        ]);
    }

    public function test_condition_note_is_required_when_condition_is_not_sehat(): void
    {
        $student = $this->mahasiswa();
        $this->actingAs($student);

        Livewire::test(AttendanceCheckIn::class)
            ->set('condition', 'Sakit Berat')
            ->set('condition_note', '')
            ->call('checkIn')
            ->assertHasErrors('condition_note');

        $this->assertDatabaseCount('daily_attendances', 0);
    }

    public function test_check_out_records_check_out_time(): void
    {
        $student = $this->mahasiswa();
        $this->actingAs($student);

        Livewire::test(AttendanceCheckIn::class)->set('condition', 'Sehat')->call('checkIn');
        Livewire::test(AttendanceCheckIn::class)->call('checkOut');

        $this->assertNotNull($student->fresh()->todayAttendance()->check_out_time);
    }
}
