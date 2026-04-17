-- 034_financeiro_agregados_multitenant.sql
-- Fecha a fronteira multi-tenant dos agregadores financeiros.

ALTER TABLE public.lancamentos_financeiros
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.lancamentos_financeiros l
SET loja_id = o.loja_id
FROM public.obreiros o
WHERE o.id = l.obreiro_id
  AND l.loja_id IS NULL;

UPDATE public.lancamentos_financeiros
SET loja_id = (
    SELECT id
    FROM public.lojas
    ORDER BY id
    LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.lancamentos_financeiros
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_lancamentos_financeiros_loja_id
    ON public.lancamentos_financeiros (loja_id);

ALTER TABLE public.mensalidades_status
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.mensalidades_status ms
SET loja_id = o.loja_id
FROM public.obreiros o
WHERE o.id = ms.obreiro_id
  AND ms.loja_id IS NULL;

UPDATE public.mensalidades_status
SET loja_id = (
    SELECT id
    FROM public.lojas
    ORDER BY id
    LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.mensalidades_status
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_mensalidades_status_loja_id
    ON public.mensalidades_status (loja_id);

ALTER TABLE public.fechamento_mensal
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.fechamento_mensal
SET loja_id = (
    SELECT id
    FROM public.lojas
    ORDER BY id
    LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.fechamento_mensal
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_fechamento_mensal_loja_id
    ON public.fechamento_mensal (loja_id);

CREATE UNIQUE INDEX IF NOT EXISTS ux_fechamento_mensal_loja_mes_ano
    ON public.fechamento_mensal (loja_id, mes_ref, ano_ref);

ALTER TABLE public.tronco_solidariedade
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.tronco_solidariedade
SET loja_id = (
    SELECT id
    FROM public.lojas
    ORDER BY id
    LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.tronco_solidariedade
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_tronco_solidariedade_loja_id
    ON public.tronco_solidariedade (loja_id);
