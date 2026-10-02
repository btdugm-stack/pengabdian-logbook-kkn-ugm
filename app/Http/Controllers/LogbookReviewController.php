<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\LogbookReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Reviu logbook oleh pembimbing (Student::REVIEWER_ROLES, dibatasi di route):
 * antrean logbook yang menunggu, lalu satu dari tiga keputusan per logbook.
 */
class LogbookReviewController extends Controller
{
    /** Nilai tombol keputusan di form -> status logbook yang dihasilkan. */
    private const DECISIONS = [
        'setujui' => Logbook::STATUS_APPROVED,
        'revisi' => Logbook::STATUS_REVISION,
        'tolak' => Logbook::STATUS_REJECTED,
    ];

    public function index(Request $request): View
    {
        $reviewer = $request->user();

        return view('logbooks.reviews.index', [
            'waiting' => Logbook::where('status', Logbook::STATUS_SUBMITTED)
                ->whereIn('student_id', $reviewer->supervisedStudents()->select('id'))
                ->with(['student.region', 'theme', 'program', 'activityType', 'location'])
                ->oldest('updated_at')
                ->paginate(15),
            'recent' => LogbookReview::where('reviewer_id', $reviewer->id)
                ->with(['logbook.student', 'logbook.program'])
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function show(Logbook $logbook): View
    {
        Gate::authorize('viewForReview', $logbook);

        $logbook->load(['student.region', 'theme', 'program', 'activityType', 'location', 'reviews.reviewer']);

        return view('logbooks.reviews.show', [
            'logbook' => $logbook,
            'reviews' => $logbook->reviews->sortByDesc('id')->values(),
            'canDecide' => Gate::allows('review', $logbook),
        ]);
    }

    public function store(Request $request, Logbook $logbook): RedirectResponse
    {
        Gate::authorize('review', $logbook);

        $data = $request->validate([
            'keputusan' => ['required', Rule::in(array_keys(self::DECISIONS))],
            // Persetujuan boleh tanpa catatan; revisi dan penolakan harus menjelaskan alasannya ke mahasiswa.
            'catatan' => ['nullable', 'required_unless:keputusan,setujui', 'string', 'max:2000'],
        ], [
            'catatan.required_unless' => 'Tulis catatan untuk mahasiswa: apa yang perlu direvisi, atau alasan penolakan.',
        ]);

        $status = self::DECISIONS[$data['keputusan']];

        DB::transaction(function () use ($logbook, $request, $status, $data) {
            $logbook->reviews()->create([
                'reviewer_id' => $request->user()->id,
                'decision' => $status,
                'note' => $data['catatan'] ?? null,
            ]);
            $logbook->update(['status' => $status]);
        });

        return redirect()->route('logbooks.reviews.index')->with('flash_success', match ($status) {
            Logbook::STATUS_APPROVED => "Logbook {$logbook->student->name} disetujui.",
            Logbook::STATUS_REVISION => "Lembar revisi dikirim ke {$logbook->student->name}.",
            default => "Logbook {$logbook->student->name} ditolak.",
        });
    }
}
