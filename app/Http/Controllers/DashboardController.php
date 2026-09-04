<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $student = Auth::user();

        $logbooks = $student->logbooks()->with(['theme', 'program', 'activityType', 'location'])
            ->orderByDesc('log_date')->get();

        return view('dashboard', [
            'student' => $student,
            'todayAttendance' => $student->todayAttendance(),
            'myLogs' => $logbooks->count(),
            'mySick' => $logbooks->filter(fn ($l) => str_contains($l->health_status, 'Sakit'))->count(),
            'community' => $logbooks->sum('community_count'),
            'locations' => $logbooks->pluck('location_id')->unique()->count(),
            'recentLogbooks' => $logbooks->take(5),
        ]);
    }
}
