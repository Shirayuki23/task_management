<?php
require_once __DIR__ . '/google.php';

const GOOGLE_SCOPES = 'https://www.googleapis.com/auth/classroom.courses.readonly '
    . 'https://www.googleapis.com/auth/classroom.coursework.me.readonly';

function google_auth_url() {
    $state = bin2hex(random_bytes(16));
    $_SESSION['gc_state'] = $state;
    $q = http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => google_redirect_uri(),
        'response_type' => 'code',
        'scope' => GOOGLE_SCOPES,
        'state' => $state,
        'access_type' => 'offline',
        'prompt' => 'consent',
    ]);
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . $q;
}

function google_http_json($url, $post = null, $header = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    if ($header !== []) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $kode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false) {
        return [null, 'Gagal menghubungi Google: ' . $err];
    }
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        return [null, 'Respons Google tidak terbaca (HTTP ' . $kode . ').'];
    }
    if ($kode >= 400 || isset($data['error'])) {
        $pesan = $data['error_description'] ?? $data['error']['message'] ?? $data['error'] ?? ('HTTP ' . $kode);
        return [null, 'Google: ' . $pesan];
    }
    return [$data, ''];
}

function google_tukar_code($code) {
    return google_http_json('https://oauth2.googleapis.com/token', [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => google_redirect_uri(),
    ]);
}

function google_email_dari_id_token($id_token) {
    $bagian = explode('.', (string) $id_token);
    if (count($bagian) < 2) {
        return '';
    }
    $payload = json_decode(base64_decode(strtr($bagian[1], '-_', '+/') . str_repeat('=', (4 - strlen($bagian[1]) % 4) % 4), true) ?: '', true);
    return is_array($payload) ? (string) ($payload['email'] ?? '') : '';
}

function google_access_token($refresh_token) {
    return google_http_json('https://oauth2.googleapis.com/token', [
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
    ]);
}

// Ambil semua halaman hasil API (mengikuti nextPageToken).
function google_cari_semua($url_awal, $access_token, $kunci) {
    $semua = [];
    $halaman = '';
    do {
        $url = $url_awal . ($halaman !== '' ? '&pageToken=' . rawurlencode($halaman) : '');
        [$data, $err] = google_http_json($url, null, ['Authorization: Bearer ' . $access_token]);
        if ($err !== '') {
            return [[], $err];
        }
        $semua = array_merge($semua, $data[$kunci] ?? []);
        $halaman = (string) ($data['nextPageToken'] ?? '');
    } while ($halaman !== '');
    return [$semua, ''];
}

function tanggal_google($d) {
    if (!is_array($d) || empty($d['year']) || empty($d['month']) || empty($d['day'])) {
        return '';
    }
    return sprintf('%04d-%02d-%02d', (int) $d['year'], (int) $d['month'], (int) $d['day']);
}

// Tarik semua tugas (coursework) dari kelas aktif milik akun yang terhubung.
function classroom_ambil_tugas($refresh_token) {
    [$tok, $err] = google_access_token($refresh_token);
    if ($err !== '') {
        return [[], $err];
    }
    $access = (string) $tok['access_token'];

    [$kelas, $err] = google_cari_semua(
        'https://classroom.googleapis.com/v1/courses?pageSize=256&courseStates=ACTIVE',
        $access,
        'courses'
    );
    if ($err !== '') {
        return [[], $err];
    }

    $hasil = [];
    foreach ($kelas as $k) {
        $id_kelas = (string) ($k['id'] ?? '');
        $nama_kelas = (string) ($k['name'] ?? '');
        if ($id_kelas === '') {
            continue;
        }
        [$pekerjaan, $err] = google_cari_semua(
            'https://classroom.googleapis.com/v1/courses/' . rawurlencode($id_kelas) . '/courseWork?pageSize=100',
            $access,
            'courseWork'
        );
        if ($err !== '') {
            continue;
        }
        foreach ($pekerjaan as $p) {
            if (($p['state'] ?? '') === 'DELETED') {
                continue;
            }
            $judul = trim((string) ($p['title'] ?? ''));
            if ($judul === '') {
                continue;
            }
            $deadline = tanggal_google($p['dueTime']['date'] ?? null)
                ?: tanggal_google($p['scheduledTime']['date'] ?? null);
            $deskripsi = mb_substr(trim(strip_tags((string) ($p['description'] ?? ''))), 0, 500);
            if ($deadline === '') {
                $deadline = date('Y-m-d', strtotime('+7 days'));
                $deskripsi = trim($deskripsi . ' (Classroom tidak mencantumkan deadline)');
            }
            $hasil[] = [
                'ext_id' => 'cw:' . $id_kelas . ':' . $p['id'],
                'judul' => mb_substr($judul, 0, 150),
                'mata_kuliah' => mb_substr($nama_kelas, 0, 100),
                'deskripsi' => $deskripsi,
                'deadline' => $deadline,
            ];
        }
    }
    return [$hasil, ''];
}

