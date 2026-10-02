<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Input dan koreksi logbook oleh admin atas nama mahasiswa atau ketua kelompok
 * (tanda * pada kolom Admin di matriks hak akses). Akses dibatasi di route
 * (Student::ACCOUNT_MANAGER_ROLES); form-nya sama dengan milik mahasiswa.
 */
class LogbookEntryController extends Controller
{
    public function create(Student $student): View
    {
        Gate::authorize('createFor', [Logbook::class, $student]);

        return view('admin.logbooks.form', ['owner' => $student, 'logbook' => null]);
    }

    public function edit(Logbook $logbook): View
    {
        Gate::authorize('update', $logbook);

        return view('admin.logbooks.form', ['owner' => $logbook->student, 'logbook' => $logbook]);
    }
}
