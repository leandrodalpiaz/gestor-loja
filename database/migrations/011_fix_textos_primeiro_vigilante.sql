-- 011_fix_textos_primeiro_vigilante.sql
-- Normaliza textos do 1º Vigilante já existentes no banco.

UPDATE public.cargos
SET nome_exibicao = '1º Vigilante'
WHERE codigo = 'PRIMEIRO_VIGILANTE';

UPDATE public.trilha_aprendiz
SET titulo_etapa = CASE etapa_ordem
  WHEN 1 THEN 'Entrega das impressões de iniciação'
  WHEN 2 THEN 'Passar o complemento à iniciação'
  WHEN 3 THEN 'Passar e receber o trabalho da 1ª instrução'
  WHEN 4 THEN 'Passar e receber o trabalho da 2ª instrução'
  WHEN 5 THEN 'Passar e receber o trabalho da 3ª instrução'
  WHEN 6 THEN 'Passar e receber o trabalho da 4ª instrução'
  WHEN 7 THEN 'Passar e receber o trabalho da 5ª instrução'
  WHEN 8 THEN 'Solicitar o certificado de conclusão da docência maçônica'
  ELSE titulo_etapa
END
WHERE etapa_ordem BETWEEN 1 AND 8;
