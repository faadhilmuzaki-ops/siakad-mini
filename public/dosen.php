<?php
// public/dosen.php — Daftar dosen + pencarian + paginasi
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireLogin();

$pdo  = getDB();
$user = currentUser();

// ── PARAMETER PENCARIAN & PAGINASI ───────────────────────────
$cari     = trim($_GET['cari'] ?? '');
$halaman  = max(1, (int)($_GET['halaman'] ?? 1));
$perHal   = 5;
$offset   = ($halaman - 1) * $perHal;

// ── QUERY HITUNG TOTAL ───────────────────────────────────────
$sqlTotal = 'SELECT COUNT(*) FROM dosen WHERE deleted_at IS NULL';
$params   = [];

if ($cari !== '') {
    $sqlTotal .= ' AND (nama LIKE :cari OR nidn LIKE :cari2 OR email LIKE :cari3)';
    $params[':cari']  = "%{$cari}%";
    $params[':cari2'] = "%{$cari}%";
    $params[':cari3'] = "%{$cari}%";
}

$stmtTotal  = $pdo->prepare($sqlTotal);
$stmtTotal->execute($params);
$total      = (int)$stmtTotal->fetchColumn();
$totalHal   = (int)ceil($total / $perHal);

// ── QUERY DATA DOSEN ─────────────────────────────────────────
$sqlData = 'SELECT d.*, 
            (SELECT COUNT(*) FROM dosen_matkul dm WHERE dm.dosen_id = d.id) AS jml_matkul
            FROM dosen d
            WHERE d.deleted_at IS NULL';

if ($cari !== '') {
    $sqlData .= ' AND (d.nama LIKE :cari OR d.nidn LIKE :cari2 OR d.email LIKE :cari3)';
}

$sqlData .= ' ORDER BY d.nama ASC LIMIT :limit OFFSET :offset';

$stmtData = $pdo->prepare($sqlData);

if ($cari !== '') {
    $stmtData->bindValue(':cari',  "%{$cari}%");
    $stmtData->bindValue(':cari2', "%{$cari}%");
    $stmtData->bindValue(':cari3', "%{$cari}%");
}
$stmtData->bindValue(':limit',  $perHal, PDO::PARAM_INT);
$stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtData->execute();
$listDosen = $stmtData->fetchAll();

