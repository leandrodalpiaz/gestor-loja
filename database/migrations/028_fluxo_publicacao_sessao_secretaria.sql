-- 028_fluxo_publicacao_sessao_secretaria.sql
-- Estrutura complementar para fluxo guiado de cadastro, revisao e publicacao da sessao.

ALTER TABLE public.sessoes
  ADD COLUMN IF NOT EXISTS grau_personalizado TEXT NULL,
  ADD COLUMN IF NOT EXISTS tipo_sessao_principal TEXT NULL,
  ADD COLUMN IF NOT EXISTS tipo_sessao_subtipo TEXT NULL,
  ADD COLUMN IF NOT EXISTS tipo_sessao_personalizado TEXT NULL,
  ADD COLUMN IF NOT EXISTS traje_tipo TEXT NULL,
  ADD COLUMN IF NOT EXISTS traje_personalizado TEXT NULL,
  ADD COLUMN IF NOT EXISTS agape_modalidade TEXT NOT NULL DEFAULT 'nao_havera',
  ADD COLUMN IF NOT EXISTS agape_valor NUMERIC(10,2) NULL,
  ADD COLUMN IF NOT EXISTS ordem_dia TEXT NULL,
  ADD COLUMN IF NOT EXISTS publicado_por UUID NULL REFERENCES public.obreiros(id);

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM pg_constraint
     WHERE conname = 'chk_sessoes_tipo_principal'
       AND conrelid = 'public.sessoes'::regclass
  ) THEN
    ALTER TABLE public.sessoes
      ADD CONSTRAINT chk_sessoes_tipo_principal
      CHECK (
        tipo_sessao_principal IS NULL
        OR tipo_sessao_principal IN ('economica', 'magna', 'outra')
      );
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM pg_constraint
     WHERE conname = 'chk_sessoes_traje_tipo'
       AND conrelid = 'public.sessoes'::regclass
  ) THEN
    ALTER TABLE public.sessoes
      ADD CONSTRAINT chk_sessoes_traje_tipo
      CHECK (
        traje_tipo IS NULL
        OR traje_tipo IN ('maconico', 'livre', 'outro')
      );
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM pg_constraint
     WHERE conname = 'chk_sessoes_agape_modalidade'
       AND conrelid = 'public.sessoes'::regclass
  ) THEN
    ALTER TABLE public.sessoes
      ADD CONSTRAINT chk_sessoes_agape_modalidade
      CHECK (
        agape_modalidade IN ('nao_havera', 'gratuito', 'pago')
      );
  END IF;
END $$;

UPDATE public.sessoes
   SET tipo_sessao_principal = COALESCE(tipo_sessao_principal, CASE
         WHEN lower(COALESCE(tipo_sessao, '')) LIKE 'econom%' THEN 'economica'
         WHEN lower(COALESCE(tipo_sessao, '')) LIKE 'magna%' THEN 'magna'
         WHEN COALESCE(tipo_sessao, '') <> '' THEN 'outra'
         ELSE NULL
       END),
       traje_tipo = COALESCE(traje_tipo, 'maconico'),
       agape_modalidade = COALESCE(agape_modalidade, CASE
         WHEN agape_ativo = TRUE THEN 'gratuito'
         ELSE 'nao_havera'
       END),
       ordem_dia = COALESCE(ordem_dia, resumo_publico)
 WHERE tipo_sessao_principal IS NULL
    OR traje_tipo IS NULL
    OR ordem_dia IS NULL;

CREATE INDEX IF NOT EXISTS ix_sessoes_publicacao_fluxo
  ON public.sessoes(status, tipo_sessao_principal, data_hora_inicio);
