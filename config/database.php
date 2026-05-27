<?php
/**
 * config/database.php
 * Koneksi PDO — di-require oleh semua halaman PHP.
 */

declare(strict_types=1);

// ── Konfigurasi ──────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'siakad_mini');
define('DB_USER',    'root');
define('DB_PASS',    '');          // XAMPP/Laragon default: kosong
define('DB_CHARSET', 'utf8mb4');
// ─────────────────────────────────────────────────────────────

/**
 * Mengembalikan instance PDO (singleton).
 * Koneksi hanya dibuat sekali lalu dipakai ulang sepanjang request.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            // Lempar PDOException saat ada error SQL
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Prepared statement asli di MySQL — cegah SQL Injection
            PDO::ATTR_EMULATE_PREPARES   => false,

            // fetchAll() langsung return array ['kolom' => 'nilai']
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        // Catat ke log server, jangan tampilkan detail ke user
        error_log('[DB ERROR] ' . $e->getMessage());
        http_response_code(500);
        die('<p style="color:red">Koneksi database gagal. Hubungi administrator.</p>');
    }

    return $pdo;
}
