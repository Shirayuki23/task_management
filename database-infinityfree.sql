-- IMPORT FILE INI DI INFINITYFREE (setelah database dibuat lewat Control Panel > MySQL).
-- Tidak ada CREATE DATABASE/USE karena nama database di hosting bebas ditentukan panel (if0_xxxxxx_nama).

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    tanggal_lahir DATE NULL,
    jenis_kelamin VARCHAR(20) NULL,
    tempat_tinggal VARCHAR(150) NULL,
    no_telepon VARCHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    mata_kuliah VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    deadline DATE NOT NULL,
    prioritas ENUM('Rendah', 'Sedang', 'Tinggi') NOT NULL DEFAULT 'Sedang',
    status ENUM('Belum Dikerjakan', 'Sedang Dikerjakan', 'Selesai') NOT NULL DEFAULT 'Belum Dikerjakan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tugas_id INT NULL,
    jenis ENUM('Deadline Dekat', 'Deadline Terlewat', 'Info') NOT NULL DEFAULT 'Info',
    judul VARCHAR(150) NOT NULL,
    pesan TEXT NOT NULL,
    dibaca TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unik_notifikasi (user_id, tugas_id, jenis),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tugas_id) REFERENCES tugas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sub_tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tugas_id INT NOT NULL,
    user_id INT NOT NULL,
    isi VARCHAR(255) NOT NULL,
    selesai TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tugas_id) REFERENCES tugas(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
