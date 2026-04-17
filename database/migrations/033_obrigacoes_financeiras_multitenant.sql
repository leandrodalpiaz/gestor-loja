-- 033_obrigacoes_financeiras_multitenant.sql
-- Fecha a fronteira primaria do financeiro por tenant.

ALTER TABLE public.obrigacoes_financeiras
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.obrigacoes_financeiras ofi
SET loja_id = o.loja_id
FROM public.obreiros o
WHERE o.id = ofi.obreiro_id
  AND ofi.loja_id IS NULL;

UPDATE public.obrigacoes_financeiras
SET loja_id = (
    SELECT id
    FROM public.lojas
    ORDER BY id
    LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.obrigacoes_financeiras
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_obrigacoes_financeiras_loja_id
    ON public.obrigacoes_financeiras (loja_id);

