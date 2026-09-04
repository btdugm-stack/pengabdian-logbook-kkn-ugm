<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            RegionSeeder::class,
            MasterDataSeeder::class,
            StudentSeeder::class,
            LogbookSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
