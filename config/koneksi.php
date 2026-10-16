<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'task_management';
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa config/koneksi.php: host, user, password, dan nama database harus sesuai, dan database sudah diimport.');
}
if (!mysqli_set_charset($conn, 'utf8mb4')) {
    mysqli_close($conn);
    http_response_code(500);
    exit('Gagal mengatur karakter database. Periksa konfigurasi MySQL.');
}