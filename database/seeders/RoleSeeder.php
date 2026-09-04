<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Role hierarchy for Fase 2 (overview per-wilayah, presensi bantuan approval).
     * Seeded now so accounts can be assigned a role while the app still only
     * ships the mahasiswa-facing pages from the PoC.
     */
    public function run(): void
    {
        foreach (['mahasiswa', 'kormasit', 'korcam', 'dpl', 'admin_she', 'admin_lppm'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
