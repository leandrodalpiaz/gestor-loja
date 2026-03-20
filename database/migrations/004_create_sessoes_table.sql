CREATE TABLE sessoes (
    id SERIAL PRIMARY KEY,
    data TIMESTAMP NOT NULL,
    grau VARCHAR(50),
    tipo VARCHAR(50),
    pauta TEXT
);