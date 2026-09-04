<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeMahasiswa(string $email = 'azmi@student.demo'): Student
    {
        $this->seed(RoleSeeder::class);
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $student = Student::create(['email' => $email, 'name' => 'Mahasiswa Uji', 'region_id' => $region->id]);
        $student->assignRole('mahasiswa');

        return $student;
    }

    public function test_guest_cannot_open_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_demo_login_with_known_email_signs_in_the_matching_student(): void
    {
        $student = $this->makeMahasiswa();

        $response = $this->post(route('demo-login'), ['email' => $student->email]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($student);
    }

    public function test_demo_login_with_unknown_email_does_not_authenticate_anyone(): void
    {
        $this->makeMahasiswa();

        $response = $this->post(route('demo-login'), ['email' => 'tidak-terdaftar@student.demo']);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
