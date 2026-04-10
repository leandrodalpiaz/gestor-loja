-- 026_financeiro_regras_fixas_pix_isencoes.sql
-- Regras fixas do financeiro, isencoes individuais, contribuicao mensal da biblioteca e rotulagem PIX.

ALTER TABLE public.configuracoes_loja
    ADD COLUMN IF NOT EXISTS contribuicao_biblioteca_valor_padrao NUMERIC(12,2) NOT NULL DEFAULT 44.00,
    ADD COLUMN IF NOT EXISTS contribuicao_biblioteca_quantidade_mensal INT NOT NULL DEFAULT 2,
    ADD COLUMN IF NOT EXISTS pix_chave_tipo VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS pix_chave_valor VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS pix_beneficiario VARCHAR(160) NULL;

UPDATE public.configuracoes_loja
SET contribuicao_biblioteca_valor_padrao = COALESCE(contribuicao_biblioteca_valor_padrao, 44.00),
    contribuicao_biblioteca_quantidade_mensal = COALESCE(contribuicao_biblioteca_quantidade_mensal, 2),
    pix_chave_tipo = COALESCE(NULLIF(pix_chave_tipo, ''), 'CNPJ'),
    pix_chave_valor = COALESCE(NULLIF(pix_chave_valor, ''), '31.274.071/0001-06'),
    pix_beneficiario = COALESCE(NULLIF(pix_beneficiario, ''), 'Renascença nº 270')
WHERE id = 1;

CREATE TABLE IF NOT EXISTS public.isencoes_financeiras (
    id SERIAL PRIMARY KEY,
    obreiro_id UUID NOT NULL REFERENCES public.obreiros(id),
    tipo_obrigacao VARCHAR(40) NOT NULL,
    categoria_id INT NULL REFERENCES public.categorias_financeiras(id),
    inicio_competencia DATE NOT NULL,
    fim_competencia DATE NULL,
    observacao TEXT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_por UUID NULL REFERENCES public.obreiros(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_isencoes_financeiras_obreiro
    ON public.isencoes_financeiras(obreiro_id, tipo_obrigacao, ativo);

CREATE TABLE IF NOT EXISTS public.biblioteca_contribuintes_mensal (
    id SERIAL PRIMARY KEY,
    mes_ref INT NOT NULL,
    ano_ref INT NOT NULL,
    obreiro_id UUID NOT NULL REFERENCES public.obreiros(id),
    valor_previsto NUMERIC(12,2) NOT NULL DEFAULT 44.00,
    observacao TEXT NULL,
    criado_por UUID NULL REFERENCES public.obreiros(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (mes_ref, ano_ref, obreiro_id)
);

ALTER TABLE public.comprovantes_pix
    ADD COLUMN IF NOT EXISTS rotulo_pagamento VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS categoria_id INT NULL REFERENCES public.categorias_financeiras(id),
    ADD COLUMN IF NOT EXISTS obrigacao_parcela_id INT NULL REFERENCES public.obrigacao_financeira_parcelas(id);
