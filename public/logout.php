<?php
// public/logout.php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session di browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session
session_destroy();

// Redirect ke halaman login
header('Location: /siakad-mini/public/login.php');
exit;
