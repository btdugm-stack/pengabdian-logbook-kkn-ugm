<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Support\Export\LogbookExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LogbookController extends Controller
{
    public function create(): View
    {
        return view('logbooks.create');
    }

    public function edit(Logbook $logbook): View
    {
        Gate::authorize('update', $logbook);

        return view('logbooks.edit', ['logbook' => $logbook]);
    }

    public function destroy(Logbook $logbook): RedirectResponse
    {
        Gate::authorize('delete', $logbook);

        $wasDraft = $logbook->isDraft();
        $logbook->delete();

        return redirect()->route('logbooks.index')->with('flash_success', $wasDraft ? 'Draft logbook dihapus.' : 'Logbook dihapus.');
    }

    public function index(): View
    {
        $logbooks = Auth::user()->logbooks()
            ->with(['student', 'theme', 'program', 'activityType', 'location', 'latestReview', 'enteredBy'])
            ->orderByDesc('log_date')
            ->paginate(15);

        return view('logbooks.index', ['logbooks' => $logbooks]);
    }

    /** Unduh logbook milik sendiri: ?format=xlsx (bawaan), csv, atau docx. */
    public function export(Request $request): Response
    {
        $format = (string) $request->query('format', 'xlsx');
        abort_unless(in_array($format, LogbookExport::FORMATS, true), 404);

        return (new LogbookExport($request->user()))->download($format);
    }
}
