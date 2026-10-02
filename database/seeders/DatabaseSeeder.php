<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // Data demo (wilayah contoh, akun *.demo, logbook & presensi contoh) tidak
        // boleh masuk DB produksi. Akun asli diimpor lewat `php artisan kkn:import-peserta`.
        if (app()->isProduction()) {
            return;
        }

        $this->call([
            RegionSeeder::class,
            MasterDataSeeder::class,
            StudentSeeder::class,
            LogbookSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
