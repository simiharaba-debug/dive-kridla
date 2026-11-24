-- Nastavenia webu, ktoré mení administrátor (napr. vzhľad: nový / predošlý dizajn).
CREATE TABLE IF NOT EXISTS site_settings (
    name        VARCHAR(80) PRIMARY KEY,
    value       TEXT NOT NULL,
    updated_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
    updated_at  VARCHAR(40) NOT NULL
);
