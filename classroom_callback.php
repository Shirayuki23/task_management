<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/classroom.php';

function kembali($query) {
    header('Location: classroom.php' . ($query !== '' ? '?' . $query : ''));
    exit;
}

if (empty($_SESSION['user_id']) || !($conn instanceof mysqli)) {
    header('Location: login.php');
    exit;
}
$user_id = (int) $_SESSION['user_id'];

$state = (string) ($_GET['state'] ?? '');
if ($state === '' || empty($_SESSION['gc_state']) || !hash_equals((string) $_SESSION['gc_state'], $state)) {
    unset($_SESSION['gc_state']);
    kembali('galat=state');
}
unset($_SESSION['gc_state']);

if (isset($_GET['error'])) {
    kembali('galat=ditolak');
}

$code = (string) ($_GET['code'] ?? '');
if ($code === '') {
    kembali('galat=kode');
}

[$token, $err] = google_tukar_code($code);
if ($err !== '' || empty($token['refresh_token'])) {
    $_SESSION['classroom_success'] = '';
    header('Location: classroom.php?galat=token');
    exit;
}

$email = google_email_dari_id_token((string) ($token['id_token'] ?? ''));

$stmt = mysqli_prepare($conn, 'UPDATE users SET google_email = ?, google_refresh_token = ? WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'ssi', $email, $token['refresh_token'], $user_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    header('Location: classroom.php?galat=simpan');
    exit;
}

$_SESSION['classroom_success'] = 'Akun Google ' . ($email !== '' ? '(' . $email . ') ' : '') . 'berhasil dihubungkan. Sekarang klik Sinkronkan.';
kembali('');
