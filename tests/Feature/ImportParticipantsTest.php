<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportParticipantsTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $csvFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        array_map('unlink', array_filter($this->csvFiles, 'file_exists'));
        parent::tearDown();
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'peserta');
        file_put_contents($path, $content);
        $this->csvFiles[] = $path;

        return $path;
    }

    public function test_imports_accounts_with_roles_and_region_hierarchy(): void
    {
        $file = $this->csv(
            "email,nama,peran,wilayah,fakultas,prodi\n".
            "Azmi@Mail.UGM.ac.id,M. Azmi,mahasiswa,Kabupaten Sleman / Kecamatan Ngaglik / Desa Candirejo / Sub-unit 5A,Fakultas Teknik,Teknik Informatika\n".
            "dpl@ugm.ac.id,Dosen Pembimbing,dpl,Kabupaten Sleman,,\n".
            "lppm@ugm.ac.id,Admin LPPM,admin_lppm,,,\n"
        );

        $this->artisan('kkn:import-peserta', ['file' => $file])->assertSuccessful();

        $azmi = Student::where('email', 'azmi@mail.ugm.ac.id')->firstOrFail();
        $this->assertTrue($azmi->hasRole('mahasiswa'));
        $this->assertSame('Fakultas Teknik', $azmi->faculty);
        $this->assertSame('sub_unit', $azmi->region->level);
        $this->assertSame('Kabupaten Sleman / Kecamatan Ngaglik / Desa Candirejo / Sub-unit 5A', $azmi->region->fullPath());

        $dpl = Student::where('email', 'dpl@ugm.ac.id')->firstOrFail();
        $this->assertTrue($dpl->hasRole('dpl'));
        $this->assertSame(1, Region::where('name', 'Kabupaten Sleman')->count());
        // Cakupan DPL bukan dari kolom wilayah, tapi dari menu Penugasan DPL.
        $this->assertNull($dpl->region_id);
        $this->assertFalse($dpl->canSupervise($azmi));

        $admin = Student::where('email', 'lppm@ugm.ac.id')->firstOrFail();
        $this->assertTrue($admin->hasRole('admin_lppm'));
        $this->assertNull($admin->region_id);
    }

    public function test_invalid_rows_abort_the_whole_import(): void
    {
        $file = $this->csv(
            "email,nama,peran,wilayah\n".
            "valid@mail.ugm.ac.id,Valid,mahasiswa,Kabupaten Uji\n".
            "tanpa-wilayah@ugm.ac.id,Tanpa Wilayah,kormasit,\n".
            "peran-salah@ugm.ac.id,Peran Salah,rektor,Kabupaten Uji\n"
        );

        $this->artisan('kkn:import-peserta', ['file' => $file])
            ->expectsOutputToContain('Baris 3')
            ->expectsOutputToContain('Baris 4')
            ->assertFailed();

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('regions', 0);
    }

    public function test_reimport_updates_account_without_blanking_existing_biodata(): void
    {
        $student = Student::create(['email' => 'azmi@mail.ugm.ac.id', 'name' => 'Azmi', 'faculty' => 'Fakultas Teknik']);
        $student->assignRole('mahasiswa');

        // Ekspor Excel berlokal Indonesia memakai pemisah titik koma.
        $file = $this->csv("email;nama;peran;wilayah;fakultas;prodi\nazmi@mail.ugm.ac.id;Muhammad Azmi;mahasiswa;Kabupaten Bantul;;\n");

        $this->artisan('kkn:import-peserta', ['file' => $file])->assertSuccessful();

        $student->refresh();
        $this->assertDatabaseCount('students', 1);
        $this->assertSame('Muhammad Azmi', $student->name);
        $this->assertSame('Fakultas Teknik', $student->faculty);
        $this->assertSame('Kabupaten Bantul', $student->region->name);
    }

    public function test_dry_run_validates_without_saving(): void
    {
        $file = $this->csv("email,nama,peran,wilayah\nvalid@mail.ugm.ac.id,Valid,mahasiswa,Kabupaten Uji\n");

        $this->artisan('kkn:import-peserta', ['file' => $file, '--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseCount('students', 0);
    }
}
