-- Jalankan HANYA jika database task_management sudah ada (dari versi lama).
-- Untuk instalasi baru cukup import database.sql.
-- Sintaks "IF NOT EXISTS" berlaku untuk MariaDB (bawaan XAMPP).
USE task_management;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS tanggal_lahir DATE NULL,
    ADD COLUMN IF NOT EXISTS jenis_kelamin ENUM('Laki-laki', 'Perempuan') NULL,
    ADD COLUMN IF NOT EXISTS tempat_tinggal VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS no_telepon VARCHAR(20) NULL;

CREATE TABLE IF NOT EXISTS notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tugas_id INT NULL,
    jenis ENUM('Deadline Dekat', 'Deadline Terlewat') NOT NULL,
    judul VARCHAR(150) NOT NULL,
    pesan TEXT NOT NULL,
    dibaca TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notifikasi (user_id, tugas_id, jenis),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tugas_id) REFERENCES tugas(id) ON DELETE CASCADE
);

-- 2026-10-15: fitur subtask/checklist per tugas
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

-- 2026-10-19: sinkronisasi Google Classroom
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS google_email VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS google_refresh_token VARCHAR(512) NULL,
    ADD COLUMN IF NOT EXISTS google_last_sync DATETIME NULL;

ALTER TABLE tugas
    ADD COLUMN IF NOT EXISTS ext_id VARCHAR(191) NULL;

CREATE INDEX IF NOT EXISTS idx_ext ON tugas (user_id, ext_id);

-- 2026-10-15: samakan nilai ENUM status dengan aplikasi
UPDATE tugas SET status = 'Belum Dikerjakan' WHERE status = '';
ALTER TABLE tugas
    MODIFY status ENUM('Belum Dikerjakan', 'Sedang Dikerjakan', 'Selesai') NOT NULL DEFAULT 'Belum Dikerjakan';
