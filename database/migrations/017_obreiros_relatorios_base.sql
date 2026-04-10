-- 017_obreiros_relatorios_base.sql
-- Expande o cadastro de obreiros para suportar relatorios individuais e estatisticos.

ALTER TABLE public.obreiros
  ADD COLUMN IF NOT EXISTS estado_civil TEXT NULL,
  ADD COLUMN IF NOT EXISTS escolaridade TEXT NULL,
  ADD COLUMN IF NOT EXISTS faixa_renda TEXT NULL,
  ADD COLUMN IF NOT EXISTS situacao_quadro TEXT NOT NULL DEFAULT 'ativo',
  ADD COLUMN IF NOT EXISTS potencia_nome TEXT NULL,
  ADD COLUMN IF NOT EXISTS potencia_sigla TEXT NULL,
  ADD COLUMN IF NOT EXISTS numero_quadro TEXT NULL,
  ADD COLUMN IF NOT EXISTS data_filiacao DATE NULL,
  ADD COLUMN IF NOT EXISTS data_regularizacao DATE NULL,
  ADD COLUMN IF NOT EXISTS data_reintegracao DATE NULL,
  ADD COLUMN IF NOT EXISTS data_quite_placet DATE NULL,
  ADD COLUMN IF NOT EXISTS data_suspensao DATE NULL,
  ADD COLUMN IF NOT EXISTS data_desligamento DATE NULL,
  ADD COLUMN IF NOT EXISTS data_oriente_eterno DATE NULL;

UPDATE public.obreiros
   SET situacao_quadro = CASE
       WHEN data_oriente_eterno IS NOT NULL THEN 'oriente_eterno'
       WHEN data_desligamento IS NOT NULL THEN 'desligado'
       WHEN data_suspensao IS NOT NULL THEN 'suspenso'
       WHEN ativo = TRUE THEN 'ativo'
       ELSE 'inativo'
   END
 WHERE COALESCE(TRIM(situacao_quadro), '') = '';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'chk_obreiros_estado_civil'
    ) THEN
        ALTER TABLE public.obreiros
          ADD CONSTRAINT chk_obreiros_estado_civil
          CHECK (
            estado_civil IS NULL OR estado_civil IN (
              'solteiro', 'casado', 'divorciado', 'separado',
              'viuvo', 'uniao_estavel', 'nao_informado'
            )
          );
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'chk_obreiros_escolaridade'
    ) THEN
        ALTER TABLE public.obreiros
          ADD CONSTRAINT chk_obreiros_escolaridade
          CHECK (
            escolaridade IS NULL OR escolaridade IN (
              'fundamental_incompleto', 'fundamental_completo',
              'medio_incompleto', 'medio_completo',
              'tecnico', 'superior_incompleto', 'superior_completo',
              'pos_graduacao', 'mestrado', 'doutorado',
              'nao_informado'
            )
          );
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'chk_obreiros_faixa_renda'
    ) THEN
        ALTER TABLE public.obreiros
          ADD CONSTRAINT chk_obreiros_faixa_renda
          CHECK (
            faixa_renda IS NULL OR faixa_renda IN (
              'ate_1_sm', 'de_1_a_3_sm', 'de_3_a_5_sm',
              'de_5_a_10_sm', 'acima_10_sm', 'nao_informado'
            )
          );
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'chk_obreiros_situacao_quadro'
    ) THEN
        ALTER TABLE public.obreiros
          ADD CONSTRAINT chk_obreiros_situacao_quadro
          CHECK (
            situacao_quadro IN (
              'ativo', 'licenciado', 'suspenso', 'desligado',
              'falecido', 'oriente_eterno', 'inativo'
            )
          );
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS ix_obreiros_situacao_quadro
  ON public.obreiros(situacao_quadro);

CREATE INDEX IF NOT EXISTS ix_obreiros_data_filiacao
  ON public.obreiros(data_filiacao);

CREATE INDEX IF NOT EXISTS ix_obreiros_data_regularizacao
  ON public.obreiros(data_regularizacao);

CREATE INDEX IF NOT EXISTS ix_obreiros_data_reintegracao
  ON public.obreiros(data_reintegracao);

CREATE INDEX IF NOT EXISTS ix_obreiros_data_desligamento
  ON public.obreiros(data_desligamento);

CREATE INDEX IF NOT EXISTS ix_obreiros_data_oriente_eterno
  ON public.obreiros(data_oriente_eterno);
