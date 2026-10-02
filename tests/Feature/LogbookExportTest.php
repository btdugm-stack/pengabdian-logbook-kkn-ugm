<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Location;
use App\Models\Logbook;
use App\Models\Program;
use App\Models\Region;
use App\Models\Student;
use App\Models\Theme;
use Database\Seeders\RoleSeeder;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

class LogbookExportTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $region = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit A']);
        $this->student = $this->account('anna@mail.ugm.ac.id', 'mahasiswa', [
            'region_id' => $region->id, 'faculty' => 'Fakultas Teknik', 'study_program' => 'Teknik Sipil',
            'kkn_period' => 'Periode 2 Tahun 2026', 'kkn_theme' => 'Digitalisasi Desa',
        ]);
        $dpl = $this->account('dpl@ugm.ac.id', 'dpl');
        $dpl->advisees()->attach($this->student);

        $this->logbook($this->student, 'Rapat koordinasi & pendataan <awal>.', '2026-09-02 09:30', Logbook::STATUS_APPROVED);
        $revised = $this->logbook($this->student, "=HYPERLINK(\"http://jahat\")\nBaris kedua catatan.", '2026-09-01 14:00', Logbook::STATUS_REVISION);
        $revised->reviews()->create(['reviewer_id' => $dpl->id, 'decision' => Logbook::STATUS_REVISION, 'note' => 'Lengkapi jumlah warga.']);
    }

    /** @param  array<string, mixed>  $attributes */
    private function account(string $email, string $role, array $attributes = []): Student
    {
        $account = Student::create(['email' => $email, 'name' => 'Nama '.ucfirst(strtok($email, '@')), ...$attributes]);
        $account->assignRole($role);

        return $account;
    }

    private function logbook(Student $owner, string $note, string $date, string $status): Logbook
    {
        return $owner->logbooks()->create([
            'theme_id' => Theme::firstOrCreate(['name' => 'Digitalisasi Desa'])->id,
            'program_id' => Program::firstOrCreate(['name' => 'Pendataan Potensi Desa'])->id,
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Program Kelompok'])->id,
            'location_id' => Location::firstOrCreate(['name' => 'Balai Desa'], ['latitude' => -7.7712, 'longitude' => 110.3776])->id,
            'log_date' => $date,
            'kkn_period' => 'Periode 2 Tahun 2026',
            'community_count' => 12,
            'health_status' => 'Sehat',
            'progress_note' => $note,
            'status' => $status,
        ]);
    }

    /**
     * Buka berkas hasil unduhan sebagai zip dan kembalikan isi satu bagiannya,
     * sekaligus memastikan bagian itu XML yang sah (Excel/Word menolak yang tidak).
     */
    private function part(TestResponse $response, string $name): string
    {
        $path = tempnam(sys_get_temp_dir(), 'export');
        file_put_contents($path, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'Unduhan bukan berkas zip yang sah.');
        $xml = $zip->getFromName($name);
        $zip->close();
        unlink($path);

        $this->assertIsString($xml, "Bagian {$name} tidak ada di dalam berkas.");
        $this->assertTrue((new DOMDocument)->loadXML($xml), "Bagian {$name} bukan XML yang sah.");

        return $xml;
    }

    public function test_excel_download_has_a_filterable_logbook_sheet_and_a_summary_sheet(): void
    {
        $response = $this->actingAs($this->student)->get(route('logbooks.export', ['format' => 'xlsx']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload('logbook-kkn-nama-anna-'.now()->format('Y-m-d').'.xlsx');

        $workbook = $this->part($response, 'xl/workbook.xml');
        $this->assertStringContainsString('name="Logbook"', $workbook);
        $this->assertStringContainsString('name="Ringkasan"', $workbook);

        $sheet = $this->part($response, 'xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('<autoFilter ref="A1:T3"/>', $sheet);
        $this->assertStringContainsString('state="frozen"', $sheet);
        $this->assertStringContainsString('Catatan Pembimbing', $sheet);
        $this->assertStringContainsString('Lengkapi jumlah warga.', $sheet);
        $this->assertStringContainsString('Rapat koordinasi &amp; pendataan &lt;awal&gt;.', $sheet);
        // Kronologis: logbook 1 September lebih dulu daripada 2 September.
        $this->assertLessThan(strpos($sheet, 'Rapat koordinasi'), strpos($sheet, 'Baris kedua catatan.'));
        // Teks berawalan "=" tetap teks (inline string), bukan formula.
        $this->assertStringContainsString('t="inlineStr"><is><t xml:space="preserve">=HYPERLINK', $sheet);
        $this->assertStringNotContainsString('<f>', $sheet);

        $summary = $this->part($response, 'xl/worksheets/sheet2.xml');
        $this->assertStringContainsString('Periode 2 Tahun 2026', $summary);
        $this->assertStringContainsString('Nama Dpl', $summary);
        $this->assertStringContainsString('Total warga terlibat', $summary);
    }

    public function test_word_download_is_a_printable_report_with_identity_entries_and_signatures(): void
    {
        $response = $this->actingAs($this->student)->get(route('logbooks.export', ['format' => 'docx']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertDownload('logbook-kkn-nama-anna-'.now()->format('Y-m-d').'.docx');

        $document = $this->part($response, 'word/document.xml');
        foreach (['LOGBOOK KEGIATAN KKN-PPM', 'Fakultas Teknik', 'Teknik Sipil', 'Sub-unit A', 'Pendataan Potensi Desa', 'Baris kedua catatan.', 'Lengkapi jumlah warga.', 'Dosen Pembimbing Lapangan', '( Nama Dpl )', '( Nama Anna )'] as $text) {
            $this->assertStringContainsString($text, $document);
        }
        $this->assertStringContainsString('w:orient="landscape"', $document);
        $this->assertStringContainsString('<w:tblHeader/>', $document);

        $this->part($response, 'word/styles.xml');
        $this->part($response, '[Content_Types].xml');
    }

    public function test_csv_download_is_one_flat_row_per_logbook_with_iso_dates(): void
    {
        $csv = $this->actingAs($this->student)->get(route('logbooks.export', ['format' => 'csv']))
            ->assertOk()
            ->assertDownload('logbook-kkn-nama-anna-'.now()->format('Y-m-d').'.csv')
            ->streamedContent();

        $lines = array_map(fn (string $line) => str_getcsv($line, ',', '"', ''), preg_split('/\R(?=\d+,|$)/', trim(substr($csv, 3))));

        $this->assertSame(['No', 'Tanggal', 'Waktu', 'Periode KKN', 'Tema'], array_slice($lines[0], 0, 5));
        $this->assertCount(20, $lines[0]);
        $this->assertSame(['1', '2026-09-01', '14:00'], array_slice($lines[1], 0, 3));
        $this->assertSame(['2', '2026-09-02', '09:30'], array_slice($lines[2], 0, 3));
        // Awalan formula dinetralkan dengan apostrof.
        $this->assertStringStartsWith("'=HYPERLINK", $lines[1][14]);
    }

    public function test_download_contains_only_the_students_own_logbooks(): void
    {
        $other = $this->account('budi@mail.ugm.ac.id', 'mahasiswa');
        $this->logbook($other, 'Catatan milik Budi.', '2026-09-03 10:00', Logbook::STATUS_SUBMITTED);

        $sheet = $this->part($this->actingAs($this->student)->get(route('logbooks.export', ['format' => 'xlsx'])), 'xl/worksheets/sheet1.xml');
        $document = $this->part($this->actingAs($this->student)->get(route('logbooks.export', ['format' => 'docx'])), 'word/document.xml');

        $this->assertStringNotContainsString('Catatan milik Budi.', $sheet);
        $this->assertStringNotContainsString('Catatan milik Budi.', $document);
    }

    /** @return array<string, array{string}> */
    public static function formats(): array
    {
        return ['excel' => ['xlsx'], 'word' => ['docx'], 'csv' => ['csv']];
    }

    #[DataProvider('formats')]
    public function test_student_without_logbooks_still_gets_a_valid_file(string $format): void
    {
        $empty = $this->account('kosong@mail.ugm.ac.id', 'mahasiswa');

        $response = $this->actingAs($empty)->get(route('logbooks.export', ['format' => $format]))->assertOk();

        if ($format === 'xlsx') {
            $this->assertStringNotContainsString('<autoFilter ref="A1:T0"', $this->part($response, 'xl/worksheets/sheet1.xml'));
        } elseif ($format === 'docx') {
            $this->assertStringContainsString('Belum ada logbook.', $this->part($response, 'word/document.xml'));
        }
    }

    public function test_unknown_format_is_not_found_and_non_participants_are_forbidden(): void
    {
        $this->actingAs($this->student)->get(route('logbooks.export', ['format' => 'pdf']))->assertNotFound();
        $this->actingAs(Student::role('dpl')->sole())->get(route('logbooks.export', ['format' => 'xlsx']))->assertForbidden();
    }
}
