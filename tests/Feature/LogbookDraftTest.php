<?php

namespace Tests\Feature;

use App\Livewire\LogbookForm;
use App\Models\ActivityType;
use App\Models\DailyAttendance;
use App\Models\Location;
use App\Models\Logbook;
use App\Models\Program;
use App\Models\Region;
use App\Models\Student;
use App\Models\Theme;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LogbookDraftTest extends TestCase
{
    use RefreshDatabase;

    private function mahasiswa(string $email = 'azmi@student.demo'): Student
    {
        $this->seed(RoleSeeder::class);
        $region = Region::firstOrCreate(['parent_id' => null, 'name' => 'Kabupaten Uji'], ['level' => 'kabupaten']);
        $student = Student::create(['email' => $email, 'name' => 'Mahasiswa '.$email, 'region_id' => $region->id]);
        $student->assignRole('mahasiswa');

        return $student;
    }

    private function logbookFor(Student $student, string $status, string $note = 'Catatan uji.', string $locationName = 'Lokasi Uji'): Logbook
    {
        return $student->logbooks()->create([
            'theme_id' => Theme::firstOrCreate(['name' => 'Tema Uji'])->id,
            'program_id' => Program::firstOrCreate(['name' => 'Program Uji'])->id,
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Jenis Uji'])->id,
            'location_id' => Location::firstOrCreate(['name' => $locationName], ['latitude' => -7.7956, 'longitude' => 110.3695])->id,
            'log_date' => now()->subDay(),
            'health_status' => 'Sakit Ringan',
            'progress_note' => $note,
            'status' => $status,
        ]);
    }

    public function test_owner_can_open_their_draft_for_editing(): void
    {
        $student = $this->mahasiswa();
        $draft = $this->logbookFor($student, Logbook::STATUS_DRAFT);

        $this->actingAs($student)->get(route('logbooks.edit', $draft))
            ->assertOk()
            ->assertSee('Lanjutkan Draft Logbook');
    }

    public function test_owner_can_submit_a_draft_without_todays_attendance(): void
    {
        $student = $this->mahasiswa();
        $draft = $this->logbookFor($student, Logbook::STATUS_DRAFT);
        $this->actingAs($student);

        Livewire::test(LogbookForm::class, ['logbook' => $draft])
            ->set('progress_note', 'Catatan diperbarui.')
            ->call('save', 'submit')
            ->assertRedirect(route('logbooks.index'));

        $draft->refresh();
        $this->assertDatabaseCount('logbooks', 1);
        $this->assertSame(Logbook::STATUS_SUBMITTED, $draft->status);
        $this->assertSame('Catatan diperbarui.', $draft->progress_note);
        // Kondisi kesehatan tetap milik hari draft dibuat.
        $this->assertSame('Sakit Ringan', $draft->health_status);
    }

    public function test_owner_can_correct_a_submitted_logbook_and_it_stays_submitted(): void
    {
        $student = $this->mahasiswa();
        $submitted = $this->logbookFor($student, Logbook::STATUS_SUBMITTED);
        $this->actingAs($student);

        $this->get(route('logbooks.edit', $submitted))->assertOk()->assertSee('Simpan Perubahan')->assertDontSee('Simpan Draft');

        // Aksi 'draft' tidak boleh menarik logbook terkirim kembali jadi draft.
        Livewire::test(LogbookForm::class, ['logbook' => $submitted])
            ->set('progress_note', 'Catatan dikoreksi.')
            ->set('community_count', 25)
            ->call('save', 'draft')
            ->assertRedirect(route('logbooks.index'));

        $submitted->refresh();
        $this->assertSame(Logbook::STATUS_SUBMITTED, $submitted->status);
        $this->assertSame('Catatan dikoreksi.', $submitted->progress_note);
        $this->assertSame(25, $submitted->community_count);
        $this->assertSame('Sakit Ringan', $submitted->health_status);
        $this->assertDatabaseCount('logbooks', 1);
    }

    /** @return array<string, array{string}> */
    public static function finalStatuses(): array
    {
        return [
            'disetujui' => [Logbook::STATUS_APPROVED],
            'ditolak' => [Logbook::STATUS_REJECTED],
        ];
    }

    #[DataProvider('finalStatuses')]
    public function test_decided_logbook_is_locked_for_its_owner(string $status): void
    {
        $student = $this->mahasiswa();
        $decided = $this->logbookFor($student, $status);
        $this->actingAs($student);

        $this->get(route('logbooks.edit', $decided))->assertForbidden();
        $this->delete(route('logbooks.destroy', $decided))->assertForbidden();
        $this->get(route('logbooks.index'))->assertOk()->assertSee('Terkunci');

        $this->assertModelExists($decided);
    }

    public function test_form_opened_before_approval_cannot_save_after_approval(): void
    {
        $student = $this->mahasiswa();
        $submitted = $this->logbookFor($student, Logbook::STATUS_SUBMITTED);
        $this->actingAs($student);
        $form = Livewire::test(LogbookForm::class, ['logbook' => $submitted]);

        $submitted->update(['status' => Logbook::STATUS_APPROVED]);

        $form->set('progress_note', 'Diubah setelah disetujui.')->call('save', 'submit')->assertForbidden();

        $this->assertSame('Catatan uji.', $submitted->fresh()->progress_note);
    }

    public function test_another_students_draft_is_not_found(): void
    {
        $owner = $this->mahasiswa('pemilik@student.demo');
        $intruder = $this->mahasiswa('lain@student.demo');
        $draft = $this->logbookFor($owner, Logbook::STATUS_DRAFT);

        $this->actingAs($intruder)->get(route('logbooks.edit', $draft))->assertNotFound();
        $this->actingAs($intruder)->delete(route('logbooks.destroy', $draft))->assertNotFound();

        $this->assertModelExists($draft);
    }

    public function test_owner_can_delete_their_draft(): void
    {
        $student = $this->mahasiswa();
        $draft = $this->logbookFor($student, Logbook::STATUS_DRAFT);

        $this->actingAs($student)->delete(route('logbooks.destroy', $draft))
            ->assertRedirect(route('logbooks.index'));

        $this->assertModelMissing($draft);
    }

    public function test_owner_can_delete_a_submitted_logbook(): void
    {
        $student = $this->mahasiswa();
        $submitted = $this->logbookFor($student, Logbook::STATUS_SUBMITTED);

        $this->actingAs($student)->delete(route('logbooks.destroy', $submitted))
            ->assertRedirect(route('logbooks.index'));

        $this->assertModelMissing($submitted);
    }

    public function test_drafts_are_hidden_from_public_search_and_public_map(): void
    {
        $student = $this->mahasiswa();
        $this->logbookFor($student, Logbook::STATUS_SUBMITTED, 'Kegiatan sudah terkirim.', 'Balai Desa Terkirim');
        $this->logbookFor($student, Logbook::STATUS_DRAFT, 'Draf belum dikirim.', 'Posyandu Draf');

        $this->get(route('search.logbooks'))
            ->assertSee('Kegiatan sudah terkirim.')
            ->assertDontSee('Draf belum dikirim.');

        $this->get(route('map.public'))
            ->assertSee('Balai Desa Terkirim')
            ->assertDontSee('Posyandu Draf');
    }

    public function test_new_logbook_rejects_invalid_coordinate_and_future_date(): void
    {
        $student = $this->mahasiswa();
        DailyAttendance::create([
            'student_id' => $student->id,
            'attendance_date' => today(),
            'check_in_time' => now(),
            'condition' => 'Sehat',
        ]);
        $this->actingAs($student);

        Livewire::test(LogbookForm::class)
            ->set('log_date', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('theme_new', 'Tema')
            ->set('program_new', 'Program')
            ->set('activity_type_new', 'Jenis')
            ->set('location_new', 'Lokasi')
            ->set('coordinate', 'di balai desa')
            ->set('progress_note', 'Catatan.')
            ->call('save', 'submit')
            ->assertHasErrors(['log_date', 'coordinate']);

        $this->assertDatabaseCount('logbooks', 0);
    }
}
