-- 038_convites_acesso.sql
-- Onboarding fechado: convite de ativacao (uso unico) por obreiro.

CREATE TABLE IF NOT EXISTS public.convites_acesso (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    obreiro_id UUID NOT NULL REFERENCES public.obreiros(id) ON DELETE CASCADE,
    token TEXT NOT NULL,
    expira_em TIMESTAMPTZ NOT NULL,
    usado_em TIMESTAMPTZ NULL,
    criado_em TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS ux_convites_acesso_token
    ON public.convites_acesso (token);

CREATE INDEX IF NOT EXISTS ix_convites_acesso_obreiro_id
    ON public.convites_acesso (obreiro_id);

CREATE INDEX IF NOT EXISTS ix_convites_acesso_expira_em
    ON public.convites_acesso (expira_em);

CREATE INDEX IF NOT EXISTS ix_convites_acesso_usado_em
    ON public.convites_acesso (usado_em);