// ── PESAN FLASH ──────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dosen — SIAKAD Mini</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }

        .navbar {
            background: #2563eb; color: #fff;
            padding: 14px 24px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .navbar h1 { font-size: 18px; font-weight: 600; }
        .navbar .nav-right { display: flex; align-items: center; gap: 12px; font-size: 13px; }
        .navbar a { color:#fff; text-decoration:none; background:rgba(255,255,255,0.2); padding:6px 14px; border-radius:6px; }
        .navbar a:hover { background:rgba(255,255,255,0.3); }

        .container { max-width: 1000px; margin: 1.5rem auto; padding: 0 1rem; }

        .card { background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 2px 8px rgba(0,0,0,0.06); }

        .toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:10px; }

        .toolbar h2 { font-size:18px; color:#1a1a1a; }

        .toolbar-right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

        .search-form { display:flex; gap:6px; }
        .search-form input { padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; width:220px; }
        .search-form input:focus { outline:none; border-color:#2563eb; }

        .btn { padding:8px 16px; border-radius:8px; font-size:13px; font-weight:500; cursor:pointer; border:none; text-decoration:none; display:inline-block; }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-primary:hover { background:#1d4ed8; }
        .btn-secondary { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }
        .btn-secondary:hover { background:#e2e8f0; }
        .btn-warning { background:#f59e0b; color:#fff; }
        .btn-warning:hover { background:#d97706; }
        .btn-danger { background:#ef4444; color:#fff; }
        .btn-danger:hover { background:#dc2626; }
        .btn-sm { padding:5px 10px; font-size:12px; }

        table { width:100%; border-collapse:collapse; font-size:14px; }
        thead th { background:#f8fafc; padding:10px 12px; text-align:left; font-weight:500; color:#64748b; border-bottom:1px solid #e2e8f0; }
        tbody td { padding:10px 12px; border-bottom:1px solid #f1f5f9; color:#1a1a1a; vertical-align:middle; }
        tbody tr:hover { background:#f8fafc; }

        .badge { display:inline-block; padding:3px 8px; border-radius:20px; font-size:11px; font-weight:500; }
        .badge-aktif { background:#dcfce7; color:#15803d; }
        .badge-nonaktif { background:#fee2e2; color:#b91c1c; }

        .foto-thumb { width:36px; height:36px; border-radius:50%; object-fit:cover; background:#e2e8f0; }
        .foto-placeholder { width:36px; height:36px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:14px; color:#94a3b8; }

        .actions { display:flex; gap:6px; }

        .alert { padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:1rem; }
        .alert-success { background:#f0fdf4; border:1px solid #86efac; color:#15803d; }

        .pagination { display:flex; gap:6px; justify-content:center; margin-top:1.5rem; align-items:center; }
        .pagination a, .pagination span {
            padding:6px 12px; border-radius:6px; font-size:13px; text-decoration:none;
            border:1px solid #e2e8f0; color:#334155;
        }
        .pagination a:hover { background:#f1f5f9; }
        .pagination .active { background:#2563eb; color:#fff; border-color:#2563eb; }
        .pagination .disabled { color:#cbd5e1; pointer-events:none; }

        .empty-state { text-align:center; padding:3rem; color:#94a3b8; }
        .empty-state p { font-size:14px; margin-top:8px; }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>SIAKAD Mini</h1>
    <div class="nav-right">
        <a href="/siakad-mini/public/index.php">Dashboard</a>
        <span><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></span>
        <a href="/siakad-mini/public/logout.php">Keluar</a>
    </div>
</nav>

<div class="container">

    <?php if ($flash !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <h2>Data Dosen</h2>
            <div class="toolbar-right">
                <form class="search-form" method="GET" action="">
                    <input type="text" name="cari" placeholder="Cari nama / NIDN / email..."
                           value="<?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                    <?php if ($cari !== ''): ?>
                        <a href="/siakad-mini/public/dosen.php" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
                <a href="/siakad-mini/public/dosen_create.php" class="btn btn-primary">+ Tambah Dosen</a>
            </div>
        </div>

        <?php if (empty($listDosen)): ?>
            <div class="empty-state">
                <div style="font-size:40px">👨‍🏫</div>
                <p><?= $cari !== '' ? 'Tidak ada dosen yang cocok dengan pencarian.' : 'Belum ada data dosen.' ?></p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="width:44px">Foto</th>
                        <th>Nama</th>
                        <th>NIDN</th>
                        <th>Program Studi</th>
                        <th>MK Diampu</th>
                        <th>Status</th>
                        <th style="width:130px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($listDosen as $i => $d): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td>
                            <?php if ($d['foto'] && file_exists(__DIR__ . '/../uploads/' . $d['foto'])): ?>
                                <img src="/siakad-mini/uploads/<?= htmlspecialchars($d['foto'], ENT_QUOTES, 'UTF-8') ?>"
                                     alt="foto" class="foto-thumb">
                            <?php else: ?>
                                <div class="foto-placeholder">👤</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($d['nama'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <small style="color:#94a3b8"><?= htmlspecialchars($d['email'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><?= htmlspecialchars($d['nidn'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($d['program_studi'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align:center"><?= (int)$d['jml_matkul'] ?> MK</td>
                        <td>
                            <span class="badge badge-<?= $d['status'] ?>">
                                <?= htmlspecialchars($d['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="/siakad-mini/public/dosen_edit.php?id=<?= $d['id'] ?>"
                                   class="btn btn-warning btn-sm">Edit</a>
                                <?php if ($user['role'] === 'admin'): ?>
                                <a href="/siakad-mini/public/dosen_delete.php?id=<?= $d['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Hapus dosen <?= htmlspecialchars($d['nama'], ENT_QUOTES, 'UTF-8') ?>?')">
                                   Hapus
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalHal > 1): ?>
            <div class="pagination">
                <?php
                $base = '/siakad-mini/public/dosen.php?' . ($cari !== '' ? 'cari=' . urlencode($cari) . '&' : '');
                ?>
                <a href="<?= $base ?>halaman=1" class="<?= $halaman === 1 ? 'disabled' : '' ?>">« Awal</a>
                <?php for ($p = max(1, $halaman - 2); $p <= min($totalHal, $halaman + 2); $p++): ?>
                    <?php if ($p === $halaman): ?>
                        <span class="active"><?= $p ?></span>
                    <?php else: ?>
                        <a href="<?= $base ?>halaman=<?= $p ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <a href="<?= $base ?>halaman=<?= $totalHal ?>" class="<?= $halaman === $totalHal ? 'disabled' : '' ?>">Akhir »</a>
            </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <div style="margin-top:1rem; font-size:12px; color:#94a3b8; text-align:center;">
        Menampilkan <?= count($listDosen) ?> dari <?= $total ?> dosen
        <?= $cari !== '' ? '(hasil pencarian: "' . htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') . '")' : '' ?>
    </div>
</div>

</body>
</html>
"<?php // dosen list" 
