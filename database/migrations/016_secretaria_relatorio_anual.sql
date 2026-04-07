-- 016_secretaria_relatorio_anual.sql
-- Base minima para relatorios anuais da Secretaria com trilha cadastral dos obreiros.

ALTER TABLE public.obreiros
  ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW();

UPDATE public.obreiros
   SET created_at = COALESCE(created_at, NOW()),
       updated_at = COALESCE(updated_at, NOW());

CREATE INDEX IF NOT EXISTS ix_obreiros_created_at
  ON public.obreiros(created_at);

CREATE INDEX IF NOT EXISTS ix_obreiros_ativo_updated_at
  ON public.obreiros(ativo, updated_at);
