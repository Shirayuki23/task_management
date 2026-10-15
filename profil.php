<?php
require_once 'auth.php';

function tanggal_lahir_valid($tanggal) {
    return tanggal_valid($tanggal) && $tanggal <= date('Y-m-d');
}

function tanggal_indonesia($tanggal) {
    if (empty($tanggal) || !tanggal_lahir_valid($tanggal)) {
        return '-';
    }
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
        'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $waktu = strtotime($tanggal);
    return date('j', $waktu) . ' ' . $bulan[(int) date('n', $waktu)] . ' ' . date('Y', $waktu);
}

$error = '';
$success = $_SESSION['profil_success'] ?? '';
unset($_SESSION['profil_success']);
$edit = isset($_GET['edit']) && $_GET['edit'] === '1';

$nama = $current_user['nama'];
$email = $current_user['email'];
$tanggal_lahir = $current_user['tanggal_lahir'] ?? '';
$jenis_kelamin = $current_user['jenis_kelamin'] ?? '';
$tempat_tinggal = $current_user['tempat_tinggal'] ?? '';
$no_telepon = $current_user['no_telepon'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit = true;
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $tempat_tinggal = trim($_POST['tempat_tinggal'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');

    if (!csrf_valid()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($nama === '' || $email === '' || mb_strlen($nama) > 100 || mb_strlen($email) > 150
        || mb_strlen($tempat_tinggal) > 150 || mb_strlen($no_telepon) > 20) {
        $error = 'Nama dan email wajib diisi, dengan panjang sesuai batas yang diizinkan.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($tanggal_lahir !== '' && !tanggal_lahir_valid($tanggal_lahir)) {
        $error = 'Tanggal lahir tidak valid.';
    } elseif (!in_array($jenis_kelamin, ['', 'Laki-laki', 'Perempuan'], true)) {
        $error = 'Pilihan jenis kelamin tidak valid.';
    } elseif ($no_telepon !== '' && !preg_match('/^[0-9+\-\s()]+$/', $no_telepon)) {
        $error = 'Nomor telepon hanya boleh berisi angka, spasi, +, - dan tanda kurung.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        if (!$stmt) {
            $error = 'Terjadi kesalahan saat memeriksa email.';
        } else {
            mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
            mysqli_stmt_execute($stmt);
            $email_terpakai = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
            mysqli_stmt_close($stmt);

            if ($email_terpakai) {
                $error = 'Email sudah digunakan akun lain.';
            } else {
                $stmt = mysqli_prepare($conn, 'UPDATE users SET nama = ?, email = ?, tanggal_lahir = NULLIF(?, \'\'), jenis_kelamin = NULLIF(?, \'\'), tempat_tinggal = NULLIF(?, \'\'), no_telepon = NULLIF(?, \'\') WHERE id = ?');
                if (!$stmt) {
                    $error = 'Profil gagal diproses. Silakan coba lagi.';
                } else {
                    mysqli_stmt_bind_param($stmt, 'ssssssi', $nama, $email, $tanggal_lahir, $jenis_kelamin, $tempat_tinggal, $no_telepon, $user_id);
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        $_SESSION['profil_success'] = 'Profil berhasil diperbarui.';
                        header('Location: profil.php');
                        exit;
                    }
                    mysqli_stmt_close($stmt);
                    $error = 'Profil gagal diperbarui. Silakan coba lagi.';
                }
            }
        }
    }
    $current_user = array_merge($current_user, [
        'nama' => $nama,
        'email' => $email,
        'tanggal_lahir' => $tanggal_lahir !== '' ? $tanggal_lahir : null,
        'jenis_kelamin' => $jenis_kelamin !== '' ? $jenis_kelamin : null,
        'tempat_tinggal' => $tempat_tinggal !== '' ? $tempat_tinggal : null,
        'no_telepon' => $no_telepon !== '' ? $no_telepon : null,
    ]);
}

$usia = '-';
if (!empty($current_user['tanggal_lahir'])) {
    try {
        $lahir = new DateTime($current_user['tanggal_lahir']);
        $sekarang = new DateTime('today');
        if ($lahir <= $sekarang) {
            $usia = $lahir->diff($sekarang)->y . ' tahun';
        }
    } catch (Exception $e) {
        $usia = '-';
    }
}

$page_title = 'Profil Saya';
require __DIR__ . '/partials/head.php';
?>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <header class="page-header">
        <div>
            <p class="eyebrow">Pengaturan Akun</p>
            <h1>Profil Saya</h1>
            <p class="muted">Lihat dan kelola informasi akun kamu.</p>
        </div>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= icon('alert', 18) ?><span><?= e($error) ?></span></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= icon('check', 18) ?><span><?= e($success) ?></span></div>
    <?php endif; ?>

    <div class="profile-banner"></div>

    <section class="card profile-card">
        <div class="profile-avatar"><?= e(strtoupper(mb_substr($nama, 0, 1))) ?></div>
        <h2><?= e($nama) ?></h2>
        <p class="muted"><?= e($email) ?></p>
        <?php if (!$edit): ?>
            <div class="form-actions">
                <a href="profil.php?edit=1" class="btn primary"><?= icon('edit', 18) ?> Kelola Akun</a>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($edit): ?>
        <section class="card">
            <h2>Kelola Informasi Akun</h2>
            <p class="muted">Perbarui informasi akun kamu melalui formulir berikut.</p>
            <form method="post" action="profil.php?edit=1">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" maxlength="100" value="<?= e($nama) ?>" autocomplete="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="150" value="<?= e($email) ?>" autocomplete="email" required>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= e($tanggal_lahir) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin">
                            <option value="">Pilih jenis kelamin</option>
                            <option value="Laki-laki" <?= $jenis_kelamin === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= $jenis_kelamin === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="no_telepon">Nomor Telepon</label>
                        <input type="tel" id="no_telepon" name="no_telepon" maxlength="20" value="<?= e($no_telepon) ?>" autocomplete="tel" placeholder="Contoh: 081234567890">
                    </div>
                    <div class="form-group">
                        <label for="tempat_tinggal">Tempat Tinggal</label>
                        <input type="text" id="tempat_tinggal" name="tempat_tinggal" maxlength="150" value="<?= e($tempat_tinggal) ?>" autocomplete="address-level2" placeholder="Kota atau kabupaten">
                    </div>
                </div>
                <div class="form-actions">
                    <a href="profil.php" class="btn secondary"><?= icon('arrow-left', 18) ?> Batal</a>
                    <button type="submit" class="btn primary"><?= icon('save', 18) ?> Simpan Perubahan</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="card">
            <h2>Informasi Pribadi</h2>
            <div class="profile-details">
                <div class="profile-field"><small>Nama</small><p><?= e($current_user['nama']) ?></p></div>
                <div class="profile-field"><small>Email</small><p><?= e($current_user['email']) ?></p></div>
                <div class="profile-field"><small>Tanggal Lahir</small><p><?= e(tanggal_indonesia($current_user['tanggal_lahir'] ?? '')) ?></p></div>
                <div class="profile-field"><small>Usia</small><p><?= e($usia) ?></p></div>
                <div class="profile-field"><small>Jenis Kelamin</small><p><?= e($current_user['jenis_kelamin'] ?: '-') ?></p></div>
                <div class="profile-field"><small>Nomor Telepon</small><p><?= e($current_user['no_telepon'] ?: '-') ?></p></div>
                <div class="profile-field"><small>Tempat Tinggal</small><p><?= e($current_user['tempat_tinggal'] ?: '-') ?></p></div>
                <div class="profile-field"><small>Status Akun</small><p><?= icon('check', 15) ?> Aktif</p></div>
            </div>
            <div class="form-actions">
                <a href="profil.php?edit=1" class="btn primary"><?= icon('edit', 18) ?> Kelola Akun</a>
            </div>
        </section>
    <?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
