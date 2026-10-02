<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesCoordinate;
use App\Models\ActivityType;
use App\Models\Location;
use App\Models\Logbook;
use App\Models\Program;
use App\Models\Student;
use App\Models\Theme;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LogbookForm extends Component
{
    use HandlesCoordinate;

    /** Terisi saat mengedit draft atau logbook terkirim; null saat membuat logbook baru. */
    public ?Logbook $logbook = null;

    /**
     * Pemilik logbook. Sama dengan pengguna yang login, kecuali saat admin
     * menginput atau mengoreksi atas nama mahasiswa. Dikunci supaya tidak bisa
     * diganti dari browser.
     */
    #[Locked]
    public ?int $ownerId = null;

    /** Logbook kegiatan kelompok - hanya berlaku bila pemiliknya ketua kelompok. */
    public bool $is_group = false;

    public string $log_date = '';

    public string $theme_name = '';

    public string $theme_new = '';

    public string $program_name = '';

    public string $program_new = '';

    public string $activity_type_name = '';

    public string $activity_type_new = '';

    public string $location_name = '';

    public string $location_new = '';

    public string $coordinate = '';

    /** Input number yang dikosongkan bisa terhidrasi sebagai '' atau null - divalidasi lalu di-cast (int) saat simpan. */
    public int|string|null $community_count = 0;

    public string $progress_note = '';

    public string $personal_info = '';

    public string $documentation = '';

    public function mount(?Logbook $logbook = null, ?Student $owner = null): void
    {
        if (! $logbook?->exists) {
            // Tanpa $owner: logbook milik sendiri. Dengan $owner: admin atas nama mahasiswa.
            if ($owner?->exists && ! $owner->is(Auth::user())) {
                Gate::authorize('createFor', [Logbook::class, $owner]);
            }

            $this->ownerId = $owner?->exists ? $owner->id : Auth::id();
            $this->log_date = now()->format('Y-m-d\TH:i');
            // Tema KKN dari Data KKN pemilik menjadi isian awal; masih bisa diganti per logbook.
            $this->theme_name = (string) $this->owner->kkn_theme;

            return;
        }

        Gate::authorize('update', $logbook);

        $this->ownerId = $logbook->student_id;
        $this->is_group = (bool) $logbook->is_group;
        $this->logbook = $logbook;
        $this->log_date = $logbook->log_date->format('Y-m-d\TH:i');
        $this->theme_name = $logbook->theme->name;
        $this->program_name = $logbook->program->name;
        $this->activity_type_name = $logbook->activityType->name;
        $this->location_name = $logbook->location->name;
        $this->coordinate = $logbook->location->latitude
            ? "{$logbook->location->latitude}, {$logbook->location->longitude}"
            : '';
        $this->community_count = $logbook->community_count;
        $this->progress_note = $logbook->progress_note;
        $this->personal_info = (string) $logbook->personal_info;
        $this->documentation = (string) $logbook->documentation;
    }

    #[Computed]
    public function themes()
    {
        return Theme::orderBy('name')->get();
    }

    #[Computed]
    public function programs()
    {
        return Program::orderBy('name')->get();
    }

    #[Computed]
    public function activityTypes()
    {
        return ActivityType::orderBy('name')->get();
    }

    #[Computed]
    public function locations()
    {
        // Lokasi yang dinamai otomatis dari koordinat tidak ditawarkan lagi:
        // titik GPS tiap orang berbeda, daftarnya akan penuh entri sekali pakai.
        return Location::where('name', 'not like', Location::AUTO_NAME_PREFIX.'%')->orderBy('name')->get();
    }

    #[Computed]
    public function owner(): Student
    {
        return Student::findOrFail($this->ownerId);
    }

    /** Admin sedang mengisi atau mengoreksi logbook milik orang lain. */
    #[Computed]
    public function onBehalf(): bool
    {
        return $this->ownerId !== Auth::id();
    }

    #[Computed]
    public function todayAttendance()
    {
        return $this->owner->todayAttendance();
    }

    public function save(string $submitType)
    {
        $isEditing = $this->logbook !== null;

        if ($isEditing) {
            // Dicek ulang: logbook yang sama bisa sudah diputuskan pembimbing sejak form dibuka.
            Gate::authorize('update', $this->logbook);
        } elseif ($this->onBehalf) {
            // Dicek ulang di setiap simpan, bukan hanya saat form dibuka.
            Gate::authorize('createFor', [Logbook::class, $this->owner]);
        } elseif (! $this->todayAttendance) {
            // Kondisi kesehatan logbook baru dibersamai dari presensi harian
            // (audit §5.1) - tanpa presensi hari ini, submit diblok. Admin yang
            // mengisi atas nama mahasiswa tidak diblok: ia mengoreksi data lampau.
            $this->addError('attendance', 'Anda belum presensi hari ini. Presensi dulu sebelum mengisi logbook.');

            return;
        }

        // Logbook yang sudah pernah dikirim tidak ditarik kembali jadi draft:
        // koreksi atas yang menunggu reviu tetap menunggu reviu, dan perbaikan
        // atas yang diminta revisi dikirim ulang ke pembimbing.
        $wasRevision = $isEditing && $this->logbook->needsRevision();
        $keepsSubmitted = $isEditing && ! $this->logbook->isDraft();

        [$lat, $lng] = $this->parseCoordinate($this->coordinate);
        $hasCoordinate = $lat !== null && $lng !== null;

        // Tiap isian master datang dari satu kolom cari-pilih-tambah: nilai yang
        // diketik/dipilih ada di *_new, nilai lama saat mengedit ada di *_name.
        // Lokasi juga terpenuhi oleh koordinat saja (tombol "Lokasi saat ini"):
        // sebelumnya koordinat terisi tetap ditolak karena nama lokasi kosong.
        $this->validate([
            'log_date' => 'required|date|before_or_equal:now',
            'theme_name' => 'required_without:theme_new',
            'theme_new' => 'nullable|string|max:150',
            'program_name' => 'required_without:program_new',
            'program_new' => 'nullable|string|max:150',
            'activity_type_name' => 'required_without:activity_type_new',
            'activity_type_new' => 'nullable|string|max:150',
            'location_name' => [Rule::requiredIf(trim($this->location_new) === '' && ! $hasCoordinate)],
            'location_new' => 'nullable|string|max:150',
            'coordinate' => $this->coordinateRules(),
            'community_count' => 'nullable|integer|min:0|max:100000',
            'progress_note' => 'required|string|max:5000',
            'personal_info' => 'nullable|string|max:5000',
            'documentation' => 'nullable|string|max:255',
        ], [
            'theme_name.required_without' => 'Pilih tema dari daftar atau ketik tema baru.',
            'program_name.required_without' => 'Pilih program kerja dari daftar atau ketik program baru.',
            'activity_type_name.required_without' => 'Pilih jenis kegiatan dari daftar atau ketik jenis baru.',
            'location_name.required' => 'Pilih lokasi, ketik lokasi baru, atau isi koordinat dengan tombol "Lokasi saat ini".',
            'progress_note.required' => 'Catatan kegiatan belum diisi.',
        ]);

        $themeInput = trim($this->theme_new) ?: $this->theme_name;
        $programInput = trim($this->program_new) ?: $this->program_name;
        $typeInput = trim($this->activity_type_new) ?: $this->activity_type_name;
        $locationInput = trim($this->location_new) ?: $this->location_name;

        $location = $locationInput !== ''
            ? Location::firstOrCreateWithCoordinates($locationInput, $lat, $lng)
            : Location::nearOrCreateFromCoordinates($lat, $lng);

        $attributes = [
            'theme_id' => Theme::firstOrCreateFromName($themeInput)->id,
            'program_id' => Program::firstOrCreateFromName($programInput)->id,
            'activity_type_id' => ActivityType::firstOrCreateFromName($typeInput)->id,
            'location_id' => $location->id,
            'log_date' => $this->log_date,
            'community_count' => (int) $this->community_count,
            'progress_note' => $this->progress_note,
            'personal_info' => $this->personal_info,
            'documentation' => $this->documentation,
            'status' => $keepsSubmitted || $submitType !== 'draft' ? Logbook::STATUS_SUBMITTED : Logbook::STATUS_DRAFT,
            // Hanya ketua kelompok yang punya logbook kelompok, apa pun yang dikirim form.
            'is_group' => $this->is_group && $this->owner->isGroupLeader(),
        ];

        if ($this->onBehalf) {
            $attributes['entered_by'] = Auth::id();
        }

        if ($isEditing) {
            // Kondisi kesehatan tetap milik hari logbook itu dibuat.
            $this->logbook->update($attributes);
        } else {
            $this->owner->logbooks()->create([
                ...$attributes,
                'health_status' => $this->healthStatusForNewLogbook(),
                // Periode yang berlaku saat logbook dibuat; koreksi di Data KKN tidak mengubah logbook lama.
                'kkn_period' => $this->owner->kkn_period,
            ]);
        }

        $message = match (true) {
            $wasRevision => 'Perbaikan dikirim ke pembimbing untuk direviu ulang.',
            $keepsSubmitted => 'Perubahan logbook disimpan.',
            $submitType === 'draft' => 'Draft logbook disimpan. Kamu masih bisa melanjutkannya dari menu Logbook Saya.',
            default => 'Logbook berhasil dikirim.',
        };

        // Lokasi dipilihkan sistem dari koordinat: sebutkan hasilnya supaya bisa dikoreksi.
        if ($locationInput === '') {
            $message .= " Lokasi dicatat sebagai \"{$location->name}\".";
        }

        if ($this->onBehalf) {
            session()->flash('flash_success', "Logbook atas nama {$this->owner->name} disimpan.");

            return redirect()->route('overview.student', $this->owner);
        }

        session()->flash('flash_success', $message);

        return redirect()->route('logbooks.index');
    }

    /**
     * Kondisi kesehatan logbook baru: dari presensi pemilik. Untuk input atas
     * nama mahasiswa, diambil dari presensi pada tanggal kegiatannya; bila hari
     * itu tidak ada presensi, dicatat apa adanya sebagai tidak tercatat.
     */
    private function healthStatusForNewLogbook(): string
    {
        if (! $this->onBehalf) {
            return $this->todayAttendance->condition;
        }

        return $this->owner->dailyAttendances()
            ->whereDate('attendance_date', Carbon::parse($this->log_date))
            ->value('condition') ?? Logbook::HEALTH_UNKNOWN;
    }

    /**
     * Label isian yang masih bermasalah, urut sesuai posisinya di form -
     * dipakai ringkasan di atas form supaya pengguna tahu apa saja yang
     * perlu dilengkapi tanpa mencari pesan merah satu per satu.
     *
     * @return array<string, string> key = id elemen form
     */
    #[Computed]
    public function invalidFields(): array
    {
        $labels = [
            'log_date' => 'Tanggal & Waktu Kegiatan',
            'community_count' => 'Jumlah Warga Terlibat',
            'theme_name' => 'Tema',
            'theme_new' => 'Tema',
            'program_name' => 'Program Kerja',
            'program_new' => 'Program Kerja',
            'activity_type_name' => 'Jenis Kegiatan',
            'activity_type_new' => 'Jenis Kegiatan',
            'location_name' => 'Lokasi',
            'location_new' => 'Lokasi',
            'coordinate' => 'Koordinat Lokasi',
            'progress_note' => 'Progress / Catatan Kegiatan',
            'personal_info' => 'Catatan Pribadi',
            'documentation' => 'Tautan Dokumentasi',
        ];

        $invalid = [];

        foreach (array_intersect_key($labels, $this->getErrorBag()->toArray()) as $property => $label) {
            // Dua properti master (_name dan _new) diisi dari satu kolom yang id-nya *_name.
            $invalid[str_ends_with($property, '_new') ? substr($property, 0, -4).'_name' : $property] ??= $label;
        }

        return $invalid;
    }

    public function render()
    {
        return view('livewire.logbook-form');
    }
}
