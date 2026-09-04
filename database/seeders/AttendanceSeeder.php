<?php

namespace Database\Seeders;

use App\Models\AssistAttendance;
use App\Models\DailyAttendance;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Presensi harian demo untuk 4 hari terakhir + hari ini (kondisi bervariasi
     * supaya KPI & riwayat di dashboard/overview tidak kosong saat didemokan),
     * plus 2 presensi bantuan contoh (satu menunggu, satu disetujui).
     */
    public function run(): void
    {
        $students = Student::whereIn('email', [
            'azmi@student.demo', 'alya@student.demo', 'nadi@student.demo',
            'rafi@student.demo', 'dimas@student.demo', 'mira@student.demo',
        ])->get()->keyBy('email');

        // 4 hari terakhir: semua presensi Sehat, checked-in & checked-out.
        foreach (range(4, 1) as $daysAgo) {
            $date = today()->subDays($daysAgo);
            foreach ($students as $student) {
                DailyAttendance::create([
                    'student_id' => $student->id,
                    'attendance_date' => $date,
                    'check_in_time' => $date->copy()->setTime(7, 30),
                    'check_out_time' => $date->copy()->setTime(16, 0),
                    'condition' => 'Sehat',
                    'region_id' => $student->region_id,
                ]);
            }
        }

        // Hari ini: kondisi bervariasi, satu mahasiswa sengaja belum presensi
        // (nadi) supaya KPI "Belum Presensi" di overview tidak selalu nol.
        $today = [
            'azmi@student.demo' => 'Sehat',
            'alya@student.demo' => 'Sehat',
            'rafi@student.demo' => 'Izin',
            'dimas@student.demo' => 'Sehat',
            'mira@student.demo' => 'Sakit Berat',
        ];
        foreach ($today as $email => $condition) {
            $student = $students[$email];
            DailyAttendance::create([
                'student_id' => $student->id,
                'attendance_date' => today(),
                'check_in_time' => today()->copy()->setTime(7, 45),
                'condition' => $condition,
                'condition_note' => $condition !== 'Sehat' ? 'Catatan demo untuk kondisi '.$condition.'.' : null,
                'region_id' => $student->region_id,
            ]);
        }

        $program = Program::first();
        AssistAttendance::create([
            'helper_student_id' => $students['mira@student.demo']->id,
            'host_student_id' => $students['azmi@student.demo']->id,
            'program_id' => $program->id,
            'assist_date' => today()->subDay(),
            'hours' => 3,
            'role_note' => 'Bantu dokumentasi & logistik',
            'approval_status' => 'Disetujui',
        ]);
        AssistAttendance::create([
            'helper_student_id' => $students['dimas@student.demo']->id,
            'host_student_id' => $students['azmi@student.demo']->id,
            'program_id' => $program->id,
            'assist_date' => today(),
            'hours' => 2,
            'role_note' => 'Bantu narasumber pelatihan',
            'approval_status' => 'Menunggu',
        ]);
    }
}
