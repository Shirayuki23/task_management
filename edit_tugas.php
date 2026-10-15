<?php
require_once 'auth.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id = $id !== false && $id !== null && $id > 0 ? $id : 0;
if ($id === 0) {
    header('Location: tugas.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT * FROM tugas WHERE id = ? AND user_id = ?');
if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memuat tugas.');
}
mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
mysqli_stmt_execute($stmt);
$tugas = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$tugas) {
    header('Location: tugas.php');
    exit;
}

$edit = true;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        [$data, $error] = validasi_tugas($_POST);
        if ($error === '') {
            $stmt = mysqli_prepare($conn, 'UPDATE tugas SET judul = ?, mata_kuliah = ?, deskripsi = ?, deadline = ?, prioritas = ?, status = ? WHERE id = ? AND user_id = ?');
            if (!$stmt) {
                $error = 'Terjadi kesalahan saat menyiapkan pembaruan tugas.';
            } else {
                mysqli_stmt_bind_param($stmt, 'ssssssii', $data['judul'], $data['mata_kuliah'], $data['deskripsi'], $data['deadline'], $data['prioritas'], $data['status'], $id, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header('Location: detail_tugas.php?id=' . $id);
                    exit;
                }
                mysqli_stmt_close($stmt);
                $error = 'Tugas gagal diperbarui. Periksa koneksi database dan struktur tabel.';
            }
        }
    }
}

require 'form_tugas.php';
