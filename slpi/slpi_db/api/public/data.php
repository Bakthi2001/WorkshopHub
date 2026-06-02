<?php
// ============================================================
//  SLPI Workshop Hub — api/public/data.php
//  Read-only public endpoints for the front-end pages.
//
//  GET ?resource=workshops[&category=X][&search=Y]
//  GET ?resource=workshop&id=5
//  GET ?resource=trainers
//  GET ?resource=resources
//  GET ?resource=upcoming
//  GET ?resource=testimonials
//  POST ?resource=testimonials  (submit feedback from index.html)
// ============================================================

require_once __DIR__ . '/../../db_config.php';

$resource = $_GET['resource'] ?? '';
$method   = $_SERVER['REQUEST_METHOD'];

// Route
if ($resource === 'testimonials' && $method === 'POST') {
    submitTestimonial();
    exit;
}

match ($resource) {
    'workshops'    => getWorkshops(),
    'workshop'     => getWorkshop(),
    'trainers'     => getTrainers(),
    'resources'    => getResources(),
    'upcoming'     => getUpcoming(),
    'testimonials' => getTestimonials(),
    default        => respond(['error' => 'Unknown resource'], 400),
};

// ── WORKSHOPS (index.html grid + upcoming.html) ─────────────
function getWorkshops(): void {
    $category = $_GET['category'] ?? '';
    $search   = trim($_GET['search'] ?? '');

    $conditions = ['w.is_active = 1'];
    $params     = [];

    if ($category && $category !== 'All') {
        $conditions[] = 'w.category = ?';
        $params[] = $category;
    }
    if ($search) {
        $conditions[] = '(w.title LIKE ? OR w.description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $stmt = db()->prepare("
        SELECT w.id, w.slug, w.title, w.category, w.description, w.image_url,
               w.workshop_date, w.workshop_time, w.location, w.max_participants,
               t.name AS trainer_name,
               COUNT(e.id) AS enrolled_count
        FROM workshops w
        LEFT JOIN trainers t    ON t.id = w.trainer_id
        LEFT JOIN enrollments e ON e.workshop_id = w.id
        $where
        GROUP BY w.id
        ORDER BY w.workshop_date ASC
    ");
    $stmt->execute($params);
    respond(['workshops' => $stmt->fetchAll()]);
}

// ── SINGLE WORKSHOP ─────────────────────────────────────────
function getWorkshop(): void {
    $id   = (int)($_GET['id'] ?? 0);
    $slug = $_GET['slug'] ?? '';

    if ($id) {
        $stmt = db()->prepare('SELECT w.*, t.name AS trainer_name, t.bio AS trainer_bio FROM workshops w LEFT JOIN trainers t ON t.id=w.trainer_id WHERE w.id=? AND w.is_active=1 LIMIT 1');
        $stmt->execute([$id]);
    } elseif ($slug) {
        $stmt = db()->prepare('SELECT w.*, t.name AS trainer_name, t.bio AS trainer_bio FROM workshops w LEFT JOIN trainers t ON t.id=w.trainer_id WHERE w.slug=? AND w.is_active=1 LIMIT 1');
        $stmt->execute([$slug]);
    } else {
        respond(['error' => 'id or slug required'], 400);
    }

    $ws = $stmt->fetch();
    if (!$ws) respond(['error' => 'Workshop not found'], 404);
    respond(['workshop' => $ws]);
}

// ── TRAINERS (trainers.html) ─────────────────────────────────
function getTrainers(): void {
    $stmt = db()->query('SELECT id, name, role, bio, avatar_url FROM trainers ORDER BY id ASC');
    respond(['trainers' => $stmt->fetchAll()]);
}

// ── RESOURCES (resources.html) ───────────────────────────────
function getResources(): void {
    $type = $_GET['type'] ?? '';
    if ($type) {
        $stmt = db()->prepare('SELECT * FROM resources WHERE is_active=1 AND type=? ORDER BY id ASC');
        $stmt->execute([$type]);
    } else {
        $stmt = db()->query('SELECT * FROM resources WHERE is_active=1 ORDER BY id ASC');
    }
    respond(['resources' => $stmt->fetchAll()]);
}

// ── UPCOMING EVENTS (upcoming.html) ──────────────────────────
function getUpcoming(): void {
    $stmt = db()->query("
        SELECT u.*, w.slug AS workshop_slug
        FROM upcoming_events u
        LEFT JOIN workshops w ON w.id = u.workshop_id
        WHERE u.status != 'Cancelled'
        ORDER BY u.event_date ASC
    ");
    respond(['events' => $stmt->fetchAll()]);
}

// ── TESTIMONIALS (index.html) ─────────────────────────────────
function getTestimonials(): void {
    $stmt = db()->query('SELECT id, author_name, author_role, message, avatar_url, created_at FROM testimonials WHERE is_approved=1 ORDER BY created_at DESC LIMIT 20');
    respond(['testimonials' => $stmt->fetchAll()]);
}

// ── SUBMIT TESTIMONIAL ────────────────────────────────────────
function submitTestimonial(): void {
    $b = body();
    if (empty($b['name']) || empty($b['message'])) {
        respond(['error' => 'Name and message are required'], 400);
    }

    // Auto-generate avatar URL using UI Avatars API
    $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($b['name']) . '&background=10b981&color=fff&size=96';

    db()->prepare('INSERT INTO testimonials (author_name, author_role, message, avatar_url, is_approved) VALUES (?,?,?,?,1)')
        ->execute([
            htmlspecialchars($b['name']),
            htmlspecialchars($b['role'] ?? ''),
            htmlspecialchars($b['message']),
            $avatarUrl,
        ]);

    respond(['success' => true, 'message' => 'Feedback submitted. Thank you!'], 201);
}
