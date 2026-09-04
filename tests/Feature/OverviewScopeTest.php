<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverviewScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_kormasit_only_sees_students_in_their_own_sub_unit(): void
    {
        $this->seed(RoleSeeder::class);

        $kabupaten = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $subUnitA = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit A', 'parent_id' => $kabupaten->id]);
        $subUnitB = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit B', 'parent_id' => $kabupaten->id]);

        $studentA = Student::create(['email' => 'a@student.demo', 'name' => 'Mahasiswa A', 'region_id' => $subUnitA->id]);
        $studentA->assignRole('mahasiswa');
        $studentB = Student::create(['email' => 'b@student.demo', 'name' => 'Mahasiswa B', 'region_id' => $subUnitB->id]);
        $studentB->assignRole('mahasiswa');

        $kormasit = Student::create(['email' => 'kormasit@demo.kkn', 'name' => 'Kormasit A', 'region_id' => $subUnitA->id]);
        $kormasit->assignRole('kormasit');

        $response = $this->actingAs($kormasit)->get(route('overview'));

        $response->assertOk();
        $response->assertSee('Mahasiswa A');
        $response->assertDontSee('Mahasiswa B');
    }

    public function test_admin_lppm_sees_every_region(): void
    {
        $this->seed(RoleSeeder::class);

        $kabupaten = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $subUnitA = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit A', 'parent_id' => $kabupaten->id]);
        $subUnitB = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit B', 'parent_id' => $kabupaten->id]);

        $studentA = Student::create(['email' => 'a@student.demo', 'name' => 'Mahasiswa A', 'region_id' => $subUnitA->id]);
        $studentA->assignRole('mahasiswa');
        $studentB = Student::create(['email' => 'b@student.demo', 'name' => 'Mahasiswa B', 'region_id' => $subUnitB->id]);
        $studentB->assignRole('mahasiswa');

        $admin = Student::create(['email' => 'lppm@demo.kkn', 'name' => 'Admin LPPM']);
        $admin->assignRole('admin_lppm');

        $response = $this->actingAs($admin)->get(route('overview'));

        $response->assertOk();
        $response->assertSee('Mahasiswa A');
        $response->assertSee('Mahasiswa B');
    }

    public function test_mahasiswa_cannot_open_overview(): void
    {
        $this->seed(RoleSeeder::class);

        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $student = Student::create(['email' => 'a@student.demo', 'name' => 'Mahasiswa A', 'region_id' => $region->id]);
        $student->assignRole('mahasiswa');

        $this->actingAs($student)->get(route('overview'))->assertForbidden();
    }
}
