<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Logbook;
use App\Models\Student;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Angka agregat untuk publik. Hanya logbook yang sudah dikirim yang
     * dihitung, dan kondisi kesehatan sengaja tidak ditampilkan di sini.
     */
    public function index(): View
    {
        return view('home', [
            'totalStudents' => Student::participants()->count(),
            'totalLogs' => Logbook::visibleToPublic()->count(),
            'totalCommunity' => (int) Logbook::visibleToPublic()->sum('community_count'),
            'totalLocations' => Location::whereHas('logbooks', fn ($q) => $q->visibleToPublic())->count(),
        ]);
    }
}
