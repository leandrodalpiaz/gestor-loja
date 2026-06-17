-- 052_tesouraria_config_membros.sql
-- Adiciona colunas para configuração financeira individual de cada obreiro: joias e contribuição à biblioteca.

ALTER TABLE public.obreiros
    ADD COLUMN IF NOT EXISTS financeiro_joia_valor NUMERIC(12,2) NULL,
    ADD COLUMN IF NOT EXISTS financeiro_joia_formato VARCHAR(40) NOT NULL DEFAULT 'a_vista',
    ADD COLUMN IF NOT EXISTS financeiro_biblioteca_valor NUMERIC(12,2) NULL,
    ADD COLUMN IF NOT EXISTS financeiro_biblioteca_formato VARCHAR(40) NOT NULL DEFAULT 'mensal';

COMMENT ON COLUMN public.obreiros.financeiro_joia_valor IS 'Valor customizado da joia de iniciacao/elevacao/exaltacao para o obreiro.';
COMMENT ON COLUMN public.obreiros.financeiro_joia_formato IS 'Formato de pagamento da joia (a_vista, parcelado, isento).';
COMMENT ON COLUMN public.obreiros.financeiro_biblioteca_valor IS 'Valor customizado da contribuicao mensal da biblioteca para o obreiro.';
COMMENT ON COLUMN public.obreiros.financeiro_biblioteca_formato IS 'Formato de pagamento da biblioteca (mensal, anual, isento).';
