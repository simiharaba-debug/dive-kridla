-- Hlavné fotografie a náhľady podstránok zvolené administrátorom (stránka admin-fotky).
-- Pôvodné nastavenie je v data/page_photos.php; riadok tu ho prepíše. ref = „s:ID“ (data/media.php) alebo „m:ID“ (tabuľka media).
CREATE TABLE IF NOT EXISTS page_photos (
    slug        TEXT NOT NULL,
    slot        TEXT NOT NULL CHECK (slot IN ('hero','card')),
    ref         TEXT NOT NULL,
    updated_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
    updated_at  TEXT NOT NULL,
    PRIMARY KEY (slug, slot)
);
