<?php

namespace Tests\Feature;

use App\Models\Student;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLoginAccessCodeTest extends TestCase
{
    use RefreshDatabase;

    private const CODE = 'kode-rahasia-uji';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoAccountSeeder::class);
        $this->app->detectEnvironment(fn () => 'production');
        // Di luar environment testing, CSRF aktif; yang diuji di sini kode akses, bukan token form.
        $this->withoutMiddleware(PreventRequestForgery::class);
        config(['demo.login_enabled' => true, 'demo.access_code' => self::CODE]);
    }

    private function realAccount(): Student
    {
        $account = Student::create(['email' => 'dosen@ugm.ac.id', 'name' => 'Dosen Sungguhan']);
        $account->assignRole('admin_lppm');

        return $account;
    }

    public function test_correct_code_signs_in_a_demo_account_in_production(): void
    {
        $this->post(route('demo-login'), ['email' => 'dpl.demo@demo.kkn', 'kode' => self::CODE])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs(Student::where('email', 'dpl.demo@demo.kkn')->firstOrFail());
    }

    public function test_wrong_or_missing_code_does_not_sign_in(): void
    {
        $this->post(route('demo-login'), ['email' => 'dpl.demo@demo.kkn', 'kode' => 'salah'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('flash_error');
        $this->post(route('demo-login'), ['email' => 'dpl.demo@demo.kkn'])
            ->assertSessionHasErrors('kode');

        $this->assertGuest();
    }

    public function test_correct_code_cannot_sign_in_a_real_account(): void
    {
        $real = $this->realAccount();

        $this->post(route('demo-login'), ['email' => $real->email, 'kode' => self::CODE])
            ->assertRedirect(route('login'))
            ->assertSessionHas('flash_error');

        $this->assertGuest();
    }

    public function test_landing_lists_demo_accounts_only(): void
    {
        $this->realAccount();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Super Admin Demo')
            ->assertSee('Kode akses')
            ->assertDontSee('Dosen Sungguhan')
            ->assertDontSee('dosen@ugm.ac.id');
    }

    public function test_demo_login_stays_off_in_production_without_a_code(): void
    {
        config(['demo.access_code' => null]);

        $this->post(route('demo-login'), ['email' => 'dpl.demo@demo.kkn', 'kode' => self::CODE])->assertNotFound();
        $this->get(route('login'))->assertOk()->assertDontSee('Coba dengan akun demo');

        $this->assertGuest();
    }
}
