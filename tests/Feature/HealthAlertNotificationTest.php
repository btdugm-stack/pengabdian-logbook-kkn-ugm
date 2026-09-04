<?php

namespace Tests\Feature;

use App\Livewire\AttendanceCheckIn;
use App\Models\Region;
use App\Models\Student;
use App\Notifications\StudentHealthAlert;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class HealthAlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        $this->seed(RoleSeeder::class);
        $subUnit = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit Uji']);
        $otherSubUnit = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit Lain']);

        $student = Student::create(['email' => 'azmi@student.demo', 'name' => 'Azmi', 'region_id' => $subUnit->id]);
        $student->assignRole('mahasiswa');

        $kormasit = Student::create(['email' => 'kormasit@demo.kkn', 'name' => 'Kormasit', 'region_id' => $subUnit->id]);
        $kormasit->assignRole('kormasit');

        $unrelatedKormasit = Student::create(['email' => 'kormasit2@demo.kkn', 'name' => 'Kormasit Lain', 'region_id' => $otherSubUnit->id]);
        $unrelatedKormasit->assignRole('kormasit');

        return compact('student', 'kormasit', 'unrelatedKormasit');
    }

    public function test_sick_condition_notifies_the_students_kormasit_only(): void
    {
        Notification::fake();
        ['student' => $student, 'kormasit' => $kormasit, 'unrelatedKormasit' => $unrelatedKormasit] = $this->scenario();

        $this->actingAs($student);
        Livewire::test(AttendanceCheckIn::class)
            ->set('condition', 'Sakit Ringan')
            ->set('condition_note', 'Demam.')
            ->call('checkIn');

        Notification::assertSentTo($kormasit, StudentHealthAlert::class);
        Notification::assertNotSentTo($unrelatedKormasit, StudentHealthAlert::class);
        Notification::assertNotSentTo($student, StudentHealthAlert::class);
    }

    public function test_sehat_condition_does_not_notify_anyone(): void
    {
        Notification::fake();
        ['student' => $student] = $this->scenario();

        $this->actingAs($student);
        Livewire::test(AttendanceCheckIn::class)
            ->set('condition', 'Sehat')
            ->call('checkIn');

        Notification::assertNothingSent();
    }

    public function test_reapplying_the_same_sick_condition_does_not_notify_twice(): void
    {
        Notification::fake();
        ['student' => $student, 'kormasit' => $kormasit] = $this->scenario();

        $this->actingAs($student);
        Livewire::test(AttendanceCheckIn::class)->set('condition', 'Sakit Ringan')->set('condition_note', 'Demam.')->call('checkIn');
        Livewire::test(AttendanceCheckIn::class)->set('condition', 'Sakit Ringan')->set('condition_note', 'Masih demam.')->call('checkIn');

        Notification::assertSentToTimes($kormasit, StudentHealthAlert::class, 1);
    }
}
