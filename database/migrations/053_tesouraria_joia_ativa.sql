-- 053_tesouraria_joia_ativa.sql
-- Add columns for optional treasurer-controlled joias and custom library month.

ALTER TABLE public.obreiros 
    ADD COLUMN IF NOT EXISTS financeiro_joia_ativa BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS financeiro_joia_tipo VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS financeiro_biblioteca_mes INT NULL;

-- Backfill existing configured joias to active
UPDATE public.obreiros 
SET financeiro_joia_ativa = TRUE,
    financeiro_joia_tipo = CASE 
        WHEN grau = 'Aprendiz' THEN 'elevacao'
        WHEN grau = 'Companheiro' THEN 'exaltacao'
        ELSE 'iniciacao'
    END
WHERE EXISTS (
       SELECT 1 
       FROM public.obrigacoes_financeiras o 
       WHERE o.obreiro_id = obreiros.id 
         AND o.tipo_obrigacao = 'joia'
   );
