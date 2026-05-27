<?php
// config/session.php
// Helper untuk session, guard, dan CSRF token
// Di-require oleh semua halaman yang butuh login

declare(strict_types=1);

// Mulai session kalau belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── GUARD: Cek apakah sudah login ────────────────────────────
// Panggil fungsi ini di atas setiap halaman protected
function requireLogin(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: /siakad-mini/public/login.php');
        exit;
    }
}

// ── GUARD: Cek role admin ────────────────────────────────────
function requireAdmin(): void
{
    requireLogin();
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        die('<p style="color:red;padding:20px">Akses ditolak. Hanya admin yang boleh masuk halaman ini.</p>');
    }
}

// ── CEK APAKAH SUDAH LOGIN (return bool) ────────────────────
function isLoggedIn(): bool
{
    return !empty($_SESSION['user']);
}

// ── AMBIL DATA USER DARI SESSION ────────────────────────────
function currentUser(): array
{
    return $_SESSION['user'] ?? [];
}

// ── CSRF TOKEN ───────────────────────────────────────────────

// Generate token baru (simpan di session)
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Cetak hidden input CSRF — taruh di dalam setiap form POST
function csrfInput(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

// Validasi CSRF — panggil sebelum proses POST
function verifyCsrf(): void
{
    $token     = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if (!hash_equals($expected, $token)) {
        http_response_code(403);
        die('<p style="color:red;padding:20px">Token CSRF tidak valid. Silakan refresh halaman dan coba lagi.</p>');
    }

    // Token sekali pakai — hapus setelah validasi
    unset($_SESSION['csrf_token']);
}
"<?php // session helper" 
