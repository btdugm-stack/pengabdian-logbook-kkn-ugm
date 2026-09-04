<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use App\Models\Location;
use App\Models\Logbook;
use App\Models\Program;
use App\Models\Student;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class LogbookSeeder extends Seeder
{
    /**
     * 7 entri demo dari database.sql PoC lama, dipetakan ke Eloquent model
     * lewat nama (bukan id hardcoded) supaya tidak bergantung urutan insert.
     */
    public function run(): void
    {
        $students = Student::whereIn('email', [
            'azmi@student.demo', 'alya@student.demo', 'nadi@student.demo',
            'rafi@student.demo', 'dimas@student.demo', 'mira@student.demo',
        ])->get()->keyBy('email');

        $theme = fn (string $name) => Theme::where('name', $name)->firstOrFail()->id;
        $program = fn (string $name) => Program::where('name', $name)->firstOrFail()->id;
        $type = fn (string $name) => ActivityType::where('name', $name)->firstOrFail()->id;
        $location = fn (string $name) => Location::where('name', $name)->firstOrFail()->id;

        $rows = [
            ['azmi@student.demo', 'Pemberdayaan UMKM', 'Digitalisasi UMKM Desa', 'Program Individu', 'Desa Candirejo, Sleman', 1, 27, 'Sehat', 'Pendampingan UMKM untuk membuat katalog produk dan simulasi promosi digital.', 'Kondisi baik. Perlu koordinasi lanjutan dengan perangkat desa.', 'Submitted'],
            ['azmi@student.demo', 'Digitalisasi Desa', 'Pendataan Potensi Desa', 'Rapat Koordinasi', 'Desa Wukirsari, Sleman', 2, 12, 'Sehat', 'Pendataan potensi desa belum lengkap karena sebagian warga belum tersedia.', 'Butuh jadwal ulang dengan kepala dusun.', 'Draft'],
            ['alya@student.demo', 'Pendidikan dan Literasi', 'Literasi Digital Sekolah', 'Program Kelompok', 'Desa Guwosari, Bantul', 3, 35, 'Sehat', 'Literasi digital untuk siswa SD tentang keamanan internet dan penggunaan aplikasi belajar.', 'Kegiatan berjalan lancar.', 'Reviewed'],
            ['nadi@student.demo', 'Kesehatan Masyarakat', 'Sosialisasi Pencegahan Stunting', 'Program Individu', 'Desa Tirtonirmolo, Bantul', 4, 42, 'Sakit Ringan', 'Sosialisasi PHBS dan pencegahan stunting bersama kader posyandu.', 'Istirahat sore karena kurang fit.', 'Submitted'],
            ['rafi@student.demo', 'Lingkungan', 'Pengelolaan Sampah Terpadu', 'Program Kelompok', 'Desa Wukirsari, Sleman', 5, 18, 'Sehat', 'Pelatihan pengelolaan sampah dan pemilahan organik non-organik.', 'Kegiatan dibantu karang taruna.', 'Reviewed'],
            ['dimas@student.demo', 'Pemberdayaan UMKM', 'Digitalisasi UMKM Desa', 'Program Individu', 'Desa Sumberadi, Sleman', 6, 21, 'Izin', 'Pendampingan branding produk tani lokal.', 'Izin setengah hari untuk urusan keluarga.', 'Submitted'],
            ['mira@student.demo', 'Kesehatan Masyarakat', 'Sosialisasi Pencegahan Stunting', 'Program Bantu', 'Desa Candirejo, Sleman', 7, 30, 'Sehat', 'Pendampingan kader kesehatan untuk pendataan balita.', 'Koordinasi baik dengan posyandu.', 'Reviewed'],
        ];

        foreach ($rows as [$email, $themeName, $programName, $typeName, $locationName, $daysAgo, $count, $health, $note, $personal, $status]) {
            Logbook::create([
                'student_id' => $students[$email]->id,
                'theme_id' => $theme($themeName),
                'program_id' => $program($programName),
                'activity_type_id' => $type($typeName),
                'location_id' => $location($locationName),
                'log_date' => now()->subDays($daysAgo),
                'community_count' => $count,
                'health_status' => $health,
                'progress_note' => $note,
                'personal_info' => $personal,
                'status' => $status,
            ]);
        }
    }
}
