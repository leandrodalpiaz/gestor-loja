-- Solicitações de empréstimo entre lojas (opcional)
-- Não substitui o fluxo local; apenas registra o pedido para aprovação na loja de origem.

CREATE TABLE IF NOT EXISTS emprestimos_interloja (
    id SERIAL PRIMARY KEY,
    loja_origem_id INTEGER NOT NULL REFERENCES lojas(id) ON DELETE CASCADE,
    loja_destino_id INTEGER NOT NULL REFERENCES lojas(id) ON DELETE CASCADE,
    acervo_id INTEGER NOT NULL REFERENCES acervo(id) ON DELETE CASCADE,
    obreiro_id UUID NOT NULL REFERENCES obreiros(id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL CHECK (status IN ('solicitado', 'aprovado', 'negado', 'cancelado')),
    solicitado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decidido_em TIMESTAMP,
    decidido_por UUID REFERENCES obreiros(id),
    observacao TEXT
);

CREATE INDEX IF NOT EXISTS idx_emprestimos_interloja_origem_status ON emprestimos_interloja (loja_origem_id, status);
CREATE INDEX IF NOT EXISTS idx_emprestimos_interloja_destino_obreiro ON emprestimos_interloja (loja_destino_id, obreiro_id);

