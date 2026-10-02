<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RegistrationRequest;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class AuthController extends Controller
{
    /**
     * Landing page: tiga jalur masuk (Google SSO, akun demo, mode tamu).
     * Dipakai untuk `/` dan `/login` - yang sudah login langsung ke dashboard.
     */
    public function landing(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.landing', [
            'demoStudents' => self::demoLoginAllowed() ? $this->demoLoginAccounts() : collect(),
            'demoNeedsCode' => self::demoAccessCode() !== null,
        ]);
    }

    /**
     * Mode tamu: HANYA penanda sesi supaya UI bisa menampilkan "Mode Tamu".
     * Tidak membuat akun, tidak menyentuh guard/RBAC - akses yang didapat
     * persis sama dengan akses publik yang memang sudah terbuka (search
     * mahasiswa/logbook & peta publik, semuanya sudah disaring dari PII).
     */
    public function enterGuest(Request $request): RedirectResponse
    {
        $request->session()->put('guest_mode', true);

        return redirect()->route('public.home');
    }

    public function redirectToGoogle(): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return $this->loginFailed('Login Google belum dikonfigurasi di server ini. Hubungi admin.');
        }

        $domains = config('services.google.allowed_domains');
        $driver = Socialite::driver('google');

        // `hd` hanya petunjuk untuk pemilih akun Google (bisa diakali), domain
        // tetap diverifikasi ulang di callback. Untuk >1 domain, '*' membatasi
        // pilihan ke akun Google Workspace saja.
        if ($domains !== []) {
            $driver->with(['hd' => count($domains) === 1 ? $domains[0] : '*']);
        }

        return $driver->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        // Pengguna menekan "Batal" di layar izin Google.
        if ($request->filled('error')) {
            return $this->loginFailed('Login Google dibatalkan.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            // State tidak cocok: tab login lama dibuka ulang atau sesi sudah habis.
            return $this->loginFailed('Sesi login kedaluwarsa. Silakan klik "Masuk dengan Akun UGM" lagi.');
        } catch (Throwable $e) {
            report($e);

            return $this->loginFailed('Login Google gagal diproses. Silakan coba lagi beberapa saat lagi.');
        }

        $email = strtolower((string) $googleUser->getEmail());

        if (! ($googleUser->getRaw()['email_verified'] ?? false) || ! self::emailDomainAllowed($email)) {
            $domains = collect(config('services.google.allowed_domains'))->map(fn ($d) => "@{$d}")->implode(' atau ');

            return $this->loginFailed("Gunakan akun Google UGM ({$domains}).");
        }

        $student = Student::where('email', $email)->first();

        if (! $student) {
            return $this->continueAsRegistrant($request, $email, (string) $googleUser->getName(), (string) $googleUser->getId());
        }

        if (! $student->google_id) {
            $student->update(['google_id' => $googleUser->getId()]);
        }

        Auth::login($student, remember: true);
        $request->session()->regenerate();
        $request->session()->forget('guest_mode');

        return redirect()->intended(route('dashboard'))
            ->with('flash_success', "Selamat datang, {$student->name}.");
    }

    /**
     * Email UGM yang sah tapi belum punya akun: arahkan ke form pendaftaran
     * mandiri dengan identitas Google yang sudah terverifikasi, kecuali
     * permintaannya masih menunggu keputusan admin.
     */
    private function continueAsRegistrant(Request $request, string $email, string $name, string $googleId): RedirectResponse
    {
        $pending = RegistrationRequest::where('email', $email)
            ->where('status', RegistrationRequest::STATUS_PENDING)
            ->exists();

        if ($pending) {
            return $this->loginFailed('Pendaftaran Anda masih menunggu persetujuan admin. Coba masuk lagi setelah disetujui.');
        }

        $request->session()->put(RegistrationRequest::SESSION_KEY, [
            'email' => $email,
            'name' => $name,
            'google_id' => $googleId,
        ]);

        return redirect()->route('register.create');
    }

    public function demoLogin(Request $request): RedirectResponse
    {
        abort_unless(self::demoLoginAllowed(), 404);

        $code = self::demoAccessCode();
        $data = $request->validate([
            'email' => 'required|email',
            'kode' => [$code !== null ? 'required' : 'nullable', 'string', 'max:100'],
        ]);

        if ($code !== null && ! hash_equals($code, (string) ($data['kode'] ?? ''))) {
            return $this->loginFailed('Kode akses demo salah.');
        }

        $student = Student::where('email', $data['email'])->first();

        // Dengan kode akses, pesan disamakan supaya email akun sungguhan tidak bisa ditebak dari sini.
        if (! $student || ($code !== null && ! $student->isDemoAccount())) {
            return $this->loginFailed('Email tidak ditemukan pada data akun demo.');
        }

        Auth::login($student);
        $request->session()->regenerate();
        $request->session()->forget('guest_mode');

        return redirect()->route('dashboard')->with('flash_success', 'Login demo berhasil.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        // Tombol "Keluar" yang sama juga dipakai untuk mengakhiri mode tamu.
        $request->session()->forget('guest_mode');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Tanpa kode akses, demo login hanya boleh aktif di environment
     * local/testing terlepas dari isi .env (audit §Kritis). Dengan
     * DEMO_LOGIN_CODE terisi, boleh aktif di mana pun karena dijaga kode dan
     * dibatasi ke akun demo - lihat demoLogin().
     */
    public static function demoLoginAllowed(): bool
    {
        return config('demo.login_enabled')
            && (app()->environment(['local', 'testing']) || self::demoAccessCode() !== null);
    }

    /** Kode akses yang dikonfigurasi, atau null bila tidak dipakai. */
    private static function demoAccessCode(): ?string
    {
        $code = trim((string) config('demo.access_code'));

        return $code !== '' ? $code : null;
    }

    /**
     * Akun yang ditawarkan di form login demo: hanya akun demo bila kode akses
     * dipakai, semua akun pada mode pengembangan lama.
     *
     * @return Collection<int, Student>
     */
    private function demoLoginAccounts(): Collection
    {
        return Student::with('roles')
            ->when(self::demoAccessCode() !== null, fn ($query) => $query->where('email', 'like', '%@'.Student::DEMO_EMAIL_DOMAIN))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /** Domain email harus persis salah satu domain yang diizinkan (bukan sekadar akhiran). */
    private static function emailDomainAllowed(string $email): bool
    {
        $domains = config('services.google.allowed_domains');

        return $domains === [] || in_array(Str::after($email, '@'), $domains, true);
    }

    private function loginFailed(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('flash_error', $message);
    }
}
