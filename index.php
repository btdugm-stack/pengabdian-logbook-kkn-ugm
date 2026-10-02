<?php

/*
 * Front controller untuk pemasangan di subfolder DocumentRoot (lihat .htaccess).
 * Bila request masuk lewat public/index.php, SCRIPT_NAME memuat "/public" dan
 * Laravel gagal mengenali base URL folder ini sehingga semua route 404.
 */

/*
 * Reverse proxy publik memetakan prefix lain ke folder ini (mis.
 * https://search.ugm.ac.id/ai/logbook-kkn -> /search/logbook-kkn) tanpa
 * mengirim X-Forwarded-Prefix. Tanpa penyesuaian ini semua URL yang dibuat
 * Laravel (aset, form, redirect, pagination) memakai prefix internal dan
 * jatuh ke aplikasi lain di proxy. Prefix publik diset di .htaccess.
 */
$publicPrefix = $_SERVER['APP_PUBLIC_PREFIX'] ?? $_SERVER['REDIRECT_APP_PUBLIC_PREFIX'] ?? '';

if ($publicPrefix !== '') {
    $internalPrefix = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

    if (str_starts_with($_SERVER['REQUEST_URI'], $internalPrefix)) {
        $_SERVER['REQUEST_URI'] = $publicPrefix.substr($_SERVER['REQUEST_URI'], strlen($internalPrefix));
    }

    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $publicPrefix.'/index.php';
}

/*
 * URL folder tanpa "/" di akhir tidak dikenali Laravel sebagai halaman utama.
 * Apache biasanya menambahkannya sendiri, tapi memakai prefix internal (lihat
 * DirectorySlash Off di .htaccess), jadi pengalihannya dilakukan di sini.
 */
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
[$requestPath, $queryString] = array_pad(explode('?', $_SERVER['REQUEST_URI'], 2), 2, null);

if ($basePath !== '' && $requestPath === $basePath) {
    header('Location: '.$basePath.'/'.($queryString !== null ? '?'.$queryString : ''), true, 301);

    exit;
}

require __DIR__.'/public/index.php';
