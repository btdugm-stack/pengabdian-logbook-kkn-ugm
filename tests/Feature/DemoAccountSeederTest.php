<?php

namespace Tests\Feature;

use App\Models\Student;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_admin_can_register_accounts_and_demo_user_cannot(): void
    {
        $this->seed(DemoAccountSeeder::class);

        $admin = Student::where('email', DemoAccountSeeder::ADMIN_EMAIL)->firstOrFail();
        $user = Student::where('email', DemoAccountSeeder::USER_EMAIL)->firstOrFail();

        $this->actingAs($user)->get(route('admin.participants.index'))->assertForbidden();
        $this->actingAs($user)->get(route('attendance.check-in'))->assertOk();

        $this->actingAs($admin)->post(route('admin.participants.store'), [
            'email' => 'baru@mail.ugm.ac.id',
            'nama' => 'Mahasiswa Baru',
            'peran' => 'mahasiswa',
            'wilayah' => 'Kabupaten Sleman / Kecamatan Ngaglik',
        ])->assertRedirect(route('admin.participants.index'));

        $this->assertTrue(Student::where('email', 'baru@mail.ugm.ac.id')->firstOrFail()->hasRole('mahasiswa'));
    }

    public function test_seeding_twice_does_not_duplicate_demo_accounts(): void
    {
        $this->seed(DemoAccountSeeder::class);
        $this->seed(DemoAccountSeeder::class);

        $this->assertDatabaseCount('students', count(Student::ROLES));
        $this->assertDatabaseCount('regions', 4);
    }

    public function test_every_role_has_a_demo_account_that_reaches_its_home_page(): void
    {
        $this->seed(DemoAccountSeeder::class);

        foreach (Student::ROLES as $role) {
            $account = Student::role($role)->sole();

            $this->assertTrue($account->isDemoAccount());
            $this->actingAs($account)->get(route($account->isParticipant() ? 'dashboard' : 'overview'))->assertOk();
        }
    }

    public function test_demo_supervisors_cover_the_demo_student(): void
    {
        $this->seed(DemoAccountSeeder::class);
        $student = Student::where('email', DemoAccountSeeder::USER_EMAIL)->firstOrFail();

        // Pimpinan hanya melihat agregat, jadi tidak "mengawasi" mahasiswa mana pun secara perorangan.
        foreach (array_diff(Student::SUPERVISORY_ROLES, Student::AGGREGATE_ONLY_ROLES) as $role) {
            $this->assertTrue(Student::role($role)->sole()->canSupervise($student), "{$role} demo harus mencakup Mahasiswa Demo");
        }

        $this->assertFalse(Student::role('pimpinan')->sole()->canSupervise($student));
    }
}
