<?php

namespace Tests\Feature;

use App\Livewire\LogbookForm;
use App\Models\ActivityType;
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

class LogbookReviewTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    private Student $outsider;

    private Student $dpl;

    private Student $kormasit;

    /**
     * Kabupaten Uji > Sub-unit A (mahasiswa + kormasit); DPL membimbing
     * mahasiswa itu; "outsider" adalah mahasiswa yang bukan bimbingannya.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $kabupaten = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Uji']);
        $subUnit = Region::create(['level' => 'sub_unit', 'name' => 'Sub-unit A', 'parent_id' => $kabupaten->id]);
        $other = Region::create(['level' => 'kabupaten', 'name' => 'Kabupaten Lain']);

        $this->student = $this->account('mhs@mail.ugm.ac.id', 'mahasiswa', $subUnit);
        $this->outsider = $this->account('luar@mail.ugm.ac.id', 'mahasiswa', $other);
        $this->dpl = $this->account('dpl@ugm.ac.id', 'dpl', $kabupaten);
        $this->kormasit = $this->account('kormasit@mail.ugm.ac.id', 'kormasit', $subUnit);
        $this->dpl->advisees()->attach($this->student);
    }

    private function account(string $email, string $role, Region $region): Student
    {
        $account = Student::create(['email' => $email, 'name' => 'Akun '.$role.' '.strtok($email, '@'), 'region_id' => $region->id]);
        $account->assignRole($role);

        return $account;
    }

    private function logbook(Student $owner, string $status = Logbook::STATUS_SUBMITTED, string $note = 'Catatan kegiatan uji.'): Logbook
    {
        return $owner->logbooks()->create([
            'theme_id' => Theme::firstOrCreate(['name' => 'Tema Uji'])->id,
            'program_id' => Program::firstOrCreate(['name' => 'Program Uji'])->id,
            'activity_type_id' => ActivityType::firstOrCreate(['name' => 'Jenis Uji'])->id,
            'location_id' => Location::firstOrCreate(['name' => 'Lokasi Uji'])->id,
            'log_date' => now()->subHour(),
            'health_status' => 'Sehat',
            'progress_note' => $note,
            'personal_info' => 'Catatan pribadi untuk DPL.',
            'status' => $status,
        ]);
    }

    public function test_dpl_approves_a_submitted_logbook_without_a_note(): void
    {
        $logbook = $this->logbook($this->student);

        $this->actingAs($this->dpl)
            ->post(route('logbooks.reviews.store', $logbook), ['keputusan' => 'setujui'])
            ->assertRedirect(route('logbooks.reviews.index'));

        $this->assertSame(Logbook::STATUS_APPROVED, $logbook->fresh()->status);
        $this->assertDatabaseHas('logbook_reviews', [
            'logbook_id' => $logbook->id,
            'reviewer_id' => $this->dpl->id,
            'decision' => Logbook::STATUS_APPROVED,
            'note' => null,
        ]);
    }

    /** @return array<string, array{string}> */
    public static function decisionsNeedingANote(): array
    {
        return ['revisi' => ['revisi'], 'tolak' => ['tolak']];
    }

    #[DataProvider('decisionsNeedingANote')]
    public function test_revision_and_rejection_require_a_note(string $decision): void
    {
        $logbook = $this->logbook($this->student);

        $this->actingAs($this->dpl)
            ->post(route('logbooks.reviews.store', $logbook), ['keputusan' => $decision, 'catatan' => ''])
            ->assertSessionHasErrors('catatan');

        $this->assertSame(Logbook::STATUS_SUBMITTED, $logbook->fresh()->status);
        $this->assertDatabaseCount('logbook_reviews', 0);
    }

    public function test_unknown_decision_is_rejected(): void
    {
        $logbook = $this->logbook($this->student);

        $this->actingAs($this->dpl)
            ->post(route('logbooks.reviews.store', $logbook), ['keputusan' => 'Approved', 'catatan' => 'x'])
            ->assertSessionHasErrors('keputusan');

        $this->assertSame(Logbook::STATUS_SUBMITTED, $logbook->fresh()->status);
    }

    public function test_revision_goes_back_to_the_student_and_returns_to_the_queue_after_the_fix(): void
    {
        $logbook = $this->logbook($this->student);

        $this->actingAs($this->dpl)
            ->post(route('logbooks.reviews.store', $logbook), ['keputusan' => 'revisi', 'catatan' => 'Lengkapi jumlah warga yang terlibat.'])
            ->assertRedirect(route('logbooks.reviews.index'));
        $this->assertSame(Logbook::STATUS_REVISION, $logbook->fresh()->status);
        $this->actingAs($this->dpl)->get(route('logbooks.reviews.index'))->assertDontSee('Catatan kegiatan uji.');

        // Mahasiswa melihat lembar revisi, tidak bisa menghapus, lalu memperbaiki.
        $this->actingAs($this->student);
        $this->get(route('logbooks.index'))->assertSee('Perlu Revisi')->assertSee('Lengkapi jumlah warga yang terlibat.');
        $this->get(route('dashboard'))->assertSee('1 logbook perlu revisi');
        $this->delete(route('logbooks.destroy', $logbook))->assertForbidden();
        $this->get(route('logbooks.edit', $logbook))->assertOk()->assertSee('Lengkapi jumlah warga yang terlibat.')->assertSee('Kirim Perbaikan');

        Livewire::test(LogbookForm::class, ['logbook' => $logbook->fresh()])
            ->set('community_count', 40)
            ->set('progress_note', 'Catatan sudah diperbaiki.')
            ->call('save', 'draft')
            ->assertRedirect(route('logbooks.index'));

        $logbook->refresh();
        $this->assertSame(Logbook::STATUS_SUBMITTED, $logbook->status);
        $this->assertSame(40, $logbook->community_count);

        // Kembali ke antrean DPL, yang kini bisa menyetujui; riwayat menyimpan kedua keputusan.
        $this->actingAs($this->dpl)->get(route('logbooks.reviews.index'))->assertSee('Catatan sudah diperbaiki.');
        $this->actingAs($this->dpl)->post(route('logbooks.reviews.store', $logbook), ['keputusan' => 'setujui'])->assertRedirect();

        $this->assertSame(Logbook::STATUS_APPROVED, $logbook->fresh()->status);
        $this->assertSame(
            [Logbook::STATUS_REVISION, Logbook::STATUS_APPROVED],
            $logbook->reviews()->orderBy('id')->pluck('decision')->all(),
        );
    }

    public function test_rejection_is_final_for_student_and_reviewer(): void
    {
        $logbook = $this->logbook($this->student);

        $this->actingAs($this->dpl)
            ->post(route('logbooks.reviews.store', $logbook), ['keputusan' => 'tolak', 'catatan' => 'Bukan kegiatan KKN.'])
            ->assertRedirect(route('logbooks.reviews.index'));
        $this->assertSame(Logbook::STATUS_REJECTED, $logbook->fresh()->status);

        $this->actingAs($this->dpl)
            ->post(route('logbooks.reviews.store', $logbook), ['keputusan' => 'setujui'])
            ->assertForbidden();

        $this->actingAs($this->student);
        $this->get(route('logbooks.index'))->assertSee('Ditolak')->assertSee('Bukan kegiatan KKN.');
        $this->get(route('logbooks.edit', $logbook))->assertForbidden();
        $this->delete(route('logbooks.destroy', $logbook))->assertForbidden();

        $this->assertSame(Logbook::STATUS_REJECTED, $logbook->fresh()->status);
        $this->assertDatabaseCount('logbook_reviews', 1);
    }

    /** @return array<string, array{string, bool}> */
    public static function publicVisibility(): array
    {
        return [
            'menunggu reviu' => [Logbook::STATUS_SUBMITTED, true],
            'disetujui' => [Logbook::STATUS_APPROVED, true],
            'perlu revisi' => [Logbook::STATUS_REVISION, false],
            'ditolak' => [Logbook::STATUS_REJECTED, false],
            'draft' => [Logbook::STATUS_DRAFT, false],
        ];
    }

    #[DataProvider('publicVisibility')]
    public function test_only_submitted_and_approved_logbooks_are_public(string $status, bool $visible): void
    {
        $this->logbook($this->student, $status, 'Catatan untuk uji publik.');

        $response = $this->get(route('search.logbooks'))->assertOk();
        $visible ? $response->assertSee('Catatan untuk uji publik.') : $response->assertDontSee('Catatan untuk uji publik.');

        $this->get(route('public.home'))->assertOk()->assertViewHas('totalLogs', $visible ? 1 : 0);
    }

    public function test_queue_lists_only_submitted_logbooks_within_the_reviewers_scope(): void
    {
        $this->logbook($this->student, Logbook::STATUS_SUBMITTED, 'Dalam cakupan, menunggu.');
        $this->logbook($this->student, Logbook::STATUS_DRAFT, 'Masih draft.');
        $this->logbook($this->student, Logbook::STATUS_APPROVED, 'Sudah disetujui.');
        $this->logbook($this->outsider, Logbook::STATUS_SUBMITTED, 'Di luar cakupan.');

        $this->actingAs($this->dpl)->get(route('logbooks.reviews.index'))
            ->assertOk()
            ->assertSee('Dalam cakupan, menunggu.')
            ->assertDontSee('Masih draft.')
            ->assertDontSee('Sudah disetujui.')
            ->assertDontSee('Di luar cakupan.');
    }

    public function test_review_page_shows_the_private_note_and_review_form_to_the_dpl(): void
    {
        $logbook = $this->logbook($this->student);

        $this->actingAs($this->dpl)->get(route('logbooks.reviews.show', $logbook))
            ->assertOk()
            ->assertSee('Catatan pribadi untuk DPL.')
            ->assertSee('Minta Revisi');
    }

    public function test_non_reviewers_and_out_of_scope_reviewers_cannot_review(): void
    {
        $inScope = $this->logbook($this->student);
        $outOfScope = $this->logbook($this->outsider);
        $draft = $this->logbook($this->student, Logbook::STATUS_DRAFT);
        $payload = ['keputusan' => 'setujui'];

        // Kormasit memantau wilayahnya tetapi bukan reviewer; mahasiswa jelas bukan.
        foreach ([$this->kormasit, $this->student] as $user) {
            $this->actingAs($user)->get(route('logbooks.reviews.index'))->assertForbidden();
            $this->actingAs($user)->get(route('logbooks.reviews.show', $inScope))->assertForbidden();
            $this->actingAs($user)->post(route('logbooks.reviews.store', $inScope), $payload)->assertForbidden();
        }

        $this->actingAs($this->dpl)->get(route('logbooks.reviews.show', $outOfScope))->assertForbidden();
        $this->actingAs($this->dpl)->post(route('logbooks.reviews.store', $outOfScope), $payload)->assertForbidden();
        $this->actingAs($this->dpl)->get(route('logbooks.reviews.show', $draft))->assertForbidden();
        $this->actingAs($this->dpl)->post(route('logbooks.reviews.store', $draft), $payload)->assertForbidden();

        $this->assertSame(Logbook::STATUS_SUBMITTED, $inScope->fresh()->status);
        $this->assertSame(Logbook::STATUS_SUBMITTED, $outOfScope->fresh()->status);
        $this->assertSame(Logbook::STATUS_DRAFT, $draft->fresh()->status);
        $this->assertDatabaseCount('logbook_reviews', 0);
    }
}
