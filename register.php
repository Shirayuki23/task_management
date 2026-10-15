<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/koneksi.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi'] ?? '';

        if ($nama === '' || mb_strlen($nama) > 100) {
            $error = 'Nama wajib diisi, maksimal 100 karakter.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi password tidak sama dengan password.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
            if (!$stmt) {
                $error = 'Terjadi kesalahan saat memeriksa email. Coba lagi.';
            } else {
                mysqli_stmt_bind_param($stmt, 's', $email);
                mysqli_stmt_execute($stmt);
                $ada = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
                mysqli_stmt_close($stmt);

                if ($ada) {
                    $error = 'Email sudah terdaftar.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = mysqli_prepare($conn, 'INSERT INTO users (nama, email, password) VALUES (?, ?, ?)');
                    if (!$stmt) {
                        $error = 'Terjadi kesalahan saat menyimpan akun. Coba lagi.';
                    } else {
                        mysqli_stmt_bind_param($stmt, 'sss', $nama, $email, $hash);
                        if (mysqli_stmt_execute($stmt)) {
                            $success = 'Registrasi berhasil. Silakan login.';
                            $_POST = [];
                        } else {
                            $error = 'Registrasi gagal. Coba lagi.';
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    }
}

$page_title = 'Register';
require __DIR__ . '/partials/head.php';
?>
<body class="auth-page">
    <form class="auth-card" method="post">
        <div class="auth-logo"><?= logo_svg(28) ?></div>
        <h1 class="auth-title">Buat Akun</h1>
        <p class="auth-subtitle">Mulai atur tugasmu dengan mudah.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?= csrf_field() ?>

        <label for="nama">Nama</label>
        <input type="text" id="nama" name="nama" maxlength="100"
               value="<?= e($_POST['nama'] ?? '') ?>" autocomplete="name" required>

        <label for="reg_email">Email</label>
        <input type="email" id="reg_email" name="email" maxlength="150"
               value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" required>

        <label for="reg_password">Password</label>
        <input type="password" id="reg_password" name="password" minlength="6"
               autocomplete="new-password" required>

        <label for="konfirmasi">Konfirmasi Password</label>
        <input type="password" id="konfirmasi" name="konfirmasi" minlength="6"
               autocomplete="new-password" required>

        <button type="submit" class="btn primary full">Daftar</button>

        <p class="auth-footer">Sudah punya akun? <a href="login.php">Login</a></p>
    </form>
    <script src="assets/js/app.js"></script>
</body>
</html>
