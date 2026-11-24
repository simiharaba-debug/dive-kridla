-- Súkromná administrátorská galéria (PostgreSQL) – zodpovedá migrations/002_private_media.sql.
CREATE TABLE IF NOT EXISTS private_media (
    id             BIGSERIAL PRIMARY KEY,
    kind           TEXT NOT NULL CHECK (kind IN ('image','video')),
    title          TEXT NOT NULL DEFAULT '',
    description    TEXT NOT NULL DEFAULT '',
    filename       TEXT NOT NULL,
    path           TEXT NOT NULL,
    thumb_path     TEXT NOT NULL DEFAULT '',
    mime           TEXT NOT NULL,
    size           BIGINT NOT NULL,
    width          INTEGER,
    height         INTEGER,
    uploaded_by    BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    created_at     TEXT NOT NULL,
    updated_at     TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_private_media_created ON private_media (created_at);
