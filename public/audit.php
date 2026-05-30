<?php
// public/audit.php — Halaman audit log (admin only)
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/AuditLog.php';

requireLogin();
requireAdmin();

$audit = new AuditLog();
$logs  = $audit->getAll(50);
$user  = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log — SIAKAD Mini</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; }
        .navbar { background:#2563eb; color:#fff; padding:14px 24px; display:flex; justify-content:space-between; align-items:center; }
        .navbar h1 { font-size:18px; font-weight:600; }
        .navbar .nav-right { display:flex; gap:12px; font-size:13px; }
        .navbar a { color:#fff; text-decoration:none; background:rgba(255,255,255,0.2); padding:6px 14px; border-radius:6px; }
        .container { max-width:1000px; margin:1.5rem auto; padding:0 1rem; }
        .card { background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .card h2 { font-size:18px; margin-bottom:1rem; color:#1a1a1a; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        thead th { background:#f8fafc; padding:10px 12px; text-align:left; font-weight:500; color:#64748b; border-bottom:1px solid #e2e8f0; }
        tbody td { padding:10px 12px; border-bottom:1px solid #f1f5f9; color:#1a1a1a; }
        tbody tr:hover { background:#f8fafc; }
        .badge { display:inline-block; padding:3px 8px; border-radius:20px; font-size:11px; font-weight:500; }
        .badge-CREATE { background:#dcfce7; color:#15803d; }
        .badge-UPDATE { background:#dbeafe; color:#1d4ed8; }
        .badge-DELETE { background:#fee2e2; color:#b91c1c; }
        .empty-state { text-align:center; padding:3rem; color:#94a3b8; }
    </style>
</head>
<body>
<nav class="navbar">
    <h1>SIAKAD Mini</h1>
    <div class="nav-right">
        <a href="/siakad-mini/public/dashboard.php">Dashboard</a>
        <a href="/siakad-mini/public/logout.php">Keluar</a>
    </div>
</nav>

<div class="container">
    <div class="card">
        <h2>📋 Audit Log</h2>

        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <div style="font-size:40px">📋</div>
                <p>Belum ada aktivitas tercatat.</p>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Aksi</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $i => $log): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td style="color:#94a3b8"><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($log['username'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><span class="badge badge-<?= $log['aksi'] ?>"><?= $log['aksi'] ?></span></td>
                    <td><?= htmlspecialchars($log['keterangan'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
