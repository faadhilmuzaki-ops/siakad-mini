<?php
// public/login.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Kalau sudah login, langsung ke halaman utama
if (isLoggedIn()) {
    header('Location: /siakad-mini/public/index.php');
    exit;
}

$error = '';

// ── PROSES FORM LOGIN ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Validasi CSRF
    verifyCsrf();

    // 2. Ambil & bersihkan input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // 3. Validasi input tidak kosong
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // 4. Cari user di database (prepared statement — aman dari SQL Injection)
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        // 5. Verifikasi password dengan password_verify()
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login berhasil!

            // Regenerate session ID — cegah Session Fixation Attack
            session_regenerate_id(true);

            // Simpan data user ke session (jangan simpan password!)
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ];

            // Redirect ke halaman utama
            header('Location: /siakad-mini/public/index.php');
            exit;
        } else {
            // Username atau password salah
            $error = 'Username atau password salah.';
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

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #fff;
            border-radius: 12px;
            padding: 2rem 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.08);
        }

        .login-card h1 {
            font-size: 22px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .login-card p.subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #333;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            color: #1a1a1a;
            transition: border-color 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        .btn-login {
            width: 100%;
            padding: 11px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.2s;
        }

        .btn-login:hover { background: #1d4ed8; }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 1rem;
        }

        .demo-info {
            margin-top: 1.5rem;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 12px;
            color: #555;
        }

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
            <input
                type="text"
                id="username"
                name="username"
                value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Masukkan username"
                autocomplete="username"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Masukkan password"
                autocomplete="current-password"
                required
            >
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
"<?php // login" 
