-- Hlavné fotografie a náhľady podstránok (PostgreSQL) – zodpovedá migrations/005_page_photos.sql.
CREATE TABLE IF NOT EXISTS page_photos (
    slug        TEXT NOT NULL,
    slot        TEXT NOT NULL CHECK (slot IN ('hero','card')),
    ref         TEXT NOT NULL,
    updated_by  BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_at  TEXT NOT NULL,
    PRIMARY KEY (slug, slot)
);
