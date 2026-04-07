-- Criacao das tabelas da biblioteca (modelo atual)

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
SELECT '270', 'R', 'Loja Renascenca', NULL
WHERE NOT EXISTS (
    SELECT 1 FROM lojas WHERE numero_loja = '270'
);

CREATE TABLE IF NOT EXISTS acervo (
    id SERIAL PRIMARY KEY,
    codigo_acervo VARCHAR(32) UNIQUE,
    loja_id INTEGER NOT NULL REFERENCES lojas(id),
    isbn VARCHAR(32),
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255) NOT NULL,
    resumo TEXT,
    tipo VARCHAR(30) NOT NULL,
    grau_restricao INTEGER NOT NULL DEFAULT 1,
    arquivo_url VARCHAR(500),
    capa_url VARCHAR(500),
    quantidade_disponivel INTEGER NOT NULL DEFAULT 0,
    grau_recomendado VARCHAR(30) NOT NULL DEFAULT 'Livre',
    nota_instrucao TEXT,
    curador_id UUID REFERENCES obreiros(id),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS emprestimos (
    id SERIAL PRIMARY KEY,
    acervo_id INTEGER NOT NULL REFERENCES acervo(id) ON DELETE CASCADE,
    obreiro_id UUID NOT NULL REFERENCES obreiros(id) ON DELETE CASCADE,
    data_emprestimo DATE NOT NULL DEFAULT CURRENT_DATE,
    data_devolucao_prevista DATE NOT NULL,
    data_devolucao_real DATE,
    status VARCHAR(20) NOT NULL CHECK (status IN ('pendente', 'aprovado', 'devolvido', 'atrasado'))
);

CREATE TABLE IF NOT EXISTS biblioteca_comentarios (
    id SERIAL PRIMARY KEY,
    acervo_id INTEGER NOT NULL REFERENCES acervo(id) ON DELETE CASCADE,
    obreiro_id UUID NOT NULL REFERENCES obreiros(id) ON DELETE CASCADE,
    comentario TEXT NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS biblioteca_reacoes (
    id SERIAL PRIMARY KEY,
    acervo_id INTEGER NOT NULL REFERENCES acervo(id) ON DELETE CASCADE,
    obreiro_id UUID NOT NULL REFERENCES obreiros(id) ON DELETE CASCADE,
    gostei BOOLEAN NOT NULL,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (acervo_id, obreiro_id)
);
