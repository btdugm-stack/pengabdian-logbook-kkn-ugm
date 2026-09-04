<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use App\Models\Location;
use App\Models\Program;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Data dropdown awal, dipindahkan langsung dari database.sql PoC lama
     * supaya alur "input baru -> masuk DB -> jadi dropdown" tetap punya
     * titik awal yang sama persis.
     */
    public function run(): void
    {
        foreach (['Pemberdayaan UMKM', 'Kesehatan Masyarakat', 'Pendidikan dan Literasi', 'Lingkungan', 'Digitalisasi Desa'] as $name) {
            Theme::create(['name' => $name]);
        }

        foreach (['Digitalisasi UMKM Desa', 'Sosialisasi Pencegahan Stunting', 'Literasi Digital Sekolah', 'Pendataan Potensi Desa', 'Pengelolaan Sampah Terpadu'] as $name) {
            Program::create(['name' => $name]);
        }

        foreach (['Program Individu', 'Program Kelompok', 'Program Bantu', 'Rapat Koordinasi', 'Monitoring Lapangan'] as $name) {
            ActivityType::create(['name' => $name]);
        }

        foreach ([
            ['Desa Candirejo, Sleman', -7.7956000, 110.3695000],
            ['Desa Wukirsari, Sleman', -7.8022000, 110.3921000],
            ['Desa Guwosari, Bantul', -7.8580000, 110.3180000],
            ['Desa Tirtonirmolo, Bantul', -7.8324000, 110.3541000],
            ['Desa Sumberadi, Sleman', -7.7608000, 110.3501000],
        ] as [$name, $lat, $lng]) {
            Location::create(['name' => $name, 'latitude' => $lat, 'longitude' => $lng]);
        }
    }
}
