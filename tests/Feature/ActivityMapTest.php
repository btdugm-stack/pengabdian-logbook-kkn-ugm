<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Location;
use App\Models\Logbook;
use App\Models\Program;
use App\Models\Region;
use App\Models\Student;
use App\Models\Theme;
use App\Support\ActivityMap;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ActivityMapTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $region = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit A']);
        $this->student = Student::create(['email' => 'mhs@mail.ugm.ac.id', 'name' => 'Nama Mahasiswa', 'region_id' => $region->id]);
        $this->student->assignRole('mahasiswa');
    }

    private function logbook(string $theme, string $health = 'Sehat', bool $located = true): Logbook
    {
        return $this->student->logbooks()->create([
            'theme_id' => Theme::firstOrCreate(['name' => $theme])->id,
            'program_id' => Program::firstOrCreate(['name' => 'Program Uji'])->id,
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Jenis Uji'])->id,
            'location_id' => Location::firstOrCreate(
                ['name' => $located ? 'Balai Desa' : 'Tanpa Koordinat'],
                $located ? ['latitude' => -7.77, 'longitude' => 110.37] : [],
            )->id,
            'log_date' => now()->subHour(),
            'health_status' => $health,
            'progress_note' => 'Catatan.',
            'status' => Logbook::STATUS_SUBMITTED,
        ]);
    }

    /** @return Collection<int, Logbook> */
    private function logbooks(): Collection
    {
        return Logbook::with(['student', 'theme', 'program', 'location'])->orderBy('id')->get();
    }

    public function test_health_map_groups_points_by_condition_and_lists_only_groups_present(): void
    {
        $this->logbook('Tema', 'Sehat');
        $this->logbook('Tema', 'Sakit Berat');
        $this->logbook('Tema', 'Alpha');
        $this->logbook('Tema', 'Sehat', located: false);

        $map = ActivityMap::byHealth($this->logbooks());

        $this->assertCount(3, $map['markers']);
        $this->assertSame(['sehat', 'sakit-berat', 'izin-alpha'], array_column($map['groups'], 'key'));
        $this->assertSame(['sehat', 'sakit-berat', 'izin-alpha'], array_column($map['markers'], 'group'));
        $this->assertSame('Sakit Berat', $map['markers'][1]['health']);
    }

    public function test_theme_map_never_carries_health_and_can_drop_student_names(): void
    {
        $this->logbook('Lingkungan', 'Sakit Berat');

        $public = ActivityMap::byTheme($this->logbooks());
        $aggregate = ActivityMap::byTheme($this->logbooks(), withStudent: false);

        $this->assertNull($public['markers'][0]['health']);
        $this->assertSame('Nama Mahasiswa', $public['markers'][0]['student']);
        $this->assertNull($aggregate['markers'][0]['student']);
        $this->assertSame('Lingkungan', $public['groups'][0]['label']);
    }

    public function test_a_themes_color_does_not_change_with_what_is_currently_shown(): void
    {
        $this->logbook('Tema Pertama');
        $this->logbook('Tema Kedua');

        $both = ActivityMap::byTheme($this->logbooks());
        $onlySecond = ActivityMap::byTheme($this->logbooks()->slice(1));

        $this->assertSame($both['groups'][1]['color'], $onlySecond['groups'][0]['color']);
        $this->assertNotSame($both['groups'][0]['color'], $both['groups'][1]['color']);
    }

    public function test_themes_beyond_the_palette_fold_into_one_other_group(): void
    {
        foreach (range(1, 10) as $number) {
            $this->logbook("Tema {$number}");
        }

        $map = ActivityMap::byTheme($this->logbooks());

        $this->assertCount(9, $map['groups']);
        $this->assertSame('Tema lainnya', $map['groups'][8]['label']);
        $this->assertCount(8, array_unique(array_column(array_slice($map['groups'], 0, 8), 'color')));
        $this->assertSame(['tema-lainnya', 'tema-lainnya'], array_column(array_slice($map['markers'], 8), 'group'));
    }

    public function test_map_pages_render_the_filterable_legend(): void
    {
        $this->logbook('Lingkungan', 'Sakit Ringan');

        $this->get(route('map.public'))->assertOk()->assertSee('Tema kegiatan')->assertSee('renderActivityMap', false);
        $this->actingAs($this->student)->get(route('map.mine'))->assertOk()->assertSee('Kondisi kesehatan')->assertSee('Sakit Ringan');
    }

    public function test_map_without_located_logbooks_explains_why_it_is_empty(): void
    {
        $this->logbook('Lingkungan', located: false);

        $this->get(route('map.public'))->assertOk()->assertSee('Belum ada logbook dengan koordinat lokasi')->assertDontSee('Tema kegiatan');
    }
}
