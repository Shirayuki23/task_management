<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/koneksi.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Koneksi database tidak tersedia.');
}
if (empty($_SESSION['user_id']) || !filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) || (int) $_SESSION['user_id'] <= 0) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
$user_id = (int) $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, 'SELECT id, nama, email, tanggal_lahir, jenis_kelamin, tempat_tinggal, no_telepon FROM users WHERE id = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    exit('Gagal menyiapkan data pengguna.');
}
mysqli_stmt_bind_param($stmt, 'i', $user_id);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    exit('Gagal mengambil data pengguna.');
}
$result = mysqli_stmt_get_result($stmt);
$current_user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
if (!$current_user) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
if (function_exists('sinkron_notifikasi')) {
    sinkron_notifikasi($conn, $user_id);
}
$jumlah_notifikasi_baru = 0;
$stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM notifikasi WHERE user_id = ? AND dibaca = 0');
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $jumlah_notifikasi_baru = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    }
    mysqli_stmt_close($stmt);
}