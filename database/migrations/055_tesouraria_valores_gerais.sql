-- Migration 055: Adiciona colunas para valores padrões de joias de graus na configuração da loja
ALTER TABLE public.configuracoes_loja
ADD COLUMN IF NOT EXISTS joia_iniciacao_valor_padrao NUMERIC NULL DEFAULT 1502.00,
ADD COLUMN IF NOT EXISTS joia_elevacao_valor_padrao NUMERIC NULL DEFAULT 1502.00,
ADD COLUMN IF NOT EXISTS joia_exaltacao_valor_padrao NUMERIC NULL DEFAULT 1502.00;
