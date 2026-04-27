-- 042_nominata_multitenant_isolamento.sql
-- Isola cargos, atribuicoes e gestoes por loja.
-- Requer configuracao explicita de tenant legado via:
--   SET app.default_tenant_slug = '<slug>';
-- ou
--   SET app.loja_numero = '<numero>';

ALTER TABLE public.cargos
  ADD COLUMN IF NOT EXISTS loja_id INTEGER;

ALTER TABLE public.atribuicoes_cargo
  ADD COLUMN IF NOT EXISTS loja_id INTEGER;

ALTER TABLE public.gestoes
  ADD COLUMN IF NOT EXISTS loja_id INTEGER;

DO $$
DECLARE
  v_tenant_slug TEXT := NULLIF(TRIM(current_setting('app.default_tenant_slug', true)), '');
  v_loja_numero TEXT := NULLIF(TRIM(current_setting('app.loja_numero', true)), '');
  v_loja_id INTEGER;
BEGIN
  IF v_tenant_slug IS NOT NULL THEN
    SELECT id
      INTO v_loja_id
      FROM public.lojas
     WHERE LOWER(sigla) = LOWER(v_tenant_slug)
        OR LOWER(numero_loja) = LOWER(v_tenant_slug)
        OR LOWER(REPLACE(nome, ' ', '-')) = LOWER(v_tenant_slug)
     ORDER BY id
     LIMIT 1;
  END IF;

  IF v_loja_id IS NULL AND v_loja_numero IS NOT NULL THEN
    SELECT id
      INTO v_loja_id
      FROM public.lojas
     WHERE numero_loja = v_loja_numero
     LIMIT 1;
  END IF;

  IF v_loja_id IS NULL THEN
    RAISE EXCEPTION 'Migration 042 abortada: tenant legado nao identificado. Configure app.default_tenant_slug ou app.loja_numero com valor valido.';
  END IF;

  UPDATE public.gestoes
     SET loja_id = v_loja_id
   WHERE loja_id IS NULL;

  UPDATE public.cargos
     SET loja_id = v_loja_id
   WHERE loja_id IS NULL;

  UPDATE public.atribuicoes_cargo ac
     SET loja_id = c.loja_id
    FROM public.cargos c
   WHERE ac.loja_id IS NULL
     AND c.id = ac.cargo_id
     AND c.loja_id IS NOT NULL;

  UPDATE public.atribuicoes_cargo ac
     SET loja_id = g.loja_id
    FROM public.gestoes g
   WHERE ac.loja_id IS NULL
     AND g.id = ac.gestao_id
     AND g.loja_id IS NOT NULL;

  UPDATE public.atribuicoes_cargo
     SET loja_id = v_loja_id
   WHERE loja_id IS NULL;
END $$;

ALTER TABLE public.cargos
  ALTER COLUMN loja_id SET NOT NULL;

ALTER TABLE public.gestoes
  ALTER COLUMN loja_id SET NOT NULL;

ALTER TABLE public.atribuicoes_cargo
  ALTER COLUMN loja_id SET NOT NULL;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conname = 'fk_cargos_loja_id'
      AND conrelid = 'public.cargos'::regclass
  ) THEN
    ALTER TABLE public.cargos
      ADD CONSTRAINT fk_cargos_loja_id
      FOREIGN KEY (loja_id) REFERENCES public.lojas(id);
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conname = 'fk_gestoes_loja_id'
      AND conrelid = 'public.gestoes'::regclass
  ) THEN
    ALTER TABLE public.gestoes
      ADD CONSTRAINT fk_gestoes_loja_id
      FOREIGN KEY (loja_id) REFERENCES public.lojas(id);
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conname = 'fk_atribuicoes_cargo_loja_id'
      AND conrelid = 'public.atribuicoes_cargo'::regclass
  ) THEN
    ALTER TABLE public.atribuicoes_cargo
      ADD CONSTRAINT fk_atribuicoes_cargo_loja_id
      FOREIGN KEY (loja_id) REFERENCES public.lojas(id);
  END IF;
END $$;

ALTER TABLE public.cargos
  DROP CONSTRAINT IF EXISTS cargos_codigo_key;

