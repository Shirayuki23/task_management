<?php
require_once 'auth.php';
require_once __DIR__ . '/config/classroom.php';

$error = '';
$success = $_SESSION['classroom_success'] ?? '';
unset($_SESSION['classroom_success']);

$galat = [
    'state' => 'Koneksi OAuth tidak valid (state berubah). Coba hubungkan dari awal.',
    'ditolak' => 'Akses Classroom ditolak di halaman Google. Hubungkan ulang dan pilih Izinkan.',
    'kode' => 'Google tidak mengirim kode autorisasi. Hubungkan ulang.',
    'token' => 'Gagal menukar kode dengan token Google. Hubungkan ulang.',
    'simpan' => 'Gagal menyimpan koneksi di database. Coba lagi.',
][$_GET['galat'] ?? ''] ?? '';
if ($galat !== '') {
    $error = $galat;
}

$stmt = mysqli_prepare($conn, 'SELECT google_email, google_refresh_token, google_last_sync FROM users WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$akun_google = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$terhubung = !empty($akun_google['google_refresh_token']);

$siap = google_kredensial_siap();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $siap) {
    if (!csrf_valid()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $aksi = $_POST['aksi'] ?? '';

        if ($aksi === 'putuskan') {
            $stmt = mysqli_prepare($conn, 'UPDATE users SET google_email = NULL, google_refresh_token = NULL, google_last_sync = NULL WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $terhubung = false;
            $akun_google = ['google_email' => '', 'google_refresh_token' => '', 'google_last_sync' => ''];
            $success = 'Akun Google diputus. Tugas yang sudah masuk tetap tersimpan.';
        }

        if ($aksi === 'sinkron' && $terhubung) {
            [$ringkas, $err] = classroom_sinkron($conn, $user_id, $akun_google['google_refresh_token']);
            if ($err !== '') {
                $error = $err;
                if (str_contains($error, 'invalid_grant')) {
                    $error .= ' — koneksi Google kedaluwarsa, klik "Hubungkan ulang" di bawah.';
                }
            } else {
                $akun_google['google_last_sync'] = date('Y-m-d H:i:s');
                $success = 'Sinkron selesai: ' . $ringkas['total'] . ' tugas di Classroom, '
                    . $ringkas['baru'] . ' baru diimpor'
                    . ($ringkas['ubah'] > 0 ? ', ' . $ringkas['ubah'] . ' diperbarui dari perubahan guru.' : '.');
            }
        }
    }
}

$page_title = 'Classroom';
require __DIR__ . '/partials/head.php';
?>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <header class="page-header">
        <div>
            <p class="eyebrow">Integrasi</p>
            <h1>Sinkronisasi Google Classroom</h1>
            <p class="muted">Tarik tugas dari Classroom-mu masuk ke daftar tugas di sini.</p>
        </div>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if (!$siap): ?>
        <div class="card panel">
            <h2>Belum dikonfigurasi</h2>
            <p class="muted">
                Fitur ini butuh kredensial OAuth Google. Buka README.txt bagian
                <b>SINKRONISASI GOOGLE CLASSROOM</b>, lalu isi
                <code>config/google.php</code> (client ID dan secret dari Google Cloud Console).
            </p>
        </div>
    <?php else: ?>
        <div class="card panel">
            <?php if ($terhubung): ?>
                <h2>Terhubung sebagai <?= e($akun_google['google_email'] ?: 'akun Google') ?></h2>
                <p class="muted">Tugas (coursework) dari semua kelas aktif akan ditarik.
                    Tugas yang sudah pernah diimpor tidak diduplikasi; kalau guru mengubah
                    judul/deadline, salinan di sini ikut diperbarui.</p>
                <p class="muted">Sinkron terakhir:
                    <b><?= $akun_google['google_last_sync'] ? e(date('d/m/Y H:i', strtotime($akun_google['google_last_sync']))) : 'belum pernah' ?></b>
                    — dashboard juga sinkron otomatis maksimal 1x per 6 jam.</p>
                <div class="form-actions">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="sinkron">
                        <button type="submit" class="btn primary"><?= icon('check') ?> Sinkronkan Sekarang</button>
                    </form>
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="putuskan">
                        <button type="submit" class="btn secondary"><?= icon('logout') ?> Putuskan</button>
                    </form>
                    <a class="btn secondary" href="<?= e(google_auth_url()) ?>">Hubungkan ulang</a>
                </div>
            <?php else: ?>
                <h2>Belum terhubung</h2>
                <p class="muted">Klik tombol di bawah, login ke Google, dan izinkan akses read-only
                    ke Classroom. Sekali terhubung, sinkron tinggal klik.</p>
                <a class="btn primary" href="<?= e(google_auth_url()) ?>"><?= icon('layers') ?> Hubungkan Akun Google</a>
            <?php endif; ?>
        </div>

        <div class="card panel">
            <h2>Catatan</h2>
            <ul class="muted">
                <li>Akun Google sekolah/kampus kadang memblokir aplikasi yang belum diverifikasi. Kalau ditolak, pakai akun Google pribadi yang terdaftar di kelas yang sama.</li>
                <li>Tugas tanpa deadline di Classroom diberi deadline 7 hari ke depan plus catatan otomatis.</li>
                <li>Yang diimpor hanya judul, kelas, deskripsi, dan deadline. Pengumpulan tetap lewat Classroom.</li>
            </ul>
        </div>
    <?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
