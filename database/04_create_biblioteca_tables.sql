-- Criação das tabelas para o módulo Bibliotecário

CREATE TABLE IF NOT EXISTS acervo (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255) NOT NULL,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('fisico', 'digital', 'ritual')),
    grau_restricao INTEGER NOT NULL CHECK (grau_restricao IN (1,2,3)),
    arquivo_url VARCHAR(500),
    quantidade_disponivel INTEGER NOT NULL DEFAULT 0,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS emprestimos (
    id SERIAL PRIMARY KEY,
    acervo_id INTEGER NOT NULL REFERENCES acervo(id) ON DELETE CASCADE,
    obreiro_id INTEGER NOT NULL REFERENCES obreiros(id) ON DELETE CASCADE,
    data_emprestimo DATE NOT NULL DEFAULT CURRENT_DATE,
    data_devolucao_prevista DATE NOT NULL,
    data_devolucao_real DATE,
    status VARCHAR(20) NOT NULL CHECK (status IN ('pendente', 'aprovado', 'devolvido', 'atrasado'))
);
