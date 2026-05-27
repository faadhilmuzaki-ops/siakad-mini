# SIAKAD Mini — Sistem Informasi Manajemen Dosen & Mata Kuliah

**Mata Kuliah:** Pemrograman Web | FASTIKOM UNSIQ

---

## Cara Setup (Langkah Persiapan)

### 1. Pastikan XAMPP/Laragon berjalan
- Buka XAMPP Control Panel
- Klik **Start** pada **Apache** dan **MySQL**
- Pastikan keduanya hijau

### 2. Taruh project di htdocs
- Copy folder `siakad-mini/` ke `C:/xampp/htdocs/`
- Akses via browser: `http://localhost/siakad-mini/`

### 3. Buat database
- Buka `http://localhost/phpmyadmin`
- Klik **New** di sidebar kiri
- Isi nama: `siakad_mini`
- Pilih collation: `utf8mb4_unicode_ci`
- Klik **Create**

### 4. Generate password hash
- Buka browser: `http://localhost/siakad-mini/generate_hash.php`
- Copy hash untuk `admin` dan `operator`
- Buka `seed.sql`, ganti bagian `password_hash` dengan hash yang dicopy
- **Hapus** `generate_hash.php` setelah selesai

### 5. Import seed.sql
- Di phpMyAdmin, klik database `siakad_mini`
- Klik tab **Import**
- Klik **Choose File**, pilih `seed.sql`
- Klik **Go**
- Pastikan muncul 4 tabel di sidebar: `users`, `dosen`, `mata_kuliah`, `dosen_matkul`

### 6. Uji koneksi
- Buka: `http://localhost/siakad-mini/test_db.php`
- Pastikan semua status **OK** dan **ADA**
- **Hapus** `test_db.php` setelah berhasil

---

## Akun Demo

| Username | Password     | Role     |
|----------|-------------|----------|
| admin    | Admin123!   | admin    |
| operator | Operator123!| operator |

---

## Fitur per Level

### Level 1 — Wajib
- [x] Setup database (4 tabel relasional)
- [x] Koneksi PDO
- [ ] Login / logout + session guard
- [ ] CRUD dosen dengan prepared statement
- [ ] Soft delete (kolom deleted_at)
- [ ] Upload foto dengan validasi MIME
- [ ] CSRF token di semua form POST
- [ ] Paginasi & pencarian

### Level 2 — Lanjutan
- [ ] Refaktor ke OOP (Database, DosenRepository, Validator, Auth)
- [ ] RBAC admin vs operator
- [ ] Relasi many-to-many dalam transaksi
- [ ] Halaman trash + restore
- [ ] Filter + sorting + paginasi gabungan

### Level 3 — Bonus
- [ ] Audit log
- [ ] Dashboard statistik
- [ ] Export CSV
- [ ] Proteksi IDOR + rate limiting

---

## Struktur Folder

```
siakad-mini/
├── config/
│   └── database.php       # koneksi PDO
├── classes/               # (Level 2) class OOP
├── public/                # halaman PHP (login, index, create, dll)
├── uploads/               # foto dosen
├── seed.sql               # skema + data awal
├── generate_hash.php      # HAPUS setelah dipakai
├── test_db.php            # HAPUS setelah dipakai
└── README.md
```

---

## Teknologi
- PHP 8.x
- MySQL 8 / MariaDB
- PDO dengan prepared statement
- XAMPP / Laragon
