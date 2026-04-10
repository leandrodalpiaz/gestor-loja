-- 022_financeiro_relatorio_gestao.sql
-- Estrutura categorias e consolidacao financeira por periodo de gestao.

ALTER TABLE public.categorias_financeiras
    ADD COLUMN IF NOT EXISTS codigo VARCHAR(80),
    ADD COLUMN IF NOT EXISTS bloco_relatorio VARCHAR(80),
    ADD COLUMN IF NOT EXISTS exige_entidade_auxiliada BOOLEAN NOT NULL DEFAULT FALSE;

UPDATE public.categorias_financeiras
   SET codigo = CASE nome
       WHEN 'Mensalidades' THEN 'MENSALIDADES'
       WHEN 'Biblioteca Fiat Lux' THEN 'BIBLIOTECA'
       WHEN 'Ágape' THEN 'AGAPE_RECEITA'
       WHEN 'Ãgape' THEN 'AGAPE_RECEITA'
       WHEN 'Iniciação' THEN 'INICIACAO'
       WHEN 'IniciaÃ§Ã£o' THEN 'INICIACAO'
       WHEN 'Elevação' THEN 'ELEVACAO'
       WHEN 'ElevaÃ§Ã£o' THEN 'ELEVACAO'
       WHEN 'Exaltação' THEN 'EXALTACAO'
       WHEN 'ExaltaÃ§Ã£o' THEN 'EXALTACAO'
       WHEN 'Regularização' THEN 'REGULARIZACAO'
       WHEN 'RegularizaÃ§Ã£o' THEN 'REGULARIZACAO'
       WHEN 'Filiação' THEN 'FILIACAO'
       WHEN 'FiliaÃ§Ã£o' THEN 'FILIACAO'
       WHEN 'Diversos' THEN 'DIVERSOS_RECEITA'
       WHEN 'Tronco de Solidariedade' THEN 'TRONCO'
       WHEN 'Juros Aplicação Bancária' THEN 'JUROS_APLICACAO'
       WHEN 'Juros AplicaÃ§Ã£o BancÃ¡ria' THEN 'JUROS_APLICACAO'
       WHEN 'Despesas Grande Loja' THEN 'DESPESAS_GRANDE_LOJA'
       WHEN 'Aluguel Templo' THEN 'ALUGUEL_TEMPLO'
       WHEN 'Aluguel Salão de Ágapes' THEN 'ALUGUEL_AGAPE'
       WHEN 'Aluguel SalÃ£o de Ãgapes' THEN 'ALUGUEL_AGAPE'
       WHEN 'Aluguel' THEN 'ALUGUEL_DIVERSOS'
       WHEN 'Despesas Bancárias' THEN 'DESPESAS_BANCARIAS'
       WHEN 'Despesas BancÃ¡rias' THEN 'DESPESAS_BANCARIAS'
       WHEN 'A Trolha' THEN 'A_TROLHA'
       WHEN 'Gráfica' THEN 'GRAFICA'
       WHEN 'GrÃ¡fica' THEN 'GRAFICA'
       WHEN 'Despesas Cartório' THEN 'DESPESAS_CARTORIO'
       WHEN 'Despesas CartÃ³rio' THEN 'DESPESAS_CARTORIO'
       WHEN 'Despesas Ágape' THEN 'DESPESAS_AGAPE'
       WHEN 'Despesas Ãgape' THEN 'DESPESAS_AGAPE'
       WHEN 'Despesas Tronco de Solidariedade' THEN 'DESPESAS_TRONCO'
       WHEN 'Despesas Diversas da Loja' THEN 'DESPESAS_DIVERSAS'
       ELSE COALESCE(codigo, UPPER(REGEXP_REPLACE(nome, '[^A-Za-z0-9]+', '_', 'g')))
   END,
       bloco_relatorio = CASE nome
       WHEN 'Mensalidades' THEN 'receitas_ordinarias'
       WHEN 'Biblioteca Fiat Lux' THEN 'receitas_eventuais'
       WHEN 'Ágape' THEN 'agapes_eventos'
       WHEN 'Ãgape' THEN 'agapes_eventos'
       WHEN 'Iniciação' THEN 'capitacoes'
       WHEN 'IniciaÃ§Ã£o' THEN 'capitacoes'
       WHEN 'Elevação' THEN 'capitacoes'
       WHEN 'ElevaÃ§Ã£o' THEN 'capitacoes'
       WHEN 'Exaltação' THEN 'capitacoes'
       WHEN 'ExaltaÃ§Ã£o' THEN 'capitacoes'
       WHEN 'Regularização' THEN 'capitacoes'
       WHEN 'RegularizaÃ§Ã£o' THEN 'capitacoes'
       WHEN 'Filiação' THEN 'capitacoes'
       WHEN 'FiliaÃ§Ã£o' THEN 'capitacoes'
       WHEN 'Diversos' THEN 'receitas_eventuais'
       WHEN 'Tronco de Solidariedade' THEN 'tronco'
       WHEN 'Juros Aplicação Bancária' THEN 'receitas_financeiras'
       WHEN 'Juros AplicaÃ§Ã£o BancÃ¡ria' THEN 'receitas_financeiras'
       WHEN 'Despesas Grande Loja' THEN 'despesas_potencia'
       WHEN 'Aluguel Templo' THEN 'despesas_administrativas'
       WHEN 'Aluguel Salão de Ágapes' THEN 'agapes_eventos'
       WHEN 'Aluguel SalÃ£o de Ãgapes' THEN 'agapes_eventos'
       WHEN 'Aluguel' THEN 'despesas_administrativas'
       WHEN 'Despesas Bancárias' THEN 'despesas_bancarias'
       WHEN 'Despesas BancÃ¡rias' THEN 'despesas_bancarias'
       WHEN 'A Trolha' THEN 'despesas_ritualisticas'
       WHEN 'Gráfica' THEN 'despesas_administrativas'
       WHEN 'GrÃ¡fica' THEN 'despesas_administrativas'
       WHEN 'Despesas Cartório' THEN 'despesas_administrativas'
       WHEN 'Despesas CartÃ³rio' THEN 'despesas_administrativas'
       WHEN 'Despesas Ágape' THEN 'agapes_eventos'
       WHEN 'Despesas Ãgape' THEN 'agapes_eventos'
       WHEN 'Despesas Tronco de Solidariedade' THEN 'entidades_auxiliadas'
       WHEN 'Despesas Diversas da Loja' THEN 'despesas_administrativas'
       ELSE COALESCE(bloco_relatorio, 'outros')
   END,
       exige_entidade_auxiliada = CASE
       WHEN nome IN ('Despesas Tronco de Solidariedade') THEN TRUE
       ELSE exige_entidade_auxiliada
   END
 WHERE TRUE;

CREATE UNIQUE INDEX IF NOT EXISTS idx_categorias_financeiras_codigo
    ON public.categorias_financeiras(codigo)
    WHERE codigo IS NOT NULL;

ALTER TABLE public.lancamentos_financeiros
    ADD COLUMN IF NOT EXISTS entidade_auxiliada VARCHAR(180),
    ADD COLUMN IF NOT EXISTS observacao_gestao TEXT;
