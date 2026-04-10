-- 003_fn_atribuir_cargo.sql
-- Função utilitária para atribuir cargo com revogação automática do titular anterior
-- Regra: 1 atribuição ativa (fim_em IS NULL) por cargo.

CREATE OR REPLACE FUNCTION public.atribuir_cargo(
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
  SELECT id
    INTO v_cargo_id
    FROM public.cargos
   WHERE codigo = p_cargo_codigo
     AND ativo = TRUE;

  IF v_cargo_id IS NULL THEN
    RAISE EXCEPTION 'Cargo "%" não encontrado ou inativo', p_cargo_codigo;
  END IF;

  IF p_gestao_id IS NOT NULL THEN
    v_gestao_id := p_gestao_id;
  ELSE
    SELECT id
      INTO v_gestao_id
      FROM public.gestoes
     WHERE status = 'aberta'
     ORDER BY inicio_em DESC, id DESC
     LIMIT 1;
  END IF;

  IF v_gestao_id IS NULL THEN
    RAISE EXCEPTION 'Nenhuma gestao aberta encontrada para atribuir o cargo "%"', p_cargo_codigo;
  END IF;

  -- Se já é o titular ativo, não duplica; só atualiza observação (se vier)
  IF EXISTS (
    SELECT 1
      FROM public.atribuicoes_cargo
     WHERE cargo_id = v_cargo_id
       AND fim_em IS NULL
       AND gestao_id = v_gestao_id
       AND obreiro_id = p_obreiro_id
  ) THEN
    UPDATE public.atribuicoes_cargo
       SET observacao = COALESCE(p_observacao, observacao)
     WHERE cargo_id = v_cargo_id
       AND fim_em IS NULL
       AND gestao_id = v_gestao_id
       AND obreiro_id = p_obreiro_id;
    RETURN;
  END IF;

  -- Revoga o titular anterior (se existir)
  UPDATE public.atribuicoes_cargo
     SET fim_em = p_inicio_em
   WHERE cargo_id = v_cargo_id
     AND gestao_id = v_gestao_id
     AND fim_em IS NULL;

  -- Atribui o novo titular
  INSERT INTO public.atribuicoes_cargo (cargo_id, obreiro_id, gestao_id, inicio_em, observacao)
  VALUES (v_cargo_id, p_obreiro_id, v_gestao_id, p_inicio_em, p_observacao);
END;
$$;
