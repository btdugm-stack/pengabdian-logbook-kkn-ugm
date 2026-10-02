<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Student;
use App\Support\ParticipantImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menu Administrasi > Kelola Peserta: daftarkan akun mahasiswa dan pembimbing
 * supaya bisa login dengan Google UGM. Akses dibatasi di route
 * (Student::ACCOUNT_MANAGER_ROLES).
 */
class ParticipantController extends Controller
{
    /** Batas baris per unggahan web; file lebih besar lewat CLI supaya tidak kena batas waktu request. */
    private const MAX_UPLOAD_ROWS = 2000;

    public function __construct(private ParticipantImporter $importer) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $role = (string) $request->get('peran', '');
        $pattern = '%'.addcslashes($q, '%_\\').'%';

        $participants = Student::with(['roles', 'region.parent.parent.parent'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', $pattern)
                ->orWhere('email', 'like', $pattern)))
            ->when(in_array($role, Student::ROLES, true), fn ($query) => $query->role($role))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.participants.index', [
            'participants' => $participants,
            'q' => $q,
            'role' => $role,
            'roleCounts' => collect(Student::ROLES)->mapWithKeys(fn (string $r) => [$r => Student::role($r)->count()]),
            'neverLoggedIn' => Student::whereNull('google_id')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.participants.create', ['regionPaths' => Region::paths()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $row = $this->importer->normalize($request->all());

        $validator = $this->importer->validator($row);
        $validator->addRules([
            'email' => [Rule::unique('students', 'email')],
            'peran' => [Rule::in($request->user()->assignableRoles())],
        ]);
        $validator->setCustomMessages(['email.unique' => 'Email ini sudah terdaftar. Cari di daftar akun untuk mengubahnya.']);
        $validator->validate();

        $student = $this->importer->save($row);

