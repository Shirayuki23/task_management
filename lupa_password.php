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
$email = '';
$tanggal_lahir = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (!csrf_valid()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $tanggal_lahir === '') {
        $error = 'Masukkan email dan tanggal lahir dengan benar.';
    } elseif (!tanggal_valid($tanggal_lahir) || $tanggal_lahir > date('Y-m-d')) {
        $error = 'Format tanggal lahir tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama dengan password baru.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, tanggal_lahir FROM users WHERE email = ? LIMIT 1');
        if (!$stmt) {
            $error = 'Terjadi kesalahan saat memuat data. Coba lagi.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$user || $user['tanggal_lahir'] === null || $user['tanggal_lahir'] !== $tanggal_lahir) {
                $error = 'Email dan tanggal lahir tidak cocok. Pastikan tanggal lahir sudah pernah diisi (daftar ulang atau lewat Profil).';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
                if (!$stmt) {
                    $error = 'Terjadi kesalahan saat menyimpan password. Coba lagi.';
                } else {
                    mysqli_stmt_bind_param($stmt, 'si', $hash, $user['id']);
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        $_SESSION['reset_success'] = 'Password berhasil dibuat baru. Silakan login.';
                        header('Location: login.php');
                        exit;
                    }
                    mysqli_stmt_close($stmt);
                    $error = 'Password gagal disimpan. Coba lagi.';
                }
            }
        }
    }
}

$page_title = 'Lupa Password';
require __DIR__ . '/partials/head.php';
?>
<body class="auth-page">
    <form class="auth-card" method="post">
        <div class="auth-logo"><?= logo_svg(28) ?></div>
        <h1 class="auth-title">Lupa Password</h1>
        <p class="auth-subtitle">Verifikasi akunmu, lalu langsung buat password baru.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?= csrf_field() ?>

        <label for="lp_email">Email</label>
        <input type="email" id="lp_email" name="email" maxlength="150"
               value="<?= e($email) ?>" autocomplete="email" required>

        <label for="tanggal_lahir">Tanggal Lahir</label>
        <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= e($tanggal_lahir) ?>"
               max="<?= date('Y-m-d') ?>" required>

        <label for="lp_password">Password Baru</label>
        <input type="password" id="lp_password" name="password" minlength="6" autocomplete="new-password" required>

        <label for="konfirmasi">Konfirmasi Password Baru</label>
        <input type="password" id="konfirmasi" name="konfirmasi" minlength="6" autocomplete="new-password" required>

        <button type="submit" class="btn primary full">Buat Password Baru</button>

        <p class="auth-footer">Ingat passwordnya? <a href="login.php">Kembali ke Login</a></p>
    </form>
    <script src="assets/js/app.js?v=<?= date('YmdHis', filemtime(__DIR__ . '/assets/js/app.js')) ?>"></script>
</body>
</html>