CREATE UNIQUE INDEX IF NOT EXISTS ux_cargos_loja_codigo
  ON public.cargos(loja_id, codigo);

DROP INDEX IF EXISTS ux_atribuicoes_cargo_ativo_por_cargo;
CREATE UNIQUE INDEX IF NOT EXISTS ux_atribuicoes_cargo_ativo_por_cargo_loja
  ON public.atribuicoes_cargo(loja_id, cargo_id)
  WHERE fim_em IS NULL;

DROP INDEX IF EXISTS ux_gestoes_abertas;
CREATE UNIQUE INDEX IF NOT EXISTS ux_gestoes_abertas_por_loja
  ON public.gestoes(loja_id)
  WHERE status = 'aberta';

CREATE INDEX IF NOT EXISTS ix_cargos_loja_id
  ON public.cargos(loja_id);

CREATE INDEX IF NOT EXISTS ix_gestoes_loja_id
  ON public.gestoes(loja_id, status);

CREATE INDEX IF NOT EXISTS ix_atribuicoes_cargo_loja_id
  ON public.atribuicoes_cargo(loja_id, obreiro_id, fim_em);

CREATE OR REPLACE FUNCTION public.atribuir_cargo(
  p_loja_id INTEGER,
  p_cargo_codigo TEXT,
  p_obreiro_id UUID,
  p_gestao_id BIGINT DEFAULT NULL,
  p_inicio_em TIMESTAMPTZ DEFAULT NOW(),
  p_observacao TEXT DEFAULT NULL
)
RETURNS VOID
LANGUAGE plpgsql
AS $$
DECLARE
  v_cargo_id BIGINT;
  v_gestao_id BIGINT;
BEGIN
  IF p_loja_id IS NULL OR p_loja_id <= 0 THEN
    RAISE EXCEPTION 'Loja invalida para atribuir cargo.';
  END IF;

  SELECT id
    INTO v_cargo_id
    FROM public.cargos
   WHERE loja_id = p_loja_id
     AND codigo = p_cargo_codigo
     AND ativo = TRUE;

  IF v_cargo_id IS NULL THEN
    RAISE EXCEPTION 'Cargo "%" nao encontrado/inativo para a loja %', p_cargo_codigo, p_loja_id;
  END IF;

  IF p_gestao_id IS NOT NULL THEN
    SELECT id
      INTO v_gestao_id
      FROM public.gestoes
     WHERE id = p_gestao_id
       AND loja_id = p_loja_id
     LIMIT 1;
  ELSE
    SELECT id
      INTO v_gestao_id
      FROM public.gestoes
     WHERE loja_id = p_loja_id
       AND status = 'aberta'
     ORDER BY inicio_em DESC, id DESC
     LIMIT 1;
  END IF;

  IF v_gestao_id IS NULL THEN
    RAISE EXCEPTION 'Nenhuma gestao valida da loja % foi encontrada para o cargo "%"', p_loja_id, p_cargo_codigo;
  END IF;

  IF EXISTS (
    SELECT 1
      FROM public.atribuicoes_cargo
     WHERE loja_id = p_loja_id
       AND cargo_id = v_cargo_id
       AND fim_em IS NULL
       AND gestao_id = v_gestao_id
       AND obreiro_id = p_obreiro_id
  ) THEN
    UPDATE public.atribuicoes_cargo
       SET observacao = COALESCE(p_observacao, observacao)
     WHERE loja_id = p_loja_id
       AND cargo_id = v_cargo_id
       AND fim_em IS NULL
       AND gestao_id = v_gestao_id
       AND obreiro_id = p_obreiro_id;
    RETURN;
  END IF;

  UPDATE public.atribuicoes_cargo
     SET fim_em = p_inicio_em
   WHERE loja_id = p_loja_id
     AND cargo_id = v_cargo_id
     AND gestao_id = v_gestao_id
     AND fim_em IS NULL;

  INSERT INTO public.atribuicoes_cargo (loja_id, cargo_id, obreiro_id, gestao_id, inicio_em, observacao)
  VALUES (p_loja_id, v_cargo_id, p_obreiro_id, v_gestao_id, p_inicio_em, p_observacao);
END;
$$;
