<?php

namespace App\Livewire;

use App\Models\AssistAttendance;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AssistAttendanceForm extends Component
{
    public string $host_student_id = '';

    public string $program_name = '';

    public string $program_new = '';

    public string $assist_date = '';

    public float $hours = 1;

    public string $role_note = '';

    public function mount(): void
    {
        $this->assist_date = today()->toDateString();
    }

    #[Computed]
    public function hostOptions()
    {
        return Student::role('mahasiswa')
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function programs()
    {
        return Program::orderBy('name')->get();
    }

    public function save()
    {
        $this->validate([
            'host_student_id' => 'required|exists:students,id',
            'assist_date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:24',
            'role_note' => 'nullable|string|max:255',
        ]);

        $programInput = trim($this->program_new) ?: $this->program_name;
        if (! $programInput) {
            $this->addError('program', 'Program kerja yang dibantu wajib diisi.');

            return;
        }

        $program = Program::firstOrCreateFromName($programInput);

        AssistAttendance::create([
            'helper_student_id' => Auth::id(),
            'host_student_id' => $this->host_student_id,
            'program_id' => $program->id,
            'assist_date' => $this->assist_date,
            'hours' => $this->hours,
            'role_note' => $this->role_note,
            'approval_status' => 'Menunggu',
        ]);

        session()->flash('flash_success', 'Presensi bantuan berhasil dicatat, menunggu persetujuan pemilik program.');

        return redirect()->route('assist-attendances.index');
    }

    public function render()
    {
        return view('livewire.assist-attendance-form')->extends('layouts.app');
    }
}
