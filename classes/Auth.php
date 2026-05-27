<?php
// classes/Auth.php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class Auth
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ── LOGIN ────────────────────────────────────────────────

    public function login(string $username, string $password): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ];
            return true;
        }
        return false;
    }

    // ── LOGOUT ───────────────────────────────────────────────

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── CEK STATUS ───────────────────────────────────────────

    public function check(): bool
    {
        return !empty($_SESSION['user']);
    }

    public function user(): array
    {
        return $_SESSION['user'] ?? [];
    }

    public function isAdmin(): bool
    {
        return ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    // ── GUARD ────────────────────────────────────────────────

    // Redirect ke login kalau belum login
    public function guard(): void
    {
        if (!$this->check()) {
            header('Location: /siakad-mini/public/login.php');
            exit;
        }
    }

    // Tolak akses kalau bukan admin
    public function guardAdmin(): void
    {
        $this->guard();
        if (!$this->isAdmin()) {
            http_response_code(403);
            die('<p style="color:red;padding:20px;font-family:sans-serif">
                 ⛔ Akses ditolak. Halaman ini hanya untuk admin.</p>');
        }
    }
}
