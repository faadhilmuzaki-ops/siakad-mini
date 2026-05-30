<?php
// public/login.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/RateLimit.php';

if (isLoggedIn()) {
    header('Location: /siakad-mini/public/dashboard.php');
    exit;
}

$error    = '';
$rateLimit = new RateLimit();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']     ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif ($rateLimit->dikunci($username)) {
        $error = 'Akun dikunci sementara karena terlalu banyak percobaan login. Coba lagi 15 menit lagi.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ];
            $rateLimit->reset($username);
            header('Location: /siakad-mini/public/dashboard.php');
            exit;
        } else {
            $rateLimit->catatGagal($username);
            $sisa  = $rateLimit->sisaPercobaan($username);
            $error = $sisa > 0
                ? "Username atau password salah. Sisa percobaan: {$sisa}x."
                : 'Akun dikunci 15 menit karena terlalu banyak percobaan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIAKAD Mini</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #fff; border-radius: 12px; padding: 2rem 2.5rem; width: 100%; max-width: 400px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); }
        .login-card h1 { font-size: 22px; font-weight: 600; color: #1a1a1a; margin-bottom: 4px; }
        .login-card p.subtitle { font-size: 14px; color: #666; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: 13px; font-weight: 500; color: #333; margin-bottom: 6px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        input:focus { outline: none; border-color: #2563eb; }
        .btn-login { width: 100%; padding: 11px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 500; cursor: pointer; margin-top: 0.5rem; }
        .btn-login:hover { background: #1d4ed8; }
        .alert-error { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 1rem; }
        .demo-info { margin-top: 1.5rem; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 12px; color: #555; }
        .demo-info strong { color: #333; }
    </style>
</head>
<body>
<div class="login-card">
    <h1>SIAKAD Mini</h1>
    <p class="subtitle">Sistem Informasi Manajemen Dosen</p>

    <?php if ($error !== ''): ?>
        <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?php csrfInput(); ?>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Masukkan username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn-login">Masuk</button>
    </form>

    <div class="demo-info">
        <strong>Akun demo:</strong><br>
        Admin &nbsp;&nbsp;: admin / Admin123!<br>
        Operator: operator / Operator123!
    </div>
</div>
</body>
</html>
