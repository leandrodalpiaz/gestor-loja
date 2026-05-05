-- 044_balaustres_independentes_secretaria.sql
-- Permite redacao de balaustre pela Secretaria antes ou sem sessao vinculada,
-- mantendo isolamento por loja para rascunhos independentes.

ALTER TABLE public.balaustres
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.balaustres b
SET loja_id = s.loja_id
FROM public.sessoes s
WHERE b.sessao_id = s.id
  AND b.loja_id IS NULL;

UPDATE public.balaustres
SET loja_id = (
    SELECT id
    FROM public.lojas
    ORDER BY id
    LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.balaustres
    ALTER COLUMN loja_id SET NOT NULL;

ALTER TABLE public.balaustres
    ALTER COLUMN sessao_id DROP NOT NULL;

CREATE INDEX IF NOT EXISTS ix_balaustres_loja_id
    ON public.balaustres (loja_id);

CREATE INDEX IF NOT EXISTS ix_balaustres_loja_id_status
    ON public.balaustres (loja_id, status, updated_at DESC);
