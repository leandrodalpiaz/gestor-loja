-- 003_fn_atribuir_cargo.sql
-- Função utilitária para atribuir cargo com revogação automática do titular anterior
-- Regra: 1 atribuição ativa (fim_em IS NULL) por cargo.

CREATE OR REPLACE FUNCTION public.atribuir_cargo(
  p_cargo_codigo TEXT,
  p_obreiro_id UUID,
  p_observacao TEXT DEFAULT NULL
)
RETURNS VOID
LANGUAGE plpgsql
AS $$
DECLARE
  v_cargo_id BIGINT;
BEGIN
  SELECT id
    INTO v_cargo_id
    FROM public.cargos
   WHERE codigo = p_cargo_codigo
     AND ativo = TRUE;

  IF v_cargo_id IS NULL THEN
    RAISE EXCEPTION 'Cargo "%" não encontrado ou inativo', p_cargo_codigo;
  END IF;

  -- Se já é o titular ativo, não duplica; só atualiza observação (se vier)
  IF EXISTS (
    SELECT 1
      FROM public.atribuicoes_cargo
     WHERE cargo_id = v_cargo_id
       AND fim_em IS NULL
       AND obreiro_id = p_obreiro_id
  ) THEN
    UPDATE public.atribuicoes_cargo
       SET observacao = COALESCE(p_observacao, observacao)
     WHERE cargo_id = v_cargo_id
       AND fim_em IS NULL
       AND obreiro_id = p_obreiro_id;
    RETURN;
  END IF;

  -- Revoga o titular anterior (se existir)
  UPDATE public.atribuicoes_cargo
     SET fim_em = NOW()
   WHERE cargo_id = v_cargo_id
     AND fim_em IS NULL;

  -- Atribui o novo titular
  INSERT INTO public.atribuicoes_cargo (cargo_id, obreiro_id, inicio_em, observacao)
  VALUES (v_cargo_id, p_obreiro_id, NOW(), p_observacao);
END;
$$;
