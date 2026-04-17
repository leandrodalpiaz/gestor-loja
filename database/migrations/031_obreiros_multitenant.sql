-- 031_obreiros_multitenant.sql
-- Materializa o tenant no eixo de identidade.
-- Mantem compatibilidade com a loja default existente e prepara login/webhook
-- para sincronizar o contexto da loja a partir do obreiro autenticado.

ALTER TABLE public.obreiros
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.obreiros
SET loja_id = (
    SELECT id
    FROM public.lojas
    ORDER BY id
    LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.obreiros
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_obreiros_loja_id
    ON public.obreiros (loja_id);

CREATE INDEX IF NOT EXISTS ix_obreiros_loja_id_ativo
    ON public.obreiros (loja_id, ativo);

