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
$info = $_SESSION['reset_success'] ?? '';
unset($_SESSION['reset_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $error = 'Masukkan email dan password yang valid.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT id, password FROM users WHERE email = ? LIMIT 1');
            if (!$stmt) {
                $error = 'Terjadi kesalahan saat memuat data. Coba lagi.';
            } else {
                mysqli_stmt_bind_param($stmt, 's', $email);
                mysqli_stmt_execute($stmt);
                $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    header('Location: dashboard.php');
                    exit;
                }

                $error = 'Email atau password salah.';
            }
        }
    }
}

$page_title = 'Login';
require __DIR__ . '/partials/head.php';
?>
<body class="auth-page">
    <form class="auth-card" method="post">
        <div class="auth-logo"><?= logo_svg(28) ?></div>
        <h1 class="auth-title">Selamat Datang</h1>
        <p class="auth-subtitle">Masuk untuk mengelola semua tugasmu.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($info !== ''): ?>
            <div class="alert alert-success"><?= e($info) ?></div>
        <?php endif; ?>

        <?= csrf_field() ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="nama@email.com"
               value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" required>

        <label for="password">Password</label>
        <div class="password-wrapper">
            <input type="password" id="password" name="password" placeholder="Masukkan password"
                   autocomplete="current-password" required>
            <button type="button" class="password-toggle" aria-label="Lihat password" title="Lihat password">
                <?= icon('eye') ?>
            </button>
        </div>

        <p class="auth-help"><a href="lupa_password.php">Lupa password?</a></p>

        <button class="btn primary full" type="submit">Login</button>

        <p class="auth-footer">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
    </form>
    <script src="assets/js/app.js?v=<?= date('YmdHis', filemtime(__DIR__ . '/assets/js/app.js')) ?>"></script>
</body>
</html>
