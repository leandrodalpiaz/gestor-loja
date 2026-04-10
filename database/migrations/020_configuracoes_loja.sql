-- 020_configuracoes_loja.sql
-- Parametros gerais da Loja para uso transversal em relatorios e validacoes institucionais.

CREATE TABLE IF NOT EXISTS public.configuracoes_loja (
    id SMALLINT PRIMARY KEY DEFAULT 1 CHECK (id = 1),
    nome_loja VARCHAR(180),
    numero_loja VARCHAR(40),
    cidade VARCHAR(120),
    uf CHAR(2),
    oriente VARCHAR(120),
    potencia_nome VARCHAR(180),
    potencia_sigla VARCHAR(40),
    grande_secretaria VARCHAR(180),
    rito VARCHAR(120),
    data_fundacao DATE,
    cnpj VARCHAR(20),
    email_oficial VARCHAR(180),
    telefone_oficial VARCHAR(40),
    endereco VARCHAR(255),
    cep VARCHAR(12),
    observacao_relatorios TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO public.configuracoes_loja (id, created_at, updated_at)
VALUES (1, NOW(), NOW())
ON CONFLICT (id) DO NOTHING;
