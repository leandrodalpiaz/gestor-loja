-- 013_backfill_trilha_companheiros_existentes.sql
-- Popula a trilha padrão para todos os Companheiros ativos já existentes.
-- Script idempotente: não duplica etapas já criadas.

INSERT INTO public.trilha_companheiro (
  companheiro_id,
  etapa_ordem,
  titulo_etapa,
  status,
  created_at,
  updated_at
)
SELECT
  o.id AS companheiro_id,
  etapas.etapa_ordem,
  etapas.titulo_etapa,
  'nao_iniciado' AS status,
  NOW() AS created_at,
  NOW() AS updated_at
FROM public.obreiros o
CROSS JOIN (
  VALUES
    (1, 'Entrega das impressões da elevação'),
    (2, 'Passar a 1ª instrução'),
    (3, 'Passar e receber o trabalho da 1ª instrução'),
    (4, 'Passar e receber o trabalho da 2ª instrução'),
    (5, 'Passar e receber o trabalho da 3ª instrução'),
    (6, 'Registrar a docência'),
    (7, 'Solicitar o certificado de conclusão da docência'),
    (8, 'Indicar para exaltação ao grau de Mestre')
) AS etapas(etapa_ordem, titulo_etapa)
WHERE o.ativo = TRUE
  AND LOWER(TRIM(COALESCE(o.grau, ''))) = 'companheiro'
ON CONFLICT (companheiro_id, etapa_ordem) DO NOTHING;
