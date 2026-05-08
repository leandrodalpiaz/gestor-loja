-- 047 - Fluxo de submissão de trabalhos (Aprendiz/Companheiro/Mestre)
-- - O obreiro submete o trabalho para validação (1º/2º Vigilante) conforme grau.
-- - Após validação, a Secretaria arquiva (com PDF) e publica para consulta na Biblioteca.

CREATE TABLE IF NOT EXISTS public.trabalhos_submissoes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    loja_id INTEGER NOT NULL,
    obreiro_id UUID NOT NULL,
    grau_obreiro VARCHAR(50) NULL,
    tipo_trabalho VARCHAR(60) NOT NULL DEFAULT 'peca_arquitetura',
    titulo VARCHAR(255) NOT NULL,
    sessao_id INTEGER NULL,
    arquivo_pdf_path TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'rascunho',
    mentor_tipo VARCHAR(30) NULL, -- 'primeiro_vigilante'|'segundo_vigilante'|NULL
    mentor_decisao VARCHAR(20) NULL, -- 'aprovado'|'rejeitado'|NULL
    mentor_observacao TEXT NULL,
    mentor_decidido_por UUID NULL,
    mentor_decidido_em TIMESTAMP NULL,
    arquivado_trabalho_sessao_id INTEGER NULL,
    arquivado_por UUID NULL,
    arquivado_em TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_trabalhos_submissoes_loja_status ON public.trabalhos_submissoes (loja_id, status);
CREATE INDEX IF NOT EXISTS idx_trabalhos_submissoes_loja_obreiro ON public.trabalhos_submissoes (loja_id, obreiro_id);
CREATE INDEX IF NOT EXISTS idx_trabalhos_submissoes_loja_sessao ON public.trabalhos_submissoes (loja_id, sessao_id);

