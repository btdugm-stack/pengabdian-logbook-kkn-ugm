<?php

namespace Tests\Feature;

use App\Models\RegistrationRequest;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const IDENTITY = ['email' => 'baru@mail.ugm.ac.id', 'name' => 'Nama Google', 'google_id' => 'google-123'];

    private const FORM = [
        'nama' => 'Mahasiswa Baru',
        'peran' => 'mahasiswa',
        'wilayah' => 'Kabupaten Sleman / Sub-unit 1A',
        'fakultas' => 'Fakultas Teknik',
        'prodi' => 'Teknik Informatika',
        'periode' => 'Periode 2 Tahun 2026',
        'tema' => 'Digitalisasi Desa',
        'catatan' => 'Unit Candirejo',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['services.google.allowed_domains' => ['ugm.ac.id', 'mail.ugm.ac.id']]);
    }

    private function account(string $email, string $role): Student
    {
        $account = Student::create(['email' => $email, 'name' => 'Akun '.$email]);
        $account->assignRole($role);

        return $account;
    }

    private function pendingRequest(): RegistrationRequest
    {
        return RegistrationRequest::create([
            'email' => self::IDENTITY['email'],
            'google_id' => self::IDENTITY['google_id'],
            'name' => 'Mahasiswa Baru',
            'requested_role' => 'mahasiswa',
            'region_path' => 'Kabupaten Sleman / Sub-unit 1A',
            'faculty' => 'Fakultas Teknik',
            'study_program' => 'Teknik Informatika',
            'kkn_period' => 'Periode 2 Tahun 2026',
            'kkn_theme' => 'Digitalisasi Desa',
            'status' => RegistrationRequest::STATUS_PENDING,
        ]);
    }

    private function googleReturns(string $email): void
    {
        $user = (new SocialiteUser)
            ->setRaw(['email_verified' => true])
            ->map(['id' => self::IDENTITY['google_id'], 'email' => $email, 'name' => self::IDENTITY['name']]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_unregistered_ugm_account_is_sent_to_the_registration_form(): void
    {
        $this->googleReturns('Baru@mail.ugm.ac.id');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('register.create'))
            ->assertSessionHas(RegistrationRequest::SESSION_KEY, self::IDENTITY);

        $this->assertGuest();
        $this->get(route('register.create'))->assertOk()->assertSee('baru@mail.ugm.ac.id')->assertSee('Kirim Pendaftaran');
    }

    public function test_registration_form_without_google_identity_only_offers_google_sign_in(): void
    {
        $this->get(route('register.create'))
            ->assertOk()
            ->assertSee('Lanjutkan dengan Akun UGM')
            ->assertDontSee('Kirim Pendaftaran');

        $this->post(route('register.store'), self::FORM)->assertRedirect(route('register.create'));

        $this->assertDatabaseCount('registration_requests', 0);
    }

    public function test_submitting_the_form_creates_a_pending_request_for_the_google_email_only(): void
    {
        $this->withSession([RegistrationRequest::SESSION_KEY => self::IDENTITY])
            ->post(route('register.store'), [...self::FORM, 'email' => 'orang-lain@ugm.ac.id'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('flash_success')
            ->assertSessionMissing(RegistrationRequest::SESSION_KEY);

        $this->assertDatabaseHas('registration_requests', [
            'email' => 'baru@mail.ugm.ac.id',
            'google_id' => 'google-123',
            'name' => 'Mahasiswa Baru',
            'requested_role' => 'mahasiswa',
            'region_path' => 'Kabupaten Sleman / Sub-unit 1A',
            'study_program' => 'Teknik Informatika',
            'kkn_period' => 'Periode 2 Tahun 2026',
            'kkn_theme' => 'Digitalisasi Desa',
            'note' => 'Unit Candirejo',
            'status' => RegistrationRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseCount('registration_requests', 1);
        $this->assertDatabaseCount('students', 0);
    }

    /** @return array<string, array{array<string, string>, string}> */
    public static function invalidForms(): array
    {
        return [
            'peran admin' => [['peran' => 'admin_lppm', 'wilayah' => ''], 'peran'],
            'peran super admin' => [['peran' => 'super_admin', 'wilayah' => ''], 'peran'],
            'tanpa wilayah' => [['wilayah' => ''], 'wilayah'],
            'dpl tanpa wilayah' => [['peran' => 'dpl', 'wilayah' => ''], 'wilayah'],
            'tanpa fakultas' => [['fakultas' => ''], 'fakultas'],
            'tanpa program studi' => [['prodi' => ''], 'prodi'],
            'tanpa periode' => [['periode' => ' '], 'periode'],
            'tanpa tema' => [['tema' => ''], 'tema'],
            'tanpa nama' => [['nama' => ''], 'nama'],
        ];
    }

    /** @param  array<string, string>  $overrides */
    #[DataProvider('invalidForms')]
    public function test_invalid_registration_is_rejected(array $overrides, string $field): void
    {
        $this->withSession([RegistrationRequest::SESSION_KEY => self::IDENTITY])
            ->post(route('register.store'), [...self::FORM, ...$overrides])
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('registration_requests', 0);
    }

    public function test_new_faculty_and_programme_are_offered_to_the_next_registrant(): void
    {
        $this->withSession([RegistrationRequest::SESSION_KEY => self::IDENTITY])
            ->post(route('register.store'), [...self::FORM, 'fakultas' => 'Sekolah Baru', 'prodi' => 'Prodi Baru', 'periode' => 'Periode 4 Tahun 2026'])
            ->assertRedirect(route('login'));

        $this->withSession([RegistrationRequest::SESSION_KEY => ['email' => 'kedua@mail.ugm.ac.id', 'name' => 'Kedua', 'google_id' => 'google-456']])
            ->get(route('register.create'))
            ->assertOk()
            ->assertSee('Sekolah Baru')
            ->assertSee('Prodi Baru')
            ->assertSee('Periode 4 Tahun 2026')
            // Daftar fakultas bawaan tetap ada.
            ->assertSee('Fakultas Kedokteran Gigi');
    }

    public function test_typed_value_matching_an_existing_option_keeps_the_existing_spelling(): void
    {
        $this->withSession([RegistrationRequest::SESSION_KEY => self::IDENTITY])
            ->post(route('register.store'), [...self::FORM, 'fakultas' => '  fakultas   TEKNIK '])
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('registration_requests', ['email' => 'baru@mail.ugm.ac.id', 'faculty' => 'Fakultas Teknik']);
    }

    public function test_pending_registrant_is_told_to_wait_instead_of_registering_again(): void
    {
        $this->pendingRequest();
        $this->googleReturns('baru@mail.ugm.ac.id');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('flash_error')
            ->assertSessionMissing(RegistrationRequest::SESSION_KEY);

        $this->assertGuest();
    }

    public function test_admin_approves_with_the_role_and_region_they_choose(): void
    {
        $admin = $this->account('lppm@ugm.ac.id', 'admin_lppm');
        $request = $this->pendingRequest();

        $this->actingAs($admin)->get(route('admin.registrations.index'))->assertOk()->assertSee('baru@mail.ugm.ac.id');

        $this->actingAs($admin)
            ->post(route('admin.registrations.approve', $request), ['peran' => 'kormasit', 'wilayah' => 'Kabupaten Bantul / Sub-unit 3A'])
            ->assertRedirect(route('admin.registrations.index'));

        $student = Student::where('email', 'baru@mail.ugm.ac.id')->firstOrFail();
        $this->assertTrue($student->hasRole('kormasit'));
        $this->assertSame('Kabupaten Bantul / Sub-unit 3A', $student->region->fullPath());
        $this->assertSame('Fakultas Teknik', $student->faculty);
        $this->assertSame('google-123', $student->google_id);
        // Data KKN dari form pendaftaran menjadi data awal akun.
        $this->assertSame('Teknik Informatika', $student->study_program);
        $this->assertSame('Periode 2 Tahun 2026', $student->kkn_period);
        $this->assertSame('Digitalisasi Desa', $student->kkn_theme);

        $request->refresh();
        $this->assertSame(RegistrationRequest::STATUS_APPROVED, $request->status);
        $this->assertSame($admin->id, $request->reviewed_by);
    }

    public function test_approved_registrant_can_sign_in_with_google(): void
    {
        $request = $this->pendingRequest();
        $this->actingAs($this->account('lppm@ugm.ac.id', 'admin_lppm'))
            ->post(route('admin.registrations.approve', $request), ['peran' => 'mahasiswa', 'wilayah' => $request->region_path]);
        auth()->logout();
        $this->googleReturns('baru@mail.ugm.ac.id');

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs(Student::where('email', 'baru@mail.ugm.ac.id')->firstOrFail());
    }

    public function test_admin_lppm_cannot_approve_a_request_as_super_admin(): void
    {
        $request = $this->pendingRequest();

        $this->actingAs($this->account('lppm@ugm.ac.id', 'admin_lppm'))
            ->post(route('admin.registrations.approve', $request), ['peran' => 'super_admin', 'wilayah' => ''])
            ->assertSessionHasErrorsIn('request_'.$request->id, 'peran');

        $this->assertDatabaseCount('students', 1);
        $this->assertTrue($request->fresh()->isPending());
    }

    public function test_rejected_registrant_sees_the_reason_and_can_resubmit(): void
    {
        $request = $this->pendingRequest();

        $this->actingAs($this->account('lppm@ugm.ac.id', 'admin_lppm'))
            ->post(route('admin.registrations.reject', $request), ['alasan' => 'Bukan peserta periode ini.'])
            ->assertRedirect(route('admin.registrations.index'));
        auth()->logout();

        $this->assertSame(RegistrationRequest::STATUS_REJECTED, $request->fresh()->status);
        $this->assertDatabaseCount('students', 1);

        $this->googleReturns('baru@mail.ugm.ac.id');
        $this->get(route('auth.google.callback'))->assertRedirect(route('register.create'));
        $this->get(route('register.create'))->assertOk()->assertSee('Bukan peserta periode ini.');

        $this->post(route('register.store'), self::FORM)->assertRedirect(route('login'));

        $request->refresh();
        $this->assertTrue($request->isPending());
        $this->assertNull($request->rejection_reason);
        $this->assertDatabaseCount('registration_requests', 1);
    }

    public function test_a_processed_request_cannot_be_decided_twice(): void
    {
        $admin = $this->account('lppm@ugm.ac.id', 'admin_lppm');
        $request = $this->pendingRequest();
        $request->update(['status' => RegistrationRequest::STATUS_REJECTED]);

        $this->actingAs($admin)
            ->post(route('admin.registrations.approve', $request), ['peran' => 'mahasiswa', 'wilayah' => 'Kabupaten Uji'])
            ->assertConflict();

        $this->assertDatabaseMissing('students', ['email' => 'baru@mail.ugm.ac.id']);
    }

    /** @return array<string, array{string}> */
    public static function nonManagerRoles(): array
    {
        return [
            'mahasiswa' => ['mahasiswa'],
            'dpl' => ['dpl'],
            'fakultas' => ['fakultas'],
            'pimpinan' => ['pimpinan'],
        ];
    }

    #[DataProvider('nonManagerRoles')]
    public function test_only_account_managers_can_review_registrations(string $role): void
    {
        $user = $this->account("{$role}@ugm.ac.id", $role);
        $request = $this->pendingRequest();

        $this->actingAs($user)->get(route('admin.registrations.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.registrations.approve', $request), ['peran' => 'mahasiswa', 'wilayah' => 'Kabupaten Uji'])->assertForbidden();
        $this->actingAs($user)->post(route('admin.registrations.reject', $request))->assertForbidden();

        $this->assertTrue($request->fresh()->isPending());
    }
}
