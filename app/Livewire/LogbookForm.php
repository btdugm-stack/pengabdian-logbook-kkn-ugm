<?php

namespace App\Livewire;

use App\Models\ActivityType;
use App\Models\Location;
use App\Models\Program;
use App\Models\Theme;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LogbookForm extends Component
{
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

    public int $community_count = 0;

    public string $progress_note = '';

    public string $personal_info = '';

    public string $documentation = '';

    public function mount(): void
    {
        $this->log_date = now()->format('Y-m-d\TH:i');
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
        return Location::orderBy('name')->get();
    }

    #[Computed]
    public function todayAttendance()
    {
        return Auth::user()->todayAttendance();
    }

    public function save(string $submitType)
    {
        // Kondisi kesehatan tidak lagi diinput di form ini - dibersamai dengan
        // presensi harian (audit §5.1). Tanpa presensi hari ini, submit diblok.
        if (! $this->todayAttendance) {
            $this->addError('attendance', 'Anda belum presensi hari ini. Presensi dulu sebelum mengisi logbook.');

            return;
        }

        $this->validate([
            'log_date' => 'required|date',
            'progress_note' => 'required|string',
            'community_count' => 'nullable|integer|min:0',
        ]);

        $themeInput = trim($this->theme_new) ?: $this->theme_name;
        $programInput = trim($this->program_new) ?: $this->program_name;
        $typeInput = trim($this->activity_type_new) ?: $this->activity_type_name;
        $locationInput = trim($this->location_new) ?: $this->location_name;

        if (! $themeInput || ! $programInput || ! $typeInput || ! $locationInput) {
            $this->addError('master', 'Tema, program kerja, jenis kegiatan, dan lokasi wajib diisi.');

            return;
        }

        [$lat, $lng] = $this->parseCoordinate($this->coordinate);

        $theme = Theme::firstOrCreateFromName($themeInput);
        $program = Program::firstOrCreateFromName($programInput);
        $activityType = ActivityType::firstOrCreateFromName($typeInput);
        $location = Location::firstOrCreateWithCoordinates($locationInput, $lat, $lng);

        Auth::user()->logbooks()->create([
            'theme_id' => $theme->id,
            'program_id' => $program->id,
            'activity_type_id' => $activityType->id,
            'location_id' => $location->id,
            'log_date' => $this->log_date,
            'community_count' => $this->community_count ?: 0,
            'health_status' => $this->todayAttendance->condition,
            'progress_note' => $this->progress_note,
            'personal_info' => $this->personal_info,
            'documentation' => $this->documentation,
            'status' => $submitType === 'draft' ? 'Draft' : 'Submitted',
        ]);

        session()->flash('flash_success', 'Logbook berhasil disimpan. Input baru masuk DB dan akan muncul sebagai pilihan dropdown berikutnya.');

        return redirect()->route('dashboard');
    }

    /** @return array{0: ?float, 1: ?float} */
    private function parseCoordinate(string $coordinate): array
    {
        $coordinate = trim($coordinate);
        if ($coordinate === '') {
            return [null, null];
        }

        $parts = array_map('trim', explode(',', $coordinate));
        if (count($parts) !== 2) {
            return [null, null];
        }

        return [
            is_numeric($parts[0]) ? (float) $parts[0] : null,
            is_numeric($parts[1]) ? (float) $parts[1] : null,
        ];
    }

    public function render()
    {
        return view('livewire.logbook-form');
    }
}
