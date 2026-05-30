<?php
// classes/AuditLog.php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class AuditLog
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // Catat aksi ke tabel audit_log (dalam transaksi yang sama)
    public function catat(string $aksi, string $tabel, string $keterangan): void
    {
        $user = $_SESSION['user'] ?? ['id' => 0, 'username' => 'system'];

        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, username, aksi, tabel, keterangan)
             VALUES (:user_id, :username, :aksi, :tabel, :keterangan)'
        );
        $stmt->execute([
            ':user_id'     => $user['id'],
            ':username'    => $user['username'],
            ':aksi'        => $aksi,
            ':tabel'       => $tabel,
            ':keterangan'  => $keterangan,
        ]);
    }

    // Ambil semua log terbaru
    public function getAll(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM audit_log ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
