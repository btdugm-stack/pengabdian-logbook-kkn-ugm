<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogbookController extends Controller
{
    public function create(): View
    {
        return view('logbooks.create');
    }

    public function index(): View
    {
        $logbooks = Auth::user()->logbooks()
            ->with(['theme', 'program', 'activityType', 'location'])
            ->orderByDesc('log_date')
            ->paginate(15);

        return view('logbooks.index', ['logbooks' => $logbooks]);
    }

    public function export(): StreamedResponse
    {
        $logbooks = Auth::user()->logbooks()
            ->with(['theme', 'program', 'activityType', 'location'])
            ->orderByDesc('log_date')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=laporan-logbook-kkn.csv',
        ];

        return response()->stream(function () use ($logbooks) {
            $out = fopen('php://output', 'w');

            // Audit §CSV-injection: sel teks user-controlled yang diawali =, +, -, @
            // bisa dieksekusi sebagai formula oleh Excel/Sheets -> beri prefix apostrof.
            $cell = fn ($v): string => (is_string($v) && $v !== '' && str_contains('=+-@', $v[0]))
                ? "'".$v
                : (string) $v;

            fputcsv($out, ['Tanggal', 'Mahasiswa', 'Tema', 'Program', 'Jenis', 'Lokasi', 'Koordinat', 'Masyarakat Terlibat', 'Kondisi', 'Status', 'Catatan']);

            foreach ($logbooks as $l) {
                fputcsv($out, [
                    $l->log_date, $cell($l->student->name), $cell($l->theme->name), $cell($l->program->name), $cell($l->activityType->name),
                    $cell($l->location->name), $l->location->latitude.','.$l->location->longitude,
                    $l->community_count, $cell($l->health_status), $cell($l->status), $cell($l->progress_note),
                ]);
            }

            fclose($out);
        }, 200, $headers);
    }
}
