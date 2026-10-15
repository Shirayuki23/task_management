<?php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    header('Location: tugas.php');
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM tugas WHERE user_id = ? AND status = 'Selesai'");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header('Location: tugas.php');
exit;
