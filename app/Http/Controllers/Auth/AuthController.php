<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('auth.login', [
            'demoStudents' => self::demoLoginAllowed()
                ? Student::orderBy('name')->get(['id', 'name', 'email'])
                : collect(),
        ]);
    }

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->with(['hd' => config('services.google.allowed_domain')])
            ->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        $email = $googleUser->getEmail();
        $domain = config('services.google.allowed_domain');

        if ($domain && ! str_ends_with($email, '@'.$domain)) {
            return redirect()->route('login')
                ->with('flash_error', "Akun Google harus menggunakan domain @{$domain}.");
        }

        $student = Student::where('email', $email)->first();

        if (! $student) {
            Log::warning('Login Google ditolak: email tidak terdaftar sebagai peserta KKN.', ['email' => $email]);

            return redirect()->route('login')
                ->with('flash_error', 'Email ini belum terdaftar sebagai peserta KKN. Hubungi admin LPPM.');
        }

        if (! $student->google_id) {
            $student->update(['google_id' => $googleUser->getId()]);
        }

        Auth::login($student, remember: true);
        request()->session()->regenerate();

        return redirect()->route('dashboard')->with('flash_success', 'Login SSO Google berhasil.');
    }

    public function demoLogin(Request $request): RedirectResponse
    {
        abort_unless(self::demoLoginAllowed(), 404);

        $data = $request->validate(['email' => 'required|email']);
        $student = Student::where('email', $data['email'])->first();

        if (! $student) {
            return redirect()->route('login')
                ->with('flash_error', 'Email tidak ditemukan pada data akun demo.');
        }

        Auth::login($student);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('flash_success', 'Login demo berhasil.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Demo login hanya boleh aktif di environment local/testing, terlepas
     * dari isi .env - mencegah jalur ini tertinggal aktif di produksi
     * (audit §Kritis). "testing" diizinkan supaya alurnya bisa diuji otomatis.
     */
    public static function demoLoginAllowed(): bool
    {
        return app()->environment(['local', 'testing']) && config('demo.login_enabled');
    }
}
