<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config/helpers.php';
$sudah_login = !empty($_SESSION['user_id']);
$page_title = 'Beranda';
$css_extra = 'landing';
require __DIR__ . '/partials/head.php';
?>
<body class="landing-page">
    <header class="landing-nav">
        <a class="landing-brand" href="index.php">
            <?= logo_svg(26) ?>
            <span>Task Management</span>
        </a>
        <nav class="landing-nav-actions">
            <?php if ($sudah_login): ?>
                <a class="btn primary" href="dashboard.php">Buka Dashboard</a>
            <?php else: ?>
                <a class="btn secondary" href="login.php">Login</a>
                <a class="btn primary" href="register.php">Daftar</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-text">
                <p class="eyebrow">Untuk pelajar SD sampai kuliah</p>
                <h1>Kelola Tugas Sekolah, Tuntas Tanpa Lupa Deadline.</h1>
                <p class="hero-sub">
                    Task Management adalah web aplikasi untuk mencatat semua tugasmu dalam satu tempat.
                    Atur prioritas, pecah tugas besar menjadi checklist kecil, dan dapatkan pengingat
                    sebelum deadline tiba.
                </p>
                <div class="hero-actions">
                    <?php if ($sudah_login): ?>
                        <a class="btn primary full-sm" href="dashboard.php">Ke Dashboard</a>
                    <?php else: ?>
                        <a class="btn primary full-sm" href="register.php">Daftar Gratis</a>
                        <a class="btn secondary full-sm" href="login.php">Sudah punya akun? Login</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-card" aria-hidden="true">
                <div class="mock-task done">
                    <span class="mock-check"><?= icon('check', 16) ?></span>
                    <div><strong>PR Matematika</strong><small>Selesai</small></div>
                </div>
                <div class="mock-task">
                    <span class="mock-check"><?= icon('clock', 16) ?></span>
                    <div><strong>Makalah Bahasa Indonesia</strong><small>H-3 &middot; Prioritas Tinggi</small></div>
                </div>
                <div class="mock-task">
                    <span class="mock-check"><?= icon('play', 16) ?></span>
                    <div><strong>Latihan Fisika Bab 2</strong><small>2/5 subtask selesai</small></div>
                </div>
            </div>
        </section>

        <section class="features">
            <h2>Yang Bisa Kamu Lakukan</h2>
            <div class="feature-grid">
                <article class="feature-card">
                    <span class="feature-icon"><?= icon('list', 22) ?></span>
                    <h3>Daftar Tugas Teratur</h3>
                    <p>Tambah, edit, cari, urutkan, dan filter tugas berdasarkan status serta prioritas. Hapus sekaligus semua tugas yang sudah selesai.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon"><?= icon('check', 22) ?></span>
                    <h3>Checklist Subtask</h3>
                    <p>Pecah tugas besar menjadi langkah kecil. Progress bar otomatis menunjukkan seberapa jauh kamu bekerja.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon"><?= icon('bell', 22) ?></span>
                    <h3>Pengingat Deadline</h3>
                    <p>Notifikasi muncul saat deadline tinggal 3 hari dan ketika terlewat, plus badge hitung mundur di setiap tugas.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon"><?= icon('user', 22) ?></span>
                    <h3>Akun Pribadi Aman</h3>
                    <p>Data tiap siswa terpisah oleh akun, password terenkripsi, dan ada fitur lupa password dengan verifikasi tanggal lahir.</p>
                </article>
            </div>
        </section>

        <section class="cta-band">
            <h2>Siap Merapikan Tugasmu?</h2>
            <p>Buat akun dalam kurang dari satu menit, lalu mulai tambah tugas pertamamu.</p>
            <?php if (!$sudah_login): ?>
                <a class="btn primary" href="register.php">Daftar Sekarang</a>
            <?php else: ?>
                <a class="btn primary" href="dashboard.php">Lihat Tugasmu</a>
            <?php endif; ?>
        </section>
    </main>

    <footer class="landing-footer">
        &copy; <?= date('Y') ?> Task Management &middot; PHP + MySQL
    </footer>
    <script src="assets/js/app.js?v=<?= date('YmdHis', filemtime(__DIR__ . '/assets/js/app.js')) ?>"></script>
</body>
</html>
