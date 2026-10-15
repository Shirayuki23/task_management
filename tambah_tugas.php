<?php
require_once 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        [$data, $error] = validasi_tugas($_POST);
        if ($error === '') {
            $stmt = mysqli_prepare($conn, 'INSERT INTO tugas (user_id, judul, mata_kuliah, deskripsi, deadline, prioritas, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            if (!$stmt) {
                $error = 'Terjadi kesalahan saat menyimpan tugas.';
            } else {
                mysqli_stmt_bind_param($stmt, 'issssss', $user_id, $data['judul'], $data['mata_kuliah'], $data['deskripsi'], $data['deadline'], $data['prioritas'], $data['status']);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header('Location: tugas.php');
                    exit;
                }
                mysqli_stmt_close($stmt);
                $error = 'Tugas gagal disimpan. Coba lagi.';
            }
        }
    }
}

require 'form_tugas.php';
