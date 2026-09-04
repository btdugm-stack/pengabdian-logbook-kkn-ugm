<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * 6 mahasiswa demo dari PoC lama (role mahasiswa, region_id = sub-unit
     * penempatan mereka) plus satu akun contoh per peran supervisi lain,
     * untuk menguji RBAC di Fase 2. Untuk akun supervisi, region_id berarti
     * "wilayah yang diawasi", bukan penempatan pribadi.
     */
    public function run(): void
    {
        $subUnit = fn (string $name) => Region::where('level', 'sub_unit')->where('name', $name)->firstOrFail();
        $desa = fn (string $name) => Region::where('level', 'desa')->where('name', $name)->firstOrFail();
        $kabupaten = fn (string $name) => Region::where('level', 'kabupaten')->where('name', $name)->firstOrFail();

        $mahasiswa = [
            ['email' => 'azmi@student.demo', 'name' => 'M. Azmi', 'birth_place' => 'Bekasi', 'birth_date' => '1999-06-07', 'faculty' => 'Fakultas Teknik', 'study_program' => 'Teknik Informatika', 'phone' => '081234567890', 'emergency_contact' => 'Keluarga - 081111111111', 'region' => $subUnit('Sub-unit 5A')],
            ['email' => 'alya@student.demo', 'name' => 'Alya Putri', 'birth_place' => 'Yogyakarta', 'birth_date' => '2001-05-12', 'faculty' => 'Fakultas Ekonomika dan Bisnis', 'study_program' => 'Manajemen', 'phone' => '082222222222', 'emergency_contact' => 'Ibu - 082000000000', 'region' => $subUnit('Sub-unit 3A')],
            ['email' => 'nadi@student.demo', 'name' => 'Nadia Salsabila', 'birth_place' => 'Sleman', 'birth_date' => '2002-01-21', 'faculty' => 'Fakultas Kedokteran', 'study_program' => 'Kesehatan Masyarakat', 'phone' => '083333333333', 'emergency_contact' => 'Ayah - 083000000000', 'region' => $subUnit('Sub-unit 7A')],
            ['email' => 'rafi@student.demo', 'name' => 'Rafi Pratama', 'birth_place' => 'Bantul', 'birth_date' => '2001-08-19', 'faculty' => 'Fakultas Ilmu Budaya', 'study_program' => 'Sastra Indonesia', 'phone' => '084444444444', 'emergency_contact' => 'Ibu - 084000000000', 'region' => $subUnit('Sub-unit 12A')],
            ['email' => 'dimas@student.demo', 'name' => 'Dimas Arya', 'birth_place' => 'Semarang', 'birth_date' => '2000-12-03', 'faculty' => 'Fakultas Pertanian', 'study_program' => 'Agribisnis', 'phone' => '085555555555', 'emergency_contact' => 'Ayah - 085000000000', 'region' => $subUnit('Sub-unit 8A')],
            ['email' => 'mira@student.demo', 'name' => 'Mira Lestari', 'birth_place' => 'Magelang', 'birth_date' => '2002-03-14', 'faculty' => 'Fakultas Psikologi', 'study_program' => 'Psikologi', 'phone' => '086666666666', 'emergency_contact' => 'Kakak - 086000000000', 'region' => $subUnit('Sub-unit 5A')],
        ];

        foreach ($mahasiswa as $data) {
            $region = $data['region'];
            unset($data['region']);
            $student = Student::create([...$data, 'region_id' => $region->id]);
            $student->assignRole('mahasiswa');
        }

        $supervisors = [
            ['email' => 'kormasit.5a@demo.kkn', 'name' => 'Koordinator Mahasiswa Sub-unit 5A', 'role' => 'kormasit', 'region' => $subUnit('Sub-unit 5A')],
            ['email' => 'korcam.cangkringan@demo.kkn', 'name' => 'Koordinator Kecamatan Cangkringan', 'role' => 'korcam', 'region' => $desa('Desa Wukirsari')],
            ['email' => 'dpl@demo.kkn', 'name' => 'Dosen Pembimbing Lapangan Demo', 'role' => 'dpl', 'region' => $kabupaten('Kabupaten Sleman')],
            ['email' => 'she@demo.kkn', 'name' => 'Admin SHE Demo', 'role' => 'admin_she', 'region' => null],
            ['email' => 'lppm@demo.kkn', 'name' => 'Admin LPPM Demo', 'role' => 'admin_lppm', 'region' => null],
        ];

        foreach ($supervisors as $data) {
            $role = $data['role'];
            $region = $data['region'];
            $student = Student::create([
                'email' => $data['email'],
                'name' => $data['name'],
                'region_id' => $region?->id,
            ]);
            $student->assignRole($role);
        }
    }
}
