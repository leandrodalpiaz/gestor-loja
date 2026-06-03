-- 050_corrigir_grafia_renascenca.sql
-- Corrige a ortografia de 'Renascenca' para 'Renascença' nos registros de exibição da Loja.

UPDATE public.lojas 
   SET nome = 'Loja Renascença' 
 WHERE nome = 'Loja Renascenca';

UPDATE public.configuracoes_loja 
   SET nome_loja = 'Renascença' 
 WHERE nome_loja = 'Renascenca';

UPDATE public.configuracoes_loja 
   SET historia_loja = REPLACE(REPLACE(historia_loja, 'Renascenca', 'Renascença'), 'RENASCENCA', 'RENASCENÇA')
 WHERE historia_loja LIKE '%Renascenca%' OR historia_loja LIKE '%RENASCENCA%';

UPDATE public.publicacoes_sessao 
   SET conteudo = REPLACE(conteudo, 'Renascenca', 'Renascença')
 WHERE conteudo LIKE '%Renascenca%';
