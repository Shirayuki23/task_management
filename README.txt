TASK MANAGEMENT - PHP + MYSQL

CARA MENJALANKAN (XAMPP)
1. Ekstrak folder task_management ke C:\xampp\htdocs\ (timpa file lama).
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Buka http://localhost/phpmyadmin
4. Instalasi BARU : tab Import -> pilih database.sql -> Go.
   Database LAMA  : tab Import -> pilih migrasi.sql -> Go
                    (menambah kolom profil, tabel notifikasi, tabel
                    sub_tugas, dan memperbaiki nilai ENUM status).
5. Jika username/password MySQL berbeda, ubah config/koneksi.php.
6. Buka http://localhost/task_management/register.php
7. Daftar akun, kemudian login.

DEPLOY KE INFINITYFREE (HOSTING GRATIS PHP+MYSQL)
1. Daftar di infinityfree.com -> buat akun & website (subdomain gratis).
2. Control Panel -> File Manager -> masuk folder htdocs -> upload
   task-management-infinityfree.zip -> Extract -> hapus zip-nya.
   (Alternatif: upload via FTP, mis. FileZilla.)
3. Control Panel -> MySQL Databases -> Create Database.
   Catat: MySQL Hostname (sqlXXX.infinityfree.com), MySQL Username
   (if0_xxxxxx), MySQL Password, dan nama database penuh (if0_xxxxxx_nama).
4. Buka phpMyAdmin dari control panel -> pilih database tadi -> tab Import
   -> import database-infinityfree.sql (BUKAN database.sql, karena
   CREATE DATABASE di file itu tidak berlaku di shared hosting).
5. Edit config/koneksi.php di server (lewat File Manager): isi $db_host,
   $db_user, $db_pass, $db_name dengan catatan dari langkah 3.
   Simpan. Buka https://namakamu.infinityfreeapp.com/register.php dan daftar.
6. Folder lokal XAMPP tetap memakai koneksi localhost; hanya file
   config/koneksi.php yang berbeda antara lokal dan server.

FITUR
- Homepage publik (index.php): penjelasan web + tombol Login/Daftar,
  tidak lagi auto-redirect ke login. Kalau sudah login tombolnya jadi
  "Buka Dashboard".
- Register dan login dengan password_hash/password_verify + CSRF.
- Lupa password: verifikasi email + tanggal lahir, lalu buat password baru
  langsung (lupa_password.php). Tanggal lahir wajib diisi saat daftar.
  (Reset via email tidak bisa dipakai di shared hosting gratis yang
  memblokir fungsi mail(), makanya pakai verifikasi identitas.)
- Dashboard dan ringkasan tugas.
- Tambah, lihat detail, edit, dan hapus tugas.
- Subtask/checklist per tugas dengan progress bar (tabel sub_tugas,
  handler sub_tugas.php, UI di detail_tugas.php).
- Badge hitung mundur deadline (Terlewat N hari / Hari ini / H-3..H-1 /
  N hari lagi) di dashboard, daftar, dan detail tugas.
- Pencarian judul dan mata pelajaran/kuliah, filter status dan prioritas,
  serta pengurutan (deadline terdekat, prioritas tertinggi, terbaru, A-Z).
- Hapus massal semua tugas berstatus Selesai (hapus_selesai.php).
- Notifikasi deadline (H-3 dan terlewat) + badge jumlah belum dibaca di sidebar.
- Profil pengguna.
- Setiap tugas terikat ke akun pemiliknya.
- Proteksi CSRF pada semua form POST.
- Logo dan ikon SVG (partials/icons.php, assets/css/icons.css).
- Sidebar responsif: tombol menu di layar kecil (assets/js/app.js).

STRUKTUR FILE
- config/koneksi.php   : koneksi MySQL
- config/helpers.php   : fungsi e(), CSRF, validasi tugas, badge, sinkron notifikasi
- partials/icons.php   : fungsi icon() dan logo_svg()
- partials/head.php    : blok <head> bersama (title lewat $page_title, CSS lewat $css_extra)
- partials/header.php  : sidebar bersama semua halaman + buka <main>
- partials/footer.php  : tutup layout + muat assets/js/app.js
- assets/css/style.css : seluruh gaya dasar (tema teal) dan responsif
- assets/css/landing.css : gaya khusus homepage (index.php)
- assets/css/icons.css : gaya tambahan untuk logo & ikon (muat setelah style.css)
- assets/css/notifikasi.css : gaya khusus halaman notifikasi
- assets/js/app.js     : menu sidebar, toggle password, auto-dismiss alert, anti klik ganda
