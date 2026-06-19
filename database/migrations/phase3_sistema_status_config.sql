-- phase3_sistema_status_config.sql
-- Adiciona os campos de controle técnico de implantação e status do sistema (online, manutenção, suspenso).

ALTER TABLE public.configuracoes_loja
    ADD COLUMN IF NOT EXISTS sistema_status VARCHAR(30) NOT NULL DEFAULT 'online',
    ADD COLUMN IF NOT EXISTS manutencao_mensagem VARCHAR(255) NOT NULL DEFAULT 'O sistema está em manutenção técnica programada. Retornaremos em breve.',
    ADD COLUMN IF NOT EXISTS suspenso_mensagem VARCHAR(255) NOT NULL DEFAULT 'O acesso a esta Loja está suspenso ou desativado.';
