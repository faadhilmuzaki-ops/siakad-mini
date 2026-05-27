<?php
// public/index.php
// Halaman utama — nanti diganti dengan daftar dosen
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Wajib login untuk akses halaman ini
requireLogin();

$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SIAKAD Mini</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }

        .navbar {
            background: #2563eb;
            color: #fff;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 { font-size: 18px; font-weight: 600; }

        .navbar .user-info { font-size: 13px; display: flex; align-items: center; gap: 16px; }

        .navbar a {
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 6px;
        }

        .navbar a:hover { background: rgba(255,255,255,0.3); }

        .container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }

        .welcome-card {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .welcome-card h2 { font-size: 20px; color: #1a1a1a; margin-bottom: 8px; }
        .welcome-card p  { font-size: 14px; color: #666; }

        .badge-role {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: <?= $user['role'] === 'admin' ? '#dbeafe' : '#f0fdf4' ?>;
            color: <?= $user['role'] === 'admin' ? '#1d4ed8' : '#15803d' ?>;
            margin-left: 8px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .menu-item {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 1.25rem;
            text-decoration: none;
            color: #1a1a1a;
            transition: box-shadow 0.2s, transform 0.1s;
        }

        .menu-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .menu-item .icon { font-size: 28px; margin-bottom: 8px; }
        .menu-item .title { font-size: 15px; font-weight: 500; }
        .menu-item .desc  { font-size: 12px; color: #888; margin-top: 4px; }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>SIAKAD Mini</h1>
    <div class="user-info">
        <span>
            <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>
            <span class="badge-role"><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></span>
        </span>
        <a href="/siakad-mini/public/logout.php">Keluar</a>
    </div>
</nav>

<div class="container">
    <div class="welcome-card">
        <h2>Selamat datang, <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>!</h2>
        <p>Sistem Informasi Akademik — Manajemen Dosen & Mata Kuliah</p>

        <div class="menu-grid">
            <a href="/siakad-mini/public/dosen.php" class="menu-item">
                <div class="icon">👨‍🏫</div>
                <div class="title">Data Dosen</div>
                <div class="desc">Lihat, tambah, edit dosen</div>
            </a>
            <?php if ($user['role'] === 'admin'): ?>
            <a href="/siakad-mini/public/trash.php" class="menu-item">
                <div class="icon">🗑️</div>
                <div class="title">Arsip Terhapus</div>
                <div class="desc">Pulihkan data dosen</div>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
