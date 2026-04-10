-- 024_obrigacoes_financeiras.sql
-- Programa obrigacoes financeiras por obreiro para acompanhamento da Tesouraria e do irmao.

CREATE TABLE IF NOT EXISTS public.obrigacoes_financeiras (
    id SERIAL PRIMARY KEY,
    obreiro_id UUID NOT NULL REFERENCES public.obreiros(id),
    categoria_id INT NULL REFERENCES public.categorias_financeiras(id),
    titulo VARCHAR(160) NOT NULL,
    tipo_obrigacao VARCHAR(40) NOT NULL,
    recorrencia VARCHAR(30) NOT NULL DEFAULT 'avulsa',
    status VARCHAR(30) NOT NULL DEFAULT 'ativa',
    valor_base NUMERIC(12,2) NOT NULL DEFAULT 0,
    parcelas_total INT NOT NULL DEFAULT 1,
    permite_parcelamento BOOLEAN NOT NULL DEFAULT FALSE,
    forma_cobranca VARCHAR(40) NOT NULL DEFAULT 'livre',
    somar_a_mensalidade BOOLEAN NOT NULL DEFAULT FALSE,
    aceita_pagamento_previo BOOLEAN NOT NULL DEFAULT FALSE,
    aceita_pagamento_posterior BOOLEAN NOT NULL DEFAULT TRUE,
    dia_vencimento INT NULL,
    mes_referencia_fixa INT NULL,
    inicio_competencia DATE NULL,
    fim_competencia DATE NULL,
    instrucoes_pagamento TEXT NULL,
    observacao TEXT NULL,
    criado_por UUID NULL REFERENCES public.obreiros(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_obrigacoes_financeiras_obreiro
    ON public.obrigacoes_financeiras(obreiro_id, status);

CREATE TABLE IF NOT EXISTS public.obrigacao_financeira_parcelas (
    id SERIAL PRIMARY KEY,
    obrigacao_id INT NOT NULL REFERENCES public.obrigacoes_financeiras(id) ON DELETE CASCADE,
    numero_parcela INT NOT NULL DEFAULT 1,
    competencia_label VARCHAR(40) NULL,
    competencia_mes INT NULL,
    competencia_ano INT NULL,
    vencimento DATE NOT NULL,
    valor_previsto NUMERIC(12,2) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'pendente',
    categoria_id INT NULL REFERENCES public.categorias_financeiras(id),
    lancamento_id INT NULL REFERENCES public.lancamentos_financeiros(id),
    comprovante_id INT NULL REFERENCES public.comprovantes_pix(id),
    pago_em DATE NULL,
    observacao TEXT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (obrigacao_id, numero_parcela)
);

CREATE INDEX IF NOT EXISTS idx_obrigacao_financeira_parcelas_obreiro
    ON public.obrigacao_financeira_parcelas(vencimento, status);
