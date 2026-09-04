<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Location;
use App\Models\Program;
use App\Models\Region;
use App\Models\Student;
use App\Models\Theme;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapPublicPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function seedStudentWithSickLogbook(): Student
    {
        $this->seed(RoleSeeder::class);

        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $student = Student::create(['email' => 'a@student.demo', 'name' => 'Mahasiswa A', 'region_id' => $region->id]);
        $student->assignRole('mahasiswa');

        $theme = Theme::create(['name' => 'Tema Uji']);
        $program = Program::create(['name' => 'Program Uji']);
        $location = Location::create(['name' => 'Lokasi Uji', 'latitude' => -7.7956, 'longitude' => 110.3695]);
        $student->logbooks()->create([
            'theme_id' => $theme->id,
            'program_id' => $program->id,
            'activity_type_id' => ActivityType::create(['name' => 'Jenis Uji'])->id,
            'location_id' => $location->id,
            'log_date' => now(),
            'community_count' => 5,
            'health_status' => 'Sakit Berat',
            'progress_note' => 'Catatan uji.',
            'status' => 'Submitted',
        ]);

        return $student;
    }

    public function test_public_map_does_not_expose_health_condition(): void
    {
        $this->seedStudentWithSickLogbook();

        $response = $this->get(route('map.public'));

        $response->assertOk();
        // Nama & lokasi tetap tampil, tapi kondisi kesehatan tidak boleh ada di JSON marker.
        $response->assertSee('Mahasiswa A');
        $response->assertSee('"health":null', false);
        $response->assertDontSee('Sakit Berat');
        // Warna marker dinetralkan (bukan warna berbasis kondisi kesehatan).
        $response->assertSee('#3B82F6', false);
    }

    public function test_authenticated_own_map_still_shows_health_condition(): void
    {
        $student = $this->seedStudentWithSickLogbook();

        $this->actingAs($student)->get(route('map.mine'))
            ->assertOk()
            ->assertSee('Sakit Berat');
    }

    public function test_master_name_strips_html_tags(): void
    {
        $this->seed(RoleSeeder::class);

        $program = Program::firstOrCreateFromName('XSS<img src=x onerror=alert(1)>probe');

        $this->assertSame('XSSprobe', $program->name);
        $this->assertSame(1, Program::where('name', 'XSSprobe')->count());
    }
}
