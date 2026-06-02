<?php
// ============================================================
//  SLPI Workshop Hub — api/admin/enrollments.php
//  Manage enrollments from admin panels.
//
//  GET    ?action=list[&workshop_id=X][&search=Y]
//  POST   ?action=toggle_select&id=X
//  DELETE ?action=remove&id=X
//  GET    ?action=export[&workshop_id=X][&selected_only=1]
// ============================================================

require_once __DIR__ . '/../../db_config.php';

$admin = requireAdminSession();
$action = $_GET['action'] ?? '';

match ($action) {
    'list'          => listEnrollments($admin),
    'toggle_select' => toggleSelect($admin),
    'remove'        => removeEnrollment($admin),
    'export'        => exportCSV($admin),
    default         => respond(['error' => 'Unknown action'], 400),
};

// ── AUTH GUARD ──────────────────────────────────────────────
function requireAdminSession(): array {
    $token = $_COOKIE['slpi_admin_token'] ?? '';
    if (!$token) respond(['error' => 'Not authenticated'], 401);
    $stmt = db()->prepare('
        SELECT a.id, a.role FROM admin_sessions s
        JOIN admins a ON a.id = s.admin_id
        WHERE s.id = ? AND s.expires_at > NOW() LIMIT 1
    ');
    $stmt->execute([$token]);
    $admin = $stmt->fetch();
    if (!$admin) respond(['error' => 'Session expired'], 401);
    return $admin;
}

// ── SCOPE FILTER (which workshops can this admin access?) ───
function ownerFilter(array $admin): array {
    if ($admin['role'] === 'super') return [null, []];
    return ['AND w.owner = ?', [$admin['role']]];
}

// ── LIST ────────────────────────────────────────────────────
function listEnrollments(array $admin): void {
    [$ownerClause, $ownerParams] = ownerFilter($admin);

    $workshopId = (int)($_GET['workshop_id'] ?? 0);
    $search     = trim($_GET['search'] ?? '');

    $conditions = ['1=1'];
    $params     = [];

    if ($ownerClause) { $conditions[] = ltrim($ownerClause, 'AND '); $params = array_merge($params, $ownerParams); }
    if ($workshopId)  { $conditions[] = 'e.workshop_id = ?'; $params[] = $workshopId; }
    if ($search) {
        $conditions[] = '(e.name LIKE ? OR e.email LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $sql = "
        SELECT e.*, w.title AS workshop_title, w.owner AS workshop_owner
        FROM enrollments e
        JOIN workshops w ON w.id = e.workshop_id
        $where
        ORDER BY e.enrolled_at DESC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    respond(['enrollments' => $stmt->fetchAll()]);
}

// ── TOGGLE SELECTED ─────────────────────────────────────────
function toggleSelect(array $admin): void {
    $id = (int)($_GET['id'] ?? 0);
    $e  = fetchEnrollment($id, $admin);

    $newVal = $e['is_selected'] ? 0 : 1;
    db()->prepare('UPDATE enrollments SET is_selected=?, status=? WHERE id=?')
        ->execute([$newVal, $newVal ? 'selected' : 'enrolled', $id]);

    // Fire user notification
    $notifType = $newVal ? 'selected' : 'deselected';
    $subject   = $newVal
        ? "You have been selected for \"{$e['workshop_title']}\""
        : "Your selection for \"{$e['workshop_title']}\" has been updated";
    $body = $newVal
        ? "Dear {$e['name']},\n\nCongratulations! You have been selected to attend \"{$e['workshop_title']}\".\n\nPlease confirm your attendance at info@slpi.lk.\n\nBest regards,\nSLPI Workshop Hub"
        : "Dear {$e['name']},\n\nYour selection status for \"{$e['workshop_title']}\" has been updated by the organiser.\n\nBest regards,\nSLPI Workshop Hub";

    db()->prepare('
        INSERT INTO user_notifications (user_id, enrollment_id, type, subject, body, workshop_title)
        VALUES (?,?,?,?,?,?)
    ')->execute([$e['user_id'], $id, $notifType, $subject, $body, $e['workshop_title']]);

    respond(['success' => true, 'is_selected' => $newVal]);
}

// ── REMOVE (reject) ─────────────────────────────────────────
function removeEnrollment(array $admin): void {
    $id = (int)($_GET['id'] ?? 0);
    $e  = fetchEnrollment($id, $admin);

    // Log rejection email
    $subject = "Your enrollment in \"{$e['workshop_title']}\" has been cancelled";
    $body    = "Dear {$e['name']},\n\n"
             . "We regret to inform you that your enrollment in the workshop \"{$e['workshop_title']}\" "
             . "has been removed by the organiser.\n\n"
             . "If you have questions, please contact us at info@slpi.lk.\n\n"
             . "Best regards,\nSLPI Workshop Hub";

    db()->prepare('
        INSERT INTO email_log
            (enrollment_id, workshop_id, user_id, recipient_name, recipient_email, workshop_title, subject, body)
        VALUES (?,?,?,?,?,?,?,?)
    ')->execute([
        $id, $e['workshop_id'], $e['user_id'],
        $e['name'], $e['email'], $e['workshop_title'],
        $subject, $body,
    ]);

    // Also store as user notification
    db()->prepare('
        INSERT INTO user_notifications (user_id, enrollment_id, type, subject, body, workshop_title)
        VALUES (?,?,?,?,?,?)
    ')->execute([$e['user_id'], $id, 'rejected', $subject, $body, $e['workshop_title']]);

    // Update status then delete
    db()->prepare('UPDATE enrollments SET status=? WHERE id=?')->execute(['rejected', $id]);
    db()->prepare('DELETE FROM enrollments WHERE id=?')->execute([$id]);

    respond(['success' => true, 'email_sent_to' => $e['email']]);
}

// ── EXPORT CSV ──────────────────────────────────────────────
function exportCSV(array $admin): void {
    [$ownerClause, $ownerParams] = ownerFilter($admin);

    $workshopId   = (int)($_GET['workshop_id'] ?? 0);
    $selectedOnly = (int)($_GET['selected_only'] ?? 0);

    $conditions = ['1=1'];
    $params     = [];

    if ($ownerClause) { $conditions[] = ltrim($ownerClause, 'AND '); $params = array_merge($params, $ownerParams); }
    if ($workshopId)  { $conditions[] = 'e.workshop_id = ?'; $params[] = $workshopId; }
    if ($selectedOnly){ $conditions[] = 'e.is_selected = 1'; }

    $where = 'WHERE ' . implode(' AND ', $conditions);
    $sql   = "SELECT e.*, w.title AS workshop_title FROM enrollments e JOIN workshops w ON w.id=e.workshop_id $where ORDER BY e.enrolled_at DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="enrollments_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name','Email','Phone','NIC','Workplace','Workshop','Enrolled At','Status','Selected']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['name'], $r['email'], $r['phone'], $r['nic'],
            $r['workplace'], $r['workshop_title'], $r['enrolled_at'],
            $r['status'], $r['is_selected'] ? 'Yes' : 'No',
        ]);
    }
    fclose($out);
    exit;
}

// ── FETCH SINGLE (with access check) ────────────────────────
function fetchEnrollment(int $id, array $admin): array {
    $stmt = db()->prepare('
        SELECT e.*, w.title AS workshop_title, w.owner AS workshop_owner
        FROM enrollments e JOIN workshops w ON w.id=e.workshop_id
        WHERE e.id=? LIMIT 1
    ');
    $stmt->execute([$id]);
    $e = $stmt->fetch();
    if (!$e) respond(['error' => 'Enrollment not found'], 404);
    if ($admin['role'] !== 'super' && $e['workshop_owner'] !== $admin['role']) {
        respond(['error' => 'Forbidden'], 403);
    }
    return $e;
}
