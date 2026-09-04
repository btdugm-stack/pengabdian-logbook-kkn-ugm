<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Logbook;
use App\Models\Student;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'totalStudents' => Student::role('mahasiswa')->count(),
            'totalLogs' => Logbook::count(),
            'sick' => Logbook::where('health_status', 'like', '%Sakit%')->count(),
            'totalLocations' => Location::count(),
        ]);
    }
}
