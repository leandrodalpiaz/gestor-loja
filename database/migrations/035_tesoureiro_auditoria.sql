-- 035_tesoureiro_auditoria.sql
-- Adiciona campos de auditoria para operações críticas na tesouraria

-- Adicionar campos de auditoria em obrigacao_financeira_parcelas
ALTER TABLE IF EXISTS public.obrigacao_financeira_parcelas
ADD COLUMN IF NOT EXISTS quitado_por UUID REFERENCES public.obreiros(id),
ADD COLUMN IF NOT EXISTS quitado_em TIMESTAMP;

-- Adicionar campos de auditoria em comprovantes_pix para rastreabilidade
ALTER TABLE IF EXISTS public.comprovantes_pix
ADD COLUMN IF NOT EXISTS criado_por UUID REFERENCES public.obreiros(id),
ADD COLUMN IF NOT EXISTS cancelado_por UUID REFERENCES public.obreiros(id),
ADD COLUMN IF NOT EXISTS cancelado_em TIMESTAMP,
ADD COLUMN IF NOT EXISTS motivo_cancelamento TEXT;

-- Adicionar campo de status para rejeição em comprovantes (se não tiver)
ALTER TABLE IF EXISTS public.comprovantes_pix
ADD COLUMN IF NOT EXISTS status_anterior VARCHAR(20);

-- Corrigir fechamento_mensal: trocar fechado_por INT para UUID
-- Nota: Você pode ter que fazer isso com cuidado se houver dados
-- Para agora, só adicionamos um novo campo se não tiver UUID
-- (Se precisar realmente trocar, consulte um DBA)

-- Criar índice para auditoria rápida
CREATE INDEX IF NOT EXISTS idx_obrigacao_financeira_parcelas_quitado_por
    ON public.obrigacao_financeira_parcelas(quitado_por);

CREATE INDEX IF NOT EXISTS idx_comprovantes_pix_criado_por
    ON public.comprovantes_pix(criado_por);

CREATE INDEX IF NOT EXISTS idx_comprovantes_pix_cancelado_por
    ON public.comprovantes_pix(cancelado_por);
