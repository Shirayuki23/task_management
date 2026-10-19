<?php
// Kredensial OAuth Google untuk sinkronisasi Classroom.
// Cara isi: lihat README.txt bagian "SINKRONISASI GOOGLE CLASSROOM".
// Keduanya masih placeholder sampai kamu ganti dari Google Cloud Console.

if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', 'GANTI-DENGAN-YOU.apps.googleusercontent.com');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', 'GANTI-DENGAN-SECRET-MU');
}

function google_kredensial_siap() {
    return !str_contains(GOOGLE_CLIENT_ID, 'GANTI-DENGAN')
        && !str_contains(GOOGLE_CLIENT_SECRET, 'GANTI-DENGAN');
}

// URI callback harus didaftarkan persis sama di Google Cloud Console,
// baik versi localhost maupun versi hosting.
function google_redirect_uri() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/classroom_callback.php';
}
