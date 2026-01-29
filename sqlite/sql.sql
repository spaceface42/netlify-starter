BEGIN TRANSACTION;

/* =========================
   PROJECTS
   ========================= */

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
(
    1,
    'spaceface-branding',
    'SpaceFace Branding',
    'Visual identity and experimental layout system for SpaceFace.',
    'SpaceFace',
    2024,
    1,
    datetime('now')
),
(
    2,
    'sandor-sandor-landing',
    'Sandor & Sandor Landing Page',
    'Landing page concept for a small design studio with organic layout.',
    'Sandor & Sandor',
    2025,
    1,
    datetime('now')
),
(
    3,
    'internal-experimental-tool',
    'Internal Experimental Tool',
    'Unpublished internal tool exploring generative layouts and motion.',
    NULL,
    2025,
    0,
    datetime('now')
);

/* =========================
   TAGS
   ========================= */

INSERT INTO tags (id, name) VALUES
(1, 'branding'),
(2, 'web'),
(3, 'experimental'),
(4, 'css'),
(5, 'javascript'),
(6, 'internal');

/* =========================
   PROJECT ↔ TAG RELATIONS
   ========================= */

INSERT INTO project_tags (project_id, tag_id) VALUES
-- SpaceFace Branding
(1, 1),
(1, 2),
(1, 3),

-- Sandor & Sandor Landing
(2, 2),
(2, 4),
(2, 5),

-- Internal Tool (unpublished)
(3, 3),
(3, 5),
(3, 6);

/* =========================
   PROJECT METADATA
   ========================= */

INSERT INTO project_meta (project_id, meta_key, meta_value) VALUES
-- SpaceFace
(1, 'role', 'Design & Frontend'),
(1, 'tools', 'Figma, Vanilla JS, CSS'),
(1, 'layout', 'Organic / non-grid'),

-- Sandor & Sandor
(2, 'role', 'Visual Design'),
(2, 'tools', 'Figma, HTML, CSS'),
(2, 'focus', 'Landing conversion'),

-- Internal Tool
(3, 'role', 'R&D'),
(3, 'tools', 'JavaScript, Canvas'),
(3, 'status', 'Work in progress');

/* =========================
   PROJECT IMAGES
   ========================= */

INSERT INTO project_images (
    project_id,
    filename,
    alt_text,
    sort_order
) VALUES
-- SpaceFace images
(1, 'spaceface-01.jpg', 'SpaceFace logo exploration', 1),
(1, 'spaceface-02.jpg', 'Experimental homepage layout', 2),

-- Sandor & Sandor images
(2, 'sandor-01.jpg', 'Landing page hero section', 1),
(2, 'sandor-02.jpg', 'Typography detail', 2),

-- Internal tool images
(3, 'internal-01.jpg', 'Generative layout experiment', 1);

COMMIT;
