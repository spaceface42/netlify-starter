PRAGMA foreign_keys = ON;

-- =========================
-- Projects
-- =========================
CREATE TABLE IF NOT EXISTS projects (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  description TEXT,
  client TEXT,
  year INTEGER,
  cover_image TEXT,

  meta_title TEXT,
  meta_description TEXT,

  published INTEGER NOT NULL DEFAULT 1 CHECK (published IN (0,1)),
  sort_order INTEGER DEFAULT 0,

  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_projects_published ON projects(published);
CREATE INDEX IF NOT EXISTS idx_projects_sort ON projects(sort_order);

-- =========================
-- Images per project
-- =========================
CREATE TABLE IF NOT EXISTS project_images (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  filename TEXT NOT NULL,
  alt_text TEXT,
  sort_order INTEGER DEFAULT 0,

  FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_project_images_project ON project_images(project_id);
CREATE INDEX IF NOT EXISTS idx_project_images_sort ON project_images(sort_order);

-- =========================
-- Tags
-- =========================
CREATE TABLE IF NOT EXISTS tags (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE
);

-- =========================
-- Project ↔ Tags
-- =========================
CREATE TABLE IF NOT EXISTS project_tags (
  project_id INTEGER NOT NULL,
  tag_id INTEGER NOT NULL,
  PRIMARY KEY (project_id, tag_id),

  FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE,

  FOREIGN KEY (tag_id)
    REFERENCES tags(id)
    ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_project_tags_tag ON project_tags(tag_id);

-- =========================
-- Optional project metadata
-- =========================
CREATE TABLE IF NOT EXISTS project_meta (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  meta_key TEXT NOT NULL,
  meta_value TEXT,

  FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_project_meta_key ON project_meta(meta_key);
