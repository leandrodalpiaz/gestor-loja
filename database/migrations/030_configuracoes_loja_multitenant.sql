-- 030_configuracoes_loja_multitenant.sql
-- Primeira etapa de multi-tenant para os parametros institucionais da Loja.
-- Mantem compatibilidade com a configuracao legado (id = 1) e associa cada
-- configuracao ao tenant operacional representado em public.lojas.

ALTER TABLE public.configuracoes_loja
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.configuracoes_loja
SET loja_id = (
    SELECT id
    FROM public.lojas
    ORDER BY id
    LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.configuracoes_loja
    ALTER COLUMN loja_id SET NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS ux_configuracoes_loja_loja_id
    ON public.configuracoes_loja (loja_id);
