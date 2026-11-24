-- Kategórie galérie spravované administrátorom (verejná aj súkromná galéria, fotografie aj videá z YouTube).
-- Pôvodné kategórie z data/media.php a data/videos.php sa sem doplnia automaticky (static_key), dajú sa premenovať,
-- zmeniť im náhľad, poradie aj odstrániť (deleted = 1, dajú sa obnoviť).
CREATE TABLE IF NOT EXISTS gallery_categories (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    scope        TEXT NOT NULL DEFAULT 'public' CHECK (scope IN ('public','private')),
    kind         TEXT NOT NULL CHECK (kind IN ('photo','video')),
    slug         TEXT NOT NULL,
    title        TEXT NOT NULL,
    description  TEXT NOT NULL DEFAULT '',
    cover        TEXT NOT NULL DEFAULT '',
    sort         INTEGER NOT NULL DEFAULT 0,
    static_key   TEXT,
    deleted      INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT NOT NULL,
    updated_at   TEXT NOT NULL,
    UNIQUE (scope, slug)
);
-- Zmeny pôvodných (súborových) položiek: presun do inej kategórie alebo odstránenie z galérie.
CREATE TABLE IF NOT EXISTS gallery_overrides (
    item_type    TEXT NOT NULL CHECK (item_type IN ('photo','video')),
    item_key     TEXT NOT NULL,
    category_id  INTEGER REFERENCES gallery_categories(id) ON DELETE SET NULL,
    hidden       INTEGER NOT NULL DEFAULT 0,
    updated_at   TEXT NOT NULL,
    PRIMARY KEY (item_type, item_key)
);
ALTER TABLE media ADD COLUMN category_id INTEGER REFERENCES gallery_categories(id) ON DELETE SET NULL;
ALTER TABLE private_media ADD COLUMN category_id INTEGER REFERENCES gallery_categories(id) ON DELETE SET NULL;
-- videá: rozsah (verejná / súkromná galéria) a kategória; to isté video môže byť v oboch galériách
CREATE TABLE videos_new (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    scope          TEXT NOT NULL DEFAULT 'public' CHECK (scope IN ('public','private')),
    category_id    INTEGER REFERENCES gallery_categories(id) ON DELETE SET NULL,
    yt_id          TEXT NOT NULL,
    title          TEXT NOT NULL DEFAULT '',
    description    TEXT NOT NULL DEFAULT '',
    orientation    TEXT NOT NULL DEFAULT 'landscape' CHECK (orientation IN ('landscape','portrait')),
    width          INTEGER,
    height         INTEGER,
    duration       INTEGER,
    added_by       INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at     TEXT NOT NULL,
    updated_at     TEXT NOT NULL,
    UNIQUE (scope, yt_id)
);
INSERT INTO videos_new (id, scope, yt_id, title, description, orientation, width, height, duration, added_by, created_at, updated_at)
    SELECT id, 'public', yt_id, title, description, orientation, width, height, duration, added_by, created_at, updated_at FROM videos;
DROP TABLE videos;
ALTER TABLE videos_new RENAME TO videos;
CREATE INDEX IF NOT EXISTS idx_videos_created ON videos (created_at);
CREATE INDEX IF NOT EXISTS idx_media_category ON media (category_id);
CREATE INDEX IF NOT EXISTS idx_private_media_category ON private_media (category_id);
