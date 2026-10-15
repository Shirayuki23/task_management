<?php
$page = basename($_SERVER['PHP_SELF']);
$halaman_tugas = ['tugas.php', 'tambah_tugas.php', 'edit_tugas.php', 'detail_tugas.php'];
?>
<div class="app-layout">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="dashboard.php">
            <span class="logo"><?= logo_svg(22) ?></span>
            <span class="brand-text">
                <b>Task Management</b>
                <small>Tugas Sekolah &amp; Kuliah</small>
            </span>
        </a>
        <p class="sidebar-label">Menu</p>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= $page === 'dashboard.php' ? 'active' : '' ?>"><?= icon('dashboard') ?> Dashboard</a>
            <a href="tugas.php" class="<?= in_array($page, $halaman_tugas, true) ? 'active' : '' ?>"><?= icon('list') ?> Semua Tugas</a>
            <a href="notifikasi.php" class="<?= $page === 'notifikasi.php' ? 'active' : '' ?>"><?= icon('bell') ?> Notifikasi
                <?php if (($jumlah_notifikasi_baru ?? 0) > 0): ?>
                    <span class="nav-badge"><?= (int) $jumlah_notifikasi_baru ?></span>
                <?php endif; ?>
            </a>
            <a href="profil.php" class="<?= $page === 'profil.php' ? 'active' : '' ?>"><?= icon('user') ?> Profil</a>
        </nav>
        <div class="sidebar-bottom">
            <a href="logout.php"><?= icon('logout') ?> Logout</a>
        </div>
    </aside>
    <button type="button" class="menu-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu"><?= icon('menu') ?></button>
    <div class="sidebar-overlay"></div>
    <main class="main-content">
