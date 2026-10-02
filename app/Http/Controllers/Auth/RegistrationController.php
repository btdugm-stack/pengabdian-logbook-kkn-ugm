<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\RegistrationRequest;
use App\Models\Student;
use App\Support\ParticipantImporter;
use App\Support\ProfileOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pendaftaran mandiri. Identitas (email, nama, google_id) TIDAK diketik
 * pendaftar: diambil dari login Google yang sudah diverifikasi di
 * AuthController::handleGoogleCallback() dan dititipkan di session, sehingga
 * orang tidak bisa mendaftarkan email milik orang lain.
 */
class RegistrationController extends Controller
{
    public function __construct(private ParticipantImporter $importer) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $identity = $request->session()->get(RegistrationRequest::SESSION_KEY);

        return view('auth.register', [
            'identity' => $identity,
            'previous' => $identity ? RegistrationRequest::where('email', $identity['email'])->first() : null,
            'regionPaths' => $identity ? Region::paths() : collect(),
            'faculties' => $identity ? ProfileOptions::faculties() : collect(),
            'studyPrograms' => $identity ? ProfileOptions::studyPrograms() : collect(),
            'periods' => $identity ? ProfileOptions::periods() : collect(),
            'themes' => $identity ? ProfileOptions::themes() : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $identity = $request->session()->get(RegistrationRequest::SESSION_KEY);

        if (! $identity) {
            return redirect()->route('register.create')
                ->with('flash_error', 'Sesi pendaftaran berakhir. Masuk dengan akun UGM lagi untuk melanjutkan.');
        }

        if (Student::where('email', $identity['email'])->exists()) {
            $request->session()->forget(RegistrationRequest::SESSION_KEY);

            return redirect()->route('login')
                ->with('flash_success', 'Akun Anda sudah terdaftar. Silakan masuk dengan akun UGM.');
        }

        // Nilai yang diketik disamakan ejaannya dengan pilihan yang sudah ada,
        // supaya "fakultas teknik" tidak menjadi pilihan baru di samping "Fakultas Teknik".
        $row = $this->importer->normalize([
            ...$request->all(),
            'email' => $identity['email'],
            'fakultas' => ProfileOptions::canonical($request->input('fakultas'), ProfileOptions::faculties()),
            'prodi' => ProfileOptions::canonical($request->input('prodi'), ProfileOptions::studyPrograms()),
            'periode' => ProfileOptions::canonical($request->input('periode'), ProfileOptions::periods()),
            'tema' => ProfileOptions::canonical($request->input('tema'), ProfileOptions::themes()),
        ]);

        // setData() mengembalikan rules ke kondisi awal, jadi harus dipanggil
        // sebelum addRules() - kalau terbalik, batasan di bawah hilang.
        $validator = $this->importer->validator($row);
        $validator->setData([...$row, 'catatan' => trim((string) $request->input('catatan'))]);
        $validator->addRules([
            'peran' => [Rule::in(Student::SELF_REGISTRABLE_ROLES)],
            // Di form pendaftaran semua data KKN wajib: menjadi data awal akun begitu disetujui.
            'wilayah' => ['required'],
            'fakultas' => ['required'],
            'prodi' => ['required'],
            'periode' => ['required'],
            'tema' => ['required'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);
        $validator->setAttributeNames(['prodi' => 'program studi', 'periode' => 'periode KKN', 'tema' => 'tema KKN']);
        $data = $validator->validate();

        RegistrationRequest::updateOrCreate(['email' => $identity['email']], [
            'google_id' => $identity['google_id'],
            'name' => $row['nama'],
            'requested_role' => $row['peran'],
            'region_path' => $row['wilayah'],
            'faculty' => $row['fakultas'],
            'study_program' => $row['prodi'],
            'kkn_period' => $row['periode'],
            'kkn_theme' => $row['tema'],
            'note' => ($data['catatan'] ?? '') !== '' ? $data['catatan'] : null,
            'status' => RegistrationRequest::STATUS_PENDING,
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $request->session()->forget(RegistrationRequest::SESSION_KEY);

        return redirect()->route('login')
            ->with('flash_success', 'Pendaftaran terkirim. Anda bisa masuk setelah admin menyetujuinya.');
    }
}
