<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Support\ParticipantImporter;
use Illuminate\Database\Seeder;

class DemoAccountSeeder extends Seeder
{
    public const USER_EMAIL = 'mahasiswa.demo@demo.kkn';

    public const ADMIN_EMAIL = 'admin.demo@demo.kkn';

    public const DPL_EMAIL = 'dpl.demo@demo.kkn';

    private const REGION = 'Kabupaten Sleman / Kecamatan Ngaglik / Desa Candirejo / Sub-unit 5A';

    /**
     * Satu akun demo per peran, tanpa data contoh lain, untuk menguji fungsi
     * tiap peran lewat login demo. Cakupan supervisi sengaja bertingkat di
     * atas penempatan Mahasiswa Demo (sub-unit, kecamatan, bimbingan DPL, lalu
     * fakultasnya), jadi cakupan tiap peran bisa dibandingkan. Dijalankan terpisah:
     * `php artisan db:seed --class=DemoAccountSeeder`. Aman diulang karena
     * akun dicocokkan lewat email.
     */
    public function run(ParticipantImporter $importer): void
    {
        $this->call(RoleSeeder::class);

        $importer->import([
            ['email' => self::USER_EMAIL, 'nama' => 'Mahasiswa Demo', 'peran' => 'mahasiswa', 'wilayah' => self::REGION, 'fakultas' => 'Fakultas Teknik', 'prodi' => 'Teknik Informatika'],
            ['email' => 'kormasit.demo@demo.kkn', 'nama' => 'Kormasit Demo', 'peran' => 'kormasit', 'wilayah' => self::REGION, 'fakultas' => 'Fakultas Teknik'],
            ['email' => 'korcam.demo@demo.kkn', 'nama' => 'Korcam Demo', 'peran' => 'korcam', 'wilayah' => self::REGION, 'fakultas' => 'Fakultas Geografi'],
            ['email' => self::DPL_EMAIL, 'nama' => 'DPL Demo', 'peran' => 'dpl'],
            ['email' => 'fakultas.demo@demo.kkn', 'nama' => 'Fakultas Demo', 'peran' => 'fakultas', 'fakultas' => 'Fakultas Teknik'],
            ['email' => 'pimpinan.demo@demo.kkn', 'nama' => 'Pimpinan Demo', 'peran' => 'pimpinan'],
            ['email' => self::ADMIN_EMAIL, 'nama' => 'Admin LPPM Demo', 'peran' => 'admin_lppm'],
            ['email' => 'superadmin.demo@demo.kkn', 'nama' => 'Super Admin Demo', 'peran' => 'super_admin'],
        ]);

        Student::where('email', self::DPL_EMAIL)->sole()->advisees()->syncWithoutDetaching(
            Student::participants()->where('email', 'like', '%@'.Student::DEMO_EMAIL_DOMAIN)->pluck('id'),
        );
    }
}
