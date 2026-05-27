<?php
// public/trash.php — Arsip dosen terhapus + restore (admin only)
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireLogin();
requireAdmin();

$pdo = getDB();

// Proses restore
if (isset($_GET['restore'])) {
    $idRestore = (int)$_GET['restore'];

    // Validasi CSRF via GET token (sederhana)
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['token'] ?? '')) {
        die('Token tidak valid.');
    }
    unset($_SESSION['csrf_token']);

    $stmt = $pdo->prepare('UPDATE dosen SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL');
    $stmt->execute([':id' => $idRestore]);

    // Ambil nama untuk pesan
    $stmtN = $pdo->prepare('SELECT nama FROM dosen WHERE id = :id');
    $stmtN->execute([':id' => $idRestore]);
    $nama = $stmtN->fetchColumn();

    $_SESSION['flash'] = 'Dosen ' . ($nama ?: '') . ' berhasil dipulihkan.';
    header('Location: /siakad-mini/public/trash.php');
    exit;
}

// Ambil semua dosen yang sudah di-soft delete
$stmt = $pdo->query('SELECT * FROM dosen WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');
$listHapus = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Generate CSRF token untuk tombol restore
$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Terhapus — SIAKAD Mini</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; }
        .navbar { background:#2563eb; color:#fff; padding:14px 24px; display:flex; justify-content:space-between; align-items:center; }
        .navbar h1 { font-size:18px; font-weight:600; }
        .navbar .nav-right { display:flex; gap:12px; align-items:center; font-size:13px; }
        .navbar a { color:#fff; text-decoration:none; background:rgba(255,255,255,0.2); padding:6px 14px; border-radius:6px; }
        .container { max-width:900px; margin:1.5rem auto; padding:0 1rem; }
        .card { background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
        .card-header h2 { font-size:18px; color:#1a1a1a; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        thead th { background:#fef3c7; padding:10px 12px; text-align:left; font-weight:500; color:#92400e; border-bottom:1px solid #fde68a; }
        tbody td { padding:10px 12px; border-bottom:1px solid #f1f5f9; color:#1a1a1a; vertical-align:middle; }
        tbody tr:hover { background:#fffbeb; }
        .btn { padding:6px 14px; border-radius:6px; font-size:13px; font-weight:500; cursor:pointer; border:none; text-decoration:none; display:inline-block; }
        .btn-success { background:#22c55e; color:#fff; }
        .btn-success:hover { background:#16a34a; }
        .btn-secondary { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }
        .alert { padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:1rem; }
        .alert-success { background:#f0fdf4; border:1px solid #86efac; color:#15803d; }
        .empty-state { text-align:center; padding:3rem; color:#94a3b8; }
        .deleted-date { font-size:11px; color:#f59e0b; margin-top:2px; }
    </style>
</head>
<body>
<nav class="navbar">
    <h1>SIAKAD Mini</h1>
    <div class="nav-right">
        <a href="/siakad-mini/public/dosen.php">← Data Dosen</a>
        <a href="/siakad-mini/public/logout.php">Keluar</a>
    </div>
</nav>

<div class="container">
    <?php if ($flash !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>🗑️ Arsip Dosen Terhapus</h2>
            <span style="font-size:13px; color:#94a3b8"><?= count($listHapus) ?> data</span>
        </div>

        <?php if (empty($listHapus)): ?>
            <div class="empty-state">
                <div style="font-size:40px">✅</div>
                <p>Tidak ada data yang dihapus.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>NIDN</th>
                        <th>Program Studi</th>
                        <th>Dihapus Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($listHapus as $i => $d): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($d['nama'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <small style="color:#94a3b8"><?= htmlspecialchars($d['email'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><?= htmlspecialchars($d['nidn'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($d['program_studi'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="deleted-date">
                                <?= date('d M Y H:i', strtotime($d['deleted_at'])) ?>
                            </div>
                        </td>
                        <td>
                            <a href="/siakad-mini/public/trash.php?restore=<?= $d['id'] ?>&token=<?= $token ?>"
                               class="btn btn-success"
                               onclick="return confirm('Pulihkan dosen <?= htmlspecialchars($d['nama'], ENT_QUOTES, 'UTF-8') ?>?')">
                               ↩ Pulihkan
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
