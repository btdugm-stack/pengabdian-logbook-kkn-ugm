<?php

namespace App\Livewire;

use App\Models\AssistAttendance;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AssistAttendanceList extends Component
{
    #[Computed]
    public function given()
    {
        return Auth::user()->assistAttendancesAsHelper()
            ->with(['host', 'program'])
            ->orderByDesc('assist_date')
            ->get();
    }

    #[Computed]
    public function pendingApproval()
    {
        return Auth::user()->assistAttendancesAsHost()
            ->where('approval_status', 'Menunggu')
            ->with(['helper', 'program'])
            ->orderByDesc('assist_date')
            ->get();
    }

    public function approve(int $assistAttendanceId): void
    {
        $this->decide($assistAttendanceId, 'Disetujui');
    }

    public function reject(int $assistAttendanceId): void
    {
        $this->decide($assistAttendanceId, 'Ditolak');
    }

    private function decide(int $assistAttendanceId, string $status): void
    {
        $assist = AssistAttendance::findOrFail($assistAttendanceId);

        // Hanya host (pemilik program yang dibantu) yang boleh menyetujui/menolak -
        // ditegakkan di sini, bukan cuma disembunyikan di tombol UI.
        abort_unless($assist->host_student_id === Auth::id(), 403);

        $assist->update(['approval_status' => $status]);

        unset($this->pendingApproval);
        session()->flash('flash_success', "Presensi bantuan telah {$status}.");
    }

    public function render()
    {
        return view('livewire.assist-attendance-list')->extends('layouts.app');
    }
}
