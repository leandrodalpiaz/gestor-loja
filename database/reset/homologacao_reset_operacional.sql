-- Homologacao: reset operacional mantendo dados reais (obreiros/cargos/efemerides).
-- Escopo: primeira loja do banco (ORDER BY id LIMIT 1).
-- Importante: este script e destrutivo para dados operacionais.

DO $$
DECLARE
  v_loja_id INTEGER;
BEGIN
  SELECT id INTO v_loja_id
  FROM public.lojas
  ORDER BY id
  LIMIT 1;

  IF v_loja_id IS NULL THEN
    RAISE NOTICE 'Nenhuma loja encontrada. Abortando reset.';
    RETURN;
  END IF;

  RAISE NOTICE 'Reset operacional para loja_id=%', v_loja_id;

  -- =========================
  -- 1) Sessoes + satelites
  -- =========================
  IF to_regclass('public.confirmacoes_sessao') IS NOT NULL THEN
    DELETE FROM public.confirmacoes_sessao cs
    USING public.sessoes s
    WHERE s.id = cs.sessao_id
      AND s.loja_id = v_loja_id;
    RAISE NOTICE 'confirmacoes_sessao=%', FOUND;
  END IF;

  IF to_regclass('public.presencas_sessao') IS NOT NULL THEN
    DELETE FROM public.presencas_sessao ps
    USING public.sessoes s
    WHERE s.id = ps.sessao_id
      AND s.loja_id = v_loja_id;
  END IF;

  IF to_regclass('public.eventos_sessao') IS NOT NULL THEN
    DELETE FROM public.eventos_sessao es
    USING public.sessoes s
    WHERE s.id = es.sessao_id
      AND s.loja_id = v_loja_id;
  END IF;

  IF to_regclass('public.visitas_externas_sessao') IS NOT NULL THEN
    DELETE FROM public.visitas_externas_sessao vs
    USING public.sessoes s
    WHERE s.id = vs.sessao_id
      AND s.loja_id = v_loja_id;
  END IF;

  IF to_regclass('public.trabalhos_sessao') IS NOT NULL THEN
    DELETE FROM public.trabalhos_sessao ts
    USING public.sessoes s
    WHERE s.id = ts.sessao_id
      AND s.loja_id = v_loja_id;
  END IF;

  IF to_regclass('public.publicacoes_sessao') IS NOT NULL THEN
    DELETE FROM public.publicacoes_sessao pub
    USING public.sessoes s
    WHERE s.id = pub.sessao_id
      AND s.loja_id = v_loja_id;
  END IF;

  IF to_regclass('public.balaustres') IS NOT NULL THEN
    DELETE FROM public.balaustres b
    WHERE EXISTS (
      SELECT 1
      FROM public.sessoes s
      WHERE s.id = b.sessao_id
        AND s.loja_id = v_loja_id
    );
  END IF;

  IF to_regclass('public.sessoes') IS NOT NULL THEN
    DELETE FROM public.sessoes
    WHERE loja_id = v_loja_id;
  END IF;

  -- =========================
  -- 2) Financeiro (operacao)
  -- =========================
  IF to_regclass('public.obrigacao_financeira_parcelas') IS NOT NULL
     AND to_regclass('public.obrigacoes_financeiras') IS NOT NULL THEN
    DELETE FROM public.obrigacao_financeira_parcelas p
    USING public.obrigacoes_financeiras o
    WHERE o.id = p.obrigacao_id
      AND o.loja_id = v_loja_id;
  END IF;

  IF to_regclass('public.obrigacoes_financeiras') IS NOT NULL THEN
    IF EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema='public'
        AND table_name='obrigacoes_financeiras'
        AND column_name='loja_id'
    ) THEN
      DELETE FROM public.obrigacoes_financeiras
      WHERE loja_id = v_loja_id;
    ELSE
      DELETE FROM public.obrigacoes_financeiras ofi
      USING public.obreiros o
      WHERE o.id = ofi.obreiro_id
        AND o.loja_id = v_loja_id;
    END IF;
  END IF;

  IF to_regclass('public.lancamentos_financeiros') IS NOT NULL THEN
    IF EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema='public'
        AND table_name='lancamentos_financeiros'
        AND column_name='loja_id'
    ) THEN
      DELETE FROM public.lancamentos_financeiros
      WHERE loja_id = v_loja_id;
    ELSIF EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema='public'
        AND table_name='lancamentos_financeiros'
        AND column_name='obreiro_id'
    ) THEN
      DELETE FROM public.lancamentos_financeiros lf
      USING public.obreiros o
      WHERE o.id = lf.obreiro_id
        AND o.loja_id = v_loja_id;
      -- fallback adicional: alguns lancamentos podem ter apenas created_by.
      IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema='public'
          AND table_name='lancamentos_financeiros'
          AND column_name='created_by'
      ) THEN
        DELETE FROM public.lancamentos_financeiros lf
        USING public.obreiros o
        WHERE o.id = lf.created_by
          AND o.loja_id = v_loja_id;
      END IF;
    END IF;
  END IF;

  IF to_regclass('public.mensalidades_status') IS NOT NULL THEN
    IF EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema='public'
        AND table_name='mensalidades_status'
        AND column_name='loja_id'
    ) THEN
      DELETE FROM public.mensalidades_status
      WHERE loja_id = v_loja_id;
    ELSIF EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema='public'
        AND table_name='mensalidades_status'
        AND column_name='obreiro_id'
    ) THEN
      DELETE FROM public.mensalidades_status ms
      USING public.obreiros o
      WHERE o.id = ms.obreiro_id
        AND o.loja_id = v_loja_id;
    END IF;
  END IF;

  IF to_regclass('public.fechamento_mensal') IS NOT NULL THEN
    IF EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema='public'
        AND table_name='fechamento_mensal'
        AND column_name='loja_id'
    ) THEN
      DELETE FROM public.fechamento_mensal
      WHERE loja_id = v_loja_id;
    END IF;
  END IF;

  IF to_regclass('public.tronco_solidariedade') IS NOT NULL THEN
    IF EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema='public'
        AND table_name='tronco_solidariedade'
        AND column_name='loja_id'
    ) THEN
      DELETE FROM public.tronco_solidariedade
      WHERE loja_id = v_loja_id;
    END IF;
  END IF;

  -- comprovantes_pix nem sempre tem loja_id; remove por join com obreiro (loja_id) quando possivel.
  IF to_regclass('public.comprovantes_pix') IS NOT NULL THEN
    IF EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema='public'
        AND table_name='comprovantes_pix'
        AND column_name='loja_id'
    ) THEN
      DELETE FROM public.comprovantes_pix
      WHERE loja_id = v_loja_id;
    ELSIF EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema='public'
        AND table_name='comprovantes_pix'
        AND column_name='obreiro_id'
    ) THEN
      DELETE FROM public.comprovantes_pix cp
      USING public.obreiros o
      WHERE o.id = cp.obreiro_id
        AND o.loja_id = v_loja_id;
    END IF;
  END IF;

  -- =========================
  -- 3) Biblioteca (reset total)
  -- =========================
  IF to_regclass('public.emprestimos') IS NOT NULL AND to_regclass('public.acervo') IS NOT NULL THEN
    DELETE FROM public.emprestimos e
    USING public.acervo a
    WHERE a.id = e.acervo_id
      AND a.loja_id = v_loja_id;
  END IF;

  IF to_regclass('public.biblioteca_comentarios') IS NOT NULL AND to_regclass('public.acervo') IS NOT NULL THEN
    DELETE FROM public.biblioteca_comentarios c
    USING public.acervo a
    WHERE a.id = c.acervo_id
      AND a.loja_id = v_loja_id;
  END IF;

  IF to_regclass('public.biblioteca_reacoes') IS NOT NULL AND to_regclass('public.acervo') IS NOT NULL THEN
    DELETE FROM public.biblioteca_reacoes r
    USING public.acervo a
    WHERE a.id = r.acervo_id
      AND a.loja_id = v_loja_id;
  END IF;

  IF to_regclass('public.acervo') IS NOT NULL THEN
    DELETE FROM public.acervo
    WHERE loja_id = v_loja_id;
  END IF;

  -- =========================
  -- Contagens finais (auditoria)
  -- =========================
  RAISE NOTICE 'Restantes: sessoes=%', (SELECT COUNT(*) FROM public.sessoes WHERE loja_id = v_loja_id);
  IF to_regclass('public.lancamentos_financeiros') IS NOT NULL AND EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='lancamentos_financeiros' AND column_name='loja_id'
  ) THEN
    RAISE NOTICE 'Restantes: lancamentos_financeiros=%', (SELECT COUNT(*) FROM public.lancamentos_financeiros WHERE loja_id = v_loja_id);
  END IF;
  IF to_regclass('public.obrigacoes_financeiras') IS NOT NULL AND EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema='public' AND table_name='obrigacoes_financeiras' AND column_name='loja_id'
  ) THEN
    RAISE NOTICE 'Restantes: obrigacoes_financeiras=%', (SELECT COUNT(*) FROM public.obrigacoes_financeiras WHERE loja_id = v_loja_id);
  END IF;
  RAISE NOTICE 'Restantes: acervo=%', (SELECT COUNT(*) FROM public.acervo WHERE loja_id = v_loja_id);
END $$;
