<?php

namespace App\Livewire;

use App\Models\DailyAttendance;
use App\Models\Student;
use App\Notifications\StudentHealthAlert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AttendanceCheckIn extends Component
{
    public string $condition = 'Sehat';

    public string $condition_note = '';

    public string $coordinate = '';

    public function mount(): void
    {
        if ($today = $this->today) {
            $this->condition = $today->condition;
            $this->condition_note = (string) $today->condition_note;
            $this->coordinate = $today->check_in_lat
                ? "{$today->check_in_lat}, {$today->check_in_lng}"
                : '';
        }
    }

    #[Computed]
    public function today(): ?DailyAttendance
    {
        return Auth::user()->todayAttendance();
    }

    #[Computed]
    public function history()
    {
        return Auth::user()->dailyAttendances()
            ->orderByDesc('attendance_date')
            ->limit(14)
            ->get();
    }

    public function checkIn(): void
    {
        $this->validate([
            'condition' => 'required|in:Sehat,Sakit Ringan,Sakit Berat,Izin,Alpha',
            'condition_note' => $this->condition === 'Sehat' ? 'nullable|string' : 'required|string',
        ]);

        [$lat, $lng] = $this->parseCoordinate($this->coordinate);
        $student = Auth::user();
        // Dicari lewat whereDate(), bukan updateOrCreate([...]) dengan string
        // tanggal mentah - kolom `date` di SQLite/MySQL bisa tersimpan dengan
        // format berbeda dari yang kita kirim, jadi pencocokan string persis
        // tidak reliable untuk menemukan baris hari ini.
        $attendance = $student->todayAttendance();
        $isNew = ! $attendance;
        $previousCondition = $attendance?->getOriginal('condition');
        $attendance ??= new DailyAttendance([
            'student_id' => $student->id,
            'attendance_date' => today(),
        ]);

        $attendance->fill([
            'check_in_time' => $attendance->check_in_time ?? now(),
            'check_in_lat' => $lat,
            'check_in_lng' => $lng,
            'condition' => $this->condition,
            'condition_note' => $this->condition_note ?: null,
            'region_id' => $student->region_id,
        ])->save();

        // Kirim eskalasi hanya saat kondisi BARU jadi sakit (bukan berulang
        // setiap kali form diperbarui dengan kondisi sakit yang sama).
        $isSick = in_array($this->condition, ['Sakit Ringan', 'Sakit Berat'], true);
        if ($isSick && $this->condition !== $previousCondition) {
            $recipients = Student::supervisorsFor($student);
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new StudentHealthAlert($attendance));
            }
        }

        unset($this->today, $this->history);
        session()->flash('flash_success', $isNew ? 'Presensi berhasil dicatat.' : 'Presensi hari ini diperbarui.');
    }

    public function checkOut(): void
    {
        $today = $this->today;
        abort_unless($today, 404);

        $today->update(['check_out_time' => now()]);

        unset($this->today, $this->history);
        session()->flash('flash_success', 'Check-out berhasil dicatat.');
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
        // ->extends() (bukan atribut #[Layout]) supaya konten masuk ke
        // @yield('content') layout klasik kita, bukan mode slot Livewire.
        return view('livewire.attendance-check-in')->extends('layouts.app');
    }
}
