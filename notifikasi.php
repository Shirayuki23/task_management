<?php
require_once 'auth.php'; // auth.php sudah menyinkronkan notifikasi dan menghitung $jumlah_notifikasi_baru

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['baca_semua']) && csrf_valid()) {
    $stmt = mysqli_prepare($conn, 'UPDATE notifikasi SET dibaca = 1 WHERE user_id = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: notifikasi.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT * FROM notifikasi WHERE user_id = ? ORDER BY dibaca ASC, created_at DESC, id DESC');
if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memuat notifikasi.');
}
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$notifikasi = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$page_title = 'Notifikasi';
$css_extra = 'notifikasi';
require __DIR__ . '/partials/head.php';
?>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <header class="page-header">
        <div>
            <p class="eyebrow">Pusat Informasi</p>
            <h1>Notifikasi</h1>
            <p class="muted">Informasi deadline dan perkembangan tugas kamu.</p>
        </div>
    </header>

    <section class="card notification-card">
        <div class="notification-header">
            <div>
                <h2>Semua Notifikasi</h2>
                <p class="muted"><?= (int) $jumlah_notifikasi_baru ?> notifikasi belum dibaca</p>
            </div>
            <?php if ($jumlah_notifikasi_baru > 0): ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <button type="submit" name="baca_semua" value="1" class="btn primary">
                        <?= icon('check', 18) ?> Tandai Semua Dibaca
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="notification-list">
            <?php if ($notifikasi === []): ?>
                <div class="notification-empty">
                    <div class="empty-icon"><?= icon('inbox', 40) ?></div>
                    <h3>Belum Ada Notifikasi</h3>
                    <p>Notifikasi deadline tugas akan muncul di halaman ini.</p>
                </div>
            <?php endif; ?>
            <?php foreach ($notifikasi as $item): ?>
                <?php $terlewat = $item['jenis'] === 'Deadline Terlewat'; ?>
                <article class="notification-item <?= !$item['dibaca'] ? 'unread' : '' ?> <?= $terlewat ? 'overdue' : '' ?>">
                    <div class="notification-icon"><?= icon($terlewat ? 'alert' : 'clock', 22) ?></div>
                    <div class="notification-content">
                        <h3><?= e($item['judul']) ?></h3>
                        <p><?= e($item['pesan']) ?></p>
                        <span class="notification-date"><?= e(date('d-m-Y H:i', strtotime($item['created_at']))) ?></span>
                        <?php if ($item['tugas_id'] !== null): ?>
                            <p class="notification-link">
                                <a href="detail_tugas.php?id=<?= (int) $item['tugas_id'] ?>">Lihat Tugas <?= icon('arrow-right', 15) ?></a>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php if (!$item['dibaca']): ?>
                        <span class="notification-status" title="Belum dibaca"></span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

<?php require __DIR__ . '/partials/footer.php'; ?>
