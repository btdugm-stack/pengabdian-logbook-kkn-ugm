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

class PublicSearchPrivacyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regresi untuk temuan kritis audit: PII (no HP, kontak darurat) dan
     * kondisi kesehatan per-entri sebelumnya tampil ke publik tanpa login.
     */
    public function test_public_student_search_does_not_leak_phone_or_emergency_contact(): void
    {
        $this->seed(RoleSeeder::class);
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $student = Student::create([
            'email' => 'azmi@student.demo',
            'name' => 'Azmi Rahasia',
            'faculty' => 'Fakultas Teknik',
            'phone' => '081200000000',
            'emergency_contact' => 'Ibu - 081299999999',
            'region_id' => $region->id,
        ]);
        $student->assignRole('mahasiswa');

        $response = $this->get(route('search.students', ['q' => 'Azmi']));

        $response->assertOk();
        $response->assertSee('Azmi Rahasia');
        $response->assertDontSee('081200000000');
        $response->assertDontSee('081299999999');
    }

    public function test_public_logbook_search_does_not_leak_health_status(): void
    {
        $this->seed(RoleSeeder::class);
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $student = Student::create(['email' => 'azmi@student.demo', 'name' => 'Azmi', 'region_id' => $region->id]);
        $student->assignRole('mahasiswa');
        $theme = Theme::create(['name' => 'Tema Uji']);
        $program = Program::create(['name' => 'Program Uji']);
        $type = ActivityType::create(['name' => 'Jenis Uji']);
        $location = Location::create(['name' => 'Lokasi Uji']);

        Logbook::create([
            'student_id' => $student->id, 'theme_id' => $theme->id, 'program_id' => $program->id,
            'activity_type_id' => $type->id, 'location_id' => $location->id,
            'log_date' => now(), 'progress_note' => 'Catatan publik.',
            'health_status' => 'Sakit - Perlu Pantauan Rahasia',
        ]);

        $response = $this->get(route('search.logbooks'));

        $response->assertOk();
        $response->assertSee('Catatan publik.');
        $response->assertDontSee('Sakit - Perlu Pantauan Rahasia');
    }
}
