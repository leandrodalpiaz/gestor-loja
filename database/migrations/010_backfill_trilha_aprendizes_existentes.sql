-- 010_backfill_trilha_aprendizes_existentes.sql
-- Popula a trilha padrão para todos os Aprendizes ativos já existentes.
-- Script idempotente: não duplica etapas que já tiverem sido criadas.

INSERT INTO public.trilha_aprendiz (
  aprendiz_id,
  etapa_ordem,
  titulo_etapa,
  status,
  created_at,
  updated_at
)
SELECT
  o.id AS aprendiz_id,
  etapas.etapa_ordem,
  etapas.titulo_etapa,
  'nao_iniciado' AS status,
  NOW() AS created_at,
  NOW() AS updated_at
FROM public.obreiros o
CROSS JOIN (
  VALUES
    (1, 'Entrega das impressões de iniciação'),
    (2, 'Passar o complemento à iniciação'),
    (3, 'Passar e receber o trabalho da 1ª instrução'),
    (4, 'Passar e receber o trabalho da 2ª instrução'),
    (5, 'Passar e receber o trabalho da 3ª instrução'),
    (6, 'Passar e receber o trabalho da 4ª instrução'),
    (7, 'Passar e receber o trabalho da 5ª instrução'),
    (8, 'Solicitar o certificado de conclusão da docência maçônica')
) AS etapas(etapa_ordem, titulo_etapa)
WHERE o.ativo = TRUE
  AND LOWER(TRIM(COALESCE(o.grau, ''))) = 'aprendiz'
ON CONFLICT (aprendiz_id, etapa_ordem) DO NOTHING;
