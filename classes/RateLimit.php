<?php
// classes/RateLimit.php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class RateLimit
{
    private PDO $pdo;
    private int $maxPercobaan = 5;      // Maks percobaan
    private int $durasiMenit  = 15;     // Dikunci berapa menit

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // Catat percobaan login gagal
    public function catatGagal(string $username): void
    {
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_attempts (username, ip) VALUES (:username, :ip)'
        );
        $stmt->execute([':username' => $username, ':ip' => $ip]);
    }

    // Hapus percobaan setelah login berhasil
    public function reset(string $username): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM login_attempts WHERE username = :username'
        );
        $stmt->execute([':username' => $username]);
    }

    // Cek apakah username ini sedang dikunci
    public function dikunci(string $username): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE username = :username
             AND created_at >= NOW() - INTERVAL :menit MINUTE'
        );
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':menit', $this->durasiMenit, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn() >= $this->maxPercobaan;
    }

    // Hitung sisa percobaan
    public function sisaPercobaan(string $username): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE username = :username
             AND created_at >= NOW() - INTERVAL :menit MINUTE'
        );
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':menit', $this->durasiMenit, PDO::PARAM_INT);
        $stmt->execute();
        $sudah = (int)$stmt->fetchColumn();
        return max(0, $this->maxPercobaan - $sudah);
    }
}
