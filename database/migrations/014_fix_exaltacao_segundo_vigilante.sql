-- 014_fix_exaltacao_segundo_vigilante.sql
-- Corrige termos de elevacao para exaltacao na trilha do 2º Vigilante.

UPDATE public.trilha_companheiro
SET status = 'apto_para_exaltacao'
WHERE status = 'apto_para_elevacao';

UPDATE public.trilha_companheiro
SET status = 'exaltacao_recomendada'
WHERE status = 'elevacao_recomendada';

UPDATE public.trilha_companheiro
SET titulo_etapa = 'Indicar para exaltação ao grau de Mestre'
WHERE etapa_ordem = 8;

ALTER TABLE public.trilha_companheiro
DROP CONSTRAINT IF EXISTS chk_trilha_companheiro_status;

ALTER TABLE public.trilha_companheiro
ADD CONSTRAINT chk_trilha_companheiro_status CHECK (
  status IN (
    'nao_iniciado',
    'disponibilizado',
    'aguardando_entrega',
    'recebido',
    'revisado',
    'concluido',
    'apto_para_certificado',
    'certificado_solicitado',
    'apto_para_exaltacao',
    'exaltacao_recomendada'
  )
);
