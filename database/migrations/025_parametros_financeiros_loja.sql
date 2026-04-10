-- 025_parametros_financeiros_loja.sql
-- Parametros financeiros gerais da Loja para mensalidade e cobranca institucional.

ALTER TABLE public.configuracoes_loja
    ADD COLUMN IF NOT EXISTS mensalidade_valor_padrao NUMERIC(12,2) NOT NULL DEFAULT 150.00,
    ADD COLUMN IF NOT EXISTS mensalidade_dia_sugerido INT NOT NULL DEFAULT 10,
    ADD COLUMN IF NOT EXISTS mensalidade_regra_atraso VARCHAR(60) NOT NULL DEFAULT 'primeiro_dia_util_mes_seguinte';

UPDATE public.configuracoes_loja
SET mensalidade_valor_padrao = COALESCE(mensalidade_valor_padrao, 150.00),
    mensalidade_dia_sugerido = COALESCE(mensalidade_dia_sugerido, 10),
    mensalidade_regra_atraso = COALESCE(NULLIF(mensalidade_regra_atraso, ''), 'primeiro_dia_util_mes_seguinte')
WHERE id = 1;

INSERT INTO public.configuracoes_loja (
    id,
    mensalidade_valor_padrao,
    mensalidade_dia_sugerido,
    mensalidade_regra_atraso,
    created_at,
    updated_at
)
VALUES (
    1,
    150.00,
    10,
    'primeiro_dia_util_mes_seguinte',
    NOW(),
    NOW()
)
ON CONFLICT (id) DO UPDATE SET
    mensalidade_valor_padrao = COALESCE(public.configuracoes_loja.mensalidade_valor_padrao, EXCLUDED.mensalidade_valor_padrao),
    mensalidade_dia_sugerido = COALESCE(public.configuracoes_loja.mensalidade_dia_sugerido, EXCLUDED.mensalidade_dia_sugerido),
    mensalidade_regra_atraso = COALESCE(NULLIF(public.configuracoes_loja.mensalidade_regra_atraso, ''), EXCLUDED.mensalidade_regra_atraso),
    updated_at = NOW();
