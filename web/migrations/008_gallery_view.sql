-- Právo iba na prezeranie súkromnej administrátorskej galérie (admin-pouzivatelia), 24. 11. 2026.
ALTER TABLE users ADD COLUMN gallery_view INTEGER NOT NULL DEFAULT 0;
