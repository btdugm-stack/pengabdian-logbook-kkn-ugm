<?php

namespace App\Http\Controllers;

use App\Models\DailyAttendance;
use App\Models\Logbook;
use App\Models\Region;
use App\Models\Student;
use App\Support\ActivityMap;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user->hasAnyRole(Student::SUPERVISORY_ROLES), 403);

        // Pimpinan hanya melihat agregat: tanpa nama, kondisi, atau daftar per mahasiswa.
        $aggregateOnly = $user->seesAggregateOnly();
        $allowed = $user->visibleRegionIds();
        $scopeIds = null;

        $selectedRegion = null;
        if ($regionId = $request->get('region_id')) {
            $selectedRegion = Region::findOrFail($regionId);
            $scopeIds = $selectedRegion->subtreeIds();
            if ($allowed !== null) {
                $scopeIds = array_values(array_intersect($scopeIds, $allowed));
                abort_if(empty($scopeIds), 403, 'Wilayah tersebut di luar cakupan akun Anda.');
            }
        }

        $filterOptions = $this->regionFilterOptions($user, $allowed);

        // Cakupan (wilayah, unit fakultas, atau semua) ditegakkan supervisedStudents();
        // filter wilayah hanya mempersempitnya.
        $students = $user->supervisedStudents()
            ->with('region')
            ->when($scopeIds !== null, fn ($q) => $q->whereIn('region_id', $scopeIds))
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        $todayAttendances = DailyAttendance::whereIn('student_id', $studentIds)
            ->whereDate('attendance_date', today())
            ->get()
            ->keyBy('student_id');

        $sickToday = $todayAttendances->whereIn('condition', ['Sakit Ringan', 'Sakit Berat'])->count();

        $trend = $this->trend($studentIds);

        $logbooks = ($aggregateOnly ? Logbook::visibleToPublic() : Logbook::published())
            ->with(['student', 'theme', 'program', 'location'])
            ->whereIn('student_id', $studentIds)
            ->get();

        return view('overview.index', [
            'aggregateOnly' => $aggregateOnly,
            'filterOptions' => $filterOptions,
            'selectedRegion' => $selectedRegion,
            'students' => $students,
            'todayAttendances' => $todayAttendances,
            // Agregat: diwarnai per tema, tanpa nama dan kondisi. Lainnya: per kondisi kesehatan.
            'map' => $aggregateOnly ? ActivityMap::byTheme($logbooks, withStudent: false) : ActivityMap::byHealth($logbooks),
            'trend' => $trend,
            // Akun region-scoped tanpa wilayah ditugaskan: tampilkan penjelasan,
            // bukan halaman kosong yang terlihat seperti error.
            'unassigned' => $user->hasNoSupervisionScope(),
            'kpi' => [
                'total' => $students->count(),
                'presentToday' => $todayAttendances->count(),
                'sickToday' => $sickToday,
                'notYetPresent' => $students->count() - $todayAttendances->count(),
            ],
        ]);
    }

    /**
     * Pilihan filter wilayah: wilayah yang memang berisi mahasiswa dalam
     * cakupan akun beserta induknya - termasuk untuk DPL dan Fakultas, yang
     * cakupannya bukan wilayah. Admin dan pimpinan melihat semua wilayah.
     *
     * @param  array<int>|null  $allowed
     * @return Collection<int, Region>
     */
    private function regionFilterOptions(Student $user, ?array $allowed): Collection
    {
        $regions = Region::all()->keyBy('id');

        if ($allowed !== null) {
            $ids = $allowed;
        } elseif ($user->hasAnyRole(Student::FULL_ACCESS_ROLES)) {
            $ids = $regions->keys()->all();
        } else {
            $ids = [];
            foreach ($user->supervisedStudents()->whereNotNull('region_id')->distinct()->pluck('region_id') as $regionId) {
                for ($region = $regions->get($regionId); $region; $region = $regions->get($region->parent_id)) {
                    $ids[] = $region->id;
                }
            }
        }

        return $regions->only(array_unique($ids))->sortBy(fn (Region $r) => $r->fullPath())->values();
    }

    /**
     * Detail satu mahasiswa untuk supervisor: kontak (termasuk kontak darurat
     * untuk tindak lanjut eskalasi kesehatan), riwayat presensi, dan logbook
     * yang sudah dikirim. Di luar cakupan dijawab 404, bukan 403, supaya akun
     * lain tidak bisa menebak siapa saja yang terdaftar.
     */
    public function student(Student $student): View
    {
        $supervisor = Auth::user();
        abort_unless($supervisor->canSupervise($student), 404);

        $student->load(['region', 'advisors:id,name']);

        return view('overview.student', [
            'student' => $student,
            'todayAttendance' => $student->todayAttendance(),
            'attendances' => $student->dailyAttendances()->orderByDesc('attendance_date')->limit(30)->get(),
            // Draft adalah catatan kerja pribadi: hanya admin (yang boleh mengoreksinya) yang ikut melihat.
            'logbooks' => $student->logbooks()
                ->when(! $supervisor->isAdmin(), fn ($query) => $query->published())
                ->with(['student', 'theme', 'program', 'activityType', 'location', 'latestReview', 'enteredBy'])
                ->orderByDesc('log_date')
                ->paginate(10),
            'canReview' => $supervisor->hasAnyRole(Student::REVIEWER_ROLES),
            'canCorrect' => $supervisor->isAdmin(),
        ]);
    }

    /**
     * Tren 14 hari terakhir (Sehat/Sakit/Izin-Alpha per hari) untuk mahasiswa
     * dalam scope - dipakai grafik di Overview Wilayah.
     *
     * @param  Collection<int, int>  $studentIds
     * @return array{labels: array<string>, sehat: array<int>, sakit: array<int>, lainnya: array<int>}
     */
    private function trend($studentIds): array
    {
        $start = today()->subDays(13);

        $rows = DailyAttendance::whereIn('student_id', $studentIds)
            ->whereDate('attendance_date', '>=', $start)
            ->get(['attendance_date', 'condition']);

        $labels = $sehat = $sakit = $lainnya = [];

        for ($d = $start->copy(); $d->lte(today()); $d->addDay()) {
            $dateStr = $d->toDateString();
            $dayRows = $rows->filter(fn ($r) => $r->attendance_date->toDateString() === $dateStr);

            $labels[] = $d->translatedFormat('d M');
            $sehat[] = $dayRows->where('condition', 'Sehat')->count();
            $sakit[] = $dayRows->whereIn('condition', ['Sakit Ringan', 'Sakit Berat'])->count();
            $lainnya[] = $dayRows->whereIn('condition', ['Izin', 'Alpha'])->count();
        }

        return compact('labels', 'sehat', 'sakit', 'lainnya');
    }
}
