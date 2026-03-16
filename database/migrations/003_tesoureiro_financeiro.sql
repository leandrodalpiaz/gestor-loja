-- ============================================================
-- Migration 003: Módulo Financeiro - Tesoureiro
-- ============================================================

-- ============================================================
-- 1. Categorias de Entrada/Saída (referência)
-- ============================================================
CREATE TABLE IF NOT EXISTS categorias_financeiras (
    id        SERIAL PRIMARY KEY,
    nome      VARCHAR(100) NOT NULL UNIQUE,
    tipo      VARCHAR(10) NOT NULL,  -- 'entrada' | 'saida'
    descricao TEXT NULL,
    principal BOOLEAN NOT NULL DEFAULT true,
    ativo     BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Seed: Categorias reais do balancete da Renascença
INSERT INTO categorias_financeiras (nome, tipo, descricao, principal) VALUES
-- Entradas
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
-- Saídas
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
('Despesas Diversas da Loja', 'saida', 'Despesas gerais', false);

CREATE INDEX IF NOT EXISTS idx_categorias_tipo_ativo 
    ON categorias_financeiras(tipo, ativo);

-- ============================================================
-- 2. Lançamentos Financeiros (livro-caixa)
-- ============================================================
CREATE TABLE IF NOT EXISTS lancamentos_financeiros (
    id              SERIAL PRIMARY KEY,
    tipo            VARCHAR(10) NOT NULL,  -- 'entrada' | 'saida'
    categoria_id    INT NOT NULL REFERENCES categorias_financeiras(id),
    valor           NUMERIC(10,2) NOT NULL,
    data_lancamento DATE NOT NULL,
    descricao       TEXT NULL,
    obreiro_id      INT NULL REFERENCES obreiros(id),  -- para mensalidades vinculadas
    mes_ref         INT NOT NULL,   -- 1-12
    ano_ref         INT NOT NULL,
    created_by      INT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_lancamentos_mes_ano 
    ON lancamentos_financeiros(mes_ref, ano_ref);
CREATE INDEX IF NOT EXISTS idx_lancamentos_categoria 
    ON lancamentos_financeiros(categoria_id);
CREATE INDEX IF NOT EXISTS idx_lancamentos_obreiro 
    ON lancamentos_financeiros(obreiro_id);

-- ============================================================
-- 3. Comprovantes PIX Pendentes (fila de validação)
-- ============================================================
CREATE TABLE IF NOT EXISTS comprovantes_pix (
    id               SERIAL PRIMARY KEY,
    obreiro_id       INT NULL REFERENCES obreiros(id),
    telegram_user_id BIGINT NOT NULL,
    nome_telegram    VARCHAR(255) NULL,
    file_id          TEXT NOT NULL,          -- file_id do Telegram (foto/doc)
    tipo_arquivo     VARCHAR(50) NULL,       -- 'foto' | 'documento' | 'desconhecido'
    nome_arquivo     VARCHAR(255) NULL,      -- nome do arquivo (para documentos)
    descricao_usuario TEXT NULL,             -- legenda/descrição do usuário
    valor_informado  NUMERIC(10,2) NULL,
    mes_ref_informado INT NULL,              -- mês informado pelo obreiro na legenda
    ano_ref_informado INT NULL,              -- ano informado
    status           VARCHAR(20) NOT NULL DEFAULT 'pendente',  -- 'pendente' | 'aprovado' | 'rejeitado'
    motivo_rejeicao  TEXT NULL,
    validado_por     INT NULL REFERENCES obreiros(id),  -- usuario tesoureiro que validou
    valor_validado   NUMERIC(10,2) NULL,               -- valor após validação
    mes_ref_validado INT NULL,                         -- período validado
    ano_ref_validado INT NULL,
    lancamento_id    INT NULL REFERENCES lancamentos_financeiros(id),
    criado_em        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    validado_em      TIMESTAMP NULL
);

CREATE INDEX IF NOT EXISTS idx_comprovantes_status 
    ON comprovantes_pix(status);
CREATE INDEX IF NOT EXISTS idx_comprovantes_obreiro 
    ON comprovantes_pix(obreiro_id);
CREATE INDEX IF NOT EXISTS idx_comprovantes_telegram 
    ON comprovantes_pix(telegram_user_id);

-- ============================================================
-- 4. Mensalidades - Status por Obreiro/Mês
-- ============================================================
CREATE TABLE IF NOT EXISTS mensalidades_status (
    id            SERIAL PRIMARY KEY,
    obreiro_id    INT NOT NULL REFERENCES obreiros(id),
    mes_ref       INT NOT NULL,   -- 1-12
    ano_ref       INT NOT NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'pendente',  -- 'pago' | 'pendente' | 'isento' | 'dispensado'
    lancamento_id INT NULL REFERENCES lancamentos_financeiros(id),
    nota          TEXT NULL,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(obreiro_id, mes_ref, ano_ref)
);

CREATE INDEX IF NOT EXISTS idx_mensalidades_status 
    ON mensalidades_status(status);
CREATE INDEX IF NOT EXISTS idx_mensalidades_obreiro_mes 
    ON mensalidades_status(obreiro_id, mes_ref, ano_ref DESC);

-- ============================================================
-- 5. Regularidade de Obreiro (Regular / Irregular)
-- ============================================================
CREATE TABLE IF NOT EXISTS regularidade_obreiro (
    id           SERIAL PRIMARY KEY,
    obreiro_id   INT NOT NULL REFERENCES obreiros(id),
    mes_ref      INT NOT NULL,
    ano_ref      INT NOT NULL,
    status       VARCHAR(20) NOT NULL DEFAULT 'irregular',  -- 'regular' | 'irregular'
    observacao   TEXT NULL,
    definido_por INT NOT NULL REFERENCES obreiros(id),  -- tesoureiro
    definido_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(obreiro_id, mes_ref, ano_ref)
);

CREATE INDEX IF NOT EXISTS idx_regularidade_obreiro 
    ON regularidade_obreiro(obreiro_id, mes_ref, ano_ref DESC);

-- ============================================================
-- 6. Fechamento Mensal (saldo inicial/final)
-- ============================================================
CREATE TABLE IF NOT EXISTS fechamento_mensal (
    id                SERIAL PRIMARY KEY,
    mes_ref           INT NOT NULL,
    ano_ref           INT NOT NULL,
    saldo_inicial     NUMERIC(10,2) NOT NULL,  -- digitado manualmente ou sugerido
    total_entradas    NUMERIC(10,2) NOT NULL DEFAULT 0,
    total_saidas      NUMERIC(10,2) NOT NULL DEFAULT 0,
    saldo_final       NUMERIC(10,2) NOT NULL GENERATED ALWAYS AS (saldo_inicial + total_entradas - total_saidas) STORED,
    status            VARCHAR(20) NOT NULL DEFAULT 'aberto',  -- 'aberto' | 'fechado'
    fechado_por       INT NULL REFERENCES obreiros(id),
    observacoes       TEXT NULL,
    criado_em         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechado_em        TIMESTAMP NULL,
    UNIQUE(mes_ref, ano_ref)
);

CREATE INDEX IF NOT EXISTS idx_fechamento_mes_ano 
    ON fechamento_mensal(mes_ref, ano_ref);

-- ============================================================
-- 7. Auditoria de Ajustes de Saldo (trilha completa)
-- ============================================================
CREATE TABLE IF NOT EXISTS ajustes_saldo_auditoria (
    id               SERIAL PRIMARY KEY,
    fechamento_id    INT NOT NULL REFERENCES fechamento_mensal(id),
    campo_alterado   VARCHAR(50) NOT NULL,  -- 'saldo_inicial'
    valor_anterior   NUMERIC(10,2) NOT NULL,
    valor_novo       NUMERIC(10,2) NOT NULL,
    justificativa    TEXT NOT NULL,
    alterado_por      INT NOT NULL REFERENCES obreiros(id),
    alterado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_auditoria_fechamento 
    ON ajustes_saldo_auditoria(fechamento_id);

-- ============================================================
-- 8. Tronco de Solidariedade (sub-caixa separado)
-- ============================================================
CREATE TABLE IF NOT EXISTS tronco_solidariedade (
    id            SERIAL PRIMARY KEY,
    tipo          VARCHAR(10) NOT NULL,  -- 'entrada' | 'saida'
    valor         NUMERIC(10,2) NOT NULL,
    data_mov      DATE NOT NULL,
    descricao     TEXT NULL,
    sessao_ref    TEXT NULL,   -- referência à data/número da sessão
    created_by    INT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_tronco_data 
    ON tronco_solidariedade(data_mov DESC);
