<?php

namespace App\Http\Controllers;

use App\Models\DailyAttendance;
use App\Models\Logbook;
use App\Models\Region;
use App\Models\Student;
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

        $allowed = $user->visibleRegionIds();
        $scopeIds = $allowed;

        $selectedRegion = null;
        if ($regionId = $request->get('region_id')) {
            $selectedRegion = Region::findOrFail($regionId);
            $scopeIds = $selectedRegion->subtreeIds();
            if ($allowed !== null) {
                $scopeIds = array_values(array_intersect($scopeIds, $allowed));
                abort_if(empty($scopeIds), 403, 'Wilayah tersebut di luar cakupan akun Anda.');
            }
        }

        $filterOptions = ($allowed !== null ? Region::whereIn('id', $allowed) : Region::query())
            ->get()
            ->sortBy(fn (Region $r) => $r->fullPath())
            ->values();

        $students = Student::role('mahasiswa')
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

        $logbooks = Logbook::with(['student', 'program', 'location'])
            ->whereIn('student_id', $studentIds)
            ->get();

        $markers = $logbooks->filter(fn ($l) => $l->location->latitude && $l->location->longitude)
            ->map(fn ($l) => [
                'lat' => (float) $l->location->latitude,
                'lng' => (float) $l->location->longitude,
                'student' => $l->student->name,
                'title' => $l->program->name,
                'location' => $l->location->name,
                'health' => $l->health_status,
                'status' => $l->status,
                'count' => $l->community_count,
                'color' => $l->markerColor(),
            ])->values();

        return view('overview.index', [
            'filterOptions' => $filterOptions,
            'selectedRegion' => $selectedRegion,
            'students' => $students,
            'todayAttendances' => $todayAttendances,
            'markers' => $markers,
            'trend' => $trend,
            'kpi' => [
                'total' => $students->count(),
                'presentToday' => $todayAttendances->count(),
                'sickToday' => $sickToday,
                'notYetPresent' => $students->count() - $todayAttendances->count(),
            ],
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
