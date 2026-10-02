<?php

namespace Tests\Feature;

use App\Models\DailyAttendance;
use App\Models\Region;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ParticipantManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function account(string $email, string $role, ?Region $region = null): Student
    {
        $account = Student::create(['email' => $email, 'name' => 'Akun '.$email, 'region_id' => $region?->id]);
        $account->assignRole($role);

        return $account;
    }

    private function admin(): Student
    {
        return $this->account('lppm@ugm.ac.id', 'admin_lppm');
    }

    /** @return array<string, array{string}> */
    public static function nonManagerRoles(): array
    {
        return [
            'mahasiswa' => ['mahasiswa'],
            'kormasit' => ['kormasit'],
            'dpl' => ['dpl'],
            'fakultas' => ['fakultas'],
            'pimpinan' => ['pimpinan'],
        ];
    }

    #[DataProvider('nonManagerRoles')]
    public function test_only_admin_lppm_can_open_participant_management(string $role): void
    {
        $user = $this->account("{$role}@ugm.ac.id", $role, Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']));

        $this->actingAs($user)->get(route('admin.participants.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.participants.store'), [])->assertForbidden();
    }

    public function test_index_filters_accounts_by_role(): void
    {
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        Student::create(['email' => 'mhs@mail.ugm.ac.id', 'name' => 'Mahasiswa Satu', 'region_id' => $region->id])->assignRole('mahasiswa');
        Student::create(['email' => 'dosen@ugm.ac.id', 'name' => 'Dosen Satu', 'region_id' => $region->id])->assignRole('dpl');

        $this->actingAs($this->admin())->get(route('admin.participants.index', ['peran' => 'dpl']))
            ->assertOk()
            ->assertSee('Dosen Satu')
            ->assertDontSee('Mahasiswa Satu');
    }

    public function test_admin_registers_a_mahasiswa_and_creates_the_region_path(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.participants.store'), [
                'email' => ' Baru@Mail.UGM.ac.id ',
                'nama' => 'Mahasiswa Baru',
                'peran' => 'mahasiswa',
                'wilayah' => 'Kabupaten Sleman / Sub-unit 1A',
                'fakultas' => 'Fakultas Teknik',
            ])
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('flash_success');

        $student = Student::where('email', 'baru@mail.ugm.ac.id')->firstOrFail();
        $this->assertTrue($student->hasRole('mahasiswa'));
        $this->assertSame('Fakultas Teknik', $student->faculty);
        $this->assertSame('Kabupaten Sleman / Sub-unit 1A', $student->region->fullPath());
    }

    public function test_registration_rejects_duplicate_email_and_missing_region(): void
    {
        $admin = $this->admin();
        $this->account('ada@mail.ugm.ac.id', 'mahasiswa', Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']));

        $this->actingAs($admin)
            ->from(route('admin.participants.create'))
            ->post(route('admin.participants.store'), [
                'email' => 'ADA@mail.ugm.ac.id',
                'nama' => 'Duplikat',
                'peran' => 'kormasit',
                'wilayah' => '',
            ])
            ->assertRedirect(route('admin.participants.create'))
            ->assertSessionHasErrors(['email', 'wilayah']);

        $this->assertDatabaseCount('students', 2);
    }

    public function test_admin_changes_role_region_and_can_clear_biodata(): void
    {
        $student = $this->account('mhs@mail.ugm.ac.id', 'mahasiswa', Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']));
        $student->update(['faculty' => 'Fakultas Lama']);

        $this->actingAs($this->admin())
            ->put(route('admin.participants.update', $student), [
                'email' => 'dicoba-diganti@mail.ugm.ac.id',
                'nama' => 'Kormasit Baru',
                'peran' => 'kormasit',
                'wilayah' => 'Kabupaten Bantul / Sub-unit 3A',
                'fakultas' => '',
            ])
            ->assertRedirect(route('admin.participants.index'));

        $student->refresh();
        $this->assertSame('mhs@mail.ugm.ac.id', $student->email);
        $this->assertSame('Kormasit Baru', $student->name);
        $this->assertTrue($student->hasRole('kormasit'));
        $this->assertFalse($student->hasRole('mahasiswa'));
        $this->assertSame('Kabupaten Bantul / Sub-unit 3A', $student->region->fullPath());
        $this->assertNull($student->faculty);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.participants.edit', $admin))
            ->put(route('admin.participants.update', $admin), [
                'nama' => 'Admin LPPM',
                'peran' => 'mahasiswa',
                'wilayah' => 'Kabupaten Uji',
            ])
            ->assertSessionHasErrors('peran');

        $this->assertTrue($admin->fresh()->hasRole('admin_lppm'));
    }

    public function test_csv_upload_imports_accounts(): void
    {
        $file = UploadedFile::fake()->createWithContent('peserta.csv',
            "email,nama,peran,wilayah,fakultas,prodi\n".
            "a@mail.ugm.ac.id,Mahasiswa A,mahasiswa,Kabupaten Sleman / Sub-unit 1A,Fakultas Teknik,\n".
            "dosen@ugm.ac.id,Dosen,dpl,Kabupaten Sleman,,\n"
        );

        $this->actingAs($this->admin())
            ->post(route('admin.participants.import.store'), ['file' => $file])
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('flash_success', 'Impor selesai: 2 akun baru, 0 akun diperbarui.');

        $this->assertTrue(Student::where('email', 'dosen@ugm.ac.id')->firstOrFail()->hasRole('dpl'));
        $this->assertSame(1, Region::where('name', 'Kabupaten Sleman')->count());
    }

    public function test_dry_run_upload_validates_without_saving(): void
    {
        $file = UploadedFile::fake()->createWithContent('peserta.csv', "email,nama,peran,wilayah\na@mail.ugm.ac.id,Mahasiswa A,mahasiswa,Kabupaten Uji\n");

        $this->actingAs($this->admin())
            ->from(route('admin.participants.import'))
            ->post(route('admin.participants.import.store'), ['file' => $file, 'dry_run' => '1'])
            ->assertRedirect(route('admin.participants.import'))
            ->assertSessionHas('flash_success');

        $this->assertDatabaseMissing('students', ['email' => 'a@mail.ugm.ac.id']);
    }

    public function test_invalid_csv_upload_saves_nothing_and_reports_rows(): void
    {
        $file = UploadedFile::fake()->createWithContent('peserta.csv',
            "email,nama,peran,wilayah\n".
            "a@mail.ugm.ac.id,Mahasiswa A,mahasiswa,Kabupaten Uji\n".
            "bukan-email,Salah,rektor,\n"
        );

        $this->actingAs($this->admin())
            ->from(route('admin.participants.import'))
            ->post(route('admin.participants.import.store'), ['file' => $file])
            ->assertRedirect(route('admin.participants.import'))
            ->assertSessionHas('import_errors', fn (array $errors) => array_keys($errors) === [3]);

        $this->assertDatabaseMissing('students', ['email' => 'a@mail.ugm.ac.id']);
    }

    public function test_admin_can_open_every_management_page(): void
    {
        $admin = $this->admin();
        $student = $this->account('mhs@mail.ugm.ac.id', 'mahasiswa', Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']));

        $this->actingAs($admin)->get(route('admin.participants.create'))->assertOk()->assertSee('Kabupaten Uji');
        $this->actingAs($admin)->get(route('admin.participants.edit', $student))->assertOk()->assertSee('mhs@mail.ugm.ac.id');
        $this->actingAs($admin)->get(route('admin.participants.import'))->assertOk()->assertSee('admin_lppm');
        $this->actingAs($admin)->get(route('admin.participants.import.template'))
            ->assertOk()
            ->assertDownload('template-peserta-kkn.csv');
    }

    public function test_admin_lppm_cannot_grant_the_super_admin_role(): void
    {
        $admin = $this->admin();
        $student = $this->account('mhs@mail.ugm.ac.id', 'mahasiswa', Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']));

        $this->actingAs($admin)
            ->post(route('admin.participants.store'), ['email' => 'baru@ugm.ac.id', 'nama' => 'Baru', 'peran' => 'super_admin'])
            ->assertSessionHasErrors('peran');
        $this->actingAs($admin)
            ->put(route('admin.participants.update', $student), ['nama' => 'Naik Peran', 'peran' => 'super_admin'])
            ->assertSessionHasErrors('peran');

        $this->assertDatabaseMissing('students', ['email' => 'baru@ugm.ac.id']);
        $this->assertTrue($student->fresh()->hasRole('mahasiswa'));
    }

    public function test_admin_lppm_cannot_edit_a_super_admin_account(): void
    {
        $superAdmin = $this->account('super@ugm.ac.id', 'super_admin');
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.participants.edit', $superAdmin))->assertForbidden();
        $this->actingAs($admin)
            ->put(route('admin.participants.update', $superAdmin), ['nama' => 'Diturunkan', 'peran' => 'mahasiswa', 'wilayah' => 'Kabupaten Uji'])
            ->assertForbidden();

        $this->assertTrue($superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_admin_lppm_csv_cannot_create_or_overwrite_super_admins(): void
    {
        $superAdmin = $this->account('super@ugm.ac.id', 'super_admin');
        $file = UploadedFile::fake()->createWithContent('peserta.csv',
            "email,nama,peran,wilayah\n".
            "naik@ugm.ac.id,Naik Peran,super_admin,\n".
            "super@ugm.ac.id,Diturunkan,mahasiswa,Kabupaten Uji\n"
        );

        $this->actingAs($this->admin())
            ->from(route('admin.participants.import'))
            ->post(route('admin.participants.import.store'), ['file' => $file])
            ->assertRedirect(route('admin.participants.import'))
            ->assertSessionHas('import_errors', fn (array $errors) => array_keys($errors) === [2, 3]);

        $this->assertDatabaseMissing('students', ['email' => 'naik@ugm.ac.id']);
        $this->assertTrue($superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_super_admin_manages_accounts_and_can_grant_super_admin(): void
    {
        $superAdmin = $this->account('super@ugm.ac.id', 'super_admin');
        $other = $this->account('lain@ugm.ac.id', 'super_admin');

        $this->actingAs($superAdmin)
            ->post(route('admin.participants.store'), ['email' => 'baru@ugm.ac.id', 'nama' => 'Super Baru', 'peran' => 'super_admin'])
            ->assertRedirect(route('admin.participants.index'));
        $this->actingAs($superAdmin)
            ->put(route('admin.participants.update', $other), ['nama' => 'Jadi Admin LPPM', 'peran' => 'admin_lppm'])
            ->assertRedirect(route('admin.participants.index'));

        $created = Student::where('email', 'baru@ugm.ac.id')->firstOrFail();
        $this->assertTrue($created->hasRole('super_admin'));
        $this->assertNull($created->region_id);
        $this->assertTrue($other->fresh()->hasRole('admin_lppm'));
    }

    public function test_super_admin_deletes_an_account_with_its_data_after_typing_the_email(): void
    {
        $superAdmin = $this->account('super@ugm.ac.id', 'super_admin');
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $student = $this->account('mhs@mail.ugm.ac.id', 'mahasiswa', $region);
        $dpl = $this->account('dpl@ugm.ac.id', 'dpl');
        $dpl->advisees()->attach($student);
        DailyAttendance::create(['student_id' => $student->id, 'attendance_date' => today(), 'condition' => 'Sehat']);

        // Tanpa mengetik email yang benar, tidak ada yang terhapus.
        $this->actingAs($superAdmin)
            ->delete(route('admin.participants.destroy', $student), ['konfirmasi_email' => 'salah@mail.ugm.ac.id'])
            ->assertSessionHasErrorsIn('deletion', 'konfirmasi_email');
        $this->assertModelExists($student);

        $this->actingAs($superAdmin)->get(route('admin.participants.edit', $student))->assertOk()->assertSee('Hapus Akun Permanen');
        $this->actingAs($superAdmin)
            ->delete(route('admin.participants.destroy', $student), ['konfirmasi_email' => 'mhs@mail.ugm.ac.id'])
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('flash_success');

        $this->assertModelMissing($student);
        $this->assertDatabaseCount('daily_attendances', 0);
        $this->assertDatabaseCount('dpl_student', 0);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $student->id]);
        $this->assertModelExists($dpl);
    }

    public function test_super_admin_cannot_delete_their_own_account(): void
    {
        $superAdmin = $this->account('super@ugm.ac.id', 'super_admin');

        $this->actingAs($superAdmin)
            ->delete(route('admin.participants.destroy', $superAdmin), ['konfirmasi_email' => 'super@ugm.ac.id'])
            ->assertSessionHas('flash_error');

        $this->assertModelExists($superAdmin);
    }

    public function test_admin_lppm_cannot_delete_accounts(): void
    {
        $admin = $this->admin();
        $student = $this->account('mhs@mail.ugm.ac.id', 'mahasiswa', Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']));

        $this->actingAs($admin)
            ->delete(route('admin.participants.destroy', $student), ['konfirmasi_email' => 'mhs@mail.ugm.ac.id'])
            ->assertForbidden();
        $this->actingAs($admin)->get(route('admin.participants.edit', $student))->assertOk()->assertDontSee('Hapus Akun Permanen');
        $this->actingAs($admin)->get(route('admin.participants.index'))->assertOk()->assertDontSee('#hapus-akun', false);

        $this->assertModelExists($student);
    }

    public function test_admin_sees_the_management_menu(): void
    {
        $this->actingAs($this->admin())->get(route('overview'))
            ->assertOk()
            ->assertSee('Kelola Peserta');
    }
}