// Simpan/ubah hasil Classroom ke database. Status & prioritas milik user
// tidak pernah ditimpa; hanya konten dari Classroom yang diperbarui.
function classroom_simpan_db($conn, $user_id, $daftar) {
    $lama = [];
    $stmt = mysqli_prepare($conn, 'SELECT id, ext_id, judul, mata_kuliah, deadline FROM tugas WHERE user_id = ? AND ext_id IS NOT NULL');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($baris = mysqli_fetch_assoc($res)) {
        $lama[$baris['ext_id']] = $baris;
    }
    mysqli_stmt_close($stmt);

    $baru = 0;
    $ubah = 0;
    $prioritas = 'Sedang';
    $status = 'Belum Dikerjakan';

    $ins = mysqli_prepare($conn, 'INSERT INTO tugas (user_id, judul, mata_kuliah, deskripsi, deadline, prioritas, status, ext_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $upd = mysqli_prepare($conn, 'UPDATE tugas SET judul = ?, mata_kuliah = ?, deskripsi = ?, deadline = ? WHERE id = ? AND user_id = ?');
    if (!$ins || !$upd) {
        return [0, 0, 'Query sinkronisasi gagal disiapkan.'];
    }

    foreach ($daftar as $t) {
        $milik = $lama[$t['ext_id']] ?? null;
        if ($milik === null) {
            mysqli_stmt_bind_param($ins, 'isssssss', $user_id, $t['judul'], $t['mata_kuliah'], $t['deskripsi'], $t['deadline'], $prioritas, $status, $t['ext_id']);
            if (mysqli_stmt_execute($ins)) {
                $baru++;
            }
        } elseif ($milik['judul'] !== $t['judul'] || $milik['mata_kuliah'] !== $t['mata_kuliah'] || $milik['deadline'] !== $t['deadline']) {
            $id = (int) $milik['id'];
            mysqli_stmt_bind_param($upd, 'ssssii', $t['judul'], $t['mata_kuliah'], $t['deskripsi'], $t['deadline'], $id, $user_id);
            if (mysqli_stmt_execute($upd)) {
                $ubah++;
            }
        }
    }
    mysqli_stmt_close($ins);
    mysqli_stmt_close($upd);

    $st = mysqli_prepare($conn, 'UPDATE users SET google_last_sync = NOW() WHERE id = ?');
    mysqli_stmt_bind_param($st, 'i', $user_id);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);

    return [$baru, $ubah, ''];
}

// Tarik dari Google lalu simpan. Menghasilkan [ringkasan, error].
function classroom_sinkron($conn, $user_id, $refresh_token) {
    [$daftar, $err] = classroom_ambil_tugas($refresh_token);
    if ($err !== '') {
        return [null, $err];
    }
    [$baru, $ubah, $err] = classroom_simpan_db($conn, $user_id, $daftar);
    if ($err !== '') {
        return [null, $err];
    }
    return [['total' => count($daftar), 'baru' => $baru, 'ubah' => $ubah], ''];
}

// Sinkron senyap saat dashboard dibuka, maksimal 1x per 6 jam.
// Dipakai karena shared hosting gratis tidak menyediakan cron.
function classroom_auto_sinkron($conn, $user_id) {
    if (!google_kredensial_siap()) {
        return;
    }
    $stmt = mysqli_prepare($conn, 'SELECT google_refresh_token, google_last_sync FROM users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return;
    }
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (empty($baris['google_refresh_token'])) {
        return;
    }
    $terakhir = $baris['google_last_sync'] ?? '';
    if ($terakhir !== '' && strtotime($terakhir) > time() - 6 * 3600) {
        return;
    }
    classroom_sinkron($conn, $user_id, $baris['google_refresh_token']);
}
