<?php
// ============================================================
//  SLPI Workshop Hub — api/user/auth.php
//  Public user signup, login, and profile.
//
//  POST /api/user/auth.php?action=signup
//  POST /api/user/auth.php?action=login
//  GET  /api/user/auth.php?action=profile   (requires token)
//  PUT  /api/user/auth.php?action=profile   (requires token)
//  POST /api/user/auth.php?action=logout
// ============================================================

require_once __DIR__ . '/../../db_config.php';

session_start();
$action = $_GET['action'] ?? '';

match ($action) {
    'signup'  => handleSignup(),
    'login'   => handleLogin(),
    'profile' => $_SERVER['REQUEST_METHOD'] === 'PUT' ? updateProfile() : getProfile(),
    'logout'  => handleLogout(),
    default   => respond(['error' => 'Unknown action'], 400),
};

// ── SIGNUP ──────────────────────────────────────────────────
function handleSignup(): void {
    $b = body();

    $required = ['name','email','phone','nic','password','confirm'];
    foreach ($required as $f) {
        if (empty($b[$f])) respond(['error' => "$f is required"], 400);
    }

    if (!filter_var($b['email'], FILTER_VALIDATE_EMAIL)) {
        respond(['error' => 'Invalid email address'], 400);
    }
    if ($b['password'] !== $b['confirm']) {
        respond(['error' => 'Passwords do not match'], 400);
    }
    if (strlen($b['password']) < 6) {
        respond(['error' => 'Password must be at least 6 characters'], 400);
    }
    if (!preg_match('/[A-Z]/', $b['password'])) {
        respond(['error' => 'Password must contain at least one uppercase letter'], 400);
    }
    if (!preg_match('/[0-9]/', $b['password'])) {
        respond(['error' => 'Password must contain at least one number'], 400);
    }

    // Check duplicate email
    $chk = db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $chk->execute([strtolower($b['email'])]);
    if ($chk->fetch()) respond(['error' => 'An account with this email already exists'], 409);

    $hash = password_hash($b['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    db()->prepare('INSERT INTO users (name, email, phone, nic, workplace, password) VALUES (?,?,?,?,?,?)')
        ->execute([
            $b['name'],
            strtolower($b['email']),
            $b['phone'],
            $b['nic'],
            $b['workplace'] ?? null,
            $hash,
        ]);

    $userId = (int)db()->lastInsertId();
    $_SESSION['slpi_user_id'] = $userId;

    respond(['success' => true, 'message' => 'Account created successfully!'], 201);
}

// ── LOGIN ───────────────────────────────────────────────────
function handleLogin(): void {
    $b = body();
    $email    = strtolower(trim($b['email'] ?? ''));
    $password = $b['password'] ?? '';

    if (!$email || !$password) respond(['error' => 'Email and password are required'], 400);

    $stmt = db()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        respond(['error' => 'Invalid email or password'], 401);
    }

    $_SESSION['slpi_user_id'] = $user['id'];

    respond([
        'success' => true,
        'user'    => [
            'id'        => $user['id'],
            'name'      => $user['name'],
            'email'     => $user['email'],
            'phone'     => $user['phone'],
            'nic'       => $user['nic'],
            'workplace' => $user['workplace'],
        ],
    ]);
}

// ── GET PROFILE ─────────────────────────────────────────────
function getProfile(): void {
    $user = requireUser();
    $uid  = $user['id'];

    // Stats
    $stats = db()->prepare('
        SELECT
          COUNT(*) AS total_enrolled,
          SUM(is_selected) AS total_selected
        FROM enrollments WHERE user_id=?
    ');
    $stats->execute([$uid]);
    $s = $stats->fetch();

    // Attended
    $att = db()->prepare('
        SELECT COUNT(*) AS cnt FROM attendance a
        JOIN enrollments e ON e.id=a.enrollment_id
        WHERE e.user_id=? AND a.attended=1
    ');
    $att->execute([$uid]);
    $attended = (int)$att->fetch()['cnt'];

    // Enrollments with workshop details
    $enrStmt = db()->prepare('
        SELECT e.*, w.title, w.category, w.workshop_date, w.workshop_time,
               w.location, w.image_url, w.trainer_id,
               t.name AS trainer_name,
               a.attended
        FROM enrollments e
        JOIN workshops w ON w.id=e.workshop_id
        LEFT JOIN trainers t ON t.id=w.trainer_id
        LEFT JOIN attendance a ON a.enrollment_id=e.id
        WHERE e.user_id=?
        ORDER BY e.enrolled_at DESC
    ');
    $enrStmt->execute([$uid]);
    $enrollments = $enrStmt->fetchAll();

    // Notifications
    $notifStmt = db()->prepare('
        SELECT * FROM user_notifications WHERE user_id=? ORDER BY sent_at DESC LIMIT 50
    ');
    $notifStmt->execute([$uid]);
    $notifications = $notifStmt->fetchAll();

    respond([
        'user'          => $user,
        'stats'         => [
            'total_enrolled' => (int)$s['total_enrolled'],
            'total_selected' => (int)$s['total_selected'],
            'total_attended' => $attended,
            'total_absent'   => (int)$s['total_enrolled'] - $attended,
        ],
        'enrollments'   => $enrollments,
        'notifications' => $notifications,
    ]);
}

// ── UPDATE PROFILE ──────────────────────────────────────────
function updateProfile(): void {
    $user = requireUser();
    $b    = body();

    $name      = trim($b['name']      ?? $user['name']);
    $email     = strtolower(trim($b['email']     ?? $user['email']));
    $phone     = trim($b['phone']     ?? $user['phone']);
    $nic       = trim($b['nic']       ?? $user['nic']);
    $workplace = trim($b['workplace'] ?? $user['workplace']);

    if (!$name || !$email) respond(['error' => 'Name and email are required'], 400);

    // Check email conflict
    $chk = db()->prepare('SELECT id FROM users WHERE email=? AND id!=? LIMIT 1');
    $chk->execute([$email, $user['id']]);
    if ($chk->fetch()) respond(['error' => 'Email already in use by another account'], 409);

    // Password change?
    $newHash = $user['password'];
    if (!empty($b['current_password']) || !empty($b['new_password'])) {
        if (!password_verify($b['current_password'] ?? '', $user['password'])) {
            respond(['error' => 'Current password is incorrect'], 400);
        }
        if (strlen($b['new_password'] ?? '') < 6) {
            respond(['error' => 'New password must be at least 6 characters'], 400);
        }
        $newHash = password_hash($b['new_password'], PASSWORD_BCRYPT, ['cost' => 12]);
    }

    db()->prepare('UPDATE users SET name=?, email=?, phone=?, nic=?, workplace=?, password=? WHERE id=?')
        ->execute([$name, $email, $phone, $nic, $workplace, $newHash, $user['id']]);

    respond(['success' => true, 'message' => 'Profile updated successfully!']);
}

// ── LOGOUT ──────────────────────────────────────────────────
function handleLogout(): void {
    $_SESSION = [];
    session_destroy();
    respond(['success' => true]);
}

// ── HELPER ──────────────────────────────────────────────────
function requireUser(): array {
    if (empty($_SESSION['slpi_user_id'])) respond(['error' => 'Not authenticated'], 401);
    $stmt = db()->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
    $stmt->execute([$_SESSION['slpi_user_id']]);
    $user = $stmt->fetch();
    if (!$user) respond(['error' => 'User not found'], 404);
    return $user;
}
