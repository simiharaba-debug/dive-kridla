-- Kategórie galérie (PostgreSQL) – zodpovedá migrations/004_gallery.sql.
CREATE TABLE IF NOT EXISTS gallery_categories (
    id           BIGSERIAL PRIMARY KEY,
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
CREATE TABLE IF NOT EXISTS gallery_overrides (
    item_type    TEXT NOT NULL CHECK (item_type IN ('photo','video')),
    item_key     TEXT NOT NULL,
    category_id  BIGINT REFERENCES gallery_categories(id) ON DELETE SET NULL,
    hidden       INTEGER NOT NULL DEFAULT 0,
    updated_at   TEXT NOT NULL,
    PRIMARY KEY (item_type, item_key)
);
ALTER TABLE media ADD COLUMN IF NOT EXISTS category_id BIGINT REFERENCES gallery_categories(id) ON DELETE SET NULL;
ALTER TABLE private_media ADD COLUMN IF NOT EXISTS category_id BIGINT REFERENCES gallery_categories(id) ON DELETE SET NULL;
ALTER TABLE videos ADD COLUMN IF NOT EXISTS scope TEXT NOT NULL DEFAULT 'public';
ALTER TABLE videos ADD COLUMN IF NOT EXISTS category_id BIGINT REFERENCES gallery_categories(id) ON DELETE SET NULL;
ALTER TABLE videos DROP CONSTRAINT IF EXISTS videos_yt_id_key;
CREATE UNIQUE INDEX IF NOT EXISTS uq_videos_scope_yt ON videos (scope, yt_id);
CREATE INDEX IF NOT EXISTS idx_media_category ON media (category_id);
CREATE INDEX IF NOT EXISTS idx_private_media_category ON private_media (category_id);
