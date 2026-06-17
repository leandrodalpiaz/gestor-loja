-- 054_tesouraria_mensalidade_isento.sql
-- Add columns to support custom monthly fee value and exemption status.

ALTER TABLE public.obreiros 
    ADD COLUMN IF NOT EXISTS financeiro_mensalidade_valor NUMERIC NULL,
    ADD COLUMN IF NOT EXISTS financeiro_mensalidade_formato VARCHAR(40) NOT NULL DEFAULT 'mensal';
