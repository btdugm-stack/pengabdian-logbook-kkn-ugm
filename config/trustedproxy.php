<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Isi dengan IP reverse proxy / load balancer di depan aplikasi (pisahkan
    | dengan koma), atau "*" bila server hanya bisa dijangkau lewat proxy.
    | Tanpa ini, di belakang proxy HTTPS Laravel mengira request datang via
    | HTTP dan rate limit memakai IP proxy, bukan IP pengguna. Dibaca oleh
    | middleware TrustProxies bawaan Laravel.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