        return redirect()
            ->route($request->input('after') === 'create' ? 'admin.participants.create' : 'admin.participants.index')
            ->with('flash_success', "Akun {$student->name} ({$student->email}) berhasil didaftarkan sebagai {$student->roleLabel()}.");
    }

    public function edit(Request $request, Student $student): View
    {
        abort_unless($request->user()->canManageAccount($student), 403, 'Akun super admin hanya bisa diubah oleh super admin.');

        $student->load('region');

        return view('admin.participants.edit', [
            'student' => $student,
            'regionPaths' => Region::paths(),
            // Ringkasan data yang ikut terhapus, untuk kartu Hapus Akun (khusus super admin).
            'deletable' => $this->deletionBlocker($request->user(), $student) === null,
            'deletionImpact' => $request->user()->isSuperAdmin() ? [
                'logbook' => $student->logbooks()->count(),
                'presensi' => $student->dailyAttendances()->count(),
                'presensi bantuan' => $student->assistAttendancesAsHelper()->count(),
                'mahasiswa bimbingan' => $student->advisees()->count(),
            ] : [],
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        abort_unless($request->user()->canManageAccount($student), 403, 'Akun super admin hanya bisa diubah oleh super admin.');

        // Email adalah identitas login Google - tidak diubah dari form ini.
        $row = $this->importer->normalize([...$request->all(), 'email' => $student->email]);

        $validator = $this->importer->validator($row);
        $validator->addRules(['peran' => [Rule::in($request->user()->assignableRoles())]]);

        if ($student->is($request->user()) && $row['peran'] !== $student->getRoleNames()->first()) {
            $validator->after(fn ($v) => $v->errors()->add('peran', 'Anda tidak bisa mengubah peran akun sendiri.'));
        }

        $validator->validate();

        $this->importer->save($row, overwriteBlankBiodata: true);

        return redirect()->route('admin.participants.index')
            ->with('flash_success', "Akun {$row['nama']} diperbarui.");
    }

    /**
     * Hapus akun permanen - hanya super admin (dibatasi juga di route).
     * Presensi, logbook beserta riwayat reviunya, presensi bantuan yang ia
     * catat, dan penugasan DPL-nya ikut terhapus lewat foreign key; yang tidak
     * ber-foreign key (notifikasi, sesi login) dibersihkan di sini.
     */
    public function destroy(Request $request, Student $student): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        if ($reason = $this->deletionBlocker($request->user(), $student)) {
            return back()->with('flash_error', $reason);
        }

        // Email harus diketik ulang: penghapusan tidak bisa dibatalkan.
        $request->validateWithBag('deletion', [
            'konfirmasi_email' => ['required', Rule::in([$student->email])],
        ], ['konfirmasi_email.*' => 'Ketik email akun ini persis untuk mengonfirmasi penghapusan.']);

        $label = "{$student->name} ({$student->email})";

        DB::transaction(function () use ($student) {
            $student->notifications()->delete();
            DB::table('sessions')->where('user_id', $student->id)->delete();
            $student->delete();
        });

        return redirect()->route('admin.participants.index')->with('flash_success', "Akun {$label} dihapus beserta seluruh datanya.");
    }

    /** Alasan akun tidak boleh dihapus oleh $actor, atau null bila boleh. */
    private function deletionBlocker(Student $actor, Student $student): ?string
    {
        return match (true) {
            ! $actor->isSuperAdmin() => 'Hanya super admin yang bisa menghapus akun.',
            $student->is($actor) => 'Anda tidak bisa menghapus akun sendiri.',
            default => null,
        };
    }

    public function importForm(): View
    {
        return view('admin.participants.import', ['maxRows' => self::MAX_UPLOAD_ROWS]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:2048', 'extensions:csv,txt'],
        ]);

        $rows = $this->importer->readCsv($request->file('file')->getRealPath());

        if ($rows === []) {
            return back()->with('flash_error', 'File kosong atau tidak memiliki baris data.');
        }

        if (count($rows) > self::MAX_UPLOAD_ROWS) {
            return back()->with('flash_error', 'File berisi '.count($rows).' baris, maksimal '.self::MAX_UPLOAD_ROWS.' per unggahan. Pecah menjadi beberapa file.');
        }

        $errors = $this->importer->validateRows($rows);

        foreach ($this->superAdminRowsBlockedFor($request->user(), $rows) as $line) {
            $errors[$line][] = 'Peran super admin hanya bisa diberikan atau diubah oleh super admin.';
        }

        ksort($errors);

        if ($errors !== []) {
            return back()
                ->with('import_errors', $errors)
                ->with('flash_error', 'Impor dibatalkan, tidak ada data yang disimpan: '.count($errors).' baris perlu diperbaiki.');
        }

        if ($request->boolean('dry_run')) {
            return back()->with('flash_success', count($rows).' baris valid dan siap diimpor. Belum ada data yang disimpan.');
        }

        ['created' => $created, 'updated' => $updated] = $this->importer->import($rows);

        return redirect()->route('admin.participants.index')
            ->with('flash_success', "Impor selesai: {$created} akun baru, {$updated} akun diperbarui.");
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            foreach ([
                ParticipantImporter::COLUMNS,
                ['nama.mahasiswa@mail.ugm.ac.id', 'Nama Mahasiswa', 'mahasiswa', 'Kabupaten Sleman / Kecamatan Ngaglik / Desa Candirejo / Sub-unit 5A', 'Fakultas Teknik', 'Teknik Informatika', 'Periode 2 Tahun 2026', 'Digitalisasi Desa'],
                ['kormasit@mail.ugm.ac.id', 'Nama Kormasit', 'kormasit', 'Kabupaten Sleman / Kecamatan Ngaglik / Desa Candirejo / Sub-unit 5A', '', '', 'Periode 2 Tahun 2026', ''],
                ['dosen@ugm.ac.id', 'Nama Dosen', 'dpl', '', '', '', '', ''],
                ['admin@ugm.ac.id', 'Nama Admin', 'admin_lppm', '', '', '', '', ''],
            ] as $row) {
                fputcsv($out, $row, ',', '"', '');
            }

            fclose($out);
        }, 'template-peserta-kkn.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    /**
     * Baris impor yang menyentuh peran super admin (memberi peran itu, atau
     * menimpa akun yang sudah super admin) padahal pengunggah bukan super admin.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, int> nomor baris di file
     */
    private function superAdminRowsBlockedFor(Student $actor, array $rows): array
    {
        if ($actor->isSuperAdmin()) {
            return [];
        }

        $superAdminEmails = Student::role(Student::SUPER_ADMIN_ROLE)->pluck('email')->all();

        return collect($rows)
            ->map(fn (array $row) => $this->importer->normalize($row))
            ->filter(fn (array $row) => $row['peran'] === Student::SUPER_ADMIN_ROLE || in_array($row['email'], $superAdminEmails, true))
            ->keys()
            ->all();
    }
}
