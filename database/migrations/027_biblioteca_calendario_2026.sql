-- 027_biblioteca_calendario_2026.sql
-- Calendario oficial de contribuicao da biblioteca para 2026.
-- Observacoes de vinculacao por semelhanca cadastral:
-- Carlos Ventura -> Andre Ventura
-- Jucemar Teixeira -> Jucemar Santos
-- Luiz Lavieja -> Luiz Cezar Lavieja
-- Tiago Camargo -> Tiago Pereira

WITH calendario (mes_ref, ano_ref, obreiro_id, valor_previsto, observacao) AS (
    VALUES
        (1, 2026, '4bb81888-6257-43dd-95ac-724e3585dd1c'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (1, 2026, '95a8ad68-1124-486d-bf86-f10bf519feff'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (2, 2026, '5a88c543-6ea2-4e15-a425-b723c8bd1526'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (2, 2026, '06269441-7eb7-4ec2-a92b-bd424f9ddaa6'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (3, 2026, '58ccd3b3-0ee0-4ada-b489-a1ac49f1aab7'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (3, 2026, 'c8b050a4-0e52-446c-b1d8-b1c41635ad72'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (4, 2026, '1abbf8d5-c93c-4b70-b08c-693c1c8b5031'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (4, 2026, '928f9f02-db23-426e-99a8-2b3fbcb316a8'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (5, 2026, 'deb4fac1-45df-47bb-a261-cadc7829223c'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (5, 2026, 'df094672-d5e2-450c-87b6-f182445f6f2a'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (6, 2026, '564dd77b-8cee-4c7d-8bc6-25d59365c68f'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (6, 2026, '74e43d11-6800-42f1-8d92-7cdf72debbf6'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (7, 2026, '77cd865b-00fa-4dd8-afcf-54b7159cc3e6'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (7, 2026, '7e6278d8-7446-4a03-b5e7-5f6473b8dfc9'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (8, 2026, 'b9886cbd-35b2-475d-8245-7af9c474c66f'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (8, 2026, 'bf863ebf-28b5-44d4-9ffe-ec360492b988'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (9, 2026, '8c117597-329f-4bb6-80be-9bf1d55ebd33'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (9, 2026, '1c5220bd-20de-4fe0-b166-212659ed0606'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (10, 2026, '58c6ac20-b271-439c-805b-6b055daf3d9c'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (10, 2026, '4464c4b2-aaa8-4754-a739-0c0665e3d8df'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (11, 2026, 'f9d4452e-5164-45a3-8aac-2dc3e7a9ecd0'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (11, 2026, 'e804c7c9-c117-4ccc-8501-fad07f557593'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (12, 2026, '61014db4-2ab4-463c-8bf3-457d4c75f832'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (12, 2026, '827bdc7e-3df4-4111-a43a-13a4b534b315'::uuid, 44.00, 'Calendario oficial da biblioteca 2026'),
        (12, 2026, 'bc533234-1444-4023-920e-dfabd326b90c'::uuid, 44.00, 'Calendario oficial da biblioteca 2026')
),
inserir_calendario AS (
    INSERT INTO public.biblioteca_contribuintes_mensal (
        mes_ref, ano_ref, obreiro_id, valor_previsto, observacao, criado_por
    )
    SELECT mes_ref, ano_ref, obreiro_id, valor_previsto, observacao, NULL
    FROM calendario
    ON CONFLICT (mes_ref, ano_ref, obreiro_id) DO UPDATE SET
        valor_previsto = EXCLUDED.valor_previsto,
        observacao = EXCLUDED.observacao
    RETURNING mes_ref, ano_ref, obreiro_id
),
categoria AS (
    SELECT id
    FROM public.categorias_financeiras
    WHERE UPPER(codigo) = 'CONTRIBUICAO_BIBLIOTECA'
    LIMIT 1
),
upsert_obrigacoes AS (
    INSERT INTO public.obrigacoes_financeiras (
        obreiro_id, categoria_id, titulo, tipo_obrigacao, recorrencia, status,
        valor_base, parcelas_total, permite_parcelamento, forma_cobranca,
        somar_a_mensalidade, aceita_pagamento_previo, aceita_pagamento_posterior,
        dia_vencimento, mes_referencia_fixa, inicio_competencia, fim_competencia,
        instrucoes_pagamento, observacao, criado_por
    )
    SELECT
        c.obreiro_id,
        categoria.id,
        'Contribuicao Biblioteca ' || LPAD(c.mes_ref::text, 2, '0') || '/' || c.ano_ref::text,
        'biblioteca',
        'avulsa',
        'ativa',
        44.00,
        1,
        FALSE,
        'mensalidade',
        TRUE,
        FALSE,
        TRUE,
        10,
        c.mes_ref,
        make_date(c.ano_ref, c.mes_ref, 1),
        make_date(c.ano_ref, c.mes_ref, 1),
        'Contribuicao da biblioteca somada a mensalidade do mes designado.',
        c.observacao,
        NULL
    FROM calendario c
    CROSS JOIN categoria
    WHERE NOT EXISTS (
        SELECT 1
        FROM public.obrigacoes_financeiras ofi
        WHERE ofi.obreiro_id = c.obreiro_id
          AND ofi.titulo = 'Contribuicao Biblioteca ' || LPAD(c.mes_ref::text, 2, '0') || '/' || c.ano_ref::text
    )
    RETURNING id, obreiro_id, titulo
)
INSERT INTO public.obrigacao_financeira_parcelas (
    obrigacao_id, numero_parcela, competencia_label, competencia_mes, competencia_ano,
    vencimento, valor_previsto, status, categoria_id, observacao
)
SELECT
    ofi.id,
    1,
    LPAD(c.mes_ref::text, 2, '0') || '/' || c.ano_ref::text,
    c.mes_ref,
    c.ano_ref,
    make_date(c.ano_ref, c.mes_ref, LEAST(10, EXTRACT(DAY FROM (date_trunc('month', make_date(c.ano_ref, c.mes_ref, 1)) + INTERVAL '1 month - 1 day'))::int)),
    44.00,
    'pendente',
    categoria.id,
    c.observacao
FROM calendario c
CROSS JOIN categoria
JOIN public.obrigacoes_financeiras ofi
    ON ofi.obreiro_id = c.obreiro_id
   AND ofi.titulo = 'Contribuicao Biblioteca ' || LPAD(c.mes_ref::text, 2, '0') || '/' || c.ano_ref::text
WHERE NOT EXISTS (
    SELECT 1
    FROM public.obrigacao_financeira_parcelas ofp
    WHERE ofp.obrigacao_id = ofi.id
      AND ofp.numero_parcela = 1
);
