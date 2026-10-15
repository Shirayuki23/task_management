<?php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    header('Location: tugas.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

$stmt = mysqli_prepare($conn, 'DELETE FROM tugas WHERE id = ? AND user_id = ?');
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header('Location: tugas.php');
exit;
