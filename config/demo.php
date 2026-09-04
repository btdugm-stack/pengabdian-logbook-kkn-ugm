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

];
