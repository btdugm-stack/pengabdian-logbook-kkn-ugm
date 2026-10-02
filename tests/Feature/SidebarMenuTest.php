<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_data_menu_is_shown_to_guests_only(): void
    {
        $this->seed(RoleSeeder::class);
        $region = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit A']);
        $student = Student::create(['email' => 'mhs@mail.ugm.ac.id', 'name' => 'Mahasiswa', 'region_id' => $region->id]);
        $student->assignRole('mahasiswa');

        // Tamu (dengan atau tanpa memilih "Lihat sebagai Tamu") melihat menu Data Publik.
        $this->get(route('public.home'))->assertOk()->assertSee('Data Publik')->assertSee('Peta Sebaran');
        $this->withSession(['guest_mode' => true])->get(route('search.students'))->assertOk()->assertSee('Data Publik');

        // Akun terdaftar tidak, tetapi halaman publiknya tetap bisa dibuka.
        $this->actingAs($student)->get(route('dashboard'))->assertOk()->assertDontSee('Data Publik')->assertDontSee('Peta Sebaran');
        $this->actingAs($student)->get(route('map.public'))->assertOk()->assertDontSee('Data Publik');
    }
}
