-- ============================================================
-- seed.sql — SIAKAD Mini
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Hapus tabel lama kalau ada (urutan penting: pivot dulu, baru induk)
DROP TABLE IF EXISTS dosen_matkul;
DROP TABLE IF EXISTS dosen;
DROP TABLE IF EXISTS mata_kuliah;
DROP TABLE IF EXISTS users;

-- ============================================================
-- TABEL 1: users
-- ============================================================
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','operator') NOT NULL DEFAULT 'operator',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 2: dosen  (soft delete pakai kolom deleted_at)
-- ============================================================
CREATE TABLE dosen (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nidn          CHAR(10)     NOT NULL UNIQUE,
    nama          VARCHAR(100) NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    program_studi ENUM('Teknik Informatika','Sistem Informasi','Teknik Elektro') NOT NULL,
    foto          VARCHAR(255) NULL DEFAULT NULL,
    status        ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_deleted (deleted_at),
    INDEX idx_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 3: mata_kuliah
-- ============================================================
CREATE TABLE mata_kuliah (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(12)  NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    sks  TINYINT UNSIGNED NOT NULL CHECK (sks BETWEEN 1 AND 6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 4: dosen_matkul  (pivot many-to-many)
-- ============================================================
CREATE TABLE dosen_matkul (
    dosen_id   INT UNSIGNED NOT NULL,
    matkul_id  INT UNSIGNED NOT NULL,
    tahun_ajar VARCHAR(9)   NOT NULL DEFAULT '2025/2026',
    PRIMARY KEY (dosen_id, matkul_id),
    FOREIGN KEY (dosen_id)  REFERENCES dosen(id)       ON DELETE CASCADE,
    FOREIGN KEY (matkul_id) REFERENCES mata_kuliah(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DATA AWAL
-- Password admin    : Admin123!
-- Password operator : Operator123!
-- Hash dibuat dengan: password_hash('...', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO users (username, password_hash, role) VALUES
(
    'admin',
    '$2y$12$3w06W/QZ5pCpDjWeaRbDSevubazGn8siwf/7ob9IyjxuS9xr1R57a',
    'admin'
),
(
    'operator',
    '$2y$12$GYmkF0RhrjNPk0Sig0UGXOf4lu2vxusf0U3ZXXfgjMHzOebCiOKya',
    'operator'
);

INSERT INTO mata_kuliah (kode, nama, sks) VALUES
('TI101', 'Pemrograman Web',              3),
('TI102', 'Basis Data',                   3),
('TI103', 'Algoritma & Struktur Data',    3),
('TI104', 'Jaringan Komputer',            2),
('TI105', 'Rekayasa Perangkat Lunak',     3);

INSERT INTO dosen (nidn, nama, email, program_studi, status) VALUES
('0101019001', 'Dr. Budi Santoso, M.Kom',   'budi.santoso@unsiq.ac.id',  'Teknik Informatika', 'aktif'),
('0202029002', 'Siti Rahmawati, M.Kom',     'siti.rahmawati@unsiq.ac.id','Teknik Informatika', 'aktif'),
('0303039003', 'Ahmad Fauzi, M.T.',         'ahmad.fauzi@unsiq.ac.id',   'Sistem Informasi',   'aktif');

INSERT INTO dosen_matkul (dosen_id, matkul_id, tahun_ajar) VALUES
(1, 1, '2025/2026'),
(1, 2, '2025/2026'),
(2, 3, '2025/2026'),
(3, 4, '2025/2026');
