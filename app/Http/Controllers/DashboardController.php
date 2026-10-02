<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $student = Auth::user();

        // Peserta (termasuk ketua kelompok) mendarat di dashboard kegiatannya.
        // Peran lain tidak punya presensi/logbook sendiri: halaman utamanya Overview.
        if (! $student->isParticipant()) {
            abort_unless($student->hasAnyRole(Student::SUPERVISORY_ROLES), 403, 'Akun Anda belum memiliki peran. Hubungi admin LPPM.');

            return redirect()->route('overview');
        }

        $logbooks = $student->logbooks()->with(['theme', 'program', 'activityType', 'location', 'latestReview', 'enteredBy'])
            ->orderByDesc('log_date')->get();

        return view('dashboard', [
            'student' => $student,
            'todayAttendance' => $student->todayAttendance(),
            'myLogs' => $logbooks->count(),
            'drafts' => $logbooks->where('status', Logbook::STATUS_DRAFT)->count(),
            'revisions' => $logbooks->where('status', Logbook::STATUS_REVISION)->count(),
            'community' => $logbooks->sum('community_count'),
            'locations' => $logbooks->pluck('location_id')->unique()->count(),
            'pendingApprovals' => $student->assistAttendancesAsHost()->where('approval_status', 'Menunggu')->count(),
            'recentLogbooks' => $logbooks->take(5),
        ]);
    }
}
