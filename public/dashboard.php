<?php
// public/dashboard.php — Dashboard statistik
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireLogin();

$pdo  = getDB();
$user = currentUser();

// ── STATISTIK ────────────────────────────────────────────────

// Total dosen aktif
$totalDosen = (int)$pdo->query(
    "SELECT COUNT(*) FROM dosen WHERE deleted_at IS NULL AND status = 'aktif'"
)->fetchColumn();

// Total dosen nonaktif
$totalNonaktif = (int)$pdo->query(
    "SELECT COUNT(*) FROM dosen WHERE deleted_at IS NULL AND status = 'nonaktif'"
)->fetchColumn();

// Total dosen terhapus
$totalTrashed = (int)$pdo->query(
    "SELECT COUNT(*) FROM dosen WHERE deleted_at IS NOT NULL"
)->fetchColumn();

// Total mata kuliah
$totalMatkul = (int)$pdo->query(
    "SELECT COUNT(*) FROM mata_kuliah"
)->fetchColumn();

// Total SKS
$totalSks = (int)$pdo->query(
    "SELECT SUM(sks) FROM mata_kuliah"
)->fetchColumn();

// Distribusi per program studi
$stmtProdi = $pdo->query(
    "SELECT program_studi, COUNT(*) AS jumlah
     FROM dosen
     WHERE deleted_at IS NULL
     GROUP BY program_studi
     ORDER BY jumlah DESC"
);
$distribusiProdi = $stmtProdi->fetchAll();

// Top dosen dengan MK terbanyak
$stmtTop = $pdo->query(
    "SELECT d.nama, d.program_studi, COUNT(dm.matkul_id) AS jml_mk
     FROM dosen d
     LEFT JOIN dosen_matkul dm ON d.id = dm.dosen_id
     WHERE d.deleted_at IS NULL
     GROUP BY d.id, d.nama, d.program_studi
     ORDER BY jml_mk DESC
     LIMIT 5"
);
$topDosen = $stmtTop->fetchAll();

