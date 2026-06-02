<?php
// ============================================================
//  SLPI Workshop Hub — api/admin/email_log.php
//  Read rejection email log.
//
//  GET ?action=list[&workshop_id=X]
//  GET ?action=get&id=X
// ============================================================

require_once __DIR__ . '/../../db_config.php';

$admin  = requireAdminSession();
$action = $_GET['action'] ?? '';

match ($action) {
    'list' => listEmails($admin),
    'get'  => getEmail($admin),
    default => respond(['error' => 'Unknown action'], 400),
};

function requireAdminSession(): array {
    $token = $_COOKIE['slpi_admin_token'] ?? '';
    if (!$token) respond(['error' => 'Not authenticated'], 401);
    $stmt = db()->prepare('SELECT a.id, a.role FROM admin_sessions s JOIN admins a ON a.id=s.admin_id WHERE s.id=? AND s.expires_at>NOW() LIMIT 1');
    $stmt->execute([$token]);
    $admin = $stmt->fetch();
    if (!$admin) respond(['error' => 'Session expired'], 401);
    return $admin;
}

function listEmails(array $admin): void {
    $wsId = (int)($_GET['workshop_id'] ?? 0);

    if ($admin['role'] === 'super') {
        $where  = $wsId ? 'WHERE el.workshop_id=?' : '';
        $params = $wsId ? [$wsId] : [];
    } else {
        $where  = 'WHERE w.owner=?' . ($wsId ? ' AND el.workshop_id=?' : '');
        $params = $wsId ? [$admin['role'], $wsId] : [$admin['role']];
    }

    $sql = "
        SELECT el.id, el.recipient_name, el.recipient_email, el.workshop_title,
               el.subject, el.sent_at, w.owner AS workshop_owner
        FROM email_log el
        LEFT JOIN workshops w ON w.id=el.workshop_id
        $where
        ORDER BY el.sent_at DESC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    respond(['emails' => $stmt->fetchAll()]);
}

function getEmail(array $admin): void {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare('SELECT el.*, w.owner AS workshop_owner FROM email_log el LEFT JOIN workshops w ON w.id=el.workshop_id WHERE el.id=? LIMIT 1');
    $stmt->execute([$id]);
    $e = $stmt->fetch();
    if (!$e) respond(['error' => 'Email not found'], 404);
    if ($admin['role'] !== 'super' && $e['workshop_owner'] !== $admin['role']) {
        respond(['error' => 'Forbidden'], 403);
    }
    respond(['email' => $e]);
}
