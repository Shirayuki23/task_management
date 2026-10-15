<?php
require_once 'auth.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, 'SELECT * FROM tugas WHERE id = ? AND user_id = ?');
if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memuat detail tugas.');
}
mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
mysqli_stmt_execute($stmt);
$tugas = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$tugas) {
    header('Location: tugas.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT * FROM sub_tugas WHERE tugas_id = ? AND user_id = ? ORDER BY selesai ASC, id ASC');
$subtasks = [];
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
    mysqli_stmt_execute($stmt);
    $subtasks = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
$sub_selesai = count(array_filter($subtasks, function ($s) { return (int) $s['selesai'] === 1; }));
$sub_total = count($subtasks);
$sub_persen = $sub_total > 0 ? (int) round($sub_selesai / $sub_total * 100) : 0;

$terlambat = $tugas['status'] !== 'Selesai' && $tugas['deadline'] < date('Y-m-d');

$page_title = 'Detail Tugas';
require __DIR__ . '/partials/head.php';
?>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <header class="page-header">
        <div>
            <p class="eyebrow">Task Management</p>
            <h1>Detail Tugas</h1>
        </div>
        <a class="btn primary" href="edit_tugas.php?id=<?= $id ?>"><?= icon('edit', 18) ?> Edit Tugas</a>
    </header>

    <div class="card">
        <span class="badge <?= e(badge_prioritas($tugas['prioritas'])) ?>"><?= e($tugas['prioritas']) ?></span>
        <?php if ($terlambat): ?><span class="badge late">Terlambat</span><?php endif; ?>
        <h2 class="detail-title"><?= e($tugas['judul']) ?></h2>
        <p class="muted"><?= icon('book', 16) ?> <?= e($tugas['mata_kuliah']) ?></p>

        <div class="detail-grid">
            <div>
                <small><?= icon('calendar', 15) ?> Deadline</small>
                <b><?= e(date('d M Y', strtotime($tugas['deadline']))) ?></b>
                <?php [$sisa_teks, $sisa_kelas] = sisa_hari($tugas['deadline']); ?>
                <?php if ($sisa_teks !== ''): ?>
                    <div><span class="badge <?= e($sisa_kelas) ?>"><?= e($sisa_teks) ?></span></div>
                <?php endif; ?>
            </div>
            <div>
                <small><?= icon('flag', 15) ?> Prioritas</small>
                <b><?= e($tugas['prioritas']) ?></b>
            </div>
            <div>
                <small><?= icon('clock', 15) ?> Status</small>
                <b><?= e($tugas['status']) ?></b>
            </div>
        </div>

        <h3>Deskripsi</h3>
        <p class="description"><?= nl2br(e($tugas['deskripsi'] !== '' ? $tugas['deskripsi'] : 'Tidak ada deskripsi.')) ?></p>

        <div class="form-actions">
            <a class="btn secondary" href="tugas.php"><?= icon('arrow-left', 18) ?> Kembali</a>
            <form method="post" action="hapus_tugas.php" onsubmit="return confirm('Yakin ingin menghapus tugas ini?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button class="btn danger" type="submit"><?= icon('trash', 18) ?> Hapus Tugas</button>
            </form>
        </div>
    </div>

    <section class="card subtask-card">
        <div class="section-title">
            <div>
                <h2>Subtask</h2>
                <p class="muted">Pecah tugas jadi langkah-langkah kecil.</p>
            </div>
            <span class="muted"><?= $sub_selesai ?>/<?= $sub_total ?> selesai</span>
        </div>

        <div class="progress" role="progressbar" aria-valuenow="<?= $sub_persen ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: <?= $sub_persen ?>%"></div>
        </div>

        <?php if ($subtasks !== []): ?>
            <ul class="subtask-list">
                <?php foreach ($subtasks as $s): ?>
                    <li class="<?= (int) $s['selesai'] === 1 ? 'done' : '' ?>">
                        <form method="post" action="sub_tugas.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="tugas_id" value="<?= $id ?>">
                            <input type="hidden" name="aksi" value="toggle">
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <input type="checkbox" <?= (int) $s['selesai'] === 1 ? 'checked' : '' ?>
                                   onchange="this.form.submit()" aria-label="Tandai selesai">
                        </form>
                        <span class="subtask-isi"><?= e($s['isi']) ?></span>
                        <form method="post" action="sub_tugas.php" onsubmit="return confirm('Hapus subtask ini?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="tugas_id" value="<?= $id ?>">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <button type="submit" class="link-button danger" aria-label="Hapus subtask"><?= icon('trash', 15) ?></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">Belum ada subtask. Tambahkan satu di bawah ini.</p>
        <?php endif; ?>

        <form method="post" action="sub_tugas.php" class="subtask-add">
            <?= csrf_field() ?>
            <input type="hidden" name="tugas_id" value="<?= $id ?>">
            <input type="hidden" name="aksi" value="tambah">
            <input type="text" name="isi" maxlength="255" placeholder="Contoh: Kumpulkan referensi jurnal" required>
            <button type="submit" class="btn primary"><?= icon('plus', 16) ?> Tambah</button>
        </form>
    </section>

<?php require __DIR__ . '/partials/footer.php'; ?>
