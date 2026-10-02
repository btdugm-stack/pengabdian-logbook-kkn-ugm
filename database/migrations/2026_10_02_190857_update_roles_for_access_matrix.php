<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Matriks hak akses: tambah peran Fakultas dan Pimpinan, hapus Admin SHE.
     * Akun yang masih berperan Admin SHE dipindah ke Pimpinan - peran lihat-saja
     * yang hak aksesnya paling sempit di antara peran lintas wilayah.
     */
    public function up(): void
    {
        $roleId = function (string $name): int {
            $id = DB::table('roles')->where(['name' => $name, 'guard_name' => 'web'])->value('id');

            return (int) ($id ?? DB::table('roles')->insertGetId([
                'name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
            ]));
        };

        $roleId('fakultas');
        $pimpinan = $roleId('pimpinan');
        $adminShe = DB::table('roles')->where(['name' => 'admin_she', 'guard_name' => 'web'])->value('id');

        if ($adminShe) {
            DB::table('model_has_roles')->where('role_id', $adminShe)->update(['role_id' => $pimpinan]);
            DB::table('roles')->where('id', $adminShe)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Tidak dikembalikan: akun Admin SHE yang sudah dipindah tidak bisa dibedakan dari Pimpinan asli.
    }
};
