-- Počiatočná schéma (SQLite). Ďalšie zmeny pridávaj ako 002_*.sql, 003_*.sql ...
CREATE TABLE users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE COLLATE NOCASE,
    email         TEXT NOT NULL UNIQUE COLLATE NOCASE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('user','admin')),
    created_at    TEXT NOT NULL,
    updated_at    TEXT NOT NULL
);

CREATE TABLE media (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    kind           TEXT NOT NULL CHECK (kind IN ('image','video')),
    title          TEXT NOT NULL DEFAULT '',
    description    TEXT NOT NULL DEFAULT '',
    filename       TEXT NOT NULL,          -- náhodný názov súboru na serveri
    path           TEXT NOT NULL,          -- verejná cesta, napr. uploads/images/abc.webp
    thumb_path     TEXT NOT NULL,          -- náhľad
    mime           TEXT NOT NULL,
    size           INTEGER NOT NULL,
    width          INTEGER,
    height         INTEGER,
    uploaded_by    INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    created_at     TEXT NOT NULL,
    updated_at     TEXT NOT NULL
);
CREATE INDEX idx_media_kind_created ON media(kind, created_at);

CREATE TABLE sessions (
    id            TEXT PRIMARY KEY,
    data          TEXT NOT NULL,
    user_id       INTEGER,
    last_activity INTEGER NOT NULL
);
CREATE INDEX idx_sessions_activity ON sessions(last_activity);
CREATE INDEX idx_sessions_user ON sessions(user_id);

CREATE TABLE remember_tokens (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    selector       TEXT NOT NULL UNIQUE,
    validator_hash TEXT NOT NULL,
    expires_at     INTEGER NOT NULL,
    created_at     INTEGER NOT NULL
);

CREATE TABLE auth_attempts (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    kind       TEXT NOT NULL,      -- login | register
    ident      TEXT NOT NULL,      -- e-mail (login) alebo IP
    ip         TEXT NOT NULL,
    created_at INTEGER NOT NULL
);
CREATE INDEX idx_attempts ON auth_attempts(kind, ident, created_at);
CREATE INDEX idx_attempts_ip ON auth_attempts(kind, ip, created_at);
