<?php

namespace App\Support\Export;

use App\Models\Logbook;
use App\Models\Student;
use App\Support\Csv;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unduhan "Logbook Saya" dalam tiga format dengan isi yang sama:
 * - CSV  : tabel datar, satu baris per logbook, untuk diolah lagi;
 * - Excel: sheet Logbook (tabel yang sama, bisa difilter) + sheet Ringkasan;
 * - Word : laporan siap cetak dengan identitas, tabel kegiatan, dan kolom tanda tangan.
 */
class LogbookExport
{
    public const FORMATS = ['xlsx', 'csv', 'docx'];

    private const COLUMNS = [
        'No', 'Tanggal', 'Waktu', 'Periode KKN', 'Tema', 'Program Kerja', 'Jenis Kegiatan', 'Lingkup',
        'Lokasi', 'Lintang', 'Bujur', 'Warga Terlibat', 'Kondisi Kesehatan', 'Status',
        'Catatan Kegiatan', 'Catatan Pribadi', 'Tautan Dokumentasi', 'Catatan Pembimbing', 'Direviu Oleh', 'Tanggal Reviu',
    ];

    /** Lebar kolom Excel, sejajar dengan COLUMNS. */
    private const COLUMN_WIDTHS = [5, 12, 8, 20, 24, 28, 20, 11, 28, 12, 12, 10, 16, 14, 60, 40, 34, 44, 22, 14];

    /** @var Collection<int, Logbook> */
    private Collection $logbooks;

    public function __construct(private Student $student)
    {
        // Urut kronologis: logbook dibaca sebagai catatan harian dari awal ke akhir.
        $this->logbooks = $student->logbooks()
            ->with(['theme', 'program', 'activityType', 'location', 'latestReview.reviewer'])
            ->orderBy('log_date')
            ->orderBy('id')
            ->get();

        $student->loadMissing(['region', 'advisors:id,name']);
    }

