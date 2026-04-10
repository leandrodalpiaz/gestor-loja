-- 023_padronizacao_categorias_entrada.sql
-- Padroniza categorias de entrada da Tesouraria para uso administrativo e de relatorio.

UPDATE public.categorias_financeiras
   SET nome = 'Mensalidade',
       codigo = 'MENSALIDADE',
       bloco_relatorio = 'receitas_ordinarias'
 WHERE nome IN ('Mensalidades')
    OR codigo IN ('MENSALIDADES');

UPDATE public.categorias_financeiras
   SET nome = 'Contribuicao a Biblioteca',
       codigo = 'CONTRIBUICAO_BIBLIOTECA',
       bloco_relatorio = 'receitas_eventuais'
 WHERE nome IN ('Biblioteca Fiat Lux')
    OR codigo IN ('BIBLIOTECA');

UPDATE public.categorias_financeiras
   SET nome = 'Doacoes',
       codigo = 'DOACOES',
       bloco_relatorio = 'receitas_eventuais'
 WHERE nome IN ('Diversos')
    OR codigo IN ('DIVERSOS_RECEITA');

INSERT INTO public.categorias_financeiras (nome, tipo, descricao, principal, ativo, codigo, bloco_relatorio, exige_entidade_auxiliada)
SELECT 'Joias', 'entrada', 'Joias de iniciacao, elevacao ou exaltacao.', TRUE, TRUE, 'JOIAS', 'capitacoes', FALSE
WHERE NOT EXISTS (
    SELECT 1 FROM public.categorias_financeiras WHERE codigo = 'JOIAS'
);

INSERT INTO public.categorias_financeiras (nome, tipo, descricao, principal, ativo, codigo, bloco_relatorio, exige_entidade_auxiliada)
SELECT 'Doacoes', 'entrada', 'Doacoes e contribuicoes espontaneas recebidas pela Loja.', FALSE, TRUE, 'DOACOES', 'receitas_eventuais', FALSE
WHERE NOT EXISTS (
    SELECT 1 FROM public.categorias_financeiras WHERE codigo = 'DOACOES'
);

UPDATE public.categorias_financeiras
   SET bloco_relatorio = 'tronco',
       codigo = 'TRONCO'
 WHERE nome IN ('Tronco de Solidariedade')
    OR codigo IN ('TRONCO');

UPDATE public.categorias_financeiras
   SET bloco_relatorio = 'capitacoes'
 WHERE codigo IN ('INICIACAO', 'ELEVACAO', 'EXALTACAO', 'REGULARIZACAO', 'FILIACAO');
