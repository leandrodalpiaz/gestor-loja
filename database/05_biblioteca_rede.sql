-- Biblioteca em rede (compartilhamento opcional por loja)
-- Padrao: tudo desativado.

CREATE TABLE IF NOT EXISTS biblioteca_loja_config (
    loja_id INTEGER PRIMARY KEY REFERENCES lojas(id) ON DELETE CASCADE,
    compartilhar_acervo BOOLEAN NOT NULL DEFAULT FALSE,
    permitir_emprestimo_cruzado BOOLEAN NOT NULL DEFAULT FALSE,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

