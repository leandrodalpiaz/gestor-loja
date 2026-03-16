-- ============================================================
-- Migration 003 ALT: Alterações para módulo Tesoureiro
-- Se as tabelas já existem desta criação anterior, roda ALTERs
-- ============================================================

-- Adicionar colunas faltantes na tabela comprovantes_pix (se não existir)
ALTER TABLE IF EXISTS comprovantes_pix 
ADD COLUMN IF NOT EXISTS tipo_arquivo VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS nome_arquivo VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS descricao_usuario TEXT NULL;

-- Se a tabela não existe ainda, criar todas
CREATE TABLE IF NOT EXISTS categorias_financeiras (
    id        SERIAL PRIMARY KEY,
    nome      VARCHAR(100) NOT NULL UNIQUE,
    tipo      VARCHAR(10) NOT NULL,
    descricao TEXT NULL,
    principal BOOLEAN NOT NULL DEFAULT true,
    ativo     BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categorias_financeiras (nome, tipo, descricao, principal) VALUES
('Mensalidades', 'entrada', 'Contribuição mensal dos obreiros', true),
('Biblioteca Fiat Lux', 'entrada', 'Receita biblioteca', true),
('Ágape', 'entrada', 'Receita de ágapes e eventos', true),
('Iniciação', 'entrada', 'Taxa de iniciação', true),
('Elevação', 'entrada', 'Taxa de elevação de grau', true),
('Exaltação', 'entrada', 'Taxa de exaltação de grau', true),
('Regularização', 'entrada', 'Taxa de regularização', true),
('Filiação', 'entrada', 'Taxa de filiação de irmão externo', false),
('Diversos', 'entrada', 'Receitas diversas não categorizadas', false),
('Tronco de Solidariedade', 'entrada', 'Tronco de arrecadação solidária', true),
('Juros Aplicação Bancária', 'entrada', 'Juros de aplicações financeiras', false),
('Despesas Grande Loja', 'saida', 'Contribuição à Grande Loja', true),
('Aluguel Templo', 'saida', 'Aluguel do espaço de reuniões', true),
('Aluguel Salão de Ágapes', 'saida', 'Aluguel para eventos e ágapes', true),
('Aluguel', 'saida', 'Aluguéis gerais', false),
('Despesas Bancárias', 'saida', 'Tarifas e taxas bancárias', true),
('A Trolha', 'saida', 'Fornecedor de materiais ritualísticos', false),
('Gráfica', 'saida', 'Serviços gráficos', false),
('Despesas Cartório', 'saida', 'Registros cartoriais', false),
('Despesas Ágape', 'saida', 'Custeio de ágapes e eventos', true),
('Despesas Tronco de Solidariedade', 'saida', 'Aplicação do tronco solidário', true),
('Despesas Diversas da Loja', 'saida', 'Despesas gerais', false)
ON CONFLICT (nome) DO NOTHING;

CREATE TABLE IF NOT EXISTS lancamentos_financeiros (
    id              SERIAL PRIMARY KEY,
    tipo            VARCHAR(10) NOT NULL,
    categoria_id    INT NOT NULL REFERENCES categorias_financeiras(id),
    valor           NUMERIC(10,2) NOT NULL,
    data_lancamento DATE NOT NULL,
    descricao       TEXT NULL,
    obreiro_id      INT NULL REFERENCES obreiros(id),
    mes_ref         INT NOT NULL,
    ano_ref         INT NOT NULL,
    created_by      INT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comprovantes_pix (
    id               SERIAL PRIMARY KEY,
    obreiro_id       INT NULL REFERENCES obreiros(id),
    telegram_user_id BIGINT NOT NULL,
    nome_telegram    VARCHAR(255) NULL,
    file_id          TEXT NOT NULL,
    tipo_arquivo     VARCHAR(50) NULL,
    nome_arquivo     VARCHAR(255) NULL,
    descricao_usuario TEXT NULL,
    valor_informado  NUMERIC(10,2) NULL,
    mes_ref_informado INT NULL,
    ano_ref_informado INT NULL,
    status           VARCHAR(20) NOT NULL DEFAULT 'pendente',
    motivo_rejeicao  TEXT NULL,
    validado_por     INT NULL REFERENCES obreiros(id),
    valor_validado   NUMERIC(10,2) NULL,
    mes_ref_validado INT NULL,
    ano_ref_validado INT NULL,
    lancamento_id    INT NULL REFERENCES lancamentos_financeiros(id),
    criado_em        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    validado_em      TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS mensalidades_status (
    id            SERIAL PRIMARY KEY,
    obreiro_id    INT NOT NULL REFERENCES obreiros(id),
    mes_ref       INT NOT NULL,
    ano_ref       INT NOT NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'pendente',
    lancamento_id INT NULL REFERENCES lancamentos_financeiros(id),
    nota          TEXT NULL,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(obreiro_id, mes_ref, ano_ref)
);

CREATE TABLE IF NOT EXISTS regularidade_obreiro (
    id           SERIAL PRIMARY KEY,
    obreiro_id   INT NOT NULL REFERENCES obreiros(id),
    mes_ref      INT NOT NULL,
    ano_ref      INT NOT NULL,
    status       VARCHAR(20) NOT NULL DEFAULT 'regular',
    observacao   TEXT NULL,
    definido_por INT NULL REFERENCES obreiros(id),
    definido_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(obreiro_id, mes_ref, ano_ref)
);

CREATE TABLE IF NOT EXISTS fechamento_mensal (
    id               SERIAL PRIMARY KEY,
    mes_ref          INT NOT NULL,
    ano_ref          INT NOT NULL,
    saldo_inicial    NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_entradas   NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_saidas     NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    saldo_final      NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    status           VARCHAR(20) NOT NULL DEFAULT 'aberto',
    fechado_por      INT NULL REFERENCES obreiros(id),
    fechado_em       TIMESTAMP NULL,
    criado_em        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(mes_ref, ano_ref)
);

CREATE TABLE IF NOT EXISTS ajustes_saldo_auditoria (
    id               SERIAL PRIMARY KEY,
    fechamento_id    INT NOT NULL REFERENCES fechamento_mensal(id),
    campo_alterado   VARCHAR(50) NOT NULL,
    valor_anterior   NUMERIC(12,2) NOT NULL,
    valor_novo       NUMERIC(12,2) NOT NULL,
    justificativa    TEXT NOT NULL,
    alterado_por     INT NULL REFERENCES obreiros(id),
    alterado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tronco_solidariedade (
    id        SERIAL PRIMARY KEY,
    tipo      VARCHAR(10) NOT NULL,
    valor     NUMERIC(12,2) NOT NULL,
    data_mov  DATE NOT NULL,
    sessao_ref VARCHAR(50) NULL,
    descricao TEXT NULL,
    criado_por INT NULL REFERENCES obreiros(id),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Criar índices
CREATE INDEX IF NOT EXISTS idx_categorias_tipo_ativo 
    ON categorias_financeiras(tipo, ativo);
CREATE INDEX IF NOT EXISTS idx_lancamentos_mes_ano 
    ON lancamentos_financeiros(mes_ref, ano_ref);
CREATE INDEX IF NOT EXISTS idx_lancamentos_categoria 
    ON lancamentos_financeiros(categoria_id);
CREATE INDEX IF NOT EXISTS idx_lancamentos_obreiro 
    ON lancamentos_financeiros(obreiro_id);
CREATE INDEX IF NOT EXISTS idx_comprovantes_status 
    ON comprovantes_pix(status);
CREATE INDEX IF NOT EXISTS idx_comprovantes_obreiro 
    ON comprovantes_pix(obreiro_id);
CREATE INDEX IF NOT EXISTS idx_comprovantes_telegram 
    ON comprovantes_pix(telegram_user_id);
CREATE INDEX IF NOT EXISTS idx_mensalidades_status 
    ON mensalidades_status(status);
CREATE INDEX IF NOT EXISTS idx_mensalidades_obreiro_mes 
    ON mensalidades_status(obreiro_id, mes_ref, ano_ref DESC);
CREATE INDEX IF NOT EXISTS idx_regularidade_obreiro 
    ON regularidade_obreiro(obreiro_id, mes_ref, ano_ref);
CREATE INDEX IF NOT EXISTS idx_fechamento_mes_ano 
    ON fechamento_mensal(mes_ref, ano_ref DESC);
CREATE INDEX IF NOT EXISTS idx_auditoria_fechamento 
    ON ajustes_saldo_auditoria(fechamento_id);
CREATE INDEX IF NOT EXISTS idx_tronco_data 
    ON tronco_solidariedade(data_mov);
