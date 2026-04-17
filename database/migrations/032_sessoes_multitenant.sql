-- 032_sessoes_multitenant.sql
-- Fecha a primeira fronteira multi-tenant da Secretaria.
-- As tabelas satelite continuam relacionadas por sessao_id, entao o
-- isolamento primario comeca em public.sessoes e se propaga pelas leituras.

ALTER TABLE public.sessoes
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.sessoes
SET loja_id = (
    SELECT id
    FROM public.lojas
    ORDER BY id
    LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.sessoes
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_sessoes_loja_id
    ON public.sessoes (loja_id);

CREATE INDEX IF NOT EXISTS ix_sessoes_loja_id_data_hora_inicio
    ON public.sessoes (loja_id, data_hora_inicio);
