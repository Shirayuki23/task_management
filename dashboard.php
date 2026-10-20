<?php
require_once 'auth.php';
require_once __DIR__ . '/config/classroom.php';

classroom_auto_sinkron($conn, $user_id);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total, SUM(status = 'Belum Dikerjakan') AS belum, SUM(status = 'Sedang Dikerjakan') AS proses, SUM(status = 'Selesai') AS selesai FROM tugas WHERE user_id = ?");
if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memuat ringkasan tugas.');
}
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, 'SELECT * FROM tugas WHERE user_id = ? AND status <> \'Selesai\' ORDER BY deadline ASC LIMIT 5');
if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memuat tugas.');
}
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$terdekat = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$kartu = [
    'total' => ['Total Tugas', 'layers', ''],
    'belum' => ['Belum Dikerjakan', 'circle', 'belum'],
    'proses' => ['Sedang Dikerjakan', 'play', 'proses'],
    'selesai' => ['Selesai', 'check', 'selesai'],
];

$page_title = 'Dashboard';
require __DIR__ . '/partials/head.php';
?>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <header class="page-header">
        <div>
            <p class="eyebrow">Dashboard</p>
            <h1>Halo, <?= e($current_user['nama']) ?> 👋</h1>
            <p class="muted">Berikut ringkasan tugas kamu.</p>
        </div>
        <a class="btn primary" href="tambah_tugas.php"><?= icon('plus', 18) ?> Tambah Tugas</a>
    </header>

    <section class="stats-grid">
        <?php foreach ($kartu as $k => [$label, $ikon, $kelas]): ?>
            <div class="stat-card">
                <span class="stat-icon <?= e($kelas) ?>"><?= icon($ikon, 20) ?></span>
                <div class="stat-info">
                    <small><?= e($label) ?></small>
                    <h3><?= (int) ($stats[$k] ?? 0) ?></h3>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <div class="section-title">
        <div>
            <h2>Deadline Terdekat</h2>
            <p class="muted">Tugas yang perlu kamu perhatikan.</p>
        </div>
        <a href="tugas.php">Lihat semua <?= icon('arrow-right', 16) ?></a>
    </div>

    <div class="task-list">
        <?php if ($terdekat === []): ?>
            <div class="empty-state">Belum ada tugas yang perlu dikerjakan.</div>
        <?php endif; ?>
        <?php foreach ($terdekat as $t): ?>
            <article class="task-row">
                <div class="task-info">
                    <span class="badge <?= e(badge_prioritas($t['prioritas'])) ?>"><?= e($t['prioritas']) ?></span>
                    <?php [$sisa_teks, $sisa_kelas] = sisa_hari($t['deadline']); ?>
                    <?php if ($sisa_teks !== ''): ?><span class="badge <?= e($sisa_kelas) ?>"><?= e($sisa_teks) ?></span><?php endif; ?>
                    <h3><?= e($t['judul']) ?></h3>
                    <p><?= icon('book', 15) ?> <?= e($t['mata_kuliah']) ?></p>
                </div>
                <div class="task-meta">
                    <span><?= icon('calendar', 15) ?> Deadline: <?= e(date('d M Y', strtotime($t['deadline']))) ?></span>
                    <span class="badge <?= e(badge_status($t['status'])) ?>"><?= e($t['status']) ?></span>
                    <a href="detail_tugas.php?id=<?= (int) $t['id'] ?>">Detail <?= icon('arrow-right', 15) ?></a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

<?php require __DIR__ . '/partials/footer.php'; ?>
