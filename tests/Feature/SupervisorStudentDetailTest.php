<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Location;
use App\Models\Logbook;
use App\Models\Program;
use App\Models\Region;
use App\Models\Student;
use App\Models\Theme;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorStudentDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kabupaten Uji > Sub-unit A (Mahasiswa A, kormasit A) & Sub-unit B
     * (Mahasiswa B); DPL membimbing Mahasiswa A dan B. Mahasiswa C berada di
     * kabupaten lain dan bukan bimbingan siapa pun di atas.
     *
     * @return array<string, Student>
     */
    private function scenario(): array
    {
        $this->seed(RoleSeeder::class);

        $kabupaten = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $subUnitA = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit A', 'parent_id' => $kabupaten->id]);
        $subUnitB = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit B', 'parent_id' => $kabupaten->id]);
        $otherKabupaten = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Lain']);

        $make = function (string $email, string $role, ?Region $region, array $extra = []): Student {
            $user = Student::create(['email' => $email, 'name' => ucfirst(strtok($email, '@')), 'region_id' => $region?->id, ...$extra]);
            $user->assignRole($role);

            return $user;
        };

        $users = [
            'studentA' => $make('a@student.demo', 'mahasiswa', $subUnitA, ['phone' => '081200000001', 'emergency_contact' => 'Ibu - 081299990000']),
            'studentB' => $make('b@student.demo', 'mahasiswa', $subUnitB),
            'studentC' => $make('c@student.demo', 'mahasiswa', $otherKabupaten),
            'kormasit' => $make('kormasit@demo.kkn', 'kormasit', $subUnitA),
            'dpl' => $make('dpl@demo.kkn', 'dpl', null),
        ];

        $users['dpl']->advisees()->attach([$users['studentA']->id, $users['studentB']->id]);

        return $users;
    }

    private function logbookFor(Student $student, string $status, string $note): Logbook
    {
        return $student->logbooks()->create([
            'theme_id' => Theme::firstOrCreate(['name' => 'Tema Uji'])->id,
            'program_id' => Program::firstOrCreate(['name' => 'Program Uji'])->id,
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Jenis Uji'])->id,
            'location_id' => Location::firstOrCreate(['name' => 'Lokasi Uji'])->id,
            'log_date' => now(),
            'health_status' => 'Sehat',
            'progress_note' => $note,
            'status' => $status,
        ]);
    }

    public function test_supervisor_sees_contact_details_of_student_in_scope(): void
    {
        ['studentA' => $studentA, 'kormasit' => $kormasit] = $this->scenario();

        $this->actingAs($kormasit)->get(route('overview.student', $studentA))
            ->assertOk()
            ->assertSee('081200000001')
            ->assertSee('Ibu - 081299990000');
    }

    public function test_student_outside_supervisor_scope_is_not_found(): void
    {
        ['studentB' => $studentB, 'kormasit' => $kormasit] = $this->scenario();

        $this->actingAs($kormasit)->get(route('overview.student', $studentB))->assertNotFound();
    }

    public function test_detail_page_lists_submitted_logbooks_but_not_drafts(): void
    {
        ['studentA' => $studentA, 'dpl' => $dpl] = $this->scenario();
        $this->logbookFor($studentA, Logbook::STATUS_SUBMITTED, 'Kegiatan sudah terkirim.');
        $this->logbookFor($studentA, Logbook::STATUS_DRAFT, 'Draf belum dikirim.');

        $this->actingAs($dpl)->get(route('overview.student', $studentA))
            ->assertOk()
            ->assertSee('Kegiatan sudah terkirim.')
            ->assertDontSee('Draf belum dikirim.');
    }

    public function test_detail_page_links_reviewers_to_the_review_page(): void
    {
        ['studentA' => $studentA, 'dpl' => $dpl, 'kormasit' => $kormasit] = $this->scenario();
        $logbook = $this->logbookFor($studentA, Logbook::STATUS_SUBMITTED, 'Kegiatan.');

        $this->actingAs($dpl)->get(route('overview.student', $studentA))
            ->assertOk()
            ->assertSee(route('logbooks.reviews.show', $logbook));

        // Kormasit memantau tetapi tidak mereviu.
        $this->actingAs($kormasit)->get(route('overview.student', $studentA))
            ->assertOk()
            ->assertDontSee(route('logbooks.reviews.show', $logbook));
    }
}