// Dosen terbaru ditambahkan
$stmtBaru = $pdo->query(
    "SELECT nama, program_studi, created_at
     FROM dosen
     WHERE deleted_at IS NULL
     ORDER BY created_at DESC
     LIMIT 5"
);
$dosenBaru = $stmtBaru->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SIAKAD Mini</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; }

        .navbar { background:#2563eb; color:#fff; padding:14px 24px; display:flex; justify-content:space-between; align-items:center; }
        .navbar h1 { font-size:18px; font-weight:600; }
        .navbar .nav-right { display:flex; gap:12px; align-items:center; font-size:13px; }
        .navbar a { color:#fff; text-decoration:none; background:rgba(255,255,255,0.2); padding:6px 14px; border-radius:6px; }
        .navbar a:hover { background:rgba(255,255,255,0.3); }

        .container { max-width:1000px; margin:1.5rem auto; padding:0 1rem; }

        .page-title { font-size:20px; font-weight:600; color:#1a1a1a; margin-bottom:1.25rem; }

        /* Stat cards */
        .stat-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:1.5rem; }
        .stat-card { background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .stat-card .num { font-size:32px; font-weight:700; margin-bottom:4px; }
        .stat-card .label { font-size:13px; color:#64748b; }
        .stat-card.blue   .num { color:#2563eb; }
        .stat-card.green  .num { color:#16a34a; }
        .stat-card.orange .num { color:#d97706; }
        .stat-card.red    .num { color:#dc2626; }
        .stat-card.purple .num { color:#7c3aed; }

        /* Grid 2 kolom */
        .row2 { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
        @media(max-width:640px){ .row2 { grid-template-columns:1fr; } }

        .card { background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .card h3 { font-size:15px; font-weight:600; color:#1a1a1a; margin-bottom:1rem; }

        /* Bar chart distribusi prodi */
        .bar-item { margin-bottom:12px; }
        .bar-label { display:flex; justify-content:space-between; font-size:13px; color:#334155; margin-bottom:4px; }
        .bar-track { background:#f1f5f9; border-radius:20px; height:10px; overflow:hidden; }
        .bar-fill  { height:10px; border-radius:20px; background:#2563eb; transition:width 0.6s; }

        /* Tabel sederhana */
        .simple-table { width:100%; border-collapse:collapse; font-size:13px; }
        .simple-table th { text-align:left; padding:8px 10px; color:#64748b; font-weight:500; border-bottom:1px solid #f1f5f9; }
        .simple-table td { padding:8px 10px; border-bottom:1px solid #f8fafc; color:#1a1a1a; }
        .simple-table tr:last-child td { border-bottom:none; }
        .badge-mk { background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:500; }

        .shortcut-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:0.75rem; margin-bottom:1.5rem; }
        .shortcut { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem; text-decoration:none; color:#1a1a1a; display:flex; flex-direction:column; align-items:center; gap:6px; transition:box-shadow 0.2s; }
        .shortcut:hover { box-shadow:0 4px 12px rgba(0,0,0,0.08); }
        .shortcut .ico { font-size:24px; }
        .shortcut .lbl { font-size:13px; font-weight:500; }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>SIAKAD Mini</h1>
    <div class="nav-right">
        <span><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></span>
        <a href="/siakad-mini/public/logout.php">Keluar</a>
    </div>
</nav>

<div class="container">
    <p class="page-title">📊 Dashboard</p>

    <!-- Shortcut Menu -->
    <div class="shortcut-grid">
        <a href="/siakad-mini/public/dosen.php" class="shortcut">
            <span class="ico">👨‍🏫</span><span class="lbl">Data Dosen</span>
        </a>
        <a href="/siakad-mini/public/dosen_create.php" class="shortcut">
            <span class="ico">➕</span><span class="lbl">Tambah Dosen</span>
        </a>
        <?php if ($user['role'] === 'admin'): ?>
        <a href="/siakad-mini/public/trash.php" class="shortcut">
            <span class="ico">🗑️</span><span class="lbl">Arsip</span>
        </a>
        <a href="/siakad-mini/public/audit.php" class="shortcut">
            <span class="ico">📋</span><span class="lbl">Audit Log</span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Stat Cards -->
    <div class="stat-grid">
        <div class="stat-card blue">
            <div class="num"><?= $totalDosen ?></div>
            <div class="label">Dosen Aktif</div>
        </div>
        <div class="stat-card orange">
            <div class="num"><?= $totalNonaktif ?></div>
            <div class="label">Dosen Non-aktif</div>
        </div>
        <div class="stat-card red">
            <div class="num"><?= $totalTrashed ?></div>
            <div class="label">Dosen Dihapus</div>
        </div>
        <div class="stat-card green">
            <div class="num"><?= $totalMatkul ?></div>
            <div class="label">Mata Kuliah</div>
        </div>
        <div class="stat-card purple">
            <div class="num"><?= $totalSks ?></div>
            <div class="label">Total SKS</div>
        </div>
    </div>

    <div class="row2">
        <!-- Distribusi Program Studi -->
        <div class="card">
            <h3>📚 Distribusi Program Studi</h3>
            <?php
            $maxJumlah = max(array_column($distribusiProdi, 'jumlah') ?: [1]);
            foreach ($distribusiProdi as $p):
                $pct = round($p['jumlah'] / $maxJumlah * 100);
            ?>
            <div class="bar-item">
                <div class="bar-label">
                    <span><?= htmlspecialchars($p['program_studi'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span><?= $p['jumlah'] ?> dosen</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($distribusiProdi)): ?>
                <p style="color:#94a3b8; font-size:13px">Belum ada data.</p>
            <?php endif; ?>
        </div>

        <!-- Top Dosen MK terbanyak -->
        <div class="card">
            <h3>🏆 Dosen Paling Banyak MK</h3>
            <table class="simple-table">
                <thead>
                    <tr><th>Nama</th><th>MK</th></tr>
                </thead>
                <tbody>
                <?php foreach ($topDosen as $d): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($d['nama'], ENT_QUOTES, 'UTF-8') ?><br>
                            <small style="color:#94a3b8"><?= htmlspecialchars($d['program_studi'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><span class="badge-mk"><?= $d['jml_mk'] ?> MK</span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($topDosen)): ?>
                    <tr><td colspan="2" style="color:#94a3b8">Belum ada data.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dosen terbaru -->
    <div class="card">
        <h3>🆕 Dosen Terbaru Ditambahkan</h3>
        <table class="simple-table">
            <thead>
                <tr><th>#</th><th>Nama</th><th>Program Studi</th><th>Tanggal</th></tr>
            </thead>
            <tbody>
            <?php foreach ($dosenBaru as $i => $d): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($d['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($d['program_studi'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="color:#94a3b8"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
