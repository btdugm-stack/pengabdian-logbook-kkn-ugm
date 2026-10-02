<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Login
    |--------------------------------------------------------------------------
    |
    | Pengganti sementara Google OAuth untuk pengembangan lokal, sebelum
    | Client ID/Secret asli tersedia. Selalu dipaksa nonaktif di luar
    | environment "local" terlepas dari nilai DEMO_LOGIN_ENABLED - lihat
    | AuthController::demoLoginAllowed().
    |
    */
    'login_enabled' => (bool) env('DEMO_LOGIN_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Kode Akses Login Demo
    |--------------------------------------------------------------------------
    |
    | Bila diisi, login demo boleh aktif di environment mana pun (termasuk
    | production) tetapi wajib memakai kode ini dan HANYA untuk akun demo
    | (email @demo.kkn, lihat Student::isDemoAccount()). Kosong = perilaku
    | lama: login demo hanya hidup di local/testing.
    |
    */
    'access_code' => env('DEMO_LOGIN_CODE'),

];
