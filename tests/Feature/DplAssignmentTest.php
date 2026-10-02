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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DplAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Student $admin;

    private Student $dpl;

    private Student $otherDpl;

    private Student $anna;

    private Student $budi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $subUnit = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit A']);

        $this->admin = $this->account('admin@ugm.ac.id', 'admin_lppm');
        $this->dpl = $this->account('dpl@ugm.ac.id', 'dpl');
        $this->otherDpl = $this->account('dpl2@ugm.ac.id', 'dpl');
        $this->anna = $this->account('anna@mail.ugm.ac.id', 'mahasiswa', $subUnit);
        $this->budi = $this->account('budi@mail.ugm.ac.id', 'mahasiswa', $subUnit);
    }

    private function account(string $email, string $role, ?Region $region = null): Student
    {
        $account = Student::create(['email' => $email, 'name' => 'Nama '.ucfirst(strtok($email, '@')), 'region_id' => $region?->id]);
        $account->assignRole($role);

        return $account;
    }

    private function submittedLogbook(Student $owner, string $note): Logbook
    {
        return $owner->logbooks()->create([
            'theme_id' => Theme::firstOrCreate(['name' => 'Tema Uji'])->id,
            'program_id' => Program::firstOrCreate(['name' => 'Program Uji'])->id,
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Jenis Uji'])->id,
            'location_id' => Location::firstOrCreate(['name' => 'Lokasi Uji'])->id,
            'log_date' => now()->subHour(),
            'health_status' => 'Sehat',
            'progress_note' => $note,
            'status' => Logbook::STATUS_SUBMITTED,
        ]);
    }

    public function test_dpl_without_assignments_sees_no_students_even_in_the_same_region(): void
    {
        $this->dpl->update(['region_id' => $this->anna->region_id]);
        $this->submittedLogbook($this->anna, 'Logbook Anna.');

        $this->actingAs($this->dpl)->get(route('overview'))->assertOk()->assertSee('Belum ada mahasiswa bimbingan')->assertDontSee('Nama Anna');
        $this->actingAs($this->dpl)->get(route('logbooks.reviews.index'))->assertOk()->assertDontSee('Logbook Anna.');
    }

    public function test_assignment_decides_which_logbooks_a_dpl_reviews_and_monitors(): void
    {
        $annaLogbook = $this->submittedLogbook($this->anna, 'Logbook Anna.');
        $budiLogbook = $this->submittedLogbook($this->budi, 'Logbook Budi.');

        $this->actingAs($this->admin)
            ->put(route('admin.dpl-assignments.update', $this->dpl), ['mahasiswa' => [$this->anna->id]])
            ->assertRedirect(route('admin.dpl-assignments.index'));

        // Antrean reviu, overview, pencarian, dan detail hanya memuat mahasiswa bimbingannya.
        $this->actingAs($this->dpl);
        $this->get(route('logbooks.reviews.index'))->assertSee('Logbook Anna.')->assertDontSee('Logbook Budi.');
        $this->get(route('overview'))->assertSee('Nama Anna')->assertDontSee('Nama Budi');
        $this->get(route('logbooks.browse.index'))->assertSee('Logbook Anna.')->assertDontSee('Logbook Budi.');
        $this->get(route('overview.student', $this->anna))->assertOk()->assertSee('Nama Dpl');
        $this->get(route('overview.student', $this->budi))->assertNotFound();

        $this->post(route('logbooks.reviews.store', $annaLogbook), ['keputusan' => 'setujui'])->assertRedirect();
        $this->post(route('logbooks.reviews.store', $budiLogbook), ['keputusan' => 'setujui'])->assertForbidden();

        $this->assertSame(Logbook::STATUS_APPROVED, $annaLogbook->fresh()->status);
        $this->assertSame(Logbook::STATUS_SUBMITTED, $budiLogbook->fresh()->status);
    }

    public function test_reassigning_replaces_the_list_and_removes_access_to_released_students(): void
    {
        $this->dpl->advisees()->attach([$this->anna->id, $this->budi->id]);
        $budiLogbook = $this->submittedLogbook($this->budi, 'Logbook Budi.');

        $this->actingAs($this->admin)
            ->put(route('admin.dpl-assignments.update', $this->dpl), ['mahasiswa' => [$this->anna->id]])
            ->assertSessionHas('flash_success');

        $this->assertSame([$this->anna->id], $this->dpl->advisees()->pluck('students.id')->all());
        $this->actingAs($this->dpl)->post(route('logbooks.reviews.store', $budiLogbook), ['keputusan' => 'setujui'])->assertForbidden();

        // Tanpa pilihan sama sekali: semua bimbingan dilepas.
        $this->actingAs($this->admin)->put(route('admin.dpl-assignments.update', $this->dpl), [])->assertRedirect();
        $this->assertSame(0, $this->dpl->advisees()->count());
    }

    public function test_a_student_can_have_two_dpls_and_health_alerts_go_to_both(): void
    {
        $this->dpl->advisees()->attach($this->anna);
        $this->otherDpl->advisees()->attach($this->anna);

        $recipients = Student::supervisorsFor($this->anna)->pluck('email');

        $this->assertTrue($recipients->contains('dpl@ugm.ac.id'));
        $this->assertTrue($recipients->contains('dpl2@ugm.ac.id'));
        $this->assertFalse(Student::supervisorsFor($this->budi)->pluck('email')->contains('dpl@ugm.ac.id'));
    }

    public function test_only_participants_can_be_assigned_and_only_to_a_dpl(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.dpl-assignments.update', $this->dpl), ['mahasiswa' => [$this->anna->id, $this->otherDpl->id]])
            ->assertSessionHasErrors('mahasiswa.1');
        $this->assertSame(0, $this->dpl->advisees()->count());

        // Akun yang bukan DPL tidak punya halaman penugasan.
        $this->actingAs($this->admin)->get(route('admin.dpl-assignments.edit', $this->anna))->assertNotFound();
        $this->actingAs($this->admin)->put(route('admin.dpl-assignments.update', $this->anna), ['mahasiswa' => [$this->budi->id]])->assertNotFound();
    }

    public function test_assignment_pages_list_dpls_and_group_students_by_placement(): void
    {
        $this->dpl->advisees()->attach($this->anna);

        $this->actingAs($this->admin)->get(route('admin.dpl-assignments.index'))
            ->assertOk()
            ->assertSee('Nama Dpl')
            ->assertSee('1 dari 2 peserta belum punya DPL');

        $this->actingAs($this->admin)->get(route('admin.dpl-assignments.edit', $this->otherDpl))
            ->assertOk()
            ->assertSee('Sub-unit A')
            ->assertSee('DPL lain: Nama Dpl');
    }

    public function test_dpl_chooses_their_own_students_and_gains_review_access(): void
    {
        $annaLogbook = $this->submittedLogbook($this->anna, 'Logbook Anna.');
        $this->otherDpl->advisees()->attach($this->budi);

        $this->actingAs($this->dpl)->get(route('overview'))->assertSee(route('advisees.edit'));
        $this->actingAs($this->dpl)->get(route('advisees.edit'))
            ->assertOk()
            ->assertSee('Nama Anna')
            ->assertSee('DPL lain: Nama Dpl2');

        $this->actingAs($this->dpl)
            ->put(route('advisees.update'), ['mahasiswa' => [$this->anna->id]])
            ->assertRedirect(route('advisees.edit'))
            ->assertSessionHas('flash_success');

        $this->assertSame([$this->anna->id], $this->dpl->advisees()->pluck('students.id')->all());
        $this->actingAs($this->dpl)->get(route('logbooks.reviews.index'))->assertSee('Logbook Anna.');
        $this->actingAs($this->dpl)->post(route('logbooks.reviews.store', $annaLogbook), ['keputusan' => 'setujui'])->assertRedirect();

        // Pilihan satu DPL tidak mengubah bimbingan DPL lain.
        $this->assertSame([$this->budi->id], $this->otherDpl->advisees()->pluck('students.id')->all());
    }

    public function test_dpl_releasing_a_student_loses_access_and_cannot_pick_non_participants(): void
    {
        $this->dpl->advisees()->attach($this->anna);

        $this->actingAs($this->dpl)
            ->put(route('advisees.update'), ['mahasiswa' => [$this->otherDpl->id]])
            ->assertSessionHasErrors('mahasiswa.0');
        $this->assertSame([$this->anna->id], $this->dpl->advisees()->pluck('students.id')->all());

        $this->actingAs($this->dpl)->put(route('advisees.update'), [])->assertRedirect(route('advisees.edit'));

        $this->assertSame(0, $this->dpl->advisees()->count());
        $this->actingAs($this->dpl)->get(route('overview.student', $this->anna))->assertNotFound();
    }

    public function test_only_dpl_accounts_have_the_self_assignment_page(): void
    {
        foreach ([$this->admin, $this->anna] as $user) {
            $this->actingAs($user)->get(route('advisees.edit'))->assertForbidden();
            $this->actingAs($user)->put(route('advisees.update'), ['mahasiswa' => [$this->budi->id]])->assertForbidden();
        }

        $this->assertDatabaseCount('dpl_student', 0);
    }

    /** @return array<string, array{string}> */
    public static function nonAdmins(): array
    {
        return ['dpl' => ['dpl'], 'mahasiswa' => ['anna']];
    }

    #[DataProvider('nonAdmins')]
    public function test_only_admin_manages_assignments(string $user): void
    {
        $actor = $this->{$user};

        $this->actingAs($actor)->get(route('admin.dpl-assignments.index'))->assertForbidden();
        $this->actingAs($actor)->get(route('admin.dpl-assignments.edit', $this->dpl))->assertForbidden();
        $this->actingAs($actor)->put(route('admin.dpl-assignments.update', $this->dpl), ['mahasiswa' => [$this->budi->id]])->assertForbidden();

        $this->assertSame(0, $this->dpl->advisees()->count());
    }
}
