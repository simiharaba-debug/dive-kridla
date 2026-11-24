-- Videá z YouTube pridané administrátorom (PostgreSQL) – zodpovedá migrations/003_videos.sql.
CREATE TABLE IF NOT EXISTS videos (
    id             BIGSERIAL PRIMARY KEY,
    yt_id          TEXT NOT NULL UNIQUE,
    title          TEXT NOT NULL DEFAULT '',
    description    TEXT NOT NULL DEFAULT '',
    orientation    TEXT NOT NULL DEFAULT 'landscape' CHECK (orientation IN ('landscape','portrait')),
    width          INTEGER,
    height         INTEGER,
    duration       INTEGER,
    added_by       BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at     TEXT NOT NULL,
    updated_at     TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_videos_created ON videos (created_at);
