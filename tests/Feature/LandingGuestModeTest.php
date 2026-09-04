<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingGuestModeTest extends TestCase
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

    public function test_landing_offers_all_three_entry_paths_to_visitors(): void
    {
        // Blok akun demo hanya dirender kalau ada akun yang bisa dipilih.
        $this->mahasiswa();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Masuk dengan Akun UGM');
        $response->assertSee('Lihat sebagai Tamu');
        $response->assertSee('Coba dengan akun demo');
        // Landing berdiri sendiri - tidak memuat app shell dengan sidebar.
        $response->assertDontSee('Panel Supervisi');
    }

    public function test_signed_in_user_is_sent_from_landing_to_dashboard(): void
    {
        $student = $this->mahasiswa();

        $this->actingAs($student)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_entering_guest_mode_flags_the_session_and_lands_on_public_home(): void
    {
        $this->seed(RoleSeeder::class);

        $response = $this->post(route('guest.enter'));

        $response->assertRedirect(route('public.home'));
        $response->assertSessionHas('guest_mode', true);
    }

    public function test_guest_mode_does_not_unlock_authenticated_pages(): void
    {
        $this->seed(RoleSeeder::class);
        $this->post(route('guest.enter'));

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_clears_guest_mode(): void
    {
        $this->seed(RoleSeeder::class);
        $this->post(route('guest.enter'))->assertSessionHas('guest_mode', true);

        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->assertNull(session('guest_mode'));
    }
}
