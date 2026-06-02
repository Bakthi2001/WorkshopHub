<?php
// ============================================================
//  SLPI Workshop Hub — api/admin/workshops.php
//  Full CRUD for workshops.
//
//  GET    ?action=list [&owner=admin1]
//  GET    ?action=get&id=5
//  POST   ?action=create          (body: workshop fields)
//  PUT    ?action=update&id=5     (body: workshop fields)
//  DELETE ?action=delete&id=5
// ============================================================

require_once __DIR__ . '/../../db_config.php';

$admin = requireAdminSession();
$action = $_GET['action'] ?? '';

match ($action) {
    'list'   => listWorkshops($admin),
    'get'    => getWorkshop($admin),
    'create' => createWorkshop($admin),
    'update' => updateWorkshop($admin),
    'delete' => deleteWorkshop($admin),
    default  => respond(['error' => 'Unknown action'], 400),
};

// ── AUTH GUARD ──────────────────────────────────────────────
function requireAdminSession(): array {
    $token = $_COOKIE['slpi_admin_token'] ?? '';
    if (!$token) respond(['error' => 'Not authenticated'], 401);

    $stmt = db()->prepare('
        SELECT a.id, a.role, a.username
        FROM admin_sessions s
        JOIN admins a ON a.id = s.admin_id
        WHERE s.id = ? AND s.expires_at > NOW()
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $admin = $stmt->fetch();
    if (!$admin) respond(['error' => 'Session expired'], 401);
    return $admin;
}

// ── LIST ────────────────────────────────────────────────────
function listWorkshops(array $admin): void {
    // Super admin sees all; others see only their own
    $owner = $_GET['owner'] ?? null;

    if ($admin['role'] === 'super') {
        $where = $owner ? 'WHERE w.owner = ?' : '';
        $params = $owner ? [$owner] : [];
    } else {
        $where  = 'WHERE w.owner = ?';
        $params = [$admin['role']];  // 'admin1' or 'admin2'
    }

    $sql = "
        SELECT w.*,
               t.name  AS trainer_name,
               t.role  AS trainer_role,
               COUNT(e.id) AS enrolled_count
        FROM workshops w
        LEFT JOIN trainers t    ON t.id = w.trainer_id
        LEFT JOIN enrollments e ON e.workshop_id = w.id
        $where
        GROUP BY w.id
        ORDER BY w.workshop_date ASC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    respond(['workshops' => $stmt->fetchAll()]);
}

// ── GET SINGLE ──────────────────────────────────────────────
function getWorkshop(array $admin): void {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare('SELECT w.*, t.name AS trainer_name FROM workshops w LEFT JOIN trainers t ON t.id=w.trainer_id WHERE w.id=? LIMIT 1');
    $stmt->execute([$id]);
    $ws = $stmt->fetch();
    if (!$ws) respond(['error' => 'Workshop not found'], 404);
    canModify($admin, $ws);
    respond(['workshop' => $ws]);
}

// ── CREATE ──────────────────────────────────────────────────
function createWorkshop(array $admin): void {
    if ($admin['role'] === 'super') respond(['error' => 'Super admin cannot create workshops'], 403);

    $b = body();
    validate($b, ['title', 'category', 'workshop_date']);

    // Resolve trainer_id from name if needed
    $trainerId = resolveTrainer($b['trainer_id'] ?? null, $b['trainer'] ?? null);

    // Auto-generate slug
    $slug = generateSlug($b['title']);

    $stmt = db()->prepare('
        INSERT INTO workshops
            (slug, owner, trainer_id, title, category, description, image_url,
             workshop_date, workshop_time, location, max_participants, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,1)
    ');
    $stmt->execute([
        $slug,
        $admin['role'],
        $trainerId,
        $b['title'],
        $b['category'],
        $b['description'] ?? null,
        $b['image_url'] ?? null,
        $b['workshop_date'],
        $b['workshop_time'] ?? null,
        $b['location'] ?? null,
        (int)($b['max_participants'] ?? 30),
    ]);
    $id = db()->lastInsertId();
    respond(['success' => true, 'id' => $id, 'slug' => $slug], 201);
}

// ── UPDATE ──────────────────────────────────────────────────
function updateWorkshop(array $admin): void {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM workshops WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $ws = $stmt->fetch();
    if (!$ws) respond(['error' => 'Workshop not found'], 404);
    canModify($admin, $ws);

    $b = body();
    $trainerId = resolveTrainer($b['trainer_id'] ?? null, $b['trainer'] ?? null);

    db()->prepare('
        UPDATE workshops SET
            trainer_id=?, title=?, category=?, description=?, image_url=?,
            workshop_date=?, workshop_time=?, location=?, max_participants=?, is_active=?
        WHERE id=?
    ')->execute([
        $trainerId,
        $b['title']           ?? $ws['title'],
        $b['category']        ?? $ws['category'],
        $b['description']     ?? $ws['description'],
        $b['image_url']       ?? $ws['image_url'],
        $b['workshop_date']   ?? $ws['workshop_date'],
        $b['workshop_time']   ?? $ws['workshop_time'],
        $b['location']        ?? $ws['location'],
        (int)($b['max_participants'] ?? $ws['max_participants']),
        isset($b['is_active']) ? (int)$b['is_active'] : $ws['is_active'],
        $id,
    ]);
    respond(['success' => true]);
}

// ── DELETE ──────────────────────────────────────────────────
function deleteWorkshop(array $admin): void {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM workshops WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $ws = $stmt->fetch();
    if (!$ws) respond(['error' => 'Workshop not found'], 404);
    canModify($admin, $ws);

    // Cascades to enrollments, email_log, upcoming_events via FK
    db()->prepare('DELETE FROM workshops WHERE id=?')->execute([$id]);
    respond(['success' => true]);
}

// ── HELPERS ─────────────────────────────────────────────────
function canModify(array $admin, array $ws): void {
    if ($admin['role'] !== 'super' && $ws['owner'] !== $admin['role']) {
        respond(['error' => 'Forbidden'], 403);
    }
}

function validate(array $data, array $required): void {
    foreach ($required as $field) {
        if (empty($data[$field])) {
            respond(['error' => "$field is required"], 400);
        }
    }
}

function generateSlug(string $title): string {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    $slug = trim($slug, '-');
    // Ensure uniqueness
    $base = $slug;
    $i = 1;
    while (true) {
        $s = db()->prepare('SELECT id FROM workshops WHERE slug=? LIMIT 1');
        $s->execute([$slug]);
        if (!$s->fetch()) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function resolveTrainer(?int $trainerId, ?string $name): ?int {
    if ($trainerId) return $trainerId;
    if (!$name) return null;
    $s = db()->prepare('SELECT id FROM trainers WHERE name=? LIMIT 1');
    $s->execute([$name]);
    $row = $s->fetch();
    return $row ? (int)$row['id'] : null;
}
