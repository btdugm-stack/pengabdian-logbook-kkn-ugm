<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['services.google.allowed_domains' => ['ugm.ac.id', 'mail.ugm.ac.id']]);
    }

    private function mahasiswa(string $email): Student
    {
        $region = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $student = Student::create(['email' => $email, 'name' => 'Azmi', 'region_id' => $region->id]);
        $student->assignRole('mahasiswa');

        return $student;
    }

    private function googleReturns(string $email, bool $verified = true): void
    {
        $user = (new SocialiteUser)
            ->setRaw(['email_verified' => $verified])
            ->map(['id' => 'google-123', 'email' => $email, 'name' => 'Nama Google']);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_registered_student_with_mail_ugm_address_can_sign_in(): void
    {
        $student = $this->mahasiswa('azmi@mail.ugm.ac.id');
        $this->googleReturns('Azmi@mail.ugm.ac.id');

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($student);
        $this->assertSame('google-123', $student->fresh()->google_id);
    }

    /** @return array<string, array{string, bool, bool}> */
    public static function rejectedLogins(): array
    {
        return [
            'domain di luar UGM' => ['azmi@gmail.com', true, true],
            'domain mirip UGM' => ['azmi@evilugm.ac.id', true, true],
            'email belum diverifikasi Google' => ['azmi@mail.ugm.ac.id', false, true],
        ];
    }

    #[DataProvider('rejectedLogins')]
    public function test_rejected_google_login_returns_to_landing_with_message(string $email, bool $verified, bool $registered): void
    {
        if ($registered) {
            $this->mahasiswa($email);
        }
        $this->googleReturns($email, $verified);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('flash_error');

        $this->assertGuest();
    }

    public function test_expired_oauth_state_shows_message_instead_of_server_error(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('flash_error');

        $this->assertGuest();
    }

    public function test_cancelled_consent_screen_returns_to_landing(): void
    {
        $this->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('flash_error', 'Login Google dibatalkan.');
    }
}
