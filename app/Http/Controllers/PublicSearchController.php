<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicSearchController extends Controller
{
    /**
     * Field sensitif (phone, emergency_contact, birth_date lengkap, kondisi
     * kesehatan per-entri) SENGAJA tidak diselect/ditampilkan di sini -
     * lihat temuan kritis audit tentang PII yang sebelumnya terbuka publik.
     */
    public function students(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $faculty = trim((string) $request->get('faculty', ''));

        $students = Student::role('mahasiswa')
            ->select(['id', 'name', 'faculty', 'study_program', 'region_id'])
            ->with('region')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', $this->likePattern($q))
                ->orWhere('faculty', 'like', $this->likePattern($q))
                ->orWhere('study_program', 'like', $this->likePattern($q))))
            ->when($faculty !== '', fn ($query) => $query->where('faculty', $faculty))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('public.students', [
            'students' => $students,
            'q' => $q,
            'faculty' => $faculty,
            'faculties' => Student::role('mahasiswa')->whereNotNull('faculty')->where('faculty', '<>', '')
                ->distinct()->orderBy('faculty')->pluck('faculty'),
            'totalAll' => Student::role('mahasiswa')->count(),
        ]);
    }

    public function logbooks(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $studentId = $request->get('student', '');

        $logbooks = Logbook::query()
            ->with(['student', 'theme', 'program', 'activityType', 'location'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->whereHas('student', fn ($s) => $s->where('name', 'like', $this->likePattern($q))->orWhere('faculty', 'like', $this->likePattern($q))->orWhere('study_program', 'like', $this->likePattern($q)))
                ->orWhereHas('theme', fn ($s) => $s->where('name', 'like', $this->likePattern($q)))
                ->orWhereHas('program', fn ($s) => $s->where('name', 'like', $this->likePattern($q)))
                ->orWhereHas('activityType', fn ($s) => $s->where('name', 'like', $this->likePattern($q)))
                ->orWhereHas('location', fn ($s) => $s->where('name', 'like', $this->likePattern($q)))
                ->orWhere('progress_note', 'like', $this->likePattern($q))))
            ->when($studentId !== '', fn ($query) => $query->where('student_id', $studentId))
            ->orderByDesc('log_date')
            ->paginate(15)
            ->withQueryString();

        return view('public.logbooks', [
            'logbooks' => $logbooks,
            'q' => $q,
            'studentId' => $studentId,
            'students' => Student::role('mahasiswa')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Pola LIKE aman (audit §wildcard): escape %, _ dan \ dari input user supaya
     * tidak berfungsi sebagai wildcard SQL (mis. q="%" tidak mengembalikan semua).
     */
    private function likePattern(string $q): string
    {
        return '%'.addcslashes($q, '%_\\').'%';
    }
}
