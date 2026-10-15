<?php
$edit = isset($edit) && $edit;
$error = $error ?? '';
$tugas = $tugas ?? [
    'judul' => '',
    'mata_kuliah' => '',
    'deskripsi' => '',
    'deadline' => date('Y-m-d'),
    'prioritas' => 'Sedang',
    'status' => 'Belum Dikerjakan',
];

$judul = $_POST['judul'] ?? $tugas['judul'];
$mata_kuliah = $_POST['mata_kuliah'] ?? $tugas['mata_kuliah'];
$deskripsi = $_POST['deskripsi'] ?? $tugas['deskripsi'];
$deadline = $_POST['deadline'] ?? $tugas['deadline'];
$pilihan_prioritas = $_POST['prioritas'] ?? $tugas['prioritas'];
if (!in_array($pilihan_prioritas, DAFTAR_PRIORITAS, true)) {
    $pilihan_prioritas = 'Sedang';
}
$pilihan_status = $_POST['status'] ?? $tugas['status'];
if (!in_array($pilihan_status, DAFTAR_STATUS, true)) {
    $pilihan_status = 'Belum Dikerjakan';
}

if (!function_exists('csrf_field')) {
    die('Fungsi keamanan CSRF belum tersedia. Periksa auth.php.');
}

$page_title = ($edit ? 'Edit' : 'Tambah') . ' Tugas';
require __DIR__ . '/partials/head.php';
?>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <header class="page-header">
        <div>
            <p class="eyebrow">Task Management</p>
            <h1><?= ($edit ? 'Edit' : 'Tambah') ?> Tugas</h1>
            <p class="muted">Kelola tugas sekolah atau kuliahmu dengan lebih mudah.</p>
        </div>
    </header>

    <div class="card">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" id="formTugas">
            <?= csrf_field() ?>

            <label for="judul">Judul Tugas</label>
            <input type="text" id="judul" name="judul" maxlength="150" value="<?= e($judul) ?>"
                   placeholder="Contoh: Membuat laporan praktikum" required>

            <label for="mata_kuliah">Mata Pelajaran / Kuliah</label>
            <input type="text" id="mata_kuliah" name="mata_kuliah" maxlength="100" value="<?= e($mata_kuliah) ?>"
                   placeholder="Contoh: Matematika atau Pemrograman Web" required>

            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="5" maxlength="5000"
                      placeholder="Catatan atau detail tugas..."><?= e($deskripsi) ?></textarea>

            <div class="form-grid">
                <div class="form-group">
                    <label for="deadline">Deadline</label>
                    <input type="date" id="deadline" name="deadline" min="<?= date('Y-m-d') ?>" value="<?= e($deadline ?: date('Y-m-d')) ?>" required>
                </div>
                <div class="form-group">
                    <label for="prioritas">Prioritas</label>
                    <select id="prioritas" name="prioritas" required>
                        <?php foreach (DAFTAR_PRIORITAS as $v): ?>
                            <option value="<?= e($v) ?>" <?= $pilihan_prioritas === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <?php foreach (DAFTAR_STATUS as $v): ?>
                            <option value="<?= e($v) ?>" <?= $pilihan_status === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <a class="btn secondary" href="tugas.php"><?= icon('arrow-left', 18) ?> Batal</a>
                <button class="btn secondary" type="button" id="resetDraft">Hapus Draft</button>
                <button class="btn primary" type="submit"><?= icon('save', 18) ?> Simpan Tugas</button>
            </div>
            <p id="draftInfo" class="form-hint" role="status" aria-live="polite"></p>
        </form>
    </div>

    <script>
    (() => {
        const form = document.getElementById('formTugas');
        const info = document.getElementById('draftInfo');
        const reset = document.getElementById('resetDraft');
        const editMode = <?= json_encode($edit) ?>;
        const draftKey = 'draft_tugas_v1';
        const fields = ['judul', 'mata_kuliah', 'deskripsi', 'deadline', 'prioritas', 'status'];
        const batas = { judul: 150, mata_kuliah: 100, deskripsi: 5000, deadline: 10, prioritas: 20, status: 20 };
        let timer;

        function simpanDraft() {
            const data = {};
            fields.forEach((id) => { data[id] = document.getElementById(id).value; });
            try {
                localStorage.setItem(draftKey, JSON.stringify(data));
                info.textContent = 'Draft tersimpan otomatis di browser.';
            } catch (err) {
                info.textContent = 'Draft tidak dapat disimpan di browser ini.';
            }
        }

        if (!editMode) {
            try {
                const saved = JSON.parse(localStorage.getItem(draftKey) || 'null');
                if (saved && typeof saved === 'object') {
                    fields.forEach((id) => {
                        const field = document.getElementById(id);
                        if (typeof saved[id] === 'string' && saved[id] !== '' && saved[id].length <= batas[id]) {
                            field.value = saved[id];
                        }
                    });
                    info.textContent = 'Draft sebelumnya dipulihkan.';
                }
            } catch (err) {
                localStorage.removeItem(draftKey);
            }
        }

        form.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(simpanDraft, 500);
        });
        form.addEventListener('change', simpanDraft);
        form.addEventListener('submit', () => {
            clearTimeout(timer);
            try { localStorage.removeItem(draftKey); } catch (err) {}
        });
        reset.addEventListener('click', () => {
            try { localStorage.removeItem(draftKey); } catch (err) {}
            form.reset();
            document.getElementById('deadline').value = <?= json_encode(date('Y-m-d')) ?>;
            document.getElementById('prioritas').value = 'Sedang';
            document.getElementById('status').value = 'Belum Dikerjakan';
            info.textContent = 'Draft dihapus. Form dikembalikan ke nilai awal.';
        });
    })();
    </script>

<?php require __DIR__ . '/partials/footer.php'; ?>