    public function download(string $format): Response
    {
        $filename = 'logbook-kkn-'.Str::slug($this->student->name).'-'.now()->format('Y-m-d').'.'.$format;

        return match ($format) {
            'xlsx' => $this->binary($this->xlsx(), $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'docx' => $this->binary($this->docx(), $filename, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            default => Csv::download($filename, self::COLUMNS, fn () => $this->rows()->map(
                // CSV tidak punya tipe tanggal: pakai format ISO supaya terbaca sama di mana pun.
                fn (array $row) => [...array_slice($row, 0, 1), $row[1]?->format('Y-m-d'), ...array_slice($row, 2, 17), $row[19]?->format('Y-m-d')],
            )),
        };
    }

    /**
     * Satu baris per logbook, sejajar dengan COLUMNS. Tanggal tetap berupa objek
     * supaya tiap format bisa menuliskannya dengan caranya sendiri.
     *
     * @return Collection<int, array<int, mixed>>
     */
    private function rows(): Collection
    {
        return $this->logbooks->values()->map(function (Logbook $l, int $index) {
            $review = $l->latestReview;

            return [
                $index + 1,
                $l->log_date,
                $l->log_date->format('H:i'),
                $l->kkn_period,
                $l->theme->name,
                $l->program->name,
                $l->activityType->name,
                $l->is_group ? 'Kelompok' : 'Pribadi',
                $l->location->name,
                $l->location->latitude !== null ? (float) $l->location->latitude : null,
                $l->location->longitude !== null ? (float) $l->location->longitude : null,
                (int) $l->community_count,
                $l->health_status,
                $l->statusLabel(),
                $l->progress_note,
                $l->personal_info,
                $l->documentation,
                $review?->note,
                $review?->reviewer?->name,
                $review?->created_at,
            ];
        });
    }

    /** @return array<string, string> label => nilai */
    private function identity(): array
    {
        return [
            'Nama' => $this->student->name,
            'Email' => $this->student->email,
            'Fakultas / Sekolah' => $this->student->faculty ?: '-',
            'Program Studi' => $this->student->study_program ?: '-',
            'Periode KKN' => $this->student->kkn_period ?: '-',
            'Tema KKN' => $this->student->kkn_theme ?: '-',
            'Wilayah Penempatan' => $this->student->region?->fullPath() ?? '-',
            'DPL Pembimbing' => $this->student->advisors->pluck('name')->implode(', ') ?: '-',
        ];
    }

    /** @return array<string, int> label => jumlah */
    private function summary(): array
    {
        $byStatus = $this->logbooks->countBy('status');

        return [
            'Jumlah logbook' => $this->logbooks->count(),
            ...collect(Logbook::STATUS_LABELS)->mapWithKeys(fn (string $label, string $status) => ["Status {$label}" => $byStatus->get($status, 0)])->all(),
            'Logbook kelompok' => $this->logbooks->where('is_group', true)->count(),
            'Total warga terlibat' => (int) $this->logbooks->sum('community_count'),
            'Jumlah lokasi kegiatan' => $this->logbooks->pluck('location_id')->unique()->count(),
        ];
    }

    private function xlsx(): string
    {
        $styles = [0 => 'number', 1 => 'date', 9 => 'number', 10 => 'number', 11 => 'number', 19 => 'date'];

        $table = [array_map(fn (string $title) => ['value' => $title, 'style' => 'header'], self::COLUMNS)];
        foreach ($this->rows() as $row) {
            $table[] = array_map(fn ($value, int $column) => ['value' => $value, 'style' => $styles[$column] ?? 'text'], $row, array_keys($row));
        }

        $overview = [[['value' => 'Logbook Kegiatan KKN-PPM UGM', 'style' => 'title']], []];
        foreach ($this->identity() as $label => $value) {
            $overview[] = [['value' => $label, 'style' => 'label'], $value];
        }
        $overview[] = [];
        $overview[] = [['value' => 'Ringkasan', 'style' => 'label']];
        foreach ($this->summary() as $label => $value) {
            $overview[] = [$label, $value];
        }
        $overview[] = [];
        $overview[] = [['value' => 'Diunduh pada', 'style' => 'label'], now()->translatedFormat('d F Y H:i')];

        return (new SimpleXlsx)
            ->addSheet('Logbook', $table, ['widths' => self::COLUMN_WIDTHS, 'freezeHeader' => true, 'autoFilter' => true])
            ->addSheet('Ringkasan', $overview, ['widths' => [26, 60]])
            ->toString();
    }

    private function docx(): string
    {
        $document = (new SimpleDocx)
            ->heading('LOGBOOK KEGIATAN KKN-PPM')
            ->heading('UNIVERSITAS GADJAH MADA', 22)
            ->paragraph('', ['after' => 60]);

        $document->table(
            collect($this->identity())->map(fn (string $value, string $label) => [['text' => $label, 'bold' => true], ': '.$value])->values()->all(),
            [3000, SimpleDocx::CONTENT_WIDTH - 3000],
            ['borders' => false, 'size' => 20],
        );

        $summary = $this->summary();
        $document->paragraph('', ['after' => 60])->paragraph(sprintf(
            '%d logbook, %d disetujui, %d menunggu reviu, %d perlu revisi. Total warga terlibat %d orang di %d lokasi.',
            $summary['Jumlah logbook'], $summary['Status Disetujui'], $summary['Status Terkirim'], $summary['Status Perlu Revisi'],
            $summary['Total warga terlibat'], $summary['Jumlah lokasi kegiatan'],
        ), ['after' => 120]);

        $rows = [['No', 'Hari, Tanggal', 'Kegiatan', 'Lokasi', 'Warga', 'Uraian Kegiatan', 'Kondisi', 'Status dan Catatan Pembimbing']];
        foreach ($this->logbooks->values() as $index => $l) {
            $rows[] = [
                (string) ($index + 1),
                $l->log_date->translatedFormat('l, d F Y')."\n".'Pukul '.$l->log_date->format('H.i'),
                implode("\n", array_filter([
                    $l->program->name,
                    'Tema: '.$l->theme->name,
                    'Jenis: '.$l->activityType->name,
                    $l->is_group ? 'Logbook kelompok' : null,
                ])),
                $l->location->name,
                (string) $l->community_count,
                $l->progress_note.($l->documentation ? "\nDokumentasi: ".$l->documentation : ''),
                $l->health_status,
                $l->statusLabel().($l->latestReview?->note ? "\n".$l->latestReview->note : ''),
            ];
        }
        if ($this->logbooks->isEmpty()) {
            $rows[] = ['', 'Belum ada logbook.', '', '', '', '', '', ''];
        }

        // Jumlah lebar kolom = lebar area isi halaman.
        $document->table($rows, [560, 1900, 2700, 1700, 760, 4150, 1100, 1700], ['header' => true]);

        $advisor = $this->student->advisors->pluck('name')->first();
        $document->paragraph('', ['after' => 200])->table([
            ['Mengetahui,', now()->translatedFormat('d F Y')],
            ['Dosen Pembimbing Lapangan', 'Mahasiswa'],
            ["\n\n\n", "\n\n\n"],
            [$advisor ? "( {$advisor} )" : '( ..................................... )', "( {$this->student->name} )"],
        ], [intdiv(SimpleDocx::CONTENT_WIDTH, 2), intdiv(SimpleDocx::CONTENT_WIDTH, 2)], ['borders' => false, 'size' => 20]);

        return $document->paragraph('')->toString();
    }

    private function binary(string $contents, string $filename, string $contentType): Response
    {
        return response($contents, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($contents),
        ]);
    }
}
