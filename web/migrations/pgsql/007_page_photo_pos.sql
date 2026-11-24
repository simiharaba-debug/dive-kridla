-- Výrez hlavných fotografií a náhľadov (PostgreSQL) – zodpovedá migrations/007_page_photo_pos.sql.
CREATE TABLE IF NOT EXISTS page_photo_pos (
    slug        TEXT NOT NULL,
    slot        TEXT NOT NULL CHECK (slot IN ('hero','card')),
    ref         TEXT NOT NULL,
    pos_x       DOUBLE PRECISION NOT NULL DEFAULT 50,
    pos_y       DOUBLE PRECISION NOT NULL DEFAULT 50,
    zoom        DOUBLE PRECISION NOT NULL DEFAULT 1,
    updated_by  BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_at  TEXT NOT NULL,
    PRIMARY KEY (slug, slot)
);
