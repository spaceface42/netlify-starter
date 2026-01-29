
<?php
// declare(strict_types=1);

/* =========================
   CONFIG
   ========================= */

$dbPath = __DIR__ . '/database.sqlite';

/* =========================
   CONNECT TO SQLITE
   ========================= */

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

/* =========================
   SQL TO RUN
   ========================= */

$sql = <<<SQL
BEGIN TRANSACTION;

INSERT INTO projects (
    id,
    slug,
    title,
    description,
    client,
    year,
    published,
    created_at
) VALUES
(1, 'spaceface-branding', 'SpaceFace Branding',
 'Visual identity and experimental layout system for SpaceFace.',
 'SpaceFace', 2024, 1, datetime('now')),
(2, 'sandor-sandor-landing', 'Sandor & Sandor Landing Page',
 'Landing page concept for a small design studio with organic layout.',
 'Sandor & Sandor', 2025, 1, datetime('now')),
(3, 'internal-experimental-tool', 'Internal Experimental Tool',
 'Unpublished internal tool exploring generative layouts and motion.',
 NULL, 2025, 0, datetime('now'));

INSERT INTO tags (id, name) VALUES
(1, 'branding'),
(2, 'web'),
(3, 'experimental'),
(4, 'css'),
(5, 'javascript'),
(6, 'internal');

INSERT INTO project_tags (project_id, tag_id) VALUES
(1, 1), (1, 2), (1, 3),
(2, 2), (2, 4), (2, 5),
(3, 3), (3, 5), (3, 6);

INSERT INTO project_meta (project_id, meta_key, meta_value) VALUES
(1, 'role', 'Design & Frontend'),
(1, 'tools', 'Figma, Vanilla JS, CSS'),
(1, 'layout', 'Organic / non-grid'),
(2, 'role', 'Visual Design'),
(2, 'tools', 'Figma, HTML, CSS'),
(2, 'focus', 'Landing conversion'),
(3, 'role', 'R&D'),
(3, 'tools', 'JavaScript, Canvas'),
(3, 'status', 'Work in progress');

INSERT INTO project_images (project_id, filename, alt_text, sort_order) VALUES
(1, 'spaceface-01.jpg', 'SpaceFace logo exploration', 1),
(1, 'spaceface-02.jpg', 'Experimental homepage layout', 2),
(2, 'sandor-01.jpg', 'Landing page hero section', 1),
(2, 'sandor-02.jpg', 'Typography detail', 2),
(3, 'internal-01.jpg', 'Generative layout experiment', 1);

COMMIT;
SQL;

/* =========================
   EXECUTE SQL
   ========================= */

try {
    $pdo->exec($sql);
    echo "✅ Database populated successfully.";
} catch (PDOException $e) {
    echo "❌ SQL error: " . $e->getMessage();
}
