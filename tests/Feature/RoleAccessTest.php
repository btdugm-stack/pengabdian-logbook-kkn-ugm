<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): Student
    {
        $this->seed(RoleSeeder::class);
        $region = Region::firstOrCreate(['parent_id' => null, 'name' => 'Sub-unit Uji'], ['level' => 'sub_unit']);
        $user = Student::create(['email' => "{$role}@example.test", 'name' => "Akun {$role}", 'region_id' => $region->id]);
        $user->assignRole($role);

        return $user;
    }

    /** @return array<string, array{string}> */
    public static function mahasiswaPages(): array
    {
        return [
            'dashboard' => ['dashboard'],
            'presensi harian' => ['attendance.check-in'],
            'input logbook' => ['logbooks.create'],
            'logbook saya' => ['logbooks.index'],
            'presensi bantuan' => ['assist-attendances.index'],
            'catat presensi bantuan' => ['assist-attendances.create'],
            'peta saya' => ['map.mine'],
            'data kkn' => ['profile.edit'],
        ];
    }

    #[DataProvider('mahasiswaPages')]
    public function test_mahasiswa_can_open_every_page_in_their_menu(string $routeName): void
    {
        $this->actingAs($this->user('mahasiswa'))->get(route($routeName))->assertOk();
    }

    /** @return array<string, array{string}> */
    public static function mahasiswaOnlyRoutes(): array
    {
        return [
            'presensi harian' => ['attendance.check-in'],
            'input logbook' => ['logbooks.create'],
            'logbook saya' => ['logbooks.index'],
            'export logbook' => ['logbooks.export'],
            'presensi bantuan' => ['assist-attendances.index'],
            'peta saya' => ['map.mine'],
        ];
    }

    #[DataProvider('mahasiswaOnlyRoutes')]
    public function test_supervisor_cannot_open_mahasiswa_field_pages(string $routeName): void
    {
        $this->actingAs($this->user('dpl'))->get(route($routeName))->assertForbidden();
    }

    /** @return array<string, array{string}> */
    public static function supervisoryRoles(): array
    {
        return array_combine(Student::SUPERVISORY_ROLES, array_map(fn ($role) => [$role], Student::SUPERVISORY_ROLES));
    }

    #[DataProvider('supervisoryRoles')]
    public function test_every_supervisory_role_can_open_the_supervision_panel(string $role): void
    {
        $supervisor = $this->user($role);

        $this->actingAs($supervisor)->get(route('overview'))->assertOk();
        // Notifikasi eskalasi memuat data per mahasiswa: tertutup untuk peran agregat.
        $this->actingAs($supervisor)->get(route('notifications.index'))
            ->assertStatus(in_array($role, Student::AGGREGATE_ONLY_ROLES, true) ? 403 : 200);
        $this->actingAs($supervisor)->get(route('profile.edit'))->assertOk();
    }

    public function test_supervisor_dashboard_redirects_to_overview(): void
    {
        $this->actingAs($this->user('dpl'))->get(route('dashboard'))->assertRedirect(route('overview'));
    }

    public function test_mahasiswa_cannot_open_notifications(): void
    {
        $this->actingAs($this->user('mahasiswa'))->get(route('notifications.index'))->assertForbidden();
    }

    /** @return array<string, array{string}> */
    public static function publicPages(): array
    {
        return [
            'beranda' => ['public.home'],
            'cari mahasiswa' => ['search.students'],
            'cari logbook' => ['search.logbooks'],
            'peta sebaran' => ['map.public'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_visitor_can_open_public_pages(string $routeName): void
    {
        $this->seed(RoleSeeder::class);

        $this->get(route($routeName))->assertOk();
    }

    public function test_pages_send_security_headers(): void
    {
        $this->get(route('public.home'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
