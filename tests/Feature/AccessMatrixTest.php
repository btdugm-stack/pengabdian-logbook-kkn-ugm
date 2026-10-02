<?php

namespace Tests\Feature;

use App\Livewire\LogbookForm;
use App\Models\ActivityType;
use App\Models\DailyAttendance;
use App\Models\Location;
use App\Models\Logbook;
use App\Models\Program;
use App\Models\Region;
use App\Models\Student;
use App\Models\Theme;
use App\Support\ParticipantImporter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Matriks hak akses proses bisnis: Mahasiswa, Ketua (kormasit/korcam), DPL,
 * Admin (admin_lppm/super_admin), Fakultas, Pimpinan.
 *
 * Wilayah: Kabupaten Uji > Kecamatan Uji > Sub-unit A & Sub-unit B; Kabupaten Lain.
 *   mhsA  : Sub-unit A, Fakultas Teknik      kormasit: Sub-unit A (ketua sub-unit)
 *   mhsB  : Sub-unit B, Fakultas Hukum       korcam  : Sub-unit A (ketua kecamatan)
 *   mhsC  : Kabupaten Lain, Fakultas Teknik  dpl     : membimbing mhsA, mhsB, dan kedua ketua
 *   fakultas: unit Fakultas Teknik           pimpinan, admin: semua
 */
class AccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, Student> */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $kabupaten = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $kecamatan = Region::create(['level' => 'kecamatan', 'name' => 'Kecamatan Uji', 'parent_id' => $kabupaten->id]);
        $subA = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit A', 'parent_id' => $kecamatan->id]);
        $subB = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit B', 'parent_id' => $kecamatan->id]);
        $other = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Lain']);

        $make = function (string $key, string $role, ?Region $region, ?string $faculty = null): void {
            $user = Student::create(['email' => "{$key}@ugm.ac.id", 'name' => 'Nama '.ucfirst($key), 'region_id' => $region?->id, 'faculty' => $faculty]);
            $user->assignRole($role);
            $this->users[$key] = $user;
        };

        $make('mhsA', 'mahasiswa', $subA, 'Fakultas Teknik');
        $make('mhsB', 'mahasiswa', $subB, 'Fakultas Hukum');
        $make('mhsC', 'mahasiswa', $other, 'Fakultas Teknik');
        $make('kormasit', 'kormasit', $subA, 'Fakultas Biologi');
        $make('korcam', 'korcam', $subA, 'Fakultas Biologi');
        $make('dpl', 'dpl', null);
        $make('fakultas', 'fakultas', null, 'Fakultas Teknik');
        $make('pimpinan', 'pimpinan', null);
        $make('admin', 'admin_lppm', null);
        $make('super', 'super_admin', null);

        // DPL membimbing peserta di Kabupaten Uji (lewat penugasan, bukan wilayah akunnya).
        $this->users['dpl']->advisees()->attach(collect(['mhsA', 'mhsB', 'kormasit', 'korcam'])->map(fn ($key) => $this->users[$key]->id));
    }

    private function logbook(string $owner, string $note, string $status = Logbook::STATUS_SUBMITTED, string $health = 'Sakit Ringan'): Logbook
    {
        return $this->users[$owner]->logbooks()->create([
            'theme_id' => Theme::firstOrCreate(['name' => 'Tema Uji'])->id,
            'program_id' => Program::firstOrCreate(['name' => 'Program Uji'])->id,
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Jenis Uji'])->id,
            'location_id' => Location::firstOrCreate(['name' => 'Lokasi Uji'])->id,
            'log_date' => now()->subHour(),
            'community_count' => 10,
            'health_status' => $health,
            'progress_note' => $note,
            'status' => $status,
        ]);
    }

    private function checkIn(string $owner): void
    {
        DailyAttendance::create([
            'student_id' => $this->users[$owner]->id,
            'attendance_date' => today(),
            'check_in_time' => now(),
            'condition' => 'Sehat',
            'region_id' => $this->users[$owner]->region_id,
        ]);
    }

    /** @return array<string, array{string, bool}> */
    public static function fieldAccess(): array
    {
        return [
            'mahasiswa' => ['mhsA', true],
            'ketua sub-unit' => ['kormasit', true],
            'ketua kecamatan' => ['korcam', true],
            'dpl' => ['dpl', false],
            'fakultas' => ['fakultas', false],
            'pimpinan' => ['pimpinan', false],
            'admin' => ['admin', false],
        ];
    }

    #[DataProvider('fieldAccess')]
    public function test_only_participants_have_their_own_attendance_and_logbook(string $user, bool $allowed): void
    {
        foreach (['attendance.check-in', 'logbooks.create', 'logbooks.index', 'logbooks.export'] as $route) {
            $this->actingAs($this->users[$user])->get(route($route))->assertStatus($allowed ? 200 : 403);
        }
    }

    /** @return array<string, array{string, bool}> */
    public static function validationAccess(): array
    {
        return [
            'mahasiswa' => ['mhsA', false],
            'ketua' => ['kormasit', false],
            'dpl' => ['dpl', true],
            'fakultas' => ['fakultas', false],
            'pimpinan' => ['pimpinan', false],
            'admin' => ['admin', true],
            'super admin' => ['super', true],
        ];
    }

    #[DataProvider('validationAccess')]
    public function test_only_dpl_and_admin_validate_logbooks(string $user, bool $allowed): void
    {
        $logbook = $this->logbook('mhsA', 'Menunggu validasi.');

        $this->actingAs($this->users[$user])->get(route('logbooks.reviews.index'))->assertStatus($allowed ? 200 : 403);
        $this->actingAs($this->users[$user])
            ->post(route('logbooks.reviews.store', $logbook), ['keputusan' => 'setujui'])
            ->assertStatus($allowed ? 302 : 403);

        $this->assertSame($allowed ? Logbook::STATUS_APPROVED : Logbook::STATUS_SUBMITTED, $logbook->fresh()->status);
    }

    /** @return array<string, array{string, array<int, string>, array<int, string>}> */
    public static function searchScopes(): array
    {
        return [
            'ketua sub-unit: kelompoknya' => ['kormasit', ['Catatan A'], ['Catatan B', 'Catatan C']],
            'ketua kecamatan: satu kecamatan' => ['korcam', ['Catatan A', 'Catatan B'], ['Catatan C']],
            'dpl: bimbingannya' => ['dpl', ['Catatan A', 'Catatan B'], ['Catatan C']],
            'fakultas: unitnya' => ['fakultas', ['Catatan A', 'Catatan C'], ['Catatan B']],
            'admin: semua' => ['admin', ['Catatan A', 'Catatan B', 'Catatan C'], []],
            'pimpinan: semua' => ['pimpinan', ['Catatan A', 'Catatan B', 'Catatan C'], []],
        ];
    }

    /**
     * @param  array<int, string>  $visible
     * @param  array<int, string>  $hidden
     */
    #[DataProvider('searchScopes')]
    public function test_logbook_search_and_export_follow_each_roles_scope(string $user, array $visible, array $hidden): void
    {
        $this->logbook('mhsA', 'Catatan A');
        $this->logbook('mhsB', 'Catatan B');
        $this->logbook('mhsC', 'Catatan C');
        $this->logbook('mhsA', 'Catatan draft', Logbook::STATUS_DRAFT);

        $page = $this->actingAs($this->users[$user])->get(route('logbooks.browse.index'))->assertOk()->assertDontSee('Catatan draft');
        array_map($page->assertSee(...), $visible);
        array_map($page->assertDontSee(...), $hidden);

        $csv = $this->actingAs($this->users[$user])->get(route('logbooks.browse.export'))->assertOk()->streamedContent();
        foreach ($hidden as $note) {
            $this->assertStringNotContainsString($note, $csv);
        }
    }

    public function test_mahasiswa_has_no_scoped_search_only_their_own_logbooks(): void
    {
        $this->logbook('mhsA', 'Catatan milik A');
        $this->logbook('mhsB', 'Catatan milik B');

        $this->actingAs($this->users['mhsA'])->get(route('logbooks.browse.index'))->assertForbidden();
        $this->actingAs($this->users['mhsA'])->get(route('logbooks.index'))->assertSee('Catatan milik A')->assertDontSee('Catatan milik B');
    }

    public function test_pimpinan_sees_strategic_data_without_individual_details(): void
    {
        $this->logbook('mhsA', 'Catatan disetujui', Logbook::STATUS_APPROVED);
        $this->logbook('mhsB', 'Catatan ditolak', Logbook::STATUS_REJECTED);
        $pimpinan = $this->users['pimpinan'];

        // Pencarian: tanpa kolom kondisi, tanpa logbook yang ditolak.
        $this->actingAs($pimpinan)->get(route('logbooks.browse.index'))
            ->assertOk()
            ->assertSee('Catatan disetujui')
            ->assertDontSee('Catatan ditolak')
            ->assertDontSee('Sakit Ringan');

        // Export: rekap per tema/program, tanpa nama mahasiswa.
        $csv = $this->actingAs($pimpinan)->get(route('logbooks.browse.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Tema Uji', $csv);
        $this->assertStringNotContainsString('Nama MhsA', $csv);
        $this->assertStringNotContainsString('Catatan disetujui', $csv);

        // Overview: angka saja, tanpa daftar nama; detail mahasiswa dan notifikasi tertutup.
        $this->actingAs($pimpinan)->get(route('overview'))->assertOk()->assertDontSee('Nama MhsA')->assertDontSee('Daftar Mahasiswa');
        $this->actingAs($pimpinan)->get(route('overview.student', $this->users['mhsA']))->assertNotFound();
        $this->actingAs($pimpinan)->get(route('notifications.index'))->assertForbidden();
    }

    public function test_fakultas_sees_only_students_of_its_unit_and_cannot_change_anything(): void
    {
        $fakultas = $this->users['fakultas'];

        $this->actingAs($fakultas)->get(route('overview'))->assertOk()
            ->assertSee('Nama MhsA')->assertSee('Nama MhsC')->assertDontSee('Nama MhsB');
        $this->actingAs($fakultas)->get(route('overview.student', $this->users['mhsA']))->assertOk();
        $this->actingAs($fakultas)->get(route('overview.student', $this->users['mhsB']))->assertNotFound();

        $this->actingAs($fakultas)->get(route('admin.participants.index'))->assertForbidden();
        $this->actingAs($fakultas)->get(route('admin.logbooks.create', $this->users['mhsA']))->assertForbidden();
    }

    public function test_group_leader_monitors_the_group_and_is_still_a_participant(): void
    {
        $kormasit = $this->users['kormasit'];

        $this->actingAs($kormasit)->get(route('dashboard'))->assertOk()->assertSee('Overview Kelompok');
        $this->actingAs($kormasit)->get(route('overview'))->assertOk()->assertSee('Nama MhsA')->assertDontSee('Nama MhsB');
        $this->actingAs($this->users['korcam'])->get(route('overview'))->assertOk()->assertSee('Nama MhsA')->assertSee('Nama MhsB')->assertDontSee('Nama MhsC');

        // Ketua tampil sebagai peserta di pencarian publik.
        $this->get(route('search.students'))->assertSee('Nama Kormasit');
    }

    public function test_only_group_leaders_can_file_a_group_logbook(): void
    {
        foreach (['kormasit' => true, 'mhsA' => false] as $user => $expected) {
            $this->checkIn($user);
            $this->actingAs($this->users[$user]);

            Livewire::test(LogbookForm::class)
                ->set('theme_new', 'Tema')->set('program_new', 'Program')->set('activity_type_new', 'Jenis')->set('location_new', 'Lokasi')
                ->set('progress_note', 'Kegiatan bersama.')
                ->set('is_group', true)
                ->call('save', 'submit')
                ->assertRedirect(route('logbooks.index'));

            $this->assertSame($expected, $this->users[$user]->logbooks()->sole()->is_group);
        }
    }

    /** @return array<string, array{string, int}> */
    public static function masterDataAccess(): array
    {
        return [
            'mahasiswa' => ['mhsA', 403],
            'ketua' => ['kormasit', 403],
            'dpl' => ['dpl', 403],
            'pimpinan' => ['pimpinan', 403],
            'fakultas (hanya baca)' => ['fakultas', 200],
            'admin' => ['admin', 200],
        ];
    }

    #[DataProvider('masterDataAccess')]
    public function test_master_data_is_managed_by_admin_and_read_only_for_fakultas(string $user, int $viewStatus): void
    {
        $theme = Theme::create(['name' => 'Tema Lama']);
        $canManage = $user === 'admin';

        $this->actingAs($this->users[$user])->get(route('admin.master-data.index'))->assertStatus($viewStatus);
        $this->actingAs($this->users[$user])
            ->put(route('admin.master-data.update', ['tema', $theme->id]), ['nama' => 'Tema Baru'])
            ->assertStatus($canManage ? 302 : 403);

        $this->assertSame($canManage ? 'Tema Baru' : 'Tema Lama', $theme->fresh()->name);
    }

    public function test_admin_merges_duplicate_master_data_and_cannot_delete_data_in_use(): void
    {
        $logbook = $this->logbook('mhsA', 'Catatan.');
        $duplicate = Program::create(['name' => 'Program Uji (dobel)']);
        $moved = $this->logbook('mhsB', 'Catatan lain.');
        $moved->update(['program_id' => $duplicate->id]);
        $admin = $this->users['admin'];

        $this->actingAs($admin)->delete(route('admin.master-data.destroy', ['program', $duplicate->id]))->assertSessionHas('flash_error');
        $this->assertModelExists($duplicate);

        $this->actingAs($admin)
            ->post(route('admin.master-data.merge', ['program', $duplicate->id]), ['tujuan' => $logbook->program_id])
            ->assertRedirect(route('admin.master-data.index', ['jenis' => 'program']));

        $this->assertModelMissing($duplicate);
        $this->assertSame($logbook->program_id, $moved->fresh()->program_id);

        $unused = Theme::create(['name' => 'Tema Tak Terpakai']);
        $this->actingAs($admin)->delete(route('admin.master-data.destroy', ['tema', $unused->id]))->assertSessionHas('flash_success');
        $this->assertModelMissing($unused);
    }

    public function test_admin_enters_a_logbook_on_behalf_of_a_student(): void
    {
        $admin = $this->users['admin'];
        $owner = $this->users['mhsA'];
        $this->actingAs($admin)->get(route('admin.logbooks.create', $owner))->assertOk()->assertSee('Anda mengisi atas nama Nama MhsA');

        // Tanpa presensi pemilik pada tanggal itu: tidak diblok, kondisi dicatat apa adanya.
        Livewire::test(LogbookForm::class, ['owner' => $owner])
            ->set('theme_new', 'Tema')->set('program_new', 'Program')->set('activity_type_new', 'Jenis')->set('location_new', 'Lokasi')
            ->set('progress_note', 'Diinput admin.')
            ->call('save', 'submit')
            ->assertRedirect(route('overview.student', $owner));

        $logbook = $owner->logbooks()->sole();
        $this->assertSame($admin->id, $logbook->entered_by);
        $this->assertSame(Logbook::HEALTH_UNKNOWN, $logbook->health_status);
        $this->assertSame(0, $admin->logbooks()->count());
    }

    public function test_admin_corrects_but_cannot_delete_a_students_logbook(): void
    {
        $admin = $this->users['admin'];
        $logbook = $this->logbook('mhsA', 'Catatan asli.');
        $this->actingAs($admin)->get(route('admin.logbooks.edit', $logbook))->assertOk();

        Livewire::test(LogbookForm::class, ['logbook' => $logbook])
            ->set('progress_note', 'Catatan dikoreksi admin.')
            ->call('save', 'submit')
            ->assertRedirect(route('overview.student', $this->users['mhsA']));

        $logbook->refresh();
        $this->assertSame('Catatan dikoreksi admin.', $logbook->progress_note);
        $this->assertSame($this->users['mhsA']->id, $logbook->student_id);
        $this->assertSame($admin->id, $logbook->entered_by);
        $this->assertSame('Sakit Ringan', $logbook->health_status);

        $approved = $this->logbook('mhsA', 'Sudah disetujui.', Logbook::STATUS_APPROVED);
        $this->actingAs($admin)->get(route('admin.logbooks.edit', $approved))->assertForbidden();
    }

    /** @return array<string, array{string}> */
    public static function nonAdmins(): array
    {
        return ['mahasiswa lain' => ['mhsB'], 'ketua' => ['kormasit'], 'dpl' => ['dpl'], 'fakultas' => ['fakultas'], 'pimpinan' => ['pimpinan']];
    }

    #[DataProvider('nonAdmins')]
    public function test_only_admin_can_enter_or_correct_logbooks_for_someone_else(string $user): void
    {
        $owner = $this->users['mhsA'];
        $logbook = $this->logbook('mhsA', 'Catatan asli.');
        $this->actingAs($this->users[$user]);

        $this->get(route('admin.logbooks.create', $owner))->assertForbidden();
        $this->get(route('admin.logbooks.edit', $logbook))->assertForbidden();
        Livewire::test(LogbookForm::class, ['owner' => $owner])->assertForbidden();
        // Bukan pemilik dan bukan admin: dijawab 404 supaya keberadaan logbook tidak terkonfirmasi.
        Livewire::test(LogbookForm::class, ['logbook' => $logbook])->assertNotFound();

        $this->assertSame('Catatan asli.', $logbook->fresh()->progress_note);
    }

    public function test_admin_cannot_enter_a_logbook_for_a_non_participant(): void
    {
        $this->actingAs($this->users['admin'])->get(route('admin.logbooks.create', $this->users['dpl']))->assertForbidden();
    }

    public function test_health_alert_reaches_individual_supervisors_but_not_pimpinan_or_the_student(): void
    {
        $recipients = Student::supervisorsFor($this->users['kormasit'])->pluck('email')->all();

        // Kormasit (Sub-unit A, Fakultas Biologi): korcam, dpl, dan admin mencakupnya.
        $this->assertContains('korcam@ugm.ac.id', $recipients);
        $this->assertContains('dpl@ugm.ac.id', $recipients);
        $this->assertContains('admin@ugm.ac.id', $recipients);
        $this->assertNotContains('kormasit@ugm.ac.id', $recipients);
        $this->assertNotContains('pimpinan@ugm.ac.id', $recipients);
        $this->assertNotContains('fakultas@ugm.ac.id', $recipients);
    }

    public function test_account_rules_for_the_new_roles(): void
    {
        $importer = app(ParticipantImporter::class);
        $row = fn (array $data) => $importer->normalize(['email' => 'baru@ugm.ac.id', 'nama' => 'Baru', ...$data]);

        $this->assertTrue($importer->validator($row(['peran' => 'fakultas']))->errors()->has('fakultas'));
        $this->assertTrue($importer->validator($row(['peran' => 'fakultas', 'fakultas' => 'Fakultas Teknik']))->passes());
        $this->assertTrue($importer->validator($row(['peran' => 'pimpinan']))->passes());
        $this->assertTrue($importer->validator($row(['peran' => 'kormasit']))->errors()->has('wilayah'));
        $this->assertTrue($importer->validator($row(['peran' => 'dpl']))->passes());
        $this->assertTrue($importer->validator($row(['peran' => 'admin_she']))->errors()->has('peran'));
    }
}
