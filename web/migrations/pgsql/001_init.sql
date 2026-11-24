-- Počiatočná schéma pre PostgreSQL (rovnaká štruktúra ako migrations/001_init.sql pre SQLite).
-- citext = text bez ohľadu na veľkosť písmen (ako COLLATE NOCASE v SQLite) – e-mail aj meno sú unikátne bez ohľadu na veľkosť písmen.
CREATE EXTENSION IF NOT EXISTS citext;

CREATE TABLE users (
    id            BIGSERIAL PRIMARY KEY,
    username      CITEXT NOT NULL UNIQUE,
    email         CITEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('user','admin')),
    created_at    TEXT NOT NULL,
    updated_at    TEXT NOT NULL
);

CREATE TABLE media (
    id             BIGSERIAL PRIMARY KEY,
    kind           TEXT NOT NULL CHECK (kind IN ('image','video')),
    title          TEXT NOT NULL DEFAULT '',
    description    TEXT NOT NULL DEFAULT '',
    filename       TEXT NOT NULL,
    path           TEXT NOT NULL,
    thumb_path     TEXT NOT NULL,
    mime           TEXT NOT NULL,
    size           BIGINT NOT NULL,
    width          INTEGER,
    height         INTEGER,
    uploaded_by    BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    created_at     TEXT NOT NULL,
    updated_at     TEXT NOT NULL
);
CREATE INDEX idx_media_kind_created ON media(kind, created_at);

CREATE TABLE sessions (
    id            TEXT PRIMARY KEY,
    data          TEXT NOT NULL,
    user_id       BIGINT,
    last_activity BIGINT NOT NULL
);
CREATE INDEX idx_sessions_activity ON sessions(last_activity);
CREATE INDEX idx_sessions_user ON sessions(user_id);

CREATE TABLE remember_tokens (
    id             BIGSERIAL PRIMARY KEY,
    user_id        BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    selector       TEXT NOT NULL UNIQUE,
    validator_hash TEXT NOT NULL,
    expires_at     BIGINT NOT NULL,
    created_at     BIGINT NOT NULL
);

CREATE TABLE auth_attempts (
    id         BIGSERIAL PRIMARY KEY,
    kind       TEXT NOT NULL,
    ident      TEXT NOT NULL,
    ip         TEXT NOT NULL,
    created_at BIGINT NOT NULL
);
CREATE INDEX idx_attempts ON auth_attempts(kind, ident, created_at);
CREATE INDEX idx_attempts_ip ON auth_attempts(kind, ip, created_at);
