<?php
// public/dosen_delete.php — Soft delete dosen
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireLogin();
requireAdmin(); // Hanya admin yang boleh hapus

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

// Cek dosen ada
$stmt = $pdo->prepare('SELECT nama FROM dosen WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$dosen = $stmt->fetch();

if (!$dosen) {
    http_response_code(404);
    die('<p style="padding:20px;color:red">Dosen tidak ditemukan.</p>');
}

// Soft delete — isi deleted_at, BUKAN hapus baris!
$stmt = $pdo->prepare('UPDATE dosen SET deleted_at = NOW() WHERE id = :id');
$stmt->execute([':id' => $id]);

$_SESSION['flash'] = 'Dosen ' . $dosen['nama'] . ' berhasil dihapus (bisa dipulihkan di Arsip).';
header('Location: /siakad-mini/public/dosen.php');
exit;
