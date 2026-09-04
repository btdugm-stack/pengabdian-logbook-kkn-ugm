<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Hierarki kabupaten > kecamatan > desa > sub_unit, contoh diambil dari
     * wilayah lokasi logbook demo PoC lama (database.sql). Sub-unit adalah
     * satuan terkecil yang nanti dipakai untuk scope akun kormasit/SHE.
     */
    public function run(): void
    {
        $tree = [
            'Kabupaten Sleman' => [
                'Kecamatan Cangkringan' => [
                    'Desa Wukirsari' => ['Sub-unit 12A'],
                ],
                'Kecamatan Mlati' => [
                    'Desa Sumberadi' => ['Sub-unit 8A'],
                ],
                'Kecamatan Ngaglik' => [
                    'Desa Candirejo' => ['Sub-unit 5A'],
                ],
            ],
            'Kabupaten Bantul' => [
                'Kecamatan Pajangan' => [
                    'Desa Guwosari' => ['Sub-unit 3A'],
                ],
                'Kecamatan Kasihan' => [
                    'Desa Tirtonirmolo' => ['Sub-unit 7A'],
                ],
            ],
        ];

        foreach ($tree as $kabupatenName => $kecamatans) {
            $kabupaten = Region::create(['level' => 'kabupaten', 'name' => $kabupatenName]);

            foreach ($kecamatans as $kecamatanName => $desas) {
                $kecamatan = Region::create([
                    'level' => 'kecamatan', 'name' => $kecamatanName, 'parent_id' => $kabupaten->id,
                ]);

                foreach ($desas as $desaName => $subUnits) {
                    $desa = Region::create([
                        'level' => 'desa', 'name' => $desaName, 'parent_id' => $kecamatan->id,
                    ]);

                    foreach ($subUnits as $subUnitName) {
                        Region::create([
                            'level' => 'sub_unit', 'name' => $subUnitName, 'parent_id' => $desa->id,
                        ]);
                    }
                }
            }
        }
    }
}
