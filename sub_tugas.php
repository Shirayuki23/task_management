<?php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    header('Location: tugas.php');
    exit;
}

$tugas_id = (int) ($_POST['tugas_id'] ?? 0);
$aksi = $_POST['aksi'] ?? '';

$stmt = mysqli_prepare($conn, 'SELECT id FROM tugas WHERE id = ? AND user_id = ?');
if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memuat tugas.');
}
mysqli_stmt_bind_param($stmt, 'ii', $tugas_id, $user_id);
mysqli_stmt_execute($stmt);
$milik_user = (bool) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$milik_user) {
    header('Location: tugas.php');
    exit;
}

if ($aksi === 'tambah') {
    $isi = trim($_POST['isi'] ?? '');
    if ($isi !== '' && mb_strlen($isi) <= 255) {
        $stmt = mysqli_prepare($conn, 'INSERT INTO sub_tugas (tugas_id, user_id, isi) VALUES (?, ?, ?)');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'iis', $tugas_id, $user_id, $isi);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
} elseif ($aksi === 'toggle' || $aksi === 'hapus') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($aksi === 'toggle') {
        $stmt = mysqli_prepare($conn, 'UPDATE sub_tugas SET selesai = 1 - selesai WHERE id = ? AND tugas_id = ? AND user_id = ?');
    } else {
        $stmt = mysqli_prepare($conn, 'DELETE FROM sub_tugas WHERE id = ? AND tugas_id = ? AND user_id = ?');
    }
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iii', $id, $tugas_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

header('Location: detail_tugas.php?id=' . $tugas_id);
exit;
