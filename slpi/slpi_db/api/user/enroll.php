<?php
// ============================================================
//  SLPI Workshop Hub — api/user/enroll.php
//  Public users enroll in workshops (login required).
//
//  POST /api/user/enroll.php?action=enroll   (body: {workshop_id})
//  GET  /api/user/enroll.php?action=my_enrollments
//  POST /api/user/enroll.php?action=mark_read&notif_id=X
//  POST /api/user/enroll.php?action=mark_all_read
// ============================================================

require_once __DIR__ . '/../../db_config.php';

session_start();
$action = $_GET['action'] ?? '';

match ($action) {
    'enroll'          => enroll(),
    'my_enrollments'  => myEnrollments(),
    'mark_read'       => markRead(),
    'mark_all_read'   => markAllRead(),
    default           => respond(['error' => 'Unknown action'], 400),
};

// ── ENROLL ──────────────────────────────────────────────────
function enroll(): void {
    $user = requireUser();
    $b    = body();
    $wsId = (int)($b['workshop_id'] ?? 0);
    if (!$wsId) respond(['error' => 'workshop_id is required'], 400);

    // Check workshop exists and is active
    $wsStmt = db()->prepare('SELECT * FROM workshops WHERE id=? AND is_active=1 LIMIT 1');
    $wsStmt->execute([$wsId]);
    $ws = $wsStmt->fetch();
    if (!$ws) respond(['error' => 'Workshop not found or inactive'], 404);

    // Check capacity
    $countStmt = db()->prepare('SELECT COUNT(*) AS cnt FROM enrollments WHERE workshop_id=?');
    $countStmt->execute([$wsId]);
    $enrolled = (int)$countStmt->fetch()['cnt'];
    if ($enrolled >= $ws['max_participants']) {
        respond(['error' => 'This workshop is fully booked'], 409);
    }

    // Check duplicate enrollment
    $dupStmt = db()->prepare('SELECT id FROM enrollments WHERE workshop_id=? AND user_id=? LIMIT 1');
    $dupStmt->execute([$wsId, $user['id']]);
    if ($dupStmt->fetch()) respond(['error' => 'You are already enrolled in this workshop'], 409);

    // Enroll
    db()->prepare('
        INSERT INTO enrollments (workshop_id, user_id, name, email, phone, nic, workplace)
        VALUES (?,?,?,?,?,?,?)
    ')->execute([
        $wsId, $user['id'],
        $user['name'], $user['email'], $user['phone'], $user['nic'], $user['workplace'],
    ]);
    $enrollId = (int)db()->lastInsertId();

    // Notify user
    $subject = "You are enrolled in \"{$ws['title']}\"";
    $body    = "Dear {$user['name']},\n\n"
             . "Thank you for enrolling in \"{$ws['title']}\" on {$ws['workshop_date']} at {$ws['location']}.\n\n"
             . "We will notify you of any updates. Good luck!\n\n"
             . "Best regards,\nSLPI Workshop Hub";

    db()->prepare('
        INSERT INTO user_notifications (user_id, enrollment_id, type, subject, body, workshop_title)
        VALUES (?,?,?,?,?,?)
    ')->execute([$user['id'], $enrollId, 'enrolled', $subject, $body, $ws['title']]);

    respond(['success' => true, 'enrollment_id' => $enrollId, 'message' => 'Enrolled successfully!'], 201);
}

// ── MY ENROLLMENTS ──────────────────────────────────────────
function myEnrollments(): void {
    $user = requireUser();
    $stmt = db()->prepare('
        SELECT e.*, w.title, w.category, w.workshop_date, w.workshop_time,
               w.location, w.image_url, t.name AS trainer_name
        FROM enrollments e
        JOIN workshops w  ON w.id = e.workshop_id
        LEFT JOIN trainers t ON t.id = w.trainer_id
        WHERE e.user_id = ?
        ORDER BY e.enrolled_at DESC
    ');
    $stmt->execute([$user['id']]);
    respond(['enrollments' => $stmt->fetchAll()]);
}

// ── MARK ONE NOTIF READ ─────────────────────────────────────
function markRead(): void {
    $user   = requireUser();
    $notifId = (int)($_GET['notif_id'] ?? 0);
    db()->prepare('UPDATE user_notifications SET is_read=1 WHERE id=? AND user_id=?')
        ->execute([$notifId, $user['id']]);
    respond(['success' => true]);
}

// ── MARK ALL READ ───────────────────────────────────────────
function markAllRead(): void {
    $user = requireUser();
    db()->prepare('UPDATE user_notifications SET is_read=1 WHERE user_id=?')
        ->execute([$user['id']]);
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
