-- 017_refino_sessoes_relatorio.sql
-- Refino da tabela de sessoes para classificacao ritual/administrativa orientada a relatorios.

ALTER TABLE public.sessoes
  ADD COLUMN IF NOT EXISTS gestao_referencia TEXT NULL,
  ADD COLUMN IF NOT EXISTS natureza_sessao TEXT NULL,
  ADD COLUMN IF NOT EXISTS formato_sessao TEXT NULL,
  ADD COLUMN IF NOT EXISTS finalidade_ritual TEXT NULL,
  ADD COLUMN IF NOT EXISTS templo_local TEXT NULL,
  ADD COLUMN IF NOT EXISTS sessao_branca BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS sessao_a_campo BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS conta_relatorio_potencia BOOLEAN NOT NULL DEFAULT TRUE,
  ADD COLUMN IF NOT EXISTS observacao_relatorio TEXT NULL;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM pg_constraint
     WHERE conname = 'chk_sessoes_natureza'
       AND conrelid = 'public.sessoes'::regclass
  ) THEN
    ALTER TABLE public.sessoes
      ADD CONSTRAINT chk_sessoes_natureza
      CHECK (
        natureza_sessao IS NULL
        OR natureza_sessao IN ('ordinaria', 'magna', 'assembleia', 'instrucao', 'administrativa', 'especial')
      );
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM pg_constraint
     WHERE conname = 'chk_sessoes_formato'
       AND conrelid = 'public.sessoes'::regclass
  ) THEN
    ALTER TABLE public.sessoes
      ADD CONSTRAINT chk_sessoes_formato
      CHECK (
        formato_sessao IS NULL
        OR formato_sessao IN ('templo', 'a_campo', 'publica', 'branca')
      );
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM pg_constraint
     WHERE conname = 'chk_sessoes_finalidade_ritual'
       AND conrelid = 'public.sessoes'::regclass
  ) THEN
    ALTER TABLE public.sessoes
      ADD CONSTRAINT chk_sessoes_finalidade_ritual
      CHECK (
        finalidade_ritual IS NULL
        OR finalidade_ritual IN ('economica', 'iniciacao', 'elevacao', 'exaltacao', 'eleicao', 'instalacao', 'funebre', 'comemorativa', 'outra')
      );
  END IF;
END $$;

CREATE INDEX IF NOT EXISTS ix_sessoes_natureza_formato
  ON public.sessoes(natureza_sessao, formato_sessao, data_hora_inicio);

CREATE INDEX IF NOT EXISTS ix_sessoes_relatorio_potencia
  ON public.sessoes(conta_relatorio_potencia, data_hora_inicio);
