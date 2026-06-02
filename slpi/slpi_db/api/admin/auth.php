<?php
// ============================================================
//  SLPI Workshop Hub — api/admin/auth.php
//  Handles admin login, logout, and session check.
//
//  POST /api/admin/auth.php?action=login
//  POST /api/admin/auth.php?action=logout
//  GET  /api/admin/auth.php?action=check
// ============================================================

require_once __DIR__ . '/../../db_config.php';

$action = $_GET['action'] ?? '';

match ($action) {
    'login'  => handleLogin(),
    'logout' => handleLogout(),
    'check'  => handleCheck(),
    default  => respond(['error' => 'Unknown action'], 400),
};

// ── LOGIN ──────────────────────────────────────────────────
function handleLogin(): void {
    $b = body();
    $username = trim($b['username'] ?? '');
    $password = trim($b['password'] ?? '');

    if (!$username || !$password) {
        respond(['error' => 'Username and password are required.'], 400);
    }

    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        respond(['error' => 'Invalid credentials. Please try again.'], 401);
    }

    // Create session token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+8 hours'));

    db()->prepare('
        INSERT INTO admin_sessions (id, admin_id, ip_address, user_agent, expires_at)
        VALUES (?, ?, ?, ?, ?)
    ')->execute([
        $token,
        $admin['id'],
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $expires,
    ]);

    // Set secure cookie
    setcookie('slpi_admin_token', $token, [
        'expires'  => strtotime('+8 hours'),
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        // 'secure' => true,  // Uncomment on HTTPS
    ]);

    respond([
        'success' => true,
        'role'    => $admin['role'],
        'label'   => $admin['label'],
        'email'   => $admin['email'],
    ]);
}

// ── LOGOUT ─────────────────────────────────────────────────
function handleLogout(): void {
    $token = $_COOKIE['slpi_admin_token'] ?? '';
    if ($token) {
        db()->prepare('DELETE FROM admin_sessions WHERE id = ?')->execute([$token]);
        setcookie('slpi_admin_token', '', time() - 3600, '/');
    }
    respond(['success' => true]);
}

// ── CHECK SESSION ───────────────────────────────────────────
function handleCheck(): void {
    $token = $_COOKIE['slpi_admin_token'] ?? '';
    if (!$token) respond(['authenticated' => false], 401);

    $stmt = db()->prepare('
        SELECT a.id, a.username, a.role, a.label, a.email
        FROM admin_sessions s
        JOIN admins a ON a.id = s.admin_id
        WHERE s.id = ? AND s.expires_at > NOW()
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $admin = $stmt->fetch();

    if (!$admin) {
        setcookie('slpi_admin_token', '', time() - 3600, '/');
        respond(['authenticated' => false], 401);
    }

    respond(['authenticated' => true, 'admin' => $admin]);
}
