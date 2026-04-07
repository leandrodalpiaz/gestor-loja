-- Biblioteca: base para paridade web/mobile e evolucao multi-loja

CREATE TABLE IF NOT EXISTS lojas (
    id SERIAL PRIMARY KEY,
    numero_loja VARCHAR(10) NOT NULL UNIQUE,
    sigla VARCHAR(10) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    oriente VARCHAR(120),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO lojas (numero_loja, sigla, nome, oriente)
SELECT
    '270',
    'R',
    'Loja Renascenca',
    NULL
WHERE NOT EXISTS (
    SELECT 1
    FROM lojas
    WHERE numero_loja = '270'
);

ALTER TABLE acervo
    ADD COLUMN IF NOT EXISTS codigo_acervo VARCHAR(32),
    ADD COLUMN IF NOT EXISTS resumo TEXT,
    ADD COLUMN IF NOT EXISTS ativo BOOLEAN NOT NULL DEFAULT TRUE,
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES lojas(id),
    ADD COLUMN IF NOT EXISTS atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE acervo
SET loja_id = (
    SELECT id
    FROM lojas
    ORDER BY id
    LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE acervo
    ALTER COLUMN loja_id SET NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS ux_acervo_codigo_acervo
    ON acervo (codigo_acervo)
    WHERE codigo_acervo IS NOT NULL;

CREATE TABLE IF NOT EXISTS biblioteca_comentarios (
    id SERIAL PRIMARY KEY,
    acervo_id INTEGER NOT NULL REFERENCES acervo(id) ON DELETE CASCADE,
    obreiro_id UUID NOT NULL REFERENCES obreiros(id) ON DELETE CASCADE,
    comentario TEXT NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS ix_biblioteca_comentarios_acervo_id
    ON biblioteca_comentarios (acervo_id);

CREATE TABLE IF NOT EXISTS biblioteca_reacoes (
    id SERIAL PRIMARY KEY,
    acervo_id INTEGER NOT NULL REFERENCES acervo(id) ON DELETE CASCADE,
    obreiro_id UUID NOT NULL REFERENCES obreiros(id) ON DELETE CASCADE,
    gostei BOOLEAN NOT NULL,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (acervo_id, obreiro_id)
);

CREATE INDEX IF NOT EXISTS ix_biblioteca_reacoes_acervo_id
    ON biblioteca_reacoes (acervo_id);
