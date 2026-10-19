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
