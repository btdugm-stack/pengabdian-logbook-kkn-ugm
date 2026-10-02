<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\Student;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pencarian dan export logbook sesuai cakupan peran (baris "Search logbook"
 * dan "Export laporan" pada matriks hak akses): ketua = kelompoknya, DPL =
 * bimbingannya, fakultas = unitnya, admin = semua, pimpinan = ringkasan
 * strategis tanpa data per mahasiswa. Cakupannya dari Student::supervisedStudents().
 */
class LogbookBrowseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $aggregateOnly = $user->seesAggregateOnly();

        return view('logbooks.browse.index', [
            'aggregateOnly' => $aggregateOnly,
            'logbooks' => $this->scoped($request)
                ->with(['student', 'theme', 'program', 'activityType', 'location', 'latestReview', 'enteredBy'])
                ->orderByDesc('log_date')
                ->paginate(20)
                ->withQueryString(),
            'q' => trim((string) $request->get('q', '')),
            'status' => (string) $request->get('status', ''),
            'kind' => (string) $request->get('jenis', ''),
            'statuses' => $this->statusOptions($user),
            'scopeLabel' => $this->scopeLabel($user),
            'canReview' => $user->hasAnyRole(Student::REVIEWER_ROLES),
            'canCorrect' => $user->isAdmin(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $date = now()->format('Y-m-d');

        if ($user->seesAggregateOnly()) {
            return $this->executiveExport($request, $date);
        }

        return Csv::download("logbook-{$date}.csv",
            ['Tanggal', 'Periode KKN', 'Mahasiswa', 'Fakultas', 'Wilayah', 'Tema', 'Program', 'Jenis', 'Lokasi', 'Warga Terlibat', 'Kondisi', 'Status', 'Kelompok', 'Catatan'],
            fn () => $this->scoped($request)
                ->with(['student.region', 'theme', 'program', 'activityType', 'location'])
                ->orderByDesc('log_date')
                ->lazy()
                ->map(fn (Logbook $l) => [
                    $l->log_date->format('Y-m-d H:i'), $l->kkn_period, $l->student->name, $l->student->faculty, $l->student->region?->fullPath(),
                    $l->theme->name, $l->program->name, $l->activityType->name, $l->location->name,
                    $l->community_count, $l->health_status, $l->statusLabel(), $l->is_group ? 'Ya' : 'Tidak', $l->progress_note,
                ]),
        );
    }

    /**
     * Laporan eksekutif: rekap per tema dan program, tanpa satu pun baris per mahasiswa.
     */
    private function executiveExport(Request $request, string $date): StreamedResponse
    {
        return Csv::download("rekap-eksekutif-{$date}.csv",
            ['Tema', 'Program', 'Jumlah Logbook', 'Mahasiswa Terlibat', 'Warga Terlibat', 'Lokasi Kegiatan'],
            fn () => $this->scoped($request)
                ->join('themes', 'themes.id', '=', 'logbooks.theme_id')
                ->join('programs', 'programs.id', '=', 'logbooks.program_id')
                ->groupBy('themes.name', 'programs.name')
                ->orderBy('themes.name')
                ->orderBy('programs.name')
                ->get([
                    'themes.name as theme', 'programs.name as program',
                    DB::raw('COUNT(*) as logbooks'),
                    DB::raw('COUNT(DISTINCT logbooks.student_id) as students'),
                    DB::raw('SUM(logbooks.community_count) as community'),
                    DB::raw('COUNT(DISTINCT logbooks.location_id) as locations'),
                ])
                ->map(fn ($row) => [$row->theme, $row->program, $row->logbooks, $row->students, (int) $row->community, $row->locations]),
        );
    }

    /**
     * Logbook dalam cakupan akun, dengan filter dari query string. Pimpinan
     * hanya melihat yang layak tampil ke publik (menunggu reviu atau disetujui).
     *
     * @return Builder<Logbook>
     */
    private function scoped(Request $request): Builder
    {
        $user = $request->user();
        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');
        $kind = (string) $request->get('jenis', '');
        $like = '%'.addcslashes($q, '%_\\').'%';

        return ($user->seesAggregateOnly() ? Logbook::visibleToPublic() : Logbook::published())
            ->whereIn('logbooks.student_id', $user->supervisedStudents()->select('id'))
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $w) => $w
                ->where('logbooks.progress_note', 'like', $like)
                ->orWhereHas('student', fn (Builder $s) => $s->where('name', 'like', $like))
                ->orWhereHas('theme', fn (Builder $s) => $s->where('name', 'like', $like))
                ->orWhereHas('program', fn (Builder $s) => $s->where('name', 'like', $like))
                ->orWhereHas('location', fn (Builder $s) => $s->where('name', 'like', $like))))
            ->when(array_key_exists($status, $this->statusOptions($user)), fn (Builder $query) => $query->where('logbooks.status', $status))
            ->when($kind === 'kelompok', fn (Builder $query) => $query->where('logbooks.is_group', true))
            ->when($kind === 'pribadi', fn (Builder $query) => $query->where('logbooks.is_group', false));
    }

    /** @return array<string, string> */
    private function statusOptions(Student $user): array
    {
        $visible = $user->seesAggregateOnly()
            ? [Logbook::STATUS_SUBMITTED, Logbook::STATUS_APPROVED]
            : [Logbook::STATUS_SUBMITTED, Logbook::STATUS_REVISION, Logbook::STATUS_APPROVED, Logbook::STATUS_REJECTED];

        return array_intersect_key(Logbook::STATUS_LABELS, array_flip($visible));
    }

    private function scopeLabel(Student $user): string
    {
        return match (true) {
            $user->seesAggregateOnly() => 'Seluruh wilayah, tampilan strategis',
            $user->hasAnyRole(Student::UNIT_ROLES) => 'Unit '.($user->faculty ?: 'belum diisi'),
            $user->hasAnyRole(Student::FULL_ACCESS_ROLES) => 'Seluruh wilayah',
            default => $user->supervisionRegion()?->fullPath() ?? 'Wilayah belum ditugaskan',
        };
    }
}
