<?php

namespace App\Notifications;

use App\Models\DailyAttendance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentHealthAlert extends Notification
{
    use Queueable;

    public function __construct(public DailyAttendance $attendance) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // database dulu: bila pengiriman email gagal, notifikasi di aplikasi sudah tersimpan.
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $student = $this->attendance->student;

        return (new MailMessage)
            ->subject("Eskalasi Kesehatan: {$student->name} - {$this->attendance->condition}")
            ->greeting("Halo {$notifiable->name},")
            ->line("{$student->name} ({$student->email}) melaporkan kondisi kesehatan **{$this->attendance->condition}** pada presensi hari ini.")
            ->when($this->attendance->condition_note, fn ($mail) => $mail->line("Catatan: {$this->attendance->condition_note}"))
            ->line('Wilayah: '.($student->region?->fullPath() ?? '-'))
            ->action('Lihat Detail Mahasiswa', route('overview.student', $student))
            ->line('Mohon segera ditindaklanjuti sesuai kebutuhan.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $student = $this->attendance->student;

        return [
            'attendance_id' => $this->attendance->id,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'condition' => $this->attendance->condition,
            'condition_note' => $this->attendance->condition_note,
            'region' => $student->region?->fullPath(),
        ];
    }
}
