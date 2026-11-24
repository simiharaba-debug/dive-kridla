-- Výrez hlavných fotografií a náhľadov (admin-fotky → „Upraviť výrez“), 22. 11. 2026.
-- pos_x / pos_y = zaostrený bod v % (object-position), zoom = priblíženie 1–3. ref = fotografia, ku ktorej výrez patrí
-- (po výmene fotografie sa starý výrez nepoužije). Bez riadku = stred fotografie, bez priblíženia.
CREATE TABLE IF NOT EXISTS page_photo_pos (
    slug        TEXT NOT NULL,
    slot        TEXT NOT NULL CHECK (slot IN ('hero','card')),
    ref         TEXT NOT NULL,
    pos_x       REAL NOT NULL DEFAULT 50,
    pos_y       REAL NOT NULL DEFAULT 50,
    zoom        REAL NOT NULL DEFAULT 1,
    updated_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
    updated_at  TEXT NOT NULL,
    PRIMARY KEY (slug, slot)
);
