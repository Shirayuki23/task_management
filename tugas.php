<?php
require_once 'auth.php';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$prioritas = $_GET['prioritas'] ?? '';
$sort = $_GET['sort'] ?? 'deadline';
$daftar_sort = [
    'deadline' => 'Deadline terdekat',
    'prioritas' => 'Prioritas tertinggi',
    'terbaru' => 'Baru ditambahkan',
    'judul' => 'Judul A-Z',
];
if (!isset($daftar_sort[$sort])) {
    $sort = 'deadline';
}

$sql = 'SELECT * FROM tugas WHERE user_id = ?';
$types = 'i';
$params = [$user_id];

if ($search !== '') {
    $sql .= ' AND (judul LIKE ? OR mata_kuliah LIKE ?)';
    $types .= 'ss';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (in_array($status, DAFTAR_STATUS, true)) {
    $sql .= ' AND status = ?';
    $types .= 's';
    $params[] = $status;
}

if (in_array($prioritas, DAFTAR_PRIORITAS, true)) {
    $sql .= ' AND prioritas = ?';
    $types .= 's';
    $params[] = $prioritas;
}

$urut = [
    'deadline' => 'deadline ASC',
    'prioritas' => "FIELD(prioritas, 'Tinggi', 'Sedang', 'Rendah'), deadline ASC",
    'terbaru' => 'created_at DESC',
    'judul' => 'judul ASC',
];
$sql .= ' ORDER BY ' . $urut[$sort];

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memuat daftar tugas.');
}
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$daftar_tugas = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$page_title = 'Semua Tugas';
require __DIR__ . '/partials/head.php';
?>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <header class="page-header">
        <div>
            <p class="eyebrow">Task Management</p>
            <h1>Semua Tugas</h1>
            <p class="muted">Cari dan kelola semua tugas kamu.</p>
        </div>
        <a class="btn primary" href="tambah_tugas.php"><?= icon('plus', 18) ?> Tambah Tugas</a>
    </header>
    <form class="filter-bar" method="get">
        <input type="search" name="search" placeholder="Cari judul tugas..." value="<?= e($search) ?>">

        <select name="status">
            <option value="">Semua Status</option>
            <?php foreach (DAFTAR_STATUS as $v): ?>
                <option value="<?= e($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="prioritas">
            <option value="">Semua Prioritas</option>
            <?php foreach (DAFTAR_PRIORITAS as $v): ?>
                <option value="<?= e($v) ?>" <?= $prioritas === $v ? 'selected' : '' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="sort">
            <?php foreach ($daftar_sort as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $sort === $k ? 'selected' : '' ?>>Urutkan: <?= e($label) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn primary"><?= icon('search', 16) ?> Cari</button>
        <a class="btn secondary" href="tugas.php">Reset</a>
        <form method="post" action="hapus_selesai.php" onsubmit="return confirm('Hapus semua tugas yang berstatus Selesai?')">
            <?= csrf_field() ?>
            <button type="submit" class="link-button danger"><?= icon('trash', 15) ?> Hapus Semua Selesai</button>
        </form>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Mata Pelajaran/Kuliah</th>
                    <th>Deadline</th>
                    <th>Prioritas</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($daftar_tugas === []): ?>
                    <tr>
                        <td colspan="6" class="empty-state">Data tugas tidak ditemukan.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($daftar_tugas as $t): ?>
                    <tr>
                        <td><b><?= e($t['judul']) ?></b></td>
                        <td><?= e($t['mata_kuliah']) ?></td>
                        <td>
                            <?= e(date('d M Y', strtotime($t['deadline']))) ?>
                            <?php [$sisa_teks, $sisa_kelas] = sisa_hari($t['deadline']); ?>
                            <?php if ($sisa_teks !== ''): ?><span class="badge <?= e($sisa_kelas) ?>"><?= e($sisa_teks) ?></span><?php endif; ?>
                        </td>
                        <td><span class="badge <?= e(badge_prioritas($t['prioritas'])) ?>"><?= e($t['prioritas']) ?></span></td>
                        <td><span class="badge <?= e(badge_status($t['status'])) ?>"><?= e($t['status']) ?></span></td>
                        <td class="table-actions">
                            <a class="btn btn-sm secondary" href="detail_tugas.php?id=<?= (int) $t['id'] ?>"><?= icon('eye', 15) ?> Detail</a>
                            <a class="btn btn-sm secondary" href="edit_tugas.php?id=<?= (int) $t['id'] ?>"><?= icon('edit', 15) ?> Edit</a>
                            <form method="post" action="hapus_tugas.php" onsubmit="return confirm('Yakin ingin menghapus tugas ini?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                <button type="submit" class="link-button danger"><?= icon('trash', 15) ?> Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php require __DIR__ . '/partials/footer.php'; ?>
