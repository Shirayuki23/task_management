<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../partials/icons.php';
if (!defined('DAFTAR_PRIORITAS')) {
    define('DAFTAR_PRIORITAS', ['Rendah', 'Sedang', 'Tinggi']);
}
if (!defined('DAFTAR_STATUS')) {
    define('DAFTAR_STATUS', ['Belum Dikerjakan', 'Sedang Dikerjakan', 'Selesai']);
}
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('badge_prioritas')) {
    function badge_prioritas($prioritas) {
        $kelas = ['Rendah' => 'priority-low', 'Sedang' => 'priority-medium', 'Tinggi' => 'priority-high'];
        return $kelas[$prioritas] ?? '';
    }
}
if (!function_exists('badge_status')) {
    function badge_status($status) {
        $kelas = [
            'Belum Dikerjakan' => 'status-pending',
            'Sedang Dikerjakan' => 'status-progress',
            'Selesai' => 'status-done',
        ];
        return 'status ' . ($kelas[$status] ?? '');
    }
}
if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
    }
}
if (!function_exists('csrf_valid')) {
    function csrf_valid() {
        return isset($_POST['csrf'], $_SESSION['csrf'])
            && is_string($_POST['csrf'])
            && is_string($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    }
}
if (!function_exists('tanggal_valid')) {
    function tanggal_valid($tanggal) {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $tanggal, $m)) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}
if (!function_exists('validasi_tugas')) {
    function validasi_tugas(array $in) {
        $data = [
            'judul' => is_string($in['judul'] ?? null) ? trim($in['judul']) : '',
            'mata_kuliah' => is_string($in['mata_kuliah'] ?? null) ? trim($in['mata_kuliah']) : '',
            'deskripsi' => is_string($in['deskripsi'] ?? null) ? trim($in['deskripsi']) : '',
            'deadline' => is_string($in['deadline'] ?? null) ? trim($in['deadline']) : '',
            'prioritas' => is_string($in['prioritas'] ?? null) ? $in['prioritas'] : '',
            'status' => is_string($in['status'] ?? null) ? $in['status'] : ''
        ];
        if ($data['judul'] === '' || $data['mata_kuliah'] === '' || $data['deadline'] === '') {
            return [$data, 'Judul, mata pelajaran/kuliah, dan deadline wajib diisi.'];
        }
        if (!function_exists('mb_strlen')) {
            $panjangJudul = strlen($data['judul']);
            $panjangMataKuliah = strlen($data['mata_kuliah']);
            $panjangDeskripsi = strlen($data['deskripsi']);
        } else {
            $panjangJudul = mb_strlen($data['judul'], 'UTF-8');
            $panjangMataKuliah = mb_strlen($data['mata_kuliah'], 'UTF-8');
            $panjangDeskripsi = mb_strlen($data['deskripsi'], 'UTF-8');
        }
        if ($panjangJudul > 150 || $panjangMataKuliah > 100 || $panjangDeskripsi > 5000) {
            return [$data, 'Judul maksimal 150 karakter, mata pelajaran/kuliah 100 karakter, dan deskripsi 5000 karakter.'];
        }
        if (!tanggal_valid($data['deadline'])) {
            return [$data, 'Format deadline tidak valid.'];
        }
        if (!in_array($data['prioritas'], DAFTAR_PRIORITAS, true)) {
            return [$data, 'Pilihan prioritas tidak valid.'];
        }
        if (!in_array($data['status'], DAFTAR_STATUS, true)) {
            return [$data, 'Pilihan status tidak valid.'];
        }
        return [$data, ''];
    }
}
if (!function_exists('sisa_hari')) {
    function sisa_hari($deadline) {
        if (!tanggal_valid($deadline)) {
            return ['', ''];
        }
        $hari = (int) (new DateTime('today'))->diff(new DateTime($deadline))->format('%r%a');
        if ($hari < 0) {
            return ['Terlewat ' . abs($hari) . ' hari', 'late'];
        }
        if ($hari === 0) {
            return ['Hari ini', 'late'];
        }
        if ($hari <= 3) {
            return ['H-' . $hari, 'soon'];
        }
        return [$hari . ' hari lagi', ''];
    }
}
if (!function_exists('sinkron_notifikasi')) {
    function sinkron_notifikasi($conn, $user_id) {
        static $sudah = [];
        $user_id = (int) $user_id;
        if ($user_id <= 0 || isset($sudah[$user_id]) || !($conn instanceof mysqli)) {
            return;
        }
        $sudah[$user_id] = true;
        $queries = [
            "DELETE n FROM notifikasi n
             JOIN tugas t ON t.id = n.tugas_id AND t.user_id = n.user_id
             WHERE n.user_id = ?
             AND (t.status = 'Selesai'
                  OR (n.jenis = 'Deadline Dekat' AND t.deadline < CURDATE())
                  OR (n.jenis = 'Deadline Terlewat' AND t.deadline >= CURDATE()))",
            "INSERT INTO notifikasi (user_id, tugas_id, jenis, judul, pesan)
             SELECT user_id, id, 'Deadline Dekat', 'Deadline tugas mendekat',
                    CONCAT('Tugas \"', judul, '\" memiliki deadline ', DATE_FORMAT(deadline, '%d-%m-%Y'), '.')
             FROM tugas
             WHERE user_id = ? AND status <> 'Selesai'
             AND deadline >= CURDATE() AND deadline <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
             ON DUPLICATE KEY UPDATE pesan = VALUES(pesan), judul = VALUES(judul)",
            "INSERT INTO notifikasi (user_id, tugas_id, jenis, judul, pesan)
             SELECT user_id, id, 'Deadline Terlewat', 'Deadline tugas terlewat',
                    CONCAT('Tugas \"', judul, '\" sudah melewati deadline ', DATE_FORMAT(deadline, '%d-%m-%Y'), ' dan belum selesai.')
             FROM tugas
             WHERE user_id = ? AND status <> 'Selesai' AND deadline < CURDATE()
             ON DUPLICATE KEY UPDATE pesan = VALUES(pesan), judul = VALUES(judul)"
        ];
        foreach ($queries as $sql) {
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                continue;
            }
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}