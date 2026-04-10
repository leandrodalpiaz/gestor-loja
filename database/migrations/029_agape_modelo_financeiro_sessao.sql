-- 029_agape_modelo_financeiro_sessao.sql
-- Separa o reflexo contabil oficial do fluxo operacional do agape.

ALTER TABLE public.sessoes
  ADD COLUMN IF NOT EXISTS agape_modelo_financeiro TEXT NOT NULL DEFAULT 'oficial_loja';

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM pg_constraint
     WHERE conname = 'chk_sessoes_agape_modelo_financeiro'
       AND conrelid = 'public.sessoes'::regclass
  ) THEN
    ALTER TABLE public.sessoes
      ADD CONSTRAINT chk_sessoes_agape_modelo_financeiro
      CHECK (agape_modelo_financeiro IN ('oficial_loja', 'particular', 'misto'));
  END IF;
END $$;

UPDATE public.sessoes
   SET agape_modelo_financeiro = COALESCE(NULLIF(TRIM(agape_modelo_financeiro), ''), 'oficial_loja');
