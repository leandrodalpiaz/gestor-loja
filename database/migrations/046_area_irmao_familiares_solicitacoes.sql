-- 046_area_irmao_familiares_solicitacoes.sql
-- Fecha lacunas de banco para Área do Irmão:
-- 1) familiares com revisão da Secretaria
-- 2) solicitações formais do obreiro para campos controlados
-- 3) flags especiais para acesso ritualístico em leitura

CREATE TABLE IF NOT EXISTS public.familiares_obreiro (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    loja_id INTEGER NOT NULL REFERENCES public.lojas(id) ON DELETE CASCADE,
    obreiro_id UUID NOT NULL REFERENCES public.obreiros(id) ON DELETE CASCADE,
    nome_completo TEXT NOT NULL,
    parentesco TEXT NOT NULL,
    data_nascimento DATE NULL,
    data_casamento DATE NULL,
    falecido BOOLEAN NOT NULL DEFAULT false,
    observacao_secretaria TEXT NULL,
    status_revisao TEXT NOT NULL DEFAULT 'pendente',
    revisado_por UUID NULL REFERENCES public.obreiros(id),
    revisado_em TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_familiares_parentesco
        CHECK (parentesco IN ('esposa', 'esposo', 'filho', 'filha')),
    CONSTRAINT chk_familiares_status_revisao
        CHECK (status_revisao IN ('pendente', 'revisado', 'corrigir'))
);

CREATE INDEX IF NOT EXISTS ix_familiares_obreiro_loja_obreiro
    ON public.familiares_obreiro (loja_id, obreiro_id);

CREATE INDEX IF NOT EXISTS ix_familiares_obreiro_status
    ON public.familiares_obreiro (loja_id, status_revisao);

CREATE INDEX IF NOT EXISTS ix_familiares_obreiro_efemerides
    ON public.familiares_obreiro (loja_id, data_nascimento)
    WHERE falecido = false;

CREATE TABLE IF NOT EXISTS public.solicitacoes_secretaria_obreiro (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    loja_id INTEGER NOT NULL REFERENCES public.lojas(id) ON DELETE CASCADE,
    obreiro_id UUID NOT NULL REFERENCES public.obreiros(id) ON DELETE CASCADE,
    tipo_solicitacao TEXT NOT NULL,
    valor_atual TEXT NULL,
    valor_solicitado TEXT NULL,
    justificativa TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pendente',
    resposta_secretaria TEXT NULL,
    analisado_por UUID NULL REFERENCES public.obreiros(id),
    analisado_em TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_solicitacao_tipo
        CHECK (tipo_solicitacao IN (
            'corrigir_grau',
            'corrigir_loja',
            'corrigir_numero_loja',
            'corrigir_oriente',
            'corrigir_potencia',
            'corrigir_rito',
            'revisar_familiar',
            'enviar_trabalho'
        )),
    CONSTRAINT chk_solicitacao_status
        CHECK (status IN ('pendente', 'aprovada', 'rejeitada', 'em_ajuste'))
);

CREATE INDEX IF NOT EXISTS ix_solicitacoes_secretaria_loja_status
    ON public.solicitacoes_secretaria_obreiro (loja_id, status, created_at DESC);

CREATE INDEX IF NOT EXISTS ix_solicitacoes_secretaria_obreiro
    ON public.solicitacoes_secretaria_obreiro (loja_id, obreiro_id, created_at DESC);

ALTER TABLE public.obreiros
    ADD COLUMN IF NOT EXISTS is_veneravel_atual BOOLEAN NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS is_mestre_instalado BOOLEAN NOT NULL DEFAULT false;

CREATE INDEX IF NOT EXISTS ix_obreiros_flags_ritualisticas
    ON public.obreiros (loja_id, is_veneravel_atual, is_mestre_instalado);
